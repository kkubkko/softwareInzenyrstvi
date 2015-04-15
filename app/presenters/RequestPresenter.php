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
    private $edit = false;
    
    public function __construct(Nette\Database\Context $database)
	{
		$this->database = $database;
	}

	protected function createComponentAddRequestForm()
	{
		$form = new Nette\Application\UI\Form;
		$form->addText('nazev', 'Název:')
			->setRequired('Prosim uvedte Název.');
		
		$form->addTextArea('popis', 'Popis:')
			->addRule(Form::MAX_LENGTH, 'Poznámka je příliš dlouhá', 255)
			->setRequired('Prosim uvedte Popis.');
        $form->addHidden('id', 0);
		
		if ($this->edit) {
            $form->addSubmit('send', 'Ulozit');
        } else {
            $form->addSubmit('send', 'Pridat');
        }
		// call method signInFormSucceeded() on success
		$form->onSuccess[] = array($this, 'addRequestFormSucceeded');
		return $form;
	}

	public function addRequestFormSucceeded($form, $values)
	{			
        $sluzby = array ( 'nazev' => $values['nazev'],
                          'popis' => $values['popis'] );
        if ($values['id'] == 0) {            
            $this->requests->addService($sluzby);            
        } else {
            $this->requests->editService($sluzby, $values['id']);            
        }
        $this->redirect('Request:services');
	}
    
    protected function createComponentAddDemandForm()
    {
        $form = new Nette\Application\UI\Form;
        
        $demand = $this->requests->listOfServices();
        
        foreach ($demand as $dem){
            $sel_dem[$dem->ID] = $dem->nazev;
        }
        
        $form->addCheckboxList('services', 'Vyber sluzeb', $sel_dem)
                ;//->setRequired('Musite vybrat aspon jednu sluzbu!');
        
        $form->addText('spec','Upresneni poptavky')
                ->addRule(Form::MAX_LENGTH, 'Text je příliš dlouhy', 255)
                ->setRequired('Uvedte prosim upresnujici popis!');
        
        $form->addSubmit('send', 'Pridat');
        
        $form->onSuccess[] = array($this, 'addDemandFormSucceded');
        return $form;        
    }
    
    public function addDemandFormSucceded($form)
    {
        $hodnoty = $form->values;
        
        try {
            $this->database->beginTransaction();
            
            $special = array('text' => $hodnoty->spec);
            $db_spec = $this->requests->addSpecial($special);
            $db_dem = $this->requests->addDemand($db_spec->ID, $this->user->id);
            foreach ($hodnoty->services as $service){
                $this->requests->addDemandService($service, $db_dem->ID);
            }            
            $this->database->commit();
        } catch (Nette\Neon\Exception $ex) {
            $this->database->rollBack();
            $form->addError($ex->getMessage());
        }       
    }

    public function actionEditService($id_service = NULL)
    {
        if ($this->user->isInRole('admin') || $this->user->isInRole('manažer')){
            if (isset($id_service)){
                $service = $this->requests->detailOfService($id_service);
                if (isset($service)){
                    $this['addRequestForm']->setDefaults(array(
                        'id'    => $service->ID,
                        'nazev' => $service->nazev,
                        'popis' => $service->popis,
                    ));
                    $this->edit = true;
                } else {
                    $this->setView('notFound');
                }
            }
        } else {
            $this->setView('notAllowed');
        }        
    }
    
    public function actionDetail($request_id)
    {
		$request = $this->requests->vratRequest($request_id);
        if (!isset($request)){
            $this->setView('notFound');
        } 
    }
    
    public function renderEditService()
    {
        if ($this->edit){
            $this->template->nadpis = 'Editace sluzby';
        } else {
            $this->template->nadpis = 'Pridat sluzbu';
        }
    }


    public function renderDetail($request_id)
    {
		$this->template->requests = $this->requests->vratRequest($request_id);
    }

	public function renderList() {
		$this->template->requests = $this->requests->listOfRequests();
	}
    
    public function renderServices()
    {
        $this->template->sluzby = $this->requests->listOfServices();
    }
}
