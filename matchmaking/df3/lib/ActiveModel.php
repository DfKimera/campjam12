<?php
/**
 * Diesel Framework
 * Copyright © LQDI Technologies - 2011
 * http://www.lqdi.net
 *
 * ActiveModel 1.3
 *		Serviço de persistencia de models
 *		Model persistence service
 *
 * @author Aryel 'DfKimera' Tupinambá
 * 
 */

define("ACTIVE_MODEL_VERSION", 1300);

/*******************************************************************************
 * ActiveModelManager
 *	Manages model instance persistence and model class registration
 ******************************************************************************/
class ActiveModelManager {
	
	private static $prevDatabaseConnection = 0;
	private static $databaseConnection = 0;
	private static $models = array();
	
	private static $cache = array();
	
	/**
	 * @var boolean Cache model instances so they're not reloaded
	 */

	public static $cacheInstances = false;
	
	
	/**
	 * Initializes the ActiveModel system with an Database connection
	 *
	 * @static
	 * @param int $LDBConnection The LDB connection ID
	 */
	public static function setup($LDBConnection) {
		self::$databaseConnection = $LDBConnection;
	}
	
	/**
	 * Switches the LDB connection to the one allocated for the ActiveModel system
	 *
	 * @static
	 */
	public static function checkIn() {
		self::$prevDatabaseConnection = Database::$currentConnection;
		Database::switchConnection(self::$databaseConnection);
	}
	
	/**
	 * Releases the LDB connection
	 *
	 * @static
	 */
	public static function checkOut() {
		Database::switchConnection(self::$prevDatabaseConnection);
	}
	
	/**
	 * Registers a type of model that can be persisted in the model table
	 *
	 * @static
	 * @param string $modelClass The classname of the model
	 * @param string $modelTable The database table in which the model instances will be stored
	 * @param array $modelAttributes The attributes that will be persisted in your model class
	 */
	public static function registerModelClass($modelClass, $modelTable, $modelAttributes) {
		self::$models[$modelClass] = array(
			'table' => $modelTable,
			'attributes' => $modelAttributes
		);
	}
	
	/**
	 * Gets available information on the given model class
	 *
	 * @static
	 * @param string $modelClass The name of the model class
	 * @return array An associative array with available information ('table' and 'attributes')
	 */
	public static function getModelClass($modelClass) {
		return self::$models[$modelClass];
	}
	
	/**
	 * Cache a model instance locally
	 *
	 * @static
	 * @param ActiveModel $obj The model instance
	 */
	public static function cacheObject(ActiveModel &$obj) {
		if(!is_array(self::$cache[$obj->modelClass])) {
			self::$cache[$obj->modelClass] = array();
		}
		self::$cache[$obj->modelClass][$obj->id] = &$obj;
	}
	
	/**
	 * Loads a model instance from the cache
	 *
	 * @static
	 * @param string $modelClass The model class
	 * @param int $id The ID of the model instance in the database
	 * @return ActiveModel
	 */
	public static function loadFromCache($modelClass, $id) {
		$obj = self::$cache[$modelClass][$id];
		if($obj) {
			return $obj;
		} else {
			return false;
		}
	}
	
	/**
	 * Checks if a model instance is cached locally
	 *
	 * @static
	 * @param string $modelClass The model class
	 * @param int $id The ID of the model instance in the database
	 * @return boolean True or false
	 */
	public static function isCached($modelClass, $id) {
		return isset(self::$cache[$modelClass][$id]);
	}
	
	/**
	 * Checks if object is a valid model instance, and generates a system error otherwise
	 *
	 * @static
	 * @param ActiveModel $obj The object to validate
	 */

	public static function validateModelInstance(&$obj) {
		if($obj == null) {
			error("Invalid model instance, object is null");
		} else if(!$obj->modelClass) {
			error("Invalid model instance, class name not found");
		} else if(!$obj->classInfo) {
			error("Invalid model instance, classInfo not found");
		}
	}
	
