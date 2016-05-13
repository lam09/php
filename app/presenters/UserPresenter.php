<?php

namespace App;

use Model\UserModel;
use Nette\Application\UI\Form;

/**
 * Description of UserPresenter
 *
 * @author e.szabo
 */
class UserPresenter extends BasePresenter {
	/** @var UserModel @inject */
	public $userModel;
	
	private $roles = array(
		"admin" => "Správca",
		"user" => "Používateľ"
	);
	
	function startup() {
		parent::startup();
		
		if ($this->getUser()->getIdentity()->role != "admin") {
			$this->flashMessage("Táto sekcia je prístupná len používateľom s rolou správcu.", "danger");
			$this->redirect("Index:default");
		}
	}
	
	function createComponentAddUserForm()
	{
		$form = new Form();
		$form->addText("username", "Meno používateľa:")
			->setRequired();
		$form->addPassword("password", "Heslo:")
			->addRule(Form::MIN_LENGTH, "Minimálna dĺžka hesla je %d znakov.", 8)
			->setRequired();
		$form->addText("name", "Meno:");
		$form->addSelect("role", "Rola:", $this->roles)->setDefaultValue("user");
		$form->addSubmit("submit", "Odoslať");
		$form->onSuccess[] = array($this, "addUserFormSubmitted");
		
		return $form;
	}
	
	function addUserFormSubmitted(Form $form)
	{
		$values = $form->getValues();
		$duplicate = $this->userModel->findByUsername($values->username);
		
		if (!$duplicate) {
			$userId = $this->userModel->addUser($values->username, $values->password, $values->name, $values->role);
			$this->logModel->save("user_added", $userId, $this->getUser()->getId(), $this->getUser()->getIdentity()->username);
			$this->flashMessage("Používateľ úspešne vytvorený.", "success");
		} else {
			$this->flashMessage("Používateľ {$values->username} už existuje.", "danger");
		}
		
		$this->redirect("this");
	}
	
	function handleDeleteUser($userId)
	{
		if ($userId != $this->getUser()->getIdentity()->id) {
			$this->userModel->delete($userId);
			$this->logModel->save("user_removed", $userId, $this->getUser()->getId(), $this->getUser()->getIdentity()->username);
			$this->flashMessage("Používateľ úspešne odstránený.", "info");
		} else {
			$this->flashMessage("Nemôžete odstrániť seba!", "danger");
		}
		
		$this->redirect("this");
	}
	
	function renderDefault()
	{
		$this->template->users = $this->userModel->findAll();
		$this->template->roles = $this->roles;
	}
}
