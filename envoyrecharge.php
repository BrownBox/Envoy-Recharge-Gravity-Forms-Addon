<?php
/*
Plugin Name: Gravity Forms EnvoyRecharge Standard Add-On
Plugin URI: http://www.gravityforms.com
Description: Integrates Gravity Forms with EnvoyRecharge Payments Standard, enabling end users to purchase goods and services through Gravity Forms.
Version: 1.0
Author: Brownbox
Author URI: http://www.brownbox.net.au
Text Domain: gravityformsenvoyrecharge
Domain Path: /languages

------------------------------------------------------------------------
Copyright 2015 EnvoyRecharge
Last updated: January 15, 2015

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/


define('GF_ENVOY_VERSION', '1.0');
define( 'GF_ENVOY_SERVER', 'https://api.envoyrecharge.com' );

add_action('gform_loaded', array(
    'GF_Envoy_Launch',
    'load'
), 5);

class GF_Envoy_Launch
{

    public static function load()
    {

        if (!method_exists('GFForms', 'include_payment_addon_framework')) {
            return;
        }

        require_once('class-gf-envoy.php');
        require_once('envoy-interval-settings.php');
        require_once('envoy-frequency-settings.php');

        GFAddOn::register('GFEnvoy');
    }

}

