<?php
/**
 * Plugin Name: WP WhatsApp Quote Form
 * Description: A simple drag-and-drop form builder that sends form submissions to WhatsApp instead of email.
 * Version: 4.1.0
 * Author: Zerocoded
 * Author URI: https://github.com/Ikiere
 * Text Domain: wp-whatsapp-quote-form
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if directly accessed
}

/**
 * Main plugin class.
 */
class WP_WhatsApp_Quote_Form {
    // Option names
    const OPTION_FORMS  = 'wpwqf_forms'; 
    const MENU_SLUG     = 'wpwqf-settings';

    // Define all allowed field types for validation
    const ALLOWED_FIELD_TYPES = ['text', 'email', 'number', 'textarea', 'select', 'radio', 'checkbox', 'date'];

    /**
     * Constructor - Hook into WordPress.
     */
    public function __construct() {
        register_activation_hook(__FILE__, [$this, 'activate']);

        // Admin hooks
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // Frontend hooks
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('init', [$this, 'register_dynamic_shortcodes']);
        
        // Add Copyright in Footer
        add_filter('admin_footer_text', [$this, 'add_admin_footer_text']);
    }

    /**
     * Plugin activation logic.
     */
    public function activate() {
        // Initialize the forms option as an empty array
        add_option(self::OPTION_FORMS, [], '', 'no');
    }

    /**
     * Registers a shortcode for every saved form.
     */
    public function register_dynamic_shortcodes() {
        $forms = get_option(self::OPTION_FORMS, []);
        
        foreach ($forms as $form_id => $form) {
            // Shortcode is wp_whatsapp_quote_form_ + sanitized_name or form ID
            $shortcode_tag = $this->get_shortcode_tag($form_id, $form['name']);
            add_shortcode($shortcode_tag, [$this, 'render_form_wrapper']);
        }
    }

    /**
     * Helper to get a unique, clean shortcode tag based on the form name/ID.
     * @param string $form_id
     * @param string $form_name
     * @return string
     */
    private function get_shortcode_tag($form_id, $form_name) {
        $clean_name = sanitize_title($form_name);
        $suffix = !empty($clean_name) ? $clean_name : $form_id;
        return 'wp_whatsapp_quote_form_' . $suffix;
    }

    /**
     * Shortcode wrapper to pass the form ID to the render function.
     */
    public function render_form_wrapper($atts, $content, $tag) {
        $slug = str_replace('wp_whatsapp_quote_form_', '', $tag);
        return $this->render_form(['slug' => $slug]);
    }
    
    // --- Admin Functions ---

    public function register_menu() {
        add_menu_page(
            __('WhatsApp Form Builder', 'wp-whatsapp-quote-form'),
            __('WhatsApp Forms', 'wp-whatsapp-quote-form'),
            'manage_options',
            self::MENU_SLUG,
            [$this, 'forms_list_page'],
            'dashicons-format-chat',
            80
        );
        
        add_submenu_page(
            null, 
            __('Edit WhatsApp Form', 'wp-whatsapp-quote-form'),
            __('Edit Form', 'wp-whatsapp-quote-form'),
            'manage_options',
            self::MENU_SLUG . '-edit',
            [$this, 'settings_page']
        );
    }
    
    public function add_admin_footer_text($text) {
        $screen = get_current_screen();
        if (strpos($screen->id, self::MENU_SLUG) !== false) {
            return 'Thank you for using WP WhatsApp Quote Form. | Copyright &copy; Zerocoded.inc';
        }
        return $text;
    }


    /**
     * Enqueue admin-specific styles and scripts.
     */
    public function enqueue_admin_assets($hook) {
        $is_plugin_page = (strpos($hook, self::MENU_SLUG) !== false);
        
        if (!$is_plugin_page) {
            return;
        }

        wp_enqueue_script('jquery-ui-sortable');
        $version = '4.1.0'; 

        wp_enqueue_style('wpwqf-admin', plugin_dir_url(__FILE__) . 'css/admin.css', [], $version);
        wp_enqueue_script('wpwqf-admin', plugin_dir_url(__FILE__) . 'js/admin.js', ['jquery', 'jquery-ui-sortable'], $version, true);
    }
    
