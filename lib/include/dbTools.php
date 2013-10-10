<?php

/**
 * Attempts to connect to a database, and if successful stores the connection in a variable.
 * @param mysqli $retVar The mysqli object to set, if any.
 * @param string $dbHost The hostname of the database.
 * @param string $user The account username.
 * @param string $pass The account password.
 * @param string $schema (optional) The default schema to set for this connection.
 * @param string $errorDescriptor (optional) The word to use in connection errors to describe this DB server.
 * @return bool True on connection success, false otherwise. 
 */
function tryConnect(&$retVar, $dbHost, $user, $pass, $schema="", $errorDescriptor ="the")
{
    $retVar = @new mysqli($dbHost,$user,$pass,$schema);
    
    if($retVar == null || mysqli_connect_errno() > 0) {
        error_log("DBTools - Can't connect to $errorDescriptor MySQL server $dbHost. Error msg: ".mysqli_connect_error());
        error_log("DBTools - $user, $pass, $dbHost");
        $retVar = null;
        return false;
    } else return true;
}

class dbTool
{
    private $simpleProfile=false;    // enable simple profile output to error_log
    
    /**
    * @var mysqli the database object
    */
    private $mysqli;
    
    /**
     * @var mysqli A read-only database connection (if the primary is read-write).
     *             Designed to address potential concurrency/scalability issues.
     */
    private $mysqli_ro;

    /**
     * @var string Primary Read Only DB server
     */
    private $ROHost1="ec2-54-200-3-111.us-west-2.compute.amazonaws.com";
    /**
     *
     * @var string Backup Read Only DB server
     */
    private $ROHost2="ec2-54-200-3-111.us-west-2.compute.amazonaws.com";
    /**
     *
     * @var string Primary Read and Write DB server
     */
    private $RWHost1="ec2-54-200-3-111.us-west-2.compute.amazonaws.com";
    /**
     *
     * @var string Backup Read and Write DB server
     */
    private $RWHost2="ec2-54-200-3-111.us-west-2.compute.amazonaws.com";

    /**
     *
     * @var integer the total number of rows returned from a query
     */
	public $total_rows;
	public $pqp=null;			// PHP Quick Profiler obj
	public $queryCount=0;		
	public $queries=array();	// query log
	
    /**
     *
     * @var boolean whether the DB object is connected
     */
    public $isConnected = false;
    /**
     *
     * @var boolean whether the DB object can write to the DB.
     */
    public $isWriteable = false;

    private $singleServerMode=false;
    
    /** 
     * Simple Profiling variables
     *  
     */
    private $createdTime; // time in micro seconds when this object is created
    private $totalQueryTime; // time in micro seonds spend running queries
    
    /**
     * Construct a DBTool object
     * 
     * @param boolean $singleServerMode  Select whether a writable DB connection should also connect to a different Read Only server
     */
	public function __construct($singleServerMode=false)
	{
            $this->pqp=null; // initialise DB logging object
            $this->singleServerMode=$singleServerMode;
            
            if($this->simpleProfile)
                $this->createdTime=microtime(true);
	}
	
        function __destruct()
        {
            // Calculate simple query profile
            if($this->simpleProfile)
            {
                $now = microtime(true);
                $diff = round($now - $this->createdTime,3);
                if(PHP_SAPI==="cli")
                {
                    $self = $_SERVER['PHP_SELF'];
                    $d = '['.date("Y-m-d H:i:s").'] ';
                }
                else
                {
                    $self = "http://{$_SERVER[HTTP_HOST]}{$_SERVER[REQUEST_URI]}";
                    $d = '';
                }
                       
                error_log("{$d}DBTool Profile: num queries={$this->queryCount} queryTime=".round($this->totalQueryTime,3)." objTime=$diff - for $self ");
            }  
        }
    
    private $_mysqli_last = null; // pointer to the last mysql object used to run a query - holds error message.
    
