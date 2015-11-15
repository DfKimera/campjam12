<?php

/**
 * Diesel Framework
 * Copyright © LQDI Technologies - 2011
 * http://www.lqdi.net
 *
 * Serviço de abstração do banco de dados
 * Database Abstraction Layer
 *
 * @author Aryel 'DfKimera' Tupinambá
 *
 */

define('LDB_VERSION', 4);

define('LDB_ERROR_EXIT', 1);
define('LDB_ERROR_LOG', 2);
define('LDB_ERROR_EXCEPTION', 3);

class Database {

	/**
	 * Array of available connections
	 * @var array
	 */
	public static $connections = array();

	/**
	 * Should we log all queries in the system log?
	 * @var boolean
	 */
	public static $logQueries = false;

	/**
	 * ID of the current connection
	 * @var int
	 */
	public static $currentConnection = 0;

	private static $connectionIndex = 0;
	private static $errorMode = LDB_ERROR_EXCEPTION;

	/**
	 * Opens a new connection to the database
	 *
	 * @static
	 * @param string $hostname The database's hostname to use
	 * @param string $username The database's username
	 * @param string $password The database's password
	 * @param string $database The database's name
	 * @param integer $port The database's port
	 * @param string $table_prefix Prefix to use when accessing tables
	 * @param boolean $auto_switch Should we switch to this connection automatically?
	 * @param boolean $persistent Should this be a persistent connection?
	 * @param string $driver The driver type to use (options: sqlite, mysql, pgsql, odbc)
	 *
	 * @return mixed Either the connection's ID or false if something happend
	 */
	public static function connect($hostname, $username, $password, $database, $port = 3306, $table_prefix = "", $auto_switch = true, $persistent = true, $driver = "mysql") {
		$conn = array();

		$conn['hostname'] = $hostname;
		$conn['username'] = $username;
		$conn['password'] = $password;
		$conn['database'] = $database;
		$conn['port'] = $port;
		$conn['persistent'] = (bool) $persistent;
		$conn['table_prefix'] = $table_prefix or "";

		$conn['last_action'] = "Connect to {$hostname} as {$username} in database {$database} (Persistent mode: {$persistent})";
		$conn['last_query'] = NULL;

		try {
			if ($conn['persistent']) {
				$conn['connection'] = new PDO("$driver:host=$hostname;port=$port;dbname=$database", $username, $password, array(PDO::ATTR_PERSISTENT => true));
			} else {
				$conn['connection'] = new PDO("$driver:host=$hostname;port=$port;dbname=$database", $username, $password);
			}
		} catch (PDOException $e) {
			self::handleException($e);
			return false;
		}


		$conn['connection']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
		$conn['connection']->exec("SET CHARACTER SET utf8");

		$connID = self::$connectionIndex++;
		self::$connections[$connID] = $conn;
		self::$connections[$connID]['id'] = $connID;

		$error = self::checkErrors($connID);
		if ($error) {
			self::handleError($error);
			return false;
		}

		if ($auto_switch) {
			self::switchConnection($connID);
		}

		return $connID;
	}

	/**
	 * Shortcut command to Connect
	 *
	 * @static
	 * @param array $server Array with the proper details (hostname, username, password, database, port)
	 * @param string $table_prefix Prefix to use when accessing tables
	 * @param boolean $auto_switch Should we switch to this connection automatically?
	 * @param boolean $persistent Should this be a persistent connection?
	 * @param string $driver The driver type to use (options: sqlite, mysql, pgsql, odbc)
	 *
	 * @return mixed Either the connection's ID or false if something happend
	 */
	public static function quickConnect($server, $table_prefix = "", $auto_switch = true, $persistent = true, $driver = "mysql") {
		return self::connect($server['hostname'], $server['username'], $server['password'], $server['database'], $server['port'], $table_prefix, $auto_switch, $persistent, $driver);
	}

