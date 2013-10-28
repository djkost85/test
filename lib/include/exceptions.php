<?php
/** @ingroup Exceptions
 */
class HandlerException extends Exception {};

/** @ingroup Exceptions
 */
class SecurityException extends Exception {};
/** @ingroup Exceptions
 */
class ParameterException extends Exception {
    
    /**
     * Checks whether the supplied value is a number
     * @param string $value The string to check
     * @param string $name The name to include in the exception
     * @return boolean returns true if value is a number, false if not.
     */
    static function checkNumber($value,$name)
    {
        // What about negative numbers??
        if(is_numeric($value))
            return true;

       throw new ParameterException("$name ($value) is not a number");
    }
    
     /**
     * Checks whether the supplied value is a number greater than 0
     * @param string $value The string to check
     * @param string $name The name to include in the exception
     * @return boolean returns true if value is a number and is greater than 0, otherwise throws ParameterException
     */
    static function checkPositiveNumber($value,$name)
    {
        
        if(is_numeric($value) && $value>0)
            return true;
       throw new ParameterException("$name ($value) not a number or must be greater than 0");
    }

      /**
     * Checks whether the supplied value is a number greater than or equal to 0
     * @param string $value The string to check
     * @param string $name The name to include in the exception
     * @return boolean returns true if value is a number and is not negative than 0, otherwise throws ParameterException
     */
    static function checkNotNegativeNumber($value,$name)
    {

        if(is_numeric($value) && $value>=0)
            return true;
       throw new ParameterException("$name ($value) not a number or must be greater than or equal to 0");
    }

    /**
     * Checks that the supplied string isn't empty whitespace
     * @param string $value The string to check
     * @param string $name The name to include in the exception
     * @return boolean true if ok, otherwise throws ParameterException
     */
    static function checkString($value,$name)
    {
        if(trim($value)!='')
            return true;
       throw new ParameterException("$name must be not be an empty string or contain only whitespace");
    }
    /**
     * Check that the value is a not an empty string or only white space,
     * escape for SQL safe characters
     *
     * @param string $value The string to check
     * @param string $name The name to include in the exception
     * @param dbTool $db The database connection object to handle the SQL escape
     * @return string SQL safe version
     */
    static function checkStringEsc($value,$name,$db)
    {
        if(self::checkString($value,$name))
            return $db->escape($value);
    }

    /**
     * Check that the comma separated list of numbers provided is not empty and
     * contains only positive values.
     *
     * @param string $list List of numbers, separated by commas
     * @param string $name The name to include in the exception
     * @return integer return the number of items in the list that are greater 0, otherwise throws ParameterException
     */
    static function checkPostiveNumberList($list,$name)
    {
        $numbers = explode(',',$list);
        $count = count($numbers);
        
        if($count<=0)
            throw new ParameterException("$name set must contain one or more positive numbers, $list ");

        foreach($numbers as $id)
        {
            ParameterException::checkPositiveNumber($id, $name);
        }

        return $count;
    }

    /**
     * Check that the array of numbers provided is not empty and
     * contains only positive values.
     *
     * @param array $list Array of numbers
     * @param string $name The name to include in the exception
     * @return integer return the number of items in the list that are greater 0, otherwise throws ParameterException
     */
    static function checkPostiveNumberArray($list,$name)
    {

        $count = count($list);

        if($count<=0)
            throw new ParameterException("$name set must contain one or more positive numbers, ".implode(",",$list));

        foreach($list as $id)
        {
            ParameterException::checkPositiveNumber($id, $name);
        }

        return $count;
    }

    /**
     * Check that the comma separated list of numbers provided is not empty and
     * contains only values greater than or equal to 0 - eg 23,54,23
     *
     * @param string $list List of numbers, separated by commas
     * @param string $name The name to include in the exception
     * @return integer return the number of items in the list that are greater than or equal to 0, otherwise throws ParameterException
     */
    static function checkNotNegativeNumberList($list,$name)
    {
        $numbers = explode(',',$list);
        $count = count($numbers);

        if($count<=0)
            throw new ParameterException("$name set must contain one or more positive numbers, $list ");

        foreach($numbers as $id)
        {
            ParameterException::checkNotNegativeNumber($id, $name);
        }

        return $count;
    }
};


?>