    /**
     *
     * @param boolean $writeable if false will allow read only access to database
     * @param string $account choose a different account to access the DB (autoencoder,livestream,dvbcapture,stats,austar,dropbox,etc...)
     * @param string $host the database host to use, defaults to 10.2.1.223 which is macsvdb1.switch.internal
     *
     * @throws Exception on database connection fail
     */
	function connectContent($writeable,$account="",$host="")
	{	
	    $user = "remoteroot";
            $pass = "6D0KQN@";
            $schema = 'switched_on';

        if($this->ROHost1==$this->RWHost1)
        {
            // The slave is the same as the master, so don't connect to both of them
            // ie don't connect to the master twice
            //error_log("Forcing single server mode");
            $this->singleServerMode=true;
        }
        
        // Just connect to the specified host (and force read-only operations to do the same).
        if(strlen($host)>0 ) // also treat the 
        {
            if(tryConnect($this->mysqli, $host, $user, $pass, $schema, "specific"))
            {
                $this->isConnected=true;
                $this->isWriteable=true;
                $this->mysqli_ro = $this->mysqli;
                return;
            }
            else
                throw new Exception("Can't connect to specific DB Server $host. Error msg: ". mysqli_connect_error());
            
        }
        
        if(!$writeable || !$this->singleServerMode) // if read only or dual server mode
        {    
            // Connect in read only mode - easy.
            // connect to Read only DB
            if(!tryConnect($this->mysqli_ro, $this->ROHost1, $user, $pass, $schema, "primary Read only"))
            {
                if(!tryConnect($this->mysqli_ro, $this->ROHost2, $user, $pass, $schema, "Secondary Read only"))
                {
                    throw new Exception("Can't connect to any Read only DB Servers  $this->ROHost1 or  $this->ROHost2. Error msg: ". mysqli_connect_error());
                }
            }
        }
        
        if(!$writeable)  
        {
            // if only Read access is needed the we're done, 
            $this->mysqli = $this->mysqli_ro; // copy connection
            $this->isConnected=true;
            $this->isWriteable=false;
            return;
        }
        
        // connect to writable
        if(!tryConnect($this->mysqli, $this->RWHost1, $user, $pass, $schema, "primaryRead Write"))
        {
            if(!tryConnect($this->mysqli, $this->RWHost2, $user, $pass, $schema, "Secondary Read Write"))
            {
                throw new Exception("Can't connect to any Read only DB Servers  $this->RWHost1 or  $this->RWHost2. Error msg: ". mysqli_connect_error());
            }
        }
        
        if($this->singleServerMode) // in single server mode, copy connection
            $this->mysqli_ro = $this->mysqli;
        
        $this->isConnected=true;
        $this->isWriteable=true;
        return;
	}

    /**
     * Closes the mysqli connection
     */
	function close()
	{
		$this->mysqli->close();
        
        if($this->mysqli !== $this->mysqli_ro)
        {
            $this->mysqli_ro->close();
        }
        
        $this->isConnected = false;
	}

    /**
     * A generic SQL query wrapper function to centralise logging.
     * @param string $sql the SQL query to be run. Please make sure this query has been sanitised!
     * @param bool $readOnly Whether to use the read-only DB connection handle.
     * @param bool $buffered Whether to buffer the mysqli result.
     * @return object A mysqli results object.
     */
	function doQuery($sql, $readOnly = false, $buffered = true)
	{
                if($this->pqp!=null)
                    $start = $this->pqp->getMicroTime();

                if($this->simpleProfile)
                    $qStart = microtime(true);
                
                if($readOnly && $this->mysqli_ro !== null) // use read-only connection
                {
                    $result = $this->mysqli_ro->query($sql, ($buffered ? MYSQLI_STORE_RESULT : MYSQLI_USE_RESULT));
                    $this->_mysqli_last=$this->mysqli_ro;
                }
                else
                {
                    $result = $this->mysqli->query($sql, ($buffered ? MYSQLI_STORE_RESULT : MYSQLI_USE_RESULT));
                    $this->_mysqli_last=$this->mysqli;
                }
                //echo "doQuery: $sql\n\n";
         
                if($this->simpleProfile)
                {
                    $qEnd = microtime(true);
                    $diff = $qEnd - $qStart;
                    //error_log($diff."\n");
                    $this->totalQueryTime += $diff;
                }
                
                $this->queryCount += 1;
		if($this->pqp!=null)
		{
                    $this->logQuery($sql, $start);
		}
		return $result;
	}
    
