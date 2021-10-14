<?php
/**
 * @category Mulberry
 * @package Mulberry\Warranty
 * @author Mulberry <support@getmulberry.com>
 * @copyright Copyright (c) 2021 Mulberry Technology Inc., Ltd (http://www.getmulberry.com)
 * @license http://opensource.org/licenses/OSL-3.0 The Open Software License 3.0 (OSL-3.0)
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * A custom queue model class.
 */
class WC_Mulberry_Queue_Model implements WC_Mulberry_Queue_Model_Interface
{
    /**
     * The primary key retrieved from the table.
     *
     * @var string
     */
    private $pk = 'id';

    /**
     * @var array
     */
    private $nullFields = [];

    /**
     * @var array
     */
    private $booleanFields = [];

    /**
     * @var array
     */
    private $numericFields = [];

    /**
     * @var WC_Mulberry_Logger
     */
    private $logger;

    public function __construct()
    {
        $this->logger = new WC_Mulberry_Logger();
    }

    private function get_table()
    {
        /** @var \wpdb $wpdb */
        global $wpdb;

        return $wpdb->prefix . self::TABLE_NAME;
    }

    /**
     * @param $data
     * @param false $set
     * @return mixed
     */
    private function transform($data, $set = false)
    {
        foreach ($this->nullFields as $field) {
            if (isset($data[$field]) && empty($data[$field])) {
                $data[$field] = null;
            }
        }

        foreach ($this->booleanFields as $field) {
            if (isset($data[$field]) && '' === $data[$field]) {
                unset($data[$field]);
            } elseif (isset($data[$field])) {
                $data[$field] = (bool)$data[$field] ? 1 : 0;
            }
        }

        if ($set) {
            return $data;
        }

        foreach ($this->numericFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = (int)$data[$field];
            }
        }

        return $data;
    }

    /**
     * Take the keys from the result array and add them to the Model.
     *
     * @param $array
     * @throws Exception
     */
    protected function applyKeys($array)
    {
        if (!is_object($array) && !is_array($array)) {
            throw new \Exception('$array must either be an object or an array.');
        }

        foreach ((array)$array as $key => $value) {
            trim($key);
            $this->$key = $value;

            if (in_array($key, $this->booleanFields, true)) {
                $this->$key = (bool)$value;
            } elseif (in_array($key, $this->numericFields, true)) {
                $this->$key = (int)$value;
            }
        }
    }

    /**
     * @param $id
     * @return $this|false
     * @throws Exception
     */
    public function load($id)
    {
        // Return empty model if the ID is not supplied.
        if (null === $id) {
            return $this;
        }

        global $wpdb;

        $result = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$this->get_table()} WHERE id = %d", $id)
        );

        /**
         * As this will always return an array of results
         * if you only want to return one record make the $returnSingleRow TRUE
         */
        if (count($result) > 0) {
            $this->applyKeys($result[0]);
        }

        return $this;
    }

    /**
     * @param $column
     * @return mixed
     */
    public function get($column)
    {
        return $this->$column ? $this->$column : null;
    }

    /**
     * @return false|void
     * @throws Mulberry_Exception
     */
    public function set()
    {
        $args = func_get_args();
        $count = func_num_args();

        if (!is_array($args[0]) && $count < 2) {
            throw new \Mulberry_Exception('The set method must contain at least 2 arguments: key and value. Or an array of data. Only one argument was passed and it was not an array.');
        }

        $key = $args[0];
        $value = !empty($args[1]) ? $args[1] : null;

        // Make sure we have a key.
        if (false === $key) {
            return false;
        }

        // If it's not an array, make it one.
        if (!is_array($key)) {
            $key = [$key => $value];
        }

        // Preprocess data.
        $key = $this->transform($key, true);

        // Save the items in this object.
        foreach ($key as $k => $v) {
            if (!empty($k)) {
                $this->$k = $v;
            }
        }

        return $this;
    }

    public function save()
    {
        $date = as_get_datetime_object();

        ActionScheduler_TimezoneHelper::set_local_timezone($date);
        $date_local = $date->format('Y-m-d H:i:s');

        /** @var \wpdb $wpdb */
        global $wpdb;

        $data = [
            $this->pk => $this->get('id'),
            'order_id' => $this->get('order_id'),
            'action_type' => $this->get('action_type'),
            'sync_status' => $this->get('sync_status'),
            'sync_date' => $date_local,
        ];

        return $wpdb->replace($this->get_table(), $data, ['%d', '%d', '%s', '%s', '%d']);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        /** @var \wpdb $wpdb */
        global $wpdb;

        return $wpdb->delete($this->get_table(), [$this->pk => $this->get('id')], ['%d']);
    }

    /**
     * @param array $filter
     * @param string $condition
     * @param false $returnSingleRow
     * @return false|mixed
     */
    public function find(array $filter = [], $condition = '=', $returnSingleRow = false)
    {
        global $wpdb;

        try {
            $sql = 'SELECT * FROM `' . $this->get_table() . '`';

            $conditionCounter = 1;

            if (count($filter) > 0) {
                $sql .= ' WHERE ';
            }

            foreach ($filter as $field => $value) {
                if ($conditionCounter > 1) {
                    $sql .= ' AND ';
                }

                switch (strtolower($condition)) {
                    case 'in':
                        if (!is_array($value)) {
                            throw new Exception("Values for IN query must be an array.", 1);
                        }

                        $sql .= $wpdb->prepare('`%s` IN (%s)', $field, implode(',', $value));
                        break;

                    default:
                        $sql .= $wpdb->prepare('`' . $field . '` ' . $condition . ' %s', $value);
                        break;
                }

                $conditionCounter++;
            }

            $result = $wpdb->get_results($sql);

            /**
             * As this will always return an array of results
             * if you only want to return one record make the $returnSingleRow TRUE
             */
            if (count($result) == 1 && $returnSingleRow) {
                $result = $result[0];
            }

            return $result;
        } catch (Exception $e) {
            $this->logger->log($e->getMessage());
            return false;
        }
    }
}
