<?php
/**
 * @category Mulberry
 * @package Mulberry\Warranty
 * @author Mulberry <support@getmulberry.com>
 * @copyright Copyright (c) 2021 Mulberry Technology Inc., Ltd (http://www.getmulberry.com)
 * @license http://opensource.org/licenses/OSL-3.0 The Open Software License 3.0 (OSL-3.0)
 */

interface WC_Mulberry_Queue_Model_Interface
{
    const TABLE_NAME = 'woocommerce_mulberry_warranty_queue';

    public function load($id);

    public function get($column);

    public function set();

    public function save();

    public function delete();

    public function delete_by_id($id);

    public function find(array $filter, $condition = '=', $returnSingleRow = false);
}