	/**
	 * Turns your model instance persistent, by creating a corresponding a database record
	 *
	 * @static
	 * @param ActiveModel $obj
	 * @return int The unique identifier of your model instance
	 */
	public static function insert(ActiveModel &$obj) {
		
		self::validateModelInstance($obj);
		
		$data = self::copyFromModel($obj, $obj->classInfo['attributes']);
		$id = Database::insert($obj->classInfo['table'], $data);
		
		if($id) {
			$obj->id = $id;
			return $id;
		} else {
			return false;
		}
		
	}
	
	/**
	 * Updates the corresponding database record with the persistent attributes in your model instance
	 *
	 * @static
	 * @param ActiveModel $obj The model to persist changes
	 * @param array $fields Which fields should we update?
	 *
	 * @return boolean True or false depending on success
	 */
	public static function update(ActiveModel &$obj, $fields = null) {
		
		self::validateModelInstance($obj);
		
		if(is_array($fields)) {
			$data = self::copyFromModel($obj, $fields);
		} else {
			$data = self::copyFromModel($obj);
		}
		
		$update = Database::update($obj->classInfo['table'], $data, $obj->id);
		
		return (boolean) $update;
	}
	
	/**
	 * Deletes the record in the database corresponding to your model instance
	 *
	 * @static
	 * @param ActiveModel $obj
	 * @return boolean True or false depending on success
	 */
	public static function delete(ActiveModel &$obj) {
		self::validateModelInstance($obj);
		return Database::delete($obj->classInfo['table'], $obj->id);
	}
	
	/**
	 * Copies the keys and values from an array to your model instance, as attributes
	 *
	 * @static
	 * @param ActiveModel $obj The target model instance
	 * @param array $data An associative array with the properties and values to be set
	 */
	public static function copyToModel(ActiveModel &$obj, $data) {
		if (is_array($data) && count($data)) {
			foreach ($data as $var => $val) {
				$obj->$var = $val;
			}
		}
	}

	/**
	 * Returns an associative array with the attributes from your model instance.
	 * If the second parameter is an array, it'll only copy the given attributes in that array.
	 * If the second parameter is "all", it'll copy ALL of the instance attributes.
	 * If the second parameter is omitted, it'll copy only the persistent attributes.
	 *
	 * @static
	 * @param ActiveModel $obj The model instance from which the attributes will be copied
	 * @param array $fields An array with the desired attributes
	 * @return array An associative array with the requested attributes and their corresponding values
	 */
	public static function copyFromModel(ActiveModel &$obj, $fields = null) {
		
		self::validateModelInstance($obj);
		
		$data = array();
				
		if($fields == 'all') {
			$fields = get_object_vars($obj);
		} else if($fields == null) {
			$fields = $obj->classInfo['attributes'];
		}
		
		if(!is_array($obj->classInfo['attributes'])) {
			error("Cannot copy model instance attributes: attributes are not valid array (modelClass: {$obj->modelClass})");
		}
		
		foreach($fields as $field) {
			$data[$field] = $obj->$field;
		}
		
		return $data;
	}	
	
}

/*******************************************************************************
 * ActiveModelProxy
 *	Acts as a proxy to model instance queries in the database
 ******************************************************************************/
class ActiveModelProxy {
	
	private $contextModelClass;
	
	private $queryFields = array();
	
	private $fromClauses = array();
	
	private $conditionalClauses = array();
	private $conditionalOperators = array();
	
	private $orderClauses = array();
	private $groupClauses = array();

	private $isPaged = false;
	private $offset = 0;
	private $limit = 0;
	
	
	/**
	 * @var PDOStatement The PDO statement used in the query
	 */
	public $pdoStatement = null;
	
	/**
	 * @param string $modelClass The name of the model class
	 * @param string $joinIdentifier [optional] The identifier for join operations
	 */
	public function __construct($modelClass, $joinIdentifier = null) {
		$this->contextModelClass = $modelClass;
		
		$classInfo = ActiveModelManager::getModelClass($modelClass);
		
		if($joinIdentifier != null) {
			$this->fromClauses = array("{$classInfo['table']} {$joinIdentifier}");
		} else {
			$this->fromClauses = array($classInfo['table']);
		}
		
	}
	
	/**
	 * Begins a query in the model table
	 *
	 * @return ActiveModelProxy 
	 */
	public function get() {
		$this->queryFields = func_get_args();
		return $this;
	}
	
