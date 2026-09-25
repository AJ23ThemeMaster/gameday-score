/**
 * Alpine.js stores y data components para Gameday Score.
 * Se carga como fallback mientras el build de Vite no se regenera
 * (Node 22.11.0 vs Vite 8 que requiere 22.12+). Bypass completo de
 * @vite: este archivo se sirve directo desde public/js/.
 *
 * Cuando se arregle el build, este contenido debe moverse a resources/js/
 * y los componentes/store deben re-registrarse en app.js.
 */

// Toast stack: notificaciones globales success/error/warning/info
document.addEventListener('alpine:init', () => {
    window.Alpine.data('toastStack', () => ({
        items: [],
        nextId: 1,
        show(message, level = 'success', timeout = 3500) {
            const id = this.nextId++;
            this.items.push({ id, message, level, visible: false });
            this.$nextTick(() => {
                const item = this.items.find((t) => t.id === id);
                if (item) item.visible = true;
            });
            if (timeout > 0) {
                setTimeout(() => this.dismiss(id), timeout);
            }
            return id;
        },
        dismiss(id) {
            const item = this.items.find((t) => t.id === id);
            if (!item) return;
            item.visible = false;
            setTimeout(() => {
                this.items = this.items.filter((t) => t.id !== id);
            }, 200);
        },
        clear() {
            this.items.forEach((t) => (t.visible = false));
            setTimeout(() => (this.items = []), 200);
        },
    }));

    // Roster store: maneja add/update/remove/substitute via fetch
    window.Alpine.store('roaster', {
        csrf: document.querySelector('meta[name="csrf-token"]')?.content || '',
        gameId: null,

        // Replace roster section after successful action
        replaceRoster(html) {
            const target = document.getElementById('roster-content');
            if (target && html) {
                target.outerHTML = html;
            }
        },

        // Toast helper
        toast(message, level = 'success') {
            window.dispatchEvent(
                new CustomEvent('toast', { detail: { message, level } })
            );
        },

        // Helper interno: fetch con headers AJAX + CSRF + Accept JSON.
        // body puede ser FormData o URLSearchParams.
        async _fetch(url, method, body) {
            const headers = {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': this.csrf,
                'Accept': 'application/json',
            };
            // No forzar Content-Type cuando es FormData (el browser pone el boundary)
            if (!(body instanceof FormData)) {
                headers['Content-Type'] = 'application/x-www-form-urlencoded';
            }
            try {
                const res = await fetch(url, {
                    method,
                    body,
                    headers,
                    credentials: 'same-origin',
                });
                const data = await res.json().catch(() => ({}));
                return { ok: res.ok, status: res.status, data };
            } catch (e) {
                this.toast('Error de red: ' + e.message, 'error');
                return { ok: false, status: 0, data: { message: e.message } };
            }
        },

        // Modal dispatchers
        openAdd(teamId, teamName, available) {
            window.dispatchEvent(new CustomEvent('open-add-modal', {
                detail: { teamId, teamName, available }
            }));
        },
        openSubstitute(outId, outName, teamId, teamName, available, lineupOrder, position, isPitcher) {
            window.dispatchEvent(new CustomEvent('open-substitute-modal', {
                detail: {
                    outAthleteId: outId, outAthleteName: outName,
                    teamId, teamName, available,
                    currentLineupOrder: lineupOrder, currentPosition: position,
                    isPitcher,
                }
            }));
        },

        // Update single field (lineup_order, position, is_pitcher, pitches_thrown)
        async updateField(inputEl) {
            const athleteId = inputEl.dataset.athleteId;
            const field = inputEl.dataset.rosterField;
            const value = inputEl.value;
            const url = `/games/${this.gameId}/roster/${athleteId}`;
            const fd = new FormData();
            fd.append('_method', 'PATCH');
            fd.append('_token', this.csrf);
            fd.append(field, value);
            const { ok, data } = await this._fetch(url, 'POST', fd);
            if (ok && data.html) {
                this.replaceRoster(data.html);
                this.toast(data.message || 'Actualizado', 'success');
            } else {
                this.toast(data.message || 'Error al actualizar', 'error');
            }
        },

        // Toggle pitcher (★/☆ button)
        async togglePitcher(btn) {
            const athleteId = btn.dataset.athleteId;
            const current = btn.dataset.current === '1';
            const url = `/games/${this.gameId}/roster/${athleteId}`;
            const fd = new FormData();
            fd.append('_method', 'PATCH');
            fd.append('_token', this.csrf);
            fd.append('is_pitcher', current ? '0' : '1');
            const { ok, data } = await this._fetch(url, 'POST', fd);
            if (ok && data.html) {
                this.replaceRoster(data.html);
                this.toast(data.message || 'Lanzador actualizado', 'success');
            } else {
                this.toast(data.message || 'Error', 'error');
            }
        },

        // Confirm remove athlete from roster (SweetAlert2 via SwalHelper)
        async confirmRemove(btn) {
            const name = btn.dataset.athleteName;
            const id = btn.dataset.athleteId;
            const ok = await window.SwalHelper.confirm({
                title: `¿Quitar a «${name}» del roster?`,
                icon: 'warning',
                danger: true,
                confirmText: 'Sí, quitar',
            });
            if (!ok) return;
            this.removeAthlete(id);
        },

        async removeAthlete(athleteId) {
            const url = `/games/${this.gameId}/roster/${athleteId}`;
            const fd = new FormData();
            fd.append('_method', 'DELETE');
            fd.append('_token', this.csrf);
            const { ok, data } = await this._fetch(url, 'POST', fd);
            if (ok && data.html) {
                this.replaceRoster(data.html);
                this.toast(data.message || 'Atleta removido', 'success');
            } else {
                this.toast(data.message || 'Error al remover', 'error');
            }
        },

        // Submit add form (modal) via fetch
        async submitAddForm(form) {
            const url = form.action;
            const fd = new FormData(form);
            const { ok, data } = await this._fetch(url, 'POST', fd);
            if (ok && data.html) {
                this.replaceRoster(data.html);
                this.toast(data.message || 'Atleta agregado', 'success');
                window.dispatchEvent(new CustomEvent('close-add-modal'));
                form.reset();
            } else {
                this.toast(data.message || 'Error al agregar', 'error');
            }
        },

        // Submit substitute form (modal) via fetch
        async submitSubstituteForm(form) {
            const url = form.action;
            const fd = new FormData(form);
            const { ok, data } = await this._fetch(url, 'POST', fd);
            if (ok && data.html) {
                this.replaceRoster(data.html);
                this.toast(data.message || 'Sustitución realizada', 'success');
                window.dispatchEvent(new CustomEvent('close-substitute-modal'));
                form.reset();
            } else {
                this.toast(data.message || 'Error en la sustitución', 'error');
            }
        },
    });

    // Scoreboard V2 component: vista paralela experimental del scoreboard.
    // Reusa los mismos endpoints que el scoreboard base (pitch, end-inning,
    // end-game) y mantiene el state reactivo en Alpine sin recargar la
    // pagina. Despues de cada accion, captura el state del response JSON
    // y lo aplica via applyState() para actualizar score, outs, bases,
    // inning/half, pitcher, batter y on-deck en vivo.
    //
    // Modales soportados (paridad con el scoreboard base):
    //  - strike: 3 subtipos (Mirando / Swing / Foul Tip)
    //  - out-step1: 4 tipos (Fly / Linea / Roletazo / De reglamento)
    //  - out-step2: secuencia defensiva (9 fildeadores)
    //  - hit: confirmacion con preview del resultado
    //  - bunt: 2 subtipos (Toque de sacrificio / Bunt single)
    //  - end-inning / end-game: confirmacion
    window.Alpine.data('scoreboardV2App', (config) => ({
        pitchUrl: config.pitchUrl,
        endInningUrl: config.endInningUrl,
        endGameUrl: config.endGameUrl,
        csrf: config.csrf,
        isFinalized: config.isFinalized ?? false,
        gameStatus: config.gameStatus ?? 'scheduled',
        tab: 'pitch',
        busy: false,
        modal: null,            // 'strike' | 'out-step1' | 'out-step2' | 'hit' | 'bunt' | 'end-inning' | 'end-game' | null
        outType: null,           // 'fly' | 'line' | 'ground' | 'reglamento' | null
        defensiveSequence: [],   // ['P', 'C', '1B', ...] en orden de fildeadores
        hitSubtype: null,        // 'single' | 'double' | 'triple' | 'hr' | 'inside_park' | null
        hitConfig: { label: '', description: '', preview: '' },

        // ===== STATE reactivo (se inicializa desde el server, se actualiza via applyState) =====
        // Score
        homeRuns: config.homeRuns ?? 0,
        awayRuns: config.awayRuns ?? 0,
        homeHits: config.homeHits ?? 0,
        awayHits: config.awayHits ?? 0,
        homeErrors: config.homeErrors ?? 0,
        awayErrors: config.awayErrors ?? 0,
        // Inning/half/count/outs
        inning: config.inning ?? 1,
        half: config.half ?? 'top',  // 'top' | 'bottom'
        balls: config.balls ?? 0,
        strikes: config.strikes ?? 0,
        outs: config.outs ?? 0,
        // Bases (athleteId en cada base, o null)
        base1: config.base1 ?? null,
        base2: config.base2 ?? null,
        base3: config.base3 ?? null,
        // Line score (carreras por inning de cada equipo)
        lineScore: config.lineScore ?? {},
        // Players
        pitcher: config.pitcher ?? null,    // { id, name, number, position, initials }
        pitcherStats: config.pitcherStats ?? { pitches: 0, strikes: 0, balls: 0, strikeouts: 0, hits: 0, walks: 0 },
        batter: config.batter ?? null,
        batterStats: config.batterStats ?? { at_bats: 0, hits: 0, strikeouts: 0, walks: 0, avg: 0 },
        onDeck: config.onDeck ?? null,
        // Identidad de equipos y contexto del juego (pasado desde la vista)
        homeShort: config.homeShort ?? '',
        awayShort: config.awayShort ?? '',
        homeLogoUrl: config.homeLogoUrl ?? null,
        awayLogoUrl: config.awayLogoUrl ?? null,
        homeColor: config.homeColor ?? null,
        awayColor: config.awayColor ?? null,
        categoryName: config.categoryName ?? '',
        stadiumName: config.stadiumName ?? '',
        // Modal "Gestionar corredor" (DISI-20 migrado al v2): base seleccionada.
        runnerBase: null, // 'first' | 'second' | 'third' | null
        runnerActionBusy: false,
        // URL del endpoint de acciones de corredor (avanzar, robar, anotar, etc.)
        runnerUrl: config.runnerUrl,
        // URLs de los EXTRAS del scoreboard base migrados al v2.
        substituteUrl: config.substituteUrl,
        lineupReorderUrl: config.lineupReorderUrl,
        statsUrl: config.statsUrl,
        // Rosters por equipo (athletes con lineup_order del pivot) — alimentar
        // los dropdowns del modal Sustituir (misma estructura que el scoreboard base).
        rosterAway: config.rosterAway || [],
        rosterHome: config.rosterHome || [],
        // Modal Sustituir (migrado exacto del scoreboard base: dropdowns del roster).
        subKind: 'pitcher', // 'pitcher' | 'batter' | 'pr'
        subInId: '',        // athlete id entrante (string vacio = sin seleccionar)
        subBase: 'first',   // base del corredor saliente (solo si subKind === 'pr')
        subBusy: false,
        subError: '',
        // Modal resumen tras finalizar inning (DISI-218 migrado al v2): el
        // pitch endpoint devuelve data.summary con {inning, half, runs, hits,
        // walks, strikeouts, errors, lob, pitcher_id, pitcher_name, pitcher_pitches}.
        inningSummary: null,
        // Modal Reordenar lineup (drag/drop local + PATCH al lineupReorderUrl).
        lineupBusy: false,
        lineupError: '',
        // Modal Stats del juego (carga statsUrl como JSON).
        statsLoading: false,
        statsError: '',
        statsData: null,
        statsTab: 'batting', // 'batting' | 'pitching'
        statsFilter: 'all', // 'home' | 'away' | 'all'
        // Identidad de los equipos del juego (necesaria para los filtros del box score).
        homeTeamId: config.homeTeamId ?? null,
        awayTeamId: config.awayTeamId ?? null,

        toast(message, level = 'success') {
            window.dispatchEvent(
                new CustomEvent('toast', { detail: { message, level, timeout: 3500 } })
            );
        },

        closeModal() {
            this.modal = null;
            this.outType = null;
            this.defensiveSequence = [];
            this.hitSubtype = null;
            this.hitConfig = { label: '', description: '', preview: '' };
        },

        // ===== applyState: actualiza todo el state reactivo desde la respuesta AJAX =====
        applyState(payload) {
            if (!payload) return;
            // Score
            if (payload.score) {
                this.homeRuns = payload.score.home ?? this.homeRuns;
                this.awayRuns = payload.score.away ?? this.awayRuns;
                this.homeHits = payload.score.home_hits ?? this.homeHits;
                this.awayHits = payload.score.away_hits ?? this.awayHits;
                this.homeErrors = payload.score.home_errors ?? this.homeErrors;
                this.awayErrors = payload.score.away_errors ?? this.awayErrors;
                this.lineScore = payload.score.line ?? this.lineScore;
            }
            // State (inning/half/count/outs/bases)
            if (payload.state) {
                const s = payload.state;
                this.inning = s.inning ?? this.inning;
                this.half = s.half ?? this.half;
                this.balls = s.balls ?? this.balls;
                this.strikes = s.strikes ?? this.strikes;
                this.outs = s.outs ?? this.outs;
                // Si el payload trae `bases` con atletas completos (caso normal
                // desde PlayController::pitch), los usamos directamente. Esto
                // garantiza que cuando un bateador se embasa o un corredor
                // avanza, el diamante del v2 muestre el nombre + dorsal nuevo
                // sin recargar la pagina.
                if (payload.bases && typeof payload.bases === 'object') {
                    // Normalizar: si el server mando el placeholder Play::ANON_RUNNER
                    // ('corredor') en vez de un athlete object, convertirlo a un
                    // objeto minimo para que la UI muestre "Corredor" en la base.
                    const normalize = (v) => {
                        if (v === null || v === undefined) return null;
                        if (typeof v === 'object') return v;
                        if (v === 'corredor' || v === 'runner') {
                            return { id: 'corredor', name: 'Corredor', number: '?', initials: '?' };
                        }
                        return null;
                    };
                    this.base1 = normalize(payload.bases.first);
                    this.base2 = normalize(payload.bases.second);
                    this.base3 = normalize(payload.bases.third);
                } else if (payload.runners && typeof payload.runners === 'object') {
                    // Fallback: el endpoint /poll del base usa 'runners' en vez de
                    // 'bases'. Mapeamos para que el v2 actualice sin re-fetch.
                    this.base1 = payload.runners.first ?? null;
                    this.base2 = payload.runners.second ?? null;
                    this.base3 = payload.runners.third ?? null;
                } else {
                    // Fallback: el payload solo trae IDs (state.bases). Conservamos
                    // el objeto cacheado si coincide con el ID, si no, queda el ID
                    // solo (la UI mostrara un valor degradado hasta el proximo poll).
                    const bases = s.bases || {};
                    const resolveBase = (current, newVal) => {
                        if (newVal === null || newVal === undefined) return null;
                        if (typeof newVal === 'object') return newVal;
                        if (current && current.id === newVal) return current;
                        return newVal;
                    };
                    this.base1 = resolveBase(this.base1, bases.first);
                    this.base2 = resolveBase(this.base2, bases.second);
                    this.base3 = resolveBase(this.base3, bases.third);
                }
            }
            // Players (objetos simples con id/name/number/position/initials)
            if (payload.pitcher !== undefined) this.pitcher = payload.pitcher;
            if (payload.batter !== undefined) this.batter = payload.batter;
            if (payload.on_deck !== undefined) this.onDeck = payload.on_deck;
            if (payload.pitcher_stats) {
                this.pitcherStats = {
                    pitches: payload.pitcher_stats.pitches ?? 0,
                    strikes: payload.pitcher_stats.strikes ?? 0,
                    balls: payload.pitcher_stats.balls ?? 0,
                    strikeouts: payload.pitcher_stats.strikeouts ?? 0,
                    hits: payload.pitcher_stats.hits ?? 0,
                    walks: payload.pitcher_stats.walks ?? 0,
                };
            }
            if (payload.batter_stats) {
                this.batterStats = {
                    at_bats: payload.batter_stats.at_bats ?? 0,
                    hits: payload.batter_stats.hits ?? 0,
                    strikeouts: payload.batter_stats.strikeouts ?? 0,
                    walks: payload.batter_stats.walks ?? 0,
                    avg: payload.batter_stats.avg ?? 0,
                };
            }
            // Game-level flags
            if (payload.is_completed !== undefined) {
                this.isFinalized = payload.is_completed;
                if (payload.is_completed) this.gameStatus = 'completed';
            }
        },

        // Helpers para el template
        isHomeBatting() { return this.half === 'bottom'; },
        isAwayBatting() { return this.half === 'top'; },
        halfLabel() { return this.half === 'top' ? 'TOP' : 'BOTTOM'; },
        initials(name) {
            if (!name) return '?';
            const parts = name.trim().split(/\s+/);
            return (parts[0]?.[0] || '').toUpperCase() + (parts[parts.length - 1]?.[0] || '').toUpperCase();
        },
        fmtRuns(n) { return n ?? 0; },
        fmtAvg(n) { return Number(n ?? 0).toFixed(3).replace(/^0/, ''); },
        strikePct() {
            const total = Math.max(1, this.pitcherStats.pitches || 1);
            return Math.round(((this.pitcherStats.strikes || 0) / total) * 100);
        },
        outsArr() {
            return [0, 1, 2].map(i => i < this.outs);
        },
        onFirst() { return this.base1 !== null && this.base1 !== undefined; },
        onSecond() { return this.base2 !== null && this.base2 !== undefined; },
        onThird() { return this.base3 !== null && this.base3 !== undefined; },

        // ===== Helpers para el shell del scoreboard (header + team cards + diamond + outs) =====
        outsLabel() {
            return this.outs + ' ' + (this.outs === 1 ? 'out' : 'outs');
        },
        categoryStadium() {
            return (this.categoryName || '—') + ' · ' + (this.stadiumName || '—');
        },
        inningCols() {
            return Math.max(9, Number(this.inning || 1));
        },
        inningLineStyle() {
            return `grid-template-columns: 1fr repeat(${this.inningCols()}, minmax(2rem, 1fr)) 1fr`;
        },
        lineInnings() {
            return Array.from({ length: this.inningCols() }, (_, i) => i + 1);
        },
        lineCellClass(i) {
            const cell = (this.lineScore || {})[i] || {};
            const isCurrent = i === this.inning && !cell.final;
            const isFinal = !!cell.final;
            const parts = [];
            if (isCurrent) parts.push('is-active');
            if (isFinal) parts.push('is-r');
            return parts.join(' ');
        },
        lineCellText(i) {
            const cell = (this.lineScore || {})[i] || {};
            if (cell.final) return String(cell.away ?? 0);
            if (i === this.inning) return '●';
            return '·';
        },
        outsDotStyle(i) {
            const filled = i < this.outs;
            const bg = filled ? '#ef4444' : 'transparent';
            return `background: ${bg}; border-color: #ef4444;`;
        },
        // Nombres de los corredores en base para mostrar en las bases del diamante.
        // Cada base (base1/base2/base3) puede contener un athlete array {id, name, number, ...}
        // o null. Devuelve un objeto {first, second, third} con los nombres o ''.
        runnersLabel() {
            return {
                first: this.base1?.name ?? '',
                second: this.base2?.name ?? '',
                third: this.base3?.name ?? '',
            };
        },
        // Numero del corredor en la base (para mostrar en el tag amarillo).
        runnersNumber() {
            return {
                first: this.base1?.number ?? '',
                second: this.base2?.number ?? '',
                third: this.base3?.number ?? '',
            };
        },
        // Etiqueta legible del estado del juego.
        gameStatusLabel() {
            const map = {
                scheduled: 'Programado',
                in_progress: 'En vivo',
                paused: 'Pausado',
                completed: 'Completado',
                finalized: 'Finalizado',
                suspended: 'Suspendido',
                cancelled: 'Cancelado',
            };
            return map[this.gameStatus] ?? this.gameStatus;
        },

        // ===== STRIKE =====
        openStrikeModal() { this.modal = 'strike'; },
        async sendStrike(subtype) {
            this.closeModal();
            await this.sendPitch('strike', subtype);
        },

        // ===== OUT =====
        openOutStep1() { this.modal = 'out-step1'; },
        openOutStep2(type) {
            this.outType = type;
            this.modal = 'out-step2';
        },
        addFielder(pos) {
            if (this.defensiveSequence.includes(pos)) {
                this.defensiveSequence = this.defensiveSequence.filter(p => p !== pos);
            } else {
                this.defensiveSequence.push(pos);
            }
        },
        removeFielder(i) {
            this.defensiveSequence.splice(i, 1);
        },
        isFielderSelected(pos) {
            return this.defensiveSequence.includes(pos);
        },
        async confirmOut() {
            // Reglas: out de reglamento NO lleva secuencia; los demas SI.
            const seq = this.outType === 'reglamento' ? [] : [...this.defensiveSequence];
            this.closeModal();
            // El endpoint pitch acepta defensive_sequence como array.
            // Usamos el metodo generico con el field defensive_sequence[].
            await this.sendPitchWithSequence(this.outType === 'reglamento' ? 'out' : 'out', null, seq);
        },

        // ===== HIT =====
        openHitModal(subtype) {
            this.hitSubtype = subtype;
            const labels = {
                single: { label: 'Sencillo (1B)', description: 'El bateador llega a primera base.', preview: 'Bateador a 1B.' },
                double: { label: 'Doble (2B)', description: 'El bateador llega a segunda base.', preview: 'Bateador a 2B.' },
                triple: { label: 'Triple (3B)', description: 'El bateador llega a tercera base.', preview: 'Bateador a 3B.' },
                hr: { label: 'Home Run', description: 'El bateador recorre todas las bases.', preview: 'Carrera anotada.' },
                inside_park: { label: 'HR de pierna', description: 'Home run sin que la pelota salga del parque.', preview: 'Carrera anotada.' },
            };
            this.hitConfig = labels[subtype] || { label: 'Hit', description: '', preview: '' };
            this.modal = 'hit';
        },
        async confirmHit() {
            const subtype = this.hitSubtype;
            this.closeModal();
            await this.sendPitch('hit', subtype);
        },

        // ===== BUNT =====
        openBuntModal() { this.modal = 'bunt'; },
        async sendBunt(subtype) {
            this.closeModal();
            await this.sendPitch('bunt', subtype);
        },

        // ===== END INNING / END GAME =====
        openEndInningModal() { this.modal = 'end-inning'; },
        async confirmEndInning() {
            this.closeModal();
            await this.endInning();
        },
        openEndGameModal() { this.modal = 'end-game'; },
        async confirmEndGame() {
            this.closeModal();
            await this.endGame();
        },

        // ===== MODAL: Gestionar corredor (DISI-20 migrado al v2) =====
        // Al hacer click sobre una base ocupada en el diamante se abre un
        // modal con las acciones tipicas del anotador sobre ese corredor:
        // avanzar, robo de base, anotarse (con/sin RBI), wild pitch, etc.
        baseLabel(base) {
            switch (base) {
                case 'first': return '1RA BASE';
                case 'second': return '2DA BASE';
                case 'third': return '3RA BASE';
                default: return '';
            }
        },
        // Devuelve el atleta asignado a una base (first/second/third) o null.
        baseAthlete(base) {
            switch (base) {
                case 'first': return this.base1;
                case 'second': return this.base2;
                case 'third': return this.base3;
                default: return null;
            }
        },
        openRunnerModal(base) {
            if (!base || !['first', 'second', 'third'].includes(base)) return;
            const athlete = this.baseAthlete(base);
            if (!athlete) {
                this.toast('No hay corredor en ' + this.baseLabel(base), 'warning');
                return;
            }
            this.runnerBase = base;
            this.modal = 'runner';
        },
        closeRunnerModal() {
            this.runnerBase = null;
            this.modal = null;
        },
        // Atleta del corredor seleccionado (para mostrar en el header del modal).
        runnerModalRunner() {
            return this.runnerBase ? this.baseAthlete(this.runnerBase) : null;
        },
        runnerModalTitle() {
            return this.runnerBase ? this.baseLabel(this.runnerBase) : '';
        },
        // Accion disabled si la base es 3B (no se puede anotar desde 3B).
        isRunnerOnThird() {
            return this.runnerBase === 'third';
        },
        async sendRunnerAction(action) {
            if (this.busy || this.runnerActionBusy) return;
            if (!this.runnerBase) return;
            const base = this.runnerBase;
            this.busy = true;
            this.runnerActionBusy = true;
            try {
                const fd = new FormData();
                fd.append('base', base);
                fd.append('action', action);
                fd.append('_token', this.csrf);
                const res = await fetch(this.runnerUrl, {
                    method: 'POST',
                    body: fd,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    this.toast(data.message || data.error || 'Error al ejecutar la accion', 'error');
                    return;
                }
                // El endpoint runnerAction devuelve {success, result:{...}}. El
                // engine devuelve el state actualizado en $result. Como runner
                // action no construye snapshot UI completo, recargamos via poll
                // para refrescar bases + score + roster en una sola pasada.
                // Pero antes intentamos aplicar el state si viene incluido.
                if (data.state) {
                    this.applyState(data);
                } else {
                    // Forzar refresh completo via poll (mejor UX que reload).
                    await this.refreshSnapshot();
                }
                this.toast('Accion registrada', 'success');
                this.closeRunnerModal();
            } catch (e) {
                this.toast('Error de red: ' + e.message, 'error');
            } finally {
                this.busy = false;
                this.runnerActionBusy = false;
            }
        },

        // ===== EXTRAS migrados del scoreboard base =====

        openLineupModal() {
            this.lineupError = '';
            this.modal = 'lineup';
        },
        async submitLineupReorder(order) {
            // order = [athleteId, athleteId, ...] en el orden deseado.
            if (this.busy || this.lineupBusy) return;
            this.lineupBusy = true;
            this.lineupError = '';
            try {
                const fd = new FormData();
                order.forEach((id, idx) => fd.append('order[' + idx + ']', id));
                fd.append('_token', this.csrf);
                const res = await fetch(this.lineupReorderUrl, {
                    method: 'POST',
                    body: fd,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-HTTP-Method-Override': 'PATCH',
                    },
                    credentials: 'same-origin',
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    this.lineupError = data.message || data.error || 'Error al reordenar';
                    return;
                }
                this.toast('Lineup actualizado', 'success');
                this.closeModal();
                await this.refreshSnapshot();
            } catch (e) {
                this.lineupError = 'Error de red: ' + e.message;
            } finally {
                this.lineupBusy = false;
            }
        },

        async openStatsModal() {
            this.modal = 'stats';
            this.statsTab = 'batting';
            this.statsFilter = 'all';
            this.statsError = '';
            await this.loadStats();
        },
        async loadStats() {
            this.statsLoading = true;
            this.statsError = '';
            try {
                const res = await fetch(this.statsUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                this.statsData = await res.json();
            } catch (e) {
                this.statsError = 'Error al cargar stats: ' + e.message;
            } finally {
                this.statsLoading = false;
            }
        },
        filteredBatting() {
            if (!this.statsData || !this.statsData.batting) return [];
            if (this.statsFilter === 'all') return this.statsData.batting;
            const tid = this.statsFilter === 'home' ? this.homeTeamId : this.awayTeamId;
            return this.statsData.batting.filter(b => b.team_id === tid);
        },
        filteredPitching() {
            if (!this.statsData || !this.statsData.pitching) return [];
            if (this.statsFilter === 'all') return this.statsData.pitching;
            const tid = this.statsFilter === 'home' ? this.homeTeamId : this.awayTeamId;
            return this.statsData.pitching.filter(p => p.team_id === tid);
        },
        lineScoreAway() {
            if (!this.statsData || !this.statsData.line_score) return [];
            return Object.values(this.statsData.line_score.away || {});
        },
        lineScoreHome() {
            if (!this.statsData || !this.statsData.line_score) return [];
            return Object.values(this.statsData.line_score.home || {});
        },
        lineScoreTotal(team) {
            if (!this.statsData || !this.statsData.line_score) return 0;
            const arr = this.statsData.line_score[team] || {};
            return Object.values(arr).reduce((a, b) => a + b, 0);
        },
        formatAvg(avg) {
            if (avg === 0 || avg === '0' || avg === null || avg === undefined) return '.000';
            const num = parseFloat(avg);
            if (isNaN(num) || num === 0) return '.000';
            return '.' + Math.round(num * 1000).toString().padStart(3, '0');
        },

        // ===== SEND (genericos) =====
        async sendPitch(type, subtype = null) {
            return this.sendPitchWithSequence(type, subtype, null);
        },

        // Refresca el snapshot completo desde el endpoint /poll (usado cuando
        // una respuesta AJAX no incluye el state fresco, p.ej. runnerAction).
        async refreshSnapshot() {
            if (!this.pollUrl) return;
            try {
                const res = await fetch(this.pollUrl, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });
                if (!res.ok) return;
                const data = await res.json();
                this.applyState(data);
            } catch (e) {
                console.warn('refreshSnapshot failed', e);
            }
        },

        async sendPitchWithSequence(type, subtype = null, defensiveSequence = null) {
            if (this.busy) return;
            this.busy = true;
            try {
                const fd = new FormData();
                fd.append('type', type);
                if (subtype) fd.append('subtype', subtype);
                if (defensiveSequence && defensiveSequence.length) {
                    defensiveSequence.forEach(p => fd.append('defensive_sequence[]', p));
                }
                fd.append('_token', this.csrf);
                const res = await fetch(this.pitchUrl, {
                    method: 'POST',
                    body: fd,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    this.toast(data.message || 'Error al registrar la jugada', 'error');
                    return;
                }
                // Aplica el state del response INMEDIATAMENTE (sin recargar):
                // outs, balls, strikes, bases, inning/half, score, pitcher,
                // batter, on-deck. El servidor devuelve { state, score,
                // pitcher, batter, on_deck, pitcher_stats, batter_stats,
                // is_completed } en la respuesta JSON.
                this.applyState(data);
                // DISI-218: si el inning termino naturalmente (3 outs) y el
                // server incluyo el summary del inning cerrado, mostrar el
                // modal con carreras, hits, BB, K, E, LOB y lanzamientos
                // del pitcher.
                if (data.end_half && data.summary) {
                    this.inningSummary = data.summary;
                    this.modal = 'inning-summary';
                }
                if (data.walk) this.toast('Base por bolas', 'info');
                else if (data.strikeout) this.toast('Ponche', 'info');
                else if (data.end_half) this.toast('Fin del inning', 'warning');
                else this.toast('Jugada registrada', 'success');
            } catch (e) {
                this.toast('Error de red: ' + e.message, 'error');
            } finally {
                this.busy = false;
            }
        },

        async endInning() {
            if (this.busy) return;
            this.busy = true;
            try {
                const fd = new FormData();
                fd.append('_token', this.csrf);
                const res = await fetch(this.endInningUrl, {
                    method: 'POST',
                    body: fd,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    this.toast(data.message || 'Error al cerrar la entrada', 'error');
                    return;
                }
                this.applyState(data);
                // DISI-218: mostrar el modal de resumen con LOB + lanzamientos
                // del pitcher cuando el servidor incluyo data.summary.
                if (data.summary) {
                    this.inningSummary = data.summary;
                    this.modal = 'inning-summary';
                }
                this.toast('Inning cerrado', 'success');
            } catch (e) {
                this.toast('Error de red: ' + e.message, 'error');
            } finally {
                this.busy = false;
            }
        },

        async endGame() {
            if (this.busy) return;
            this.busy = true;
            try {
                const fd = new FormData();
                fd.append('_token', this.csrf);
                const res = await fetch(this.endGameUrl, {
                    method: 'POST',
                    body: fd,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    this.toast(data.message || 'Error al finalizar el juego', 'error');
                    return;
                }
                this.applyState(data);
                this.toast('Juego finalizado', 'success');
            } catch (e) {
                this.toast('Error de red: ' + e.message, 'error');
            } finally {
                this.busy = false;
            }
        },

        // ===== Modal Sustituir (migrado exacto del scoreboard base) =====
        openSubstituteModal(kind = 'pitcher') {
            this.subKind = kind || 'pitcher';
            this.subInId = '';
            // Selecciona la primera base ocupada por defecto (first, second, third).
            if (this.base1) this.subBase = 'first';
            else if (this.base2) this.subBase = 'second';
            else if (this.base3) this.subBase = 'third';
            else this.subBase = 'first';
            this.subError = '';
            this.modal = 'substitute';
        },
        // Roster del equipo que esta bateando (half='top' => away, 'bottom' => home).
        rosterForBattingTeam() {
            return this.half === 'top' ? (this.rosterAway || []) : (this.rosterHome || []);
        },
        // Busca un atleta por id en la union de ambos rosters.
        _findAthlete(id) {
            if (id === null || id === undefined) return null;
            const num = Number(id);
            return [...(this.rosterAway || []), ...(this.rosterHome || [])].find(a => Number(a.id) === num) || null;
        },
        currentPitcherLabel() {
            const p = this.pitcher;
            if (!p) return '—';
            return `#${p.number ?? '-'} ${p.first_name ?? ''} ${p.last_name ?? ''}`.trim();
        },
        currentBatterLabel() {
            const b = this.batter;
            if (!b) return '—';
            return `#${b.number ?? '-'} ${b.first_name ?? ''} ${b.last_name ?? ''}`.trim();
        },
        // base = 'first' | 'second' | 'third'  (devuelve el objeto atleta en la base).
        runnerLabel(base) {
            const obj = ({first: this.base1, second: this.base2, third: this.base3})[base];
            if (!obj) return '—';
            if (typeof obj === 'object') {
                // Si es un corredor placeholder ('corredor'), no tiene nombre real.
                if (obj.id === 'corredor' || obj.id === 'runner') return 'Corredor';
                return `${obj.first_name ?? ''} ${obj.last_name ?? obj.name ?? ''}`.trim() || obj.name || `ID ${obj.id}`;
            }
            return `ID ${obj}`;
        },
        canConfirmSubstitute() {
            if (!this.subInId) return false;
            if (this.subKind === 'pr') {
                if (!this.subBase) return false;
                const obj = ({first: this.base1, second: this.base2, third: this.base3})[this.subBase];
                if (!obj) return false;
                // No permitir pinch runner sobre un placeholder sin roster.
                if (typeof obj !== 'object' || obj.id === 'corredor' || obj.id === 'runner') return false;
            }
            return true;
        },
        async confirmSubstitute() {
            if (!this.canConfirmSubstitute()) {
                this.subError = 'Selecciona el atleta entrante';
                return;
            }
            // Determinar out_athlete_id segun el subKind.
            let outId = null;
            if (this.subKind === 'pitcher') {
                outId = this.pitcher ? Number(this.pitcher.id) : null;
            } else if (this.subKind === 'batter') {
                outId = this.batter ? Number(this.batter.id) : null;
            } else if (this.subKind === 'pr') {
                const obj = ({first: this.base1, second: this.base2, third: this.base3})[this.subBase];
                outId = obj && typeof obj === 'object' ? Number(obj.id) : (obj ? Number(obj) : null);
            }
            if (!outId) {
                this.subError = 'No se puede identificar el atleta saliente';
                return;
            }
            this.subBusy = true;
            this.subError = '';
            try {
                const body = new FormData();
                body.append('kind', this.subKind);
                body.append('out_athlete_id', String(outId));
                body.append('in_athlete_id', String(this.subInId));
                if (this.subKind === 'pr') body.append('base', this.subBase);
                body.append('_token', this.csrf);
                const resp = await fetch(this.substituteUrl, {
                    method: 'POST',
                    body,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });
                const data = await resp.json().catch(() => ({}));
                if (!resp.ok || data.success === false) {
                    this.subError = data.message || data.error || 'Error al sustituir';
                    return;
                }
                this.toast('Sustitucion registrada', 'success');
                this.closeModal();
                await this.pollNow();
            } catch (e) {
                this.subError = 'Error de red: ' + e.message;
            } finally {
                this.subBusy = false;
            }
        },
    }));

    // Scoreboard live-poll component (DISI-12)
    window.Alpine.data('scoreboardApp', (config) => ({
        gameId: config.gameId,
        pollUrl: config.pollUrl,
        pitchUrl: config.pitchUrl,
        endInningUrl: config.endInningUrl,
        endGameUrl: config.endGameUrl,
        substituteUrl: config.substituteUrl,
        runnerUrl: config.runnerUrl,
        statsUrl: config.statsUrl,
        lineupReorderUrl: config.lineupReorderUrl,
        homeName: config.homeName,
        awayName: config.awayName,
        homeShort: config.homeShort,
        awayShort: config.awayShort,
        homeTeamId: config.homeTeamId,
        awayTeamId: config.awayTeamId,
        csrf: config.csrf,
        rosterAway: config.rosterAway || [],
        rosterHome: config.rosterHome || [],
        pollInterval: null,
        pollStatus: 'Conectado',
        isPolling: false,
        isPitching: false,
        // DISI-33: flag reactivo que indica si el juego esta finalizado.
        // Se inicializa desde el server via x-data ($game->isCompleted()) y
        // se actualiza automaticamente en applyState() cuando el poll
        // detecta data.is_completed === true. Las secciones del scoreboard
        // usan x-show="!isFinalized" para desaparecer sin recargar la pagina.
        // FIX DISI-34: el parametro de la factory es `config`, no `e`.
        // Antes usabamos `e.isFinalized` que es un ReferenceError y rompia
        // toda la inicializacion de Alpine (tabs vacios, etc).
        isFinalized: config.isFinalized ?? false,
        // Tab activo del bloque inferior (PITCHEo / BATEo / EXTRAS).
        // DISI-33: cuando el juego finaliza, applyState() cambia este tab a
        // 'extra' automaticamente para que el usuario vea los botones
        // Stats + Box Score sin tener que hacer click.
        tab: config.tab ?? 'pitch',
        // DISI-45: URL publica del juego (/game/live/{token}) para el modal
        // Live/Share. La URL se calcula server-side en x-data y se pasa
        // como prop al factory de Alpine. Sin esta linea, this.publicUrl es
        // undefined en el navegador y los metodos openLiveView/sharePublicUrl
        // lanzan ReferenceError (FIX ANTERIOR era ReferenceError, no "no
        // tiene URL publica").
        publicUrl: config.publicUrl ?? '',
        modal: null, // 'strike' | 'out-step1' | 'out-step2' | 'hit' | 'bunt' | 'end-inning' | 'inning-summary' | 'end-game' | 'substitute' | 'stats' | 'lineup' | 'runner' | 'live-share' | null
        outSubtype: null,
        // DISI-20: base seleccionada en el modal "Gestionar corredor"
        runnerBase: null, // 'first' | 'second' | 'third' | null
        hitSubtype: null,
        hitConfig: { label: '', description: '', preview: '' },
        defensiveSequence: [],
        inningSummary: null,
        subKind: 'pitcher',
        subInId: '',
        subBase: 'first',
        lastBases: { first: null, second: null, third: null },
        lastRunners: {},
        lastScore: { home: 0, away: 0 },
        // Estado reactivo del juego (para los modales de sustitucion)
        stateHalf: 'top',
        stateBatterId: null,
        statePitcherId: null,
        // Tracking del inning/half previo (para detectar cierre de inning)
        prevInning: 1,
        prevHalf: 'top',
        // Flag para que el modal de resumen solo se muestre una vez por cierre
        inningClosedFlag: null,
        stateBases: { first: null, second: null, third: null },
        // MEJ-2: AudioContext lazy para el beep de cierre de inning
        _audioCtx: null,
        // MEJ-3: datos del modal de stats
        statsData: null,
        statsLoading: false,
        statsTab: 'batting', // 'batting' | 'pitching'
        statsFilter: 'home', // 'home' | 'away' | 'all'
        // MEJ-4: estado del modal de lineup
        lineupTeam: 'away', // 'away' | 'home'
        lineupAway: [],
        lineupHome: [],
        lineupLoading: false,
        lineupDirty: false,
        lineupDragId: null,

        start() {
            this.pollInterval = setInterval(() => this.poll(), 5000);
            this.poll();
            // Listener global para botones OPCIONES que se re-renderizan via renderBase
            // (no son parte del DOM reactivo de Alpine, asi que necesitan un evento custom).
            this._onOpenRunnerModal = (ev) => {
                const base = ev?.detail?.base;
                if (base) this.openRunnerModal(base);
            };
            window.addEventListener('open-runner-modal', this._onOpenRunnerModal);
        },

        async pollNow() {
            // DISI-43: pollNow() fuerza un refresh inmediato sin esperar al
            // intervalo. Antes compartia el guard `isPolling` con el poll
            // automatico de 5s, lo que causaba una race condition: si el
            // usuario hacia clic en "Finalizar inning" justo cuando el poll
            // automatico estaba corriendo, el refresh manual se saltaba
            // (`if (this.isPolling) return;`) y los cards de Pitcher/Batter/
            // On-deck quedaban con datos viejos hasta el siguiente intervalo.
            // Solucion: pollNow() salta el guard y reusa la logica de fetch
            // del poll regular.
            if (this.isPolling) {
                // Esperar a que el poll automatico termine para no duplicar
                // requests. Poll regular dura ~50-200ms tipicamente.
                await new Promise(resolve => {
                    const check = () => {
                        if (!this.isPolling) resolve();
                        else setTimeout(check, 20);
                    };
                    check();
                });
            }
            this.isPolling = true;
            try {
                const res = await fetch(this.pollUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();
                this.applyState(data);
                this.pollStatus = 'Última actualización: ' + new Date().toLocaleTimeString();
            } catch (e) {
                this.pollStatus = 'Sin conexión. Reintentando...';
            } finally {
                this.isPolling = false;
            }
        },

        stop() {
            if (this.pollInterval) {
                clearInterval(this.pollInterval);
                this.pollInterval = null;
            }
        },

        async poll() {
            if (this.isPolling) return;
            this.isPolling = true;
            try {
                const res = await fetch(this.pollUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();
                this.applyState(data);
                this.pollStatus = 'Última actualización: ' + new Date().toLocaleTimeString();
            } catch (e) {
                this.pollStatus = 'Sin conexión. Reintentando...';
            } finally {
                this.isPolling = false;
            }
        },

        // ===== PITCHEo (Fase 2) =====
        async sendPitch(event) {
            if (this.isPitching) return;
            this.isPitching = true;
            try {
                const fd = new FormData();
                for (const [k, v] of Object.entries(event)) {
                    if (Array.isArray(v)) {
                        v.forEach(item => fd.append(k + '[]', item));
                    } else {
                        fd.append(k, v);
                    }
                }
                fd.append('_token', this.csrf);
                const res = await fetch(this.pitchUrl, {
                    method: 'POST',
                    body: fd,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    this.toast(data.message || 'Error al registrar la jugada', 'error');
                    return;
                }
                // DISI-21: Aplicar el state del response INMEDIATAMENTE para que
                // out/strike/ball se reflejen sin esperar al proximo poll automatica.
                // El poll refresca despues para traer pitcher/batter/on-deck/runners.
                if (data.state) {
                    this.applyPitchState(data.state, data.score || this.lastScore);
                }
                if (data.walk) this.toast('Base por bolas', 'info');
                else if (data.strikeout) this.toast('Ponche', 'info');
                else if (data.end_half) {
                    this.toast('Fin del inning', 'warning');
                    if (data.summary) {
                        this.inningSummary = data.summary;
                        this.modal = 'inning-summary';
                        this.playInningEndBeep();
                    }
                }
                else this.toast('Jugada registrada', 'success');
                // Refresca despues para traer la info completa (runners, pitcher, batter).
                // AWAIT para garantizar que se aplique antes de que el usuario vea el estado.
                await this.pollNow();
            } catch (e) {
                this.toast('Error de red: ' + e.message, 'error');
            } finally {
                this.isPitching = false;
            }
        },

        // DISI-21: Aplica solo el subconjunto de state que viene en la respuesta
        // del pitch (balls, strikes, outs, bases, inning/half). El poll completo
        // trae despues el resto (pitcher, batter, on_deck, runners, etc.).
        applyPitchState(state, score) {
            if (!state) return;
            const s = state;
            if (score) {
                const homeScore = document.querySelector('[data-score="home"]');
                const awayScore = document.querySelector('[data-score="away"]');
                if (homeScore) homeScore.textContent = score.home;
                if (awayScore) awayScore.textContent = score.away;
            }

            const inningNum = document.querySelector('[data-inning-number]');
            const inningHalf = document.querySelector('[data-inning-half]');
            const inningAnimChanged = inningNum && inningNum.textContent !== String(s.inning);
            const halfAnimChanged = inningHalf && inningHalf.textContent !== (s.half === 'top' ? '▲' : '▼');
            if (inningNum) inningNum.textContent = s.inning;
            if (inningHalf) inningHalf.textContent = s.half === 'top' ? '▲' : '▼';
            if (inningAnimChanged || halfAnimChanged) {
                this.triggerInningFlip();
            }

            const homeZone = document.querySelector('[data-team-zone="home"]');
            const awayZone = document.querySelector('[data-team-zone="away"]');
            if (homeZone && awayZone) {
                const newHomeBatting = s.half === 'bottom' ? '1' : '0';
                const newAwayBatting = s.half === 'top' ? '1' : '0';
                if (homeZone.dataset.batting !== newHomeBatting) homeZone.dataset.batting = newHomeBatting;
                if (awayZone.dataset.batting !== newAwayBatting) awayZone.dataset.batting = newAwayBatting;
            }

            this.renderDots('[data-balls]', s.balls ?? 0, 'bg-emerald-500', 'bg-gray-200', 4);
            this.renderDots('[data-strikes]', s.strikes ?? 0, 'bg-amber-500', 'bg-gray-200', 3);
            this.renderDots('[data-outs]', s.outs ?? 0, 'bg-rose-500', 'bg-gray-200', 3);

            const bases = s.bases || { first: null, second: null, third: null };
            // No tenemos info de corredores en el response, asi que solo actualizamos
            // la base (con el jersey si ya estaba identificado en lastRunners).
            this.renderBase('first', bases.first, this.lastRunners?.first || null);
            this.renderBase('second', bases.second, this.lastRunners?.second || null);
            this.renderBase('third', bases.third, this.lastRunners?.third || null);

            this.lastBases = bases;
            this.stateHalf = s.half;
            this.stateBatterId = s.current_batter_id;
            this.statePitcherId = s.current_pitcher_id;
            this.stateBases = bases;

            this.prevInning = s.inning;
            this.prevHalf = s.half;
            this.stateBases = s.bases || { first: null, second: null, third: null };
        },

        sendBall() { this.sendPitch({ type: 'ball' }); },
        sendFoul() { this.sendPitch({ type: 'foul' }); },
        sendStrike(subtype) { this.sendPitch({ type: 'strike', subtype }); this.closeModal(); },
        sendOut(subtype, defensiveSequence = []) {
            this.sendPitch({ type: 'out', subtype, defensive_sequence: defensiveSequence });
            this.closeModal();
        },
        sendHit(subtype) { this.sendPitch({ type: 'hit', subtype }); this.closeModal(); },

        openStrikeModal() { this.modal = 'strike'; },
        openOutStep1() { this.modal = 'out-step1'; },
        openOutStep2(subtype) { this.outSubtype = subtype; this.defensiveSequence = []; this.modal = 'out-step2'; },
        confirmOut() { this.sendOut(this.outSubtype, this.defensiveSequence); },
        addFielder(pos) { this.defensiveSequence.push(pos); },
        removeFielder(i) { this.defensiveSequence.splice(i, 1); },

        // DISI-30: helper visual para el diamante — devuelve true si la posicion
        // ya esta en la secuencia (la pintamos de color ambar para feedback).
        isFielderSelected(pos) {
            return this.defensiveSequence.includes(pos);
        },

        // Hit modal
        openHitModal(subtype) {
            this.hitSubtype = subtype;
            this.hitConfig = this.hitPreview(subtype);
            this.modal = 'hit';
        },
        confirmHit() { this.sendHit(this.hitSubtype); },
        hitPreview(subtype) {
            const bases = this.lastBases || { first: null, second: null, third: null };
            const onFirst = !!bases.first;
            const onSecond = !!bases.second;
            const onThird = !!bases.third;
            const runnersCount = (onFirst ? 1 : 0) + (onSecond ? 1 : 0) + (onThird ? 1 : 0);
            const configs = {
                single: {
                    label: 'Sencillo',
                    description: 'El bateador llega a 1B y los corredores avanzan una base.',
                    runs: onThird ? 1 : 0,
                    batterTo: '1B',
                    runners: onFirst ? '1B→2B' : (onSecond ? '2B→3B' : null),
                },
                double: {
                    label: 'Doble',
                    description: 'El bateador llega a 2B y los corredores avanzan dos bases.',
                    runs: (onThird ? 1 : 0) + (onSecond ? 1 : 0),
                    batterTo: '2B',
                    runners: onFirst ? '1B→3B' : null,
                },
                triple: {
                    label: 'Triple',
                    description: 'El bateador llega a 3B y todos los corredores anotan carrera.',
                    runs: runnersCount,
                    batterTo: '3B',
                    runners: null,
                },
                hr: {
                    label: 'Home Run',
                    description: 'El bateador y todos los corredores en base anotan carrera.',
                    runs: runnersCount + 1,
                    batterTo: 'Home',
                    runners: 'Todos anotan',
                },
                inside_park: {
                    label: 'Home Run de pierna',
                    description: 'El bateador anota una carrera sin que la pelota salga del parque.',
                    runs: 1,
                    batterTo: 'Home',
                    runners: null,
                },
            };
            const c = configs[subtype] || { label: 'Hit', description: '', runs: 0, batterTo: '?', runners: null };
            const preview = [
                `<div><b>Bateador:</b> ${c.batterTo}</div>`,
                c.runners ? `<div><b>Corredores:</b> ${c.runners}</div>` : null,
                `<div><b>Carreras estimadas:</b> <span class="font-black text-emerald-700">${c.runs}</span></div>`,
            ].filter(Boolean).join('');
            return { label: c.label, description: c.description, preview };
        },

        closeModal() {
            this.modal = null;
            this.outSubtype = null;
            this.hitSubtype = null;
            this.hitConfig = { label: '', description: '', preview: '' };
            this.defensiveSequence = [];
            this.runnerBase = null;
        },

        // ============= FASE 4: EXTRAS =============
        // Balk: directo (no requiere modal de confirmacion)
        sendBalk() { this.sendPitch({ type: 'balk' }); this.toast('Balk registrado', 'info'); },

        // Bunt: abre modal con opciones sacrifice vs bunt_single
        openBuntModal() { this.modal = 'bunt'; },
        sendBunt(subtype) { this.sendPitch({ type: 'bunt', subtype }); this.closeModal(); this.toast('Toque registrado', 'info'); },

        // ============= DISI-42: Modal Live / Compartir URL publica =============
        // DISI-42: al dar clic en el boton play del header (gated por is_public)
        // se abre este modal con 2 opciones: 'Abrir vista en vivo' (en nueva pestana)
        // o 'Compartir URL' (abre el menu nativo del navegador con navigator.share()
        // o fallback a clipboard.copy si el navegador no soporta la Web Share API).
        openLiveShareModal() {
            this.modal = 'live-share';
        },
        openLiveView() {
            // El link publico se pasa como prop (config.publicUrl) desde x-data.
            const url = this.publicUrl;
            if (! url) {
                this.toast('Este juego aun no tiene un token publico asignado.', 'error');
                return;
            }
            window.open(url, '_blank', 'noopener,noreferrer');
            this.closeModal();
            this.toast('Vista en vivo abierta en nueva pestana', 'success');
        },
        async sharePublicUrl() {
            const url = this.publicUrl;
            if (! url) {
                this.toast('Este juego aun no tiene un token publico asignado.', 'error');
                return;
            }
            const title = 'Sigue el juego en vivo';
            const text = `Sigue el marcador en vivo: ${this.homeShort || 'Local'} vs ${this.awayShort || 'Visitante'}`;
            // Camino 1: Web Share API (moviles y navegadores modernos)
            if (navigator.share) {
                try {
                    await navigator.share({ title, text, url });
                    this.toast('Compartido correctamente', 'success');
                    this.closeModal();
                    return;
                } catch (e) {
                    // El usuario cancelo o fallo. Caemos al fallback.
                    if (e?.name === 'AbortError') {
                        this.closeModal();
                        return;
                    }
                }
            }
            // Camino 2: Clipboard API (fallback para desktop / navegadores sin share)
            try {
                if (navigator.clipboard?.writeText) {
                    await navigator.clipboard.writeText(url);
                    this.toast('URL copiada al portapapeles', 'success');
                } else {
                    // Fallback final: prompt con el texto seleccionado
                    window.prompt('Copia este enlace para compartir:', url);
                    this.toast('Enlace mostrado para copiar manualmente', 'info');
                }
                this.closeModal();
            } catch (e) {
                console.error('sharePublicUrl error', e);
                this.toast('No se pudo compartir: ' + e.message, 'error');
            }
        },

        // Finalizar inning
        openEndInningModal() { this.modal = 'end-inning'; },
        async confirmEndInning() {
            try {
                const resp = await fetch(this.endInningUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });
                // DISI-36: si el server aborta con 403/422, leer el JSON de
                // error para mostrar el mensaje real al usuario en vez de
                // un toast generico de "Error de red".
                if (!resp.ok) {
                    let errMsg = `Error HTTP ${resp.status}`;
                    try {
                        const errData = await resp.json();
                        if (errData.message) errMsg = errData.message;
                    } catch (_) { /* body no era JSON */ }
                    this.toast(errMsg, 'error');
                    return;
                }
                const data = await resp.json();
                if (data.success) {
                    this.closeModal();
                    if (data.result?.status === 'game_over') {
                        this.toast('Juego finalizado (inning completo)', 'warning');
                    } else {
                        this.toast('Inning finalizado', 'success');
                    }
                    this.inningSummary = data.summary || null;
                    if (this.inningSummary) {
                        this.modal = 'inning-summary';
                        this.playInningEndBeep();
                    }
                    // Re-render inmediato del state
                    await this.pollNow();
                } else {
                    this.toast(data.message || 'Error al finalizar inning', 'error');
                }
            } catch (e) {
                console.error('endInning error', e);
                this.toast('Error de red al finalizar inning', 'error');
            }
        },

        // Finalizar juego
        openEndGameModal() { this.modal = 'end-game'; },
        async confirmEndGame() {
            try {
                const resp = await fetch(this.endGameUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });
                // DISI-36: si el server aborta con 403/422, leer el JSON de
                // error para mostrar el mensaje real al usuario en vez de
                // un toast generico de "Error de red".
                if (!resp.ok) {
                    let errMsg = `Error HTTP ${resp.status}`;
                    try {
                        const errData = await resp.json();
                        if (errData.message) errMsg = errData.message;
                    } catch (_) { /* body no era JSON */ }
                    this.toast(errMsg, 'error');
                    return;
                }
                const data = await resp.json();
                if (data.success) {
                    this.closeModal();
                    this.toast('Juego finalizado: ' + data.result.away_score + '-' + data.result.home_score, 'warning');
                    await this.pollNow();
                } else {
                    this.toast(data.message || 'Error al finalizar juego', 'error');
                }
            } catch (e) {
                console.error('endGame error', e);
                this.toast('Error de red al finalizar juego', 'error');
            }
        },

        // ============= FASE 4b: SUSTITUCIONES =============
        openSubstituteModal() {
            this.subKind = 'pitcher';
            this.subInId = '';
            this.subBase = (this.lastBases && this.lastBases.first) ? 'first'
                : ((this.lastBases && this.lastBases.second) ? 'second'
                : ((this.lastBases && this.lastBases.third) ? 'third' : 'first'));
            this.modal = 'substitute';
        },
        // Roster del equipo que esta bateando (half === 'top' => away, 'bottom' => home)
        rosterForBattingTeam() {
            return this.stateHalf === 'top' ? this.rosterAway : this.rosterHome;
        },
        currentPitcherLabel() {
            const id = this.statePitcherId;
            if (!id) return '—';
            const a = [...this.rosterAway, ...this.rosterHome].find(r => r.id === id);
            return a ? `#${a.lineup_order ?? '-'} ${a.first_name} ${a.last_name}` : `ID ${id}`;
        },
        currentBatterLabel() {
            const id = this.stateBatterId;
            if (!id) return '—';
            const a = [...this.rosterAway, ...this.rosterHome].find(r => r.id === id);
            return a ? `#${a.lineup_order ?? '-'} ${a.first_name} ${a.last_name}` : `ID ${id}`;
        },
        runnerLabel(id) {
            if (!id) return '—';
            const a = [...this.rosterAway, ...this.rosterHome].find(r => r.id === id);
            return a ? `${a.first_name} ${a.last_name}` : `ID ${id}`;
        },
        canConfirmSubstitute() {
            if (!this.subInId) return false;
            if (this.subKind === 'pr') {
                if (!this.subBase) return false;
                if (!this.lastBases || !this.lastBases[this.subBase]) return false;
                // DISI-27: no permitir pinch runner sobre placeholder (corredor sin roster).
                const outId = this.lastBases[this.subBase];
                if (typeof outId !== 'number') return false;
            }
            return true;
        },
        async confirmSubstitute() {
            if (!this.canConfirmSubstitute()) return;
            // Determinar out_athlete_id
            let outId = null;
            if (this.subKind === 'pitcher') {
                outId = this.statePitcherId;
            } else if (this.subKind === 'batter') {
                outId = this.stateBatterId;
            } else if (this.subKind === 'pr') {
                outId = this.lastBases[this.subBase];
            }
            if (!outId) {
                this.toast('No se puede identificar el atleta saliente', 'error');
                return;
            }
            // DISI-27: no se puede sustituir un placeholder (corredor sin roster).
            if (typeof outId !== 'number') {
                this.toast('Este corredor no tiene atleta identificado en el roster. Asignale un atleta primero o registralo en el roster.', 'error');
                return;
            }
            try {
                const body = new FormData();
                body.append('kind', this.subKind);
                body.append('out_athlete_id', outId);
                body.append('in_athlete_id', this.subInId);
                if (this.subKind === 'pr') body.append('base', this.subBase);
                const resp = await fetch(this.substituteUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
                    body,
                });
                const data = await resp.json();
                if (data.success) {
                    this.closeModal();
                    this.toast('Sustitucion registrada', 'success');
                    await this.pollNow();
                } else {
                    this.toast(data.error || 'Error al sustituir', 'error');
                }
            } catch (e) {
                console.error('substitute error', e);
                this.toast('Error de red al sustituir', 'error');
            }
        },

        toast(message, level = 'success') {
            window.dispatchEvent(new CustomEvent('toast', { detail: { message, level } }));
        },

        // ============= DISI-20: GESTION DE CORREDORES =============
        // Abre el modal de gestion de corredor sobre la base indicada.
        // DISI-27: permite abrir el modal aunque el ID sea un placeholder
        // (corredor sin roster identificado) — el modal mostrara el grid
        // de acciones igual que un corredor registrado (solo sustitucion
        // queda oculta porque requiere un atleta identificado).
        openRunnerModal(base) {
            if (!base || !['first', 'second', 'third'].includes(base)) return;
            const id = (this.lastBases || {})[base];
            if (!id) {
                this.toast('No hay corredor en ' + this.baseLabel(base), 'warning');
                return;
            }
            this.runnerBase = base;
            this.modal = 'runner';
        },

        // DISI-27: indica si la base seleccionada en el modal es un placeholder
        // (corredor sin roster identificado). Usado en el modal para mostrar
        // el grid de acciones pero mantener oculta la sustitucion (PR).
        runnerModalPlaceholder() {
            const base = this.runnerBase;
            if (!base) return false;
            const id = (this.lastBases || {})[base];
            return typeof id === 'string' && id !== '';
        },

        // Cierra el modal de corredor (la accion closeModal ya limpia
        // runnerBase por la extension que hicimos a closeRunnerModal).
        closeRunnerModal() {
            this.runnerBase = null;
        },

        // Devuelve el atleta identificado en runnerBase (desde rosterAway/Home).
        runnerModalRunner() {
            if (!this.runnerBase) return null;
            const id = (this.lastBases || {})[this.runnerBase];
            if (!id) return null;
            return [...(this.rosterAway || []), ...(this.rosterHome || [])].find(r => r.id === id) || null;
        },

        // Titulo del modal (1RA BASE / 2DA BASE / 3RA BASE).
        runnerModalTitle() {
            if (!this.runnerBase) return '';
            return this.baseLabel(this.runnerBase);
        },

        // Label humano para la accion "avanza a siguiente base" (cambia segun base).
        runnerAdvanceLabel() {
            if (!this.runnerBase) return '';
            switch (this.runnerBase) {
                case 'first': return 'Avanza a 2B';
                case 'second': return 'Avanza a 3B';
                case 'third': return 'Anota carrera';
                default: return '—';
            }
        },

        baseLabel(base) {
            switch (base) {
                case 'first': return '1RA BASE';
                case 'second': return '2DA BASE';
                case 'third': return '3RA BASE';
                default: return '';
            }
        },

        // Envia la accion del anotador sobre el corredor en runnerBase.
        async sendRunnerAction(action) {
            if (this.isPitching) return;
            if (!this.runnerBase) return;
            const base = this.runnerBase;
            try {
                const body = new FormData();
                body.append('base', base);
                body.append('action', action);
                const resp = await fetch(this.runnerUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrf,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body,
                    credentials: 'same-origin',
                });
                const data = await resp.json().catch(() => ({}));
                if (!resp.ok || !data.success) {
                    this.toast(data.error || data.message || 'Error al registrar la accion', 'error');
                    return;
                }
                this.closeRunnerModal();
                this.closeModal();
                // Mensaje segun la accion
                const r = data.result || {};
                let msg = 'Accion registrada';
                if (action === 'advance') msg = base === 'third' ? 'Corredor anota carrera' : 'Corredor avanza';
                else if (action === 'stolen_base') msg = 'Robo de base exitoso';
                else if (action === 'wild_pitch') msg = 'Wild pitch registrado';
                else if (action === 'passed_ball') msg = 'Passed ball registrado';
                else if (action === 'error_advance') msg = 'Avanza por error';
                else if (action === 'obstruction') msg = 'Obstruccion registrada';
                else if (action === 'score_rbi') msg = 'Carrera anotada (con RBI)';
                else if (action === 'score_no_rbi') msg = 'Carrera anotada (sin RBI)';
                else if (action === 'caught_stealing') msg = 'Out por robo de base';
                else if (action === 'pickoff') msg = 'Out por pickoff';
                else if (action === 'out_at_2b') msg = 'Out en 2B';
                else if (action === 'out_at_3b') msg = 'Out en 3B';
                if (r.runs_scored) msg += ' (+' + r.runs_scored + ' carrera)';
                this.toast(msg, r.end_half ? 'warning' : 'success');
                if (r.end_half) {
                    this.playInningEndBeep();
                }
                await this.pollNow();
            } catch (e) {
                console.error('runnerAction error', e);
                this.toast('Error de red: ' + e.message, 'error');
            }
        },

        // ============= MEJ-3: MODAL DE STATS DEL JUEGO =============
        async openStatsModal() {
            this.modal = 'stats';
            this.statsTab = 'batting';
            this.statsFilter = 'all';
            await this.loadStats();
        },
        async loadStats() {
            this.statsLoading = true;
            try {
                const res = await fetch(this.statsUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                this.statsData = await res.json();
            } catch (e) {
                this.toast('Error al cargar stats: ' + e.message, 'error');
            } finally {
                this.statsLoading = false;
            }
        },
        filteredBatting() {
            if (!this.statsData) return [];
            if (this.statsFilter === 'all') return this.statsData.batting;
            const tid = this.statsFilter === 'home' ? this.homeTeamId : this.awayTeamId;
            return this.statsData.batting.filter(b => b.team_id === tid);
        },
        filteredPitching() {
            if (!this.statsData) return [];
            if (this.statsFilter === 'all') return this.statsData.pitching;
            const tid = this.statsFilter === 'home' ? this.homeTeamId : this.awayTeamId;
            return this.statsData.pitching.filter(p => p.team_id === tid);
        },
        lineScoreAway() {
            if (!this.statsData) return [];
            return Object.values(this.statsData.line_score.away);
        },
        lineScoreHome() {
            if (!this.statsData) return [];
            return Object.values(this.statsData.line_score.home);
        },
        lineScoreTotal(team) {
            if (!this.statsData) return 0;
            const arr = this.statsData.line_score[team] || {};
            return Object.values(arr).reduce((a, b) => a + b, 0);
        },
        formatAvg(avg) {
            if (avg === 0 || avg === '0') return '.000';
            const num = parseFloat(avg);
            if (isNaN(num) || num === 0) return '.000';
            return '.' + Math.round(num * 1000).toString().padStart(3, '0');
        },

        // ============= MEJ-4 + DISI-31: MODAL DE LINEUP =============
        async openLineupModal() {
            this.modal = 'lineup';
            this.lineupTeam = 'away';
            this.lineupDirty = false;
            await this.loadLineup();
        },
        async loadLineup() {
            this.lineupLoading = true;
            try {
                const res = await fetch(this.statsUrl, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    credentials: 'same-origin',
                });
                const data = await res.json();
                // Inicializar lineups desde roster si estan vacios.
                // rosterAway/rosterHome contienen TODOS los atletas del equipo (titulares + bench).
                // El modal los separa: starters (lineup_order 1-9) arriba, resto abajo.
                if (!this.lineupAway.length) {
                    this.lineupAway = JSON.parse(JSON.stringify(this.rosterAway));
                }
                if (!this.lineupHome.length) {
                    this.lineupHome = JSON.parse(JSON.stringify(this.rosterHome));
                }
            } catch (e) {
                this.toast('Error al cargar lineup: ' + e.message, 'error');
            } finally {
                this.lineupLoading = false;
            }
        },

        // DISI-31: separa el roster del equipo en titulares (lineup 1-9) y disponibles.
        // Si hay mas/menos de 9 titulares, retorna los que tengan lineup_order 1-9.
        currentLineup() {
            const all = this.lineupTeam === 'away' ? this.lineupAway : this.lineupHome;
            return all
                .filter(a => a.lineup_order !== null && a.lineup_order !== undefined)
                .sort((a, b) => (a.lineup_order || 0) - (b.lineup_order || 0));
        },
        availableRoster() {
            const all = this.lineupTeam === 'away' ? this.lineupAway : this.lineupHome;
            return all
                .filter(a => a.lineup_order === null || a.lineup_order === undefined)
                .sort((a, b) => (a.number || 0) - (b.number || 0));
        },
        currentTeamId() {
            return this.lineupTeam === 'away' ? this.awayTeamId : this.homeTeamId;
        },

        // DISI-31: gestion del lineup
        addToLineup(athleteId) {
            const all = this.lineupTeam === 'away' ? this.lineupAway : this.lineupHome;
            const lineup = this.currentLineup();
            if (lineup.length >= 9) {
                this.toast('El lineup ya tiene 9 jugadores. Quita uno antes de agregar otro.', 'warning');
                return;
            }
            const athlete = all.find(a => a.id === athleteId);
            if (!athlete) return;
            // Asignar al final con defaults sensatos
            athlete.lineup_order = lineup.length + 1;
            athlete.position = athlete.position || this.suggestPosition(lineup.length);
            athlete.is_pitcher = false;
            this.lineupDirty = true;
            // Reordenar todos por lineup_order
            this.reindexLineup();
        },
        removeFromLineup(athleteId) {
            const all = this.lineupTeam === 'away' ? this.lineupAway : this.lineupHome;
            const athlete = all.find(a => a.id === athleteId);
            if (!athlete) return;
            athlete.lineup_order = null;
            athlete.position = null;
            athlete.is_pitcher = false;
            this.lineupDirty = true;
            this.reindexLineup();
        },
        setLineupPosition(athleteId, position) {
            const all = this.lineupTeam === 'away' ? this.lineupAway : this.lineupHome;
            const athlete = all.find(a => a.id === athleteId);
            if (!athlete) return;
            athlete.position = position;
            this.lineupDirty = true;
        },
        setLineupPitcher(athleteId) {
            const all = this.lineupTeam === 'away' ? this.lineupAway : this.lineupHome;
            // Solo puede haber 1 pitcher. Limpiar el resto.
            all.forEach(a => { a.is_pitcher = (a.id === athleteId); });
            this.lineupDirty = true;
        },
        reindexLineup() {
            const lineup = this.currentLineup();
            lineup.forEach((a, i) => { a.lineup_order = i + 1; });
        },
        suggestPosition(idx) {
            // Sugerir posicion por defecto segun el orden (1=P, 2=C, 3=1B, 4=2B, 5=3B, 6=SS, 7=LF, 8=CF, 9=RF)
            return ['P','C','1B','2B','3B','SS','LF','CF','RF'][idx] || '';
        },

        // Drag & drop heredado (sin cambios en firma)
        onDragStart(event, id) {
            this.lineupDragId = id;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', String(id));
            event.currentTarget.classList.add('opacity-40');
        },
        onDragEnd(event) {
            event.currentTarget.classList.remove('opacity-40');
            this.lineupDragId = null;
            document.querySelectorAll('[data-lineup-drop]').forEach(el => {
                el.classList.remove('border-emerald-500', 'bg-emerald-50');
            });
        },
        onDragOver(event, overId) {
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';
            const dropEl = event.currentTarget;
            dropEl.classList.add('border-emerald-500', 'bg-emerald-50');
        },
        onDragLeave(event) {
            event.currentTarget.classList.remove('border-emerald-500', 'bg-emerald-50');
        },
        onDrop(event, overId) {
            event.preventDefault();
            event.currentTarget.classList.remove('border-emerald-500', 'bg-emerald-50');
            const fromId = this.lineupDragId ?? Number(event.dataTransfer.getData('text/plain'));
            if (!fromId || fromId === overId) return;
            const arr = this.currentLineup();
            const fromIdx = arr.findIndex(a => a.id === fromId);
            const toIdx = arr.findIndex(a => a.id === overId);
            if (fromIdx < 0 || toIdx < 0) return;
            const [moved] = arr.splice(fromIdx, 1);
            arr.splice(toIdx, 0, moved);
            arr.forEach((a, i) => { a.lineup_order = i + 1; });
            this.lineupDirty = true;
        },
        moveUp(id) {
            const arr = this.currentLineup();
            const idx = arr.findIndex(a => a.id === id);
            if (idx <= 0) return;
            [arr[idx - 1], arr[idx]] = [arr[idx], arr[idx - 1]];
            arr.forEach((a, i) => { a.lineup_order = i + 1; });
            this.lineupDirty = true;
        },
        moveDown(id) {
            const arr = this.currentLineup();
            const idx = arr.findIndex(a => a.id === id);
            if (idx < 0 || idx >= arr.length - 1) return;
            [arr[idx], arr[idx + 1]] = [arr[idx + 1], arr[idx]];
            arr.forEach((a, i) => { a.lineup_order = i + 1; });
            this.lineupDirty = true;
        },

        // DISI-31: enviar payload completo (9 atletas con orden, posicion, pitcher).
        // DISI-32: defaults robustos en position/is_pitcher por si algun item del
        // lineup llega sin esos campos (ej: atletas agregados via drag&drop que
        // no han pasado por el modal de Gestion de Lineup).
        async saveLineup() {
            const lineup = this.currentLineup();
            const teamId = this.currentTeamId();
            if (lineup.length !== 9) {
                this.toast('El lineup debe tener exactamente 9 jugadores. Actual: ' + lineup.length, 'error');
                return;
            }
            const pitcherCount = lineup.filter(a => !!a.is_pitcher).length;
            if (pitcherCount !== 1) {
                this.toast('Debe haber exactamente 1 pitcher. Actual: ' + pitcherCount, 'error');
                return;
            }
            const payload = {
                team_id: teamId,
                lineup: lineup.map(a => ({
                    athlete_id: a.id,
                    lineup_order: a.lineup_order,
                    position: a.position || 'LF',
                    is_pitcher: !!a.is_pitcher,
                })),
            };
            try {
                const res = await fetch(this.lineupReorderUrl, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload),
                });
                const data = await res.json();
                if (data.success) {
                    this.toast('Lineup guardado', 'success');
                    this.lineupDirty = false;
                    // Re-sincronizar caches rosterAway/Home desde los arrays del modal
                    if (this.lineupTeam === 'away') {
                        this.rosterAway = JSON.parse(JSON.stringify(this.lineupAway));
                    } else {
                        this.rosterHome = JSON.parse(JSON.stringify(this.lineupHome));
                    }
                    this.pollNow();
                } else {
                    this.toast(data.error || 'Error al guardar', 'error');
                }
            } catch (e) {
                this.toast('Error: ' + e.message, 'error');
            }
        },
        onDragStart(event, id) {
            this.lineupDragId = id;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', String(id));
            event.currentTarget.classList.add('opacity-40');
        },
        onDragEnd(event) {
            event.currentTarget.classList.remove('opacity-40');
            this.lineupDragId = null;
            // Limpiar marcadores visuales
            document.querySelectorAll('[data-lineup-drop]').forEach(el => {
                el.classList.remove('border-emerald-500', 'bg-emerald-50');
            });
        },
        onDragOver(event, overId) {
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';
            const dropEl = event.currentTarget;
            dropEl.classList.add('border-emerald-500', 'bg-emerald-50');
        },
        onDragLeave(event) {
            event.currentTarget.classList.remove('border-emerald-500', 'bg-emerald-50');
        },
        onDrop(event, overId) {
            event.preventDefault();
            event.currentTarget.classList.remove('border-emerald-500', 'bg-emerald-50');
            const fromId = this.lineupDragId ?? Number(event.dataTransfer.getData('text/plain'));
            if (!fromId || fromId === overId) return;
            const arr = this.currentLineup();
            const fromIdx = arr.findIndex(a => a.id === fromId);
            const toIdx = arr.findIndex(a => a.id === overId);
            if (fromIdx < 0 || toIdx < 0) return;
            // Mover
            const [moved] = arr.splice(fromIdx, 1);
            arr.splice(toIdx, 0, moved);
            // Reasignar lineup_order (1..9)
            arr.forEach((a, i) => { a.lineup_order = i + 1; });
            this.lineupDirty = true;
        },
        moveUp(id) {
            const arr = this.currentLineup();
            const idx = arr.findIndex(a => a.id === id);
            if (idx <= 0) return;
            [arr[idx - 1], arr[idx]] = [arr[idx], arr[idx - 1]];
            arr.forEach((a, i) => { a.lineup_order = i + 1; });
            this.lineupDirty = true;
        },
        moveDown(id) {
            const arr = this.currentLineup();
            const idx = arr.findIndex(a => a.id === id);
            if (idx < 0 || idx >= arr.length - 1) return;
            [arr[idx], arr[idx + 1]] = [arr[idx + 1], arr[idx]];
            arr.forEach((a, i) => { a.lineup_order = i + 1; });
            this.lineupDirty = true;
        },
        // NOTA: la version vieja de saveLineup() que enviaba solo
        // order[athlete_id] + order[lineup_order] (sin position ni is_pitcher)
        // fue eliminada en DISI-32. Quedaba duplicada al final de este objeto
        // y JS tomaba la ultima definicion, anulando el saveLineup() nuevo de
        // DISI-31 que SI envia position/is_pitcher. La version nueva esta
        // arriba (lineas ~986-1036).

        // MEJ-2: Web Audio API beep de cierre de inning.
        // 2 tonos: uno corto (440Hz) y uno largo (660Hz) con 180ms de gap.
        playInningEndBeep() {
            try {
                if (!this._audioCtx) {
                    const Ctx = window.AudioContext || window.webkitAudioContext;
                    if (!Ctx) return;
                    this._audioCtx = new Ctx();
                }
                const ctx = this._audioCtx;
                if (ctx.state === 'suspended') {
                    ctx.resume().catch(() => {});
                }
                const beep = (freq, startAt, duration) => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sine';
                    osc.frequency.value = freq;
                    gain.gain.setValueAtTime(0.001, ctx.currentTime + startAt);
                    gain.gain.exponentialRampToValueAtTime(0.25, ctx.currentTime + startAt + 0.02);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + startAt + duration);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(ctx.currentTime + startAt);
                    osc.stop(ctx.currentTime + startAt + duration);
                };
                beep(440, 0, 0.18);
                beep(660, 0.25, 0.30);
            } catch (e) {
                // Silenciar errores de audio (politicas de autoplay, etc.)
                console.warn('inning beep failed', e);
            }
        },

        // MEJ-1: Dispara la animacion slide del inning/half.
        // Agrega `is-flipping` por 700ms y la quita para que el keyframe
        // pueda volver a dispararse en el siguiente cambio.
        triggerInningFlip() {
            const hosts = document.querySelectorAll('[data-inning-anim]');
            hosts.forEach(el => {
                el.classList.remove('is-flipping');
                // Forzar reflow para reiniciar la animacion
                void el.offsetWidth;
                el.classList.add('is-flipping');
                setTimeout(() => el.classList.remove('is-flipping'), 700);
            });
        },

        applyState(data) {
            if (!data || !data.state) return;
            const s = data.state;
            const score = data.score || { home: 0, away: 0 };

            const homeScore = document.querySelector('[data-score="home"]');
            const awayScore = document.querySelector('[data-score="away"]');
            if (homeScore) homeScore.textContent = score.home;
            if (awayScore) awayScore.textContent = score.away;

            const inningNum = document.querySelector('[data-inning-number]');
            const inningHalf = document.querySelector('[data-inning-half]');
            // MEJ-1: detectar cambio real de inning/half para disparar animacion
            const inningAnimChanged = inningNum && inningNum.textContent !== String(s.inning);
            const halfAnimChanged = inningHalf && inningHalf.textContent !== (s.half === 'top' ? '▲' : '▼');
            if (inningNum) inningNum.textContent = s.inning;
            if (inningHalf) inningHalf.textContent = s.half === 'top' ? '▲' : '▼';
            if (inningAnimChanged || halfAnimChanged) {
                this.triggerInningFlip();
            }

            // MEJ-5: sincronizar el highlight del equipo que esta bateando.
            // top = away batea, bottom = home batea.
            const homeZone = document.querySelector('[data-team-zone="home"]');
            const awayZone = document.querySelector('[data-team-zone="away"]');
            if (homeZone && awayZone) {
                const newHomeBatting = s.half === 'bottom' ? '1' : '0';
                const newAwayBatting = s.half === 'top' ? '1' : '0';
                if (homeZone.dataset.batting !== newHomeBatting) homeZone.dataset.batting = newHomeBatting;
                if (awayZone.dataset.batting !== newAwayBatting) awayZone.dataset.batting = newAwayBatting;
            }

            this.renderDots('[data-balls]', s.balls, 'bg-emerald-500', 'bg-gray-200', 4);
            this.renderDots('[data-strikes]', s.strikes, 'bg-amber-500', 'bg-gray-200', 3);
            this.renderDots('[data-outs]', s.outs, 'bg-rose-500', 'bg-gray-200', 3);

            this.renderBase('first', s.bases?.first, data.runners?.first);
            this.renderBase('second', s.bases?.second, data.runners?.second);
            this.renderBase('third', s.bases?.third, data.runners?.third);

            // Cachear las bases actuales para que el modal de hit muestre el
            // preview correcto.
            this.lastBases = s.bases || { first: null, second: null, third: null };
            this.lastRunners = data.runners || this.lastRunners || {};
            this.lastScore = score;
            this.stateHalf = s.half;
            this.stateBatterId = s.current_batter_id;
            this.statePitcherId = s.current_pitcher_id;

            // DISI-33: deteccion reactiva de juego finalizado. Si el poll reporta
            // is_completed=true (derivado de $game->isCompleted() en el
            // controller), activamos el flag isFinalized y cambiamos
            // automaticamente al tab Extras para que el usuario vea Stats
            // + Box Score sin hacer click. Las secciones del scoreboard
            // usan x-show="!isFinalized" para desaparecer sin recargar la
            // pagina (Bolas/Strikes/Outs, Pitcheando/Al bate/Prevenido,
            // Diamante, tabs Pitcheo/Bateo, contenido completo de Extras).
            // Es one-way: isFinalized solo pasa de false->true.
            if (data.is_completed === true && !this.isFinalized) {
                this.isFinalized = true;
                this.tab = 'extra';
            }

            // Detectar cierre de inning: si el half cambio (o inning incremento
            // de 1 a 2 con el mismo half), el inning se cerro. Cargar el
            // summary y abrir el modal (solo si no fue el primer poll).
            const halfChanged = this.prevHalf && this.prevHalf !== s.half;
            const inningRolled = (s.half === 'top') && (s.inning !== this.prevInning);
            if ((halfChanged || inningRolled) && (this.prevInning !== 1 || this.prevHalf !== 'top')) {
                // El inning se cerro. Calcular cual se cerro:
                const closedHalf = s.half === 'top' ? 'bottom' : 'top';
                const closedInning = s.half === 'top' ? s.inning - 1 : s.inning;
                const flagKey = closedInning + '-' + closedHalf;
                if (this.inningClosedFlag !== flagKey && data.summary) {
                    this.inningClosedFlag = flagKey;
                    this.inningSummary = data.summary;
                    this.modal = 'inning-summary';
                    this.playInningEndBeep();
                }
            }
            this.prevInning = s.inning;
            this.prevHalf = s.half;
            this.stateBases = s.bases || { first: null, second: null, third: null };

            // Pitcher card
            this.renderAthleteCard('[data-card="pitcher"]', data.pitcher, data.pitcher_stats, 'pitcher-stats', (s) =>
                `${s.pitches} lanz. (${s.strikes}S / ${s.balls}B) · K: ${s.strikeouts} · H: ${s.hits}`);
            // Batter card
            this.renderAthleteCard('[data-card="batter"]', data.batter, data.batter_stats, 'batter-stats', (s) =>
                `AB: ${s.at_bats} · H: ${s.hits} · AVG: ${s.avg.toFixed(3).replace(/^0+/, '')} · BB: ${s.walks} · K: ${s.strikeouts}`);
            // On-deck (prevenido): avatar + nombre
            this.renderOnDeck(data.on_deck);
        },

        renderAthleteCard(selector, athlete, stats, statsAttr, formatStats) {
            const card = document.querySelector(selector);
            if (!card) return;
            const nameEl = card.querySelector('.text-sm.font-bold');
            if (nameEl) {
                if (athlete) {
                    const colorClass = selector.includes('pitcher') ? 'text-indigo-600' : 'text-amber-600';
                    nameEl.innerHTML = `<span class="${colorClass}">#${athlete.number ?? '?'}</span> ${athlete.name}`;
                } else {
                    nameEl.innerHTML = '<span class="text-gray-400 italic font-normal">Sin lanzador</span>';
                }
            }
            const statsEl = card.querySelector(`[data-${statsAttr}]`);
            if (statsEl && stats) {
                statsEl.textContent = formatStats(stats);
            }
            this.renderAthleteAvatar(card, athlete);
        },

        renderOnDeck(athlete) {
            const card = document.querySelector('[data-card="ondeck"]');
            if (!card) return;
            const nameEl = card.querySelector('[data-on-deck-name]');
            if (nameEl) {
                if (athlete) {
                    nameEl.innerHTML = `<span class="text-gray-500">#${athlete.number ?? '?'}</span> ${athlete.name}`;
                } else {
                    nameEl.innerHTML = '<span class="text-gray-400 italic font-normal">Sin prevenido</span>';
                }
            }
            this.renderAthleteAvatar(card, athlete);
        },

        renderAthleteAvatar(scope, athlete) {
            const avatar = scope.querySelector('[data-athlete-avatar]');
            if (!avatar) return;
            if (athlete && athlete.photo_url) {
                avatar.innerHTML = `<img src="${athlete.photo_url}" class="w-full h-full object-cover" data-athlete-photo>`;
            } else if (athlete && athlete.initials) {
                avatar.innerHTML = `<span data-athlete-initials>${athlete.initials}</span>`;
            } else {
                avatar.innerHTML = '<span>?</span>';
            }
        },

        renderDots(selector, count, activeClass, inactiveClass, max) {
            const container = document.querySelector(selector);
            if (!container) return;
            container.innerHTML = '';
            for (let i = 0; i < max; i++) {
                const dot = document.createElement('span');
                dot.className = `w-3 h-3 rounded-full border ${i < count ? activeClass : inactiveClass}`;
                container.appendChild(dot);
            }
        },

        renderBase(base, athleteId, runner) {
            const wrapper = document.querySelector(`[data-base="${base}"]`);
            if (!wrapper) return;
            const inner = wrapper.querySelector(':scope > div');
            if (!inner) return;
            const baseButton = inner.querySelector('button');
            if (!baseButton) return;
            // Eliminar TODOS los hijos EXCEPTO el baseButton (limpia el server-render
            // y evita duplicacion de OPCIONES cuando hay corredor).
            while (inner.children.length > 1) {
                inner.removeChild(inner.lastChild);
            }

            // DISI-27: detectar placeholder de bateador sin roster. El motor
            // guarda el string 'corredor' cuando el bateador no esta identificado
            // (equipo sin roster, atleta eliminado, etc.). Cualquier string en
            // la base se trata como placeholder.
            const isPlaceholder = typeof athleteId === 'string' && athleteId !== '';

            if (athleteId && runner && !isPlaceholder) {
                baseButton.className = 'w-11 h-11 bg-amber-300 border-2 border-amber-500 shadow-md rounded flex items-center justify-center font-bold text-xs text-amber-900 cursor-pointer hover:scale-110 transition-transform';
                baseButton.disabled = false;
                baseButton.innerHTML = `<div class="text-center leading-tight"><div class="text-[9px] font-bold">${base.toUpperCase()}</div><div class="text-[11px] font-black">${runner.number ?? ''}</div></div>`;
                // Agregar el label con el nombre del corredor + boton OPCIONES.
                const label = document.createElement('div');
                label.className = 'bg-white/95 rounded px-1.5 py-0.5 text-[10px] leading-tight text-center shadow-md';
                label.setAttribute('data-runner-label', base);
                const fullName = (runner.first_name || '') + ' ' + (runner.last_name || '');
                label.innerHTML = `<div class="font-bold text-gray-900 truncate max-w-[80px]" title="${this.escapeHtml(fullName)}">${this.escapeHtml(fullName)}</div>`;
                inner.appendChild(label);
                const opBtn = document.createElement('button');
                opBtn.type = 'button';
                opBtn.setAttribute('data-runner-options-btn', base);
                opBtn.className = 'bg-amber-600 hover:bg-amber-700 text-white text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded shadow';
                opBtn.textContent = 'Opciones';
                opBtn.addEventListener('click', () => {
                    window.dispatchEvent(new CustomEvent('open-runner-modal', { detail: { base } }));
                });
                inner.appendChild(opBtn);
            } else if (isPlaceholder) {
                // DISI-27: placeholder visible — base con corredor pero sin identificador.
                baseButton.className = 'w-11 h-11 bg-amber-300 border-2 border-amber-500 shadow-md rounded flex items-center justify-center font-bold text-[10px] text-amber-900 cursor-pointer hover:scale-110 transition-transform';
                baseButton.disabled = false;
                baseButton.innerHTML = `<div class="text-center leading-tight"><div class="text-[9px] font-bold">${base.toUpperCase()}</div><div class="text-[9px] font-black tracking-wider">CORREDOR</div></div>`;
                // Label indicando que no hay identificador
                const label = document.createElement('div');
                label.className = 'bg-white/95 rounded px-1.5 py-0.5 text-[10px] leading-tight text-center shadow-md';
                label.setAttribute('data-runner-label', base);
                label.innerHTML = `<div class="font-bold text-gray-500 italic truncate max-w-[80px]" title="Sin corredor identificado">Sin identificar</div>`;
                inner.appendChild(label);
                const opBtn = document.createElement('button');
                opBtn.type = 'button';
                opBtn.setAttribute('data-runner-options-btn', base);
                opBtn.className = 'bg-amber-600 hover:bg-amber-700 text-white text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded shadow';
                opBtn.textContent = 'Opciones';
                opBtn.addEventListener('click', () => {
                    window.dispatchEvent(new CustomEvent('open-runner-modal', { detail: { base } }));
                });
                inner.appendChild(opBtn);
            } else {
                baseButton.className = 'w-11 h-11 bg-emerald-50/90 border-2 border-white rounded flex items-center justify-center font-bold text-xs text-emerald-700/40 cursor-default';
                baseButton.disabled = true;
                baseButton.textContent = base.toUpperCase();
            }
        },

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML;
        },
    }));
});
