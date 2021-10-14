<?php
/**
 * @category Mulberry
 * @package Mulberry\Warranty
 * @author Mulberry <support@getmulberry.com>
 * @copyright Copyright (c) 2021 Mulberry Technology Inc., Ltd (http://www.getmulberry.com)
 * @license http://opensource.org/licenses/OSL-3.0 The Open Software License 3.0 (OSL-3.0)
 */

class WC_Mulberry_Logger
{
    const LOGGER_HANDLER = 'mulberry';

    /**
     * @var WC_Logger
     */
    private $logger;

    /**
     * @var bool $debug
     */
    private $debug = true;

    public function __construct()
    {
        $this->logger = new WC_Logger();
    }

    /**
     * @param $message
     */
    public function log($message)
    {
        if ($this->debug) {
            $this->logger->add(self::LOGGER_HANDLER, $message);
        }
    }
}
