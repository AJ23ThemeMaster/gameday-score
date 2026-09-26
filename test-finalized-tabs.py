"""E2E: verifica que al finalizar el juego, solo se muestra la pestana Extras
con los botones 'Stats del juego' y 'Box Score'.

  - Pitcheo y Bateo tabs ocultos
  - Solo Extras visible
  - En Extras solo aparecen Stats del juego + Box Score
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


def set_game_status(status):
    subprocess.run(
        ['php', '-r', f'require "vendor/autoload.php"; $app=require "bootstrap/app.php"; $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); $g=App\\Models\\Game::find(1); $g->status="{status}"; $g->save(); echo "status={status}\\n";'],
        capture_output=True, timeout=30, text=True
    )


with sync_playwright() as pw:
    browser = pw.chromium.launch(headless=True)
    ctx = browser.new_context(viewport={"width": 1280, "height": 1000})
    page = ctx.new_page()
    page.set_default_timeout(15000)

    # Caso 1: juego en curso -> deberian verse Pitcheo, Bateo, Extras con todo
    print("=" * 60)
    print("CASO 1: juego in_progress (en juego)")
    print("=" * 60)
    set_game_status("in_progress")
    login(page)
    page.goto(f"{BASE}/games/1/scoreboard", wait_until="networkidle")
    page.wait_for_timeout(1500)

    state = page.evaluate("""() => {
        const s = document.querySelector('[x-data]')._x_dataStack[0];
        return { tab: s.tab, isFinalized: s.isFinalized, gameStatus: s.gameStatus };
    }""")
    print(f"  Alpine state: {state}")

    tabs_visible = page.evaluate("""() => {
        const buttons = Array.from(document.querySelectorAll('.flex.border-b button'));
        return buttons.filter(b => b.offsetParent !== null).map(b => b.textContent.trim());
    }""")
    print(f"  Tabs visibles: {tabs_visible}")

    page.screenshot(path="storage/screenshots/tabs-in-progress.png")
    browser.close()

    # Caso 2: juego finalizado
    print()
    print("=" * 60)
    print("CASO 2: juego completed (finalizado)")
    print("=" * 60)
    set_game_status("completed")
    browser = pw.chromium.launch(headless=True)
    ctx = browser.new_context(viewport={"width": 1280, "height": 1000})
    page = ctx.new_page()
    page.set_default_timeout(15000)
    login(page)
    page.goto(f"{BASE}/games/1/scoreboard", wait_until="networkidle")
    page.wait_for_timeout(1500)

    state = page.evaluate("""() => {
        const s = document.querySelector('[x-data]')._x_dataStack[0];
        return { tab: s.tab, isFinalized: s.isFinalized, gameStatus: s.gameStatus };
    }""")
    print(f"  Alpine state: {state}")

    tabs_visible = page.evaluate("""() => {
        const buttons = Array.from(document.querySelectorAll('.flex.border-b button'));
        return buttons.filter(b => b.offsetParent !== null).map(b => b.textContent.trim());
    }""")
    print(f"  Tabs visibles: {tabs_visible}")

    # Verificar que solo Stats + Box Score se ven en el contenido de Extras
    extras_buttons = page.evaluate("""() => {
        // Buscar el contenedor de Extras (el que tiene tab=='extra')
        const tabContent = document.querySelector('[x-show*="tab===' + "'extra'" + '"]');
        if (!tabContent) return null;
        const buttons = Array.from(tabContent.querySelectorAll('button, a'));
        return buttons.filter(b => b.offsetParent !== null).map(b => b.textContent.trim().substring(0, 40));
    }""")
    print(f"  Botones visibles en Extras: {extras_buttons}")

    page.screenshot(path="storage/screenshots/tabs-finalized.png")
    browser.close()

    # Restaurar
    set_game_status("in_progress")

    # Aserciones
    print()
    print("=" * 60)
    print("RESULTADO")
    print("=" * 60)

    # Caso 1: in_progress debe tener los 3 tabs
    if len(tabs_visible) == 3:
        print(f"[OK] in_progress: 3 tabs visibles ({tabs_visible})")
    else:
        print(f"[FAIL] in_progress: esperaba 3 tabs, vi {len(tabs_visible)} ({tabs_visible})")
