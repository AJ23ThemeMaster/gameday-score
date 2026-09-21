<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Athlete;
use App\Models\Category;
use App\Models\Game;
use App\Models\League;
use App\Models\Referee;
use App\Models\Scorekeeper;
use App\Models\Stadium;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Busqueda global del sistema.
 *
 * GET /search?q=... consulta simultaneamente 9 modelos del sistema
 * (juegos, ligas, categorias, torneos, equipos, atletas, anotadores,
 * arbitros, estadios) y devuelve una vista con los resultados agrupados
 * por tipo. Cada item trae title (mostrado principal), subtitle
 * (secundario) y url (donde navegar al hacer click).
 *
 * Limites por tipo:
 *  - 5 resultados como maximo por recurso (suficiente para una vista
 *    de resultados; si el usuario necesita mas, que refine la query).
 *  - Minimo 2 caracteres para evitar consultas ruidosas.
 *
 * Esta accion es accesible para cualquier usuario autenticado. La
 * autorizacion fina por recurso (no mostrar ligas privadas, etc.)
 * queda delegada a las policies existentes que las vistas show
 * ya respetan.
 */
class SearchController extends Controller
{
    public function search(Request $request): View
    {
        $q = trim((string) $request->input('q', ''));

        if (mb_strlen($q) < 2) {
            return view('search.results', [
                'query' => $q,
                'results' => collect(),
                'tooShort' => true,
            ]);
        }

        $like = '%' . $q . '%';

        $results = [
            'games' => $this->mapGames(Game::query()->with(['homeTeam', 'awayTeam', 'category'])
                ->where(function ($qb) use ($like) {
                    $qb->where('notes', 'LIKE', $like)
                        ->orWhereHas('homeTeam', fn ($t) => $t->where('name', 'LIKE', $like)->orWhere('short_name', 'LIKE', $like))
                        ->orWhereHas('awayTeam', fn ($t) => $t->where('name', 'LIKE', $like)->orWhere('short_name', 'LIKE', $like));
                })
                ->orderByDesc('scheduled_at')
                ->limit(5)
                ->get()),

            'leagues' => $this->mapSimple(
                League::query()->where('name', 'LIKE', $like)->orWhere('short_name', 'LIKE', $like)->orderBy('name')->limit(5)->get(),
                fn ($l) => $this->row($l->name, $l->short_name ?? $l->country ?? '', route('leagues.show', $l))
            ),

            'categories' => $this->mapSimple(
                Category::query()->where('name', 'LIKE', $like)->orWhere('slug', 'LIKE', $like)->orderBy('name')->limit(5)->get(),
                fn ($c) => $this->row($c->name, $c->slug ?? '', route('categories.show', $c))
            ),

            'tournaments' => $this->mapSimple(
                Tournament::query()->with('league')->where('name', 'LIKE', $like)->orWhere('season', 'LIKE', $like)->orderBy('name')->limit(5)->get(),
                fn ($t) => $this->row($t->name, $t->league->name ?? $t->season ?? '', route('tournaments.show', $t))
            ),

            'teams' => $this->mapSimple(
                Team::query()->with('league')->where('name', 'LIKE', $like)->orWhere('short_name', 'LIKE', $like)->orWhere('city', 'LIKE', $like)->orderBy('name')->limit(5)->get(),
                fn ($t) => $this->row(
                    $t->name,
                    trim(($t->short_name ? "({$t->short_name}) " : '') . ($t->league->name ?? '') . ($t->city ? " · {$t->city}" : '')),
                    route('teams.show', $t)
                )
            ),

            'athletes' => $this->mapSimple(
                // DISI-N: el subtitle del atleta ahora muestra Equipo - Categoria -
                // Numero (antes solo Equipo - Numero). Cargamos la relacion
                // category ademas de team para evitar N+1.
                Athlete::query()->with(['team', 'category'])->where('first_name', 'LIKE', $like)->orWhere('last_name', 'LIKE', $like)->orWhere('document_id', 'LIKE', $like)->orderBy('last_name')->limit(5)->get(),
                fn ($a) => $this->row(
                    $a->full_name,
                    trim(($a->team->short_name ?? $a->team->name ?? '')
                        . ($a->category?->name ? ' · ' . $a->category->name : '')
                        . ($a->number ? ' · #' . $a->number : '')),
                    route('athletes.show', $a)
                )
            ),

            'scorekeepers' => $this->mapSimple(
                Scorekeeper::query()->where('first_name', 'LIKE', $like)->orWhere('last_name', 'LIKE', $like)->orWhere('document_id', 'LIKE', $like)->orderBy('last_name')->limit(5)->get(),
                fn ($s) => $this->row($s->full_name, $s->document_id ?? '', route('scorekeepers.show', $s))
            ),

            'referees' => $this->mapSimple(
                Referee::query()->where('first_name', 'LIKE', $like)->orWhere('last_name', 'LIKE', $like)->orWhere('certification', 'LIKE', $like)->limit(5)->get(),
                fn ($r) => $this->row($r->full_name, $r->certification ?? '', route('referees.show', $r))
            ),

            'stadiums' => $this->mapSimple(
                Stadium::query()->where('name', 'LIKE', $like)->orWhere('city', 'LIKE', $like)->orWhere('state', 'LIKE', $like)->orderBy('name')->limit(5)->get(),
                fn ($s) => $this->row($s->name, trim(($s->city ?? '') . (($s->city && $s->state) ? ', ' : '') . ($s->state ?? ''), ', ') ?: '', route('stadiums.show', $s))
            ),
        ];

        $results = collect($results)->filter(fn (Collection $items) => $items->isNotEmpty());

        return view('search.results', [
            'query' => $q,
            'results' => $results,
            'tooShort' => false,
        ]);
    }

    /**
     * Mapea una coleccion de Games a filas estandar (title, subtitle, url).
     * Necesita formato especial porque el "title" de un juego es el
     * enfrentamiento (homeTeam vs awayTeam), no su ID.
     *
     * @return Collection<int, array{title:string,subtitle:string,url:string}>
     */
    private function mapGames(Collection $games): Collection
    {
        return $games->map(fn (Game $g) => [
            'title' => ($g->homeTeam->short_name ?? $g->homeTeam->name) . ' vs ' . ($g->awayTeam->short_name ?? $g->awayTeam->name),
            'subtitle' => trim(
                ($g->category->name ?? '')
                . ($g->scheduled_at ? ' · ' . $g->scheduled_at->format('d/m/Y') : '')
                . ($g->stadium?->name ? ' · ' . $g->stadium->name : '')
            ),
            'url' => route('games.show', $g),
        ]);
    }

    /**
     * Mapea una coleccion de modelos "simples" (con nombre y un ID)
     * a filas estandar. El callable recibe el modelo y devuelve [title, subtitle, url].
     *
     * @param Collection<int, object> $items
     * @param callable(object): array{title:string,subtitle:string,url:string} $mapper
     * @return Collection<int, array{title:string,subtitle:string,url:string}>
     */
    private function mapSimple(Collection $items, callable $mapper): Collection
    {
        return $items->map(fn ($item) => $mapper($item));
    }

    private function row(string $title, string $subtitle, string $url): array
    {
        return ['title' => $title, 'subtitle' => $subtitle, 'url' => $url];
    }
}