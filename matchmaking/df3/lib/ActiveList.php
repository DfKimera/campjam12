<?php
/**
 * Diesel Framework
 * Copyright © LQDI Technologies - 2011
 * http://www.lqdi.net
 *
 * ActiveList 2.0
 *		JavaScript tabular data utility
 *
 * @author Aryel 'DfKimera' Tupinambá
 *
 */

class ActiveList {

	public $table = "";
	public $currentPage = 0;
	public $numPages = 0;
	public $numRecords = 0;
	public $recordsPerPage = 10;

	public $fields = array();
	public $ordering = array();
	public $filters = array();

	public $data = array();

	public function __construct($table, $currentPage, $recordsPerPage = 10) {
		$this->table = $table;
		$this->currentPage = $currentPage;
		$this->recordsPerPage = $recordsPerPage;
	}

	/**
	 * Adds/sets an ordering parameter
	 * @param string $field The name of the field to order by
	 * @param string $mode The ordering mode (ASC or DESC)
	 */
	public function setOrder($field, $mode) {
		$this->ordering[$field] = $mode;
	}

	/**
	 * Adds a new filter/conditional, in SQL format
	 * @param string $condition The conditional (Ex.: "name LIKE 'John'")
	 */
	public function addFilter($condition) {
		array_push($this->filters, $condition);
	}

	/**
	 * Sets the list of fields to be fetched on the list
	 * If no calls to this function are made, all fields will be added to the list
	 * @param array $fieldList The list of fields
	 */
	public function setFields($fieldList = array()) {
		$this->fields = $fieldList;
	}

	/**
	 * Fetches the data according to the parameters
	 * @return array The fetched data
	 */
	public function fetchData() {

		if(sizeof($this->filters) > 0) {
			$filters = "WHERE ".join(" AND ", $this->filters);
		} else {
			$filters = "";
		}

		if(sizeof($this->ordering)) {

			$ordering = array();

			foreach($this->ordering as $orderField => $orderMode) {
				array_push($ordering, "{$orderField} {$orderMode}");
			}

			$ordering = "ORDER BY ".join(", ", $ordering);

		} else {
			$ordering = "";
		}

		if(sizeof($this->fields) > 0) {
			$fields = join(", ", $this->fields);
		} else {
			$fields = "*";
		}

		$this->numRecords = intval(Database::runQuery("SELECT COUNT(*) FROM {$this->table} {$filters}")->fetchColumn());

		if($this->numRecords > 0) {
			$this->numPages = ceil($this->numRecords / $this->recordsPerPage);
		} else {
			$this->numPages = 1;
		}

		$offset = ($this->currentPage - 1) * $this->recordsPerPage;

		$query = Database::runQuery("SELECT {$fields} FROM `{$this->table}` {$filters} {$ordering} LIMIT {$offset},{$this->recordsPerPage}");
		$this->data = $query->fetchAll(PDO::FETCH_ASSOC);

		return $this->data;


	}

	/**
	 * Outputs the encoded JavaScript response to the client
	 * Will discard all output until this point
	 */
	public function pushResponse() {
		
		$response = array(
			'currentPage' => $this->currentPage,
			'numPages' => $this->numPages,
			'numRecords' => $this->numRecords,
			'data' => $this->data
		);

		reply("STATUS_OK", $response);
		
	}
	
}

?>