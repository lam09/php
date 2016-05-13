<?php

namespace App;

use Model\LogModel;
use Nette\Application\UI\Presenter;

abstract class BasePresenter extends Presenter {
	/** @var LogModel @inject */
	public $logModel;
	
	protected function startup()
	{
		parent::startup();
		
		if (!$this->getUser()->isLoggedIn() && $this->getName() != "Login") {
			$this->redirect("Login:");
		}
	}
	
	protected function createTemplate($class = NULL) {
		$template = parent::createTemplate($class);
		$template->addFilter(null, "\Filters::common");
		return $template;
	}
}
