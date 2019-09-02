<?php
/*
Common function and variables for dynamic processing

 */

class Common_db
{

    private $servername; // last error
    private $database;
    private $username; // last error
    private $password;
    private $error; // last error
    private $debug;

    public function __construct( $servername, $database, $username, $password ,$debug = false )
    {
        $this->error = '';
        $this->debug = $debug;
        $this->servername = $servername;        
        $this->database = $database;        
        $this->username = $username;        
        $this->password = $password;
    }    


    /**
     * getLastError
     * return last error description
     *
     * @return    string
     */
    public function getLastError()
    {
        return ($this->error);
    }

    /**
     * setLastError
     * set last error description
     *
     * @param    string  $err
     * @return    string
     */
    public function setLastError($err)
    {
        $this->error = $err;
        return (true);
    }    

    public function dbConnect()
    {
        // Create connection

        $mysqli = mysqli_connect(
        $this->servername, 
        $this->username, 
        $this->password, 
        $this->database);

        // Check connection

        if ($mysqli->connect_errno) {
            $this->setLastError( $mysqli->connect_error );
            return (false);
        }
        return ($mysqli);
    }

    public function dbDisconnect($mysqli)
    {
        @$mysqli->close();
    }

    public function getRecords($mysqli)
    {
        $needDisconnect = false;
        if (!$mysqli) {
            $needDisconnect = true;
            $mysqli = $this->dbConnect();
        }
        if (!$mysqli) {
            $this->setLastError("Haven't connection to database");
            return (false);
        }
        $sql="select a.*, b.* from video_messages a, video_elements b where a.video_created=? 
        and a.video_id=b.video_id order by b.video_id, b.object_id";
        $records = $this->execSQL($dbConnection, $sql, array("i",0), false) ;
        if ($needDisconnect) {
            $this->dbDisconnect($mysqli);
        }
        return ($records);
    }

    public function updateVideoRecord($mysqli, $video_id)
    {
        $needDisconnect = false;
        if (!$mysqli) {
            $needDisconnect = true;
            $mysqli = $this->dbConnect();
        }
        if (!$mysqli) {
            return (false);
        }
        $sql="update video_messages set video_created=1 where video_id=?";
        if( !$this->execSQL($dbConnection, $sql, array("s", $video_id ), false) ) {
            return( false);
        }
        if ($needDisconnect) {
            $this->dbDisconnect($mysqli);
        }
        return (true);
    }


    public function execSQL($mysqli, $sql, $params, $close)
    {
        $results = array();
        if (!$stmt = $mysqli->prepare($sql)) {
            $this->setLastError("Failed to prepared the statement! $sql .Error:".$mysqli->error);
            return (false);
        }
        $ref = new ReflectionClass('mysqli_stmt');
        $method = $ref->getMethod("bind_param");
        $method->invokeArgs($stmt, $this->refValues($params));
        if (! $stmt->execute()) {
            $this->setLastError("Cannot execute prepared request! $sql .Error:".$mysqli->error);
            return (false);            
        }        


        if ($close) {
            $result = $mysqli->affected_rows;
        } else {
            $meta = $stmt->result_metadata();

            while ($field = $meta->fetch_field()) {
                $parameters[] =& $row[$field->name];
            }

            call_user_func_array(array($stmt, 'bind_result'), $this->refValues($parameters));

            while ($stmt->fetch()) {
                $x = array();
                foreach ($row as $key => $val) {
                    $x[$key] = $val;
                }
                $results[] = $x;
            }

            $result = $results;
        }

        $stmt->close();
        return $result;
    }

    function refValues($arr)
    {
        if (strnatcmp(phpversion(), '5.3') >= 0) { //Reference is required for PHP 5.3+
            $refs = array();
            foreach ($arr as $key => $value) {
                $refs[$key] =& $arr[$key];
            }
    
            return $refs;
        }
        return $arr;
    }    

}
