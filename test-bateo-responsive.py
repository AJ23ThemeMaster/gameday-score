"""E2E: verifica que en viewport movil, el tab BATEO muestra 4 botones en fila 1."""
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


with sync_playwright() as pw:
    browser = pw.chromium.launch(headless=True)
    # iPhone X viewport: 375x812 (movil pequeno)
    ctx = browser.new_context(viewport={"width": 375, "height": 812}, device_scale_factor=2)
    page = ctx.new_page()
    page.set_default_timeout(15000)

    print("[1/4] Login + abrir scoreboard en viewport movil (375x812)")
    login(page)
    page.goto(f"{BASE}/games/1/scoreboard", wait_until="networkidle")
    page.wait_for_timeout(1500)
    page.evaluate("() => { const s = document.querySelector('[x-data]')._x_dataStack[0]; s.modal = null; }")
    page.wait_for_timeout(400)

    print("[2/4] Click en tab Bateo")
    page.click('button.sb-tab:has-text("Bateo")')
    page.wait_for_timeout(500)

    print("[3/4] Verificar posiciones Y de los botones principales")
    # Obtener bounding boxes de los 4 botones principales (Sencillo, Doble, Triple, HR)
    positions = page.evaluate("""() => {
        const labels = ['Sencillo', 'Doble', 'Triple', 'HR'];
        return labels.map(label => {
            const btns = Array.from(document.querySelectorAll('button')).filter(b => b.textContent.includes(label));
            const visible = btns.find(b => b.offsetParent !== null);
            if (!visible) return { label, found: false };
            const r = visible.getBoundingClientRect();
            return { label, x: r.x, y: r.y, width: r.width };
        });
    }""")
    print(f"  Posiciones: {positions}")
    problems = []
    for p in positions:
        if not p.get('found'):
            problems.append(f"  - Boton '{p.get('label')}' no encontrado")

    # Verificar que las 4 Y son similares (estan en la misma fila)
    if all(p.get('found') for p in positions):
        ys = [p['y'] for p in positions]
        y_range = max(ys) - min(ys)
        print(f"  Rango Y de los 4 botones principales: {y_range:.1f}px")
        if y_range > 5:  # permitir pequena variacion
            problems.append(f"  - Los 4 botones principales NO estan en la misma fila (rango Y: {y_range:.1f}px)")

    print("[4/4] Verificar segunda fila: HR de pierna + Toque de bolas")
    row2 = page.evaluate("""() => {
        const labels = ['HR de pierna', 'Toque de bolas'];
        return labels.map(label => {
            const btns = Array.from(document.querySelectorAll('button')).filter(b => b.textContent.includes(label));
            const visible = btns.find(b => b.offsetParent !== null);
            if (!visible) return { label, found: false };
            const r = visible.getBoundingClientRect();
            return { label, x: r.x, y: r.y };
        });
    }""")
    print(f"  Segunda fila: {row2}")

    # Tomar screenshot del tab BATEO en movil
    page.screenshot(path="storage/screenshots/bateo-tab-mobile.png")
    print("\nScreenshot guardado en storage/screenshots/bateo-tab-mobile.png")

    browser.close()

    print("\n" + "=" * 60)
    if problems:
        print(f"BUGS ({len(problems)}):")
        for p in problems: print(p)
        sys.exit(1)
    else:
        print("OK - tab BATEO muestra 4 botones por fila en viewport movil")
        sys.exit(0)
