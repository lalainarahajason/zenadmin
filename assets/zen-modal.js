/**
 * ZenAdmin Modal
 * Handles the custom interaction dialog.
 */

(function(window, document) {
    'use strict';

    const ZenModal = {
        overlay: null,
        modal: null,
        lastFocusedElement: null,
        
        /**
         * Initialize the modal HTML structure
         */
        init: function() {
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
                if (e.key === 'Escape' && this.isOpen()) {
                    this.close();
                }
            });
        },

        /**
         * Open the modal with configuration
         * @param {Object} config 
         */
        open: function(config) {
            this.init();
            this.lastFocusedElement = document.activeElement;

            // Render Content
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

                    <div class="zenadmin-field">
                         <label>
                            <input type="checkbox" id="zenadmin-session-only"> 
                            ${config.i18n.sessionOnly || 'Hide for this session only'}
                        </label>
                    </div>
                </div>
                <div class="zenadmin-modal-footer">
                    <button class="zenadmin-btn zenadmin-btn-secondary" id="zenadmin-cancel-btn">${config.i18n.cancel || 'Cancel'}</button>
                    <button class="zenadmin-btn zenadmin-btn-primary" id="zenadmin-confirm-btn">${config.i18n.confirm || 'Block Forever'}</button>
                </div>
            `;

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
                const label = document.getElementById('zenadmin-label-input').value;
                const isSession = document.getElementById('zenadmin-session-only').checked;
                
                if (config.onConfirm) {
                    config.onConfirm({
                        label: label,
                        isSession: isSession
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
        close: function() {
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
        isOpen: function() {
            return this.overlay && this.overlay.style.display === 'flex';
        },

        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    // Expose Global
    window.ZenAdminModal = ZenModal;

})(window, document);