	/**
	 * Sets the way LDB should handle errors
	 *
	 * @static
	 * @param int $errorMode The error mode (LDB_ERROR_*
	 */
	public static function setErrorMode($errorMode) {
		self::$errorMode = $errorMode;
	}

	/**
	 * Checks for errors on a connection
	 *
	 * @static
	 * @param integer $connID The connection's ID
	 * @return mixed False if there's no error or an error string if there is
	 */
	private static function checkErrors($connID) {
		$conn = self::$connections[$connID];

		if (is_array($conn)) {
			$errInfo = $conn['connection']->errorInfo();
			$errMsg = $errInfo[2];
			if (strlen($errMsg) > 0) {
				$errCode = $conn['connection']->errorCode();
				return "MySQL Error (ConnID: {$connID}) #{$errCode} ('{$errMsg}') while performing the following action: {$conn['last_action']}";
			} else {
				return false;
			}
		} else {
			return false;
		}

	}

	/**
	 * Checks for errors on a query
	 *
	 * @static
	 * @param integer $connID The connection's ID
	 * @param PDOStatement $query Query object
	 * @return mixed False if there's no error or an error string if there is
	 */
	private static function checkQueryErrors($connID, $query) {
		$conn = self::$connections[$connID];

		$errInfo = $query->errorInfo();
		$errMsg = $errInfo[2];
		if (strlen($errMsg) > 0) {
			$errCode = $query->errorCode();
			return "MySQL Error (ConnID: {$connID}) #{$errCode} ('{$errMsg}')";
		} else {
			return false;
		}
	}

	/**
	 * Handles internal errors according to the current error mode
	 *
	 * @static
	 * @param $error
	 * @throws Exception
	 */
	private static function handleError($error) {

		switch(self::$errorMode) {

			case LDB_ERROR_EXIT:
				Log::Write("[LDB:ERROR] {$error}");
				error("LDB: {$error}");
				break;
			case LDB_ERROR_LOG:
				Log::Write("[LDB:ERROR] {$error}");
				break;
			case LDB_ERROR_EXCEPTION:
				throw new Exception($error);
				break;

		}

	}

	/**
	 * Handles internal exceptions (usually PDO) according to the current error mode
	 *
	 * @static
	 * @param Exception $e
	 * @throws Exception
	 */
	private static function handleException(Exception $e) {
		switch(self::$errorMode) {

			case LDB_ERROR_EXIT:
				Log::Write("[LDB:EXCEPTION] {$e->getMessage()}");
				Log::Object($e);

				error("LDB: {$e->getMessage()}");

				break;
			case LDB_ERROR_LOG:
				Log::Write("[LDB:EXCEPTION] {$e->getMessage()}");
				Log::Object($e);

				break;
			case LDB_ERROR_EXCEPTION:
				throw $e;
				break;

		}
	}

	/**
	 * Prepares an associative to be bound with PDO parameters
	 *
	 * @static
	 * @param $data
	 * @return array
	 */
	private static function getPDOInsertParameters($data) {

		$columnList = array();
		$valueList = array();

		$newData = array();

		foreach ($data as $column => $value) {
			$columnList[] = $column;
			$valueList[] = ":$column";

			$newData[":$column"] = $value;
		}

		$columns = join(" , ", $columnList);
		$values = join(" , ", $valueList);

		return array($newData, $columns, $values);

	}

	/**
	 * Prepares an associative to be bound with PDO parameters
	 *
	 * @static
	 * @param $data
	 * @param $glue
	 * @return array
	 */
	private static function getPDOParameters($data, $glue = ", ") {

		$pairs = array();
		$newData = array();

		foreach ($data as $column => $value) {
			array_push($pairs, "{$column} = :{$column}");
			$newData[":$column"] = $value;
		}

		$pairs = join($glue, $pairs);


		return array($newData, $pairs);

	}

