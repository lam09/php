<?php

namespace App;

use Model\GameModel;
use Model\WintableModel;
use Nette\Application\UI\Form;

/**
 * Description of WintablePresenter
 *
 * @author e.szabo
 */
class WintablePresenter extends BasePresenter {
	const MIN_SYMBOL_COUNT = 1;
	
	/** @var GameModel @inject */
	public $gameModel;
	/** @var WintableModel @inject */
	public $wintableModel;
	
	private $games;
	private $gameId;
	private $game;
	private $bet;
	
	private $bets = array(
		'0.1' => '0,10 €',
		'0.3' => '0,30 €',
		'0.5' => '0,50 €',
		'0.7' => '0,70 €',
		'1'   => '1 €',
		'1.5' => '1,50 €',
		'2'   => '2 €',
		'2.5' => '2,50 €',
		'3'   => '3 €',
		'3.5' => '3,50 €',
		'4'   => '4 €',
		'4.5' => '4,50 €',
		'5'   => '5 €',
		'6'   => '6 €',
		'7'   => '7 €',
		'8'   => '8 €',
		'9'   => '9 €',
		'10'  => '10 €'
	);

	private $betsToxicCity = array(
		'0.1' => '0,10 €',
		'0.2' => '0,20 €',
		'0.5' => '0,50 €',
		'1'   => '1 €',
		'2'   => '2 €',
		'5'   => '5 €',
		'10'  => '10 €'
	);
	
	public function actionDefault($id, $bet = "0.1")
	{
		$this->games = $this->gameModel->findGameNames();
		$this->gameId = (empty($id)) ? key($this->games) : $id;
		$this->game = $this->gameModel->findOneBy($this->gameId);
		$this->bet = $bet;
		
		if (!$this->game) {
			$this->redirect("this", array("id" => null));
		}
	}
	
	function createComponentWinCombinationForm()
	{
		$form = new Form();
		$form->addText("win", "Výhra:")
			->setRequired()
			->addRule(Form::FLOAT, "Neplatné číslo");
		
		$symbolCounts = range(static::MIN_SYMBOL_COUNT, $this->game->columns);
		$form->addSelect("symbolCount", "Počet symbolov:")
			->setItems($symbolCounts, false);
		
		$symbols = range(1, $this->game->symbols);
		$form->addSelect("symbol")
			->setItems($symbols, false);
		
		$form->addSubmit("submit", "Pridať");
		$form->onSuccess[] = array($this, "winCombFormSubmitted");
		
		return $form;
	}
	
	function winCombFormSubmitted(Form $form)
	{
		$values = $form->getValues();
		$combination = $this->wintableModel->findCombination($this->gameId, $values->symbol, $values->symbolCount, $this->bet);
		
		if (!$combination) {
			$rowId = $this->wintableModel->add(array(
				"game_id" => $this->gameId,
				"symbol_id" => $values->symbol,
				"symbol_count" => $values->symbolCount,
				"bet" => $this->bet,
				"win" => $values->win
			));
			
			$this->logModel->save("combination_added", $rowId, $this->getUser()->getId(), $this->getUser()->getIdentity()->username);
		} else {
			$this->flashMessage("Táto kombinácia už eixstuje", "danger");
		}
		
		$this->redrawControl("table");
	}
	
	public function handleRemoveCombination($combId)
	{
		$this->wintableModel->delete($combId);
		$this->logModel->save("combination_removed", $combId, $this->getUser()->getId(), $this->getUser()->getIdentity()->username);
		$this->redrawControl("table");
	}
	
	public function renderDefault($id, $bet)
	{
		$this->template->games = $this->gameModel->findGameNames();
		$this->template->gameId = $this->gameId;
		$this->template->gameName = $this->games[$this->gameId];
		$this->template->bets = $this->gameId == 8 ? $this->betsToxicCity : $this->bets;
		$this->template->currentBet = $this->bet;
		
		$combinations = $this->wintableModel->findCombinations($this->gameId, $this->bet);
		$groupedCombs = array();
		
		foreach ($combinations as $comb) {
			$groupedCombs[$comb->symbol_id][] = $comb;
		}
		
		$this->template->combinations = $groupedCombs;
	}
}
