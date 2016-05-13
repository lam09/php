<?php

namespace App;

use Model\GameModel;
use Model\SymbolModel;

/**
 * Description of SymbolPresenter
 *
 * @author e.szabo
 */
class SymbolPresenter extends BasePresenter {
	/** @var GameModel @inject */
	public $gameModel;
	/** @var SymbolModel @inject */
	public $symbolModel;
	
	private $gameId;
	private $games;
	
	public function actionDefault($id)
	{
		$this->games = $this->gameModel->findGameNames();
		$this->gameId = (empty($id)) ? key($this->games) : $id;
	}
	
	public function actionCreate()
	{
		$games = $this->gameModel->findAll();
		
		foreach ($games as $game) {
			for ($i = 1; $i <= $game->symbols; $i++) {
				$this->symbolModel->add(array(
					"game_id" => $game->id,
					"symbol_id" => $i
				));
			}
		}
		
		echo "OK";
		$this->terminate();
	}
	
	public function handleToggleSymbol($symbolId)
	{
		$symbol = $this->symbolModel->findOneBy($symbolId);
		if ($symbol) {
			$newSetting = ($symbol->special == 0) ? 1 : 0;
			$this->symbolModel->edit($symbolId, $newSetting);
			$this->logModel->save("special_symbol", $symbolId, $this->getUser()->getId(), $this->getUser()->getIdentity()->username);
		}
		
		$this->redrawControl("symbols");
	}
	
	public function renderDefault($id)
	{
		$this->template->symbols = $this->symbolModel->findByGame($this->gameId);
		$this->template->games = $this->gameModel->findGameNames();
		$this->template->gameId = $this->gameId;
		$this->template->gameName = $this->games[$this->gameId];
	}
}
