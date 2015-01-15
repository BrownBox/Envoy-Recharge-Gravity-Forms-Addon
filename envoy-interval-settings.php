<?php
add_filter('gform_add_field_buttons', 'envoy_add_interval_field');

function envoy_add_interval_field($field_groups) {
    foreach ($field_groups as &$group) {
        if ($group["name"] == "advanced_fields") { // to add to the Advanced Fields
            //if( $group["name"] == "standard_fields" ){ // to add to the Standard Fields
            //if( $group["name"] == "post_fields" ){ // to add to the Standard Fields
            $group["fields"][] = array(
                    "class" => "button",
                    "value" => __("Interval", "gravityforms"),
                    "onclick" => "StartAddField('interval');"
            );
            break;
        }
    }
    return $field_groups;
}

// Adds title to GF custom field
add_filter('gform_field_type_title', 'envoy_interval_title');

function envoy_interval_title($type) {
    if ($type == 'interval')
        return __('Interval', 'gravityforms');
}

// Adds the input area to the external side
add_action("gform_field_input", "envoy_interval_field_input", 10, 5);

function envoy_interval_field_input($input, $field, $value, $lead_id, $form_id) {
    if ($field["type"] == "interval") {
        $max_chars = "";
        if (!IS_ADMIN && !empty($field["maxLength"]) && is_numeric($field["maxLength"]))
            $max_chars = self::get_counter_script($form_id, $field_id, $field["maxLength"]);

        $input_name = $form_id . '_' . $field["id"];
        $tabindex = GFCommon::get_tabindex();
        $css = isset($field['cssClass']) ? $field['cssClass'] : "";
        //add a variable to disable a select field if admin  dashboard is opened
        if (IS_ADMIN)
            $disabled = 'disabled';
        else
            $disabled = '';
        return sprintf("<div class='ginput_container'><select $disabled name='input_%s' id='%s' class='select gform_interval %s' >
	<option value=''>Select interval</option>
	<option value='one-off'>one-off</option>
	<option value='day'>day</option>
	<option value='week'>week</option>
	<option value='month'>month</option>
	<option value='year'>year</option>
	</select></div>{$max_chars}", $field["id"], 'interval-' . $field['id'], $field["type"] . ' ' . esc_attr($css) . ' ' . $field['size'], esc_html($value));
    }

    return $input;
}

// Now we execute some javascript technicalitites for the field to load correctly
add_action("gform_editor_js", "envoy_interval_gform_editor_js");

function envoy_interval_gform_editor_js() {
    ?>
<script type='text/javascript'>

jQuery(document).ready(function($) {
    //Add all textarea settings to the "interval" field plus custom "interval_setting"
    // fieldSettings["interval"] = fieldSettings["textarea"] + ", .interval_setting"; // this will show all fields that Paragraph Text field shows plus my custom setting

    // from forms.js; can add custom "interval_setting" as well
    fieldSettings["interval"] = ".label_setting, .description_setting, .admin_label_setting, .size_setting, .default_value_textarea_setting, .error_message_setting, .css_class_setting, .visibility_setting, .interval_setting"; //this will show all the fields of the Paragraph Text field minus a couple that I didn't want to appear.

    //binding to the load field settings event to initialize the checkbox
    $(document).bind("gform_load_field_settings", function(event, field, form){
        jQuery("#field_interval").attr("checked", field["field_interval"] == true);
        $("#field_interval_value").val(field["interval"]);
    });
});

</script>
<?php
}

// Add a custom setting to the interval advanced field
add_action("gform_field_advanced_settings", "envoy_interval_settings", 10, 2);

function envoy_interval_settings($position, $form_id) {

    // Create settings on position 50 (right after Field Label)
    if ($position == 50) {
?>
        <li class="interval_setting field_setting"><input type="checkbox" id="field_interval" onclick="SetFieldProperty('field_interval', this.checked);" /> <label for="field_interval" class="inline">
        <?php _e("Disable Submit Button", "gravityforms"); ?>
        <?php gform_tooltip("form_field_interval"); ?>
        </label></li>
<?php
    }
}

//Filter to add a new tooltip
add_filter('gform_tooltips', 'envoy_add_interval_tooltips');

function envoy_add_interval_tooltips($tooltips) {
    $tooltips["form_field_interval"] = "<h6>Disable Submit Button</h6>Check the box if you would like to disable the submit button.";
    $tooltips["form_field_default_value"] = "<h6>Default Value</h6>Enter the Terms of Service here.";
    return $tooltips;
}

// Add a script to the display of the particular form only if interval field is being used
add_action('gform_enqueue_scripts', 'envoy_interval_gform_enqueue_scripts', 10, 2);

function envoy_interval_gform_enqueue_scripts($form, $ajax) {
    // cycle through fields to see if interval is being used
    foreach ($form['fields'] as $field) {
        if (($field['type'] == 'interval') && (isset($field['field_interval']))) {
            $url = plugins_url('gform_interval.js', __FILE__);
            wp_enqueue_script("gform_interval_script", $url, array(
                    "jquery"
            ), '1.0');
            break;
        }
    }
}

// Add a custom class to the field li
add_action("gform_field_css_class", "custom_interval_class", 10, 3);

function custom_interval_class($classes, $field, $form) {
    if ($field["type"] == "interval") {
        $classes .= " gform_interval";
    }

    return $classes;
}