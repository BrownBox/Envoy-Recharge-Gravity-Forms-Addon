<?php
if (class_exists("GFForms")) {
    GFForms::include_payment_addon_framework();
    class GFEnvoy extends GFPaymentAddOn {
        protected $_version = GF_ENVOY_VERSION;
        protected $_min_gravityforms_version = "1.8.12";
        protected $_slug = "EnvoyRecharge";
        protected $_path = "gravity-forms-bb-ech/envoyrecharge.php";
        protected $_full_path = __FILE__;
        protected $_url = "http://www.envoyrecharge.com";
        protected $_title = "Gravity Forms EnvoyRecharge Standard Add-On";
        protected $_short_title = "EnvoyRecharge";
        protected $formid;
        protected $form;
        protected $gateways;
        private static $_instance = null;

        public static function get_instance() {
            if (self::$_instance == null) {
                self::$_instance = new GFEnvoy();
            }

            self::$_instance->form = self::$_instance->get_current_form();

            self::$_instance->formid = self::$_instance->form["id"];

            return self::$_instance;
        }

        public function pre_init() {
            parent::pre_init();
            $response = $this->getGetways();
            $gatewaysObject = json_decode($response);
            $this->gateways = array();
            array_push($this->gateways, array(
                    "label" => __('Select a gateway', 'gravityformsenvoyrecharge'),
                    "value" => ""
            ));
            // gateways returned then add them to option list

            if (is_object($gatewaysObject) && sizeof($gatewaysObject->gateways) > 0) {
                foreach ($gatewaysObject->gateways as $key => $gateway) {
                    array_push($this->gateways, array(
                            "label" => __($gateway->name, 'gravityformsenvoyrecharge'),
                            "value" => $gateway->_id
                    ));
                }
            }
        }

        public function init() {
            parent::init();
            add_filter("gform_validation", array($this, "envoyValidateForm"));
            // This is a filter to change the default validation message that Gravity Forms generates
            add_filter('gform_validation_message', array($this, 'change_validation_message'), 10, 2);
        }

        public function plugin_page() {
?>
<div class="wrap about-wrap">
    <h1>EnvoyRecharge Add-On v1.0</h1>
    <div class="about-text">Thank you for using the EnvoyRecharge standard Add-On! This Add-On makes managing your reccuring payments through EnvoyRecharge simple.</div>
    <div class="changelog">
        <hr />
        <div class="feature-section col two-col">
            <div class="col-1">
                <h3>Manage EnvoyRecharge Contextually</h3>
                <p>EnvoyRecharge Feeds are accessed via the EnvoyRecharge sub-menu within the Form Settings for the form you would like to integrate with EnvoyRecharge.</p>
            </div>
            <div class="col-2 last-feature">
                <img src="http://envoyrecharge.com/wp-content/uploads/2014/10/another2-700x350.png">
            </div>
        </div>
        <hr />
        <p>Cost-effective recurring payments made easy with your choice of gateway – integrated with your choice of platform.</p>
        <p>
            <a href="http://envoyrecharge.com/" target="_blank">Visit us to know more</a> or <a href="admin.php?page=gf_settings&subview=EnvoyRecharge">Go to Settings</a>
        </p>
    </div>
</div>
<?php
        }

        public function feed_settings_fields() {
            $form = $this->get_current_form();

            $gatewayfields = self::$_instance->generalSettings();
            //$checkboxradio_field_settings = self::$_instance->CheckboxRadioFields();

            self::$_instance->onload();
            self::$_instance->show_general_settings();

            $fields = array(
                    array(
                            "title" => "Envoyrecharge Feed Settings",
                            "fields" => array(
                                    array(
                                            "label" => __('Name', 'gravityformsenvoyrecharge'),
                                            "type" => "text",
                                            "name" => "feedName",
                                            "required" => true,
                                            "tooltip" => __('Enter a feed name to uniquely identify this setup', 'gravityformsenvoyrecharge')
                                    ),

                                    array(
                                            "label" => __("Gateway", 'gravityformsenvoyrecharge'),
                                            "type" => "select",
                                            "name" => "envoygateway_" . $form['id'],
                                            "tooltip" => __('Choose a payment Gateway', 'gravityformsenvoyrecharge'),
                                            "id" => "envoygatewayid",
                                            "required" => true,
                                            "choices" => $this->gateways
                                    ),
                                    array(
                                            "label" => "",
                                            "type" => "select",
                                            "name" => "transactionType",
                                            "id" => "envoytransactiontypeid",
                                            "class" => "hidden",
                                            "choices" => array(
                                                    array(
                                                            "label" => __('subscription to envoy', 'gravityformsenvoyrecharge'),
                                                            "value" => "subscription"
                                                    )
                                            )

                                    )
                            )
                    )
            );

            foreach ($gatewayfields as $key => $gatewayfield) {
                array_push($fields[0]["fields"], $gatewayfield);
            }

            return $fields;
        }

        protected function generalSettings() {
            $field_settings = self::$_instance->formFields();
            $product_field_settings = self::$_instance->productFields();
            $fields = array();
            array_push($fields, array(
                    "label" => __('Amount', 'gravityformsenvoyrecharge'),
                    "type" => "select",
                    "name" => "envoyamount_" . self::$_instance->formid,
                    "class" => "envoy_general",
                    "required" => true,
                    "choices" => $product_field_settings
            ));

            array_push($fields, array(
                    "label" => __('Currency', 'gravityformsenvoyrecharge'),
                    "type" => "select",
                    "name" => "envoycurrency_" . self::$_instance->formid,
                    "class" => "envoy_general",
                    "required" => false,
                    "choices" => $field_settings
            ));

            array_push($fields, array(
                    "label" => __('First Name', 'gravityformsenvoyrecharge'),
                    "type" => "select",
                    "name" => "envoyfname_" . self::$_instance->formid,
                    "class" => "envoy_general",
                    "required" => true,
                    "choices" => $field_settings
            ));
            array_push($fields, array(
                    "label" => __('Last Name', 'gravityformsenvoyrecharge'),
                    "type" => "select",
                    "name" => "envoylname_" . self::$_instance->formid,
                    "class" => "envoy_general",
                    "required" => true,
                    "choices" => $field_settings
            ));
            array_push($fields, array(
                    "label" => __('Email', 'gravityformsenvoyrecharge'),
                    "type" => "select",
                    "name" => "envoyemail_" . self::$_instance->formid,
                    "class" => "envoy_general",
                    "required" => true,
                    "choices" => $field_settings
            ));
            array_push($fields, array(
                    "label" => __('Address', 'gravityformsenvoyrecharge'),
                    "type" => "select",
                    "name" => "envoyaddress_" . self::$_instance->formid,
                    "class" => "envoy_general",
                    "required" => true,
                    "choices" => $field_settings
            ));
            array_push($fields, array(
                    "label" => __('Address2', 'gravityformsenvoyrecharge'),
                    "type" => "select",
                    "name" => "envoyaddress2_" . self::$_instance->formid,
                    "class" => "envoy_general",
                    "choices" => $field_settings
            ));
            array_push($fields, array(
                    "label" => __('City', 'gravityformsenvoyrecharge'),
                    "type" => "select",
                    "name" => "envoycity_" . self::$_instance->formid,
                    "class" => "envoy_general",
                    "required" => true,
                    "choices" => $field_settings
            ));
            array_push($fields, array(
                    "label" => __('State', 'gravityformsenvoyrecharge'),
                    "type" => "select",
                    "name" => "envoystate_" . self::$_instance->formid,
                    "class" => "envoy_general",
                    "required" => true,
                    "choices" => $field_settings
            ));
            array_push($fields, array(
                    "label" => __('Zip/Post code', 'gravityformsenvoyrecharge'),
                    "type" => "select",
                    "name" => "envoyzip_" . self::$_instance->formid,
                    "class" => "envoy_general",
                    "required" => true,
                    "choices" => $field_settings
            ));
            array_push($fields, array(
                    "label" => __('Country', 'gravityformsenvoyrecharge'),
                    "type" => "select",
                    "name" => "envoycountry_" . self::$_instance->formid,
                    "class" => "envoy_general",
                    "required" => true,
                    "choices" => $field_settings
            ));

            array_push($fields, array(
                    "label" => __('Start date', 'gravityformsenvoyrecharge'),
                    "type" => "select",
                    "name" => "envoystartdate_" . self::$_instance->formid,
                    "class" => "envoy_general",
                    "choices" => $field_settings
            ));

            array_push($fields, array(
                    "label" => __('End date', 'gravityformsenvoyrecharge'),
                    "type" => "select",
                    "name" => "envoyenddate_" . self::$_instance->formid,
                    "class" => "envoy_general",
                    "choices" => $field_settings
            ));

            array_push($fields, array(
                    "label" => __('Reference for payment', 'gravityformsenvoyrecharge'),
                    "type" => "select",
                    "name" => "envoytxref_" . self::$_instance->formid,
                    "class" => "envoy_general",
                    "choices" => $field_settings
            ));

            array_push($fields, array(
                    "label" => __('Reference for 3rd party integration', 'gravityformsenvoyrecharge'),
                    "type" => "select",
                    "name" => "envoyreference_" . self::$_instance->formid,
                    "class" => "envoy_general",
                    "choices" => $field_settings
            ));

            return $fields;
        }

        protected function formFields() {
            $form = $this->get_current_form();

            $fields = $form['fields'];
            $default_settings = array();
            array_push($default_settings, array(
                    "value" => "",
                    "label" => ""
            ));
            foreach ($fields as $key => $field) {
                if ($field["type"] != "creditcard") {
                    //if type is address or name, this is special handle it differently
                    if ($field["type"] == 'address' || ($field["type"] == 'name' && (!isset($field["nameFormat"]) || $field["nameFormat"] != 'simple'))) {
                        foreach ($field['inputs'] as $keyvalue => $inputvalue) {
                            $field_settings = array();
                            $field_settings['value'] = str_replace('.', '_', $inputvalue['id']);
                            $field_settings['label'] = __($inputvalue['label'], 'gravityformsenvoyrecharge');
                            array_push($default_settings, $field_settings);
                        }
                    } else {
                        $field_settings = array();
                        $field_settings['value'] = $field['id'];
                        $field_settings['label'] = __($field['label'], 'gravityformsenvoyrecharge');
                        array_push($default_settings, $field_settings);
                    }
                }
            }

            return $default_settings;
        }

        //this function return products fields and total field
        protected function productFields() {
            $form = $this->get_current_form();
            $fields = $form['fields'];
            $default_settings = array();

            $check_total_exist = 0; // if field total does not exist
            array_push($default_settings, array(
                    "value" => "",
                    "label" => ""
            ));

            // If we have BB Cart, we can get amount from there
            if (is_plugin_active('bb_cart/bb_cart.php')) {
                $default_settings[] = array(
                        'value' => 'bb_cart',
                        'label' => 'BB Cart',
                );
            }

            foreach ($fields as $key => $field) {
                if ($field['type'] == 'product' || $field['type'] == 'envoyrecharge' || $field['type'] == 'total') {
                    if ($field['type'] == 'total')
                        $check_total_exist = 1; //total exists.
                    $field_settings = array();
                    $field_settings['value'] = $field['id'];
                    $field_settings['label'] = __($field['label'], 'gravityformsenvoyrecharge');
                    array_push($default_settings, $field_settings);
                }
            }
            //check if field total don't exist then add it
            if ($check_total_exist == 0) {
                $field_settings = array();
                $field_settings['value'] = 'total';
                $field_settings['label'] = __('Total', 'gravityformsenvoyrecharge');
                array_push($default_settings, $field_settings);
            }
            return $default_settings;
        }

        //this function return checkbox and radio fields
        protected function CheckboxRadioFields() {
            $form = $this->get_current_form();
            $fields = $form['fields'];
            $default_settings = array();
            array_push($default_settings, array(
                    "label" => __('Select a transaction type', 'gravityformsenvoyrecharge'),
                    "value" => ""
            ));
            foreach ($fields as $key => $field) {
                if ($field['type'] == 'checkbox' || $field['type'] == 'radio') {
                    $field_settings = array();
                    $field_settings['value'] = $field['id'];
                    $field_settings['label'] = __($field['label'], 'gravityformsenvoyrecharge');
                    array_push($default_settings, $field_settings);
                }
            }

            return $default_settings;
        }

        //hide all general settings onload
        protected function onload() {
?>
<script type="text/javascript">
    jQuery('document').ready(function() {
        if(jQuery("#envoygatewayid").val() == '' || jQuery("#envoygatewayid").val()==null){
            jQuery('.envoy_general').parent().parent().hide();
        }
    });
</script>
<?php
        }

        protected function show_general_settings() {
?>
<script type="text/javascript">
    jQuery('document').ready(function() {
        jQuery("#envoygatewayid").change(function(){
            if(this.value != '' && this.value !=null){
                jQuery('.envoy_general').parent().parent().show();
            }
            else{
                jQuery('.envoy_general').parent().parent().hide();
            }
        });

    });
</script>
<?php
        }

        public function plugin_settings_fields() {
            return array(
                    array(
                            "title" => "Envoyrecharge Add-On Settings",
                            "fields" => array(
                                    array(
                                            "name" => "envoyapikey",
                                            "tooltip" => __('Your EnvoyRecharge Key', 'gravityformsenvoyrecharge'),
                                            "label" => __('API Key', 'gravityformsenvoyrecharge'),
                                            "type" => "text",
                                            "class" => "small"
                                    )
                            )
                    )
            );
        }

        /*
         * ==============================Process Payment============================================
         */
        public function envoyValidateForm($validation_result) {
            //array to hold errors.
            $errors = array();
            //hold total amount if total amount chosen
            $total = 0;
            //array to hold field ids and labels
            $fields_in_feed = array();
            $form = $validation_result["form"];
            $form_id = $form["id"];
            $entry = GFFormsModel::create_lead($form);
            $feed = $this->get_payment_feed($entry, $form);

            if ($feed) {
                $envoyrechargemeta = $feed["meta"];

                $fname_fid = $envoyrechargemeta['envoyfname_' . $form_id];
                if (empty($fname_fid))
                    array_push($errors, __('Error: EnvoyRecharge firstname feed field is empty.', 'gravityformsenvoyrecharge'));
                else
                    $fname_idarray = array('firstname', $fname_fid);

                $lname_fid = $envoyrechargemeta['envoylname_' . $form_id];
                if (empty($lname_fid))
                    array_push($errors, __('Error: EnvoyRecharge lastname feed field is empty.', 'gravityformsenvoyrecharge'));
                else
                    $lname_idarray = array('lastname', $lname_fid);

                $email_fid = $envoyrechargemeta['envoyemail_' . $form_id];
                if (empty($email_fid))
                    array_push($errors, __('Error: EnvoyRecharge email feed field is empty.', 'gravityformsenvoyrecharge'));
                else
                    $email_idarray = array('email', $email_fid);

                $address_fid = $envoyrechargemeta['envoyaddress_' . $form_id];
                if (empty($address_fid))
                    array_push($errors, __('Error: EnvoyRecharge address feed field is empty.', 'gravityformsenvoyrecharge'));
                else
                    $address_idarray = array('address_line1', $address_fid);

                $address2_fid = $envoyrechargemeta['envoyaddress2_' . $form_id];
                if (!empty($address2_fid))
                    $address2_idarray = array('address_line2', $address2_fid);

                $city_fid = $envoyrechargemeta['envoycity_' . $form_id];
                if (empty($city_fid))
                    array_push($errors, __('Error: EnvoyRecharge city feed field is empty.', 'gravityformsenvoyrecharge'));
                else
                    $city_idarray = array('address_city', $city_fid);

                $state_fid = $envoyrechargemeta['envoystate_' . $form_id];
                if (empty($state_fid))
                    array_push($errors, __('Error: EnvoyRecharge state feed field is empty.', 'gravityformsenvoyrecharge'));
                else
                    $state_idarray = array('address_state', $state_fid);

                $zip_fid = $envoyrechargemeta['envoyzip_' . $form_id];
                if (empty($zip_fid))
                    array_push($errors, __('Error: EnvoyRecharge zip or postcode feed field is empty.', 'gravityformsenvoyrecharge'));
                else
                    $zip_idarray = array('address_postcode', $zip_fid);

                $country_fid = $envoyrechargemeta['envoycountry_' . $form_id];
                if (empty($country_fid))
                    array_push($errors, __('Error: EnvoyRecharge country feed field is empty.', 'gravityformsenvoyrecharge'));
                else
                    $country_idarray = array('address_country', $country_fid);

                $amount_id = $envoyrechargemeta['envoyamount_' . $form_id];
                if (empty($amount_id))
                    array_push($errors, __('Error: EnvoyRecharge amount feed field is empty.', 'gravityformsenvoyrecharge'));
                else {
                    if ($amount_id != 'total' && $amount_id != 'bb_cart') // check that amount field is not a total field.
                        $amount_idarray = array('amount', $amount_id);
                }

                $startdate_id = $envoyrechargemeta['envoystartdate_' . $form_id];
                if (!empty($startdate_id))
                    $startdate_idarray = array('startdate', $startdate_id);
                $enddate_id = $envoyrechargemeta['envoyenddate_' . $form_id];
                if (!empty($enddate_id))
                    $enddate_idarray = array('enddate', $enddate_id);
                $currency_id = $envoyrechargemeta['envoycurrency_' . $form_id];
                if (!empty($currency_id))
                    $currency_idarray = array('currency', $currency_id);

                $paymentgateway_fid = $envoyrechargemeta['envoygateway_' . $form_id];
                if (empty($paymentgateway_fid))
                    array_push($errors, __('Error: EnvoyRecharge Gateway id feed field is empty.', 'gravityformsenvoyrecharge'));

                $txref_fid = $envoyrechargemeta['envoytxref_' . $form_id];
                if (!empty($txref_fid))
                    $txtref_idarray = array('txref', $txref_fid);
                $reference_fid = $envoyrechargemeta['envoyreference_' . $form_id];
                if (!empty($reference_fid))
                    $reference_idarray = array('reference', $reference_fid);

                // if any of the above mentioned feed exists, stop processing and return the errors
                if (sizeof($errors) > 0) {
                    $settings_errors = '<ul>' . "\n";
                    $settings_errors .= '<li class="gfield gfield_contains_required gfield_error">' . "\n";
                    $settings_errors .= '<ul>' . "\n";
                    foreach ($errors as $key => $feed_error) {
                        $settings_errors .= '<li >' . "\n";
                        $settings_errors .= __($feed_error, 'gravityformsenvoyrecharge') . "\n";
                        $settings_errors .= '</li>' . "\n";
                    }
                    $settings_errors .= '</ul>' . "\n";
                    $settings_errors .= '</li>' . "\n";
                    $settings_errors .= '</ul>' . "\n";
                    $validation_result["form"]["error"] = $settings_errors;
                    $validation_result["is_valid"] = false;
                    return $validation_result;
                }

                //if no error in feed creation, then push all field ids and labels into feeds array
                array_push($fields_in_feed, $fname_idarray);
                array_push($fields_in_feed, $lname_idarray);
                array_push($fields_in_feed, $email_idarray);
                array_push($fields_in_feed, $address_idarray);
                if (!empty($address2_idarray))
                    array_push($fields_in_feed, $address2_idarray);
                array_push($fields_in_feed, $city_idarray);
                array_push($fields_in_feed, $state_idarray);
                array_push($fields_in_feed, $zip_idarray);
                array_push($fields_in_feed, $country_idarray);
                if (isset($amount_idarray))
                    array_push($fields_in_feed, $amount_idarray);
                if (!empty($startdate_idarray))
                    array_push($fields_in_feed, $startdate_idarray);
                if (!empty($enddate_idarray))
                    array_push($fields_in_feed, $enddate_idarray);
                if (!empty($currency_idarray))
                    array_push($fields_in_feed, $currency_idarray);
                if (!empty($txtref_idarray))
                    array_push($fields_in_feed, $txtref_idarray);
                if (!empty($reference_idarray))
                    array_push($fields_in_feed, $reference_idarray);

                foreach ($fields_in_feed as $key => $feed_field) {
                    foreach ($form["fields"] as $fieldkey => $field) {
                        if ($field['type'] != 'creditcard' && $field['type'] != 'interval' && $field['type'] != 'frequency') {
                            //create an array containing feed id and input id if any
                            $feed_array = explode('_', $feed_field[1]);
                            if ($field['id'] == $feed_array[0]) {
                                $$feed_field[0] = rgpost('input_' . $feed_field[1]);
                                if ($field['type'] == 'product')
                                    $total += $this->clean_amount(rgpost('input_' . $field['id'])) / 100;
                                elseif ($field['type'] == 'envoyrecharge') {
                                    $total += $this->clean_amount(rgpost('input_' . $field['id'].'_1')) / 100;
                                    $$feed_field[0] = rgpost('input_' . $feed_field[1].'_1');
                                    if (rgpost('input_' . $field['id'].'_5') == 'recurring')
                                        $interval = rgpost('input_' . $field['id'].'_2');
                                }
                            }
                        } else if ($field['type'] == 'creditcard') {
                            $ccnumber = rgpost('input_' . $field['id'] . '_1');
                            $ccdate_array = rgpost('input_' . $field['id'] . '_2');
                            $ccdate_month = $ccdate_array[0];
                            $ccdate_year = $ccdate_array[1];
                            $ccv = rgpost('input_' . $field['id'] . '_3');
                            $ccname = rgpost('input_' . $field['id'] . '_5');
                        } else if ($field["type"] == 'interval') {
                            $interval = rgpost('input_' . $field['id']);
                        } else if ($field["type"] == 'frequency') {
                            $frequency = rgpost('input_' . $field['id']);
                        }
                    }
                }

                //if validation is valid the process
                if ($validation_result["is_valid"]) {
                    $transactions = array();
                    $interval = (isset($interval) && $interval != '' && $interval != null) ? $interval : 'one-off';
                    $frequency = (isset($frequency) && $frequency != '' && $frequency != null) ? $frequency : 1;

                    //create array of parameter to be sent to EnvoyRecharge
                    //clean amount first by converting to cents then to $

                    if (isset($amount)) {
                        $amount = $this->clean_amount($amount);
                        $amount = $amount / 100;
                        $transactions[$interval] = $amount;
                    } elseif ($amount_id == 'total') { // total amount to be used
                        foreach ($form["fields"] as $key => $field) {
                            if ($field['type'] == 'product') {
                                $total += $this->clean_amount(rgpost('input_' . $field['id'])) / 100;
                            } elseif ($field['type'] == 'envoyrecharge') {
                                $total += $this->clean_amount(rgpost('input_' . $field['id'].'.1')) / 100;
                            }
                        }
                        $amount = $total;
                        $transactions[$interval] = $amount;
                    } elseif ($amount_id == 'bb_cart') {
                        if (!empty($_SESSION[BB_CART_SESSION_ITEM])) {
                            foreach ($_SESSION[BB_CART_SESSION_ITEM] as $cart_item) {
                                if (!isset($transactions[$cart_item['frequency']]))
                                    $transactions[$cart_item['frequency']] = 0;
                                $transactions[$cart_item['frequency']] += $cart_item['price'];
                            }
                        }
                    }

                    foreach ($transactions as $the_interval => $amount) {
                        $data = array();
                        $data["firstname"] = $firstname;
                        $data["lastname"] = $lastname;
                        $data["email"] = $email;
                        $data["amount"] = $amount;
                        $data["currency"] = (!empty($currency)) ? $currency : GFCommon::get_currency();
                        $data["interval"] = $the_interval;
                        $data["frequency"] = $frequency;
                        if (isset($startdate))
                            $data["startdate"] = $startdate; //format need to be date('d/m/Y');
                        if (isset($enddate))
                            $data["enddate"] = $enddate; // format need to be date('d/m/Y');
                        if (isset($txtref))
                            $data["txref"] = $txtref;
                        if (isset($reference))
                            $data["reference"] = $reference;
                        $data["cardholder"] = $ccname;
                        $data["cardnumber"] = $ccnumber;
                        $data["cardexp"] = $ccdate_month . '/' . $ccdate_year;
                        $data["cardccv"] = $ccv;
                        $data["gateway"] = $paymentgateway_fid;
                        $data["address_line1"] = $address_line1;
                        if (isset($address_line2))
                            $data["address_line2"] = $address_line2;
                        $data["address_city"] = $address_city;
                        $data["address_postcode"] = $address_postcode;
                        $data["address_state"] = $address_state;
                        $data["address_country"] = $address_country;

                        $result = $this->processToEnvoy($data);
                        $result = json_decode($result);

                        if (!isset($result->subscription->status) && !isset($result->transaction->status)) {
                            $validation_result["is_valid"] = false;
                            $error_message = '<ul>' . "\n";
                            $error_message .= '<li class="gfield gfield_contains_required gfield_error">' . "\n";
                            $error_message .= '<ul>' . "\n";
                            if ($result == null || $result == '') {
                                $error_message .= '<li>' . __('Sorry Buddy. We could not connect to EnvoyRecharge at this time. Invalid credentials</li>', 'gravityformsenvoyrecharge') . "\n";
                            } else {
                                if (is_string($result))
                                    $error_message .= '<li>' . __($result, 'gravityformsenvoyrecharge') . '</li>' . "\n";
                                else {
                                    $error_array = $result->_errors;
                                    foreach ($error_array as $key => $error) {
                                        if (is_object($error)) {
                                            $error_object = get_object_vars($error);
                                            $error_message .= '<li>' . __($error_object['description'], 'gravityformsenvoyrecharge') . '</li>' . "\n";
                                        } else
                                            $error_message .= '<li>' . __($error, 'gravityformsenvoyrecharge') . '</li>' . "\n";
                                    }
                                }
                            }
                            $error_message .= '</ul>' . "\n";
                            $error_message .= '</li>' . "\n";
                            $error_message .= '</ul>' . "\n";

                            $validation_result["form"] = $form;
                            $validation_result["form"]["error"] = $error_message;
                            return $validation_result;
                        }
                    }
                }
            }
            return $validation_result;
        }

        function change_validation_message($message, $form) {
            $error_message = $form["error"];
            $form["description"] = "";
            return "<div class='validation_error'>" . $error_message . "</div>";
        }

        //send subscription data to EnvoyRecharge
        protected function processToEnvoy($data) {
            $data_string = json_encode($data);
            $settings = $this->get_plugin_settings();
            $envoyrecharge_key = $settings['envoyapikey'];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, GF_ENVOY_SERVER . '/v1/subscriptions');
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'x-user-token:' . $envoyrecharge_key,
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($data_string)
            ));

            $result = curl_exec($ch);
            curl_close($ch);
            return $result;
        }

        // retrieve gateway ids
        protected function getGetways() {
            $settings = $this->get_plugin_settings();
            $envoyrecharge_key = $settings['envoyapikey'];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, GF_ENVOY_SERVER . '/v1/gateways');
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'x-user-token:' . $envoyrecharge_key,
                    'Content-Type: application/json'
            ));

            $result = curl_exec($ch);
            curl_close($ch);
            return $result;
        }

        // THIS IS OUR FUNCTION FOR CLEANING UP THE PRICING AMOUNTS THAT GF SPITS OUT
        function clean_amount($entry) {
            $entry = preg_replace("/\|(.*)/", '', $entry); // replace everything from the pipe symbol forward
            if (strpos($entry, '.') === false) {
                $entry .= ".00";
            }
            if (strpos($entry, '$') !== false) {
                $startsAt = strpos($entry, "$") + strlen("$");
                $endsAt = strlen($entry);
                $amount = substr($entry, 0, $endsAt);
                $amount = preg_replace("/[^0-9,.]/", "", $amount);
            } else {
                $amount = preg_replace("/[^0-9,.]/", "", sprintf("%.2f", $entry));
            }

            $amount = str_replace('.', '', $amount);
            $amount = str_replace(',', '', $amount);
            return $amount;
        }
    }
}
?>