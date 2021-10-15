<?php
/**
 * @category Mulberry
 * @package Mulberry\Warranty
 * @author Mulberry <support@getmulberry.com>
 * @copyright Copyright (c) 2020 Mulberry Technology Inc., Ltd (http://www.getmulberry.com)
 * @license http://opensource.org/licenses/OSL-3.0 The Open Software License 3.0 (OSL-3.0)
 */

class WC_Mulberry_Api_Rest_Validate_Warranty
{
    /**
     * Endpoint URI for warranty validation
     */
    const WARRANTY_VALIDATE_ENDPOINT_URL = '/api/validate_warranty/%s';

    /**
     * @var WC_Mulberry_Api_Rest_Service
     */
    private $service;

    /**
     * Data mapping for warranty attributes,
     * stored as follows:
     * ['Meta information key'] => ['Mulberry API key']
     *
     * @var array $warranty_attributes_mapping
     */
    protected $warranty_attributes_mapping = array(
        'warranty_price' => array('cost'),
        'service_type' => array('service_type'),
        'warranty_hash' => array('warranty_hash'),
        'duration_months' => array('duration_months'),
        'warranty_offer_id' => array('warranty_offer_id'),
    );

    /**
     * Mulberry_Warranty_Model_Api_Rest_Warranty constructor.
     */
    public function __construct()
    {
        $this->service = new WC_Mulberry_Api_Rest_Service();
    }

    /**
     * Retrieve warranty information from API using hash identifier
     *
     * @param string $hash
     * @return array
     * @throws Exception
     */
    public function get_warranty_by_hash(string $hash)
    {
        $response = $this->service->make_request(sprintf(self::WARRANTY_VALIDATE_ENDPOINT_URL, $hash));

        return $this->parse_response($response);
    }

    /**
     * Prepare data mapping for warranty product by hash
     *
     * @param $response
     *
     * @return array
     */
    private function parse_response($response)
    {
        $result = array();

        /**
         * Warranty product information is stored in $response[0][0]
         */
        $warranty_product = (is_array($response) && isset($response['message'])) ? $response['message'] : array();

        if (!empty($warranty_product) && $this->validate_warranty_product_response($warranty_product)) {
            $result = array(
                'warranty_price' => (float) $warranty_product['cost'],
                'service_type' => $warranty_product['service_type'],
                'warranty_hash' => $warranty_product['warranty_hash'],
                'warranty_offer_id' => $warranty_product['warranty_offer_id'],
                'duration_months' => $warranty_product['duration_months'],
            );
        }

        return $result;
    }

    /**
     * Make sure we have all the necessary information
     *
     * @param $warranty_product
     *
     * @return bool
     */
    private function validate_warranty_product_response($warranty_product)
    {
        foreach ($this->warranty_attributes_mapping as $meta_attribute => $api_node) {
            $warranty_attribute_node = $warranty_product;

            foreach ($api_node as $node) {
                if (!isset($warranty_attribute_node[$node])) {
                    return false;
                } else {
                    $warranty_attribute_node = $warranty_attribute_node[$node];
                }
            }
        }

        return true;
    }
}
