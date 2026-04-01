// Scoreboard logic initialization
function initScoreboard() {
    const game = state.currentGame;
    if (!game) return; // Exit if no active game

    const appContent = document.getElementById('app-content');

    // Set Team Names correctly ensuring we target visible DOM
    const localNameEl = appContent.querySelector('#team-local-name');
    const visitanteNameEl = appContent.querySelector('#team-visitante-name');
    if (localNameEl) localNameEl.textContent = game.local || "Local";
    if (visitanteNameEl) visitanteNameEl.textContent = game.visitante || "Visitante";

    // Format Date from YYYY-MM-DD to DD-MM-YYYY
    let displayDate = "--";
    if (game.date) {
        const parts = game.date.split('-');
        if (parts.length === 3) displayDate = `${parts[2]}-${parts[1]}-${parts[0]}`;
        else displayDate = game.date;
    }

    // Format time from 24h (HH:mm) to 12h (g:i a)
    let displayTime = "--";
    if (game.time) {
        const parts = game.time.split(':');
        if (parts.length >= 2) {
            let h = parseInt(parts[0], 10);
            const m = parts[1];
            const ampm = h >= 12 ? 'pm' : 'am';
            h = h % 12;
            h = h ? h : 12;
            displayTime = `${h}:${m} ${ampm}`;
        } else {
            displayTime = game.time;
        }
    }

    // Set Game Info
    const stadiumEl = appContent.querySelector('#game-stadium-display');
    const catEl = appContent.querySelector('#game-category-display');
    const dateEl = appContent.querySelector('#game-date-display');
    const timeEl = appContent.querySelector('#game-time-display');

    if (stadiumEl) {
        if (game.stadium) {
            stadiumEl.querySelector('span').textContent = game.stadium;
            stadiumEl.classList.remove('d-none');
        } else {
            stadiumEl.classList.add('d-none');
        }
    }

    if (catEl) {
        catEl.textContent = game.category || "General";
        if (game.isFinished) {
            catEl.innerHTML += ' <span class="badge bg-danger ms-2 shadow-sm" style="font-size: 0.75rem;">FINALIZADO</span>';
        }
    }
    if (dateEl) dateEl.textContent = displayDate;
    if (timeEl) timeEl.textContent = displayTime;

    // Set initial scores
    updateScoreTotal();

    // Render Innings Table
    renderInningsTable(game.inningsData.length);

    // Highlight Batting Team
    updateActiveBattingTeam();

    // Setup Roster logic
    setupRoster();

    showShareButton();

    // Setup Controls Listeners
    setupIndicators();
}

function updateScoreTotal() {
    const game = state.currentGame;
    let rL = 0, rV = 0;

    game.inningsData.forEach(i => {
        rL += parseInt(i.local) || 0;
        rV += parseInt(i.visitante) || 0;
    });

    game.runsLocal = rL;
    game.runsVisitante = rV;

    const appContent = document.getElementById('app-content');
    const scoreLocalEl = appContent.querySelector('#score-local');
    const scoreVisitanteEl = appContent.querySelector('#score-visitante');

    if (scoreLocalEl) scoreLocalEl.textContent = game.runsLocal;
    if (scoreVisitanteEl) scoreVisitanteEl.textContent = game.runsVisitante;

    const tbVisC = appContent.querySelector('#score-total-visitante-table');
    const tbLocC = appContent.querySelector('#score-total-local-table');
    if (tbVisC) tbVisC.textContent = game.runsVisitante;
    if (tbLocC) tbLocC.textContent = game.runsLocal;
}

