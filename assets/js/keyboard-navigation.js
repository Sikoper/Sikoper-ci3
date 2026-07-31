/**
 * Keyboard Navigation for Forms (MYOB Style)
 * Allows users to navigate form inputs using Arrow Keys and Enter.
 */
document.addEventListener('DOMContentLoaded', function() {
    // We target inputs, selects, and textareas inside forms.
    // Exclude hidden inputs and disabled elements.
    const getFocusableElements = () => {
        // Find visible inputs, textareas, buttons, and Select2 containers
        const elements = Array.from(new Set(document.querySelectorAll('input:not([type="hidden"]):not([disabled]):not([readonly]), select:not([disabled]), textarea:not([disabled]), button[type="submit"]:not([disabled]), button#tombol_simpan:not([disabled]), button#tombol_simpan_setoran:not([disabled]), button.btn-success:not([disabled]), button.btn-primary:not([disabled]), .select2-selection')));
        
        return elements.filter(el => {
            // Include select2-selection spans
            if (el.classList.contains('select2-selection')) return true;
            // Native select is usually hidden if select2 is applied on it, so we skip hidden native selects
            if (el.classList.contains('select2-hidden-accessible')) return false;
            return el.offsetWidth > 0 || el.offsetHeight > 0;
        });
    };

    // Observer to automatically highlight and style the active button whenever a SweetAlert2 popup opens
    const swalObserver = new MutationObserver(function() {
        const swalModal = document.querySelector('.swal2-popup, .swal-modal');
        if (swalModal && (swalModal.offsetWidth > 0 || swalModal.offsetHeight > 0)) {
            const buttons = Array.from(swalModal.querySelectorAll('.swal2-actions button:not([disabled]):not(.swal2-close), .swal-footer button:not([disabled])')).filter(btn => {
                return btn.offsetWidth > 0 || btn.offsetHeight > 0 || window.getComputedStyle(btn).display !== 'none';
            });
            if (buttons.length > 0) {
                const confirmBtn = swalModal.querySelector('.swal2-confirm') || swalModal.querySelector('.swal-button--confirm') || buttons[0];
                let activeBtn = buttons.find(b => b === document.activeElement) || confirmBtn;
                if (activeBtn && !activeBtn.dataset.swalFocused) {
                    buttons.forEach(b => {
                        b.style.outline = '';
                        b.style.boxShadow = '';
                        b.style.transform = '';
                        delete b.dataset.swalFocused;
                    });
                    activeBtn.focus();
                    activeBtn.style.outline = '3px solid #0d6efd';
                    activeBtn.style.boxShadow = '0 0 0 4px rgba(13, 110, 253, 0.35)';
                    activeBtn.style.transform = 'scale(1.04)';
                    activeBtn.dataset.swalFocused = 'true';
                }
            }
        }
    });
    swalObserver.observe(document.body, { childList: true, subtree: true });

    document.addEventListener('keydown', function(e) {
        // 1. SweetAlert2 / SweetAlert modal keyboard arrow navigation & selection support
        const swalModal = document.querySelector('.swal2-popup, .swal-modal');
        if (swalModal && (swalModal.offsetWidth > 0 || swalModal.offsetHeight > 0)) {
            const buttons = Array.from(swalModal.querySelectorAll('.swal2-actions button:not([disabled]):not(.swal2-close), .swal-footer button:not([disabled])')).filter(btn => {
                return btn.offsetWidth > 0 || btn.offsetHeight > 0 || window.getComputedStyle(btn).display !== 'none';
            });
            if (buttons.length > 0) {
                const updateSwalFocus = (btn) => {
                    buttons.forEach(b => {
                        b.style.outline = '';
                        b.style.boxShadow = '';
                        b.style.transform = '';
                        delete b.dataset.swalFocused;
                    });
                    btn.focus();
                    btn.style.outline = '3px solid #0d6efd';
                    btn.style.boxShadow = '0 0 0 4px rgba(13, 110, 253, 0.35)';
                    btn.style.transform = 'scale(1.04)';
                    btn.dataset.swalFocused = 'true';
                };

                // Left / Right / Up / Down / Tab arrow navigation between SweetAlert buttons
                if (['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Tab'].includes(e.key)) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    let currentIdx = buttons.indexOf(document.activeElement);
                    let nextIdx = 0;
                    if (currentIdx === -1) {
                        nextIdx = (e.key === 'ArrowRight' || e.key === 'ArrowDown' || (e.key === 'Tab' && !e.shiftKey)) ? Math.min(1, buttons.length - 1) : 0;
                    } else {
                        if (e.key === 'ArrowRight' || e.key === 'ArrowDown' || (e.key === 'Tab' && !e.shiftKey)) {
                            nextIdx = (currentIdx + 1) % buttons.length;
                        } else {
                            nextIdx = (currentIdx - 1 + buttons.length) % buttons.length;
                        }
                    }
                    updateSwalFocus(buttons[nextIdx]);
                    return;
                }

                // Enter / Space key activates the focused SweetAlert button
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    let targetBtn = buttons.find(b => b === document.activeElement);
                    if (!targetBtn) {
                        targetBtn = swalModal.querySelector('.swal2-confirm') || buttons[0];
                    }
                    if (targetBtn) {
                        targetBtn.click();
                    }
                    return;
                }
            }
            return;
        }

        let activeEl = document.activeElement;
        
        // 2. Quick-Save shortcut: Ctrl+Enter anywhere in form triggers Simpan
        if (e.key === 'Enter' && e.ctrlKey) {
            e.preventDefault();
            const saveBtn = document.querySelector('#tombol_simpan, #tombol_simpan_setoran, button[type="submit"]');
            if (saveBtn && !saveBtn.disabled) {
                saveBtn.click();
            }
            return;
        }

        // If ANY Select2 dropdown menu is currently OPEN on the page, let Select2 handle ALL keyboard events!
        if (document.querySelector('.select2-container--open') !== null || 
            activeEl.classList.contains('select2-search__field') || 
            activeEl.closest('.select2-dropdown') !== null ||
            (e.target && ((e.target.classList && e.target.classList.contains('select2-search__field')) || (e.target.closest && e.target.closest('.select2-dropdown') !== null)))) {
            return; 
        }

        let isSelect2 = false;
        if (activeEl.classList.contains('select2-hidden-accessible')) {
            isSelect2 = true;
            let selectionSpan = null;
            const nextContainer = activeEl.nextElementSibling;
            if (nextContainer && nextContainer.classList.contains('select2-container')) {
                selectionSpan = nextContainer.querySelector('.select2-selection');
            } else {
                selectionSpan = $(activeEl).next('.select2-container').find('.select2-selection')[0];
            }
            if (selectionSpan) activeEl = selectionSpan;
        } else if (activeEl.closest('.select2-container') !== null) {
            isSelect2 = true;
            const selectionSpan = activeEl.closest('.select2-container').querySelector('.select2-selection');
            if (selectionSpan) activeEl = selectionSpan;
        }

        const activeTag = activeEl.tagName.toLowerCase();
        const isFormElement = ['input', 'select', 'textarea', 'button', 'a'].includes(activeTag) || isSelect2;
        
        // 3. Smart Error Recovery & Button/Body Navigation:
        // When ArrowUp or ArrowDown is pressed while focused on a button (#tombol_simpan, Batal, etc.) OR when focus was lost (!isFormElement):
        // If there is any validation error field (.is-invalid), jump directly to that invalid field!
        if ((e.key === 'ArrowUp' || e.key === 'ArrowDown') && (!isFormElement || activeTag === 'button' || activeTag === 'a' || ['submit', 'button', 'reset'].includes(activeEl.type) || activeEl.id === 'tombol_simpan' || activeEl.classList.contains('btn'))) {
            const invalidEl = document.querySelector('.is-invalid, .error, [aria-invalid="true"]');
            if (invalidEl) {
                e.preventDefault();
                e.stopPropagation();
                let targetToFocus = invalidEl;
                if (invalidEl.tagName && invalidEl.tagName.toLowerCase() === 'select' && $(invalidEl).hasClass('select2-hidden-accessible')) {
                    const s2 = $(invalidEl).next('.select2-container').find('.select2-selection');
                    if (s2.length) targetToFocus = s2[0];
                }
                targetToFocus.focus();
                if (targetToFocus.tagName && targetToFocus.tagName.toLowerCase() === 'input' && ['text', 'number', 'tel', 'email'].includes(targetToFocus.type)) {
                    targetToFocus.select();
                }
                return;
            }
            // If no error field is visible, but focus was lost (!isFormElement), let ArrowUp return to the last form element
            if (!isFormElement) {
                const elements = getFocusableElements();
                if (elements.length > 0) {
                    e.preventDefault();
                    e.stopPropagation();
                    const targetEl = e.key === 'ArrowUp' ? elements[elements.length - 1] : elements[0];
                    targetEl.focus();
                    if (targetEl.tagName && targetEl.tagName.toLowerCase() === 'input' && ['text', 'number', 'tel', 'email'].includes(targetEl.type)) {
                        targetEl.select();
                    }
                }
                return;
            }
        }

        if (!isFormElement) return;

        // 3. If Enter or Space is pressed on a button (like #tombol_simpan or submit), execute click!
        if ((e.key === 'Enter' || e.key === ' ' || e.key === 'Spacebar') && (activeTag === 'button' || ['submit', 'button'].includes(activeEl.type) || activeEl.id === 'tombol_simpan' || activeEl.classList.contains('btn'))) {
            e.preventDefault();
            activeEl.click();
            return;
        }

        if (activeTag === 'textarea' && (e.key === 'Enter' || e.key === 'ArrowUp' || e.key === 'ArrowDown')) {
            return; // Let textarea behave normally
        }

        // 4. Handle native <select> elements (like #jenis_rekening, etc.)
        if (activeTag === 'select' && !isSelect2) {
            // Let Space open dropdown menu natively
            if (e.key === ' ' || e.key === 'Spacebar') {
                if (typeof activeEl.showPicker === 'function') {
                    e.preventDefault();
                    try { activeEl.showPicker(); } catch (err) {}
                }
                return;
            }
            // If Enter is pressed on a select that is empty, try to open it
            if (e.key === 'Enter') {
                if (activeEl.value === '' && typeof activeEl.showPicker === 'function') {
                    e.preventDefault();
                    try {
                        activeEl.showPicker();
                        return;
                    } catch (err) {}
                }
            }
        }

        // 5. Handle Select2 elements
        if (isSelect2) {
            // Let Space open select2
            if (e.key === ' ' || e.key === 'Spacebar') {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                const container = activeEl.closest('.select2-container');
                const selectEl = container ? (container.previousElementSibling || $(container).prev('select')[0]) : null;
                if (selectEl) {
                    try { $(selectEl).select2('open'); } catch (err) {}
                }
                return;
            }
            // If Enter is pressed on select2 selection span that is empty, open it
            if (e.key === 'Enter') {
                const container = activeEl.closest('.select2-container');
                const selectEl = container ? (container.previousElementSibling || $(container).prev('select')[0]) : null;
                if (selectEl && (selectEl.value === '' || selectEl.value === '0' || !selectEl.value)) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    try {
                        $(selectEl).select2('open');
                        return;
                    } catch (err) {}
                }
            }
        }

        const elements = getFocusableElements();
        const currentIndex = elements.indexOf(activeEl);

        if (currentIndex > -1) {
            let nextIndex = null;

            // Handle space key navigation (only if not typing in text field)
            const isTextInput = activeTag === 'input' && ['text', 'password', 'email', 'search', 'tel', 'url', 'number'].includes(activeEl.type);
            const isSpaceKey = e.key === ' ' || e.key === 'Spacebar';
            
            if (e.key === 'Enter' || e.key === 'ArrowDown' || (isSpaceKey && !isTextInput && activeTag !== 'textarea')) {
                // Do not prevent default for space on select2 or buttons as they use it to open/click
                if (!(isSpaceKey && (isSelect2 || activeTag === 'button'))) {
                    e.preventDefault();
                    if (e.key === 'ArrowDown' || (isSelect2 && e.key === 'Enter')) {
                        e.stopPropagation();
                    }
                }
                nextIndex = currentIndex + 1;
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                e.stopPropagation();
                nextIndex = currentIndex - 1;
            } else if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
                if (isTextInput) {
                    if (e.key === 'ArrowLeft' && activeEl.selectionStart > 0) return;
                    if (e.key === 'ArrowRight' && activeEl.selectionEnd < activeEl.value.length) return;
                }
                if (isSelect2) return; // Let select2 handle left/right if needed
                
                e.preventDefault();
                nextIndex = e.key === 'ArrowRight' ? currentIndex + 1 : currentIndex - 1;
            }

            if (nextIndex !== null) {
                if (nextIndex >= elements.length || nextIndex < 0) {
                    return; 
                }

                const nextElement = elements[nextIndex];
                
                // If the next element is a native select but it has select2, focus the select2 instead
                if (nextElement.tagName.toLowerCase() === 'select' && $(nextElement).hasClass('select2-hidden-accessible')) {
                    const s2 = $(nextElement).next('.select2-container').find('.select2-selection');
                    if (s2.length) s2.focus();
                } else {
                    nextElement.focus();
                    if (nextElement.tagName.toLowerCase() === 'input' && ['text', 'number', 'tel', 'email'].includes(nextElement.type)) {
                        nextElement.select();
                    }
                }
            }
        }
    }, true);

    // -------------------------------------------------------------------------
    // BANNER PETUNJUK KEYBOARD KASIR
    // -------------------------------------------------------------------------
    setTimeout(function() {
        // Sisipkan Keyboard Shortcuts Banner di bawah tombol Simpan/Submit
        const saveBtn = document.querySelector('#tombol_simpan, #tombol_simpan_setoran, button[type="submit"]');
        if (saveBtn && !document.getElementById('sikoper-keyboard-banner')) {
            const btnContainer = saveBtn.closest('.text-center') || saveBtn.parentElement;
            if (btnContainer) {
                const bannerHtml = `
                <div id="sikoper-keyboard-banner" class="alert alert-light border shadow-sm mt-3 mb-2 py-2 px-3 text-center" style="border-radius: 8px; font-size: 0.85rem; background-color: #f8f9fa; border-color: #e9ecef !important;">
                    <i class="fa fa-keyboard text-primary me-1"></i>
                    <strong>Tip Kasir:</strong>
                    <span class="text-muted ms-1">
                        Gunakan <kbd class="bg-primary text-white px-1 py-0" style="font-size: 0.75rem;">Enter</kbd> / <kbd class="bg-primary text-white px-1 py-0" style="font-size: 0.75rem;">↑ ↓</kbd> untuk pindah kolom &bull;
                        <kbd class="bg-primary text-white px-1 py-0" style="font-size: 0.75rem;">Spasi</kbd> buka pilihan &bull;
                        <kbd class="bg-success text-white px-1 py-0" style="font-size: 0.75rem;">Ctrl + Enter</kbd> langsung Simpan
                    </span>
                </div>`;
                $(btnContainer).after(bannerHtml);
            }
        }
    }, 150);
});
