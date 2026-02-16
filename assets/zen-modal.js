/**
 * ZenAdmin Modal
 * Handles the custom interaction dialog.
 *
 * @param {Window}   window   - The global window object.
 * @param {Document} document - The document object.
 */
(function (window, document) {
    'use strict';

    const ZenModal = {
        overlay: null,
        modal: null,
        lastFocusedElement: null,

        /**
         * Initialize the modal HTML structure
         */
        init: function () {
            if (this.overlay) return;

            // Create Overlay
            this.overlay = document.createElement('div');
            this.overlay.className = 'zenadmin-modal-overlay';
            this.overlay.style.display = 'none';
            this.overlay.setAttribute('aria-hidden', 'true');

            // Create Modal
            this.modal = document.createElement('div');
            this.modal.className = 'zenadmin-modal';
            this.modal.setAttribute('role', 'dialog');
            this.modal.setAttribute('aria-modal', 'true');
            this.modal.setAttribute('aria-labelledby', 'zenadmin-modal-title');

            this.overlay.appendChild(this.modal);
            document.body.appendChild(this.overlay);

            // Bind Events
            this.overlay.addEventListener('click', (e) => {
                if (e.target === this.overlay) this.close();
            });

            document.addEventListener('keydown', (e) => {
                if (!this.isOpen()) return;

                if (e.key === 'Escape') {
                    this.close();
                }

                if (e.key === 'Tab') {
                    this.handleTab(e);
                }
            });
        },

        /**
         * Open the modal with configuration
         * @param {Object} config 
         */
        open: function (config) {
            this.init();
            // Store currently focused element
            this.lastFocusedElement = this.modal.ownerDocument.activeElement;

            // Build role checkboxes HTML
            let rolesHtml = '';
            if (config.roles && typeof config.roles === 'object') {
                for (const [roleSlug, roleName] of Object.entries(config.roles)) {
                    rolesHtml += `
                        <label class="zenadmin-role-checkbox">
                            <input type="checkbox" name="zenadmin-hidden-for" value="${roleSlug}" checked>
                            ${this.escapeHtml(roleName)}
                        </label>
                    `;
                }
            }

            // Render Content
            if (config.type === 'confirm') {
                this.modal.innerHTML = `
                    <h2 id="zenadmin-modal-title">
                        <span class="dashicons dashicons-warning"></span>
                        ${config.title || 'Confirm Action'}
                    </h2>
                    <div class="zenadmin-modal-body">
                        <p>${this.escapeHtml(config.message || 'Are you sure?')}</p>
                    </div>
                    <div class="zenadmin-modal-footer">
                        <button class="zenadmin-btn zenadmin-btn-secondary" id="zenadmin-cancel-btn">${config.i18n.cancel || 'Cancel'}</button>
                        <button class="zenadmin-btn zenadmin-btn-primary" id="zenadmin-confirm-btn">${config.i18n.confirm || 'Confirm'}</button>
                    </div>
                `;
            } else {
                this.modal.innerHTML = `
                    <h2 id="zenadmin-modal-title">
                        <span class="dashicons dashicons-shield"></span>
                        ${config.title || 'Block Element'}
                    </h2>
                    <div class="zenadmin-modal-body">
                        <div class="zenadmin-warning" id="zenadmin-specificity-warning">
                            <span class="dashicons dashicons-warning"></span>
                            ${config.warning || 'Warning: This selector matches multiple elements.'}
                        </div>
                        
                        <div class="zenadmin-field">
                            <label>${config.i18n.label || 'Label'}</label>
                            <input type="text" id="zenadmin-label-input" value="${this.escapeHtml(config.label)}" placeholder="e.g. Annoying Banner">
                        </div>

                        <div class="zenadmin-field">
                            <label>Target Selector</label>
                            <code>${this.escapeHtml(config.selector)}</code>
                        </div>

                        <div class="zenadmin-field zenadmin-roles-field">
                            <label>${config.i18n.hiddenFor || 'Hide for roles:'}</label>
                            <div class="zenadmin-roles-list">
                                ${rolesHtml}
                            </div>
                        </div>

                        <div class="zenadmin-field">
                             <label>
                                <input type="checkbox" id="zenadmin-session-only"> 
                                ${config.i18n.sessionOnly || 'Hide for this session only'}
                            </label>
                        </div>
                    </div>
                    <div class="zenadmin-modal-footer">
                        <button class="zenadmin-btn zenadmin-btn-secondary" id="zenadmin-cancel-btn">${config.i18n.cancel || 'Cancel'}</button>

                        <button class="zenadmin-btn zenadmin-btn-primary" id="zenadmin-confirm-btn">${config.i18n.confirm || 'Hide Element'}</button>
                    </div>
                `;
            }

            // Insert "Restrict Access" field if URL is present (before Session checkbox)
            if (config.targetUrl && config.type !== 'confirm') {
                const sessionField = this.modal.querySelector('#zenadmin-session-only').closest('.zenadmin-field');
                const hardBlockHtml = `
                    <div class="zenadmin-field" style="margin-bottom: 10px; border-left: 3px solid #d63638; padding-left: 10px;">
                         <label style="color: #d63638; font-weight: 500;">
                            <input type="checkbox" id="zenadmin-hard-block" value="1"> 
                            Restrict Access (Hard Block)
                        </label>
                        <p class="description" style="margin: 0; font-size: 11px; color: #666;">
                            Also blocks access to: <code>${this.escapeHtml(config.targetUrl)}</code>
                        </p>
                    </div>
                `;
                sessionField.insertAdjacentHTML('beforebegin', hardBlockHtml);
            }

            // Show Specificity Warning if needed
            if (config.showWarning) {
                document.getElementById('zenadmin-specificity-warning').style.display = 'block';
            }

            // Show
            this.overlay.style.display = 'flex';
            this.overlay.setAttribute('aria-hidden', 'false');

            // Bind Button Events
            document.getElementById('zenadmin-cancel-btn').addEventListener('click', () => {
                this.close();
                if (config.onCancel) config.onCancel();
            });

            document.getElementById('zenadmin-confirm-btn').addEventListener('click', () => {
                if (config.type === 'confirm') {
                    if (config.onConfirm) config.onConfirm();
                    this.close();
                    return;
                }

                const label = document.getElementById('zenadmin-label-input').value;
                const isSession = document.getElementById('zenadmin-session-only').checked;

                // Get Hard Block status (if exists)
                const hardBlockCheckbox = document.getElementById('zenadmin-hard-block');
                const isHardBlock = hardBlockCheckbox ? hardBlockCheckbox.checked : false;

                // Collect checked roles
                const hiddenFor = [];
                this.modal.querySelectorAll('input[name="zenadmin-hidden-for"]:checked').forEach(cb => {
                    hiddenFor.push(cb.value);
                });

                if (config.onConfirm) {
                    config.onConfirm({
                        label: label,
                        isSession: isSession,
                        hiddenFor: hiddenFor,
                        targetUrl: config.targetUrl, // Pass URL back
                        isHardBlock: isHardBlock     // Pass Hard Block status
                    });
                }
                this.close();
            });



            // Trap Focus
            const focusable = this.modal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
            if (focusable.length) {
                focusable[0].focus();
            }
        },

        /**
         * Close the modal
         */
        close: function () {
            if (!this.overlay) return;
            this.overlay.style.display = 'none';
            this.overlay.setAttribute('aria-hidden', 'true');
            if (this.lastFocusedElement) {
                this.lastFocusedElement.focus();
            }
        },

        /**
         * Check if modal is open
         */
        isOpen: function () {
            return this.overlay && this.overlay.style.display === 'flex';
        },

        escapeHtml: function (text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    /**
     * ZenAdmin Toast Notification System
     * Replaces native alert() with custom styled notifications
     */
    const ZenToast = {
        container: null,

        /**
         * Initialize toast container
         */
        init: function () {
            if (this.container) return;

         * Show a toast notification
                *
         * @param { string } message - The message to display.
         * @param { string } type - Type: 'success', 'error', 'warning', 'info'.
         * @param { number } duration - Duration in ms(0 = manual close).
         */
            show: function (message, type = 'info', duration = 4000) {
                this.init();

                const toast = document.createElement('div');
                toast.className = `zenadmin-toast zenadmin-toast-${type}`;

                const icons = {
                    success: 'yes-alt',
                    error: 'dismiss',
                    warning: 'warning',
                    info: 'info'
                };

                toast.innerHTML = `
                <span class="dashicons dashicons-${icons[type] || 'info'}"></span>
                <span class="zenadmin-toast-message">${this.escapeHtml(message)}</span>
                <button class="zenadmin-toast-close" aria-label="Close">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            `;

                // Close button handler
                toast.querySelector('.zenadmin-toast-close').addEventListener('click', () => {
                    this.dismiss(toast);
                });

                this.container.appendChild(toast);

                // Animate in
                requestAnimationFrame(() => {
                    toast.classList.add('zenadmin-toast-visible');
                });

                // Auto dismiss
                if (duration > 0) {
                    setTimeout(() => {
                        this.dismiss(toast);
                    }, duration);
                }

                return toast;
            },

            /**
             * Dismiss a toast
             */
            dismiss: function (toast) {
                if (!toast || !toast.parentNode) return;

                toast.classList.remove('zenadmin-toast-visible');
                toast.classList.add('zenadmin-toast-hiding');

                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            },

            /**
             * Shorthand methods
             */
            success: function (message, duration) {
                return this.show(message, 'success', duration);
            },

            error: function (message, duration) {
                return this.show(message, 'error', duration || 6000);
            },

            warning: function (message, duration) {
                return this.show(message, 'warning', duration || 5000);
            },

            info: function (message, duration) {
                return this.show(message, 'info', duration);
            },

            escapeHtml: function (text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        };

        // Expose Globals
        window.ZenAdminModal = ZenModal;
        window.ZenAdminToast = ZenToast;

    })(window, document);