function renderInningsTable(totalInnings = 9) {
    const appContent = document.getElementById('app-content');
    const headersRaw = appContent.querySelector('#inning-headers');
    const visRow = appContent.querySelector('#inning-visitante');
    const locRow = appContent.querySelector('#inning-local');

    let htmlHeaders = '';
    let htmlVis = '';
    let htmlLoc = '';

    for (let i = 1; i <= totalInnings; i++) {
        htmlHeaders += `<th>${i}</th>`;

        let visVal = state.currentGame.inningsData[i - 1]?.visitante;
        let locVal = state.currentGame.inningsData[i - 1]?.local;
        visVal = (visVal === undefined) ? '' : visVal;
        locVal = (locVal === undefined) ? '' : locVal;

        htmlVis += `<td class="inning-cell" data-inning="${i}" data-team="visitante">${visVal}</td>`;
        htmlLoc += `<td class="inning-cell" data-inning="${i}" data-team="local">${locVal}</td>`;
    }

    // Append C (Carreras)
    htmlHeaders += `<th class="border-start border-3 border-secondary fw-bolder bg-black text-white">C</th>`;
    htmlVis += `<td class="border-start border-3 border-secondary fw-bold bg-dark text-white" id="score-total-visitante-table">${state.currentGame.runsVisitante}</td>`;
    htmlLoc += `<td class="border-start border-3 border-secondary fw-bold bg-dark text-white" id="score-total-local-table">${state.currentGame.runsLocal}</td>`;

    // Append H (Hits)
    if (state.settings.showHits) {
        htmlHeaders += `<th class="bg-black text-white">H</th>`;
        htmlVis += `<td class="hit-cell fw-bold bg-dark text-white" data-team="visitante" style="cursor: pointer;">${state.currentGame.hitsVisitante || 0}</td>`;
        htmlLoc += `<td class="hit-cell fw-bold bg-dark text-white" data-team="local" style="cursor: pointer;">${state.currentGame.hitsLocal || 0}</td>`;
    }

    // Append E (Errores)
    if (state.settings.showErrors) {
        htmlHeaders += `<th class="bg-black text-white">E</th>`;
        htmlVis += `<td class="error-cell fw-bold bg-dark text-white" data-team="visitante" style="cursor: pointer;">${state.currentGame.errorsVisitante || 0}</td>`;
        htmlLoc += `<td class="error-cell fw-bold bg-dark text-white" data-team="local" style="cursor: pointer;">${state.currentGame.errorsLocal || 0}</td>`;
    }

    if (headersRaw) headersRaw.innerHTML = htmlHeaders;
    if (visRow) visRow.innerHTML = htmlVis;
    if (locRow) locRow.innerHTML = htmlLoc;

    // Attach click to edit cells
    if (!state.currentGame.isFinished) {
        appContent.querySelectorAll('.inning-cell').forEach(cell => {
            cell.addEventListener('click', async function () {
                const inning = this.getAttribute('data-inning');
                const team = this.getAttribute('data-team');

                const { value: val } = await Swal.fire({
                    title: `Carreras Inning ${inning}`,
                    text: `Equipo: ${team}`,
                    input: 'text',
                    inputValue: this.textContent,
                    showCancelButton: true,
                    confirmButtonText: 'Guardar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#0d6efd',
                    cancelButtonColor: '#6c757d'
                });

                if (val !== undefined && val !== null) {
                    const strVal = String(val).trim();
                    const numeric = parseInt(strVal);
                    let finalVal = isNaN(numeric) ? '' : numeric;

                    const ruleSet = state.currentGame.ruleSet;
                    if (typeof finalVal === 'number' && ruleSet?.maxRunsPerInning > 0) {
                        if (finalVal > ruleSet.maxRunsPerInning) {
                            Swal.fire({
                                title: 'Tope de Carreras',
                                text: `El límite es de ${ruleSet.maxRunsPerInning} carreras por Inning según la Regla Activa.`,
                                icon: 'warning',
                                confirmButtonColor: '#0d6efd'
                            });
                            finalVal = ruleSet.maxRunsPerInning;
                        }
                    }

                    this.textContent = finalVal;
                    // Update specific index safely avoiding array reference cloning issues
                    let currentInningObj = { ...state.currentGame.inningsData[inning - 1] };
                    currentInningObj[team] = finalVal;
                    state.currentGame.inningsData[inning - 1] = currentInningObj;

                    updateScoreTotal();
                    saveState();
                    setTimeout(checkGameLimits, 300);
                }
            });
        });

        // HITS
        appContent.querySelectorAll('.hit-cell').forEach(cell => {
            cell.addEventListener('click', async function () {
                const team = this.getAttribute('data-team');
                const { value: val } = await Swal.fire({
                    title: `Hits`,
                    text: `Equipo: ${team}`,
                    input: 'number',
                    inputValue: this.textContent,
                    showCancelButton: true,
                    confirmButtonText: 'Guardar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#0d6efd',
                    cancelButtonColor: '#6c757d'
                });

                if (val !== undefined && val !== null) {
                    const numeric = parseInt(val) || 0;
                    this.textContent = numeric;
                    if (team === 'visitante') state.currentGame.hitsVisitante = numeric;
                    else state.currentGame.hitsLocal = numeric;
                    saveState();
                }
            });
        });

        // ERRORES
        appContent.querySelectorAll('.error-cell').forEach(cell => {
            cell.addEventListener('click', async function () {
                const team = this.getAttribute('data-team');
                const { value: val } = await Swal.fire({
                    title: `Errores`,
                    text: `Equipo: ${team}`,
                    input: 'number',
                    inputValue: this.textContent,
                    showCancelButton: true,
                    confirmButtonText: 'Guardar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#0d6efd',
                    cancelButtonColor: '#6c757d'
                });

                if (val !== undefined && val !== null) {
                    const numeric = parseInt(val) || 0;
                    this.textContent = numeric;
                    if (team === 'visitante') state.currentGame.errorsVisitante = numeric;
                    else state.currentGame.errorsLocal = numeric;
                    saveState();
                }
            });
        });
    }
}

