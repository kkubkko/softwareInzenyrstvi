<?php

namespace App\Presenters;

use Nette,
	App\Model;


/**
 * User Edit presenters.
 */
class UsereditPresenter extends BasePresenter
{

	    /** @var \App\Model\Users @inject */
    public $users;

	public function renderDefault() {
		
	}

	public function renderUsersList(){
        $this->template->users = $this->users->listOfUsers();
    }
	
	public function actionDelete($id) {
		$this->users->deleteUser($id);
		$this->redirect('useredit:usersList');
	}

		/**
	 * Add User form factory.
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentAddUserForm()
	{
		$form = new Nette\Application\UI\Form;
		$form->addText('jmeno', 'Jméno:')
			->setRequired('Please enter your username.');
		
		$form->addText('login', 'Login:')
			->setRequired('Please enter your login.');

		$form->addPassword('heslo', 'Heslo:')
			->setRequired('Please enter your password.');

		$form->addSubmit('send', 'Uložit');

		// call method signInFormSucceeded() on success
		$form->onSuccess[] = array($this, 'addUserFormSucceeded');
		return $form;
	}


	public function addUserFormSucceeded($form, $values)
	{
		//$UsersModel = $this->context->Users;
		
		try {
			$this->users->addUser($values);
			$this->redirect('Homepage:');
            //$this->redirect('Admin:default');

		} catch (\Nette\Neon\Exception $e) {
			$form->addError($e->getMessage());
		}
	}


}
