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
        homeName: config.homeName,
        awayName: config.awayName,
        csrf: config.csrf,
        pollInterval: null,
        pollStatus: 'Conectado',
        isPolling: false,
        isPitching: false,
        modal: null, // 'strike' | 'out-step1' | 'out-step2' | null
        outSubtype: null,
        defensiveSequence: [],

        start() {
            this.pollInterval = setInterval(() => this.poll(), 5000);
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
                // Refresca el state inmediatamente (no espera al poll).
                this.poll();
                if (data.walk) this.toast('Base por bolas', 'info');
                else if (data.strikeout) this.toast('Ponche', 'info');
                else if (data.end_half) this.toast('Fin del inning', 'warning');
                else this.toast('Jugada registrada', 'success');
            } catch (e) {
                this.toast('Error de red: ' + e.message, 'error');
            } finally {
                this.isPitching = false;
            }
        },

        sendBall() { this.sendPitch({ type: 'ball' }); },
        sendFoul() { this.sendPitch({ type: 'foul' }); },
        sendStrike(subtype) { this.sendPitch({ type: 'strike', subtype }); this.closeModal(); },
        sendOut(subtype, defensiveSequence = []) {
            this.sendPitch({ type: 'out', subtype, defensive_sequence: defensiveSequence });
            this.closeModal();
        },

        openStrikeModal() { this.modal = 'strike'; },
        openOutStep1() { this.modal = 'out-step1'; },
        openOutStep2(subtype) { this.outSubtype = subtype; this.defensiveSequence = []; this.modal = 'out-step2'; },
        confirmOut() { this.sendOut(this.outSubtype, this.defensiveSequence); },
        addFielder(pos) { this.defensiveSequence.push(pos); },
        removeFielder(i) { this.defensiveSequence.splice(i, 1); },
        closeModal() { this.modal = null; this.outSubtype = null; this.defensiveSequence = []; },

        toast(message, level = 'success') {
            window.dispatchEvent(new CustomEvent('toast', { detail: { message, level } }));
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
            if (inningNum) inningNum.textContent = s.inning;
            if (inningHalf) inningHalf.textContent = s.half === 'top' ? '▲' : '▼';

            this.renderDots('[data-balls]', s.balls, 'bg-emerald-500', 'bg-gray-200', 4);
            this.renderDots('[data-strikes]', s.strikes, 'bg-amber-500', 'bg-gray-200', 3);
            this.renderDots('[data-outs]', s.outs, 'bg-rose-500', 'bg-gray-200', 3);

            this.renderBase('first', s.bases?.first, data.runners?.first);
            this.renderBase('second', s.bases?.second, data.runners?.second);
            this.renderBase('third', s.bases?.third, data.runners?.third);

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
