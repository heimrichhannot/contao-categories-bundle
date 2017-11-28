// if (window.MooTools) {
//     window.addEvent('domready', function() {
//         $$('.toggle_select.primary-category').each(function(el) {
//             var boundEvent = el.retrieve('boundEvent');
//
//             if (boundEvent) {
//                 el.removeEvent('click', boundEvent);
//             }
//
//             boundEvent = clickEvent.bind(el);
//
//             el.addEvent('click', boundEvent);
//             el.store('boundEvent', boundEvent);
//
//             el.getElements('input[type="radio"]').addEvent('click', function(e){
//                e.stopPropagation();
//             });
//         });
//
//         function clickEvent(e) {
//             var input = this.getElement('input[type="checkbox"]');
//
//             if (!input || input.get('disabled')) {
//                 return;
//             }
//
//             // Radio buttons
//             if (input.type == 'radio') {
//                 if (!input.checked) {
//                     input.checked = 'checked';
//                 }
//
//                 return;
//             }
//
//             // Checkboxes
//             if (e.shift && start) {
//                 shiftToggle(input);
//             } else {
//                 input.checked = input.checked ? '' : 'checked';
//
//                 if (input.get('onclick') == 'Backend.toggleCheckboxes(this)') {
//                     Backend.toggleCheckboxes(input); // see #6399
//                 }
//             }
//
//             start = input;
//         };
//     });
// }