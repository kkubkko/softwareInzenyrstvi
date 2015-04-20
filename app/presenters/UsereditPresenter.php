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
    /** @var Nette\Database\Context */
    private $database;
	private $isNewEmployee = FALSE;
	private $edit = FALSE;
	private $userId = 0;
        
        
    protected function startup()
    {
        parent::startup();         
    }
    
    public function __construct(Nette\Database\Context $database)
	{
		$this->database = $database;
	}

	public function renderDefault() {
		
	}

	public function renderUsersList(){
        $this->template->employees = $this->users->listOfEmployes();
		$this->template->customers = $this->users->listOfCustomers();
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
			->setRequired('Prosím zadejte jméno.');
		
		$form->addText('login', 'Login:')
			->setRequired('Prosím zadejte příjmení.');

		$form->addPassword('heslo', 'Heslo:')
			->setRequired('Prosím zadejte heslo.');
		
		$form->addPassword('zopakovaneHeslo', 'Potvrď heslo:')
			->setRequired('Prosím zopakujte heslo.');
		
		$form->addText('telefon', 'Telefón:')
				->setRequired('Prosím zadejte telefónní číslo.')
				->setType('number');
		
		$form->addText('email', 'Email:')
			->setRequired('Prosím zadejte email.')
			->addRule($this->emailValidator, 'Nesprávny formát emailu.');
		
		$form->addText('mesto', 'Mesto:')
			->setRequired('Prosím zadejte město.');
		
		$form->addText('ulice', 'Ulice:')
			->setRequired('Prosím zadejte ulici.');
		
		$form->addText('psc', 'PSČ:')
				->setType('number')
			->setRequired('Prosím zadejte psč.');
		
	
	if ($this->edit == FALSE) {
		
			if ($this->isNewEmployee == TRUE) {
			$roles = $this->users->rolesForEmployees();
		} else {
			$roles = $this->users->rolesForCustomer();
		}

		foreach ($roles as $role) {
				$arr_roles[$role->ID] = $role->nazev;
			}
			$form->addSelect('role', 'Role:', $arr_roles)
					->setRequired('Prosím zadejte roli.')
					->setPrompt('Volba role');
			
	} 
		
		$form->addSubmit('send', 'Uložit');

		$form->onValidate[] = array($this, 'validateAddUserForm');
		// call method signInFormSucceeded() on success
		$form->onSuccess[] = array($this, 'addUserFormSucceeded');
		return $form;
	}

	
	public function validateAddUserForm($form) {
		$values = $form->getValues();

		if ($values["heslo"] != $values["zopakovaneHeslo"]) { // validační podmínka
			$form->addError('Heslá se neshodují');
		}
	}

	public function addUserFormSucceeded($form, $values)
	{
		//$UsersModel = $this->context->Users;
		
		try {
			$this->database->beginTransaction();
			
			if ($this->edit == TRUE) {
				$user = array(	'ID' => $this->userId, 
							'jmeno' => $values['jmeno'],
                           'login' => $values['login'], 
						   'heslo' => $values['heslo']);
				
				$contacts = array ( 'osoba_id' => $this->userId,
								'telefon' => $values['telefon'],
								'email' => $values['email'],
								'ulice' => $values['ulice'],
								'mesto' => $values['mesto'],
								'psc' => $values['psc']);
			} else {
				$user = array( 'jmeno' => $values['jmeno'],
                           'login' => $values['login'], 
						   'heslo' => $values['heslo']);
				$userId = $this->users->addUser($user);
				$contacts = array ( 'osoba_id' => $userId,
								'telefon' => $values['telefon'],
								'email' => $values['email'],
								'ulice' => $values['ulice'],
								'mesto' => $values['mesto'],
								'psc' => $values['psc']);
			}
			
			
			
			if ($this->edit == TRUE) {
				
				$this->users->editUser($user);
				$this->users->editKontakty($contacts);
				
			} else {
				$this->users->addContacts($contacts);
				
				$date = /*DateTime::getTimestamp();*/ date('Y-m-d');
				$role= array(	'role_id' => $values['role'],
							'osoby_id' => $userId,
							'datum_prirazeni' => $date);
				$this->users->addRole($role);
			
				if ($this->isNewEmployee == TRUE) {
				$role= array('role_id' => '2',
							'osoby_id' => $userId,
							'datum_prirazeni' => $date);
				$this->users->addRole($role);
				}
			}
            
            $this->database->commit();
			
			if ($this->edit == TRUE) {
				$this->flashMessage('Uživatel byl aktualizován');
			} else {
				$this->flashMessage('Uživatel byl vytvořen');
			}
			
			$this->redirect('Useredit:usersList');
			
            //$this->redirect('Admin:default');

		} catch (\Nette\Neon\Exception $e) {
            $this->database->rollBack();
			$form->addError($e->getMessage());
		}
	}

	public function actionDetail($user_id)
    {
        if (!$this->user->isInRole('admin') && !$this->user->isInRole('manažer')) {
            $this->setView('notAllowed');
        }
        
		$user = $this->users->vratUser($user_id);
		$kontakt = $this->users->vratKontakty($user_id);
        if (!isset($user) || !isset($kontakt)){
            $this->setView('notFound');
        } 
    }
	
	public function renderDetail($user_id)
    {
		$this->template->isCustomer = FALSE;
		
        $user = $this->users->vratUser($user_id);
		
		$projekty = $this->users->vratProjektyZakaznika($user_id);
		$this->template->projekty = $projekty;

		$tymy = $this->users->vratTymy($user_id);
		$this->template->tymy = $tymy;
		
		$this->template->uzivatel = $user;
		$this->template->kontakt = $this->users->vratKontakty($user_id);
		
		$roles = $this->users->vratRoluPreUsera($user_id);
		
		foreach ($roles as $role) {
			if ($role->role->nazev == 'zákazník') {
				$this->template->isCustomer = TRUE;
			}
		}
		
    }

	public function actionNewEmployee() {
		
		$this->isNewEmployee = TRUE;
		
	}
	
	public function actionEditUser($id_user)
    {
        if ($this->user->isInRole('admin') || $this->user->isInRole('manažer')) {
            $this->edit = true;
			$this->userId = $id_user;
			
			$user = $this->users->vratUser($id_user);
			$kontakty = $this->users->vratKontakty($id_user);
			
            $this['addUserForm']->setDefaults(array(
                'jmeno' => $user->jmeno,
                'login' => $user->login,
				'telefon' => $kontakty->telefon,
				'email' => $kontakty->email,
				'ulice' => $kontakty->ulice,
				'mesto' => $kontakty->mesto,
				'psc' => $kontakty->psc
            ));
            $this->setView('default');
        } else {
            $this->setView('notAllowed');
        }
    }
	
}
