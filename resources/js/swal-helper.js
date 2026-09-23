/**
 * SwalHelper: wrapper de SweetAlert2 con tema dark WattVision.
 *
 * Provee API consistente para alertas, confirmaciones, toasts y loaders
 * en todo el sistema. Cualquier `alert()` o `confirm()` JS queda override
 * por estas versiones (no rompe codigo legacy, lo mejora visualmente).
 *
 * Modos:
 *   - SwalHelper.alert(title, text, icon)
 *   - SwalHelper.confirm({ title, text, confirmText, cancelText, icon, danger }) -> Promise<bool>
 *   - SwalHelper.toast(message, level)            // success|error|warning|info
 *   - SwalHelper.loading(title?)                   // swal2 loading persistente
 *   - SwalHelper.close()                           // cierra el loading
 *   - SwalHelper.apiFetch(url, opts)               // wrapper fetch con loader
 *
 * Form handlers globales (registrados en init):
 *   - <form data-confirm="texto">  -> swal2 confirm antes de submit
 *   - <form data-loader>           -> cambia boton submit a "Guardando..."
 *   - <form data-loader="full">    -> ademas muestra overlay loading
 *
 * Override globales (en init):
 *   - window.alert(message)        -> SwalHelper.alert(...)
 *   - window.confirm(message)      -> SwalHelper.confirm(...) async (ojo: retorna Promise<bool>, NO bool sync)
 *
 * Importacion CSS:
 *   El CSS de sweetalert2 se importa en resources/css/app.css via
 *   @import 'sweetalert2/dist/sweetalert2.min.css'.
 */

import Swal from 'sweetalert2';

// ---------------------------------------------------------------------------
// Tema dark WattVision: customClass reutilizable.
// bg-wv-bg = #121212 (dark), wv-surface = #1c1c1c, wv-accent = verde-azulado,
// wv-text = blanco, wv-border = gris oscuro.
// ---------------------------------------------------------------------------
const WV_CLASSES = {
    popup: 'wv-swal-popup',
    title: 'wv-swal-title',
    htmlContainer: 'wv-swal-html',
    confirmButton: 'wv-swal-confirm',
    cancelButton: 'wv-swal-cancel',
    denyButton: 'wv-swal-deny',
    icon: 'wv-swal-icon',
    actions: 'wv-swal-actions',
    input: 'wv-swal-input',
    validationMessage: 'wv-swal-validation',
};

const WV_TOAST_CLASSES = {
    popup: 'wv-swal-toast',
    title: 'wv-swal-title',
    icon: 'wv-swal-icon',
};

function levelToIcon(level) {
    switch ((level || 'info').toLowerCase()) {
        case 'success': return 'success';
        case 'error':   return 'error';
        case 'warning': return 'warning';
        default:        return 'info';
    }
}

let activeLoading = null;

class SwalHelperClass {
    /**
     * Reemplazo de alert(). Muestra un modal con un solo boton "OK".
     */
    alert(title, text = '', icon = 'info') {
        return Swal.fire({
            title,
            text,
            icon,
            customClass: WV_CLASSES,
            buttonsStyling: false,
            confirmButtonText: 'Aceptar',
        });
    }

    /**
     * Reemplazo de confirm(). Resuelve Promise<boolean>.
     * Si danger=true, el boton confirm se pinta en rojo (wv-alert).
     */
    confirm({ title, text = '', confirmText = 'Sí', cancelText = 'Cancelar', icon = 'warning', danger = false } = {}) {
        return Swal.fire({
            title,
            text,
            icon,
            showCancelButton: true,
            confirmButtonText: confirmText,
            cancelButtonText: cancelText,
            customClass: {
                ...WV_CLASSES,
                confirmButton: danger ? 'wv-swal-confirm wv-swal-confirm--danger' : WV_CLASSES.confirmButton,
            },
            buttonsStyling: false,
            reverseButtons: true,
            focusCancel: true,
        }).then((result) => result.isConfirmed);
    }

