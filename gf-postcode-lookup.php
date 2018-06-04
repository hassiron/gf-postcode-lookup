<?php
/**
 * Gravity Forms: Postcode Look-up Field
 * 
 * This file is used by WordPress to generate the plugin information and load it
 * into the CMS
 * 
 * @since 1.0.0
 * @package GF_Postcode_Lookup
 * 
 * @wordpress-plugin
 * Plugin Name: Gravity Forms: Postcode Look-up Field
 * Plugin URI: https://www.github.com/vyygir/gf-postcode-lookup
 * Description: A field for Gravity Forms that allows postcode look-up implementations using the getaddress.io API
 * Version: 1.0.0
 * Author: Vyygir
 * Author URI: https://vyygir.me
 * Text Domain: gf-postcode-lookup
 */

if (!defined('WPINC')) {
    exit;
}

require_once 'vendor/autoload.php';

define('GF_POSTCODE_LOOKUP_VERSION', '1.0.0');
define('GF_POSTCODE_LOOKUP_PATH', plugin_dir_path(__FILE__));
define('GF_POSTCODE_LOOKUP_URL', plugin_dir_url(__FILE__));
define('GF_POSTCODE_LOOKUP_DOMAIN', 'gf-postcode-lookup');

// initialise the customised updater
require_once GF_POSTCODE_LOOKUP_PATH . 'includes/class-updater.php';

if (is_admin()) {
    new Postcode_Lookup_Updater(__FILE__, 'vyygir', 'gf-postcode-lookup');
}

// create the GF add-on
add_action('gform_loaded', function() {
    if (method_exists('GFForms', 'include_addon_framework')) {
        global $gfPostcodeLookupAddon;

        GFForms::include_addon_framework();

        require_once GF_POSTCODE_LOOKUP_PATH . 'includes/class-gf-postcode-lookup.php';
        require_once GF_POSTCODE_LOOKUP_PATH . 'includes/class-postcode-lookup.php';

        Postcode_Lookup::listen();
        GFAddOn::register('GFPostcodeLookup');
    }
}, 5);