    /**
     * Runs a query which should be a REPLACE query. It will return the affected rows.
     * 1 means the value wasn't there before, higher means rows were deleted and then inserted
     *
     * @param string $sql the SQL query to be run. Please make sure this query has been sanitised!
     * @return integer The affected row count.
     *
     * @throws Exception a string containing the mysql error
     */
	function replace($sql)
	{
        
            if(stripos($sql,"REPLACE")===false)
            {
                error_log("Not a replace query: ".$sql."  ".$this->mysqli->error);
                array_walk(debug_backtrace(),create_function('$a,$b','error_log("{$a[\'function\']}()(".basename($a[\'file\']).":{$a[\'line\']}); ");'));
            }
        
            $err = $this->doQuery($sql);
            if(!$err)
            {
                    throw( new Exception("Query failed! $sql ".$this->_mysqli_last->error));
            }

            return $this->mysqli->affected_rows;
	}
	
    /**
     * Runs a query which should be an INSERT query, as it will return the MySQL insert ID.
     *
     * @param string $sql the SQL query to be run. Please make sure this query has been sanitised!
     * @return integer the MySQL insert ID
     *
     * @throws Exception a string containing the mysql error
     */
	function insert($sql)
	{
        
        if(stripos($sql,"INSERT")===false && stripos($sql,"REPLACE")===false)
        {
            error_log("Not an insert or replace query: ".$sql."  ".$this->mysqli->error);
            array_walk(debug_backtrace(),create_function('$a,$b','error_log("{$a[\'function\']}()(".basename($a[\'file\']).":{$a[\'line\']}); ");'));
        }
        
		$err = $this->doQuery($sql);
		if(!$err)
		{
			throw( new Exception("Query failed! $sql ".$this->_mysqli_last->error));
		}
		
		return $this->_mysqli_last->insert_id;
	}

    /**
     * Runs a query which should be an UPDATE query, as it will return the MySQL affected rows.
     *
     * @param string $sql the SQL query to be run. Please make sure this query has been sanitised!
     * @return integer the MySQL affected rows
     *
     * @throws Exception a string containing the mysql error
     */
	function update($sql)
	{

        if(stripos($sql,"UPDATE")===false)
        {
            error_log("Not an update query: ".$sql."  ".$this->mysqli->error);
		array_walk(debug_backtrace(),create_function('$a,$b','error_log("{$a[\'function\']}()(".basename($a[\'file\']).":{$a[\'line\']}); ");'));

        }
        
		if(!$this->doQuery($sql))
		{
			throw( new Exception("Query failed! $sql ".$this->_mysqli_last->error));
		}
		return $this->mysqli->affected_rows;
	}

    /**
     * Runs a query and does not return any results
     *
     * @param string $sql the SQL query to be run. Please make sure this query has been sanitised!
     * @param boolean $forceMaster force the query to run on the writable Master DB connection, disabled by default.
     * 
     * @throws Exception a string containing the mysql error
     */
	function query($sql,$forceMaster=false)
	{
		if(!$this->doQuery($sql, !$forceMaster))
		{
			throw( new Exception("Query failed! $sql ".$this->_mysqli_last->error));
		}
	}
    
    /**
     * Runs a delete query and returns the number of affected rows
     *
     * @param string $sql the SQL query to be run. Please make sure this query has been sanitised!
     * @return integer the MySQL affected rows
     * 
     * @throws Exception a string containing the mysql error
     */
	function delete($sql)
	{
        if(stripos($sql,"DELETE")===false)
        {
            error_log("Not a delete query: ".$sql."  ".$this->mysqli->error);
		array_walk(debug_backtrace(),create_function('$a,$b','error_log("{$a[\'function\']}()(".basename($a[\'file\']).":{$a[\'line\']}); ");'));

        }
        
		if(!$this->doQuery($sql))
		{
			throw( new Exception("Query failed! $sql ".$this->_mysqli_last->error));
		}
        return $this->mysqli->affected_rows;
	}
	
    /**
     * Runs a query and returns a single row as an enumerated array.
     *
     * @param string $sql the SQL query to be run. Please make sure this query has been sanitised!
     * @return array enumerated array containing the results
     *
     * @throws Exception if there are no results returned
     * @throws Exception if there are more than 1 rows returned
     * @throws Exception if there is a MySQL error
     */
	function getSingleRow($sql)
	{
		if($result = $this->doQuery($sql, true))
		{
			if($result->num_rows == 0)
			{
				throw new Exception(sprintf("Failed! Couldn't find result. '%s'",$sql));
				
			}
			else if($result->num_rows > 1)
			{
				throw new Exception(printf("Failed! Duplicate results(%d) found for '%s'",$result->num_rows,$sql));
			}
			$row = $result->fetch_row();
			$result->close();
			return $row;
		}
		else
		{
			throw( new Exception("Query failed! $sql ".$this->_mysqli_last->error));
		}
		return null;
	}

