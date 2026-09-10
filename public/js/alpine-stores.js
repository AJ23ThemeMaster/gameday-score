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
});
