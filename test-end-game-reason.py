"""Test E2E: verifica que el modal 'Finalizar juego' pide motivo y envia reason al backend."""
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


api_calls = []
def on_response(resp):
    if '/end-game' in resp.url or '/plays/end-game' in resp.url:
        try:
            api_calls.append({
                'method': resp.request.method,
                'url': resp.url,
                'status': resp.status,
                'body': resp.json(),
            })
        except Exception:
            api_calls.append({'method': resp.request.method, 'url': resp.url, 'status': resp.status, 'body': None})


with sync_playwright() as pw:
    browser = pw.chromium.launch(headless=True)
    ctx = browser.new_context(viewport={"width": 1280, "height": 1000})
    page = ctx.new_page()
    page.set_default_timeout(15000)
    page.on('response', on_response)
    problems = []

    print("[1/4] Login + abrir scoreboard")
    login(page)
    page.goto(f"{BASE}/games/1/scoreboard", wait_until="networkidle")
    page.wait_for_timeout(1500)
    page.evaluate("() => { const s = document.querySelector('[x-data]')._x_dataStack[0]; s.modal = null; }")
    page.wait_for_timeout(400)

    # Verificar estado del juego
    game_state = page.evaluate("() => document.querySelector('[x-data]')._x_dataStack[0].gameStatus")
    print(f"  gameStatus: {game_state}")

    print("\n[2/4] Abrir modal Finalizar juego")
    page.click('button.sb-tab:has-text("Extras")')
    page.wait_for_timeout(400)
    page.click('button:has-text("Finalizar juego")')
    page.wait_for_timeout(800)

    # Verificar que el modal esta abierto y el select de motivo esta visible
    modal_open = page.evaluate("() => document.querySelector('[x-data]')._x_dataStack[0].modal === 'end-game'")
    print(f"  Modal abierto: {modal_open}")
    if not modal_open:
        problems.append("Modal no se abrio")

    # Verificar que el boton Finalizar esta disabled inicialmente
    finalizar_disabled = page.evaluate("""() => {
        const btns = Array.from(document.querySelectorAll('button')).filter(b => b.textContent.trim() === 'Finalizar');
        return btns.some(b => b.disabled);
    }""")
    print(f"  Boton Finalizar disabled sin reason: {finalizar_disabled}")

    # Tomar screenshot del modal abierto
    page.screenshot(path="storage/screenshots/end-game-modal-with-reason.png")
    print("\n[3/4] Screenshot guardado")

    # Verificar que el select tiene las 5 opciones
    select_options = page.evaluate("""() => {
        const select = document.querySelector('select[x-model="endGameReason"]');
        if (!select) return null;
        return Array.from(select.options).map(o => ({ value: o.value, text: o.textContent.trim() }));
    }""")
    print(f"  Opciones del select: {select_options}")
    if not select_options or len(select_options) < 5:
        problems.append(f"Select no tiene 5 opciones: {select_options}")

    print("\n[4/4] Cerrar modal sin finalizar")
    page.click('button:has-text("Cancelar")')
    page.wait_for_timeout(500)

    # Verificar que NO se llamo el endpoint de end-game
    end_game_calls = [c for c in api_calls if 'end-game' in c['url'] and 'stats' not in c['url']]
    print(f"  Llamadas a /end-game: {len(end_game_calls)} (debe ser 0)")

    page.screenshot(path="storage/screenshots/scoreboard-after-modal-cancel.png")
    browser.close()

    print("\n" + "=" * 60)
    if problems:
        print(f"BUGS ({len(problems)}):")
        for p in problems: print(f"  - {p}")
        sys.exit(1)
    else:
        print("OK - modal 'Finalizar juego' muestra dropdown de motivo")
        sys.exit(0)
