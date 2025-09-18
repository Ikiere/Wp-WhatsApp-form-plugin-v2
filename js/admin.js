jQuery(document).ready(function($) {

    const $builder = $('#wpwqf-builder');
    const $fieldsInput = $('#wpwqf_fields_input');
    const $addFieldButton = $('#wpwqf-add-field');
    
    // Global counter to ensure unique names for new fields
    let fieldCounter = 0;

    /**
     * Extracts data from a single field item in the builder.
     * * @param {jQuery} $item - The jQuery <li> element representing the field.
     * @returns {object} The field data object.
     */
    function getFieldData($item) {
        const $requiredCheckbox = $item.find('input[name="required"]');

        const data = {
            label: $item.find('input[name="label"]').val() || '',
            type: $item.find('.wpwqf-field-type').val(),
            placeholder: $item.find('input[name="placeholder"]').val() || '',
            width: $item.find('input[name="width"]').val() || '100%',
            border: $item.find('input[name="border"]').val() || '1px solid #ccc',
            name: $item.find('input[name="name"]').val(),
            options: $item.find('textarea[name="options"]').val() || '',
            // NEW: Capture Required and Description
            required: $requiredCheckbox.is(':checked') ? 1 : 0,
            description: $item.find('textarea[name="description"]').val() || '',
        };
        return data;
    }

    /**
     * Serializes all field data from the builder and updates the hidden input.
     */
    function updateFieldList() {
        const fields = [];
        $builder.find('.wpwqf-item').each(function(index) {
            const fieldData = getFieldData($(this));

            // Ensure name is clean, especially on label change
            if (!fieldData.name || fieldData.name.indexOf('new_field_') !== -1) {
                // If the name is default/new, generate one from the label
                fieldData.name = sanitizeTitle(fieldData.label) || ('field_' + index);
                $(this).find('input[name="name"]').val(fieldData.name);
            }
            fields.push(fieldData);
        });
        
        $fieldsInput.val(JSON.stringify(fields));
    }
    
    /**
     * Toggles visibility of the Options group based on field type.
     * @param {jQuery} $item - The jQuery <li> element.
     */
    function toggleOptionsGroup($item) {
        const type = $item.find('.wpwqf-field-type').val();
        const $optionsGroup = $item.find('.wpwqf-options-group');
        
        if (['select', 'radio', 'checkbox'].includes(type)) {
            $optionsGroup.show();
        } else {
            $optionsGroup.hide();
        }
    }

    /**
     * Sanitizes a string for use as a name attribute (slug/title).
     * @param {string} text - The input string.
     * @returns {string} The sanitized string.
     */
    function sanitizeTitle(text) {
        if (!text) return '';
        let slug = text.toLowerCase();
        slug = slug.replace(/[^\w\s-]/g, ''); // Remove non-alphanumeric/space/hyphen
        slug = slug.replace(/[\s_-]+/g, '-'); // Replace spaces/underscores/hyphens with a single hyphen
        slug = slug.replace(/^-+|-+$/g, ''); // Remove leading/trailing hyphens
        return slug;
    }


    /**
     * Creates a new field item using the PHP-generated template and existing data.
     * * @param {object} fieldData - The data for the field.
     * @returns {jQuery} The jQuery <li> element.
     */
    function createFieldItem(fieldData) {
        let template = wpwqf_admin_vars.field_template;
        const index = fieldCounter++; // Use the counter for unique temporary name creation

        // Default values for new/missing properties
        const requiredChecked = fieldData.required ? 'checked="checked"' : '';
        const description = fieldData.description || '';
        const name = fieldData.name || ('new_field_' + index);
        
        // Perform replacements
        template = template.replace(/__INDEX__/g, index);
        template = template.replace(/__TYPE__/g, fieldData.type);
        template = template.replace(/__LABEL__/g, fieldData.label);
        template = template.replace(/__NAME__/g, name);
        template = template.replace(/__PLACEHOLDER__/g, fieldData.placeholder);
        template = template.replace(/__WIDTH__/g, fieldData.width);
        template = template.replace(/__BORDER__/g, fieldData.border);
        template = template.replace(/__OPTIONS__/g, fieldData.options || '');
        // NEW REPLACEMENTS
        template = template.replace(/__REQUIRED_CHECKED__/g, requiredChecked);
        template = template.replace(/__DESCRIPTION__/g, description);

        const $item = $(template);

        // Set the selected option for type
        $item.find('.wpwqf-field-type').val(fieldData.type);

        // Initial check for visibility of options group
        toggleOptionsGroup($item);

        // Bind event handlers
        $item.find('.wpwqf-field-type').on('change', function() {
            toggleOptionsGroup($item);
            updateFieldList();
        });

        // Remove button handler
        $item.find('.remove').on('click', function() {
            if (confirm('Are you sure you want to remove this field?')) {
                $item.remove();
                updateFieldList();
            }
        });
        
        // Input change handlers (use delegated events for performance and robustness)
        $item.on('change input', 'input[name="label"]', function() {
            // Auto-update the field name slug based on label if it's a new field
            const $nameInput = $item.find('input[name="name"]');
            if ($nameInput.val().indexOf('new_field_') !== -1) {
                const newName = sanitizeTitle($(this).val()) || $nameInput.val();
                $nameInput.val(newName);
            }
            updateFieldList();
        });

        // Other generic input/textarea change handlers (including the new ones)
        $item.on('change input', 'input, textarea, select', function() {
            // Ignore the type change event as it's handled above
            if (!$(this).hasClass('wpwqf-field-type')) {
                updateFieldList();
            }
        });

        return $item;
    }
    
    // --- Initialization and Event Binding ---

    // 1. Initialize Sortable
    $builder.sortable({
        handle: '.dashicons-move',
        axis: 'y',
        update: updateFieldList // Update list order after drag/drop
    });

    // 2. Load Existing Fields
    if (wpwqf_admin_vars.initial_fields.length > 0) {
        wpwqf_admin_vars.initial_fields.forEach(function(field) {
            $builder.append(createFieldItem(field));
        });
    }

    // 3. Add Field Button Click Handler
    $addFieldButton.on('click', function() {
        const newField = {
            label: 'New Text Field',
            type: 'text',
            placeholder: 'Enter text here',
            width: '100%',
            border: '1px solid #ccc',
            name: '', // Name will be set by JS on first save/change
            options: '',
            required: false, // Default is optional
            description: '', // Default is empty
        };
        $builder.append(createFieldItem(newField));
        updateFieldList();
    });

    // 4. Initial update (for empty forms or validation)
    updateFieldList();

});