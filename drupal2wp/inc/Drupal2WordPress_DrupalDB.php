<?php
/*
	+=============================================================================================+
	|	PHP MYSQL ABSTRACTION CLASS									  					  		  |
	+=============================================================================================+
	|																							  |
	|	Available Functions:																	  |
	|																							  |
	|	* check																					  |
	|	* query																					  |
	|	* row																					  |
	|	* results																				  |
	|	* count																					  |
	|	* affected_rows																			  |
	|																							  |
	+=============================================================================================+
	|	Author:  Chris Ennis (https://github.com/dotrage)										  |
	+=============================================================================================+
*/


// Deny direct access
defined('ABSPATH') or die("No script kiddies please!");

define("DB_QUERY_REGEXP", "/(%d|%s|%%|%f|%b)/");

class Drupal2WordPress_DrupalDB {
    public function __construct($connection){
        if (!empty($connection['host']) && !empty($connection['username']) && !empty($connection['password']) && !empty($connection['database'])){
            $this->host = $connection['host'];
            $this->user = $connection['username'];
            $this->pass = $connection['password'];
            $this->name = $connection['database'];
            $this->port = null;
            $this->socket = null;
            if (!empty($connection['port'])){
                $this->port = $connection['port'];
            }
            if (!empty($connection['socket'])){
                $this->socket = $connection['socket'];
            }
        }
        else if (!empty($connection) && is_array($connection)){
            foreach ($connection as $c){
                if (!empty($c['host']) && !empty($c['username']) && !empty($c['password']) && !empty($c['database'])  && !empty($c['alias'])){
                    if (empty($c['port'])){
                        $c['port'] = null;
                    }
                    if (empty($c['socket'])){
                        $c['socket'] = null;
                    }

                    if (!empty($c['mode']) && ($c['mode'] == "r" || $c['mode'] == "w" || $c['mode'] == "rw")){
                        $mode = $c['mode'];
                        unset($c['mode']);
                        $this->connections[$mode][$c['alias']] = $c;
                    }
                    else{
                        unset($c['mode']);
                        $this->connections['rw'][$c['alias']] = $c;
                    }
                }
            }
        }
    }

    //Confirm the server is accessible
    public function check($connection=null){
        $port = $socket = null;
        if (!empty($connection)){
            if (!empty($this->connections['r'][$connection]['host']) && !empty($this->connections['r'][$connection]['username']) && !empty($this->connections['r'][$connection]['password']) && !empty($this->connections['r'][$connection]['database'])){
                if (!empty($this->connections['r'][$connection]['port'])){
                    $port = $this->connections['r'][$connection]['port'];
                }
                if (!empty($this->connections['r'][$connection]['socket'])){
                    $socket = $this->connections['r'][$connection]['socket'];
                }

                $dbc = @mysqli_connect($this->connections['r'][$connection]['host'],$this->connections['r'][$connection]['username'],$this->connections['r'][$connection]['password'],$this->connections['r'][$connection]['database'],$port,$socket);
                if (mysqli_connect_errno()){
                    return false;
                }
            }
            else if (!empty($this->connections['w'][$connection]['host']) && !empty($this->connections['w'][$connection]['username']) && !empty($this->connections['w'][$connection]['password']) && !empty($this->connections['w'][$connection]['database'])){
                if (!empty($this->connections['w'][$connection]['port'])){
                    $port = $this->connections['w'][$connection]['port'];
                }
                if (!empty($this->connections['w'][$connection]['socket'])){
                    $socket = $this->connections['w'][$connection]['socket'];
                }

                $dbc = @mysqli_connect($this->connections['w'][$connection]['host'],$this->connections['w'][$connection]['username'],$this->connections['w'][$connection]['password'],$this->connections['w'][$connection]['database'],$port,$socket);
                if (mysqli_connect_errno()){
                    return false;
                }
            }
            else if (!empty($this->connections['rw'][$connection]['host']) && !empty($this->connections['rw'][$connection]['username']) && !empty($this->connections['rw'][$connection]['password']) && !empty($this->connections['rw'][$connection]['database'])){
                if (!empty($this->connections['rw'][$connection]['port'])){
                    $port = $this->connections['rw'][$connection]['port'];
                }
                if (!empty($this->connections['rw'][$connection]['socket'])){
                    $socket = $this->connections['rw'][$connection]['socket'];
                }

                $dbc = @mysqli_connect($this->connections['rw'][$connection]['host'],$this->connections['rw'][$connection]['username'],$this->connections['rw'][$connection]['password'],$this->connections['rw'][$connection]['database'],$port,$socket);
                if (mysqli_connect_errno()){
                    return false;
                }
            }
        }
        else{
            if (!empty($this->host) && !empty($this->name) && !empty($this->user) && !empty($this->pass)){
                $dbc = @mysqli_connect($this->host,$this->user,$this->pass,$this->name,$port,$socket);
                if (mysqli_connect_errno()){
                    return false;
                }
            }
            else if (!empty($this->connections)){
                $output = array();
                foreach ($this->connections as $conns){
                    foreach ($conns as $k=>$v){
                        if (!empty($v['host']) && !empty($v['username']) && !empty($v['password']) && !empty($v['database'])){
                            $port = $socket = null;
                            if (!empty($v['port'])){
                                $port = $v['port'];
                            }
                            if (!empty($v['socket'])){
                                $socket = $v['socket'];
                            }

                            $dbc = @mysqli_connect($v['host'],$v['username'],$v['password'],$v['database'],$port,$socket);
                            if (mysqli_connect_errno()){
                                $output[$k] = 0;
                            }
                            else{
                                $output[$k] = 1;
                            }
                        }
                        else{
                            $output[$k] = 0;
                        }
                    }
                }

                return $output;
            }
            else{
                return false;
            }
        }
        return true;
    }


