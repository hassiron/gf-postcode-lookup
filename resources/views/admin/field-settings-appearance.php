<li class="css_class_setting field_setting">
    <label for="field_button_text" class="section_label">
        <?= esc_html_e('Button Text', GF_POSTCODE_LOOKUP_DOMAIN); ?>
        <?php gform_tooltip('field_button_text'); ?>
    </label>

    <input id="field_button_text" type="text" size="30" placeholder="Find address"
        onkeyup="SetButtonTextSetting(jQuery(this).val());" onchange="SetButtonTextSetting(jQuery(this).val());">
</li>