function setupIndicators() {
    // Initial sync
    updateAllBSO();
    updateBasesVisuals();

    const appContent = document.getElementById('app-content');

    // Setup Delete Game Button
    const delContainer = appContent.querySelector('#delete-game-container');
    const btnDeleteGame = appContent.querySelector('#btn-delete-game');

    if (delContainer && btnDeleteGame) {
        if (state.settings.allowDelete && state.currentGame.isFinished) {
            delContainer.classList.remove('d-none');
            // Clone to avoid multiple listeners
            const clonedBtn = btnDeleteGame.cloneNode(true);
            btnDeleteGame.parentNode.replaceChild(clonedBtn, btnDeleteGame);

            clonedBtn.addEventListener('click', async () => {
                const res = await Swal.fire({
                    title: '¿Eliminar Juego?',
                    text: 'Se borrará de forma permanente del historial.',
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                });

                if (res.isConfirmed) {
                    await DB.deleteFromHistory(state.currentGame.id);
                    state.currentGame = null;
                    await DB.removeCurrentGame();

                    Swal.fire({
                        title: 'Eliminado',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    });

                    if (typeof renderView === 'function') {
                        renderView('home-view', 'tmpl-home-view');
                        const backBtn = document.getElementById('back-btn');
                        if (backBtn) backBtn.classList.add('d-none');
                    }
                }
            });
        } else {
            delContainer.classList.add('d-none');
        }
    }

    // Disable interactions if game is finished
    if (state.currentGame.isFinished) {
        const actionBtnContainer = appContent.querySelector('#action-buttons-container');
        if (actionBtnContainer) actionBtnContainer.classList.add('d-none');
        appContent.querySelectorAll('.bso-row, .base').forEach(el => el.classList.add('pe-none'));
        return;
    }

    // Handling BSO Controls
    appContent.querySelectorAll('.bso-row').forEach(row => {
        const type = row.getAttribute('data-type');
        const btnClear = row.querySelector('.bso-btn');

        row.addEventListener('click', (e) => {
            if (e.target.tagName !== 'BUTTON') {
                const max = type === 'balls' ? 4 : 3;
                let val = state.currentGame[type] + 1;

                if (type === 'balls' || type === 'strikes') {
                    incrementPitchCount();
                }

                if (val >= max) {
                    if (type === 'balls') { // 4 Balls = Walk
                        state.currentGame.balls = 0;
                        state.currentGame.strikes = 0;
                        advanceRunnersWalk();
                    } else if (type === 'strikes') { // 3 Strikes = Out
                        state.currentGame.balls = 0;
                        state.currentGame.strikes = 0;
                        handleOut();
                    } else if (type === 'outs') { // 3 Outs = Inning ends
                        handleOut(true);
                    }
                } else {
                    state.currentGame[type] = val;
                }

                updateAllBSO();
                saveState();
            }
        });

        // Clear Button
        if (btnClear) {
            btnClear.addEventListener('click', (e) => {
                e.stopPropagation();
                state.currentGame[type] = 0;
                updateAllBSO();
                saveState();
            });
        }
    });

    // Handling Bases Status
    if (!state.currentGame.bases) state.currentGame.bases = { 1: false, 2: false, 3: false };
    [1, 2, 3].forEach(b => {
        const baseEl = appContent.querySelector(`#base-${b}`);
        if (baseEl) {
            baseEl.addEventListener('click', () => {
                state.currentGame.bases[b] = !state.currentGame.bases[b];
                updateBasesVisuals();
                saveState();
            });
        }
    });

    // Setup Next Play / Inning Button
    const btnNextPlay = appContent.querySelector('#btn-next-play');
    if (btnNextPlay) {
        btnNextPlay.addEventListener('click', () => {
            advanceHalfInning();
        });
    }

    // Setup Finish Game Button
    const btnFinishGame = appContent.querySelector('#btn-finish-game');
    if (btnFinishGame) {
        btnFinishGame.addEventListener('click', async () => {
            const res = await Swal.fire({
                title: '¿Finalizar juego?',
                text: "No podrás editarlo más.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, finalizar',
                cancelButtonText: 'Cancelar'
            });

            if (res.isConfirmed) {
                endGame();
            }
        });
    }


}

function advanceHalfInning() {
    const game = state.currentGame;

    // Record 0 if empty for the team that just finished batting
    const inningObj = game.inningsData[game.inning - 1];
    if (inningObj) {
        if (game.half === 'Top') {
            if (inningObj.visitante === '') inningObj.visitante = 0;
        } else {
            if (inningObj.local === '') inningObj.local = 0;
        }
    }

    // Switch half inning
    if (game.half === 'Top') {
        game.half = 'Bottom';
    } else {
        // Estamos concluyendo la parte baja del Inning (3 outs).
        // Evaluar si el juego se terminó legalmente ANTES de crear el nuevo inning.
        if (checkGameLimits(true)) {
            return; // Bloquea el avance y la creación del nuevo inning en UI.
        }

        game.half = 'Top';
        game.inning++;

        // Expand table if we pass current max
        if (game.inning > game.inningsData.length) {
            game.inningsData.push({ local: '', visitante: '' });
            renderInningsTable(game.inningsData.length);
        }
    }

    // Clear board states
    game.balls = 0;
    game.strikes = 0;
    game.outs = 0;
    game.bases = { 1: false, 2: false, 3: false };

    updateAllBSO();
    updateBasesVisuals();
    updateActiveBattingTeam();

    // Force native re-render of Innings table if needed
    renderInningsTable(game.inningsData.length);
    saveState();

    // Check Limits
    setTimeout(checkGameLimits, 300);
}

function getInningOrdinal(n) {
    if (n === 1) return "1er";
    if (n === 2) return "2do";
    if (n === 3) return "3er";
    if (n === 4) return "4to";
    if (n === 5) return "5to";
    if (n === 6) return "6to";
    if (n === 7) return "7mo";
    if (n === 8) return "8vo";
    if (n === 9) return "9no";
    if (n === 10) return "10mo";
    return n + "to";
}

