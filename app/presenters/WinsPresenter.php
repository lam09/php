<?php

namespace App;

use DateTime;
use Model\WinsModel;

class WinsPresenter extends BasePresenter {
    /** @var WinsModel @inject */
	public $winsModel;
	
	private $date;
	private $ranges = array(
		"hour" => "Hodina",
		"day" => "Deň",
		"month" => "Mesiac",
		"year" => "Rok"
	);
	
	public function actionDefault($date = null)
	{
		if (array_key_exists($date, $this->ranges)) {
			$this->date = $date;
		} else {
			$this->date = "hour";
		}
	}

    public function renderDefault($date = null)
	{
		$to = new DateTime();
		$from = new DateTime();
		switch ($this->date) {
			case "hour":
				$from->setTime(date('H'), 0, 0);
				$splitBy = "min";
				break;
			case "day":
				$from->setTime(0, 0, 0);
				$splitBy = "hour";
				break;
			case "month":
				$from->setTime(0, 0, 0);
				$from->setDate(date('Y'), date('m'), 1);
				$splitBy = "day";
				break;
			case "year":
				$from->setTime(0, 0, 0);
				$from->setDate(date('Y'), 1, 1);
				$splitBy = "month";
				break;
		}
		
		$vyhernost = $this->winsModel->getWinningRates($from, $to, $splitBy);

		$vyhernosthra = $this->winsModel->getWinningRateForGame(1);
		
		$this->template->vyhernost = implode(",", $vyhernosthra);
		$this->template->range = $this->date;
		$this->template->ranges = $this->ranges;
	}
}