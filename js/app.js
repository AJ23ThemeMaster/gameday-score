const APP_VERSION = 'v1.1.5'; // Update this when releasing new versions

// Application state
const state = {
    currentGame: null,
    pastGames: [],
    settings: {
        innings: 9, // Deprecated, kept for backward compat.
        allowDelete: false,
        showHits: true,
        showErrors: true,
        enableRoster: true,
        theme: 'dark',
        gameRules: [
            {
                id: 'rule-default',
                name: 'Béisbol Por Defecto (9 Innings)',
                maxInnings: 9,
                maxRunsPerInning: 0,
                mercyRule: { enabled: false, diff: 10, atInning: 5 },
                pitchCountLimit: 0
            },
            {
                id: 'rule-infantil',
                name: 'Beisbol Menor (6 Innings)',
                maxInnings: 6,
                maxRunsPerInning: 0,
                mercyRule: { enabled: true, diff: 10, atInning: 1 },
                pitchCountLimit: 85
            }
        ]
    }
};

// Application Views
const views = [
    'home-view',
    'new-game-view',
    'past-games-view',
    'settings-view'
];

document.addEventListener('DOMContentLoaded', () => {
    // Register Service Worker for PWA
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('./sw.js')
                .then(reg => {
                    console.log('ServiceWorker registered', reg.scope);

                    // Handle SW updates
                    reg.addEventListener('updatefound', () => {
                        const newWorker = reg.installing;
                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                // New update available
                                Swal.fire({
                                    title: '¡Actualización Disponible!',
                                    text: 'Hay una nueva versión de la aplicación. ¿Deseas recargar para aplicarla?',
                                    icon: 'info',
                                    showCancelButton: true,
                                    confirmButtonColor: '#1d9bf0',
                                    cancelButtonColor: '#6c757d',
                                    confirmButtonText: 'Sí, actualizar',
                                    cancelButtonText: 'Más tarde'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        newWorker.postMessage({ type: 'SKIP_WAITING' });
                                    }
                                });
                            }
                        });
                    });
                })
                .catch(err => console.log('ServiceWorker registration failed', err));

            // Reload when new SW activates
            let refreshing = false;
            navigator.serviceWorker.addEventListener('controllerchange', () => {
                if (!refreshing) {
                    window.location.reload();
                    refreshing = true;
                }
            });
        });
    }

    // Initialize the app
    initApp().then(() => {
        // Initialize Rule Modal
        initRuleModalLogic();
    });
});

/**
 * Migration from localStorage to IndexedDB
 */
async function migrateFromLocalStorage() {
    const migrationFlag = 'indexeddb_migrated';
    if (localStorage.getItem(migrationFlag)) return;

    console.log('Starting migration from localStorage to IndexedDB...');

    try {
        // 1. Settings
        const savedSettings = localStorage.getItem('appSettings');
        if (savedSettings) {
            await DB.saveSettings(JSON.parse(savedSettings));
        }

        // 2. Current Game State
        const currentGameState = localStorage.getItem('currentGameState');
        if (currentGameState) {
            await DB.saveCurrentGame(JSON.parse(currentGameState));
        }

        // 3. Game History
        const gameHistory = localStorage.getItem('gameHistory');
        if (gameHistory) {
            const historyArray = JSON.parse(gameHistory);
            for (const game of historyArray) {
                await DB.saveToHistory(game);
            }
        }

        // Set migration flag
        localStorage.setItem(migrationFlag, 'true');
        console.log('Migration completed successfully.');

        // Optional: Clean up localStorage (clean only specific keys)
        // localStorage.removeItem('appSettings');
        // localStorage.removeItem('currentGameState');
        // localStorage.removeItem('gameHistory');
    } catch (error) {
        console.error('Migration failed:', error);
    }
}