	/**
	 * Specifies a list of conditions for the query
	 *
	 * @return ActiveModelProxy 
	 */
	
	public function where() {
		$conditionals = func_get_args();
		
		if(sizeof($conditionals) <= 0) {
			error("No conditional defined in where() clause");
		}

		if(sizeof($conditionals) == 1 && is_array($conditionals[0])) {
				$conditionals = $conditionals[0];
		}

		if(sizeof($conditionals) <= 0) {
			return $this;
		}
		
		$conditionals = join(" AND ", $conditionals);
		array_push($this->conditionalClauses, $conditionals);
		array_push($this->conditionalOperators, "AND");
		
		return $this;
		
	}
	
	/**
	 * Specifies an alternative (OR) list of conditions for the query
	 *
	 * @return ActiveModelProxy 
	 */
	public function also() {
		$conditionals = func_get_args();
		
		if(sizeof($conditionals) <= 0) {
			error("No conditional defined in also() clause");
		}
		
		$conditionals = join(" AND ", $conditionals);
		array_push($this->conditionalClauses, $conditionals);
		array_push($this->conditionalOperators, "OR");
		
		return $this;
	}
	
	/**
	 * Specifies a limit to the amount of records returned
	 * Will discard onPage()
	 *
	 * @param int $max The maximum amount of records
	 * @return ActiveModelProxy 
	 */
	public function limit($max = 1024) {
		$this->isPaged = true;
		$this->limit = $max;
		
		return $this;
	}

	/**
	 * Specifies a number of records to offset
	 * Will discard onPage()
	 *
	 * @param $offset int The amount of records to offset
	 * @return ActiveModelProxy
	 */
	public function offset($offset = 0) {
		$this->isPaged = true;
		$this->offset = $offset;

		return $this;
	}
	
	/**
	 * Pages the query results
	 * Will discard offset() and limit()
	 *
	 * @param int $pageNum The current page number
	 * @param int $perPage The amount of records per page
	 * @return ActiveModelProxy 
	 */
	public function onPage($pageNum, $perPage = 24) {
		$this->isPaged = true;
		$this->offset = ($pageNum-1)*$perPage;
		$this->limit = $perPage;
		
		return $this;
	}
	
	/**
	 * Specifies an order to the query results
	 *
	 * @return ActiveModelProxy 
	 */
	public function orderBy() {
		
		$ordering = func_get_args();
		
		if(sizeof($ordering) <= 0) {
			error("No order parameter defined in orderBy() clause");
		}
		
		$this->orderClauses = $ordering;
		
		return $this;
		
	}

	/**
	 * Specifies a grouping set
	 *
	 * @return ActiveModelProxy
	 */
	public function groupBy() {

		$grouping = func_get_args();

		if(sizeof($grouping) <= 0) {
			error("No grouping parameter defined in groupBy() clause");
		}

		$this->groupClauses = $grouping;

		return $this;

	}
	
	/**
	 * Performs a natural join with another table for the query results
	 *
	 * @param string $table The table to be joined
	 * @return ActiveModelProxy 
	 */
	public function with($table) {
		array_push($this->fromClauses, ", {$table}");
		return $this;
	}
	
	/**
	 * Performs a left join with another table for the query results
	 *
	 * @param string $table The table to be joined
	 * @param array $on An array of conditions for the join
	 * @return ActiveModelProxy 
	 */
	public function leftJoin($table, $on = null) {
		if(is_array($on)) {
			$on = "ON ".join(" AND ", $on);
		}
		array_push($this->fromClauses, "LEFT JOIN {$table} {$on}");
		return $this;
	}
	
	/**
	 * Performs a right join with another table for the query results
	 *
	 * @param string $table The table to be joined
	 * @param array $on An array of conditions for the join
	 * @return ActiveModelProxy 
	 */
	public function rightJoin($table, $on = null) {
		if(is_array($on)) {
			$on = "ON ".join(" AND ", $on);
		}
		array_push($this->fromClauses, "RIGHT JOIN {$table} {$on}");
		return $this;
	}
	
