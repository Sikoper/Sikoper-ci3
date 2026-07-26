/**
 * Keyboard Navigation for Forms (MYOB Style)
 * Allows users to navigate form inputs using Arrow Keys and Enter.
 */
document.addEventListener('DOMContentLoaded', function() {
    // We target inputs, selects, and textareas inside forms.
    // Exclude hidden inputs and disabled elements.
    const getFocusableElements = () => {
        // Find visible inputs, textareas, buttons, and Select2 containers
        const elements = Array.from(new Set(document.querySelectorAll('input:not([type="hidden"]):not([disabled]):not([readonly]), select:not([disabled]), textarea:not([disabled]), button[type="submit"]:not([disabled]), button#tombol_simpan:not([disabled]), button#tombol_simpan_setoran:not([disabled]), button.btn-success:not([disabled]), button.btn-primary:not([disabled]), .select2-selection[tabindex]')));
        
        return elements.filter(el => {
            // Include select2
            if (el.classList.contains('select2-selection')) return true;
            // Native select is usually hidden if select2 is applied on it, so we skip hidden native selects
            return el.offsetWidth > 0 || el.offsetHeight > 0;
        });
    };

    document.addEventListener('keydown', function(e) {
        // 1. SweetAlert2 support: if a SweetAlert modal is open, let Enter close/confirm it!
        const swalModal = document.querySelector('.swal2-popup');
        if (swalModal && swalModal.offsetWidth > 0) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const confirmBtn = swalModal.querySelector('.swal2-confirm');
                const closeBtn = swalModal.querySelector('.swal2-close');
                if (confirmBtn && confirmBtn.offsetWidth > 0 && !confirmBtn.disabled) {
                    confirmBtn.click();
                } else if (closeBtn && closeBtn.offsetWidth > 0) {
                    closeBtn.click();
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

        // If inside select2, active element might be the selection span or the search input
        if (activeEl.classList.contains('select2-search__field')) {
            // Let select2 handle its own arrows if it's open
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') return; 
        }

        const isSelect2 = activeEl.closest('.select2-container') !== null;
        if (isSelect2) {
            // normalize active element to the selection span for indexing
            const selectionSpan = activeEl.closest('.select2-container').querySelector('.select2-selection');
            if (selectionSpan) activeEl = selectionSpan;
        }

        const activeTag = activeEl.tagName.toLowerCase();
        const isFormElement = ['input', 'select', 'textarea', 'button'].includes(activeTag) || isSelect2;
        
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
                }
                nextIndex = currentIndex + 1;
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
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
    });
});
