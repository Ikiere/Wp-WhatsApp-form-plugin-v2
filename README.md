# Wp-WhatsApp-form-plugin-v2
A lightweight WordPress plugin that lets you create fully customizable forms and send the responses directly to WhatsApp instead of email.
Contributors: Zerocoded
Donate link: 
Tags: whatsapp, form, quote, contact form, drag and drop, form builder
Requires at least: 5.0
Tested up to: 6.6
Stable tag: 4.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A powerful and simple drag-and-drop form builder that sends form submissions directly to a WhatsApp number, bypassing email configuration entirely. Features dynamic fields, required/optional settings, and custom styling.

== Description ==

The **WP WhatsApp Quote Form** plugin provides a highly efficient and modern way for users to contact you directly via WhatsApp from your WordPress site.

Instead of dealing with complicated email server setups or spam filters, form submissions are immediately converted into a structured WhatsApp message and opened in the user's WhatsApp application (or web client).

### Key Features

* **Drag-and-Drop Builder:** Easily create and reorder your form fields using a simple, intuitive interface in the WordPress admin area.
* **Direct WhatsApp Submission:** Form data is securely passed to a unique WhatsApp link (`wa.me/`) for instant communication.
* **Multiple Form Support:** Create, manage, and deploy multiple unique forms on different pages, each targeting a different WhatsApp number or team member.
* **Custom Field Types:** Includes standard fields: Text, Email, Number, Textarea, Select (Dropdown), Radio, Checkbox, and Date.
* **Advanced Field Control (NEW in 4.1.0):**
    * **Required/Optional Setting:** Mark any input field as mandatory using the native HTML5 `required` attribute.
    * **Optional Description/Hint:** Add helpful contextual text below the label for each input field.
* **Custom Styling:** Control the width and border style of individual input fields directly from the builder.
* **Dynamic Shortcodes:** Each form generates a unique shortcode based on its name for easy deployment.

== Installation ==

### 1. Upload via WordPress Dashboard

1.  Navigate to **Plugins** -> **Add New** in your WordPress dashboard.
2.  Click **Upload Plugin**.
3.  Select the zipped `wp-whatsapp-quote-form.zip` file and click **Install Now**.
4.  Once installed, click **Activate Plugin**.

### 2. Manual Upload (FTP)

1.  Unzip the plugin file.
2.  Upload the `wp-whatsapp-quote-form` folder to the `/wp-content/plugins/` directory.
3.  Navigate to **Plugins** in your WordPress dashboard.
4.  Find "WP WhatsApp Quote Form" and click **Activate**.

== Configuration ==

1.  Go to the new menu item **WhatsApp Forms** in your WordPress admin dashboard.
2.  Click **Add New Form**.
3.  **Form Settings:**
    * **Form Name:** (e.g., "Main Contact Form"). This generates the shortcode suffix.
    * **WhatsApp Phone Number:** Enter the destination number with the country code (e.g., `+1234567890`).
4.  **Form Fields (Builder):**
    * Click **+ Add Field** to add a new input element.
    * **Label:** The visible text above the input (e.g., "Your Email Address").
    * **Type:** Select the input type (Text, Email, Select, etc.).
    * **Required (NEW):** Check this box to make the field mandatory before the user can submit the form.
    * **Description (NEW):** Add an optional hint or description that appears under the label.
    * For **Select, Radio, or Checkbox** types, use the options textarea to enter choices, one per line or separated by the pipe character (`|`).
    * Drag the fields to reorder them.
5.  Click **Save Form**.

== Usage ==

After creating and saving your form, a shortcode will be generated for you.

### Displaying the Form

Copy the unique shortcode provided on the form edit screen and paste it into any WordPress Post, Page, or Widget where you want the form to appear.

**Example Shortcode:**

If your form name is "Sales Quote", the shortcode will look like this:

`[wp_whatsapp_quote_form_sales-quote]`

### How the Submission Works

1.  The user fills out the form on your website.
2.  They click the **"Send via WhatsApp"** button.
3.  If all `required` fields are filled (and basic browser validation passes), the browser opens a new tab or window to the WhatsApp API.
4.  The pre-filled message contains the form name and a clear list of all the submitted field labels and values, ready for the user to click 'Send'.

== Screenshots ==

1.  Form List and Shortcodes
2.  Drag-and-Drop Field Builder
3.  Editing Field Properties (including new Required/Description options)
4.  Frontend Form Example

== Changelog ==

### 4.1.0 - [2025-09-18]

* **Feature:** Added optional **Description/Hint** text field to the field builder for providing contextual information to users.
* **Feature:** Added a **Required** checkbox to all relevant input types, utilizing native HTML5 constraint validation.
* **Enhancement:** Updated data sanitization to handle new field parameters.
* **Fix:** Ensured all necessary assets (`frontend.js` and `frontend.css`) are loaded correctly using `wp_enqueue_scripts` hook.

### 4.0.1 - [Date of previous release]

* Initial release with Drag-and-Drop builder and multi-form support.
* Implemented dynamic shortcode generation per form.
* Basic frontend rendering for all core input types.

== Frequently Asked Questions ==

### Q: Why is my form not submitting to WhatsApp?

A: This is usually a JavaScript issue.
1. **Check the phone number:** Ensure the WhatsApp number in the form settings includes the correct country code (e.g., `+1234567890`).
2. **Check the shortcode:** Make sure you are using the exact shortcode for the form you created.
3. **Console Errors:** Check your browser's console for JavaScript errors. This plugin relies on jQuery to prevent the default form submission and construct the WhatsApp link. Ensure your theme is not blocking script loading.

### Q: How can I change the pre-filled message content?

A: The message content is automatically generated using the **Label** and **Value** of every field. To change the message, you need to change the field labels in the form builder.

### Q: Does this plugin store any user data?

A: No. The plugin does not connect to any external service or database. The form data is sent *directly* from the user's browser to the WhatsApp API. No submission records are kept on your WordPress server.