	/**
	 * Switches to a different connection
	 *
	 * @static
	 * @param integer $connID The new connection's ID
	 */
	public static function switchConnection($connID) {
		self::$currentConnection = $connID;
	}

	/**
	 * Gets the current connection
	 *
	 * @static
	 * @return object The current connection
	 */
	public static function getCurrentConnection() {
		return self::$connections[self::$currentConnection];
	}

	/**
	 * Gets the last inserted row's ID
	 *
	 * @static
	 * @return integer The last inserted row's ID
	 */
	public static function lastInsertID() {
		return self::$connections[self::$currentConnection]['connection']->lastInsertId();
	}

	/**
	 * Executes a query
	 *
	 * @static
	 * @param string $sqlStatement The SQL string to execute
	 * @param mixed $data Additional data in case of PDO parameter binding
	 *
	 * @return PDOStatement The query object or false on failure
	 */
	public static function runQuery($sqlStatement, $data = null) {
		$conn = self::getCurrentConnection();

		if(self::$logQueries) {
			Log::Write("[LDB] Running query: [{$sqlStatement}]");
		}

		$query = $conn['connection']->prepare($sqlStatement);

		if ($data != NULL) {
			foreach ($data as $column => $value) {
				$query->bindParam($column, $value);
			}
		}

		$queryReturn = $query->execute($data);
		$conn['last_action'] = "Query: {$sqlStatement}";
		$conn['last_query'] = $query;

		$error = self::checkErrors($conn['id']);
		$error = (!$error) ? self::checkQueryErrors($conn['id'], $query) : false;

		if (!$error && $queryReturn) {
			return $query;
		} else {
			self::handleError($error);
			return false;
		}

	}

	/**
	 * Inserts a row into the database.
	 * Utilizes the current connection;
	 *
	 * @static
	 * @param string $table The table to utilize
	 * @param array $data An array of data
	 *
	 *  Example: array('name' => 'John Doe', 'title' => 'Test');
	 *
	 * @return int The ID of the new row
	 */
	public static function insert($table, $data) {
		$conn = self::getCurrentConnection();

		$table = $conn['table_prefix'] . $table;

		list($data, $columns, $values) = self::getPDOInsertParameters($data);

		$sqlStatement = "INSERT INTO {$table} ( {$columns} ) VALUES ( {$values} )";
		$query = self::runQuery($sqlStatement, $data);

		if ($query) {
			return self::lastInsertID();
		} else {
			return false;
		}
	}

	/**
	 * Updates a single row in the database.
	 * Utilizes the current connection;
	 *
	 * @param string $table The table to utilize
	 * @param array $data An array of data
	 *
	 *  Example: array('name' => 'John Doe', 'title' => 'Test');
	 *
	 * @param int $id The row's ID in the database (column 'id')
	 *
	 * @return boolean True if the operation was completed, false if an error has occurred.
	 */
	public static function update($table, $data, $id) {
		$conn = self::getCurrentConnection();

		$table = $conn['table_prefix'] . $table;
		$id = intval($id);

		list($data, $pairs) = self::getPDOParameters($data);

		$sqlStatement = "UPDATE {$table} SET {$pairs} WHERE `id` = {$id}";
		$query = self::runQuery($sqlStatement, $data);

		return (boolean) $query;
	}

	/**
	 * Updates multiple rows in the database, using the conditions specified.
	 * Utilizes the current connection.
	 *
	 * @param string $table The table to utilize
	 * @param array $data An array of data
	 *
	 * Example: array('name' => 'John Doe', 'title' => 'Test');
	 *
	 * @param array $conditions An array of conditions in SQL format
	 *
	 * Example: array("`contractID` = 15", "`value` > 5000");
	 *
	 * @param string $operator The junction of the conditional operator (AND, OR, etc.)
	 *
	 * @return boolean True if the operation was completed, false if an error has occurred.
	 */
	public static function updateIf($table, $data, $conditions, $operator = "AND") {
		$conn = self::getCurrentConnection();

		$table = $conn['table_prefix'] . $table;

		list($data, $pairs) = self::getPDOParameters($data);
		$conditions = join(" {$operator} ", $conditions);

		$sqlStatement = "UPDATE {$table} SET {$pairs} WHERE {$conditions}";
		$query = self::runQuery($sqlStatement, $data);

		return (boolean) $query;
	}

