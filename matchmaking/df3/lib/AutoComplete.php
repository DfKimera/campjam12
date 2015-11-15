<?php
/**
 * Diesel Framework
 * Copyright © LQDI Technologies - 2011
 * http://www.lqdi.net
 *
 * AutoComplete 2.0
 *		JavaScript autocomplete utility
 *
 * @author Aryel 'DfKimera' Tupinambá
 *
 */

class AutoComplete {

	public $table = "";
	public $field = "";
	public $input = "";
	public $limit = 0;

	public $filters = array();
	public $query;

	public function __construct($table, $field, $input, $limit = 6) {
		$this->table = $table;
		$this->field = $field;
		$this->input = $input;
		$this->limit = $limit;

		$this->addFilter("`{$field}` LIKE '{$input}%'");
	}

	/**
	 * Adds a new filter/conditional, in SQL format
	 * @param string $condition The conditional (Ex.: "name LIKE 'John'")
	 */
	public function addFilter($condition) {
		array_push($this->filters, $condition);
	}

	/**
	 * Fetches the data according to the parameters
	 * @return PDOStatement The executed query
	 */
	public function fetchData() {

		$filters = "WHERE ".join(" AND ", $this->filters);

		$this->query = Database::runQuery("SELECT {$this->field} FROM `{$this->table}` {$filters} LIMIT {$this->limit}");

		return $this->query;


	}

	/**
	 * Outputs the response to the client, in our JS AutoComplete lib format
	 * Will discard all output until this point
	 */
	public function pushResponse() {

		$results = array();

		while($row = Database::fetchNext($this->query)) {
			array_push($results, $row[$this->field]);
		}

		discard_output();

		die(join("\n",$results));

	}

}