	/**
	 * Performs an inner join with another table for the query results
	 *
	 * @param string $table The table to be joined
	 * @param array $on An array of conditions for the join
	 * @return ActiveModelProxy  
	 */
	public function innerJoin($table, $on = null) {
		if(is_array($on)) {
			$on = "ON ".join(" AND ", $on);
		}
		array_push($this->fromClauses, "INNER JOIN {$table} {$on}");
		return $this;
	}
	
	/**
	 * Fetches a single model instance from the database
	 *
	 * @return ActiveModel The model instance
	 */
	public function fetchOne() {
		
		$this->query();
		
		$data = $this->pdoStatement->fetch(PDO::FETCH_ASSOC);

        if($data === false) {
            return false;
        }
		
		if(ActiveModelManager::isCached($this->contextModelClass, $data['id'])) {
			$obj = &ActiveModelManager::loadFromCache($this->contextModelClass, $data['id']);
		} else {
			$obj = (new $this->contextModelClass());
		}
		
		ActiveModelManager::copyToModel($obj, $data);
		
		return $obj;
		
	}
	
	/**
	 * Fetches an array of model instances from the database
	 *
	 * @return array An array of model instances
	 */
	public function fetchAll() {
		
		$this->query();
		
		$objs = array();
		$data = $this->pdoStatement->fetchAll(PDO::FETCH_ASSOC);

        if($data === false) {
            return false;
        }

		foreach($data as $d) {
			
			if(ActiveModelManager::isCached($this->contextModelClass, $d['id'])) {
				$obj = &ActiveModelManager::loadFromCache($this->contextModelClass, $d['id']);
			} else {
				$obj = (new $this->contextModelClass());
			}
			
			ActiveModelManager::copyToModel($obj, $d);
			array_push($objs, $obj);
		}
		
		return $objs;
		
	}
	
	/**
	 * Fetches an array of associative key-values from the database
	 *
	 * @return array
	 */
	public function fetchRaw() {
		
		$this->query();
		
		return $this->pdoStatement->fetchAll(PDO::FETCH_ASSOC);
		
	}
	
	/**
	 * Prepares, executes and returns the prepared PDO statement for the database query
	 *
	 * @return PDOStatement
	 */
	public function query() {
		
		ActiveModelManager::checkIn();
		
		$sqlStatement = "SELECT {$this->prepareFieldClause()} FROM {$this->prepareFromClause()} {$this->prepareWhereClause()} {$this->prepareGroupClause()} {$this->prepareOrderClause()} {$this->prepareLimitClause()}";
		$this->pdoStatement = Database::runQuery($sqlStatement);
		
		ActiveModelManager::checkOut();
		
		return $this->pdoStatement;
		
	}
	
	
	
	// -------------------------------------------------------------------------
	
	/**
	 * Prepares the field declaration clause
	 *
	 * @return string Ex.: "*", "id, name", "usr.id, art.title"
	 */
	private function prepareFieldClause() {
		if(sizeof($this->queryFields) > 0) {
			return join(", ", $this->queryFields);
		} else {
			return "*";
		}
	}
	
	/**
	 * Prepares the FROM clause
	 *
	 * @return string Ex.: "news", "news n, articles a", "news n LEFT JOIN articles a"
	 */
	private function prepareFromClause() {
		return join(" ", $this->fromClauses);
	}
	
	/**
	 * Prepares the WHERE clause
	 *
	 * @return string Ex.: "WHERE ( x = 1 )", "WHERE ( x = 1 AND x = 2 )", "WHERE (x = 1 AND X = 2) OR ( z = 3 )"
	 */
	private function prepareWhereClause() {
		if(sizeof($this->conditionalClauses) <= 0) {
			return "";
		}
		
		$conditionals = "WHERE ";
		
		foreach($this->conditionalClauses as $index => $conditional) {
			$conditionals .= " ({$conditional}) ";
			if($this->conditionalOperators[$index+1]) {
				$conditionals .= $this->conditionalOperators[$index+1];
			}
		}
		return $conditionals;
	}
	
	/**
	 * Prepares the LIMIT clause
	 *
	 * @return string Ex.: "LIMIT 10", "LIMIT 0,24", "LIMIT 800,24"
	 */
	private function prepareLimitClause() {
		
		if(!$this->isPaged) {
			return "";
		}

        if($this->offset > 0 || $this->limit > 0) {

            $limit = "LIMIT ";
            if($this->offset > 0) {

				if($this->limit <= 0) {
					$this->limit = 1024;
				}

                $limit .= "{$this->offset},{$this->limit}";
            } else {
                $limit .= "{$this->limit}";
            }

            return $limit;

        } else {
            return "";
        }
		

		
	}

