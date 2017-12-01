if (window.MooTools) {
    window.addEvent('domready', function() {
        var $form = $$('form[action*="usePrimaryCategory=1"]');

        if ($form.length < 1)
        {
            return;
        }

        $form.getNext('.tl_listing_container').getElements('.toggle_select').each(function(el) {
            el.each(function(el) {
                var boundEvent = el.retrieve('boundEvent');

                if (boundEvent) {
                    el.removeEvent('click', boundEvent);
                }

                boundEvent = clickEvent.bind(el);

                el.addEvent('click', boundEvent);
                el.store('boundEvent', boundEvent);

                el.getElements('input[name="primaryCategory"][type="radio"], input[name="primaryCategory"][type="radio"] + label').addEvent('click', function(e){
                    e.stopPropagation();
                });
            });
        });

        function clickEvent(e) {
            var input = this.getElement('input[type="checkbox"]');

            if (!input || input.get('disabled')) {
                return;
            }

            // Radio buttons
            if (input.type == 'radio') {
                if (!input.checked) {
                    input.checked = 'checked';
                }

                return;
            }

            // Checkboxes
            if (e.shift && start) {
                shiftToggle(input);
            } else {
                input.checked = input.checked ? '' : 'checked';

                if (input.get('onclick') == 'Backend.toggleCheckboxes(this)') {
                    Backend.toggleCheckboxes(input); // see #6399
                }
            }

            start = input;
        };
    });
}