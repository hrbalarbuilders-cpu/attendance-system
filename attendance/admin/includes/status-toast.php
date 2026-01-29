<?php
/**
 * Global Status Toast Notification
 * This file is included in header.php to provide a unified notification system across all admin pages.
 */
?>

<!-- Global Status Toast Container -->
<div id="statusAlertWrapper" class="position-fixed start-50 translate-middle-x p-3" style="z-index: 9999; top: 20px;">
    <div id="statusAlert"
        class="alert shadow-lg d-none align-items-center justify-content-between mb-0 text-center animate-toast"
        role="alert" style="min-width: 300px; border-radius: 12px; border: none; padding: 12px 20px;">
        <span id="statusAlertText" class="fw-semibold"></span>
        <button type="button" class="btn-close ms-2" aria-label="Close"
            onclick="document.getElementById('statusAlert').classList.add('d-none');">
        </button>
    </div>
</div>

<style>
    .animate-toast {
        animation: toastSlideIn 0.3s var(--ease-bounce, cubic-bezier(0.68, -0.55, 0.265, 1.55));
    }

    @keyframes toastSlideIn {
        from {
            transform: translateY(-100px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .alert-success {
        background: var(--color-success-gradient, linear-gradient(135deg, #10b981 0%, #059669 100%)) !important;
        color: var(--text-inverse, white) !important;
    }

    .alert-danger {
        background: var(--color-danger-gradient, linear-gradient(135deg, #ef4444 0%, #dc2626 100%)) !important;
        color: var(--text-inverse, white) !important;
    }

    .alert-info {
        background: var(--color-info-gradient, linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)) !important;
        color: var(--text-inverse, white) !important;
    }

    #statusAlert .btn-close {
        filter: brightness(0) invert(1);
        opacity: 0.8;
    }
</style>

<script>
    (function () {
        let statusTimer;

        /**
         * Show a global status message (toast)
         * @param {string} message - The text to display
         * @param {string} type - 'success', 'danger', or 'info'
         * @param {number} duration - Auto-hide duration in ms (default 3000)
         */
        window.showStatus = function (message, type = 'success', duration = 3000) {
            const box = document.getElementById('statusAlert');
            const text = document.getElementById('statusAlertText');
            if (!box || !text) return;

            text.textContent = message;

            // Reset classes
            box.classList.remove('d-none', 'alert-success', 'alert-danger', 'alert-info', 'd-flex');

            // Add specific type
            const alertClass = type === 'danger' ? 'alert-danger' : (type === 'info' ? 'alert-info' : 'alert-success');
            box.classList.add(alertClass, 'd-flex');

            if (statusTimer) clearTimeout(statusTimer);
            statusTimer = setTimeout(() => {
                box.classList.add('d-none');
            }, duration);
        };

        // Auto-check for URL flags on every page load
        document.addEventListener('DOMContentLoaded', () => {
            const params = new URLSearchParams(window.location.search);

            // Map common flags to messages
            const flags = {
                'success': { msg: params.get('message') || 'Action completed successfully.', type: 'success' },
                'added': { msg: params.get('message') || 'Record added successfully.', type: 'success' },
                'updated': { msg: params.get('message') || 'Changes saved successfully.', type: 'success' },
                'deleted': { msg: params.get('message') || 'Record deleted successfully.', type: 'success' },
                'error': { msg: params.get('message') || params.get('msg') || 'An error occurred.', type: 'danger' }
            };

            for (const [key, config] of Object.entries(flags)) {
                if (params.has(key)) {
                    // Special handling for success parameter value if it's not just a flag
                    if (key === 'success' && params.get('success') !== '1' && params.get('success') !== '') {
                        showStatus(params.get('success'), 'success');
                    } else {
                        showStatus(config.msg, config.type);
                    }

                    // Clean up URL to prevent repeated toast on refresh
                    window.history.replaceState({}, document.title, window.location.pathname);
                    break;
                }
            }
        });

    })();
</script>