<?php

namespace App\Presenters;

use Nette,
	App\Model,
	Nette\Utils\DateTime,
	Nette\Utils\Validators;

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
	
	public function emailValidator($item, $arg) {
		if (Validators::isEmail($item->value)) {
			return TRUE;
		} else {
			return FALSE;
		}
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
		
		$form->addPassword('zopakovaneHeslo', 'Potvrď heslo:')
			->setRequired('Please re-enter your password.');
		
		$form->addText('telefon', 'Telefón:')
				->setType('number');
		
		$form->addText('email', 'Email:')
			->setRequired('Please enter your username.')
			->addRule($this->emailValidator, 'Nesprávny formát emailu.');
		
		$form->addText('ulice', 'Ulice:')
			->setRequired('Please enter your street.');
		
		$form->addText('mesto', 'Mesto:')
			->setRequired('Please enter your city.');
		
		$form->addText('psc', 'PSČ:')
				->setType('number')
			->setRequired('Please enter your psč.');
		
	$roles = $this->users->roles();
	foreach ($roles as $role) {
            $arr_roles[$role->ID] = $role->nazev;
        }
		$form->addSelect('role', 'Role:', $arr_roles)
				->setRequired('Please enter your role.')
                ->setPrompt('Volba role');

		$form->addSubmit('send', 'Uložit');

		$form->onValidate[] = array($this, 'validateAddUserForm');
		// call method signInFormSucceeded() on success
		$form->onSuccess[] = array($this, 'addUserFormSucceeded');
		return $form;
	}

	
	public function validateAddUserForm($form) {
		$values = $form->getValues();

		if ($values["heslo"] != $values["zopakovaneHeslo"]) { // validační podmínka
			$form->addError('Heslá sa nezhodujú');
		}
	}

	public function addUserFormSucceeded($form, $values)
	{
		//$UsersModel = $this->context->Users;
		
		try {
			
			$user = array( 'jmeno' => $values['jmeno'], 
						   'heslo' => $values['heslo']);
			$userId = $this->users->addUser($user);
			
			
			$contacts = array ( 'osoba_id' => $userId,
								'telefon' => $values['telefon'],
								'email' => $values['email'],
								'ulice' => $values['ulice'],
								'mesto' => $values['mesto'],
								'psc' => $values['psc']);
			$this->users->addContacts($contacts);
			
			$date = DateTime::getTimestamp();
			$role= array(	'role_id' => $values['role'],
							'osoba_id' => $userId,
							'datum_prirazeni' => $date);
			$this->users->addRole($role);
			
			$this->redirect('Homepage:');
            //$this->redirect('Admin:default');

		} catch (\Nette\Neon\Exception $e) {
			$form->addError($e->getMessage());
		}
	}


}