async function initApp() {
    // Migrate from localStorage if needed
    await migrateFromLocalStorage();

    // Load settings from IndexedDB
    const savedSettings = await DB.getSettings();
    if (savedSettings) {
        state.settings = { ...state.settings, ...savedSettings };
    }

    // Apply Theme
    document.documentElement.setAttribute('data-bs-theme', state.settings.theme || 'dark');

    // Show home view by default
    renderView('home-view', 'tmpl-home-view');

    // Setup generic back button listener
    document.getElementById('back-btn').addEventListener('click', () => {
        renderView('home-view', 'tmpl-home-view');
        hideBackButton();
    });
}

function renderView(viewId, templateId) {
    const appContent = document.getElementById('app-content');
    const template = document.getElementById(templateId);

    // Default: hide share button on view change
    hideShareButton();

    if (!template) {
        // Fallback to placeholder if template not found
        const placeholderTempl = document.getElementById('tmpl-placeholder-view');
        appContent.innerHTML = placeholderTempl.innerHTML;
        const titleEl = appContent.querySelector('.view-title');
        if (titleEl) titleEl.textContent = formatViewTitle(viewId);
    } else {
        appContent.innerHTML = template.innerHTML;
    }

    attachViewListeners(viewId);
}

function formatViewTitle(viewId) {
    switch (viewId) {
        case 'past-games-view': return "Juegos Pasados";
        case 'settings-view': return "Configuración";
        case 'new-game-view': return "Nuevo Juego";
        default: return "Vista";
    }
}

