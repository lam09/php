<?php

namespace App;

use Model\LogModel;

/**
 * Description of EventPresenter
 *
 * @author e.szabo
 */
class EventPresenter extends BasePresenter {
	public function renderDefault($id)
	{
		$this->template->events = $this->logModel->getLastEvents($id);
		$this->template->eventTypes = LogModel::$EVENT_TYPES;
		$this->template->eventType = $id;
	}
}
