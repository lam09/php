<?php

namespace App;

use Model\GameModel;
use Model\MachineModel;
use Model\WinsModel;
use Nette\Utils\Paginator;

use Tracy\Debugger;



/**
 * Description of SpinPresenter
 *
 * @author e.szabo
 */


class SpinPresenter extends BasePresenter {


	/** @var MachineModel @inject */
	public $machineModel;
	/** @var WinsModel @inject */
	public $winsModel;
	/** @var GameModel @inject */
	public $gameModel;
	
	private $machines;
	private $machineId;
	private $games;
	private $gameId;
	private $limits;
	private $limit;
	private $gameConfig;
	private $columns;
	private $rows;
	private $all;
	private $paginator;
	private $actual_page;
	private $start;
	private $end;


	
	function actionDefault($id = null, $gameId = null, $limit = null, $start = null, $end = null)
	{
		date_default_timezone_set('UTC');
		Debugger::enable();

		if (empty($start)) {
			$this->start = date('Y-m-d');
			$this->end = date('Y-m-d');
		} else {
			$this->start = $start;
			$this->end = $end;
		}

		$this->machines = $this->machineModel->getAllMachines();
		$this->games = $this->gameModel->findGameNames();

		if (empty($id) && count($this->machines) > 0) {
			$this->machineId = $this->machines[0]->id;
		} else {
			$this->machineId = $id;
		}
		if (empty($gameId)) {
			$this->gameId = key($this->games);
		} else {
			$this->gameId = $gameId;
		}
		if (empty($limit)) {
			$this->limit = 1;
		} else {
			$this->limit = $limit;
		}
		$this->gameConfig = $this->winsModel->getGameConfig($this->gameId);
		$this->all = $this->winsModel->countRows($this->machineId);
		$this->columns = $this->gameConfig->columns;
		$this->rows = $this->gameConfig->rows;

		// PAGINATOR
		$this->paginator = new Paginator();
		$this->paginator->setItemCount($this->all->count); // celkový počet položek (např. článků)
		$this->paginator->setItemsPerPage($this->limit); // počet položek na stránce
		$this->paginator->setPage($this->actual_page); // číslo aktuální stránky, číslováno od 1
		$this->paginator->getPageCount();
		// PAGINATOR
	}


	function renderDefault()
	{

		$this->limits = array(1,5,10,20,30,50,100,200,500,1000);
		$this->template->machines = $this->machines;
		$this->template->games = $this->games;
		$this->template->gameId = $this->gameId;
		$this->template->limits= $this->limits;
		$this->template->limit= $this->limit;
		$this->template->all= $this->all->count;
		$this->template->selected = ($this->limit >1000 ? 2000:(int)$this->limit);
		$this->template->paginator= $this->paginator;
		$this->template->start= $this->start;
		$this->template->end= $this->end;

		foreach ($this->machines as $machine) {
			if ($machine->id == $this->machineId) {
				$this->template->machine = $machine;
			}
		}

		$values = $winnings = array();
		
		// get last spins for all games
		$spins = $this->winsModel->getLastSpins($this->machineId, $this->gameId, $this->start, $this->end, (int)$this->limit);


		$egwx = null;
		foreach ($spins as $spin) {
			$spin['bet'] /= 100.0;
			$spin['type'] = "";    /*str_replace('"', "", $spin->type);*/
			$spin['credit'] /= 100.0;
			$spin['change'] /= 100.0;
			$spin['lineWinnings'] =  $this->extractSingleArray($spin->lineWinnings); //Čiastková výhra
			$values[$spin->id] = $this->extractSymbols($spin->values); //
			list($linesEncoded, $lineCount) = $this->extractLines($spin->winnings);
			$spin['extra_status'] = $spin->extra_games;
			$spin['extra_game_values'] = $this->parseExtraArray($spin->extra_game_values,$spin->extra_games);
			$spin['extra_game_winnings'] = $this->parseExtraArray($spin->extra_game_winnings,$spin->extra_games);
			$spin['extra_game_winlines'] = $this->parseExtraSimple($spin->extra_game_winlines);
			$spin['extra_game_linewinnings'] = $this->parseExtraSimple($spin->extra_game_linewinnings);
			$spin['extra_game_bonuses'] = $this->parseExtraArray($spin->extra_game_bonuses,$spin->extra_games);
			$spin['extra_game_bonus'] = $this->parseExtraSimple($spin->extra_game_bonus);
			$spin['extra_game_win'] = $this->parseExtraSimple($spin->extra_game_win);
			$spin['extra_game_big_symbol'] = $this->parseExtraSimple($spin->extra_game_big_symbol);
			$spin['extra'] = "extra";
			$spin['extra_bet'] = "0";
			$spin['extra_line_win_count'] = $spin->extra_line_win_count;
			$egw = $this->parseExtraWinnings($spin->extra_game_winnings,$spin->extra_games,$spin->id,$spin->extra_line_win_count);
			$spin['egwx'] = $egw[$spin->id];
			$spin['select_win'] =  $this->extractSingleArray($spin->select_win); // select-win bonus
                        if (strlen($spin['select_win'])) $spin['bonuses'] .= 'select-win:'.$spin['select_win'].' ';

			$grid = "";
			for ($i = 0; $i < $lineCount; $i++) {

				$boxes = "<div class=\"slot-box\">";
				foreach ($linesEncoded as $line) {
					foreach ($line as $slot) {
						$status = ($i < strlen($slot) && $slot[$i] == 1) ? "active" : "";
						$boxes .= "<span class=\"{$status}\"></span>";
					}
					$boxes .= "<br>";
				}
				$boxes .= "</div>";
				$grid .= $boxes;
			}
			$winnings[$spin->id] = $grid;

		} //SPIN END
		$this->template->spins = $spins;
		$this->template->values = $values;
		$this->template->winnings = $winnings;
	}

