<?php
/**
 * Created by PhpStorm.
 * User: Gabriel
 * Date: 14/12/2018
 * Time: 10:46 AM
 */
namespace db;

final class SqlLogger
{
    private static $instance;

    public $data = [];

    public function append($data)
    {
        $this->data[]=$data;
    }

    /**
     * Replaces any parameter placeholders in a query with the value of that
     * parameter. Useful for debugging. Assumes anonymous parameters from
     * $params are are in the same order as specified in $query
     *
     * @param string $query The sql query with parameter placeholders
     * @param array $params The array of substitution parameters
     * @return string The interpolated query
     */
    public static function InterpolateQuery($query, $params) {

        # build a regular expression for each parameter
        foreach ($params as $key => $value) {

            if (is_null($value))
                $value =  'NULL';
            else
                $value = "'$value'";
            $query = preg_replace("/\?/", $value, $query, 1);

        }


        return $query;

        /*
        $keys = array();
        $values = $params;

        # build a regular expression for each parameter
        foreach ($params as $key => $value) {
            if (is_string($key)) {
                $keys[] = '/:'.$key.'/';
            } else {
                $keys[] = '/[?]/';
            }

            if (is_string($value))
                $values[$key] = "'" . $value . "'";

            if (is_array($value))
                $values[$key] = "'" . implode("','", $value) . "'";

            if (is_null($value))
                $values[$key] = 'NULL';
        }

        $query = preg_replace($keys, $values, $query);

        return $query;*/
    }



    private function __construct()
    {
    }

    function __wakeup()
    {
        return self::getInstance();
    }

    function __clone()
    {
        return self::getInstance();
    }


    public static function getInstance()
    {
        return self::$instance ?? self::$instance = new static();
    }

}