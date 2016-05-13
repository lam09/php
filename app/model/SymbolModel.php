<?php

namespace Model;

use Nette\Database\Connection;

/**
 * Description of SymbolModel
 *
 * @author e.szabo
 */
class SymbolModel extends BaseModel {
	function __construct(Connection $database) {
		parent::__construct($database);
		$this->table = "symbol";
	}
	
	public function findByGame($id)
	{
		$sql = "SELECT * FROM symbol WHERE game_id = ? ORDER BY game_id ASC, symbol_id ASC";
		return $this->database->fetchAll($sql, $id);
	}
	
	public function edit($id, $special)
	{
		$sql = "UPDATE symbol SET special = ? WHERE id = ?";
		return $this->database->query($sql, $special, $id);
	}
}
