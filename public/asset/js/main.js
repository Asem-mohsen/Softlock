$(function () {
    "use strict";

        // :: 12.0 PreventDefault a Click
    $("a[href='#']").on('click', function ($) {
        $.preventDefault();
    });

    $('.nav-item').each(function() {
        if ($(this).find('.nav-link.active').length > 0) {
            $(this).addClass('menu-open');
        }
    });

});

/*
**
    This function check all the disabled inputs, add the 
    disabled attribute if the user deleted it manually 
**
*/
function monitorDisabledInputs() {
    const disabledInputs = document.querySelectorAll('input[disabled], textarea[disabled]');
    
    disabledInputs.forEach(input => {
        const observer = new MutationObserver(mutations => {
            mutations.forEach(mutation => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'disabled') {
                    if (!input.disabled) {
                        // Re-add the disabled attribute
                        input.disabled = true;

                        // Trigger a change event
                        const event = new Event('change', { bubbles: true });
                        input.dispatchEvent(event);
                    }
                }
            });
        });

        // Observe changes to the attributes of each input
        observer.observe(input, {
            attributes: true
        });
    });
}

// Monitor initially disabled inputs
monitorDisabledInputs();

// Re-monitor inputs if new inputs are added dynamically to the DOM
const bodyObserver = new MutationObserver(mutations => {
    mutations.forEach(mutation => {
        if (mutation.type === 'childList' || mutation.type === 'attributes') {
            monitorDisabledInputs();
        }
    });
});

// Start observing the entire body for changes
bodyObserver.observe(document.body, {
    childList: true,
    attributes: true,
    subtree: true
});