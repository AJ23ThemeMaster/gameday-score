"""
E2E: verifica los colores de los botones Cancelar/Confirmar en los
modales del scoreboard-v2.

  Cancelar  -> rojo #f43f5e
  Confirmar -> verde #10b981

Estrategia: hacemos click en los botones reales del UI (no llamadas
JS directas, ya que los handlers viven dentro del scope Alpine).
"""
import sys
import subprocess
from playwright.sync_api import sync_playwright

BASE = "http://127.0.0.1:8000"
EMAIL = "frankjmarvala@gmail.com"
PASS = "AdminTest2026!"

CANCEL_HEX = "#f43f5e"
CONFIRM_HEX = "#10b981"


def login(page):
    subprocess.run(['php', 'reset-password.php'], capture_output=True, timeout=30)
    page.goto(f"{BASE}/login", wait_until="domcontentloaded")
    page.fill('input[name="email"]', EMAIL)
    page.fill('input[name="password"]', PASS)
    page.click('button[type="submit"]')
    page.wait_for_load_state("networkidle")


def rgb_to_hex(rgb_str):
    if not rgb_str or 'rgb' not in rgb_str:
        return rgb_str
    nums = [int(x.strip()) for x in rgb_str.replace('rgb(', '').replace(')', '').split(',')[:3]]
    return '#{:02x}{:02x}{:02x}'.format(*nums)


def check_modal_buttons(page, modal_label, screenshot_name):
    """Verifica los colores del modal abierto actualmente."""
    problems = []

    # Esperar a que aparezca un Cancelar visible
    page.wait_for_timeout(800)

    # Buscar el Cancelar visible
    cancel_bg = page.evaluate("""() => {
        const buttons = Array.from(document.querySelectorAll('button'));
        const visibles = buttons.filter(b => {
            const r = b.getBoundingClientRect();
            return r.width > 0 && r.height > 0 && b.offsetParent !== null;
        });
        const btn = visibles.find(b => b.textContent.trim() === 'Cancelar');
        if (!btn) return null;
        return getComputedStyle(btn).backgroundColor;
    }""")
    if cancel_bg is None:
        problems.append(f"[{modal_label}] no encontre boton Cancelar visible")
        page.screenshot(path=f"storage/screenshots/{screenshot_name}")
        return problems

    actual_cancel = rgb_to_hex(cancel_bg)
    if actual_cancel.lower() != CANCEL_HEX.lower():
        problems.append(f"[{modal_label}] Cancelar bg: esperaba {CANCEL_HEX}, obtuve {actual_cancel}")
    print(f"    Cancelar bg: {actual_cancel}")

    # Buscar el Confirm (sibling del Cancelar, dentro del mismo modal)
    confirm_data = page.evaluate("""() => {
        const buttons = Array.from(document.querySelectorAll('button'));
        const visibles = buttons.filter(b => {
            const r = b.getBoundingClientRect();
            return r.width > 0 && r.height > 0 && b.offsetParent !== null;
        });
        const cancel = visibles.find(b => b.textContent.trim() === 'Cancelar');
        if (!cancel) return null;
        // Buscar el padre .flex que contiene Cancelar y un boton adicional
        let parent = cancel.parentElement;
        // Subir hasta el contenedor flex con gap-2
        while (parent && !(parent.classList && parent.classList.contains('flex') && parent.classList.contains('gap-2'))) {
            parent = parent.parentElement;
            if (!parent) break;
        }
        if (!parent) return null;
        const siblings = Array.from(parent.querySelectorAll('button')).filter(b => {
            const t = b.textContent.trim();
            return t && t !== 'Cancelar' && t !== 'X' && t !== '×';
        });
        if (siblings.length === 0) return null;
        const btn = siblings[0];
        return { text: btn.textContent.trim().substring(0, 30), bg: getComputedStyle(btn).backgroundColor };
    }""")

    if confirm_data:
        actual_confirm = rgb_to_hex(confirm_data['bg'])
        if actual_confirm.lower() != CONFIRM_HEX.lower():
            problems.append(f"[{modal_label}] Confirmar ('{confirm_data['text']}') bg: esperaba {CONFIRM_HEX}, obtuve {actual_confirm}")
        print(f"    Confirmar ('{confirm_data['text']}') bg: {actual_confirm}")
    else:
        print(f"    (modal sin boton Confirm)")

    page.screenshot(path=f"storage/screenshots/{screenshot_name}")
    return problems


def run():
    with sync_playwright() as pw:
        browser = pw.chromium.launch(headless=True)
        ctx = browser.new_context(viewport={"width": 1280, "height": 1000})
        page = ctx.new_page()
        page.set_default_timeout(15000)
        all_problems = []

        print("[1/4] Login + abrir scoreboard...")
        login(page)
        if "/login" in page.url:
            print("FAIL login"); return 1
        page.goto(f"{BASE}/games/1/scoreboard", wait_until="networkidle")
        page.wait_for_timeout(1500)

        print("\n[2/4] Hit modal (tab Bateo -> Sencillo)...")
        page.click('button:has-text("Bateo")')
        page.wait_for_timeout(300)
        page.click('button:has-text("Sencillo")')
        all_problems += check_modal_buttons(page, "hit", "modal-hit.png")
        page.evaluate("() => document.body.click()")  # backdrop close
        page.wait_for_timeout(300)

        print("\n[3/4] End inning modal (tab Extras -> Cerrar inning)...")
        page.click('button:has-text("Extras")')
        page.wait_for_timeout(300)
        page.click('button:has-text("Cerrar inning")')
        all_problems += check_modal_buttons(page, "end-inning", "modal-end-inning.png")
        page.evaluate("() => document.body.click()")
        page.wait_for_timeout(300)

        print("\n[4/4] End game modal (tab Extras -> Finalizar juego)...")
        page.click('button:has-text("Finalizar juego")')
        all_problems += check_modal_buttons(page, "end-game", "modal-end-game.png")
        page.evaluate("() => document.body.click()")
        page.wait_for_timeout(300)

        print("\n[5/5] Substitute modal (tab Extras -> Sustitucion)...")
        page.click('button:has-text("Sustituci")')
        all_problems += check_modal_buttons(page, "substitute", "modal-substitute.png")
        page.evaluate("() => document.body.click()")
        page.wait_for_timeout(300)

        print("\n[+] Runner modal (click en base 1B si hay corredor)...")
        page.evaluate("""() => {
            // Llamar directo al Alpine para abrir el modal runner
            const el = document.querySelector('[x-data*=\"scoreboardV2\"]') || document.querySelector('[x-data]');
            if (el && el._x_dataStack) {
                el._x_dataStack[0].modal = 'runner';
                el._x_dataStack[0].runnerBase = 'first';
            }
        }""")
        page.wait_for_timeout(500)
        all_problems += check_modal_buttons(page, "runner", "modal-runner.png")

        browser.close()
        if all_problems:
            print("\nPROBLEMAS:")
            for p in all_problems:
                print(f"  - {p}")
            return 1
        print("\nOK — todos los botones Cancelar/Confirmar usan los colores correctos")
        return 0


if __name__ == "__main__":
    sys.exit(run())
