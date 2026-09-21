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
                // Visual: logo de la liga si tiene, sino icono 'flag'.
                fn ($l) => $this->row($l->name, $l->short_name ?? $l->country ?? '', route('leagues.show', $l), image: $l->logo_url, fallbackIcon: 'flag', imageClass: 'bg-white p-1')
            ),

            'categories' => $this->mapSimple(
                Category::query()->where('name', 'LIKE', $like)->orWhere('slug', 'LIKE', $like)->orderBy('name')->limit(5)->get(),
                fn ($c) => $this->row($c->name, $c->slug ?? '', route('categories.show', $c), fallbackIcon: 'category')
            ),

            'tournaments' => $this->mapSimple(
                Tournament::query()->with('league')->where('name', 'LIKE', $like)->orWhere('season', 'LIKE', $like)->orderBy('name')->limit(5)->get(),
                fn ($t) => $this->row($t->name, $t->league->name ?? $t->season ?? '', route('tournaments.show', $t), image: $t->logo_url, fallbackIcon: 'emoji_events', imageClass: 'bg-white p-1')
            ),

            'teams' => $this->mapSimple(
                Team::query()->with('league')->where('name', 'LIKE', $like)->orWhere('short_name', 'LIKE', $like)->orWhere('city', 'LIKE', $like)->orderBy('name')->limit(5)->get(),
                // Visual: logo del equipo si tiene, sino icono 'stadium'.
                fn ($t) => $this->row(
                    $t->name,
                    trim(($t->short_name ? "({$t->short_name}) " : '') . ($t->league->name ?? '') . ($t->city ? " · {$t->city}" : '')),
                    route('teams.show', $t),
                    image: $t->logo_url,
                    fallbackIcon: 'stadium',
                    imageClass: 'bg-white p-1'
                )
            ),

            'athletes' => $this->mapSimple(
                // Visual: foto real del atleta si tiene, sino iniciales (primer
                // caracter de first_name + last_name). Fallback 'person'.
                Athlete::query()->with(['team', 'category'])->where('first_name', 'LIKE', $like)->orWhere('last_name', 'LIKE', $like)->orWhere('document_id', 'LIKE', $like)->orderBy('last_name')->limit(5)->get(),
                fn ($a) => $this->row(
                    $a->full_name,
                    trim(($a->team->short_name ?? $a->team->name ?? '')
                        . ($a->category?->name ? ' · ' . $a->category->name : '')
                        . ($a->number ? ' · #' . $a->number : '')),
                    route('athletes.show', $a),
                    image: $a->photoUrl,
                    initials: self::initials($a->first_name, $a->last_name),
                    fallbackIcon: 'person'
                )
            ),

            'scorekeepers' => $this->mapSimple(
                Scorekeeper::query()->where('first_name', 'LIKE', $like)->orWhere('last_name', 'LIKE', $like)->orWhere('document_id', 'LIKE', $like)->orderBy('last_name')->limit(5)->get(),
                fn ($s) => $this->row($s->full_name, $s->document_id ?? '', route('scorekeepers.show', $s), image: $s->photoUrl, initials: self::initials($s->first_name, $s->last_name), fallbackIcon: 'edit_note')
            ),

            'referees' => $this->mapSimple(
                Referee::query()->where('first_name', 'LIKE', $like)->orWhere('last_name', 'LIKE', $like)->orWhere('certification', 'LIKE', $like)->limit(5)->get(),
                fn ($r) => $this->row($r->full_name, $r->certification ?? '', route('referees.show', $r), image: $r->photoUrl, initials: self::initials($r->first_name, $r->last_name), fallbackIcon: 'sports')
            ),

            'stadiums' => $this->mapSimple(
                Stadium::query()->where('name', 'LIKE', $like)->orWhere('city', 'LIKE', $like)->orWhere('state', 'LIKE', $like)->orderBy('name')->limit(5)->get(),
                fn ($s) => $this->row($s->name, trim(($s->city ?? '') . (($s->city && $s->state) ? ', ' : '') . ($s->state ?? ''), ', ') ?: '', route('stadiums.show', $s), fallbackIcon: 'stadium')
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
     * @return Collection<int, array<string, mixed>>
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
            'image' => null,            // los juegos no tienen imagen individual
            'initials' => null,
            'fallbackIcon' => 'sports_baseball',
            'imageClass' => '',
        ]);
    }

    /**
     * Mapea una coleccion de modelos "simples" a filas estandar.
     * El callable recibe el modelo y devuelve el array completo.
     *
     * @param Collection<int, object> $items
     * @param callable(object): array<string, mixed> $mapper
     * @return Collection<int, array<string, mixed>>
     */
    private function mapSimple(Collection $items, callable $mapper): Collection
    {
        return $items->map(fn ($item) => $mapper($item));
    }

    /**
     * Construye la representacion estandar de un item de busqueda.
     *
     * Ademas de title/subtitle/url, incluye el bloque 'visual' que la
     * vista usa para renderizar el avatar/logo/icono a la izquierda
     * de cada fila:
     *   - image       URL de la imagen si la tiene (avatar/logo).
     *                 Si es null, no se renderiza <img>.
     *   - initials    Iniciales cuando hay persona sin foto.
     *                 Si es null, no se renderiza el circulo de iniciales.
     *   - fallbackIcon Glyph de material-symbols-outlined que se muestra
     *                 cuando ni image ni initials estan disponibles.
     *   - imageClass  Clases extra para el contenedor de <img>
     *                 (típicamente 'bg-white p-1' para logos que vienen
     *                 con fondo blanco y necesitan contraste).
     *
     * @return array<string, mixed>
     */
    private function row(
        string $title,
        string $subtitle,
        string $url,
        ?string $image = null,
        ?string $initials = null,
        string $fallbackIcon = 'circle',
        string $imageClass = ''
    ): array {
        return [
            'title' => $title,
            'subtitle' => $subtitle,
            'url' => $url,
            'image' => $image,
            'initials' => $initials,
            'fallbackIcon' => $fallbackIcon,
            'imageClass' => $imageClass,
        ];
    }

    /**
     * Iniciales para placeholder de avatar: primer caracter del
     * nombre + primer caracter del apellido, en mayusculas.
     * Funciona con multibyte (UTF-8): 'n' = 0xC3 0xB1, etc.
     */
    private static function initials(?string $first, ?string $last): string
    {
        $f = mb_strtoupper(mb_substr((string) $first, 0, 1, 'UTF-8'), 'UTF-8');
        $l = mb_strtoupper(mb_substr((string) $last, 0, 1, 'UTF-8'), 'UTF-8');
        return $f . $l;
    }
}