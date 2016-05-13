<?php

namespace Model;

use Nette\Database\Connection;
use Nette\Object;

/**
 * Description of BaseModel
 *
 * @author e.szabo
 */
class BaseModel extends Object {
	/** @var Connection */
	protected $database;
	
	protected $table;

	public function __construct(Connection $database) {
		$this->database = $database;
	}
	
	function getTable() {
		return $this->table;
	}

	function setTable($table) {
		$this->table = $table;
	}
	
	public function findAll()
	{
		$sql = "SELECT * FROM {$this->games}";
		return $this->database->fetchAll($sql);
	}
	
	public function add(array $params)
	{
		$this->database->query("INSERT INTO {$this->table}", $params);
		return $this->database->getInsertId();
	}
	
	public function findOneBy($id)
	{
		return $this->database->fetch("SELECT * FROM {$this->table} WHERE id = ?", $id);
	}
	
	public function delete($id)
	{
		$this->database->query("DELETE FROM {$this->table} WHERE id = ?", $id);
	}
	
	public function update($id, array $params)
	{
		$this->database->query("UPDATE {$this->table} SET ? WHERE id = ?", $params, $id);
	}
}