    /**
     * Runs a query and returns a single row as an associative array.
     *
     * @param string $sql the SQL query to be run. Please make sure this query has been sanitised!
     * @return array associative array containing the results
     *
     * @throws Exception if there are no results returned
     * @throws Exception if there are more than 1 rows returned
     * @throws Exception if there is a MySQL error
     */
	function getSingleRowAssoc($sql)
	{
		if($result = $this->doQuery($sql, true))
		{
			if($result->num_rows == 0)
			{
				throw new Exception(sprintf("Failed! Couldn't find result. '%s'",$sql));
				
			}
			else if($result->num_rows > 1)
			{
				throw new Exception(printf("Failed! Duplicate results(%d) found for '%s'",$result->num_rows,$sql));
			}
			$row = $result->fetch_assoc();
			$result->close();
			return $row;
		}
		else
		{
			throw( new Exception("Query failed! $sql ".$this->_mysqli_last->error));
		}
		return null;
	}

    /**
     * Runs a query and returns a single row as an associative array.
     *
     * @param string $sql the SQL query to be run. Please make sure this query has been sanitised!
     * @return array associative array containing the results
     * 
     */
	function getSingleRowAssocNoEx($sql)
	{
            if($result = $this->doQuery($sql, true))
            {
                if($result->num_rows == 0)
                {
                        return null;

                }
                else if($result->num_rows > 1)
                {
                        return null;
                }
                $row = $result->fetch_assoc();
                $result->close();
                return $row;
            }
            else
            {
                throw( new Exception("Query failed! $sql ".$this->_mysqli_last->error));
            }
            return null;
	}

	/**
	 * Execute the supplied SQL statement and return the resuls and a mysqli_result object
	 * @param String $sql SQL Statement
	 * @param Booleam $calcRows Flag to determine whether the total rows should be calculate for queries with a SQL_CAL_FOUND_ROWS and LIMIT clause
	 * @return mysqli_result Query results
	 */
	function getResults($sql,$calcRows=false)
	{
		if($result = $this->doQuery($sql, true))
		{
			if($calcRows)
			{
				$rows = $this->doQuery("SELECT FOUND_ROWS() AS 'found_rows';",true);
				$rows = $rows->fetch_assoc();
				$this->total_rows = $rows['found_rows'];
			}
			
			return $result;
		}
		else
			throw( new Exception("Query failed! $sql ".$this->_mysqli_last->error));
	}

    /**
     * Runs a query and returns a multiples row as an multidimensional associative array.
     *
     * @param string $sql the SQL query to be run. Please make sure this query has been sanitised!
     * @param Booleam $calcRows Flag to determine whether the total rows should be calculate for queries with a SQL_CAL_FOUND_ROWS and LIMIT clause
     * @return array associative array containing the results
     *
     */
	function getMultiDimensionalArray($sql,$calcRows=false,$forceMaster=false)
	{		
            $data = array();
            if($result = $this->doQuery($sql, !$forceMaster))
            {

                    $data = self::convertResultsToHashtable($result);
                    if($calcRows)
                    {
                            $rows = $this->doQuery("SELECT FOUND_ROWS() AS 'found_rows';",!$forceMaster);
                            $rows = $rows->fetch_assoc();
                            $this->total_rows = $rows['found_rows'];
                    }
                    $result->close();
            }
            else
                    throw( new Exception("Query failed! $sql ".$this->_mysqli_last->error));

            return $data;
	}

    /**
     * Takes a mysqli results object and iterates over it to convert it into a hash table
     * @param mysqli_result $results
     * @return array array containing results
     */
    function convertResultsToHashtable($results)  {
        $hash = array();
        $count = $results->num_rows;
        for($i=0;$i<$count;$i++)  {
             $hash[] = $results->fetch_assoc();
		}
        return $hash;
    }

    /**
     * Runs a query and returns a single value. Since this function calls getSingleRow
     * it will throw all the usual exceptions.
     *
     * @param string $sql the SQL query to be run. Please make sure this query has been sanitised!
     * @return mixed    the value of the first field of the row returned by the query.
     *
     * @throws Exception if there are no results returned
     * @throws Exception if there are more than 1 rows returned
     * @throws Exception if there is a MySQL error
     */
	function getSingleValue($sql)
	{
		$row = $this->getSingleRow($sql);
		//error_log("wtf: $row[0]");
		return $row[0];
	}

