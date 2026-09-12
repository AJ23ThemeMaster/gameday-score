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

        // Confirm remove athlete from roster
        confirmRemove(btn) {
            const name = btn.dataset.athleteName;
            const id = btn.dataset.athleteId;
            if (!confirm(`¿Quitar a «${name}» del roster?`)) return;
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
        modal: null, // 'strike' | 'out-step1' | 'out-step2' | 'hit' | 'bunt' | 'end-inning' | 'inning-summary' | 'end-game' | 'substitute' | 'stats' | 'lineup' | 'runner' | null
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
            await this.poll();
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

        // Finalizar inning
        openEndInningModal() { this.modal = 'end-inning'; },
        async confirmEndInning() {
            try {
                const resp = await fetch(this.endInningUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
                });
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
                    this.toast('Error al finalizar inning', 'error');
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
                    headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
                });
                const data = await resp.json();
                if (data.success) {
                    this.closeModal();
                    this.toast('Juego finalizado: ' + data.result.away_score + '-' + data.result.home_score, 'warning');
                    await this.pollNow();
                } else {
                    this.toast('Error al finalizar juego', 'error');
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
        // Solo se abre si hay un corredor identificado en la base.
        openRunnerModal(base) {
            if (!base || !['first', 'second', 'third'].includes(base)) return;
            const id = (this.lastBases || {})[base];
            if (!id) {
                this.toast('No hay corredor identificado en ' + this.baseLabel(base), 'warning');
                return;
            }
            this.runnerBase = base;
            this.modal = 'runner';
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

        // ============= MEJ-4: MODAL DE LINEUP CON DRAG&DROP =============
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
                // Construir lineups a partir de rosterAway/Home + lineupOrder.
                // Como statsUrl no devuelve roster, hacemos una copia profunda
                // de rosterAway/Home y la mantenemos en el estado del modal.
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
        currentLineup() {
            return this.lineupTeam === 'away' ? this.lineupAway : this.lineupHome;
        },
        currentTeamId() {
            return this.lineupTeam === 'away' ? this.awayTeamId : this.homeTeamId;
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
        async saveLineup() {
            const arr = this.currentLineup();
            const teamId = this.currentTeamId();
            const order = arr.map((a, i) => ({
                athlete_id: a.id,
                lineup_order: i + 1,
            }));
            try {
                const body = new FormData();
                body.append('_token', this.csrf);
                body.append('team_id', teamId);
                order.forEach((o, i) => {
                    body.append(`order[${i}][athlete_id]`, o.athlete_id);
                    body.append(`order[${i}][lineup_order]`, o.lineup_order);
                });
                // La ruta games.lineup.reorder acepta PATCH. Usamos
                // X-HTTP-Method-Override para soportar PATCH real (los
                // formularios HTML solo permiten GET/POST). Como el navegador
                // sí soporta PATCH en fetch, mandamos PATCH directo.
                const res = await fetch(this.lineupReorderUrl, {
                    method: 'PATCH',
                    body,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    credentials: 'same-origin',
                });
                const data = await res.json();
                if (data.success) {
                    this.toast('Lineup guardado', 'success');
                    this.lineupDirty = false;
                    // Sincronizar rosterAway/Home con el nuevo orden
                    if (this.lineupTeam === 'away') {
                        this.rosterAway = JSON.parse(JSON.stringify(arr));
                    } else {
                        this.rosterHome = JSON.parse(JSON.stringify(arr));
                    }
                } else {
                    this.toast(data.error || data.message || 'Error al guardar', 'error');
                }
            } catch (e) {
                this.toast('Error de red al guardar lineup: ' + e.message, 'error');
            }
        },

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
            // Eliminar labels/buttons que pueda haber añadido un poll anterior
            const existingLabel = inner.querySelector('[data-runner-label]');
            if (existingLabel) existingLabel.remove();
            const existingOpBtn = inner.querySelector('[data-runner-options-btn]');
            if (existingOpBtn) existingOpBtn.remove();
            if (athleteId && runner) {
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
                // Re-bind: como Alpine re-renderiza, usamos window.__scoreboardOpenRunner
                // que setea el base y abre el modal. Pero como el scoreboardApp vive en Alpine,
                // podemos llamar directamente al metodo expuesto.
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
