<?php
/**
 * @category Mulberry
 * @package Mulberry\Warranty
 * @author Mulberry <support@getmulberry.com>
 * @copyright Copyright (c) 2020 Mulberry Technology Inc., Ltd (http://www.getmulberry.com)
 * @license http://opensource.org/licenses/OSL-3.0 The Open Software License 3.0 (OSL-3.0)
 */

class WC_Mulberry_Api_Rest_Service
{
    /**
     * @var array $headers
     */
    private $headers = array();

    /**
     * @var null|string
     */
    private $uri;

    /**
     * Mulberry_Warranty_Model_Api_Rest_Service constructor.
     */
    public function __construct()
    {
        $this->uri = WC_Integration_Mulberry_Warranty::get_config_value('partner_url');
    }

    /**
     * @param $url
     * @param string $body
     * @param string $method
     * @return array|mixed
     * @throws Exception
     */
    public function make_request($url, $body = '', $method = 'GET')
    {
        $response = array(
            'is_successful' => false,
        );

        try {
            $this->set_header('Content-Type', 'application/json');
            $this->set_header('Authorization', sprintf('Bearer %s', WC_Integration_Mulberry_Warranty::get_config_value('api_token')));

            if (!$this->uri) {
                throw new Exception(__('Partner URL setting is not set'));
            }

            $args = array(
                'timeout' => 5,
                'connect_timeout' => 5,
                'httpversion' => '1.1',
                'method' => $method,
                'headers' => $this->headers,
            );

            if ($method === 'POST' && $body !== '') {
                $args['body'] = json_encode($body);
            }

            $request = wp_remote_request($this->uri . $url, $args);
            $response = $this->process_response($request);
        } catch (Mulberry_Exception $e) {
            $response['message'] = $e->getMessage();
        }

        if (!$response['is_successful']) {
            $this->log_error_response($response, $url);
        }

        return $response;
    }

    /**
     * @param string $header
     * @param string|null $value
     */
    public function set_header($header, $value = null)
    {
        if (!$value) {
            unset($this->headers[$header]);

            return;
        }

        $this->headers[$header] = $value;
    }

    /**
     * Process the response and return an array
     *
     * @param $response
     * @return array
     * @throws Mulberry_Exception
     */
    private function process_response($response)
    {
        $result = array(
            'is_successful' => true,
        );

        if ($response['response']['code'] !== 200) {
            throw new Mulberry_Exception(
                sprintf('API Request Failed, code: %s, message: %s', $response['response']['code'], $response['response']['message'])
            );
        }

        if ($response['body'] && $response['body'] !== '') {
            $result['message'] = json_decode($response['body'], true);
        }

        return $result;
    }

    /**
     * Log request/response information
     *
     * @param $request
     * @param $response
     * @param $url
     */
    private function log_error_response($response, $url)
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $data = array(
                'response' => $response,
                'url' => $url,
            );

            error_log(print_r($data, true));
        }
    }
}

new WC_Mulberry_Api_Rest_Service();