function attachViewListeners(viewId) {
    if (viewId === 'home-view') {
        const buttons = document.querySelectorAll('.menu-actions button');
        buttons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const action = e.currentTarget.getAttribute('data-action');
                let targetViewObj = `tmpl-${action}-view`;
                renderView(`${action}-view`, targetViewObj);
                showBackButton();
            });
        });
        hideBackButton();
    }

    if (viewId === 'new-game-view') {
        const form = document.getElementById('new-game-form');
        // Set default date and time to now
        const now = new Date();
        const dateString = now.toISOString().split('T')[0];
        const tzOffsetMs = now.getTimezoneOffset() * 60000;
        const timeString = (new Date(now.getTime() - tzOffsetMs)).toISOString().slice(11, 16);

        document.getElementById('game-date').value = dateString;
        document.getElementById('game-time').value = timeString;

        const ruleSelect = document.getElementById('game-rule-select');
        if (ruleSelect) {
            ruleSelect.innerHTML = state.settings.gameRules.map(r =>
                `<option value="${r.id}">${r.name} (${r.maxInnings} INN)</option>`
            ).join('');
        }

        form.addEventListener('submit', (e) => {
            e.preventDefault();

            // Get data from form
            const localName = form.querySelector('input[placeholder="Nombre local"]').value || "Local";
            const visitanteName = form.querySelector('input[placeholder="Nombre visitante"]').value || "Visitante";
            const category = document.getElementById('game-category').value;
            const date = document.getElementById('game-date').value;
            const time = document.getElementById('game-time').value;
            const stadium = document.getElementById('game-stadium').value.trim();
            const selectedRuleId = ruleSelect ? ruleSelect.value : null;

            let ruleSet = state.settings.gameRules.find(r => r.id === selectedRuleId);
            if (!ruleSet) ruleSet = state.settings.gameRules[0];

            // Deep clone the ruleSet so it remains immutable for this game
            const gameRule = JSON.parse(JSON.stringify(ruleSet));

            // Simple Game State Setup
            state.currentGame = {
                id: Date.now(),
                local: localName,
                visitante: visitanteName,
                stadium: stadium,
                category: category,
                date: date,
                time: time,
                inning: 1,
                half: 'Top', // Top = visitante, Bottom = local
                outs: 0,
                strikes: 0,
                balls: 0,
                runsLocal: 0,
                runsVisitante: 0,
                hitsLocal: 0,
                hitsVisitante: 0,
                errorsLocal: 0,
                errorsVisitante: 0,
                ruleSet: gameRule,
                pitchCount: { local: 0, visitante: 0 },
                inningsData: Array.from({ length: gameRule.maxInnings }, () => ({ local: '', visitante: '' })),
                bases: { 1: false, 2: false, 3: false },
                rosterLocal: Array.from({ length: 11 }, () => ({ order: '', pos: '', number: '', name: '' })),
                rosterVisitante: Array.from({ length: 11 }, () => ({ order: '', pos: '', number: '', name: '' }))
            };

            // Save to IndexedDB
            DB.saveCurrentGame(state.currentGame).then(() => {
                saveToHistory(state.currentGame);
            });

            const btn = form.querySelector('button[type="submit"]');
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Preparando...';
            btn.classList.add('disabled');

            setTimeout(() => {
                btn.innerHTML = 'Comenzar Partido';
                btn.classList.remove('disabled');

                // Show Scoreboard
                renderView('scoreboard-view', 'tmpl-scoreboard-view');
                showBackButton(); // Allow returning, although technically a game is active
            }, 800);
        });
    }

    if (viewId === 'scoreboard-view') {
        initScoreboard();
    }

    if (viewId === 'past-games-view') {
        const appContent = document.getElementById('app-content');
        const historyList = appContent.querySelector('#history-list');
        const historyActions = appContent.querySelector('#history-actions');

        DB.getHistory().then(history => {
            if (history.length === 0) {
                historyList.innerHTML = `<div class="text-center text-muted mt-5"><i class="ri-history-line display-1 block mb-3"></i><p class="fs-5">No hay juegos registrados.</p></div>`;
                if (historyActions) historyActions.classList.add('d-none');
                return;
            }

            if (historyActions) {
                if (state.settings.allowDelete) {
                    historyActions.classList.remove('d-none');
                } else {
                    historyActions.classList.add('d-none');
                }
            }

            // Sort descending by ID (newest first)
            history.sort((a, b) => b.id - a.id);

            let html = '';
            history.forEach(game => {
                const dateStr = game.date ? game.date.split('-').reverse().join('-') : '--';
                const statusBadge = game.isFinished
                    ? '<span class="badge bg-danger shadow-sm ms-1" style="font-size: 0.70rem;">FINAL</span>'
                    : `<span class="badge bg-secondary shadow-sm ms-1" style="font-size: 0.70rem;">INNING ${game.inning || 1} ${game.half === 'Top' ? '▲' : '▼'}</span>`;

                const checkboxHtml = state.settings.allowDelete ?
                    `<div class="form-check custom-checkbox ms-1 me-2" style="z-index: 10;">
                        <input class="form-check-input game-select-cb" type="checkbox" value="${game.id}" id="cb-${game.id}">
                     </div>` : '';

                html += `
                <div class="card glass-card mb-3 game-history-card position-relative" data-id="${game.id}" style="cursor: pointer; transition: transform 0.2s;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="d-flex align-items-center">
                                ${checkboxHtml}
                                <span class="badge bg-primary text-uppercase tracking-wider px-2 py-1">${game.category || 'General'}</span>
                                ${statusBadge}
                            </div>
                            <small class="text-muted"><i class="ri-calendar-event-line"></i> ${dateStr}</small>
                        </div>
                        <div class="row text-center fw-bold fs-5 mt-3">
                            <div class="col-5 text-truncate" title="${game.visitante}">${game.visitante}</div>
                            <div class="col-2 text-muted fw-normal" style="font-size: 0.9rem;">vs</div>
                            <div class="col-5 text-truncate" title="${game.local}">${game.local}</div>
                        </div>
                        <div class="row text-center display-5 fw-bold mt-1 text-white">
                            <div class="col-5">${game.runsVisitante || 0}</div>
                            <div class="col-2">-</div>
                            <div class="col-5">${game.runsLocal || 0}</div>
                        </div>
                    </div>
                </div>`;
            });

            historyList.innerHTML = html;

            // Add listeners
            appContent.querySelectorAll('.game-history-card').forEach(card => {
                card.addEventListener('click', function (e) {
                    // Ignore clicks if they landed exactly on the checkbox wrapper to prevent false triggers
                    if (e.target.closest('.custom-checkbox')) return;

                    const gameId = parseInt(this.getAttribute('data-id'));
                    const selectedGame = history.find(g => g.id === gameId);
                    if (selectedGame) {
                        state.currentGame = selectedGame;
                        DB.saveCurrentGame(state.currentGame).then(() => {
                            renderView('scoreboard-view', 'tmpl-scoreboard-view');
                        });
                    }
                });
            });

            if (state.settings.allowDelete) {
                appContent.querySelectorAll('.game-select-cb').forEach(cb => {
                    cb.addEventListener('click', (e) => {
                        e.stopPropagation();
                    });
                    cb.addEventListener('change', () => {
                        const anyChecked = Array.from(appContent.querySelectorAll('.game-select-cb')).some(c => c.checked);
                        const btnDeleteSelected = appContent.querySelector('#btn-delete-selected');
                        if (btnDeleteSelected) btnDeleteSelected.disabled = !anyChecked;
                    });
                });

                const btnDeleteSelected = appContent.querySelector('#btn-delete-selected');
                const btnDeleteAll = appContent.querySelector('#btn-delete-all');

                if (btnDeleteSelected) {
                    const newBtnSel = btnDeleteSelected.cloneNode(true);
                    btnDeleteSelected.parentNode.replaceChild(newBtnSel, btnDeleteSelected);
                    newBtnSel.addEventListener('click', async () => {
                        const checkedIds = Array.from(appContent.querySelectorAll('.game-select-cb:checked')).map(cb => parseInt(cb.value));
                        if (checkedIds.length > 0) {
                            const res = await Swal.fire({
                                title: '¿Eliminar seleccionados?',
                                text: `Se borrarán ${checkedIds.length} juegos permanentemente.`,
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#dc3545',
                                cancelButtonColor: '#6c757d',
                                confirmButtonText: 'Sí, eliminar',
                                cancelButtonText: 'Cancelar'
                            });

                            if (res.isConfirmed) {
                                for (const id of checkedIds) {
                                    await DB.deleteFromHistory(id);
                                }

                                if (state.currentGame && checkedIds.includes(state.currentGame.id)) {
                                    state.currentGame = null;
                                    await DB.removeCurrentGame();
                                }
                                renderView('past-games-view', 'tmpl-past-games-view');

                                Swal.fire({
                                    title: 'Eliminados',
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                            }
                        }
                    });
                }

                if (btnDeleteAll) {
                    const newBtnAll = btnDeleteAll.cloneNode(true);
                    btnDeleteAll.parentNode.replaceChild(newBtnAll, btnDeleteAll);
                    newBtnAll.addEventListener('click', async () => {
                        const res = await Swal.fire({
                            title: '¿ELIMINAR TODOS?',
                            text: "Esta acción borrará todo tu historial por completo.",
                            icon: 'error',
                            showCancelButton: true,
                            confirmButtonColor: '#dc3545',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: 'Sí, borrar todo',
                            cancelButtonText: 'Cancelar'
                        });

                        if (res.isConfirmed) {
                            await DB.clearHistory();
                            state.currentGame = null;
                            await DB.removeCurrentGame();
                            renderView('past-games-view', 'tmpl-past-games-view');

                            Swal.fire({
                                title: 'Historial vacío',
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            });
                        }
                    });
                }
            }
        });
    }

    if (viewId === 'settings-view') {
        const appContent = document.getElementById('app-content');

        // Render Rules List
        renderRulesList();

        const btnCreateRule = appContent.querySelector('#btn-create-rule');
        if (btnCreateRule) {
            btnCreateRule.addEventListener('click', () => {
                openRuleModal();
            });
        }

        const inputAllowDelete = appContent.querySelector('#setting-allow-delete');
        const inputShowHits = appContent.querySelector('#setting-show-hits');
        const inputShowErrors = appContent.querySelector('#setting-show-errors');
        const inputEnableRoster = appContent.querySelector('#setting-enable-roster');
        const inputTheme = appContent.querySelector('#setting-theme');
        const btnSave = appContent.querySelector('#btn-save-settings');

        if (inputAllowDelete) inputAllowDelete.checked = !!state.settings.allowDelete;

        if (inputShowHits) inputShowHits.checked = state.settings.showHits !== false;
        if (inputShowErrors) inputShowErrors.checked = state.settings.showErrors !== false;
        if (inputEnableRoster) inputEnableRoster.checked = state.settings.enableRoster !== false;
        if (inputTheme) inputTheme.value = state.settings.theme || 'dark';

        const versionDisplay = appContent.querySelector('#app-version-display');
        const btnCheckUpdates = appContent.querySelector('#btn-check-updates');

        if (versionDisplay) versionDisplay.textContent = APP_VERSION;

        if (btnCheckUpdates) {
            // Remove previous listeners
            const clonedBtnUpdate = btnCheckUpdates.cloneNode(true);
            btnCheckUpdates.parentNode.replaceChild(clonedBtnUpdate, btnCheckUpdates);

            clonedBtnUpdate.addEventListener('click', () => {
                if ('serviceWorker' in navigator) {
                    clonedBtnUpdate.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                    clonedBtnUpdate.disabled = true;

                    navigator.serviceWorker.ready.then(reg => {
                        reg.update().then(() => {
                            setTimeout(() => {
                                // If an update was found, the `updatefound` listener we set in DOMContentLoaded 
                                // will have fired and shown the Swal.fire prompt. 
                                // If not, we just reset the button and notify that it is up to date.
                                clonedBtnUpdate.innerHTML = '<i class="ri-refresh-line me-1"></i> Buscar';
                                clonedBtnUpdate.disabled = false;

                                if (!reg.installing && !reg.waiting) {
                                    Swal.fire({
                                        title: 'Huelga de Novedades',
                                        text: `Ya tienes instalada la última versión (${APP_VERSION}).`,
                                        icon: 'success',
                                        toast: true,
                                        position: 'bottom-end',
                                        showConfirmButton: false,
                                        timer: 3000
                                    });
                                }
                            }, 800);
                        }).catch(err => {
                            clonedBtnUpdate.innerHTML = '<i class="ri-refresh-line me-1"></i> Buscar';
                            clonedBtnUpdate.disabled = false;
                            console.error('Error checking for updates:', err);
                        });
                    });
                } else {
                    Swal.fire({
                        title: 'No Soportado',
                        text: 'Las actualizaciones en segundo plano no están disponibles en este navegador.',
                        icon: 'info',
                        confirmButtonColor: '#0d6efd'
                    });
                }
            });
        }

        if (btnSave) {
            // Remove previous listeners
            const clonedBtn = btnSave.cloneNode(true);
            btnSave.parentNode.replaceChild(clonedBtn, btnSave);

            clonedBtn.addEventListener('click', () => {
                const isAllow = inputAllowDelete ? inputAllowDelete.checked : false;
                const sHits = inputShowHits ? inputShowHits.checked : true;
                const sErrors = inputShowErrors ? inputShowErrors.checked : true;
                const eRoster = inputEnableRoster ? inputEnableRoster.checked : true;
                const selectedTheme = inputTheme ? inputTheme.value : 'dark';

                state.settings.allowDelete = isAllow;
                state.settings.showHits = sHits;
                state.settings.showErrors = sErrors;
                state.settings.enableRoster = eRoster;
                state.settings.theme = selectedTheme;
                DB.saveSettings(state.settings);

                // Apply immediately
                document.documentElement.setAttribute('data-bs-theme', selectedTheme);

                const originalText = clonedBtn.innerHTML;
                clonedBtn.innerHTML = '<i class="ri-check-line me-2"></i> Guardado';
                clonedBtn.classList.add('btn-success');
                clonedBtn.classList.remove('btn-primary');

                setTimeout(() => {
                    renderView('home-view', 'tmpl-home-view');
                    hideBackButton();
                }, 800);
            });
        }
    }
}

function saveToHistory(gameState) {
    if (!gameState) return;
    DB.saveToHistory(gameState);
}

function showBackButton() {
    document.getElementById('back-btn').classList.remove('d-none');
}

function hideBackButton() {
    document.getElementById('back-btn').classList.add('d-none');
}

function showShareButton() {
    const btn = document.getElementById('share-btn');
    if (btn) {
        btn.classList.remove('d-none');
        // Prevent multiple listeners
        const newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);
        newBtn.addEventListener('click', () => {
            if (typeof window.shareCurrentScoreboard === 'function') {
                window.shareCurrentScoreboard();
            }
        });
    }
}

