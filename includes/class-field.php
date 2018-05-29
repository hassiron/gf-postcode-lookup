<?php
/**
 * Postcode_Lookup_Field
 * 
 * Create the new field instance based on GF_Field
 * 
 * @since 1.0.0
 * @package GF_Postcode_Lookup
 */
class Postcode_Lookup_Field extends GF_Field {
    public $type = 'postcode-lookup';

    /**
     * Get the field title
     * 
     * @return string
     */
    public function get_form_editor_field_title() {
        return esc_attr__('Postcode Look-up', GF_POSTCODE_LOOKUP_DOMAIN);
    }

    /**
     * Assign the field button to the advanced group
     * 
     * @return array
     */
    public function get_form_editor_button() {
        return [
            'group' => 'advanced_fields',
            'text' => $this->get_form_editor_field_title()
        ];
    }

    /**
     * Define the settings that should be available on the field in the editor
     * 
     * @return array
     */
    function get_form_editor_field_settings() {
        return [
            'label_setting',
			'description_setting',
			'rules_setting',
			'placeholder_setting',
			'input_class_setting',
			'css_class_setting',
			'size_setting',
			'admin_label_setting',
			'default_value_setting',
			'visibility_setting',
			'conditional_logic_field_setting',
        ];
    }

    /**
     * Allow conditional logic to be used with this field
     * 
     * @return bool
     */
    public function is_conditional_logic_supported() {
        return true;
    }

    public function validate($value, $form) {
        if ($this->isRequired) {
            $line1 = rgpost('input_' . $this->id . '_1');
            $postcode = rgpost('input_' . $this->id . '_postcode');

            if (empty($line1) || empty($postcode)) {
                $this->failed_validation = true;
                $this->validation_message = empty($this->errorMessage) ?
                    esc_html__('This field is required. Please provide the first line of your address and a postcode.') : $this->errorMessage;
            }
        }
    }

    public function get_value_submission($field_values, $get_from_post_global_var = true) {
        $value = parent::get_value_submission($field_values, $get_from_post_global_var);

        return $value;
    }

    /**
     * Create the markup for the field on the front-end
     * 
     * @param array $form
     * @param string|array $value
     * @param null|array $entry
     * 
     * @return string
     */
    public function get_field_input($form, $value = '', $entry = null) {
        $id = absint($this->id);
        $form_id = absint($form['id']);
        $is_entry_detail = $this->is_entry_detail();
        $is_form_editor = $this->is_form_editor();

        $line1 = '';
        $line2 = '';
        $city = '';
        $county = '';
        $postcode = '';

        if (is_array($value)) {
            $line1 = esc_attr(rgget($this->id . '.1', $value));
            $line2 = esc_attr(rgget($this->id . '.2', $value));
            $city = esc_attr(rgget($this->id . '.city', $value));
            $county = esc_attr(rgget($this->id . '.county', $value));
            $postcode = esc_attr(rgget($this->id . '.postcode', $value));
        }

        // inputs
        $line1_input = GFFormsModel::get_input($this, $this->id . '.1');
        $line2_input = GFFormsModel::get_input($this, $this->id . '.2');
        $city_input = GFFormsModel::get_input($this, $this->id . '.city');
        $county_input = GFFormsModel::get_input($this, $this->id . '.county');
        $postcode_input = GFFormsModel::get_input($this, $this->id . '.postcode');

        // create the ID specific to this field
        $field_id = $is_entry_detail || $is_form_editor || $form_id == 0 ? sprintf('input_%s', $id) : sprintf('input_%s_%s', $form_id, $id);

        // get the field options specific to this field
        $field_options = $this->get_field_options($field_id, $form);

        // get the value of the buttonText property for the current field
        $button_text = $this->buttonText;

        // create any variables used in the view to display the field
        $class_suffix = $is_entry_detail ? '_admin' : '';
        $class = $this->size . $class_suffix;
        $attributes = [
            'tabindex' => $this->get_tabindex(),
            'logic_event' => !$is_form_editor && !$is_entry_detail ? $this->get_conditional_logic_event('keyup') : '',
            'placeholder' => $this->get_field_placeholder_attribute(),
            'required' => $this->isRequired ? 'aria-required="true"' : '',
            'validated' => $this->failed_validation ? 'aria-invalid="true"' : 'aria-invalid="false"',
            'disabled' => $is_form_editor ? 'disabled="disabled"' : ''
        ];

        // create the field markup
        ob_start();

        require GF_POSTCODE_LOOKUP_PATH . 'resources/views/field.php';

        $input = ob_get_clean();

        return sprintf('<div class="ginput_container ginput_complex ginput_container_%s">%s</div>', $this->type, $input);
    }

