<?php

namespace App;

use Nette\Application\UI\Form;
use Nette\Security\AuthenticationException;

class LoginPresenter extends BasePresenter {
	protected function createComponentSignInForm()
	{
		$form = new Form;
		$form->getElementPrototype()->id = "login-box";

		$form->addText('username', 'Meno')
			->setRequired('Vložte meno');

		$form->addPassword('password', 'Heslo')
			->setRequired('Vložte heslo');

		$form->addCheckbox('remember', ' Zapamätať prihlásenie');

		$form->addSubmit('send', 'Prihlásiť');

		$form->onSuccess[] = $this->signInFormSucceeded;
		return $form;
	}
	
	public function signInFormSucceeded($form)
	{
		$values = $form->getValues();

		if ($values->remember) {
			$this->getUser()->setExpiration('+14 days', FALSE);
		} else {
			$this->getUser()->setExpiration('+24 hours', TRUE);
		}

		try {
			$this->getUser()->login($values->username, $values->password);
			$this->logModel->save("login", $this->getUser()->getId(), $this->getUser()->getId(), $this->getUser()->getIdentity()->username);
			$this->redirect('Index:');
		} catch (AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}
	
	public function renderDefault($logout)
	{
		if ($this->getUser()->isLoggedIn() && $logout) {
			$this->logModel->save("logout", $this->getUser()->getId(), $this->getUser()->getId(), $this->getUser()->getIdentity()->username);
			$this->getUser()->logout(true);
			$this->redirect("default");
		}
	}
}