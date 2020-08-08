<?php


class DB
{


    public static $con;

    public static function connect($hostname , $username , $password , $database)
    {
        static::$con = mysqli_connect($hostname , $username , $password , $database) or die('Error: '.mysqli_connect_error());
    }

    public static function disconnect()
    {
        mysqli_close(static::$con);
    }

    public static function insert($sql , $values)
    {
        $values = self::safeValues($values);
        $sql = static::$con->prepare($sql);
        mysqli_set_charset(static::$con , 'UTF8');
        self::bindValues($sql , $values);
        $result = $sql->execute();
        
        return $result;
    }

    public static function select($sql , $values = null)
    {
        $values = self::safeValues($values);
        $sql = static::$con->prepare($sql);
        mysqli_set_charset(static::$con , 'UTF8');
        self::bindValues($sql , $values);
        $sql->execute();
        $result = $sql->get_result();
        
        return $result;
    }

    public static function update($sql , $values)
    {
        $values = self::safeValues($values);
        $sql = static::$con->prepare($sql);
        mysqli_set_charset(static::$con , 'UTF8');
        self::bindValues($sql , $values);
        $result = $sql->execute();
        
        return $result;
    }

    public static function delete($sql , $values = null)
    {
        $values = self::safeValues($values);
        $sql = static::$con->prepare($sql);
        self::bindValues($sql , $values);
        $result = $sql->execute();
        
        return $result;
    }

    public function safeValues($values = null)
    {
        if($values != null)
        {
            for($i = 0 ; $i < count($values) ; $i++)
            {
                $values[$i] = trim($values[$i]);
                $values[$i] = strip_tags($values[$i]);
                mysqli_real_escape_string(self::$con , $values[$i]);
            }
        }
        
        return $values;
    }

    public function bindValues($sql, $values = null)
    {
        if ($values != null)
        {
            // Generate the Type String (eg: 'issisd')
            $types = '';
            foreach($values as $value)
            {
                if(is_int($value))
                    $types .= 'i';
                elseif (is_float($value))
                    $types .= 'd';
                elseif (is_string($value))
                    $types .= 's';
                else
                    $types .= 'b';
            }
            // Add the Type String as the first Parameter
            $bind_names[] = $types;
            // Loop thru the given Parameters
            for ($i = 0 ; $i < count($values) ; $i++)
            {
                // Create a variable Name
                $bind_name = 'bind'.$i;
                // Add the Parameter to the variable Variable
                $$bind_name = $values[$i];
                // Associate the Variable as an Element in the Array
                $bind_names[] = &$$bind_name;
            }
            // Call the Function bind_param with dynamic Parameters
            call_user_func_array(array($sql,'bind_param') , $bind_names);
        }
        
        return $sql;
    }


}