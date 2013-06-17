<?php

// ZQL 2.1

class zql {
	private $utils = array(); protected $conn; public $last_query;
	/**
	 * Construct new ZQL object with new zql( params );
	 *
	 * @param string $user					The username for the MySQL Connection
	 * @param string $password				The password for the MySQL Connection
	 * @param string optional $database		The database for the MySQL Connection (mysql_select_db()) if different than $user
	 * @param string optional $host			The address for the MySQL Connection if different from localhost
	 *
	 * @example new zql("myzql", "password");
	 * @example new zql("myzql", "password", "myzql_database");
	 * @example new zql("myzql", "password", "myzql_database", "127.0.0.1");
	 *
	 * @author z43 Studio Inc.
	 **/
	function __construct($user, $password, $database='', $host='localhost') {
		$this->utils['user'] = $user;
		$this->utils['password'] = $password;
		$this->utils['database'] = (empty($database))?$user:$database;
		$this->utils['host'] = $host;
		$this->conn = new mysqli($host, $user, $password, $this->utils['database']);
	}
	function __destruct() {
		$this->conn->close();
	}
	/**
	 * Check if connected to MySQL Server
	 *
	 * @return bool		Whether a connection to the server is active or false
	 *
	 * @author z43 Studio Inc.
 	**/
	public function isConnected() {
		return $this->conn->ping();
	}
	/**
	 * Run a safe query with sprintf(); and escape string arguments
	 *
	 * @param string			The base for the sprintf command
	 * @param mixed optional	The data for the sprintf command (if any)
	 *
	 * @return mysqli_result or bool	This value is saved in $this->last_query and used in other arguments unless specified
	 *
	 * @example $zql->query("SELECT * FROM `table` WHERE `id` = %d LIMIT 1;", 39);
	 * @example $zql->query("UPDATE FROM `table` SET `name` = '%s' WHERE `id` = %d LIMIT 1;", $unescaped_name, 39);
	 *
	 * @author z43 Studio Inc.
	 **/
	public function query() {
		$args = func_get_args();
		for ($i=1; $i < count($args); $i++) $args[$i] = $this->escape($args[$i]);
		$spr = call_user_func_array('sprintf', $args);
		$this->last_query = $this->conn->query($spr);
		return $this->last_query;
	}
	/**
	 * Run an unsafe query with sprintf(); and unescaped string arguments
	 *
	 * @param string			The base for the sprintf command
	 * @param mixed optional	The data for the sprintf command (if any)
	 *
	 * @return mysqli_result or bool	This value is saved in $this->last_query and used in other arguments unless specified
	 *
	 * @example $zql->unsafeQuery("SELECT * FROM `table` WHERE %s;", $filters);
	 *
	 * @author z43 Studio Inc.
	 **/
	public function unsafeQuery() {
		$args = func_get_args();
		for ($i=1; $i < count($args); $i++) $args[$i] = $args[$i];
		$spr = call_user_func_array('sprintf', $args);
		$this->last_query = $this->conn->query($spr);
		return $this->last_query;
	}
	/**
	 * Escape a string with the current server settings and connection
	 *
	 * @param string	The unescaped string to be escaped
	 *
	 * @return string	An escaped version of an unescaped string
	 *
	 * @example $zql->escape($_REQUEST['email']);
	 *
	 * @author z43 Studio Inc.
	 **/
	public function escape($string) {
		return $this->conn->real_escape_string($string);
	}
	/**
	 * Get the column of a specific row in the result of a query
	 *
	 * @param mixed $column						The column the row is in, same as the $column variable in mysql_result();
	 * @param id optional $row					The row to get the column data, or else the 0th row
	 * @param mysqli_result optional $query		The query to search for the information, otherwise using $this->last_query
	 *
	 * @return mixed							The information from $column at $row in $query's results
	 *
	 * @example $zql->cell("email");
	 * @example $zql->cell("email", 39);
	 * @example $zql->cell("email", 39, $query);
	 *
	 * @author z43 Studio Inc.
	 **/
	public function cell($column=0, $row=0, $query='') {
		if (empty($query)) $query = $this->last_query;
		
		$query->data_seek($row);
		$row = $query->fetch_array();
		//print_r($row); exit();
		return $row[$column];
	}
	// LEGACY SUPPORT
	public function result($c=0, $r=0, $q='') {
		$this->cell($c,$r,$q);
	}
	/**
	 * Get an array of data for a single row in a query
	 *
	 * @param id optional $row					The row to get the array of information, or else the 0th row
	 * @param mysqli_result optional $query		The query to search for the information, otherwise using $this->last_query
	 *
	 * @return array							The data from the $row in $query's results
	 *
	 * @example $zql->results();
	 * @example $zql->results(492);
	 * @example $zql->results(492, $query);
	 *
	 * @author z43 Studio Inc.
	 **/
	public function row($row=0, $query='') {
		if (empty($query)) $query = $this->last_query;
		
		$query->data_seek($row);
		return $query->fetch_array();
	}
	/**
	 * Get an array of rows' data in a query
	 *
	 * @param mysqli_result optional $query		The query to search for the information, otherwise using $this->last_query
	 *
	 * @return array							Array of each result with subarray of all available data for each row
	 *
	 * @example $zql->results();
	 *
	 * @author z43 Studio Inc.
	 **/
	public function results($query='') {
		if (empty($query)) $query = $this->last_query;
		
		$results = array();
		for ($i=0; $i < $this->rows($query); $i++) 
			$results[] = $this->row($i, $query);
		
		return $results;
	}
	/**
	 * Get the number of returned rows of a query or the most recent query
	 *
	 * @param mysqli_result optional $query		The query to get the number of results from
	 *
	 * @return int								The number of rows returned from a query
	 *
	 * @author z43 Studio Inc.
	 **/
	public function rows($query='') {
		if (empty($query)) $query = $this->last_query;
		
		return $query->num_rows;
	}
	/**
	 * Get the INSERT_ID of a the most recently inserted row to any table
	 *
	 * @return integer	The ID of the new row in auto_increment
	 *
	 * @example $zql->id();
	 *
	 * @author z43 Studio Inc.
	 **/
	public function id() {
		return $this->conn->insert_id;
	}
}
?>