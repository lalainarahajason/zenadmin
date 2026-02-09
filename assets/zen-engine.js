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

            // Admin Bar: Clear Session
            const clearSessionBtn = document.getElementById('wp-admin-bar-zenadmin-clear-session');
            if (clearSessionBtn) {
                clearSessionBtn.querySelector('a').addEventListener('click', (e) => {
                    e.preventDefault();
                    sessionStorage.removeItem('zenadmin_session_blocks');
                    alert('Session blocks cleared. Reloading...');
                    window.location.reload();
                });
            }

            // Admin Bar: Reset All
            const resetAllBtn = document.getElementById('wp-admin-bar-zenadmin-reset-all');
            if (resetAllBtn) {
                resetAllBtn.querySelector('a').addEventListener('click', (e) => {
                    if (!confirm('Warning: This will delete ALL blocked elements database and session.\n\nAre you sure completely reset ZenAdmin?')) {
                        e.preventDefault();
                    } else {
                        sessionStorage.removeItem('zenadmin_session_blocks');
                        // Let link navigation proceed
                    }
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

                // 1. Conflict Prevention: Specificity Check
                const count = document.querySelectorAll(selector).length;
                let showWarning = false;
                let warningMsg = '';

                if (count > 1) {
                    showWarning = true;
                    warningMsg = `Attention: This selector matches ${count} elements. Do you want to block all of them?`;
                }

                // 2. Redundancy Check: Is it already inside a blocked element?
                // This is hard to check perfectly without list of all blocked selectors locally, 
                // but we can check if it matches the 'display: none' style if injected, 
                // but since we are in "Zen Mode", styles might not be applied or we might be looking at raw DOM.
                // We'll skip complex redundancy check for now as it requires syncing blacklist to JS.

                // Whitelist Check
                if (this.isWhitelisted(selector, this.currentTarget)) {
                    alert('Safety Warning: This element is critical and cannot be blocked.');
                    return;
                }

                ZenAdminModal.open({
                    title: this.config.i18n.confirmTitle,
                    selector: selector,
                    label: this.getClassLabel(this.currentTarget),
                    showWarning: showWarning,
                    warning: warningMsg,
                    i18n: this.config.i18n,
                    roles: this.config.roles,
                    onConfirm: (data) => {
                        this.saveBlock(selector, data);
                    },
                    onCancel: () => { }
                });
            } catch (err) {
                console.error('ZenAdmin Selection Error:', err);
                alert('Error generating selector. Check console.');
            }
        },

        generateSelector: function (el) {
            if (el.tagName.toLowerCase() === 'html') return 'html';
            if (el.tagName.toLowerCase() === 'body') return 'body';

            // 0. Admin Menu Strategy: Surgical targeting to avoid hiding parent menus
            const adminMenuLi = el.closest('#adminmenu li');
            if (adminMenuLi) {
                // Check if we're inside a submenu (.wp-submenu)
                const isInSubmenu = el.closest('.wp-submenu') !== null;

                if (isInSubmenu) {
                    // SUBMENU ITEM: Target the specific <a> link, never the parent li
                    // This prevents hiding the entire parent menu when hiding a submenu item
                    const link = el.closest('a');
                    if (link) {
                        const hrefAttr = link.getAttribute('href');
                        if (hrefAttr && hrefAttr !== '#' && hrefAttr.length > 3) {
                            // Use a[href="..."] selector for submenu items
                            return `#adminmenu .wp-submenu a[href="${hrefAttr.replace(/"/g, '\\"')}"]`;
                        }
                    }
                } else {
                    // TOP-LEVEL MENU: Safe to target the li directly
                    // Only if it's NOT a parent with submenu children we want to preserve
                    if (adminMenuLi.id && !/(\d{3,}|[-_]\d+)/.test(adminMenuLi.id)) {
                        const safeId = window.CSS && window.CSS.escape ? window.CSS.escape(adminMenuLi.id) : adminMenuLi.id;
                        return '#' + safeId;
                    }
                    // Fallback to class combination for top-level
                    if (adminMenuLi.className && typeof adminMenuLi.className === 'string') {
                        const classes = adminMenuLi.className.split(/\s+/).filter(c => {
                            return c.length > 2 && !c.startsWith('zenadmin-') && !/^\d+$/.test(c);
                        });
                        if (classes.length > 0) {
                            const safeClasses = classes.map(c => window.CSS && window.CSS.escape ? window.CSS.escape(c) : c);
                            return '#adminmenu li.' + safeClasses.join('.');
                        }
                    }
                }
            }

            // 1. ID Strategy (Strict Filter)
            // Reject generated IDs like 'el-1234', 'ui-id-5'
            if (el.id && !/(\d{3,}|[-_]\d+)/.test(el.id)) {
                const safeId = window.CSS && window.CSS.escape ? window.CSS.escape(el.id) : el.id;
                return '#' + safeId;
            }

            // 2. HREF Surgical Strategy (for links or inside links)
            const link = el.closest('a');
            if (link && link.href) {
                const url = new URL(link.href);
                const params = new URLSearchParams(url.search);

                // WordPress Admin typical params: 'page', 'action', 'tab'
                if (params.has('page')) {
                    return `a[href*="page=${params.get('page')}"]`;
                }
                if (params.has('action')) {
                    return `a[href*="action=${params.get('action')}"]`;
                }
                // Fallback to minimal href match if specific
                // Avoid matching '#' or 'admin.php' generic
                const hrefAttr = link.getAttribute('href');
                if (hrefAttr && hrefAttr !== '#' && hrefAttr.length > 5) {
                    return `a[href="${hrefAttr.replace(/"/g, '\\"')}"]`;
                }
            }

            // 3. Class Combinations
            if (el.className && typeof el.className === 'string') {
                const classes = el.className.split(/\s+/).filter(c => {
                    return !c.startsWith('zenadmin-') && !c.startsWith('ng-') && c.length > 2 && !/^\d+$/.test(c);
                });

                if (classes.length > 0) {
                    // If we have a specific class (not generic like 'wrap', 'notice'), use it.
                    // Heuristic: generic classes usually simple words. Specific often dashed.
                    // For now, join all non-blacklisted classes.
                    const safeClasses = classes.map(c => window.CSS && window.CSS.escape ? window.CSS.escape(c) : c);

                    // If only 1 class and it looks generic (no dashes, < 6 chars), try to add parent
                    if (classes.length === 1 && classes[0].indexOf('-') === -1 && classes[0].length < 6) {
                        // Fallthrough to structural or parent combination
                    } else {
                        return '.' + safeClasses.join('.');
                    }
                }
            }

            // 4. Attribute Matching (src, name, data-*)
            const attrs = ['name', 'data-id', 'data-slug', 'src'];
            for (let attr of attrs) {
                if (el.hasAttribute(attr)) {
                    const val = el.getAttribute(attr);
                    if (val && val.length > 2) {
                        return `${el.tagName.toLowerCase()}[${attr}="${val.replace(/"/g, '\\"')}"]`;
                    }
                }
            }

            // 5. Structural Fallback (nth-of-type) - The "Last Resort"
            // We walk up to find a stable parent, then use path
            let path = [];
            let current = el;
            let depth = 0;
            const maxDepth = 5; // Don't go too deep

            while (current && current.nodeType === Node.ELEMENT_NODE && current.tagName.toLowerCase() !== 'html' && depth < maxDepth) {
                let nodeSelector = current.tagName.toLowerCase();

                if (current.id && !/(\d{3,}|[-_]\d+)/.test(current.id)) {
                    nodeSelector = '#' + (window.CSS && window.CSS.escape ? window.CSS.escape(current.id) : current.id);
                    path.unshift(nodeSelector);
                    break; // Found anchor
                } else if (current.className) {
                    const classes = current.className.split(/\s+/).filter(c => !c.startsWith('zenadmin-') && c.length > 2);
                    if (classes.length > 0) {
                        nodeSelector += '.' + classes.map(c => window.CSS && window.CSS.escape ? window.CSS.escape(c) : c).join('.');
                    }
                }

                if (current !== el) { // Only use nth-of-type for the target or generic parents
                    // actually, let's keep it simple: just tag+class
                }

                // Add nth-of-type if needed for uniqueness among siblings
                const parent = current.parentNode;
                if (parent && parent.children) {
                    const siblings = Array.from(parent.children).filter(c => c.tagName === current.tagName);
                    if (siblings.length > 1) {
                        const index = siblings.indexOf(current) + 1;
                        nodeSelector += `:nth-of-type(${index})`;
                    }
                }

                path.unshift(nodeSelector);
                current = current.parentNode;
                depth++;
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
                label: data.label,
                hidden_for: JSON.stringify(data.hiddenFor || [])
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