function hideShareButton() {
    const btn = document.getElementById('share-btn');
    if (btn) btn.classList.add('d-none');
}

// ----------------------------------------------------
// Game Rules Management
// ----------------------------------------------------

function renderRulesList() {
    const listContainer = document.getElementById('settings-rules-list');
    if (!listContainer) return;

    listContainer.innerHTML = '';

    if (!state.settings.gameRules || state.settings.gameRules.length === 0) {
        listContainer.innerHTML = '<div class="text-center text-white-50 small py-3">No hay reglas configuradas.</div>';
        return;
    }

    state.settings.gameRules.forEach(rule => {
        const div = document.createElement('div');
        div.className = 'card bg-black border-secondary border-opacity-50';

        let details = [];
        details.push(`${rule.maxInnings} INN`);
        if (rule.maxRunsPerInning > 0) details.push(`Max ${rule.maxRunsPerInning} C/Inn`);
        if (rule.mercyRule?.enabled) details.push(`Nocaut`);
        if (rule.pitchCountLimit > 0) details.push(`${rule.pitchCountLimit} Pitcheos`);

        div.innerHTML = `
            <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-bold text-white mb-1"><i class="ri-file-list-3-line text-primary me-1"></i> ${rule.name}</div>
                    <div class="text-white-50 small" style="font-size: 0.75rem;">${details.join(' • ')}</div>
                </div>
                <button type="button" class="btn btn-sm btn-dark border-secondary rounded-circle edit-rule-btn" data-id="${rule.id}">
                    <i class="ri-pencil-line"></i>
                </button>
            </div>
        `;
        listContainer.appendChild(div);
    });

    // Attach edit listeners
    listContainer.querySelectorAll('.edit-rule-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            openRuleModal(e.currentTarget.getAttribute('data-id'));
        });
    });
}