function updateActiveBattingTeam() {
    const game = state.currentGame;
    const appContent = document.getElementById('app-content');

    const localNameEl = appContent.querySelector('#team-local-name');
    const visitanteNameEl = appContent.querySelector('#team-visitante-name');

    let activeTeamName = "Visitante";

    if (localNameEl && visitanteNameEl) {
        const batIcon = ' <i class="ri-baseball-fill ms-1" title="Bateando"></i>';

        if (game.half === 'Top') {
            visitanteNameEl.innerHTML = (game.visitante || "Visitante") + batIcon;
            visitanteNameEl.classList.add('text-warning');
            localNameEl.innerHTML = game.local || "Local";
            localNameEl.classList.remove('text-warning');
            activeTeamName = game.visitante || "Visitante";
        } else {
            localNameEl.innerHTML = (game.local || "Local") + batIcon;
            localNameEl.classList.add('text-warning');
            visitanteNameEl.innerHTML = game.visitante || "Visitante";
            visitanteNameEl.classList.remove('text-warning');
            activeTeamName = game.local || "Local";
        }
    }

    const statusContainer = appContent.querySelector('#inning-status-text');
    if (statusContainer) {
        if (game.isFinished) {
            statusContainer.innerHTML = '<h6 class="mb-0 fw-bold text-uppercase tracking-wider text-danger mt-2">Juego Finalizado</h6>';
        } else {
            const parte = game.half === 'Top' ? 'Alta' : 'Baja';
            const ordinal = getInningOrdinal(game.inning);

            statusContainer.innerHTML = `
                <h6 class="mb-0 fw-bold text-uppercase tracking-wider text-white">Parte ${parte} del ${ordinal} Inning</h6>
                <p class="text-warning small mb-0 fw-medium">Batea <span id="current-batting-team" class="fw-bold">${activeTeamName}</span></p>
                
                <div id="pitch-count-container" class="d-none mt-2 d-flex flex-column align-items-center">
                    <div class="text-secondary small mb-1">
                        <i class="ri-user-voice-line me-1"></i> <span id="pitcher-name-display" class="fw-bold text-white-50">Lanzador</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-center">
                        <div class="badge bg-dark rounded-pill border border-secondary px-3 py-1 d-flex align-items-center shadow-sm">
                            <i class="ri-focus-2-line text-warning me-2"></i> 
                            <strong id="pitch-count-current" class="text-white fs-5">0</strong> 
                            <span class="text-white-50 ms-1">/ <span id="pitch-count-max">85</span></span>
                        </div>
                        <button class="btn btn-sm btn-outline-warning rounded-circle ms-2 d-flex align-items-center justify-content-center" style="width:32px; height:32px; padding:0" id="btn-foul-pitch" title="Foul (+1 Pitcheo extra)">
                            <i class="ri-add-line fs-5"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-primary rounded-circle ms-2 d-flex align-items-center justify-content-center" style="width:32px; height:32px; padding:0" id="btn-change-pitcher" title="Cambiar Lanzador">
                            <i class="ri-user-shared-line fs-5"></i>
                        </button>
                    </div>
                </div>
            `;
            updatePitchCountUI();
        }
    }
}

function advanceRunnersWalk() {
    const b = state.currentGame.bases;
    if (b[1]) {
        if (b[2]) {
            if (b[3]) {
                // Bases loaded walk: Run scores
                scoreRun();
            }
            b[3] = true;
        }
        b[2] = true;
    }
    b[1] = true;
    updateBasesVisuals();
}

function handleOut(forceInningEnd = false) {
    if (forceInningEnd) {
        state.currentGame.outs = 3;
    } else {
        state.currentGame.outs++;
    }

    if (state.currentGame.outs >= 3) {
        // Automatically advance the half inning on 3 outs
        advanceHalfInning();
    } else {
        updateAllBSO();
        saveState();
    }
}

function scoreRun() {
    const game = state.currentGame;
    const team = game.half === 'Top' ? 'visitante' : 'local';
    const inningIdx = game.inning - 1;

    if (game.inningsData[inningIdx] === undefined) return;

    // Copy the object to avoid array reference issues
    let currentInningObj = { ...game.inningsData[inningIdx] };
    let currentRuns = parseInt(currentInningObj[team]) || 0;

    currentInningObj[team] = currentRuns + 1;
    game.inningsData[inningIdx] = currentInningObj;

    // Update visuals
    renderInningsTable(game.inningsData.length);
    updateScoreTotal();

    // Check Limits immediately after a Run scores
    setTimeout(checkGameLimits, 300);
}

function updateBasesVisuals() {
    if (!state.currentGame.bases) state.currentGame.bases = { 1: false, 2: false, 3: false };
    const appContent = document.getElementById('app-content');
    [1, 2, 3].forEach(b => {
        const el = appContent.querySelector(`#base-${b}`);
        if (el) {
            if (state.currentGame.bases[b]) el.classList.add('active');
            else el.classList.remove('active');
        }
    });
}

function updateAllBSO() {
    const appContent = document.getElementById('app-content');
    ['balls', 'strikes', 'outs'].forEach(type => {
        const row = appContent.querySelector(`.bso-row[data-type="${type}"]`);
        if (row) {
            const circles = row.querySelectorAll('.indicator-circle');
            updateIndicatorVisuals(circles, state.currentGame[type]);
        }
    });
}

function updateIndicatorVisuals(circles, value) {
    circles.forEach((circle, index) => {
        if (index < value) {
            circle.classList.add('active');
        } else {
            circle.classList.remove('active');
        }
    });
}