	public function parseExtraWinnings($data, $nr, $id, $wincount) {

		$ew = array();
		$ews = array();
		$wincount = substr($wincount, 2, strlen($wincount) - 4);
		$count = (explode(", ", $wincount));

		for($x = 0; $x<$nr; $x++) {
			$linesEncoded[$x] = $data[$x];   //symbols array x nr. of lines
			$lineCount = (int)$count[0];
			if($lineCount == 0) {
				$lineCount = 1;
			}
			$grid2 = "";
			for ($i = 0; $i < $lineCount; $i++) {

				$boxes = "<div class=\"slot-box\">";
				foreach ($linesEncoded[$x] as $line) {
					foreach ($line as $slot) {
						$status = ($i < strlen($slot) && $slot[$i] == 1) ? "active" : "";
						$boxes .= "<span class=\"{$status}\"></span>";
					}
					$boxes .= "<br>";
				}
				$boxes .= "</div>";
				$grid2 .= $boxes;
			}
			$ew[$x] = $grid2;
			}
		$ews[$id] = $ew;

		return $ews;
		}


	//EG,TETRIS SIMPLE ARRAY (WINLINES, BONUS,.....)
	public function parseExtraSimple($values)	{

		$egValues = $values;
		$egValues = substr($egValues, 1, strlen($egValues) - 2);
		$egValue = (explode(", ", $egValues));
		for($i=0;$i<sizeof($egValue);$i++){
			$egValue[$i] = substr($egValue[$i], 1, strlen($egValue[$i]) - 2);
			if(strcasecmp($egValue[$i], "") == 0){
				$egValue[$i] = "0";
			}
		}
		return $egValue;
	}

	//EG,TETRIS COMPLEX ARRAYS
	public function parseExtraArray($values, $number)	{

		$partial = array(); //PARTIAL RESULT
		$all = array();	//COMPLETED RESULT

		if($values !== "null") {
			$values = substr($values, 1, strlen($values) - 2);
			$eV = (explode(", ", $values));
			for($i=0;$i<$number;$i++){
				$eV[$i] = substr($eV[$i], 1, strlen($eV[$i]) - 2);
				$row = (explode(" ", $eV[$i]));
				$rowx = $this->rows;
				$colx = $this->columns;
				$x = 0; // COUNTER
				for ($j =0;$j<$rowx;$j++){
					for ($k =0;$k<$colx;$k++){
						isset($row[$x]) ? $partial[$j][$k] = $row[$x] : $partial[$j][$k] = 0;
						$x++;
					}
				}
				$all[$i] = $partial;
			}
		} else {
			$all[0] ="0";
		}
		return $all;
	}

	function extractSingleArray($string){

		if(is_null($string) || $string=="null") return array();
		$string = substr($string, 1, strlen($string) - 2);
		$values = explode(",", $string);
		$symbolsFormated = array();
		foreach ($values as $symbol) {
		  if($symbol != "") $symbolsFormated[] = $symbol /100;
		}
		return $symbolsFormated;
	}
	
	function extractSymbols($string)
	{

		$string = substr($string, 1, strlen($string) - 2);
		$symbols = array();
		$i = 0;
		while ($i < strlen($string)) {
			$start = strpos($string, "[", $i);
			$end = strpos($string, "]", $i);
			$row = substr($string, $start + 1, $end - $start - 1);
			$symbols[] = explode(",", $row);
			$i = $end + 1;
		}
		return $symbols;
	}
	
	function extractLines($string)
	{
		$rowx = $this->rows;
		$colx = $this->columns;
		$winnings = array();
		$string = substr($string, 1, strlen($string) - 2);
		$row = explode(" ", $string);
		$lineCount = strlen($row[0]);

		$x = 0;
		if($x < $lineCount){
			for ($j =0;$j<$rowx;$j++){
				for ($k =0;$k<$colx;$k++){
					isset($row[$x]) ? $winnings[$j][$k] = $row[$x] : $winnings[$j][$k] = 0;
					$x++;
				}
			}
		}
		return array($winnings, $lineCount);
	}
}
