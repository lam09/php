<?php

namespace Model;

use Nette\Database\Connection;
use Nette\Database\Row;

/**
 * Lines management.
 */
class LinesModel extends BaseModel
{
	function __construct(Connection $database) {
		parent::__construct($database);
		$this->table = "line";
	}
	
	/**
	 * Creates a new line definition.
	 * @param array $slots
	 * @return int line id
	 */
	public function addLine($slots)
	{
		// add new line index
		$this->database->query("INSERT INTO {$this->table} (id) values (null)");
		$lineId = $this->database->getInsertId();
		
		// add line slots
		foreach ($slots as $slot) {
			$this->addPoint($lineId, $slot[0], $slot[1]);
		}
		
		return $lineId;
	}
	
	/**
	 * Finds line for a game.
	 * @param int $gameId
	 * @param int $lineId
	 * @return Row
	 */
	public function findLine($gameId, $lineId)
	{
		$sql = "SELECT * FROM line_game WHERE game_id = ? AND line_id = ?";
		return $this->database->fetch($sql, $gameId, $lineId);
	}
	
	public function findLineById($lineId)
	{
		$sql = "SELECT * FROM line_game WHERE id = ?";
		return $this->database->fetch($sql, $lineId);
	}

	/**
	 * Adds new slot for a line.
	 * @param int $lineid
	 * @param int $m row
	 * @param int $n column
	 */
	public function addPoint($lineid, $m, $n)
	{
		$this->database->query('INSERT INTO line_point (line_id, m, n) values (?, ?, ?)', $lineid, $m, $n);
	}

	public function getAllLines($gameId)
	{
		$sql = "SELECT l.id, m, n FROM line_game l
				JOIN line_point lp on l.line_id = lp.line_id
				WHERE l.game_id = ?";

		$rows = $this->database->fetchAll($sql, $gameId);

		$ids = array();
		foreach ($rows as $row) {
			$ids[$row->id][$row->m][$row->n] = true;
		}

		return $ids;
	}

	public function getGameInfo($id)
	{
		$sql = "SELECT * FROM games WHERE id = ?";
		$ret = $this->database->fetch($sql, $id);

		return array(
			"id" => $ret->id,
			"name" => $ret->name,
			"X" => $ret->columns,
			"Y" => $ret->rows,
			"note" => $ret->note
		);
	}

	public function addLineForGame($gameid, $lineid)
	{
		$this->database->query('INSERT INTO line_game (game_id,line_id) values (?,?)', $gameid, $lineid);
	}
	
	/**
	 * Deletes complete line (main record, slots, game mappings).
	 * @param int $lineId
	 */
	public function deleteLine($lineId)
	{
		$line = $this->findLineById($lineId);
		
		if ($line) {
			$this->database->query("DELETE FROM {$this->table} WHERE id = ?", $line->line_id);
		}
	}
}
