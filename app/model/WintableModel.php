<?php

namespace Model;

use Nette\Database\Connection;

/**
 * Description of WintableModel
 *
 * @author e.szabo
 */
class WintableModel extends BaseModel {
	function __construct(Connection $database) {
		parent::__construct($database);
		$this->table = "win_comb";
	}
	
	/**
	 * Finds winning combinations by game AND bet.
	 * @param int $gameId
	 * @param double $bet
	 * @return array
	 */
	public function findCombinations($gameId, $bet)
	{
		return $this->database->fetchAll("SELECT * FROM {$this->table} WHERE game_id = ? AND bet = ? ORDER BY symbol_id, symbol_count", $gameId, $bet);
	}
	
	/**
	 * Finds combination by set of parameters.
	 * @param int $gameId
	 * @param int $symbol
	 * @param int $symbolCount
	 * @param float $bet
	 * @return Row
	 */
	public function findCombination($gameId, $symbol, $symbolCount, $bet)
	{
		$sql = "SELECT * FROM {$this->table} WHERE game_id = ? AND symbol_id = ? AND bet = ? AND symbol_count = ?";
		return $this->database->fetch($sql, $gameId, $symbol, $bet, $symbolCount);
	}
}