    /**
     * Retrieve the field options when using the postcode lookup
     * 
     * @param string $field_id
     * @param array $form
     * 
     * @return array
     */
    public function get_field_options($field_id, $form) {
        return [
            'button_text' => rgblank($this->buttonText) ? 'Find address' : $this->buttonText
        ];
    }

    /**
     * Include scripts inline with the editor
     * 
     * @return string
     */
	public function get_form_editor_inline_script_on_page_render() {
		// set the default field label for the simple type field
        $script = sprintf( "function SetDefaultValues_postcode(field) {field.label = '%s';}", $this->get_form_editor_field_title() ) . PHP_EOL;
        
		// initialize the fields custom settings
		$script .= "jQuery(document).bind('gform_load_field_settings', function (event, field, form) {" .
		           "var buttonText = field.buttonText == undefined ? '' : field.buttonText;" .
		           "jQuery('#field_button_text').val(buttonText);" .
		           "});" . PHP_EOL;
		// saving the simple setting
		$script .= "function SetButtonTextSetting(value) {SetFieldProperty('buttonText', value);}" . PHP_EOL;
		return $script;
    }
    
    public function get_input_property($input_id, $property_name) {
        $input = GFFormsModel::get_input($this, $this->id . '.' . (string) $input_id);

        return rgar($input, $property_name);
    }

    public function get_value_export($entry, $input_id = '', $use_text = false, $is_csv = false) {
        if (empty($input_id)) {
            $input_id = $this->id;
        }

        if (absint($input_id) == $input_id) {
            $line1 = str_replace('  ', ' ', trim(rgar($entry, $input_id . '.1')));
            $line2 = str_replace('  ', ' ', trim(rgar($entry, $input_id . '.2')));
            $city = str_replace('  ', ' ', trim(rgar($entry, $input_id . '.city')));
            $county = str_replace('  ', ' ', trim(rgar($entry, $input_id . '.county')));
            $postcode = str_replace('  ', ' ', trim(rgar($entry, $input_id . '.postcode')));

            $address = $line1;
            $address .= !empty($address) && !empty($line2) ? sprintf(' %s', $line2) : '';
            $address .= !empty($address) && !empty($city) ? sprintf(' %s', $city) : '';
            $address .= !empty($address) && !empty($county) ? sprintf(' %s', $county) : '';
            $address .= !empty($address) && !empty($postcode) ? sprintf(' %s', $postcode) : '';

            return $address;
        } else {
            return rgar($entry, $input_id);
        }
    }

    public function get_value_entry_detail($value, $currency = '', $use_text = false, $format = 'html', $media = 'screen') {
        var_dump($value);

        if (is_array($value)) {
            $line1 = trim(rgget($this->id . '.1', $value));
            $line2 = trim(rgget($this->id . '.2', $value));
            $city = trim(rgget($this->id . '.city', $value));
            $county = trim(rgget($this->id . '.county', $value));
            $postcode = trim(rgget($this->id . '.postcode', $value));

            if ($format === 'html') {
                $line1 = esc_html($line1);
                $line2 = esc_html($line2);
                $city = esc_html($city);
                $county = esc_html($county);
                $postcode = esc_html($postcode);

                $line_break = '<br />';
            } else {
                $line_break = "\n";
            }

            $address = $line1;
            $address .= !empty($address) && !empty($line2) ? $line_break . $line2 : '';
            $address .= !empty($address) && !empty($city) ? $line_break . $city : '';
            $address .= !empty($address) && !empty($county) ? $line_break . $county : '';
            $address .= !empty($address) && !empty($postcode) ? $line_break . $postcode : '';

            // @TODO implement toggling map link
            if (!empty($address) && $format == 'html') {
                $address_qs = str_replace($line_break, ' ', $address);
                $address_qs = urlencode($address_qs);
                $address .= sprintf('<br><a href="https://maps.google.com/maps?q=%s" target="_blank">View on Google Maps</a>', $address_qs);
            }

            return $address;
        } else {
            return '';
        }
    }
}