    /**
     * Main Form List/Dashboard Page
     */
    public function forms_list_page() {
        $action = isset($_GET['action']) ? sanitize_key($_GET['action']) : '';
        $form_id = isset($_GET['form_id']) ? sanitize_text_field($_GET['form_id']) : '';

        if ($action === 'delete' && !empty($form_id)) {
            $this->handle_form_delete($form_id);
        }

        $forms = get_option(self::OPTION_FORMS, []);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('WhatsApp Forms', 'wp-whatsapp-quote-form'); ?> 💬</h1>
            
            <a href="<?php echo esc_url(admin_url('admin.php?page=' . self::MENU_SLUG . '-edit')); ?>" class="page-title-action">
                <?php esc_html_e('Add New Form', 'wp-whatsapp-quote-form'); ?>
            </a>
            
            <?php settings_errors('wpwqf_messages'); ?>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column"><?php esc_html_e('Form Name', 'wp-whatsapp-quote-form'); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e('WhatsApp Number', 'wp-whatsapp-quote-form'); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e('Shortcode', 'wp-whatsapp-quote-form'); ?></th>
                        <th scope="col" class="manage-column" style="width: 120px;"><?php esc_html_e('Actions', 'wp-whatsapp-quote-form'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($forms)): ?>
                        <tr>
                            <td colspan="4"><?php esc_html_e('No forms found. Click "Add New Form" to begin!', 'wp-whatsapp-quote-form'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($forms as $form_id => $form): 
                            $edit_url = admin_url('admin.php?page=' . self::MENU_SLUG . '-edit&form_id=' . urlencode($form_id));
                            $delete_url = wp_nonce_url(admin_url('admin.php?page=' . self::MENU_SLUG . '&action=delete&form_id=' . urlencode($form_id)), 'wpwqf_delete_form_' . $form_id);
                            $shortcode_tag = $this->get_shortcode_tag($form_id, $form['name']);
                        ?>
                        <tr>
                            <td data-colname="<?php esc_attr_e('Form Name', 'wp-whatsapp-quote-form'); ?>">
                                <strong><?php echo esc_html($form['name']); ?></strong>
                                <div class="row-actions">
                                    <span class="edit"><a href="<?php echo esc_url($edit_url); ?>"><?php esc_html_e('Edit', 'wp-whatsapp-quote-form'); ?></a> | </span>
                                    <span class="delete"><a href="<?php echo esc_url($delete_url); ?>" class="submitdelete" onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this form?', 'wp-whatsapp-quote-form'); ?>');"><?php esc_html_e('Delete', 'wp-whatsapp-quote-form'); ?></a></span>
                                </div>
                            </td>
                            <td data-colname="<?php esc_attr_e('WhatsApp Number', 'wp-whatsapp-quote-form'); ?>"><?php echo esc_html($form['phone'] ?? 'N/A'); ?></td>
                            <td data-colname="<?php esc_attr_e('Shortcode', 'wp-whatsapp-quote-form'); ?>">
                                <code style="padding: 2px 4px; background: #eee; border-radius: 3px;" onclick="navigator.clipboard.writeText(this.innerText.trim()); alert('Shortcode Copied!');">
                                    [<?php echo esc_html($shortcode_tag); ?>]
                                </code>
                            </td>
                            <td data-colname="<?php esc_attr_e('Actions', 'wp-whatsapp-quote-form'); ?>">
                                <a href="<?php echo esc_url($edit_url); ?>" class="button button-primary button-small"><?php esc_html_e('Edit', 'wp-whatsapp-quote-form'); ?></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Delete Form Handler
     */
    private function handle_form_delete($form_id) {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'wpwqf_delete_form_' . $form_id)) {
            wp_die(__('Security check failed.', 'wp-whatsapp-quote-form'));
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $forms = get_option(self::OPTION_FORMS, []);

        if (isset($forms[$form_id])) {
            unset($forms[$form_id]);
            update_option(self::OPTION_FORMS, $forms);
            add_settings_error('wpwqf_messages', 'wpwqf_deleted', __('Form deleted successfully!', 'wp-whatsapp-quote-form'), 'updated');
        } else {
            add_settings_error('wpwqf_messages', 'wpwqf_not_found', __('Error: Form not found.', 'wp-whatsapp-quote-form'), 'error');
        }
    }