	/**
	 * Prepares the ORDER clause
	 *
	 * @return string Ex.: "ORDER BY id DESC", "ORDER BY status ASC, id DESC"
	 */
	private function prepareOrderClause() {
		
		if(sizeof($this->orderClauses) <= 0) {
			return "";
		}
		
		return "ORDER BY ".join(", ", $this->orderClauses);
	}

	/**
	 * Prepares the GROUP clause
	 *
	 * @return string Ex.: "GROUP BY user_id", "GROUP BY status, user_id"
	 */
	private function prepareGroupClause() {

		if(sizeof($this->groupClauses) <= 0) {
			return "";
		}

		return "GROUP BY ".join(", ", $this->groupClauses);
	}
	
}

/**
 * Gets a database query proxy for the given model class
 *
 * @param string $modelClass The name of the model class
 * @param string $joinIdentifier [optional] The table shorthand identifier in case of joins
 * @return ActiveModelProxy The query proxy
 */
function ActiveModelProxy($modelClass, $joinIdentifier = null) { return new ActiveModelProxy($modelClass, $joinIdentifier); }

// ----------------------------------------

/*******************************************************************************
 * ActiveModel
 *	Represents a model that can be persisted in the database
 ******************************************************************************/
abstract class ActiveModel implements ArrayAccess {
	
	/**
	 * @var int The primary key and unique identifier of the model instance
	 */
	public $id;
	
	/**
	 * @var string The model class name
	 */
	public $modelClass;
	
	/**
	 * @var array Metadata of the model class
	 */
	public $classInfo;
	
	public function __construct($id = null, $conditions = null) {
		
		$this->classInfo = ActiveModelManager::getModelClass($this->modelClass);
		
		if($id == null) { // New empty model

			$this->onCreated();
			
		} else if($id == "where") { // Load model from the database where conditions are met
			
			$data = Database::getSingleByProperty($this->classInfo['table'], $conditions);
			ActiveModelManager::copyToModel($this, $data);
			
			$this->id = $data['id'];
			
			$this->onLoaded();
			
		} else { // Load model from the database by primary key (ID)
			
			$this->id = intval($id);
			
			$data = Database::getSingle($this->classInfo['table'], $this->id);
			ActiveModelManager::copyToModel($this, $data);
			
			$this->onLoaded();
			
		}
		
		
	}
	
	/**
	 * Imports an associative array containing column => data values into an empty model.
	 * Useful to generate model proxies straight from LDB queries.
	 *
	 * @param array $data An associative array of column => value pairs
	 * @param array $fields Which fields in the data array should we import?
	 * @param bool $dispatchEvents Should we dispatch the model loading events (such as onLoaded)?
	 */
	public function importData($data, $fields = null, $dispatchEvents = true) {

        if($fields != null) {
            $filtered = array();
            
            foreach($fields as $field) {
                $filtered[$field] = $data[$field];
            }

            $data = $filtered;
        }

		ActiveModelManager::copyToModel($this, $data);
		
		if($dispatchEvents) {
			$this->onLoaded();
		}
		
	}

    /**
     * Exports an associative array containing column => data values from the current model.
     * Useful to extract JSON representations for AJAX-based interactions;
	 *
     * @param array $fields [optional] The fields to get from the object
     * @return array[] Associative column => data array
     */
    public function toArray($fields = null) {

		if(is_array($fields)) {
			$data = ActiveModelManager::copyFromModel($this, $fields);
		} else {
			$data = ActiveModelManager::copyFromModel($this);
		}

        return $data;
        
    }
	
	/**
	 * Saves this model instance in the database
	 *
	 * @return boolean True or false depending on success
	 */
	public function update() {
		
		$fields = func_get_args();
		
		if(!is_array($fields) || sizeof($fields) <= 0) {
			$fields = null;
		}

		if(is_array($fields[0])) {
			$fields = $fields[0];
		}
		
		$this->beforeUpdate();
		$this->beforeSave();
		$update = ActiveModelManager::update($this, $fields);
		
		$this->afterUpdate();
		$this->afterSave();
		
		return $update;
	}

