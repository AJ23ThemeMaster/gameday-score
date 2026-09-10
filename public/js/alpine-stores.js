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
        homeName: config.homeName,
        awayName: config.awayName,
        pollInterval: null,
        pollStatus: 'Conectado',
        isPolling: false,

        start() {
            // Polling cada 5s
            this.pollInterval = setInterval(() => this.poll(), 5000);
            // Poll inmediato
            this.poll();
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

        applyState(data) {
            if (!data || !data.state) return;
            const s = data.state;
            const score = data.score || { home: 0, away: 0 };

            // Scores
            const homeScore = document.querySelector('[data-score="home"]');
            const awayScore = document.querySelector('[data-score="away"]');
            if (homeScore) homeScore.textContent = score.home;
            if (awayScore) awayScore.textContent = score.away;

            // Inning
            const inningNum = document.querySelector('[data-inning-number]');
            const inningHalf = document.querySelector('[data-inning-half]');
            if (inningNum) inningNum.textContent = s.inning;
            if (inningHalf) inningHalf.textContent = s.half === 'top' ? '▲' : '▼';

            // Count: balls / strikes / outs
            this.renderDots('[data-balls]', s.balls, 'bg-emerald-500', 'bg-gray-200', 4);
            this.renderDots('[data-strikes]', s.strikes, 'bg-amber-500', 'bg-gray-200', 3);
            this.renderDots('[data-outs]', s.outs, 'bg-rose-500', 'bg-gray-200', 3);

            // Bases
            this.renderBase('first', s.bases?.first, data.runners?.first);
            this.renderBase('second', s.bases?.second, data.runners?.second);
            this.renderBase('third', s.bases?.third, data.runners?.third);
        },

        renderDots(selector, count, activeClass, inactiveClass, max) {
            const container = document.querySelector(selector);
            if (!container) return;
            // Limpia y regenera los dots
            container.innerHTML = '';
            for (let i = 0; i < max; i++) {
                const dot = document.createElement('span');
                dot.className = `w-3 h-3 rounded-full border ${i < count ? activeClass : inactiveClass}`;
                container.appendChild(dot);
            }
        },

        renderBase(base, athleteId, runner) {
            const el = document.querySelector(`[data-base="${base}"] > div`);
            if (!el) return;
            if (athleteId && runner) {
                el.className = 'w-11 h-11 bg-amber-300 border-2 border-amber-500 shadow-md rounded flex items-center justify-center font-bold text-xs text-amber-900';
                el.innerHTML = `<div class="text-center leading-tight"><div class="text-[9px] font-bold">${base.toUpperCase()}</div><div class="text-[11px] font-black">${runner.number ?? ''}</div></div>`;
            } else {
                el.className = 'w-11 h-11 bg-emerald-50/90 border-2 border-white rounded flex items-center justify-center font-bold text-xs text-emerald-700/40';
                el.textContent = base.toUpperCase();
            }
        },
    }));
});