    // Opens a connection to the database and sets up mysql_query object
    public function query($sql){
        $mode = "r";
        $args = func_get_args();
        array_shift($args);

        if (isset($args[0]) and is_array($args[0])){
            if (!empty($args[1]) && count($args)==2){
                $mode = $args[1];
            }
            $args = $args[0];
        }

        $result = $this->run_query($sql,$args);

        return $result;
    }


    private function run_query($sql,$args=array(),$mode="r"){
        if (!empty($sql) && !is_array($sql)){
            $sql = array("query" => $sql);
        }

        if (is_string($args)){
            $args = func_get_args();
            array_shift($args);
        }

        if (!empty($sql['show_error'])){
            $show_error = $sql['show_error'];
        }
        else{
            $show_error = TRUE;
        }

        if (!empty($this->host) && !empty($this->name) && !empty($this->user) && !empty($this->pass)){
            $connection = array(
                "host" => $this->host,
                "username" => $this->user,
                "password" => $this->pass,
                "database" => $this->name,
                "port" => $this->port,
                "socket" => $this->socket
            );
        }
        else if (!empty($this->connections)){
            $arr1 = $arr2 = array();

            if ($mode == "r"){
                if (!empty($this->connections['r'])){
                    $arr1 = $this->connections['r'];
                }
                if (!empty($this->connections['rw'])){
                    $arr2 = $this->connections['rw'];
                }
                $arr3 = array_merge($arr1,$arr2);
            }
            else if ($mode == "w"){
                if (!empty($this->connections['w'])){
                    $arr1 = $this->connections['w'];
                }
                if (!empty($this->connections['rw'])){
                    $arr2 = $this->connections['rw'];
                }
                $arr3 = array_merge($arr1,$arr2);
            }

            if (!empty($arr3)){
                if (count($arr3)==1){
                    $connection = $arr3[0];
                }
                else{
                    $random = rand(0,count($arr3)-1);
                    $connection = $arr[$random];
                }
            }
        }


        if (empty($connection)){
            printf("Invalid Database Connection");
            exit;
        }

        $conn = @mysqli_connect($connection['host'],$connection['username'],$connection['password'],$connection['database'],$connection['port'],$connection['socket']);
        $this->conn = $conn;

        if (mysqli_connect_errno()){
            printf("Connect failed: %s\n", mysqli_connect_error());
            exit;
        }

        $this->string_sanitize($args, TRUE);
        $query = preg_replace_callback(DB_QUERY_REGEXP, array(&$this, 'string_sanitize'), $sql['query']);
        mysqli_query($conn,"SET NAMES 'utf8'");
        mysqli_query($conn,"SET SQL_BIG_SELECTS=1 ");
        $result = mysqli_query($conn, $query);

        if (!$result){
            if ($show_error){
                echo "Error querying database. : $query<br/>";
            }
        }
        else{
            return $result;
        }
    }

    // Executes query and filters results into a single array or object
    public function row($sql){
        if (!empty($sql['output'])){
            if ($sql['output'] == "array" || $sql['output'] == "object"){
                $type = $sql['output'];
            }
            else{
                $type = "array";
            }
        }
        else{
            $type = "array";
        }

        $args = func_get_args();
        array_shift($args);

        if (isset($args[0]) and is_array($args[0])){
            $args = $args[0];
        }

        $result = $this->run_query($sql,$args);

        if ($result){
            $row = mysqli_fetch_assoc($result);
            if ($type == "array"){
                return $row;
            }
            if ($type == "object"){
                return (object) $row;
            }
        }
    }

    // Executes query and filters results into an array or object
    public function results($sql){
        if (!empty($sql['output'])){
            if ($sql['output'] == "array" || $sql['output'] == "object"){
                $type = $sql['output'];
            }
            else{
                $type = "array";
            }
        }
        else{
            $type = "array";
        }

        $args = func_get_args();
        array_shift($args);

        if (isset($args[0]) and is_array($args[0])){
            $args = $args[0];
        }

        $result = $this->run_query($sql,$args);

        $return = array();

        while ($row = mysqli_fetch_assoc($result)){
            if ($type == "array"){
                $return[] = $row;
            }
            if ($type == "object"){
                $return[] = (object) $row;
            }
        }

        if ($result){
            if ($type == "array"){
                return $return;
            }
            if ($type == "object"){
                return (object) $return;
            }
        }
    }

    // Executes query and returns row count for result set
    public function count($sql){

        $args = func_get_args();
        array_shift($args);

        if (isset($args[0]) and is_array($args[0])){
            $args = $args[0];
        }

        $result = $this->run_query($sql,$args);

        if ($result){
            $row = mysqli_num_rows($result);
            return $row;
        }

        return 0;
    }

    // Executes query and returns affected row count for result set
    public function affected_rows($sql){

        $args = func_get_args();
        array_shift($args);

        if (isset($args[0]) and is_array($args[0])){
            $args = $args[0];
        }

        $result = $this->run_query($sql,$args);

        if ($result){
            $row = mysqli_affected_rows();
            return $row;
        }
    }

    private function string_sanitize($match,$init=FALSE){
        static $args = NULL;
        if ($init){
            $args = $match;
            return;
        }

        switch ($match[1]){
            case '%d':
                return (int) array_shift($args); // We don't need db_escape_string as numbers are db-safe
            case '%s':
                return mysqli_real_escape_string($this->conn,array_shift($args));
            case '%%':
                return '%';
            case '%f':
                return (float) array_shift($args);
            case '%b': // binary data
                return mysqli_real_escape_string($this->conn,array_shift($args));
        }
    }
}
