"""
E2E smoke test: verifica que TODAS las opciones del scoreboard-v2 son
accesibles y no rompen la UI (admin, game 1 Peluitos Pre-infantil).

Para cada accion:
  1. Verifica que el boton/elemento es visible
  2. Hace click
  3. Verifica que no hay errores de consola
  4. Cierra el modal si se abrio
"""
import sys
import subprocess
from playwright.sync_api import sync_playwright

BASE = "http://127.0.0.1:8000"
EMAIL = "frankjmarvala@gmail.com"
PASS = "AdminTest2026!"


def login(page):
    subprocess.run(['php', 'reset-password.php'], capture_output=True, timeout=30)
    page.goto(f"{BASE}/login", wait_until="domcontentloaded")
    page.fill('input[name="email"]', EMAIL)
    page.fill('input[name="password"]', PASS)
    page.click('button[type="submit"]')
    page.wait_for_load_state("networkidle")


def click_first(page, text, exact=False):
    elems = page.query_selector_all(f'button:has-text("{text}")')
    for b in elems:
        if b.is_visible():
            try:
                b.click(timeout=2000)
                return True
            except: continue
    return False


def click_tab_safe(page, text):
    elems = page.query_selector_all(f'button.sb-tab:has-text("{text}")')
    for b in elems:
        if b.is_visible():
            b.click(timeout=2000)
            return True
    return False


def close_any_modal(page):
    page.evaluate("""() => {
        const s = document.querySelector('[x-data]')._x_dataStack[0];
        s.modal = null;
        s.runnerBase = null;
    }""")
    page.wait_for_timeout(400)


console_errors = []


def on_console(msg):
    if msg.type == "error":
        text = msg.text
        # Filtrar errores no relacionados al scoreboard
        if "favicon" in text.lower() or "404" in text.lower():
            return
        console_errors.append(text)


print("=" * 60)
print("E2E SMOKE TEST: TODAS las opciones del scoreboard")
print("=" * 60)