    /**
     * Toast (notificacion esquina superior derecha). Auto-dismiss en 3.5s.
     * Mantiene la firma (message, level) del sistema Alpine previo.
     */
    toast(message, level = 'success') {
        const icon = levelToIcon(level);
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true,
            icon,
            title: message,
            customClass: WV_TOAST_CLASSES,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            },
        });
        return Toast.fire({ title: message, icon });
    }

    success(message) { return this.toast(message, 'success'); }
    error(message)   { return this.toast(message, 'error'); }
    warning(message) { return this.toast(message, 'warning'); }
    info(message)    { return this.toast(message, 'info'); }

    /**
     * Muestra un modal loading persistente. Solo uno a la vez (re-llamar
     * reemplaza el titulo del actual). Cerrar con close().
     */
    loading(title = 'Procesando...') {
        if (activeLoading) {
            // Reusar la instancia abierta solo cambia el titulo.
            Swal.update({ title });
            return activeLoading;
        }
        activeLoading = Swal.fire({
            title,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => Swal.showLoading(),
            customClass: WV_CLASSES,
            buttonsStyling: false,
        });
        return activeLoading;
    }

    close() {
        if (activeLoading) {
            Swal.close();
            activeLoading = null;
        }
    }

    /**
     * Wrapper fetch con loader swal2 opcional + manejo de errores.
     *
     * - opts.loading (bool, default true): muestra overlay loading.
     * - Si la respuesta HTTP no es ok, dispara toast de error y lanza Error.
     * - opts.parseJson (bool, default true): parsea respuesta como JSON.
     * - Pasa opts.successMessage (string|null) para toast verde al final.
     *
     * Uso:
     *   const data = await SwalHelper.apiFetch('/users/1/edit');
     *   const data = await SwalHelper.apiFetch('/users', { method: 'POST', body: fd });
     */
    async apiFetch(url, opts = {}) {
        const {
            loading: showLoading = true,
            parseJson = true,
            successMessage = null,
            silent = false,
            ...fetchOpts
        } = opts;

        const headers = {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            ...(fetchOpts.headers || {}),
        };
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        if (csrf && ['POST', 'PUT', 'PATCH', 'DELETE'].includes((fetchOpts.method || 'GET').toUpperCase())) {
            headers['X-CSRF-TOKEN'] = csrf;
        }

        if (showLoading) this.loading();
        try {
            const res = await fetch(url, {
                credentials: 'same-origin',
                ...fetchOpts,
                headers,
            });
            let data = null;
            if (parseJson) {
                data = await res.json().catch(() => ({}));
            }
            if (!res.ok) {
                const msg = (data && data.message) || `Error HTTP ${res.status}`;
                if (!silent) this.error(msg);
                const err = new Error(msg);
                err.status = res.status;
                err.data = data;
                throw err;
            }
            if (successMessage) this.success(successMessage);
            return data;
        } catch (e) {
            if (!silent && e.status === undefined) {
                this.error('Error de red: ' + (e.message || 'desconocido'));
            }
            throw e;
        } finally {
            if (showLoading) this.close();
        }
    }

    /**
     * Adjunta un listener global unificado para forms con data-confirm /
     * data-loader / data-confirm + data-loader.
     *
     * Bug fix 2026-09-22: antes habia DOS listeners (uno para confirm,
     * otro para loader) que se disparaban ambos al hacer submit. El de
     * loader cambiaba el boton a "Guardando..." antes de que el usuario
     * confirmara, y si cancelaba el swal el boton quedaba stuck.
     *
     * Ahora es un solo listener que:
     *  - Si data-confirm: previene submit, muestra swal2, y SOLO si el
     *    usuario confirma aplica el loader (si data-loader) y reenvia.
     *  - Si solo data-loader (sin confirm): aplica el loader directo.
     *  - Si re-envio (flag _confirmed=1): no hace nada (deja pasar).
     *
     * Llamar una sola vez en el bootstrap.
     */
    bindFormHandlers() {
        const applyLoader = (form) => {
            const full = form.dataset.loader === 'full';
            const iconOnly = form.dataset.loaderIconOnly !== undefined;
            const btn = form.querySelector('button[type="submit"], input[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.dataset._originalText = btn.innerHTML;
                if (iconOnly) {
                    // Action-buttons (cuadrados 32x32): solo spinner para no
                    // expandir el boton ni desplazar los iconos vecinos.
                    btn.innerHTML = '<span class="inline-block w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin"></span>';
                } else {
                    btn.innerHTML = '<span class="inline-block w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin me-2"></span>' + (btn.dataset.loadingText || 'Guardando...');
                }
            }
            if (full) this.loading('Guardando...');
        };

        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (!(form instanceof HTMLFormElement)) return;
            if (form.dataset._confirmed === '1') return;

            const msg = form.dataset.confirm;
            const hasLoader = form.dataset.loader !== undefined || form.hasAttribute('data-loader');

            if (msg) {
                // Confirm + (opcional) loader: solo loader si confirma.
                e.preventDefault();
                this.confirm({
                    title: msg,
                    icon: 'warning',
                    danger: form.dataset.confirmDanger === 'true',
                    confirmText: form.dataset.confirmText || 'Sí, continuar',
                }).then((ok) => {
                    if (!ok) return; // usuario cancelo: no tocamos el boton
                    if (hasLoader) applyLoader(form);
                    form.dataset._confirmed = '1';
                    form.submit();
                });
            } else if (hasLoader) {
                // Solo loader (sin confirm): aplicar y dejar pasar.
                applyLoader(form);
            }
        }, true);
    }

    /**
     * Override window.alert / window.confirm. Llamar una sola vez al boot.
     *
     * OJO: window.confirm retorna Promise<bool>, NO bool sync. Codigo que
     * use `if (confirm(...))` deja de funcionar como espera. Migrar a
     * SwalHelper.confirm(...) directo o a data-confirm en el form.
     */
    overrideNativeDialogs() {
        window.alert = (msg) => this.alert(typeof msg === 'string' ? msg : 'Aviso');
        window.confirm = (msg) => this.confirm({ title: typeof msg === 'string' ? msg : '¿Continuar?' });
    }
}

const instance = new SwalHelperClass();
window.SwalHelper = instance;

// Auto-init en DOMContentLoaded para que los handlers queden antes de que
// cualquier form sea enviado. Si el script se carga tarde (defer), DOMContentLoaded
// ya disparo; en ese caso usamos setTimeout(fn, 0) para correr al final del tick.
function bootstrap() {
    instance.bindFormHandlers();
    instance.overrideNativeDialogs();
}
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootstrap, { once: true });
} else {
    setTimeout(bootstrap, 0);
}

export default instance;
