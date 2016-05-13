<?php

namespace App;

use Model\GameModel;
use Model\MachineModel;
use Model\NastavenieModel;
use Model\WinsModel;
use Nette\Application\UI\Form;
use Nette\Utils\Paginator;

/**
 * Description of MachinePresenter
 *
 * @author e.szabo
 */
class MachinePresenter extends BasePresenter {
	const TRANSACTIONS_PER_PAGE = 20;
	
	/** @var MachineModel @inject */
	public $machineModel;
	/** @var WinsModel @inject */
	public $winsModel;
	/** @var GameModel @inject */
	public $gameModel;
	/** @var NastavenieModel @inject */
	public $configModel;
	
	private $machines;
	private $machineId;
	private $machine;

	function Init($id = null, $page = 0){
		$this->machines = $this->machineModel->getAllMachines();

		if (empty($id) && count($this->machines) > 0) {
			$this->machineId = $this->machines[0]->id;
		} else {
			$this->machineId = $id;
		}

		foreach ($this->machines as $machine) {
			if ($machine->id == $this->machineId) {
				$this->machine = $machine;
			}
		}
	}
	
	function actionDefault($id = null, $page = 0)
	{
		$this->Init($id,$page);
	}
	
	function createComponentEditForm()
	{
		$form = new Form();
		$form->addText("description", "Popis")->setDefaultValue($this->machine->description);
		$form->addCheckbox("enabled", "Stroj povolený")->setDefaultValue($this->machine->enabled);

		$form->addSelect('config', 'Config HW/SF', $this->configModel->getConfigsArray())->setDefaultValue($this->machine->configuration);
		$form->addSelect('config_game', 'Config hier', $this->configModel->getGameConfigsArray())->setDefaultValue($this->machine->configuration_game);

		$form->addSubmit("submit", "Uložiť");
		
		$form->onSuccess[] = array($this, "editFormSubmitted");
		return $form;
	}
	
	function editFormSubmitted(Form $form)
	{
		$values = $form->getValues();

		$params = array(
				"description" => $values->description,
				"enabled" => $values->enabled,
				"configuration" => $values->config,
				"configuration_game" => $values->config_game
		);

		if($params["configuration"] == "") $params["configuration"] = null;
		if($params["configuration_game"] == "") $params["configuration_game"] = null;

		$this->machineModel->update($this->machineId, $params);
		
		$this->redirect("this");
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

	function renderStatus($page = 0){

		$this->Init();

		$this->template->machines = $this->machines;

		$json = @file_get_contents('http://localhost:8000/online');
		$onlineobj = json_decode($json);

		$machinesarray = array();
		foreach($this->machines as $tmachine){
			$machinesarray[$tmachine->id] = $tmachine;
			$machinesarray[$tmachine->id]->online = false;
		}

		if($json === FALSE) { return; }
		foreach($onlineobj->onlinemachines as $onlinestatus){
			$machinesarray[$onlinestatus]->online = true;
		}


	/*	$paginator = new Paginator();
		$paginator->setBase(0);
		$paginator->setItemCount(sizeof($this->machines));
		$paginator->setItemsPerPage(static::TRANSACTIONS_PER_PAGE);
		$paginator->setPage($page);

		$this->template->page = $page;
		$this->template->offset = $page * static::TRANSACTIONS_PER_PAGE;
		$this->template->paginator = $paginator;*/
	}

}
