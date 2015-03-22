<?php

namespace App\Presenters;

use Nette,
	App\Model;


/**
 * Sign in/out presenters.
 */
class SignPresenter extends BasePresenter
{


	/**
	 * Sign-in form factory.
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentSignInForm()
	{
		$form = new Nette\Application\UI\Form;
                $form->addText('login', 'Uživatelské jméno:')
                    ->setRequired('Prosím vyplňte své uživatelské jméno.')
                    ->setAttribute('placeholder', 'Uživatelské jméno');
                

                $form->addPassword('password', 'Heslo:')
                    ->setRequired('Prosím vyplňte své heslo.')
                    ->setAttribute('placeholder', 'Heslo');

                $form->addCheckbox('remember', 'Zůstat přihlášen');

                $form->addSubmit('send', 'Přihlásit');
                

                $form->onSuccess[] = array($this, 'signInFormSucceeded');
                return $form;
	}


	public function signInFormSucceeded($form, $values)
	{
		if ($values->remember) {
			$this->getUser()->setExpiration('14 days', FALSE);
		} else {
			$this->getUser()->setExpiration('20 minutes', TRUE);
		}

		try {
			$this->getUser()->login($values->login, $values->password);
			$this->redirect('Homepage:');
            //$this->redirect('Admin:default');

		} catch (Nette\Security\AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}


	public function actionOut()
	{
		$this->getUser()->logout();
                $this->flashMessage('Odhlášení bylo úspěšné.');
                $this->redirect('Homepage:');
	}

}
