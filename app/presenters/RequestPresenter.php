<?php

namespace App\Presenters;

use Nette,
	App\Model,
	Nette\Application\UI\Form,
	Nette\Utils\DateTime,
	Nette\Utils\Validators;

/**
 * User Edit presenters.
 */
class RequestPresenter extends BasePresenter
{

	/** @var \App\Model\Requests @inject */
    public $requests;
    /** @var Nette\Database\Context */
    private $database;
    
    public function __construct(Nette\Database\Context $database)
	{
		$this->database = $database;
	}

	public function renderDefault() {
		
	}

		/**
	 * Add User form factory.
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentAddRequestForm()
	{
		$form = new Nette\Application\UI\Form;
		$form->addText('nazev', 'Názov:')
			->setRequired('Please enter your Názov.');
		
		$form->addTextArea('popis', 'Popis:')
			->addRule(Form::MAX_LENGTH, 'Poznámka je příliš dlouhá', 360)
			->setRequired('Please enter your Popis.');

		$form->addTextArea('specialni', 'Specialni:')
				->addRule(Form::MAX_LENGTH, 'Specialni je příliš dlouhá', 360);
		
		$form->addSubmit('send', 'Odeslat');
		// call method signInFormSucceeded() on success
		$form->onSuccess[] = array($this, 'addRequestFormSucceeded');
		return $form;
	}

	public function addRequestFormSucceeded($form, $values)
	{
		
		try {
			$this->database->beginTransaction();
			$specialni = array( 'text' => $values['specialni'] );
			$special = $this->requests->addSpecial($specialni);
			
			
			$sluzby = array ( 'nazev' => $values['nazev'],
							  'popis' => $values['popis'] );
			$service = $this->requests->addService($sluzby);
			
			$this->requests->addRequest($special->ID, $service->ID, $this->user->id);
			
			$this->database->commit();
			
			$this->redirect('Homepage:');
			
		} catch (\Nette\Neon\Exception $e) {
            $this->database->rollBack();
			$form->addError($e->getMessage());
		}
	}

	public function actionDetail($request_id)
    {
		$request = $this->requests->vratRequest($request_id);
        if (!isset($request)){
            $this->setView('notFound');
        } 
    }
	
	public function renderDetail($request_id)
    {
		$this->template->requests = $this->requests->vratRequest($request_id);
    }

	public function renderList() {
		$this->template->requests = $this->requests->listOfRequests();
	}
}