	/**
	 * Increases the value of a specific field.
	 * Utilizes the current connection.
	 *
	 * @param string $table The table to utilize
	 * @param string $field The field to increase the value of
	 * @param integer $value The amount to increase it by
	 * @param integer $id The row's ID (field `id`)
	 *
	 * @return boolean True on success, false on error.
	 */
	public static function increaseValue($table, $field, $value, $id) {
		$conn = self::getCurrentConnection();

		$table = $conn['table_prefix'] . $table;
		$id = intval($id);
		$value = intval($value);

		$sqlStatement = "UPDATE {$table} SET {$field} = {$field} + :value WHERE `id` = {$id}";
		$query = self::runQuery($sqlStatement, array('value' => $value));

		if ($query) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Conditionally increases the value of a specific field.
	 * Utilizes the current connection.
	 *
	 * @param string $table The table to utilize
	 * @param string $field The field to increase the value of
	 * @param integer $value The amount to increase it by
	 * @param array $conditions An array of conditions in SQL format
	 *
	 * 	Example: array("`contractID` = 15", "`value` > 5000");
	 *
	 * @param string $operator The condition operator
	 *
	 * @return boolean True on success, false on error.
	 */
	public static function increaseValueIf($table, $field, $value, $conditions, $operator="AND") {
		$conn = self::getCurrentConnection();

		$table = $conn['table_prefix'] . $table;

		$conditions = join(" {$operator} ", $conditions);

		$sqlStatement = "UPDATE {$table} SET {$field}={$field}+:value WHERE {$conditions}";
		$query = self::runQuery($sqlStatement, array('value' => $value));

		if ($query) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Removes a row from the database.
	 * Uses the current connection.
	 *
	 * @param string $table The table to utilize
	 * @param int $id The ID of the row to remove
	 *
	 * @return boolean True if the operation was completed, false if an error has occurred.
	 */
	public static function delete($table, $id) {
		$conn = self::getCurrentConnection();

		$table = $conn['table_prefix'] . $table;
		$id = intval($id);

		$sqlStatement = "DELETE FROM {$table} WHERE `id` = {$id}";
		$query = self::runQuery($sqlStatement);

		if ($query) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Removes multiple rows from the database.
	 * Uses the current connection.
	 *
	 * @param string $table The table to utilize
	 * @param array $conditions An array of conditions in SQL format
	 *
	 * Example: array("`contractID` = 15", "`value` > 5000");
	 *
	 * @param string $operator The junction of the conditional operator (AND, OR, etc.)
	 *
	 * @return boolean True if the operation was completed, false if an error has occurred.
	 */
	public static function deleteIf($table, $conditions, $operator="AND") {
		$conn = self::getCurrentConnection();

		$table = $conn['table_prefix'] . $table;
		$conditions = join(" {$operator} ", $conditions);

		$sqlStatement = "DELETE FROM {$table} WHERE {$conditions}";
		$query = self::runQuery($sqlStatement);

		if ($query) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Gets the data from a single row in the database.
	 * Utilizes the current connection.
	 *
	 * @param string $table The table to utilize
	 * @param int $id The ID of the row to get the data of
	 * @param array $fields The fields to use
	 *
	 * @return array An array of columns and values​​, or false if an error occurs.
	 */
	public static function getSingle($table, $id, $fields = null) {
		$conn = self::getCurrentConnection();

		$table = $conn['table_prefix'] . $table;
		$id = intval($id);

		$selection = "*";

		if ($fields != NULL) {

			$selection = array();

			foreach ($fields as $field) {
				$field = "`" . $field . "`";
				array_push($selection, $field);
			}

			$selection = join(" , ", $selection);
		}

		$sqlStatement = "SELECT {$selection} FROM {$table} WHERE `id` = {$id} LIMIT 1";
		$query = self::runQuery($sqlStatement);

		if ($query) {
			$data = $query->fetch(PDO::FETCH_ASSOC);

			return $data;
		} else {
			return false;
		}
	}

	/**
	 * Gets the data in a single row in the database, from the properties.
	 * Uses the current connection.
	 *
	 * @param string $table The table to utilize
	 * @param array $properties An array of properties to go by
	 *
	 * Example: array('name' => 'John Doe', 'title' => 'Test');
	 *
	 * @return array An array of columns and values​​, or false if an error occurs.
	 */
	public static function getSingleByProperty($table, $properties) {
		$conn = self::getCurrentConnection();

		$table = $conn['table_prefix'] . $table;

		list($properties, $pairs) = self::getPDOParameters($properties, " AND ");

		$sqlStatement = "SELECT * FROM {$table}  WHERE {$pairs} LIMIT 1";
		$query = self::runQuery($sqlStatement, $properties);

		if ($query) {
			$data = $query->fetch(PDO::FETCH_ASSOC);
			return $data;
		} else {
			return false;
		}
	}

	/**
	 * Gets a list of multiple records in the database, according to the conditions and parameters
	 * Uses the current connection.
	 *
	 * @static
	 * @param string $table The table to use in the query
	 * @param array $conditionals An array of conditions in SQL format
 	 *
 	 * Example: array("`contractID` = 15", "`value` > 5000");
	 *
	 * @param string $operator The junction of the conditional operator (AND, OR, etc.)
	 * @param string $order The order of the records.
	 *
	 * Example: `id` DESC
	 *
	 * @param int $maxRecords Maximum records to obtain
	 * @param int $offset Where should we start counting records from?
	 * @param array $fields Optionally filter out desired columns
	 *
	 * @return array|bool Returns the query to iterate using while, or false if an error occurs.
	 *
	 * @see LDB::Next();
	 *
	 */
	public static function getMultiple($table, $conditionals = array(), $operator = "AND", $order = "", $maxRecords = 0, $offset = 0, $fields = array()) {
		$conn = self::getCurrentConnection();

		$table = $conn['table_prefix'] . $table;

		if (sizeof($conditionals) > 0) {
			$conditionals = "WHERE " . join(" {$operator} ", $conditionals);
		} else {
			$conditionals = "";
		}


		if ($order != "") {
			$order = "ORDER BY {$order}";
		} else {
			$order = "";
		}

		if ($maxRecords > 0) {
			if ($offset != 0) {
				$limit = "LIMIT {$offset}, {$maxRecords}";
			} else {
				$limit = "LIMIT {$maxRecords}";
			}
		} else {
			$limit = "";
		}

		$selection = "*";
		if (sizeof($fields) > 0) {
			$selection = array();
			foreach ($fields as $field) {
				$field = "`" . $field . "`";
				array_push($selection, $field);
			}
			$selection = join(" , ", $selection);
		}

		$sqlStatement = "SELECT {$selection} FROM {$table} {$conditionals} {$order} {$limit}";
		$query = self::runQuery($sqlStatement);

		if ($query) {
			return $query->fetchAll(PDO::FETCH_ASSOC);
		} else {
			return false;
		}
	}

	/**
	 * Gets the next record from a list.
	 *
	 * @param PDOStatement $query Which query to fetch?
	 * @return array|bool The record data or false if the list is invalid
	 */
	public static function fetchNext($query) {
		if ($query) {
			return $query->fetch(PDO::FETCH_ASSOC);
		} else {
			return false;
		}
	}


	public static function countRecords($table, $conditionals = NULL, $operator = "AND") {
		$conn = self::getCurrentConnection();

		$table = $conn['table_prefix'] . $table;

		if ($conditionals != NULL) {
			$conditionals = "WHERE " . join(" {$operator} ", $conditionals);
		} else {
			$conditionals = "";
		}

		$sqlStatement = "SELECT COUNT(*) as count FROM {$table} {$conditionals}";
		$query = self::runQuery($sqlStatement);


		if ($query) {
			return intval($query->fetchColumn());
		} else {
			return false;
		}
	}

	/**
	 * Returns The number of affected rows in the last query in the current connection
	 *
	 * @return integer The number of affected rows in the last query in the current connection
	 */
	public static function getAffectedRows() {
		$conn = self::getCurrentConnection();
		return $conn['last_query']->rowCount();
	}

	/**
	 * Cleans the memory from data of the last query.
	 *
	 * @param string $connID The connection ID or NULL to use the current connection
	 * @return boolean True if the operation was completed, false if an error occurs
	 */
	public static function cleanup($connID = NULL) {
		if ($connID != NULL) {
			$conn = self::getCurrentConnection();
		} else {
			$conn = self::$connections[$connID];
		}

		$conn['last_action'] = "Cleared the last called query";
		$conn['last_query'] = NULL;

		//return $success;
		return true;
	}

	/**
	 * Disconnects a connection.
	 *
	 * @param string $connID The ID of the connection to disconnect
	 * @return boolean true
	 */
	public static function disconnect($connID = NULL) {
		if ($connID != NULL) {
			$conn = self::getCurrentConnection();
		} else {
			$conn = self::$connections[$connID];
		}

		self::cleanup($connID);
		$conn['connection'] = NULL;
		$conn['last_action'] = "Disconnected";

		return true;
	}

}

if(defined('DF3_BACKWARDS_COMPAT')) {

	/**
	 * Handler para compatibilidade com projetos no Diesel Framework 2
	 * Na versão 3, a nomenclatura foi padronizada para Class::methodInCamelCase()
	 * O nome do objeto LDB foi trocado para Database
	 *
	 * @deprecated
	 */
	class LDB {

		private static $BWC_FUNCTION_MAP = array(
			'Connect' => 'connect',
			'QuickConnect' => 'quickConnect',
			'SetErrorMode' => 'setErrorMode',
			'CheckErrors' => 'checkErrors',
			'CheckQueryErrors' => 'checkQueryErrors',
			'HandleError' => 'handleError',
			'HandleException' => 'handleException',
			'SwitchTo' => 'switchConnection',
			'GetCurrent' => 'getCurrentConnection',
			'LastInsertID' => 'lastInsertID',
			'RunQuery' => 'runQuery',
			'Insert' => 'insert',
			'Update' => 'update',
			'ConditionalUpdate' => 'updateIf',
			'IncreaseValue' => 'increaseValue',
			'ConditionalIncreaseValue' => 'increaseValueIf',
			'Remove' => 'remove',
			'ConditionalRemove' => 'removeIf',
			'GetSingle' => 'getSingle',
			'GetSingleByProperty' => 'getSingleByProperty',
			'GetMultiple' => 'getMultiple',
			'Next' => 'fetchNext',
			'CountRecords' => 'countRecords',
			'Clear' => 'cleanup',
			'Disconnect' => 'disconnect'
		);

		private static $BWC_DEPRECATED = array(
			'NumRows'
		);

		public static function __callStatic($name, $arguments) {

			if(in_array($name, self::$BWC_DEPRECATED)) {
				throw new Exception("Method {$name} in LDB is deprecated!");
			} else {
				$translatedName = self::$BWC_FUNCTION_MAP[$name];

				return call_user_func_array("Database::{$translatedName}", $arguments);
			}

		}

	}

} /* End of backwards-compatibility wrapper */