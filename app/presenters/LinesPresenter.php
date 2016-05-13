<?php

namespace App;

use Model\GameModel;
use Model\LinesModel;

class LinesPresenter extends BasePresenter {
    /** @var LinesModel @inject */
    public $linesModel;
	/** @var GameModel @inject */
	public $gameModel;

    private $games;
	private $game;
	private $gameId;
    private $lines;
	private $combinations;
	
	public function actionDefault($id)
	{
		$this->games = $this->gameModel->findGameNames();
		$this->gameId = (empty($id)) ? key($this->games) : $id;
	}
	
	/**
	 * Generates so called "criss-cross" combinations for selected games.
	 */
	public function actionCreateCCLines()
	{
		$games = array(5); // game IDs
		
		foreach ($games as $gameId) {
			$game = $this->linesModel->getGameInfo($gameId);
			$cols = $game["X"];
			$rows = $game["Y"];
			
			// generate all unique combinations where there is exactly 1 symbol in each column (criss-cross)
			$this->combinations = array();
			$this->generateCombinations($rows, $cols, 0, "");
			
			// convert to combinations, where 1 or more columns are omitted (e.g. X000, 0X00, 00X0, 000X, XX00, X00X etc.)
			/*$replacableCallback = $this->removeCharFromString(array(0));
			$newCombs1 = array_map($replacableCallback, $this->combinations);
			$replacableCallback = $this->removeCharFromString(array($cols - 1));
			$newCombs2 = array_map($replacableCallback, $this->combinations);
			$replacableCallback = $this->removeCharFromString(array(0, 1));
			$newCombs3 = array_map($replacableCallback, $this->combinations);
			$replacableCallback = $this->removeCharFromString(array($cols - 1, $cols - 2));
			$newCombs4 = array_map($replacableCallback, $this->combinations);
			$newCombinations = array_merge($newCombs1, $newCombs2, $newCombs3, $newCombs4);*/
			$newCombs = array_map("strrev", $this->combinations); // for combinations where each line instead of column contains exactly 1 slot
			$newCombinationsUnique = array_unique($newCombs);

			foreach ($newCombinationsUnique as $comb) {
				// create matrix from string
				$lineCompact = array();
				for ($i = 0; $i < strlen($comb); $i++) {
					// store position coordinates
					if ($comb[$i] != 'X') {
						$lineCompact[] = array($i, $comb[$i]); // for combinations where each line instead of column contains exactly 1 slot, otherwise shift elements
					}
				}
				
				$lineId = $this->linesModel->addLine($lineCompact);
				$this->linesModel->addLineForGame($gameId, $lineId);
			}
		}
		
		$this->terminate();
	}
	
	public function handleAddNewLine()
	{
		$postData = $this->getRequest()->getPost();
		$slots = $postData["line"];
		$game = $this->linesModel->getGameInfo($this->gameId);
		$cols = $game['X'];
		$rows = $game['Y'];
		$lineCompact = $mline = array();
		
		// convert slot indexes to row-column coordinates
		foreach ($slots as $slot) {
			$row = (int)($slot / $cols);
			$col = $slot % $cols;
			$lineCompact[] = array($row, $col);
			$mline[$row][$col] = true;
		}
		
		// check if the new line does not exist yet
		$equalRowFound = false;
		$lines = $this->linesModel->getAllLines($this->gameId);
		foreach ($lines as $line) {
			$match = true;
			for ($r = 0; $r < $rows; $r++) {
				for ($c = 0; $c < $cols; $c++) {
					if ((!isset($mline[$r][$c]) && isset($line[$r][$c])) || (isset($mline[$r][$c]) && !isset($line[$r][$c]))) {
						// symbols do not match on a position
						$match = false;
						break 2;
					}
				}
			}
			
			if ($match) {
				$equalRowFound = true;
				break;
			}
		}
		
		if (!$equalRowFound) {
			$lineId = $this->linesModel->addLine($lineCompact);
			$this->linesModel->addLineForGame($this->gameId, $lineId);
			$this->logModel->save("winning_line_added", $lineId, $this->getUser()->getId(), $this->getUser()->getIdentity()->username);
		} else {
			$this->flashMessage("Definícia tejto výhernej línie už existuje.", "danger");
		}
		
		$this->redirect("this");
	}
	
	public function handleDeleteLine($lineId)
	{
		$this->linesModel->deleteLine($lineId);
		$this->logModel->save("winning_line_removed", $lineId, $this->getUser()->getId(), $this->getUser()->getIdentity()->username);
		$this->redrawControl("lines");
	}
	
	public function handleSaveNote($note)
	{
		$this->gameModel->updateGame($this->gameId, $note);
		$this->redrawControl("note");
	}
	
	public function renderDefault($id)
	{
		$game = $this->linesModel->getGameInfo($this->gameId);
		$this->game = array(
			"name" => $game['name'],
			"id" => $game['id'],
			"rows" => $game['Y'],
			"columns" => $game['X'],
			"note" => $game['note']
		);

		$this->lines = $this->linesModel->getAllLines($this->gameId);
		
        $this->template->columns = $this->game["columns"];
        $this->template->rows = $this->game["rows"];
		$this->template->allLines = $this->lines;
        $this->template->games = $this->games;
        $this->template->game = $this->game;
	}
	
	function generateCombinations($rows, $cols, $col, $comb)
	{
		if ($col == $cols) {
			$this->combinations[] = $comb;
			return;
		}
		
		for ($row = 0; $row < $rows; $row++) {
			$newComb = "{$comb}{$row}";
			$this->generateCombinations($rows, $cols, $col + 1, $newComb);
		}
	}
	
	function removeCharFromString(array $positions)
	{
		return function($string) use ($positions) {
			foreach ($positions as $pos) {
				$string[$pos] = 'X';
			}
			
			return $string;
		};
	}
}
