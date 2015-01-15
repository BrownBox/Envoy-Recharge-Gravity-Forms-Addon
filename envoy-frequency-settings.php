<?php
add_filter('gform_add_field_buttons', 'envoy_add_frequency_field');

function envoy_add_frequency_field($field_groups) {
    foreach ($field_groups as &$group) {
        if ($group["name"] == "advanced_fields") { // to add to the Advanced Fields
            //if( $group["name"] == "standard_fields" ){ // to add to the Standard Fields
            //if( $group["name"] == "post_fields" ){ // to add to the Standard Fields
            $group["fields"][] = array(
                    "class" => "button",
                    "value" => __("frequency", "gravityforms"),
                    "onclick" => "StartAddField('frequency');"
            );
            break;
        }
    }
    return $field_groups;
}

// Adds title to GF custom field
add_filter('gform_field_type_title', 'envoy_frequency_title');

function envoy_frequency_title($type) {
    if ($type == 'frequency')
        return __('frequency', 'gravityforms');
}

// Adds the input area to the external side
add_action("gform_field_input", "envoy_frequency_field_input", 10, 5);

function envoy_frequency_field_input($input, $field, $value, $lead_id, $form_id) {
    if ($field["type"] == "frequency") {
        $max_chars = "";
        if (!IS_ADMIN && !empty($field["maxLength"]) && is_numeric($field["maxLength"]))
            $max_chars = self::get_counter_script($form_id, $field_id, $field["maxLength"]);

        $input_name = $form_id . '_' . $field["id"];
        $tabindex = GFCommon::get_tabindex();
        $css = isset($field['cssClass']) ? $field['cssClass'] : "";
        //add a variable to disable a select field if admin  dashboard is opened
        if (IS_ADMIN)
            $readonly = 'readonly';
        else
            $readonly = '';
        return sprintf("<div class='ginput_container'><input type='text' $readonly name='input_%s' id='%s' class='select gform_frequency %s' value=''></div>{$max_chars}", $field["id"], 'frequency-' . $field['id'], $field["type"] . ' ' . esc_attr($css) . ' ' . $field['size'], esc_html($value));
    }

    return $input;
}

// Now we execute some javascript technicalitites for the field to load correctly
add_action("gform_editor_js", "envoy_frequecy_gform_editor_js");

function envoy_frequecy_gform_editor_js() {
?>
<script type='text/javascript'>

jQuery(document).ready(function($) {
    //Add all textarea settings to the "frequency" field plus custom "frequency_setting"
    // fieldSettings["frequency"] = fieldSettings["textarea"] + ", .frequency_setting"; // this will show all fields that Paragraph Text field shows plus my custom setting

    // from forms.js; can add custom "frequency_setting" as well
    fieldSettings["frequency"] = ".label_setting, .description_setting, .admin_label_setting, .size_setting, .default_value_textarea_setting, .error_message_setting, .css_class_setting, .visibility_setting, .frequency_setting"; //this will show all the fields of the Paragraph Text field minus a couple that I didn't want to appear.

    //binding to the load field settings event to initialize the checkbox
    $(document).bind("gform_load_field_settings", function(event, field, form){
        jQuery("#field_frequency").attr("checked", field["field_frequency"] == true);
        $("#field_frequency_value").val(field["frequency"]);
    });
});

</script>
<?php
}

// Add a custom setting to the frequency advanced field
add_action("gform_field_advanced_settings", "envoy_frequency_settings", 10, 2);

function envoy_frequency_settings($position, $form_id) {

    // Create settings on position 50 (right after Field Label)
    if ($position == 50) {
?>
        <li class="frequency_setting field_setting"><input type="checkbox" id="field_frequency" onclick="SetFieldProperty('field_frequency', this.checked);" /> <label for="field_frequency" class="inline">
        <?php _e("Disable Submit Button", "gravityforms"); ?>
        <?php gform_tooltip("form_field_frequency"); ?>
        </label></li>
<?php
    }
}

//Filter to add a new tooltip
add_filter('gform_tooltips', 'envoy_add_frequency_tooltips');

function envoy_add_frequency_tooltips($tooltips) {
    $tooltips["form_field_frequency"] = "<h6>Disable Submit Button</h6>Check the box if you would like to disable the submit button.";
    $tooltips["form_field_default_value"] = "<h6>Default Value</h6>Enter the Terms of Service here.";
    return $tooltips;
}

// Add a script to the display of the particular form only if frequency field is being used
add_action('gform_enqueue_scripts', 'envoy_frequency_gform_enqueue_scripts', 10, 2);

function envoy_frequency_gform_enqueue_scripts($form, $ajax) {
    // cycle through fields to see if frequency is being used
    foreach ($form['fields'] as $field) {
        if (($field['type'] == 'frequency') && (isset($field['field_frequency']))) {
            $url = plugins_url('gform_frequency.js', __FILE__);
            wp_enqueue_script("gform_frequency_script", $url, array(
                    "jquery"
            ), '1.0');
            break;
        }
    }
}

// Add a custom class to the field li
add_action("gform_field_css_class", "custom_frequency_class", 10, 3);

function custom_frequency_class($classes, $field, $form) {
    if ($field["type"] == "frequency") {
        $classes .= " gform_frequency";
    }

    return $classes;
}