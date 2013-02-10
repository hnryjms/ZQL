<?php

class zql {
	private $utils = array(); public $conn; public $last_query;
	/**
		 * Construct new ZQL object with new zql( params );
		 *
		 * @param string $user The username for the MySQL Connection
		 * @param string $password The password for the MySQL Connection
		 * @param string optional $database The database for the MySQL Connection (mysql_select_db()) if different from $user
		 * @param string optional $host  The address for the MySQL Connection if different from localhost
		 *
		 * @example new zql("myzql", "password");
		 * @example new zql("myzql", "password", "myzql_database");
		 * @example new zql("myzql", "password", "myzql_database", "127.0.0.1");
		 *
		 * @author z43 Studio Inc.
		 */
	function __construct($user, $password, $database='', $host='localhost') {
		$this->utils['user'] = $user;
		$this->utils['password'] = $password;
		$this->utils['database'] = (empty($database))?$user:$database;
		$this->utils['host'] = $host;
		try {
			$this->conn = new mysqli($host, $user, $password, $this->utils['database']);
			if ($this->conn) {
				if (!$this->conn->connect_errno) throw new Exception(mysqli_error());
			} else throw new Exception(mysqli_error());
		} catch (Exception $e) {
			return $e;
		}
	}
	function __destruct() {
		$this->conn->close();
	}
	/**
		 * Check if connected to MySQL Server
		 *
		 * @return bool Whether connected to the server or not
		 *
		 * @author z43 Studio Inc.
		 */
	function isConnected() {
		return !$this->conn->connect_errno;
	}
	/**
		 * Run a safe query with sprintf(); and escape string arguments
		 *
		 * @param string The base for the sprintf command to be ran from
		 * @param mixed The data for the sprintf command (if any)
		 *
		 * @return mysqli_result or bool and saved in $this->last_query and used in other arguments if not manually added
		 *
		 * @example $zql->query("SELECT * FROM `table` WHERE `id` = %d LIMIT 1;", 39);
		 * @example $zql->query("UPDATE FROM `table` SET `name` = '%s' WHERE `id` = %d LIMIT 1;", $unescaped_name, 39);
		 *
		 * @author z43 Studio Inc.
		 */
	function query() {
		$args = func_get_args();
		for ($i=1; $i < count($args); $i++) $args[$i] = $this->conn->real_escape_string($args[$i]);
		$spr = call_user_func_array('sprintf', $args);
		$this->last_query = $this->conn->query($spr);
		return $this->last_query;
	}
	/**
		 * Get the result of a query or the result of a query
		 *
		 * @param mixed $column The column the row is in, same as the $column variable in mysql_result();
		 * @param id optional $row The row to get the information from column or else the first row found
		 * @param mysqli_result optional $query The query to search for the information or else the most recent query from this zql class
		 *
		 * @return mixed The information from $column at $row in $query's results
		 *
		 * @example $zql->result("email");
		 * @example $zql->result("email", 39);
		 * @example $zql->result("email", 39, $query);
		 *
		 * @author z43 Studio Inc.
		 */
	function result($column=0, $row=0, $query='') {
		if (empty($query)) $query = $this->last_query;
		
		$query->data_seek($row);
		$row = $query->fetch_array();
		//print_r($row); exit();
		return $row[$column];
	}
	/**
	 * Get an array of data for a returned row of a query
	 *
	 * @param id optional $row The row to get the information or else the first row found
	 * @param mysqli_result optional $query The query to search for the information, or else the most recent query from this zql class
	 *
	 * @return array The data from the $row in $query's results
	 *
	 * @example $zql->results();
	 * @example $zql->results(492);
	 * @example $zql->results(492, $query);
	 *
	 * @author z43 Studio Inc.
	 **/
	function results($row=0, $query='') {
		if (empty($query)) $query = $this->last_query;
		
		$query->data_seek($row);
		return $query->fetch_array();
	}
	/**
		 * Get the number of returned rows of a query or the most recent query
		 *
		 * @param mysqli_result optional $query The query to get the number of results from
		 *
		 * @return int The number of rows returned from a query
		 *
		 * @author z43 Studio Inc.
		 */
	function rows($query='') {
		if (empty($query)) $query = $this->last_query;
		
		return $query->num_rows;
	}
	/**
	 * Get the INSERT_ID of a the most recently inserted row to any table
	 *
	 * @return integer The ID of the new row in auto_increment
	 *
	 * @example $zql->id();
	 *
	 * @author z43 Studio Inc.
	 **/
	function id() {
		return $this->conn->insert_id;
	}
}
?>