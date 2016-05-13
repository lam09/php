<?php

/**
 * Contains filters for use in templates.
 *
 * @author e.szabo
 */
class Filters {
	/**
	 * Encapsulates all filters.
	 */
	public static function common($filter, $value)
    {
        if (method_exists(__CLASS__, $filter)) {
            $args = func_get_args();
            array_shift($args);
            return call_user_func_array(array(__CLASS__, $filter), $args);
        }
    }
	
	/**
	 * Formats a float number.
	 * @param float $number
	 * @return string
	 */
	public static function formatFloat($number)
	{
		return number_format($number, 2, ",", " ");
	}
	
	/**
	 * Formats a float number for SQL queries.
	 * @param float $number
	 * @return string
	 */
	public static function formatFloatSql($number)
	{
		return str_replace(',', '.', $number);
	}
}
