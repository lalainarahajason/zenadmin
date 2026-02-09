/**
 * ZenAdmin Engine
 * Handles the selection and blocking logic.
 */

(function (window, document, $) {
    'use strict';

    const Engine = {
        isActive: false,
        hoverOverlay: null,
        currentTarget: null,
        config: window.zenadminConfig || {},

        init: function () {
            if (!this.config.nonce) return;

            this.createOverlay();
            this.bindEvents();
            this.applySessionBlocks();
        },

        createOverlay: function () {
            this.hoverOverlay = document.createElement('div');
            this.hoverOverlay.className = 'zenadmin-hover-overlay';
            document.body.appendChild(this.hoverOverlay);
        },

        bindEvents: function () {
            // Admin Bar Toggle
            const toggleBtn = document.getElementById('wp-admin-bar-zenadmin-toggle');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.toggleMode();
                });
            }

            // Document Hover
            document.addEventListener('mouseover', (e) => {
                if (!this.isActive) return;
                this.handleHover(e);
            }, true);

            // Document Click
            document.addEventListener('click', (e) => {
                if (!this.isActive) return;

                // Allow clicking the toggle button itself
                if (e.target.closest('#wp-admin-bar-zenadmin-toggle') || e.target.closest('.zenadmin-modal-overlay')) {
                    return;
                }

                e.preventDefault();
                e.stopPropagation();
                this.handleClick(e);
            }, true);

            // Escape to exit mode
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isActive) {
                    this.toggleMode(false);
                }
            });
        },

        toggleMode: function (forceState) {
            this.isActive = typeof forceState !== 'undefined' ? forceState : !this.isActive;

            if (this.isActive) {
                document.body.classList.add('zenadmin-mode-active');
            } else {
                document.body.classList.remove('zenadmin-mode-active');
                this.hideOverlay();
            }
        },

        handleHover: function (e) {
            const target = e.target;

            // Ignore admin bar, our overlay, and modal
            if (target.closest('#wpadminbar') ||
                target.classList.contains('zenadmin-hover-overlay') ||
                target.closest('.zenadmin-modal-overlay')) {
                this.hideOverlay();
                return;
            }

            this.currentTarget = target;
            this.positionOverlay(target);
        },

        positionOverlay: function (target) {
            const rect = target.getBoundingClientRect();
            const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

            this.hoverOverlay.style.width = rect.width + 'px';
            this.hoverOverlay.style.height = rect.height + 'px';
            this.hoverOverlay.style.top = (rect.top + scrollTop) + 'px';
            this.hoverOverlay.style.left = (rect.left + scrollLeft) + 'px';
            this.hoverOverlay.style.display = 'block';
        },

        hideOverlay: function () {
            if (this.hoverOverlay) {
                this.hoverOverlay.style.display = 'none';
            }
        },

        handleClick: function (e) {
            if (!this.currentTarget) return;

            try {
                const selector = this.generateSelector(this.currentTarget);
                const count = document.querySelectorAll(selector).length;

                // Whitelist Check
                if (this.isWhitelisted(selector, this.currentTarget)) {
                    alert('Safety Warning: This element is critical and cannot be blocked.');
                    return;
                }

                ZenAdminModal.open({
                    title: this.config.i18n.confirmTitle,
                    selector: selector,
                    label: this.getClassLabel(this.currentTarget), // Improved label guess
                    showWarning: count > 5,
                    warning: `Warning: This selector matches ${count} elements on the page.`,
                    i18n: this.config.i18n,
                    onConfirm: (data) => {
                        this.saveBlock(selector, data);
                    },
                    onCancel: () => {
                        // Maybe just close
                    }
                });
            } catch (err) {
                console.error('ZenAdmin Selection Error:', err);
                alert('Error generating selector for this element. Please check the console.');
            }
        },

        generateSelector: function (el) {
            if (el.tagName.toLowerCase() === 'html') return 'html';
            if (el.tagName.toLowerCase() === 'body') return 'body';

            // 1. HREF Strategy: If it's a link (or inside one), use the HREF for precision
            const link = el.closest('a');
            if (link && link.href) {
                const hrefAttr = link.getAttribute('href');
                if (hrefAttr && hrefAttr !== '#') {
                    // JSON.stringify handles generic string escaping, but for CSS selector attribute matching
                    // we mainly need to escape quotes.
                    let hrefSelector = `a[href="${hrefAttr.replace(/"/g, '\\"')}"]`;

                    try {
                        if (document.querySelectorAll(hrefSelector).length === 1) {
                            return hrefSelector;
                        }
                    } catch (e) {
                        // If selector invalid, fall through
                    }
                }
            }

            // 2. ID Strategy
            if (el.id && !/\d/.test(el.id)) {
                // Use CSS.escape if available, otherwise fallback (mostly for modern browsers)
                const safeId = window.CSS && window.CSS.escape ? window.CSS.escape(el.id) : el.id;
                return '#' + safeId;
            }

            // 3. Path Strategy with nth-of-type for specificity
            let path = [];
            let current = el;

            while (current && current.nodeType === Node.ELEMENT_NODE && current.tagName.toLowerCase() !== 'html') {
                let nodeSelector = current.nodeName.toLowerCase();

                if (current.id && !/\d/.test(current.id)) {
                    const safeId = window.CSS && window.CSS.escape ? window.CSS.escape(current.id) : current.id;
                    nodeSelector += '#' + safeId;
                    path.unshift(nodeSelector);
                    break; // Strong anchor found
                } else {
                    // Classes
                    if (current.className && typeof current.className === 'string' && current.className.trim() !== '') {
                        const classes = current.className.split(/\s+/).filter(c => {
                            return !c.startsWith('zenadmin-') && !c.startsWith('ng-') && c.length > 2;
                        });

                        if (classes.length > 0) {
                            // Escape classes for safety (e.g. tailwind hover:text-white -> hover\:text-white)
                            const safeClasses = classes.map(c => window.CSS && window.CSS.escape ? window.CSS.escape(c) : c);
                            nodeSelector += '.' + safeClasses.join('.');
                        }
                    }

                    // Sibling Specificity (nth-of-type)
                    const parent = current.parentNode;
                    if (parent) {
                        const siblings = Array.from(parent.children).filter(c => c.tagName === current.tagName);
                        if (siblings.length > 1) {
                            const index = siblings.indexOf(current) + 1;
                            nodeSelector += `:nth-of-type(${index})`;
                        }
                    }
                }

                path.unshift(nodeSelector);
                current = current.parentNode;

                if (current && current.tagName.toLowerCase() === 'html') break;
            }

            return path.join(' > ');
        },

        getClassLabel: function (el) {
            // Try to find text content, or class name
            let text = el.innerText || el.textContent;
            if (text && text.length < 30) return text.trim();
            if (el.id) return '#' + el.id;
            if (el.className) return '.' + el.className.split(' ')[0];
            return el.tagName.toLowerCase();
        },

        isWhitelisted: function (selector, el) {
            // Client-side quick check
            if (!this.config.whitelist) return false;

            // Check if the element ITSELF is whitelisted
            if (el) {
                // Use try-catch for matches as some selectors in whitelist might be complex or invalid if not careful
                try {
                    return this.config.whitelist.some(w => el.matches(w));
                } catch (e) {
                    console.error('Invalid whitelist selector check', e);
                    return false;
                }
            }

            // Fallback to strict equality if no element provided
            return this.config.whitelist.some(w => selector === w);
        },

        saveBlock: function (selector, data) {
            if (data.isSession) {
                this.saveSessionBlock(selector);
                this.hideElement(selector);
                this.toggleMode(false);
                return;
            }

            $.post(this.config.ajaxUrl, {
                action: 'zenadmin_save_block',
                security: this.config.nonce,
                selector: selector,
                label: data.label
            }, (response) => {
                if (response.success) {
                    this.hideElement(selector);
                    this.toggleMode(false);
                } else {
                    alert('Error: ' + response.data.message);
                }
            });
        },

        saveSessionBlock: function (selector) {
            let blocks = JSON.parse(sessionStorage.getItem('zenadmin_session_blocks') || '[]');
            blocks.push(selector);
            sessionStorage.setItem('zenadmin_session_blocks', JSON.stringify(blocks));
        },

        applySessionBlocks: function () {
            if (this.config.safeMode) return;

            const blocks = JSON.parse(sessionStorage.getItem('zenadmin_session_blocks') || '[]');
            if (blocks.length > 0) {
                const style = document.createElement('style');
                style.innerHTML = blocks.join(', ') + ' { display: none !important; }';
                document.head.appendChild(style);
            }
        },

        hideElement: function (selector) {
            const style = document.createElement('style');
            style.innerHTML = selector + ' { display: none !important; }';
            document.head.appendChild(style);
        }
    };

    // Initialize on load
    $(document).ready(function () {
        Engine.init();
    });

})(window, document, jQuery);