function saveState() {
    DB.saveCurrentGame(state.currentGame);
    if (typeof saveToHistory === 'function') {
        saveToHistory(state.currentGame);
    }
}

function setupRoster() {
    const appContent = document.getElementById('app-content');
    if (!appContent) return;

    const rosterBtns = appContent.querySelectorAll('.roster-btn');

    // Hide roster buttons if disabled in settings
    if (state.settings.enableRoster === false) {
        rosterBtns.forEach(btn => btn.classList.add('d-none'));
        return;
    } else {
        rosterBtns.forEach(btn => btn.classList.remove('d-none'));
    }

    const modalTeamName = document.querySelector('#modal-team-name');
    const startersTbody = document.querySelector('#roster-starters-tbody');
    const subsTbody = document.querySelector('#roster-subs-tbody');
    const btnSaveRoster = document.querySelector('#btn-save-roster');

    let currentRosterTeam = null;
    const standardPos = ['P', 'C', '1B', '2B', '3B', 'SS', 'LF', 'CF', 'RF'];

    const createRowHtml = (player, index, isStarter) => {
        const posPlaceholder = isStarter ? (standardPos[index] || 'POS') : 'SUB';
        const posVal = player.pos || (isStarter ? posPlaceholder : '');

        return `
            <tr draggable="true" class="roster-row">
                <td class="p-1 align-middle text-center">
                    <div class="drag-handle"><i class="ri-drag-move-2-line"></i></div>
                </td>
                <td class="p-1 align-middle">
                    <input type="text" class="form-control form-control-sm text-center bg-dark text-white border-secondary roster-input-pos" value="${posVal}" placeholder="${posPlaceholder}">
                </td>
                <td class="p-1 align-middle">
                    <input type="number" class="form-control form-control-sm text-center bg-dark text-white border-secondary roster-input-number" value="${player.number || ''}" placeholder="00">
                </td>
                <td class="p-1 align-middle">
                    <input type="text" class="form-control form-control-sm bg-dark text-white border-secondary roster-input-name" value="${player.name || ''}" placeholder="Nombre del atleta...">
                </td>
            </tr>
        `;
    };

    const initRosterDragAndDrop = () => {
        const rows = document.querySelectorAll('.roster-row');
        let dragSrcEl = null;

        rows.forEach(row => {
            row.addEventListener('dragstart', function (e) {
                dragSrcEl = this;
                e.dataTransfer.effectAllowed = 'move';
                this.classList.add('roster-row-dragging');
                e.dataTransfer.setData('text/html', this.innerHTML);
            });

            row.addEventListener('dragover', function (e) {
                if (e.preventDefault) e.preventDefault();
                this.classList.add('roster-drop-target');
                return false;
            });

            row.addEventListener('dragleave', function () {
                this.classList.remove('roster-drop-target');
            });

            row.addEventListener('drop', function (e) {
                if (e.stopPropagation) e.stopPropagation();

                if (dragSrcEl !== this) {
                    const sourceParent = dragSrcEl.parentNode;
                    const targetParent = this.parentNode;

                    if (sourceParent === targetParent) {
                        const allRows = Array.from(sourceParent.children);
                        const sourceIdx = allRows.indexOf(dragSrcEl);
                        const targetIdx = allRows.indexOf(this);

                        if (sourceIdx < targetIdx) {
                            sourceParent.insertBefore(dragSrcEl, this.nextSibling);
                        } else {
                            sourceParent.insertBefore(dragSrcEl, this);
                        }
                    } else {
                        targetParent.insertBefore(dragSrcEl, this);
                    }
                }
                return false;
            });

            row.addEventListener('dragend', function () {
                document.querySelectorAll('.roster-row').forEach(r => {
                    r.classList.remove('roster-row-dragging');
                    r.classList.remove('roster-drop-target');
                });
            });
        });
    };

    rosterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            currentRosterTeam = btn.getAttribute('data-team');
            const teamName = state.currentGame[currentRosterTeam === 'local' ? 'local' : 'visitante'];
            if (modalTeamName) modalTeamName.textContent = teamName;

            const rosterData = state.currentGame[currentRosterTeam === 'local' ? 'rosterLocal' : 'rosterVisitante'] || [];

            // Render starters (1-9)
            let startersHtml = '';
            for (let i = 0; i < 9; i++) {
                startersHtml += createRowHtml(rosterData[i] || {}, i, true);
            }
            if (startersTbody) startersTbody.innerHTML = startersHtml;

            // Render subs (9+)
            let subsHtml = '';
            const actualSubs = rosterData.slice(9);
            const subCount = Math.max(6, actualSubs.length + 1); // At least 6 rows or one more than current
            for (let i = 0; i < subCount; i++) {
                subsHtml += createRowHtml(actualSubs[i] || {}, i, false);
            }
            if (subsTbody) subsTbody.innerHTML = subsHtml;

            initRosterDragAndDrop();
        });
    });

    if (btnSaveRoster) {
        const clonedBtn = btnSaveRoster.cloneNode(true);
        btnSaveRoster.parentNode.replaceChild(clonedBtn, btnSaveRoster);

        clonedBtn.addEventListener('click', () => {
            if (!currentRosterTeam) return;

            const startersRows = document.querySelectorAll('#roster-starters-tbody tr');
            const subsRows = document.querySelectorAll('#roster-subs-tbody tr');
            const newRoster = [];

            const processRows = (rows, isStarter) => {
                rows.forEach((row, idx) => {
                    const pos = row.querySelector('.roster-input-pos').value.trim();
                    const num = row.querySelector('.roster-input-number').value.trim();
                    const name = row.querySelector('.roster-input-name').value.trim();

                    if (name || num || pos) {
                        newRoster.push({
                            order: isStarter ? (newRoster.length + 1) : ('S' + (newRoster.length - 8)),
                            pos: pos.toUpperCase(),
                            number: num,
                            name: name
                        });
                    }
                });
            };

            processRows(startersRows, true);
            processRows(subsRows, false);

            // Validation: Ensure at least 9 players TOTAL have a name filled in
            const playersWithNames = newRoster.filter(p => p.name && p.name.trim() !== "");
            
            if (playersWithNames.length < 9) {
                Swal.fire({
                    title: 'Roster Incompleto',
                    text: 'Debes ingresar al menos 9 nombres de atletas antes de guardar.',
                    icon: 'warning',
                    confirmButtonColor: '#0d6efd',
                    background: '#212529',
                    color: '#fff'
                });
                return;
            }

            if (currentRosterTeam === 'local') {
                state.currentGame.rosterLocal = newRoster;
            } else {
                state.currentGame.rosterVisitante = newRoster;
            }

            saveState();

            const rosterModalEl = document.getElementById('rosterModal');
            if (rosterModalEl) {
                const modal = bootstrap.Modal.getInstance(rosterModalEl) || new bootstrap.Modal(rosterModalEl);
                modal.hide();
            }

            Swal.fire({
                title: 'Roster Guardado',
                text: 'La alineación ha sido actualizada exitosamente.',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false,
                background: '#212529',
                color: '#fff'
            });
        });
    }
}
window.shareCurrentScoreboard = async function () {
    const btn = document.getElementById('share-btn');
    if (!btn) return;

    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>';
    btn.disabled = true;

    try {
        const actionBtns = document.getElementById('action-buttons-container');
        const deleteBtn = document.getElementById('delete-game-container');
        const backBtn = document.getElementById('back-btn');
        const shareBtn = document.getElementById('share-btn');

        // Extra requested elements
        const rosterBtns = document.querySelectorAll('.roster-btn');
        const inningStatusContainer = document.getElementById('inning-status-text');
        // Bottom Indicators is the row next to action buttons, it doesn't have an exact ID but it's the element immediately preceding it
        const bottomIndicators = document.querySelector('.row.g-2.flex-grow-1');

        // Hide elements we don't want in the screenshot
        if (actionBtns) actionBtns.classList.add('d-none');
        if (deleteBtn) deleteBtn.classList.add('d-none');
        if (backBtn) backBtn.classList.add('d-none');
        if (shareBtn) shareBtn.classList.add('d-none');

        if (inningStatusContainer) inningStatusContainer.classList.add('d-none');
        if (bottomIndicators) bottomIndicators.classList.add('d-none');
        rosterBtns.forEach(btn => btn.classList.add('d-none'));

        // Capture the app container
        const element = document.querySelector('.app-container');
        const currentTheme = document.documentElement.getAttribute('data-bs-theme');
        const bgColor = currentTheme === 'light' ? '#f0f2f5' : '#0a0e17';

        // Temporarily allow containers to shrink to wrap content
        element.style.setProperty('height', 'auto', 'important');

        const contentWrapper = element.querySelector('.content-wrapper');
        if (contentWrapper) {
            contentWrapper.style.setProperty('overflow', 'visible', 'important');
        }

        const viewContainer = element.querySelector('.view-container');
        if (viewContainer) {
            viewContainer.classList.remove('h-100');
        }

        const canvas = await html2canvas(element, {
            backgroundColor: bgColor,
            scale: 2,
            useCORS: true,
            windowWidth: element.scrollWidth,
            windowHeight: element.scrollHeight
        });

        // Restore layout bindings immediately
        element.style.removeProperty('height');
        if (contentWrapper) {
            contentWrapper.style.removeProperty('overflow');
        }
        if (viewContainer) {
            viewContainer.classList.add('h-100');
        }

        // Restore elements immediately
        if (actionBtns && !state.currentGame.isFinished) actionBtns.classList.remove('d-none');
        if (deleteBtn && state.settings.allowDelete) deleteBtn.classList.remove('d-none');
        if (backBtn) backBtn.classList.remove('d-none');
        if (shareBtn) shareBtn.classList.remove('d-none');

        if (inningStatusContainer) inningStatusContainer.classList.remove('d-none');
        if (bottomIndicators) bottomIndicators.classList.remove('d-none');
        rosterBtns.forEach(btn => btn.classList.remove('d-none'));

        canvas.toBlob(async (blob) => {
            if (!blob) throw new Error('Blob generation failed');

            const file = new File([blob], 'SCORE-GAMEDAY.png', { type: 'image/png' });

            if (navigator.canShare && navigator.canShare({ files: [file] })) {
                try {
                    await navigator.share({
                        files: [file],
                        title: 'Gameday Score',
                        text: '¡Mira el score de este partido!'
                    });
                } catch (e) {
                    console.log('Share canceled or failed', e);
                }
            } else {
                // Fallback download if Web Share is not supported
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'SCORE-GAMEDAY.png';
                a.click();
                URL.revokeObjectURL(url);
            }

            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }, 'image/png');

    } catch (err) {
        console.error('Error sharing scoreboard:', err);
        Swal.fire('Error', 'No se pudo generar la imagen para compartir.', 'error');
        btn.innerHTML = originalHtml;
        btn.disabled = false;

        // Make sure to restore elements if error occurred
        const actionBtns = document.getElementById('action-buttons-container');
        const deleteBtn = document.getElementById('delete-game-container');
        const backBtn = document.getElementById('back-btn');
        const shareBtn = document.getElementById('share-btn');
        const rosterBtns = document.querySelectorAll('.roster-btn');
        const inningStatusContainer = document.getElementById('inning-status-text');
        const bottomIndicators = document.querySelector('.row.g-2.flex-grow-1');

        if (actionBtns && !state.currentGame.isFinished) actionBtns.classList.remove('d-none');
        if (deleteBtn && state.settings.allowDelete) deleteBtn.classList.remove('d-none');
        if (backBtn) backBtn.classList.remove('d-none');
        if (shareBtn) shareBtn.classList.remove('d-none');

        if (inningStatusContainer) inningStatusContainer.classList.remove('d-none');
        if (bottomIndicators) bottomIndicators.classList.remove('d-none');
        rosterBtns.forEach(btn => btn.classList.remove('d-none'));
    }
};