function openRuleModal(ruleId = null) {
    const modalEl = document.getElementById('ruleModal');
    if (!modalEl) return;

    const form = document.getElementById('form-rule');
    const title = document.getElementById('modal-rule-title');
    const btnDelete = document.getElementById('btn-delete-rule');

    const isNew = !ruleId;

    form.reset();

    // Toggle options based on default reset
    document.getElementById('rule-mercy-options').classList.add('d-none');
    document.getElementById('rule-pitch-options').classList.add('d-none');

    if (isNew) {
        title.textContent = 'Nueva Plantilla';
        document.getElementById('rule-id').value = '';
        btnDelete.classList.add('d-none');
        document.getElementById('rule-max-runs').value = "5"; // Default selection
    } else {
        const rule = state.settings.gameRules.find(r => r.id === ruleId);
        if (!rule) return;

        title.textContent = 'Editar Plantilla';
        document.getElementById('rule-id').value = rule.id;
        document.getElementById('rule-name').value = rule.name;
        document.getElementById('rule-max-innings').value = rule.maxInnings;
        document.getElementById('rule-max-runs').value = rule.maxRunsPerInning;

        const mercySwitch = document.getElementById('rule-mercy-enabled');
        mercySwitch.checked = rule.mercyRule.enabled;
        if (rule.mercyRule.enabled) {
            document.getElementById('rule-mercy-options').classList.remove('d-none');
        }
        document.getElementById('rule-mercy-diff').value = rule.mercyRule.diff;
        document.getElementById('rule-mercy-inning').value = rule.mercyRule.atInning;

        const pitchSwitch = document.getElementById('rule-pitch-enabled');
        pitchSwitch.checked = rule.pitchCountLimit > 0;
        if (rule.pitchCountLimit > 0) {
            document.getElementById('rule-pitch-options').classList.remove('d-none');
            document.getElementById('rule-pitch-limit').value = rule.pitchCountLimit;
        } else {
            document.getElementById('rule-pitch-limit').value = 85;
        }

        btnDelete.classList.remove('d-none');
    }

    const ruleModal = new bootstrap.Modal(modalEl);
    ruleModal.show();
}

