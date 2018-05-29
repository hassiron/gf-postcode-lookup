<?php

use Philcross\GetAddress\Client;
use Philcross\GetAddress\Responses\Address;

/**
 * Create the lookup request to getaddress.io
 * 
 * @package GF_Postcode_Lookup
 * @since 1.0.0
 */
class Postcode_Lookup extends WP_Ajax {
    protected $action = 'gf-postcode-lookup';
    protected $client = null;
    private $statusCode = 200;

    protected function run() {
        $postcode = filter_input(INPUT_POST, 'postcode', FILTER_SANITIZE_STRING);
        $data = $this->getUserAddress($postcode);

        header("Content-type: application/json");
        echo json_encode($data);
    }

    private function getUserAddress($postcode) {
        $data = $this->getCacheEntriesFor($postcode);

        if (!$data) {
            $data = $this->makeGetAddressRequest($postcode);
        }

        return $this->formatResponseData($data);
    }

    private function getCacheEntriesFor($postcode) {
        // implement caching checks

        return false;
    }

    private function makeGetAddressRequest($postcode) {
        global $gfPostcodeLookupAddon;

        $api_key = $gfPostcodeLookupAddon->get_plugin_setting('gfpcl_api_key');
        $admin_key = $gfPostcodeLookupAddon->get_plugin_setting('gfpcl_admin_key');

        if ($api_key && $admin_key) {
            $client = new Client($api_key, $admin_key);
            $response = $client->find($postcode, null, Address::SORT_NUMERICALLY);

            if ($response->getAddresses()) {
                return array_map(function($address) {
                    return $address->toArray();
                }, $response->getAddresses());
            }
        }
        
        return false;
    }

    private function formatResponseData($data) {
        $data = (array) $data;
        $response = [];

        if (empty($data)) {
            $this->statusCode = 404;
            $response['message'] = __("We weren't able to find any addresses for the given postcode", GF_POSTCODE_LOOKUP_DOMAIN);
        } else {
            $response['data'] = $data;
        }

        $response['status'] = $this->statusCode;

        return (object) $response;
    }
}
