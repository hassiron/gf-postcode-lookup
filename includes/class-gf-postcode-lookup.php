<?php

/**
 * GF_Postcode_Lookup
 * 
 * Create the instance of the add-on to hook into Gravity Forms
 * 
 * @since 1.0.0
 * @package GF_Postcode_Lookup
 */
class GFPostcodeLookup extends GFAddOn {
    
    protected $_version = GF_POSTCODE_LOOKUP_VERSION;
    protected $_min_gravityforms_version = '1.9';
    protected $_slug = 'gf-postcode-lookup';
    protected $_path = 'gf-postcode-lookup/gf-postcode-lookup.php';
    protected $_full_path = __FILE__;
    protected $_title = 'Gravity Forms: Postcode Lookup Field';
    protected $_short_title = 'Postcode Lookup';
    protected $_client = null;

    private static $_instance = null;

    /**
     * Returns an instance of the class and stores it in the $_instance property
     * 
     * @return object $_instance
     */
    public static function get_instance() {
        if (self::$_instance == null) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Include the field early so it's available during entry exports
     */
    public function pre_init() {
        global $gfPostcodeLookupAddon;

        parent::pre_init();

        if ($this->is_gravityforms_supported() && class_exists('GF_Field')) {
            require_once GF_POSTCODE_LOOKUP_PATH . 'includes/class-field.php';

            $gfPostcodeLookupAddon = $this;

            GF_Fields::register(new Postcode_Lookup_Field());
        }
    }

    public function init_admin() {
        parent::init_admin();

        add_filter('gform_tooltips', [$this, 'tooltips']);
        add_action('gform_field_appearance_settings', [$this, 'field_appearance_settings'], 10, 2);

        $this->register_getaddress_api();
    }

    /**
     * Create an admin instance of the getaddress.io API
     *
     * @return void
     */
    public function register_getaddress_api() {
        $api_key = $this->get_plugin_setting('gfpcl_api_key');
        $admin_key = $this->get_plugin_setting('gfpcl_admin_key');

        if ($api_key && $admin_key) {
            $this->_client = new Philcross\GetAddress\Client($api_key, $admin_key);
        }
    }

    /**
     * Include any scripts associated with the postcode look-up field
     * 
     * @return array
     */
    public function scripts() {
        return array_merge(parent::scripts(), [
            [
                'handle' => 'gf-postcode-lookup-js',
                'src' => GF_POSTCODE_LOOKUP_URL . '/public/js/plugin.js',
                'version' => $this->_version,
                'deps' => ['jquery'],
                'enqueue' => [[ 'field_types' => ['postcode-lookup'] ]]
            ]
        ]);
    }

    /**
     * Includes any styles associated with the postcode look-up field
     */
    public function styles() {
        return array_merge(parent::styles(), [
            [
                'handle' => 'gf-postcode-lookup-css',
                'src' => GF_POSTCODE_LOOKUP_URL . 'public/css/plugin.css',
                'version' => $this->_version,
                'enqueue' => [[ 'field_types' => ['postcode-lookup'] ]]
            ]
        ]);
    }

    /**
     * Add tooltips for the field
     * 
     * @param array $tooltips
     * 
     * @return array
     */
    public function tooltips($tooltips) {
        return array_merge($tooltips, [
            'field_button_text' => sprintf('%s', esc_html__('The button text for the postcode search form', GF_POSTCODE_LOOKUP_DOMAIN))
        ]);
    }

    /**
     * Add custom settings for the field
     * 
     * @param int $position
     * @param int $form_id
     * 
     * @return void
     */
    public function field_appearance_settings($position, $form_id) {
        if ($position == 250) {
            require_once GF_POSTCODE_LOOKUP_PATH . 'resources/views/admin/field-settings-appearance.php';
        }
    }

    /**
     * Create a plugin settings page
     *
     * @return void
     */
    public function plugin_settings_fields() {
        return [
            [
                'title' => '<h3>API Keys</h3>',
                'description' => '<P>The postcode lookup field uses <a href="https://getaddress.io" target="_blank">getaddress.io</a> to fetch addresses. You\'ll need an API key from there to perform lookups.</p>',
                'fields' => [
                    [
                        'name'              => 'gfpcl_api_key',
                        'tooltip'           => esc_html__('Your getaddress.io API key', 'simpleaddon'),
                        'label'             => esc_html__('API Key', 'simpleaddon'),
                        'type'              => 'text',
                        'class'             => 'medium',
                        'feedback_callback' => [$this, 'check_api_key'],
                        'required'          => true
                    ],
                    [
                        'name'              => 'gfpcl_admin_key',
                        'tooltip'           => esc_html__('Your getaddress.io administrative API key', 'simpleaddon'),
                        'label'             => esc_html__('Administrative Key', 'simpleaddon'),
                        'type'              => 'text',
                        'class'             => 'medium',
                        'feedback_callback' => [$this, 'check_admin_key'],
                        'required'          => true
                    ]
                ]
            ],
            [
                'title' => '<h3>API Usage</h3>',
                // 'dependency' => [$this, 'get_address_client_is_valid'],
                'description' => $this->get_api_usage_description(),
                'fields' => []
            ]
        ];
    }

    public function get_api_usage_description() {
        $description = 'Unable to get usage data';

        if ($this->_client != null) {
            try {
                $usage = $this->_client->usage();
                $description = sprintf('You currently have %d API requests remaining', (int) $usage->requestsRemaining());
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                $description = sprintf('Unable to get usage data: <code>%s</code>', $e->getMessage());
            }
        }

        return sprintf('<p>%s</p>', $description);
    }

    /**
     * Validate the API key given to the plugin settings
     * 
     * @return boolean
     */
    public function check_api_key() {
        return $this->get_plugin_setting('gfpcl_api_key') != '';
    }

    /**
     * Validate the administrative API key given to the plugin settings
     * 
     * @return boolean
     */
    public function check_admin_key() {
        return $this->get_plugin_setting('gfpcl_admin_key') != '';
    }

    /**
     * Check that there's currently a valid instance of the getaddress.io API in place
     *
     * @return boolean
     */
    public function get_address_client_is_valid() {
        return $this->_client != null;
    }

    /**
     * Create a static method to get plugin settings
     * 
     * @param string $setting
     * 
     * @return mixed
     */
    public static function _get_plugin_setting($setting) {
        return $this->get_plugin_setting($setting);
    }
}
