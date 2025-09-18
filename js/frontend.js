/* Revised Conceptual content for js/frontend.js (Version 4.0.1) */
jQuery(document).ready(function($){
    // Use a class selector to attach the handler to ALL forms
    $(".wpwqf-form-container").submit(function(e){
        e.preventDefault();
        const $form = $(this);
        
        // Retrieve the form-specific phone number from the data attribute
        const phone = $form.data('whatsapp-phone');
        const formName = $form.find('input[name="form_name"]').val() || "Quote Request";
        const messageAreaId = '#' + $form.find('.wpwqf-message-area').attr('id'); // Target the specific message area

        if (!phone) {
             console.error('WhatsApp phone number not configured for this form.');
             $(messageAreaId).text('Error: WhatsApp number not set.').css('color', 'red').show().delay(5000).fadeOut();
             return;
        }
        
        // Use a map to handle fields with the same name (like checkboxes/radio)
        const fieldDataMap = {};
        
        // 1. Serialize and Group Data
        $form.serializeArray().forEach(function(f) {
            // Skip hidden fields used for form identification
            if (f.name === 'form_name' || f.name.startsWith('_')) {
                return;
            }

            // Check if the field name already exists (e.g., for checkboxes or multi-select)
            if (fieldDataMap[f.name]) {
                // If it exists, convert it to an array if it's not one, then push the new value
                if (Array.isArray(fieldDataMap[f.name])) {
                    fieldDataMap[f.name].push(f.value);
                } else {
                    fieldDataMap[f.name] = [fieldDataMap[f.name], f.value];
                }
            } else {
                fieldDataMap[f.name] = f.value;
            }
        });

        // 2. Build the URL-encoded Message String
        let message = `*${formName} Request*:%0A%0A`; // Start with a bold, encoded form name

        for (const name in fieldDataMap) {
            let value = fieldDataMap[name];
            
            // Format the field name nicely
            let cleanName = name.replace(/_/g, ' ').trim();
            // Handle Checkbox/Multi-select arrays
            if (Array.isArray(value)) {
                value = value.join(', '); // Join multiple selections with a comma and space
            }
            
            // Format: Field Name: Value
            // %0A is URL-encoded newline
            message += `*${cleanName}:* ${encodeURIComponent(value)}%0A`;
        }
        
        // 3. Construct the WhatsApp URL
        // We ensure the phone number is cleaned (WhatsApp prefers no special chars in the number)
        const cleanPhone = phone.replace(/[^0-9+]/g, '');
        const whatsapp_url = `https://wa.me/${cleanPhone}?text=${message}`;

        // 4. Send the user to the WhatsApp chat link
        window.open(whatsapp_url, "_blank");
        
        // Show confirmation message and reset the form
        $(messageAreaId).text('Success! Opening WhatsApp chat...').css('color', 'green').show();
        $form.trigger('reset'); // Clear the form fields
        $(messageAreaId).delay(4000).fadeOut(); // Hide the message after a delay
    });
});