	/**
	 * Updates the object parameter and persists it in the database
	 *
	 * @param string $key The parameter name
	 * @param mixed $value The new value
	 * @return boolean True or false depending on success
	 */
	public function set($key, $value) {
		$this->$key = $value;
		return $this->update($key);
	}

	/**
	 * Updates the object parameters based on array of keys and values, persisting it in the database.
	 *
	 * @param array $keyValues An array of keys and values
	 * @return bool True or false depending on success
	 */
	public function map($keyValues = array()) {
		$columns = array_keys($keyValues);
		$this->importData($keyValues, null, false);
		return $this->update($columns);
	}

	/**
	 * Inserts this model instance in the database, making it persistent
	 * @return int The model instance ID, or false on failure
	 */
	public function insert() {
		
		$this->beforeInsert();
		$this->beforeSave();
		
		$this->id = ActiveModelManager::insert($this);
		
		$this->afterInsert();
		$this->afterSave();
		
		return $this->id;
		
	}
	
	/**
	 * Removes this model instance from the database, disabling its persistence
	 * @return boolean True or false depending on success
	 */
	public function delete() {
		$this->beforeDelete();
		$delete = ActiveModelManager::delete($this);
		$this->afterDelete();
		
		return $delete;
	}
	
	/**
	 * Called when the model instance is created from scratch
	 */
	public function onCreated() {}
	
	/**
	 * Called when the model instance is loaded from the database
	 */
	public function onLoaded() {}
	
	/**
	 * Called before the model instance is inserted in the database
	 */
	public function beforeInsert() {}
	
	/**
	 * Called after the model instance is inserted in the database (ID is available)
	 */
	public function afterInsert() {}
	
	/**
	 * Called before the model instance is updated in the database
	 */
	public function beforeUpdate() {}
	
	/**
	 * Called after the model instance is updated in the database
	 */
	public function afterUpdate() {}
	
	/**
	 * Called before the model instance is removed from the database
	 */
	public function beforeDelete() {}
	
	/**
	 * Called after the model instance is removed from the database
	 */
	public function afterDelete() {}
	
	/**
	 * Called before the model instance is changed in the database (update and insert)
	 */
	public function beforeSave() {}
	
	/**
	 * Called after the model instance is changed in the database (update and insert)
	 */
	public function afterSave() {}

	/**
	 * (PHP 5 &gt;= 5.1.0)<br/>
	 * Whether a offset exists
	 * @link http://php.net/manual/en/arrayaccess.offsetexists.php
	 * @param mixed $offset <p>
	 * An offset to check for.
	 * </p>
	 * @return boolean Returns true on success or false on failure.
	 * </p>
	 * <p>
	 * The return value will be casted to boolean if non-boolean was returned.
	 */
	public function offsetExists($offset) {
		return isset($this->$offset);
	}

	/**
	 * (PHP 5 &gt;= 5.1.0)<br/>
	 * Offset to retrieve
	 * @link http://php.net/manual/en/arrayaccess.offsetget.php
	 * @param mixed $offset <p>
	 * The offset to retrieve.
	 * </p>
	 * @return mixed Can return all value types.
	 */
	public function offsetGet($offset) {
		return $this->$offset;
	}

	/**
	 * (PHP 5 &gt;= 5.1.0)<br/>
	 * Offset to set
	 * @link http://php.net/manual/en/arrayaccess.offsetset.php
	 * @param mixed $offset <p>
	 * The offset to assign the value to.
	 * </p>
	 * @param mixed $value <p>
	 * The value to set.
	 * </p>
	 * @return void
	 */
	public function offsetSet($offset, $value) {
		$this->$offset = $value;
	}

	/**
	 * (PHP 5 &gt;= 5.1.0)<br/>
	 * Offset to unset
	 * @link http://php.net/manual/en/arrayaccess.offsetunset.php
	 * @param mixed $offset <p>
	 * The offset to unset.
	 * </p>
	 * @return void
	 */
	public function offsetUnset($offset) {
		unset($this->$offset);
	}
}
