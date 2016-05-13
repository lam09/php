<?php

namespace Model;

use Nette\Database\Connection;

/**
 * Description of GameModel
 *
 * @author e.szabo
 */
class GameModel extends BaseModel {
	function __construct(Connection $database) {
		parent::__construct($database);
		$this->table = "games";
	}
	
	public function findGameNames()
	{
		$sql = "SELECT * FROM {$this->table} ORDER BY name";
		$ret = $this->database->query($sql);

		$ids = array();

		while ($row = $ret->fetch()) {
			$ids[$row->id] = $row->name;
		}
		return $ids;
	}

	public function getGames(){
		$sql = "SELECT * FROM {$this->table} ORDER BY name";
		$ret = $this->database->FetchAll($sql);
		return $ret;
	}
	
	/**
	 * Updates note for a game.
	 * @param int $id
	 * @param string $note
	 */
	public function updateGame($id, $note)
	{
		$this->database->query("UPDATE {$this->table} SET note = ? WHERE id = ?", $note, $id);
	}
}
