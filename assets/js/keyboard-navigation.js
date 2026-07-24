/**
 * Keyboard Navigation for Forms (MYOB Style)
 * Allows users to navigate form inputs using Arrow Keys and Enter.
 */
document.addEventListener('DOMContentLoaded', function() {
    // We target inputs, selects, and textareas inside forms.
    // Exclude hidden inputs and disabled elements.
    const getFocusableElements = () => {
        return Array.from(document.querySelectorAll('input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled]), button[type="submit"]:not([disabled])'))
            .filter(el => {
                // Ensure it's visible
                return el.offsetWidth > 0 || el.offsetHeight > 0;
            });
    };

    document.addEventListener('keydown', function(e) {
        // Only trigger on form elements
        const activeTag = document.activeElement.tagName.toLowerCase();
        const isFormElement = ['input', 'select', 'textarea', 'button'].includes(activeTag);
        
        if (!isFormElement) return;

        // If it's a textarea and Enter/Up/Down is pressed, let it act normally
        if (activeTag === 'textarea' && (e.key === 'Enter' || e.key === 'ArrowUp' || e.key === 'ArrowDown')) {
            return;
        }

        // Specifically for AutoNumeric inputs (which often use text type)
        // If they press Enter, ArrowDown, ArrowUp, we navigate. 
        // For ArrowLeft/Right we only navigate if cursor is at bounds.
        
        const elements = getFocusableElements();
        const currentIndex = elements.indexOf(document.activeElement);

        if (currentIndex > -1) {
            let nextIndex = null;

            if (e.key === 'Enter' || e.key === 'ArrowDown') {
                e.preventDefault();
                nextIndex = currentIndex + 1;
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                nextIndex = currentIndex - 1;
            } else if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
                // Don't override left/right if the user is editing text inside an input
                // Only move if cursor is at the very beginning/end
                if (activeTag === 'input' && (document.activeElement.type === 'text' || document.activeElement.type === 'number')) {
                    if (e.key === 'ArrowLeft' && document.activeElement.selectionStart > 0) return;
                    if (e.key === 'ArrowRight' && document.activeElement.selectionEnd < document.activeElement.value.length) return;
                }
                
                e.preventDefault();
                nextIndex = e.key === 'ArrowRight' ? currentIndex + 1 : currentIndex - 1;
            }

            if (nextIndex !== null) {
                if (nextIndex >= elements.length || nextIndex < 0) {
                    return; 
                }

                const nextElement = elements[nextIndex];
                nextElement.focus();
                
                // Select text if it's an input so typing replaces immediately (like MYOB)
                if (nextElement.tagName.toLowerCase() === 'input' && ['text', 'number', 'tel', 'email'].includes(nextElement.type)) {
                    nextElement.select();
                }
            }
        }
    });
});
