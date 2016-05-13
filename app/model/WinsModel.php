<?php

namespace Model;

use DateTime;
use Nette\Database\Connection;
use Tracy\Debugger;

/**
 * Wins management.
 */
class WinsModel extends BaseModel
{

	function __construct(Connection $database) {
		parent::__construct($database);
	}

	public function getGeneratorStatusForGame($game){
		$sql = "select betcount from generator_status gs
				LEFT JOIN games g on gs.game = g.name
				WHERE g.id = ?";
		return $this->database->fetch($sql, $game);
	}

	public function getWinningRateForGame($game){
		$count = $this->getGeneratorStatusForGame($game);
		$sql = "
				SELECT *,
				@total := @total + (a.change) AS cumulativechange,
				@totalbet := @totalbet + (a.bet) AS cumulativebet
				from
				(
				select req.game, res.*, req.bet
				from log_responses res
				LEFT JOIN log_requests req on res.request_id = req.id
				WHERE game = ?
				order by res.id desc
				LIMIT ?) a,  (SELECT @total:=0) as t,  (SELECT @totalbet:=0) as tb
				order by a.id asc";

		$ret = $this->database->fetchAll($sql, $game, $count['betcount']);
		$data = array();
		foreach ($ret as $row) {
			$winningRate = $row->cumulativechange / $row->cumulativebet * 100;
			$date = strtotime($row->time) * 1000;
			$data[] = "[$date, $winningRate]";
		}
		return $data;
	}

	/**
	 * Calculates winning rates for a time period for showing in graphs.
	 * @param DateTime $from
	 * @param DateTime $to
	 * @param string $split time unit representing point on graph (min, hour, day, month)
	 * @return array
	 */
	public function getWinningRates($from, $to, $split)
	{
		switch ($split) {
			case "min":
				$col = "EXTRACT(MINUTE FROM res.time) as date_min";
				$colName = "date_min";
				break;
			case "hour":
				$col = "EXTRACT(HOUR FROM res.time) as date_h";
				$colName = "date_h";
				break;
			case "day":
				$col = "EXTRACT(DAY FROM res.time) as date_d";
				$colName = "date_d";
				break;
			case "month":
				$col = "EXTRACT(MONTH FROM res.time) as date_m";
				$colName = "date_m";
				break;
		}
		
		$sql = "SELECT SUM(`change`) as win, SUM(bet) as bets, game, res.time, {$col}
				FROM log_responses res
				INNER JOIN log_requests req ON res.request_id = req.id
				WHERE res.time >= ? AND res.time <= ?
				GROUP BY {$colName}
				ORDER BY res.time ASC";

		$ret = $this->database->fetchAll($sql, $from, $to);
		$data = array();

		foreach ($ret as $row) {
			$winningRate = 100 * $row->win / $row->bets;
			$date = strtotime($row->time) * 1000;
			$data[] = "[$date, $winningRate]";
		}

		return $data;
	}

	/**
	 * Counts lifetime winning rate.
	 * @return float
	 */
	public function getTotalWinningRate()
	{
		$sql = "SELECT SUM(`change`) as win, SUM(bet) as bets
				FROM log_responses res
				INNER JOIN log_requests req ON res.request_id = req.id";
		
		$ret = $this->database->fetch($sql);
		$winningRate = $ret->win / $ret->bets * 100;
		
		return $winningRate;
	}

	/**
	 * Finds last spins for a game on a machine.
	 * @param int $machine
	 * @param int $game
	 * @param int $limit
	 * @param $start
	 * @param $end
	 * @return array
	 */

	public function getLastSpins($machine, $game, $start, $end, $limit)
	{
		Debugger::enable();
		$sql = "SELECT req.id, req.user, req.game, req.bet, res.time, res.machine, res.credit, res.win, res.change, res.bonus, res.values, res.winnings, res.bonuses, res.lineWinnings, res.bigSymbol,res.extra_games,res.extra_game_values,res.extra_game_winnings,res.extra_game_winlines,res.extra_game_linewinnings,res.extra_game_bonuses,res.extra_game_bonus,res.extra_game_win,res.extra_game_big_symbol,res.tetris_games,res.tetris_game_values,res.tetris_game_winnings,res.tetris_game_winlines,res.tetris_game_linewinnings,res.tetris_game_bonuses,res.tetris_game_bonus,res.tetris_game_win,res.tetris_game_big_symbol,res.extra_line_win_count,res.select_win
				FROM log_responses res
				INNER JOIN log_requests req ON res.request_id = req.id
				WHERE res.machine = ? AND req.game = ? AND DATE(res.time) >= ? AND DATE(res.time) <= ?
				ORDER BY res.time DESC
				LIMIT ?";

		return $this->database->fetchAll($sql, $machine, $game, $start, $end, $limit);

		//SELECT WITH DATE
		//SELECT COUNT(*) FROM log_responses WHERE DATE(`time`) >= '2016-03-01' AND DATE(`time`) <= '2016-03-14' AND machine=34
	}

/*
	public function getLastSpins($machine, $game, $limit)
	{
		$sql = "SELECT req.id, req.user, req.game, req.bet, res.time, res.machine, res.credit, res.win, res.change, res.bonus, res.values, res.winnings, res.bonuses, res.lineWinnings, res.bigSymbol,res.extra_games,res.extra_game_values,res.extra_game_winnings,res.extra_game_winlines,res.extra_game_linewinnings,res.extra_game_bonuses,res.extra_game_bonus,res.extra_game_win,res.extra_game_big_symbol,res.tetris_games,res.tetris_game_values,res.tetris_game_winnings,res.tetris_game_winlines,res.tetris_game_linewinnings,res.tetris_game_bonuses,res.tetris_game_bonus,res.tetris_game_win,res.tetris_game_big_symbol,res.extra_line_win_count,res.select_win
				FROM log_responses res
				INNER JOIN log_requests req ON res.request_id = req.id
				WHERE res.machine = ? AND req.game = ?
				ORDER BY res.time DESC
				LIMIT ?";

		return $this->database->fetchAll($sql, $machine, $game, $limit);
		//return $this->database->fetchAll($sql, $machine, $game, $limit);

	}
*/
	public function getTotalSpinCount()
	{
		return $this->database->fetch("SELECT COUNT(*) as count FROM log_responses");
	}
	
	public function getSpinCountByMachine($machineId)
	{
		return $this->database->fetch("SELECT COUNT(*) as count FROM log_responses WHERE machine = ?", $machineId);
	}

	public function getGameConfig($gameId)
	{
		return $this->database->fetch("SELECT columns, rows FROM games WHERE id=$gameId");
	}
	public function countRows($machine)
	{
		$query = $this->database->fetch('SELECT COUNT(*) as count FROM log_responses WHERE machine = ?',$machine);
		return $query;
	}
}

