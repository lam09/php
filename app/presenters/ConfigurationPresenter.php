<?php

namespace App;

use Model\NastavenieModel;
use Model\GameModel;
use Nette\Utils\Paginator;
use Nette\Application\UI\Form;

class ConfigurationPresenter extends BasePresenter {
	const TRANSACTIONS_PER_PAGE = 20;

	/** @var NastavenieModel @inject */
	public $configModel;
	/** @var GameModel @inject */
	public $gameModel;

	private $gameConfigs;
	private $gameConfigId;
	private $gameConfig;


	function actionGameConfig($id = null)
	{
		$this->gameConfigs =  $this->configModel->getGameConfigs();

		if (empty($id) && count($this->gameConfigs) > 0) {
			$this->gameConfigId = $this->gameConfigs[0]->id;
		} else {
			$this->gameConfigId = $id;
		}
	}

	function renderDefault($id = null, $page = 0)
	{
		$this->template->machines = $this->machines;
		$this->template->machine = $this->machine;
		$this->template->spinCount = $this->winsModel->getSpinCountByMachine($this->machineId);
		
		$depositTranCount = $this->machineModel->getDepositCountByMachine($this->machineId);
		$payoutTranCount = $this->machineModel->getPayoutCountByMachine($this->machineId);
		$totalTransactions = $depositTranCount->count + $payoutTranCount->count;
		
		$paginator = new Paginator();
		$paginator->setBase(0);
		$paginator->setItemCount($totalTransactions);
		$paginator->setItemsPerPage(static::TRANSACTIONS_PER_PAGE);
		$paginator->setPage($page);
		
		$this->template->transactions = $this->machineModel->getTransactionsByMachine($this->machineId, $paginator->getOffset(), $paginator->getLength());
		$this->template->page = $page;
		$this->template->offset = $page * static::TRANSACTIONS_PER_PAGE;
		$this->template->paginator = $paginator;
		$this->template->deposits = $this->machineModel->getDepositAmountByMachine($this->machineId);
		$this->template->payouts = $this->machineModel->getPayoutAmountByMachine($this->machineId);
	}

	function createComponentGameConfigForm()
	{
		$form = new Form;

		$games = $this->configModel->getGamesWithConfig($this->gameConfigId);

		$sub = $form->addContainer('gamelist');
		foreach ($games as $game) {
			if(!is_null($game->config_id)) {
				$sub->addCheckbox($game->id, $game->name)->setDefaultValue(TRUE);
			} else {
				$sub->addCheckbox($game->id, $game->name);
			}
		}
		$form->addSubmit("submit", "Uložiť");

		$form->onSuccess[] = array($this, "gameConfigFormSubmitted");
		return $form;
	}

	function gameConfigFormSubmitted(Form $form)
	{
		$values = $form->getValues();

		$this->configModel->setGameConfig($this->gameConfigId,$values->gamelist);

		$this->redirect("this");
	}


	function renderGameConfig($id){

		//$this->gameConfig =  $this->configModel->getGameConfig($this->gameConfigId);

		$this->template->games = $this->gameModel->getGames();

		$this->template->gameConfigs = $this->gameConfigs;
		$this->template->gameConfigId = $this->gameConfigId;

		//$this->template->gameConfig = $this->gameConfig;
	}

}