    /**
     * Form Builder/Edit Page
     */
    public function settings_page() {
        $form_id = isset($_GET['form_id']) ? sanitize_text_field($_GET['form_id']) : null;
        
        if (isset($_POST['wpwqf_save'])) {
            $form_id = $this->handle_settings_save($form_id);
        }

        $forms = get_option(self::OPTION_FORMS, []);
        $current_form = $form_id && isset($forms[$form_id]) ? $forms[$form_id] : null;

        $form_name = $current_form['name'] ?? 'New WhatsApp Form';
        $phone     = $current_form['phone'] ?? '';
        $fields    = $current_form['fields'] ?? [];
        $form_id   = $current_form['id'] ?? 'new_form';
        
        // --- ADMIN JAVASCRIPT TEMPLATE ---
        // This is the core template for the draggable/editable field item.
        // It must be updated to include the new fields.
        $field_template = '
            <li class="wpwqf-item ui-state-default" data-index="__INDEX__" data-type="__TYPE__">
                <span class="dashicons dashicons-move"></span>
                <select name="type" class="wpwqf-field-type">
                    ' . implode('', array_map(function($type) {
                        return '<option value="' . esc_attr($type) . '">' . esc_html(ucfirst($type)) . '</option>';
                    }, self::ALLOWED_FIELD_TYPES)) . '
                </select>
                <input type="text" name="label" class="label" placeholder="' . esc_attr__('Field Label (e.g., Your Name)', 'wp-whatsapp-quote-form') . '" value="__LABEL__" required>
                <button type="button" class="remove">X</button>
                
                <div class="wpwqf-advanced-settings">
                    <input type="text" name="name" class="name" placeholder="' . esc_attr__('Field Name (e.g., your_name)', 'wp-whatsapp-quote-form') . '" value="__NAME__" readonly>
                    <input type="text" name="placeholder" class="placeholder" placeholder="' . esc_attr__('Placeholder Text (Optional)', 'wp-whatsapp-quote-form') . '" value="__PLACEHOLDER__">
                    
                    <label>
                        <input type="checkbox" name="required" class="required-toggle" value="1" __REQUIRED_CHECKED__> 
                        ' . esc_html__('Required', 'wp-whatsapp-quote-form') . '
                    </label>

                    <div class="wpwqf-description-group">
                        <textarea name="description" class="description" placeholder="' . esc_attr__('Optional Description/Hint for the user...', 'wp-whatsapp-quote-form') . '">__DESCRIPTION__</textarea>
                    </div>

                    <div class="wpwqf-options-group" style="display:none;">
                        <textarea name="options" placeholder="' . esc_attr__('Enter options, one per line (or pipe-separated)', 'wp-whatsapp-quote-form') . '">__OPTIONS__</textarea>
                    </div>

                    <div class="wpwqf-styling-group">
                        <input type="text" name="width" class="width" placeholder="' . esc_attr__('Width (e.g., 50%, 200px)', 'wp-whatsapp-quote-form') . '" value="__WIDTH__">
                        <input type="text" name="border" class="border" placeholder="' . esc_attr__('Border (e.g., 1px solid #ccc)', 'wp-whatsapp-quote-form') . '" value="__BORDER__">
                    </div>
                </div>
            </li>
        ';
        // --- END JAVASCRIPT TEMPLATE ---

        // Localize script with current form data and the new template
        wp_localize_script('wpwqf-admin', 'wpwqf_admin_vars', [
            'initial_fields' => $fields,
            'nonce'          => wp_create_nonce('wpwqf_save_settings'),
            'field_types'    => self::ALLOWED_FIELD_TYPES, 
            'field_template' => $field_template, // Pass the template to JS
        ]);

        ?>
        <div class="wrap">
            <h1><?php echo empty($form_id) ? esc_html__('Add New WhatsApp Form', 'wp-whatsapp-quote-form') : sprintf(esc_html__('Edit Form: %s', 'wp-whatsapp-quote-form'), esc_html($form_name)); ?></h1>
            
            <a href="<?php echo esc_url(admin_url('admin.php?page=' . self::MENU_SLUG)); ?>" class="button button-secondary">
                &larr; <?php esc_html_e('Back to Forms List', 'wp-whatsapp-quote-form'); ?>
            </a>
            
            <?php settings_errors('wpwqf_messages'); ?>

            <form method="post">
                <?php wp_nonce_field('wpwqf_save_settings', 'wpwqf_settings_nonce'); ?>
                <input type="hidden" name="form_id" value="<?php echo esc_attr($form_id); ?>" />
                
                <h2><?php esc_html_e('Form Settings', 'wp-whatsapp-quote-form'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="wpwqf_form_name"><?php esc_html_e('Form Name', 'wp-whatsapp-quote-form'); ?> *</label></th>
                        <td>
                            <input type="text" id="wpwqf_form_name" name="wpwqf_form_name" value="<?php echo esc_attr($form_name); ?>" class="regular-text" required />
                            <p class="description"><?php esc_html_e('This name is used for the admin list and the shortcode suffix.', 'wp-whatsapp-quote-form'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wpwqf_phone"><?php esc_html_e('WhatsApp Phone Number', 'wp-whatsapp-quote-form'); ?> *</label></th>
                        <td>
                            <input type="text" id="wpwqf_phone" name="wpwqf_phone" value="<?php echo esc_attr($phone); ?>" class="regular-text" placeholder="+1234567890" required />
                            <p class="description"><?php esc_html_e('Enter the phone number with country code (e.g., +1234567890).', 'wp-whatsapp-quote-form'); ?></p>
                        </td>
                    </tr>
                    <?php if ($form_id !== 'new_form'): ?>
                    <tr>
                        <th scope="row"><?php esc_html_e('Shortcode', 'wp-whatsapp-quote-form'); ?></th>
                        <td>
                            <?php $shortcode_tag = $this->get_shortcode_tag($form_id, $form_name); ?>
                            <code style="padding: 2px 4px; background: #eee; border-radius: 3px;" onclick="navigator.clipboard.writeText(this.innerText.trim()); alert('Shortcode Copied!');">
                                [<?php echo esc_html($shortcode_tag); ?>]
                            </code>
                            <p class="description"><?php esc_html_e('Use this shortcode to display the form on any post or page.', 'wp-whatsapp-quote-form'); ?></p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>

                <h2><?php esc_html_e('Form Fields (Drag to reorder)', 'wp-whatsapp-quote-form'); ?></h2>
                <div id="wpwqf-field-builder-container">
                    <ul id="wpwqf-builder">
                        </ul>
                    <input type="hidden" id="wpwqf_fields_input" name="wpwqf_fields" value="<?php echo esc_attr(json_encode($fields)); ?>" />
                </div>
                
                <button id="wpwqf-add-field" class="button button-secondary" type="button">+ <?php esc_html_e('Add Field', 'wp-whatsapp-quote-form'); ?></button>
                <br><br>
                <?php submit_button(__('Save Form', 'wp-whatsapp-quote-form'), 'primary', 'wpwqf_save'); ?>
            </form>
            
            <?php $this->inline_admin_styles(); ?>
        </div>
        <?php
    }

    /**
     * Handles the saving of plugin settings (a single form).
     */
    private function handle_settings_save($old_form_id) {
        if (!isset($_POST['wpwqf_settings_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['wpwqf_settings_nonce'])), 'wpwqf_save_settings')) {
            wp_die(__('Security check failed.', 'wp-whatsapp-quote-form'));
        }

        if (!current_user_can('manage_options')) {
            return $old_form_id;
        }

        $form_name  = isset($_POST['wpwqf_form_name']) ? sanitize_text_field(wp_unslash($_POST['wpwqf_form_name'])) : '';
        $phone      = isset($_POST['wpwqf_phone']) ? sanitize_text_field(wp_unslash($_POST['wpwqf_phone'])) : '';

        if (empty($form_name) || empty($phone)) {
            add_settings_error('wpwqf_messages', 'wpwqf_empty_fields', __('Error: Form Name and WhatsApp Number are required.', 'wp-whatsapp-quote-form'), 'error');
            return $old_form_id;
        }

        $new_form_id = $old_form_id && $old_form_id !== 'new_form' ? $old_form_id : uniqid('form_');
        $forms = get_option(self::OPTION_FORMS, []);
        $fields = $this->sanitize_form_fields_data();

        $forms[$new_form_id] = [
            'id'    => $new_form_id,
            'name'  => $form_name,
            'phone' => $phone,
            'fields' => $fields,
            'updated' => time(),
        ];

        update_option(self::OPTION_FORMS, $forms);
        add_settings_error('wpwqf_messages', 'wpwqf_saved', __('Form saved successfully!', 'wp-whatsapp-quote-form'), 'updated');

        if ($old_form_id === 'new_form' || empty($old_form_id)) {
            $redirect_url = admin_url('admin.php?page=' . self::MENU_SLUG . '-edit&form_id=' . urlencode($new_form_id) . '&settings-updated=true');
            wp_redirect($redirect_url);
            exit;
        }

        return $new_form_id;
    }
    
    /**
     * Sanitizes the fields array from $_POST data, including new 'required' and 'description' keys.
     */
    private function sanitize_form_fields_data() {
        $fields = [];
        if (!isset($_POST['wpwqf_fields'])) {
            return $fields;
        }
        
        $fields_json = wp_unslash($_POST['wpwqf_fields']);
        $decoded_fields = json_decode($fields_json, true);

        if (!is_array($decoded_fields)) {
            return $fields;
        }

        foreach ($decoded_fields as $field) {
            $type = isset($field['type']) && in_array($field['type'], self::ALLOWED_FIELD_TYPES) ? sanitize_key($field['type']) : 'text';
            
            $sanitized_field = [
                'label'         => isset($field['label']) ? sanitize_text_field($field['label']) : '',
                'type'          => $type,
                'placeholder'   => isset($field['placeholder']) ? sanitize_text_field($field['placeholder']) : '',
                'width'         => isset($field['width']) ? sanitize_text_field($field['width']) : '100%',
                'border'        => isset($field['border']) ? sanitize_text_field($field['border']) : '1px solid #ccc',
                'name'          => isset($field['name']) ? sanitize_title_with_dashes($field['name']) : 'field_' . time(),
                // NEW: Sanitize 'required' (boolean/int)
                'required'      => isset($field['required']) ? (bool) $field['required'] : false,
                // NEW: Sanitize 'description' (textarea/string)
                'description'   => isset($field['description']) ? sanitize_textarea_field($field['description']) : '',
            ];

            // Sanitize options for select/radio/checkbox
            if (in_array($type, ['select', 'radio', 'checkbox'])) {
                $options_raw = isset($field['options']) ? $field['options'] : '';
                $sanitized_field['options'] = sanitize_textarea_field($options_raw);
            }

            $fields[] = $sanitized_field;
        }
        return $fields;
    }

    private function inline_admin_styles() {
        ?>
        <style>
            #wpwqf-builder { list-style:none; margin:0; padding:0; }
            .wpwqf-item { 
                padding:10px; 
                background:#f9f9f9; 
                border:1px solid #ddd; 
                margin-bottom:5px; 
                cursor:grab; 
                display:flex; 
                flex-wrap: wrap;
                align-items: center;
                border-radius: 3px;
                position: relative;
                padding-bottom: 5px;
            }
            .wpwqf-item:hover { background:#fff; border-color: #ccc; }
            .wpwqf-item input, .wpwqf-item select, .wpwqf-item textarea { 
                padding: 5px 8px; 
                border: 1px solid #ddd; 
                border-radius: 3px; 
            }
            .wpwqf-item > span.dashicons-move { margin-right: 5px; cursor: grab; }
            .wpwqf-item input.label { flex-grow: 1; }
            .wpwqf-item button.remove { 
                background:#dc3232; 
                color:#fff; 
                border:none; 
                padding:5px 10px; 
                cursor:pointer; 
                line-height: 1;
                border-radius: 3px;
                font-weight: bold;
                margin-left: auto;
            }
            .wpwqf-advanced-settings {
                width: 100%;
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
                padding-top: 5px;
                border-top: 1px solid #eee;
                margin-top: 5px;
            }
            .wpwqf-advanced-settings input, .wpwqf-advanced-settings select {
                flex-basis: auto;
                min-width: 100px;
            }
            .wpwqf-advanced-settings .name { background: #eee; cursor: not-allowed; }
            .wpwqf-options-group, .wpwqf-description-group { 
                width: 100%;
                margin-top: 5px;
                order: 10; /* Push to the end of the flex row */
            }
            .wpwqf-options-group textarea, .wpwqf-description-group textarea {
                width: 100%;
                min-height: 40px;
                font-size: 11px;
            }
        </style>
        <?php
    }

    // --- Frontend Functions ---

    public function enqueue_frontend_assets() {
        $version = '4.1.0';
        wp_enqueue_style('wpwqf-frontend', plugin_dir_url(__FILE__) . 'css/frontend.css', [], $version);
        wp_enqueue_script('wpwqf-frontend', plugin_dir_url(__FILE__) . 'js/frontend.js', ['jquery'], $version, true);
    }

    /**
     * Shortcode callback to render the form.
     */
    public function render_form($atts) {
        $slug = $atts['slug'] ?? null;
        if (!$slug) {
            return '';
        }

        $forms = get_option(self::OPTION_FORMS, []);
        $target_form = null;

        // Find the form using the slug derived from the shortcode
        foreach ($forms as $form_id => $form) {
            if ($this->get_shortcode_tag($form_id, $form['name']) === 'wp_whatsapp_quote_form_' . $slug) {
                $target_form = $form;
                break;
            }
        }
        
        if (!$target_form) {
            return is_user_logged_in() && current_user_can('manage_options') 
                ? '<p><strong>' . sprintf(esc_html__('WhatsApp Form Error: Form with slug "%s" not found. Check your forms list.', 'wp-whatsapp-quote-form'), esc_html($slug)) . '</strong></p>' 
                : '';
        }

        $fields = $target_form['fields'] ?? [];
        $phone  = $target_form['phone'] ?? '';
        
        ob_start();
        ?>
        <form id="wpwqf-form-<?php echo esc_attr($slug); ?>" class="wpwqf-form-container" data-whatsapp-phone="<?php echo esc_attr($phone); ?>">
            <input type="hidden" name="form_name" value="<?php echo esc_attr($target_form['name']); ?>" />
            <?php foreach ($fields as $field): 
                // Ensure required keys exist before outputting
                $label       = $field['label'] ?? '';
                $type        = $field['type'] ?? 'text';
                $name        = $field['name'] ?? sanitize_title_with_dashes($label);
                $placeholder = $field['placeholder'] ?? '';
                $width       = $field['width'] ?? '100%';
                $border      = $field['border'] ?? '1px solid #ccc';
                $required    = $field['required'] ?? false; // NEW
                $description = $field['description'] ?? ''; // NEW
                $options_str = isset($field['options']) ? explode('|', $field['options']) : [];
                $options     = array_map('trim', $options_str);
                
                // Construct the required attribute string
                $required_attr = $required ? ' required' : '';
                // Add an asterisk to the label if required
                $display_label = $label . ($required ? ' <span class="wpwqf-required-asterisk">*</span>' : '');
            ?>
                <div class="wpwqf-field wpwqf-field-<?php echo esc_attr($type); ?>">
                    <label for="<?php echo esc_attr($name); ?>"><?php echo $display_label; ?></label>
                    
                    <?php if (!empty($description)): // NEW: Display Description ?>
                        <p class="wpwqf-description"><?php echo esc_html($description); ?></p>
                    <?php endif; ?>

                    <?php if ($type === 'textarea'): ?>
                        <textarea 
                            id="<?php echo esc_attr($name); ?>"
                            name="<?php echo esc_attr($name); ?>" 
                            placeholder="<?php echo esc_attr($placeholder); ?>" 
                            style="width:<?php echo esc_attr($width); ?>;border:<?php echo esc_attr($border); ?>;"
                            <?php echo $required_attr; // NEW: Add required attribute ?>></textarea>

                    <?php elseif ($type === 'select'): ?>
                        <select 
                            id="<?php echo esc_attr($name); ?>"
                            name="<?php echo esc_attr($name); ?>" 
                            style="width:<?php echo esc_attr($width); ?>;border:<?php echo esc_attr($border); ?>;"
                            <?php echo $required_attr; // NEW: Add required attribute ?>>
                            <?php if (!empty($placeholder)): ?>
                                <option value="" disabled selected><?php echo esc_html($placeholder); ?></option>
                            <?php endif; ?>
                            <?php foreach ($options as $option): 
                                $opt_value = esc_attr(sanitize_title($option));
                                $opt_label = esc_html($option);
                            ?>
                                <option value="<?php echo $opt_value; ?>"><?php echo $opt_label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    
                    <?php elseif ($type === 'radio'): ?>
                        <div class="wpwqf-options-group" style="width:<?php echo esc_attr($width); ?>">
                            <?php foreach ($options as $option): 
                                $opt_value = esc_attr(sanitize_title($option));
                                $opt_label = esc_html($option);
                            ?>
                                <label>
                                    <input type="radio" name="<?php echo esc_attr($name); ?>" value="<?php echo $opt_value; ?>" <?php echo $required_attr; ?>> 
                                    <?php echo $opt_label; ?>
                                </label>
                            <?php endforeach; ?>
                        </div>

                    <?php elseif ($type === 'checkbox'): ?>
                        <div class="wpwqf-options-group" style="width:<?php echo esc_attr($width); ?>">
                            <?php foreach ($options as $option): 
                                $opt_value = esc_attr(sanitize_title($option));
                                $opt_label = esc_html($option);
                            ?>
                                <label>
                                    <input type="checkbox" name="<?php echo esc_attr($name); ?>[]" value="<?php echo $opt_value; ?>" <?php echo $required_attr; ?>> 
                                    <?php echo $opt_label; ?>
                                </label>
                            <?php endforeach; ?>
                        </div>

                    <?php else: // text, email, number, date ?>
                        <input 
                            type="<?php echo esc_attr($type); ?>" 
                            id="<?php echo esc_attr($name); ?>"
                            name="<?php echo esc_attr($name); ?>" 
                            placeholder="<?php echo esc_attr($placeholder); ?>" 
                            style="width:<?php echo esc_attr($width); ?>;border:<?php echo esc_attr($border); ?>;"
                            <?php echo $required_attr; // NEW: Add required attribute ?> />
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <button type="submit" class="wpwqf-submit-button"><?php esc_html_e('Send via WhatsApp', 'wp-whatsapp-quote-form'); ?></button>
            <p id="wpwqf-message-<?php echo esc_attr($slug); ?>" class="wpwqf-message-area" style="display:none;"></p>
        </form>
        <?php
        return ob_get_clean();
    }
}

// Instantiate the main class
new WP_WhatsApp_Quote_Form();