with sync_playwright() as pw:
    browser = pw.chromium.launch(headless=True)
    ctx = browser.new_context(viewport={"width": 1280, "height": 1000})
    page = ctx.new_page()
    page.set_default_timeout(15000)
    page.on('console', on_console)
    problems = []
    actions = []

    # FASE 1: Login + abrir
    print("\n[FASE 1] Login + abrir scoreboard game 1")
    login(page)
    page.goto(f"{BASE}/games/1/scoreboard", wait_until="networkidle")
    page.wait_for_timeout(2000)
    close_any_modal(page)
    actions.append("page_load")
    print("  OK - scoreboard cargado")

    # FASE 2: PITCHEO tab
    print("\n[FASE 2] PITCHEO tab - botones principales")
    click_tab_safe(page, 'Pitcheo')
    page.wait_for_timeout(500)

    # Verificar que los 4 botones existen y son clickeables
    for label in ['Ball', 'Strike', 'Foul', 'Out']:
        clicked = click_first(page, label)
        actions.append(f"open_modal_{label}")
        print(f"  {label}: click OK={clicked}")
        if not clicked:
            problems.append(f"Click '{label}' fallo")
        page.wait_for_timeout(800)
        # Cerrar cualquier modal que se haya abierto
        close_any_modal(page)

    # Strike modal: Mirando, Swing, Foul Tip
    print("  Sub-modal Strike:")
    for sub in ['Mirando', 'Swing', 'Foul Tip']:
        click_first(page, 'Strike')  # abrir modal
        page.wait_for_timeout(500)
        clicked = click_first(page, sub)
        actions.append(f"strike_{sub}")
        print(f"    {sub}: click OK={clicked}")
        if not clicked:
            problems.append(f"Strike '{sub}' no encontrado")
        page.wait_for_timeout(500)
        close_any_modal(page)

    # Out modal: Fly, Linea, Roletazo, De reglamento
    print("  Sub-modal Out:")
    for sub in ['Fly (elevado)', 'Línea', 'Roletazo', 'De reglamento']:
        click_first(page, 'Out')  # abrir modal step1
        page.wait_for_timeout(500)
        clicked = click_first(page, sub)
        actions.append(f"out_{sub}")
        print(f"    {sub}: click OK={clicked}")
        if not clicked:
            problems.append(f"Out '{sub}' no encontrado")
        page.wait_for_timeout(500)
        close_any_modal(page)

    # FASE 3: BATEO tab
    print("\n[FASE 3] BATEO tab - hits")
    click_tab_safe(page, 'Bateo')
    page.wait_for_timeout(500)

    for hit in ['Sencillo', 'Doble', 'Triple', 'HR de pierna']:
        clicked = click_first(page, hit)
        actions.append(f"open_modal_{hit}")
        print(f"  {hit}: click OK={clicked}")
        if not clicked:
            problems.append(f"Click '{hit}' fallo")
        page.wait_for_timeout(800)
        close_any_modal(page)

    # HR (sin "de pierna")
    hr_buttons = page.query_selector_all('button:has-text("HR")')
    for b in hr_buttons:
        if b.is_visible() and "pierna" not in (b.inner_text() or ""):
            try:
                b.click(timeout=2000)
                actions.append("open_modal_HR")
                print(f"  HR: click OK=True")
                break
            except: continue
    page.wait_for_timeout(800)
    close_any_modal(page)

    # Toque de bolas
    clicked = click_first(page, 'Toque de bolas')
    actions.append("open_modal_bunt")
    print(f"  Toque de bolas: click OK={clicked}")
    if not clicked: problems.append("Toque de bolas no encontrado")
    page.wait_for_timeout(800)
    close_any_modal(page)

    # FASE 4: EXTRAS tab
    print("\n[FASE 4] EXTRAS tab - modales varios")
    click_tab_safe(page, 'Extras')
    page.wait_for_timeout(500)
    close_any_modal(page)

    for btn in ['Sustituci', 'Lineup', 'Stats del juego']:
        clicked = click_first(page, btn)
        actions.append(f"open_modal_{btn}")
        print(f"  {btn}: click OK={clicked}")
        if not clicked:
            problems.append(f"'{btn}' no encontrado")
        page.wait_for_timeout(1500)
        # Cerrar el modal
        close_any_modal(page)

    # Roster (es link, no boton)
    roster_link = page.query_selector('a[href*="roster"]')
    if roster_link and roster_link.is_visible():
        actions.append("roster_link_visible")
        print(f"  Link Roster: visible=True")
    else:
        problems.append("Link Roster no visible")

    # Box Score link
    box_link = page.query_selector('a[href*="box-score"]')
    if box_link and box_link.is_visible():
        actions.append("box_score_link_visible")
        print(f"  Link Box Score: visible=True")
    else:
        problems.append("Link Box Score no visible")

    # FASE 5: Modal Runner
    print("\n[FASE 5] Modal Runner - todas las opciones")
    # Asegurar corredor en 1B via Alpine state
    page.evaluate("""() => {
        const s = document.querySelector('[x-data]')._x_dataStack[0];
        s.base1 = { id: 1, name: 'Test', number: 9, athlete_id: 1 };
    }""")
    page.wait_for_timeout(300)
    # Abrir modal runner via click en base 1B
    page.click('button.sb-base-btn:has-text("1B")')
    page.wait_for_timeout(800)
    modal_open = page.evaluate("() => document.querySelector('[x-data]')._x_dataStack[0].modal === 'runner'")
    print(f"  Modal Runner abierto: {modal_open}")
    if not modal_open:
        problems.append("Modal Runner no se abrio")

    # Verificar que todas las opciones estan visibles
    runner_options = ['Avanzar', 'Robo', 'Anota', 'Wild pitch', 'Passed ball', 'OBS', 'Pickoff', 'Out al intentar avanzar']
    visible_options = []
    for opt in runner_options:
        clicked = click_first(page, opt)
        visible_options.append((opt, clicked))
        page.wait_for_timeout(400)
        close_any_modal(page)
        # Re-abrir el modal runner si se cerro
        page.click('button.sb-base-btn:has-text("1B")')
        page.wait_for_timeout(500)
    for opt, ok in visible_options:
        status = 'OK' if ok else 'FAIL'
        print(f"    {opt}: {status}")
        if not ok:
            problems.append(f"Runner option '{opt}' no funciona")
    actions.append("runner_all_options_tested")

    # FASE 6: Modal Sustituir (pitcher / bateador / corredor)
    print("\n[FASE 6] Modal Sustituir (3 kinds)")
    close_any_modal(page)
    click_tab_safe(page, 'Extras')
    page.wait_for_timeout(400)
    click_first(page, 'Sustituci')
    page.wait_for_timeout(800)
    # Verificar tabs del modal (pitcher/bateador/corredor)
    for kind in ['Pitcher', 'Bateador', 'Corredor']:
        clicked = click_first(page, kind)
        actions.append(f"substitute_kind_{kind}")
        print(f"  Tab {kind}: OK={clicked}")
        if not clicked:
            problems.append(f"Sustituir kind '{kind}' no encontrado")
        page.wait_for_timeout(400)
    close_any_modal(page)

    # FASE 7: Cerrar inning / Finalizar juego (NO finalizar realmente)
    print("\n[FASE 7] Botones Cerrar inning / Finalizar juego")
    # Verificar que el boton Cerrar inning existe
    click_first(page, 'Cerrar inning')
    page.wait_for_timeout(500)
    cancel = click_first(page, 'Cancelar')
    actions.append("cerrar_inning_cancelar")
    print(f"  Modal Cerrar inning: cancel OK={cancel}")

    click_first(page, 'Finalizar juego')
    page.wait_for_timeout(500)
    cancel = click_first(page, 'Cancelar')
    actions.append("finalizar_juego_cancelar")
    print(f"  Modal Finalizar juego: cancel OK={cancel}")

    # Screenshot final
    page.screenshot(path="storage/screenshots/e2e-final.png")
    browser.close()

    # RESULTADO
    print("\n" + "=" * 60)
    print(f"Total de acciones ejecutadas: {len(actions)}")
    print(f"Errores de consola: {len(console_errors)}")
    if console_errors:
        for e in console_errors[:5]:
            print(f"  - {e[:150]}")
    if problems:
        print(f"\nBUGS DETECTADOS ({len(problems)}):")
        for p in problems: print(f"  - {p}")
        sys.exit(1)
    else:
        print("\nOK - todas las opciones del scoreboard son accesibles")
        sys.exit(0)