    /**
     * Runs a query and returns a single value. All exceptions are ignored.
     *
     * @param string $sql the SQL query to be run. Please make sure this query has been sanitised!
     * @return mixed    the value of the first field of the row returned by the query or empty string upon exception.
     *
     */
	function getSingleValueNoEx($sql)
	{
		try
		{
			$row = $this->getSingleRow($sql, true);
			return $row[0];
		}
		catch (Exception $e)
		{
			return "";
		}
	}
	
    /**
     * Runs a query and return an array of results, single column query returned as an array
     *
     * @param string $sql the SQL query to be run. Please make sure this query has been sanitised!
     * @return array enumerated array containing the single column results
     *
     */
	function getSingleValueArray($sql)
	{
		
		if($result = $this->doQuery($sql, true))
		{
			if($result->num_rows >= 0)
			{
				$vals = Array(); 
				for($i=0;$i< $result->num_rows;$i++)
				{
					$row=$result->fetch_array();
					$vals[] = $row[0];	
				}
				$result->close();
				
			}
			else
				return null;
		}
		else 
			return Array();
			
		return $vals;	
	}

    /**
     * Runs a query and return an associative array of results
     *
     * @param string $sql the SQL query to be run. Please make sure this query has been sanitised!
     * @param string $keyCol
     * @param string $valCol
     * @return array associative array containing the results
     *
     */
	function getArrayAssoc($sql,$keyCol,$valCol)
	{
		
		if($result = $this->doQuery($sql, true))
		{
			if($result->num_rows >= 0)
			{
				$vals = Array(); 
				for($i=0;$i< $result->num_rows;$i++)
				{
					$row=$result->fetch_assoc();
					$vals[$row[$keyCol]] = $row[$valCol];	
				}
				$result->close();
				
			}
			else
				return null;
		}
		else 
			return Array();
			
		return $vals;	
	}

    /*
     * Function to expose the MySQLi escape_string() function
     */
	function escape($str)
	{
		return $this->mysqli->real_escape_string($str);	
	}

     /* 
     * Function to ensure the parameter is a number
     */
	function isNum($var,$default=0)
	{
		$tmp = trim($var); 
		if(is_numeric($tmp))
			return $tmp;
        return $default;
	}

    /*
     * Function to expose the MySQLi stat() function
     */
	function stat()
	{
		return $this->mysqli->stat();
	}

    /*
     * Function to expose the MySQLi prepare() function
     */
    function prepare($sql) {
        return $this->mysqli->prepare($sql);
    }

    /*
     * Function to expose the MySQLi bind_param() function
     */
    function bind_param() {
        if(func_num_args() > 1) {
            $args = func_get_args();
            $params = array_shift($args);
            return $this->mysqli->bind_param($params,$args);
        } else {
            return false;
        }
    }
    
    /** 
     * Ping the current connection(s) and reconnect if they have closed due to 
     * timeout
     * 
     * By default the php.ini may not enable this. mysqli.reconnect = On
     */
    function ping()
    {
        $this->mysqli->ping();
        $this->mysqli_ro->ping();
    }
    
 	/**
 	 * PHP Quick Profiler DB logger
 	 */   
    function logQuery($sql, $start) 
    {
	    $query = array(
	        'sql' => $sql,
	        'time' => ($this->pqp->getMicroTime() - $start)*1000);
    	array_push($this->queries, $query);
	}
	
	function explain($sql)
	{
		return $this->mysqli->query($sql);	
	}

    function outputDownloadCSV($sql) {
        $results = $this->getResults($sql);
        $result_fields = $results->fetch_fields();
        $field_names = array_map(function($result_field) {
                    return $result_field->name;
                }, $result_fields);
        
        header('Expires: 0');
        header('Cache-control: private');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Description: File Transfer');
        header('Content-type: application/octet-stream');
        header('Content-Disposition: attachment; filename="Search_Results_' . $start_date . '_' . $end_date . '.csv"');

        $out = fopen('php://output', 'w');

        fputcsv($out, $field_names);

        while ($row = $results->fetch_assoc()) {
            fputcsv($out, array_values($row));
        }

        fclose($out);
    }
}
?>