// ----------------------------------------------------
// Game Rules & Limits Checks
// ----------------------------------------------------

function checkGameLimits(isEndOfInning = false) {
    const game = state.currentGame;
    if (!game || game.isFinished) return false;

    const rules = game.ruleSet;
    if (!rules) return false;

    let gameOver = false;
    let mercyWinner = null;
    let winnerName = '';
    let loserName = '';
    let winnerScore = 0;
    let loserScore = 0;

    // 1. Mercy Rule Check
    if (rules.mercyRule?.enabled) {
        if (isEndOfInning) {
            // El inning completo (alta y baja) ha concluido. Si un equipo tiene la ventaja reglamentaria, gana de inmediato.
            if (game.inning >= rules.mercyRule.atInning) {
                if (game.runsVisitante - game.runsLocal >= rules.mercyRule.diff) {
                    mercyWinner = game.visitante || 'Visitante';
                    gameOver = true;
                } else if (game.runsLocal - game.runsVisitante >= rules.mercyRule.diff) {
                    mercyWinner = game.local || 'Local';
                    gameOver = true;
                }
            }
        } else {
            // Juego corriendo. El Local gana de inmediato si tiene la ventaja requerida en la parte baja (Walk-off Mercy)
            if (game.half === 'Bottom' && game.inning >= rules.mercyRule.atInning) {
                if (game.runsLocal - game.runsVisitante >= rules.mercyRule.diff) {
                    mercyWinner = game.local || 'Local';
                    gameOver = true;
                }
            }
        }
    }

    if (mercyWinner && gameOver) {
        Swal.fire({
            title: '¡REGLA DEL NOCAUT!',
            html: `<h5>Victoria para <b>${mercyWinner}</b></h5><br>La diferencia límite de carreras ha sido ratificada de forma reglamentaria.`,
            icon: 'info',
            confirmButtonText: 'Finalizar Juego',
            confirmButtonColor: '#198754'
        }).then((r) => {
            endGame();
        });
        return true;
    }

    // 2. Max Innings Check y Fin de Partido Regular
    if (isEndOfInning) {
        // Acaba de terminar la parte baja del inning reglamentario
        if (game.inning >= rules.maxInnings) {
            if (game.runsLocal !== game.runsVisitante) {
                gameOver = true; // Alguien ganó
            } else {
                // Empate, notificar extrainnings solo una vez
                if (game.inning === rules.maxInnings) {
                    Swal.fire({
                        title: 'Innings Extras',
                        text: 'El partido está empatado tras los innings reglamentarios.',
                        icon: 'info',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 4000
                    });
                }
            }
        }
    } else {
        // Escenario B: Estamos corriendo la parte BAJA del último inning reglamentario (o en extrainnings)
        if (game.inning >= rules.maxInnings && game.half === 'Bottom') {
            // Si el equipo local tiene más carreras que el visitante, gana de forma inmediata (dejados en el terreno / walk-off)
            if (game.runsLocal > game.runsVisitante) {
                gameOver = true;
            }
        }
    }

    if (gameOver) {
        const isLocalWinner = game.runsLocal > game.runsVisitante;
        winnerName = isLocalWinner ? (game.local || 'Local') : (game.visitante || 'Visitante');
        loserName = isLocalWinner ? (game.visitante || 'Visitante') : (game.local || 'Local');
        winnerScore = isLocalWinner ? game.runsLocal : game.runsVisitante;
        loserScore = isLocalWinner ? game.runsVisitante : game.runsLocal;

        Swal.fire({
            title: '¡JUEGO TERMINADO!',
            html: `<h5>Victoria para <b>${winnerName}</b></h5><br>
                   Marcador Final:<br>
                   <span style="font-size: 1.5rem; color: #198754; font-weight: bold;">${winnerName}: ${winnerScore}</span><br>
                   <span style="font-size: 1.2rem; color: #dc3545;">${loserName}: ${loserScore}</span><br>`,
            icon: 'success',
            showCancelButton: true,
            confirmButtonText: 'Finalizar Juego',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                endGame();
            }
        });
        return true;
    }

    // 3. Aviso de Último Inning
    if (!isEndOfInning && game.inning === rules.maxInnings && game.half === 'Top' && game.outs === 0 && game.runsLocal === 0 && game.runsVisitante === 0 && game.strikes === 0 && game.balls === 0) {
        Swal.fire({
            title: '¡Último Inning!',
            text: `Iniciamos el Inning ${rules.maxInnings}, último reglamentario.`,
            icon: 'info',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
    }

    return false;
}

function endGame() {
    if (!state.currentGame) return;

    state.currentGame.isFinished = true;
    saveState();

    if (typeof initScoreboard === 'function') {
        initScoreboard();
    }

    Swal.fire({
        title: '¡Juego Finalizado!',
        icon: 'success',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2000
    });
}

// ----------------------------------------------------
// Pitch Count Tools
// ----------------------------------------------------

function updatePitchCountUI() {
    const game = state.currentGame;
    if (!game || !game.ruleSet) return;

    const container = document.getElementById('pitch-count-container');
    if (!container) return;

    if (game.ruleSet.pitchCountLimit > 0) {
        container.classList.remove('d-none');
        const pitchCurrentEl = document.getElementById('pitch-count-current');
        const pitchMaxEl = document.getElementById('pitch-count-max');
        const pitcherNameEl = document.getElementById('pitcher-name-display');

        // Defensive team throws the pitches
        const defenseTeam = game.half === 'Top' ? 'local' : 'visitante';
        
        // Use currentPitcher specific count
        if (!game.currentPitcher) {
            game.currentPitcher = { 
                local: { name: 'Lanzador', count: game.pitchCount?.local || 0 }, 
                visitante: { name: 'Lanzador', count: game.pitchCount?.visitante || 0 } 
            };
        }

        const pitcherInfo = game.currentPitcher[defenseTeam];
        const currentCount = pitcherInfo.count || 0;

        if (pitchCurrentEl) pitchCurrentEl.textContent = currentCount;
        if (pitchMaxEl) pitchMaxEl.textContent = game.ruleSet.pitchCountLimit;
        if (pitcherNameEl) pitcherNameEl.textContent = pitcherInfo.name || 'Lanzador';

        // Bind foul button securely
        const btnFoul = document.getElementById('btn-foul-pitch');
        if (btnFoul) {
            const newBtnFoul = btnFoul.cloneNode(true);
            btnFoul.parentNode.replaceChild(newBtnFoul, btnFoul);
            newBtnFoul.addEventListener('click', () => {
                incrementPitchCount();
            });
        }

        // Bind change pitcher button
        const btnChange = document.getElementById('btn-change-pitcher');
        if (btnChange) {
            const newBtnChange = btnChange.cloneNode(true);
            btnChange.parentNode.replaceChild(newBtnChange, btnChange);
            newBtnChange.addEventListener('click', () => {
                window.changePitcher(defenseTeam);
            });
        }
    } else {
        container.classList.add('d-none');
    }
}

function incrementPitchCount() {
    const game = state.currentGame;
    if (!game || !game.ruleSet || game.ruleSet.pitchCountLimit <= 0) return;

    if (!game.currentPitcher) {
        game.currentPitcher = { 
            local: { name: 'Lanzador', count: game.pitchCount?.local || 0 }, 
            visitante: { name: 'Lanzador', count: game.pitchCount?.visitante || 0 } 
        };
    }

    const defenseTeam = game.half === 'Top' ? 'local' : 'visitante';
    
    // Increment specific pitcher count
    game.currentPitcher[defenseTeam].count++;
    
    // Also keep global team count for stats if needed
    if (!game.pitchCount) game.pitchCount = { local: 0, visitante: 0 };
    game.pitchCount[defenseTeam]++;

    updatePitchCountUI();
    saveState();

    if (game.currentPitcher[defenseTeam].count >= game.ruleSet.pitchCountLimit) {
        Swal.fire({
            title: 'Límite de Pitcheos',
            html: `El lanzador <b>${game.currentPitcher[defenseTeam].name}</b> ha alcanzado el límite máximo permitido (${game.ruleSet.pitchCountLimit} pitcheos).`,
            icon: 'warning',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 5000
        });
    }
}

window.changePitcher = async function(team) {
    const game = state.currentGame;
    if (!game) return;

    const teamName = game[team];
    const currentName = game.currentPitcher[team].name;

    const { value: newName } = await Swal.fire({
        title: 'Cambio de Lanzador',
        text: `Equipo: ${teamName}`,
        input: 'text',
        inputLabel: 'Nombre del nuevo lanzador',
        inputValue: currentName === 'Lanzador' ? '' : currentName,
        showCancelButton: true,
        confirmButtonText: 'Realizar Cambio',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#0d6efd',
        inputValidator: (value) => {
            if (!value) {
                return '¡Debes ingresar un nombre!';
            }
        }
    });

    if (newName) {
        // Reset count for the new pitcher
        game.currentPitcher[team] = {
            name: newName,
            count: 0
        };

        Swal.fire({
            title: 'Lanzador Actualizado',
            text: `${newName} entra al relevo. Conteo reiniciado.`,
            icon: 'success',
            timer: 2000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });

        updatePitchCountUI();
        saveState();
    }
};
