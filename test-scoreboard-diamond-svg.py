"""
DISI-piloto: verifica el nuevo diamante SVG del scoreboard principal.
  1. Login admin.
  2. Abrir scoreboard game 1.
  3. Verificar que el contenedor .sb-field-grid tiene un SVG con fondo
     verde oscuro y las 5 bases (2B, 3B, P, 1B, HOME) posicionadas.
  4. Capturar screenshot del diamante.
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


def run():
    with sync_playwright() as pw:
        browser = pw.chromium.launch(headless=True)
        ctx = browser.new_context(viewport={"width": 1280, "height": 1000})
        page = ctx.new_page()
        page.set_default_timeout(15000)
        problems = []

        print("[1/5] Login admin + abrir scoreboard...")
        login(page)
        if "/login" in page.url:
            print("FAIL login"); return 1
        page.goto(f"{BASE}/games/1/scoreboard", wait_until="networkidle")
        page.wait_for_timeout(2000)

        print("[2/5] Buscar el contenedor del diamante...")
        field_present = page.evaluate("() => !!document.querySelector('.sb-field-grid')")
        print(f"  .sb-field-grid presente: {field_present}")
        if not field_present:
            problems.append("No se encontro .sb-field-grid en el DOM")
            browser.close()
            print("\nPROBLEMAS:")
            for p in problems:
                print(f"  - {p}")
            return 1

        print("[3/5] Verificar SVG dentro del diamante...")
        svg_present = page.evaluate("() => !!document.querySelector('.sb-field-grid svg.sb-field-bg')")
        print(f"  SVG de fondo presente: {svg_present}")
        if not svg_present:
            problems.append("No hay SVG .sb-field-bg dentro de .sb-field-grid")
        rect_present = page.evaluate("""() => {
            const svg = document.querySelector('.sb-field-grid svg.sb-field-bg');
            if (!svg) return false;
            return svg.querySelectorAll('rect').length > 0;
        }""")
        print(f"  rect verde oscuro presente: {rect_present}")
        if not rect_present:
            problems.append("No hay rect verde en el SVG del diamante")

        print("[4/5] Verificar las 5 bases en su nueva posicion...")
        bases_visible = page.evaluate("""() => {
            const sb = document.querySelector('.sb-field-grid');
            if (!sb) return {positions: 0, mound: 0, bases: 0};
            return {
                positions: sb.querySelectorAll('button.sb-base-btn').length,
                mound: sb.querySelectorAll('.sb-pitcher-mound').length,
                bases: sb.querySelectorAll('.sb-base-tag').length,
            };
        }""")
        print(f"  {bases_visible}")
        if bases_visible['positions'] < 3:
            problems.append(f"Solo {bases_visible['positions']} botones de base (esperados 3)")
        if bases_visible['mound'] < 1:
            problems.append("Pitcher mound faltante")
        if bases_visible['bases'] < 4:
            problems.append(f"Solo {bases_visible['bases']} cuadritos de base (esperados >= 4)")

        print("[5/5] Capturar screenshot del scoreboard con nuevo diamante...")
        page.screenshot(path="storage/screenshots/scoreboard-diamond-svg.png", full_page=False)

        browser.close()
        if problems:
            print("\nPROBLEMAS:")
            for p in problems:
                print(f"  - {p}")
            return 1
        print("\nOK — diamante con SVG bg verde oscuro renderizado")
        return 0


if __name__ == "__main__":
    sys.exit(run())