function initRuleModalLogic() {
    const modalEl = document.getElementById('ruleModal');
    if (!modalEl) return;

    // Toggle options
    document.getElementById('rule-mercy-enabled').addEventListener('change', (e) => {
        const opts = document.getElementById('rule-mercy-options');
        e.target.checked ? opts.classList.remove('d-none') : opts.classList.add('d-none');
    });

    document.getElementById('rule-pitch-enabled').addEventListener('change', (e) => {
        const opts = document.getElementById('rule-pitch-options');
        e.target.checked ? opts.classList.remove('d-none') : opts.classList.add('d-none');
    });

    // Save Rule
    document.getElementById('btn-save-rule').addEventListener('click', () => {
        const id = document.getElementById('rule-id').value || 'rule-' + Date.now();
        const name = document.getElementById('rule-name').value.trim();
        const maxInnings = parseInt(document.getElementById('rule-max-innings').value);
        const maxRunsPerInning = parseInt(document.getElementById('rule-max-runs').value);

        if (!name || isNaN(maxInnings) || maxInnings < 1) {
            Swal.fire({ toast: true, position: 'bottom-end', title: 'Campos inválidos', icon: 'error', showConfirmButton: false, timer: 3000 });
            return;
        }

        const mercyEnabled = document.getElementById('rule-mercy-enabled').checked;
        const mercyDiff = parseInt(document.getElementById('rule-mercy-diff').value) || 10;
        const mercyInning = parseInt(document.getElementById('rule-mercy-inning').value) || 4;

        const pitchEnabled = document.getElementById('rule-pitch-enabled').checked;
        const pitchLimit = pitchEnabled ? (parseInt(document.getElementById('rule-pitch-limit').value) || 85) : 0;

        const newRule = {
            id,
            name,
            maxInnings,
            maxRunsPerInning,
            mercyRule: {
                enabled: mercyEnabled,
                diff: mercyDiff,
                atInning: mercyInning
            },
            pitchCountLimit: pitchLimit
        };

        if (!state.settings.gameRules) state.settings.gameRules = [];

        const existingIdx = state.settings.gameRules.findIndex(r => r.id === id);
        if (existingIdx >= 0) {
            state.settings.gameRules[existingIdx] = newRule;
        } else {
            state.settings.gameRules.push(newRule);
        }

        // Save
        DB.saveSettings(state.settings);

        bootstrap.Modal.getInstance(modalEl).hide();
        renderRulesList();

        Swal.fire({ toast: true, position: 'bottom-end', title: 'Plantilla Guardada', icon: 'success', showConfirmButton: false, timer: 2000 });
    });

    // Delete Rule
    document.getElementById('btn-delete-rule').addEventListener('click', () => {
        const id = document.getElementById('rule-id').value;
        if (!id) return;

        Swal.fire({
            title: '¿Mandar al retiro?',
            text: 'Esta plantilla será eliminada permanentemente.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                state.settings.gameRules = state.settings.gameRules.filter(r => r.id !== id);
                DB.saveSettings(state.settings);

                bootstrap.Modal.getInstance(modalEl).hide();
                renderRulesList();

                Swal.fire({ toast: true, position: 'bottom-end', title: 'Plantilla Eliminada', icon: 'success', showConfirmButton: false, timer: 2000 });
            }
        });
    });
}
