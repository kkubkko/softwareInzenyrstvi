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
    /** @var \App\Model\Projects @inject */
    public $projects;
    /** @var \App\Model\Documents @inject */
    public $documents;
    /** @var \App\Model\Versions @inject */
    public $versions;
    /** @var Nette\Database\Context */
    private $database;
    private $edit = false;
    private $accept = true; 
    private $customer;
    private $identif;
    
    
    protected function startup()
    {
        parent::startup();         
    }
    
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
        $this->redirect('Request:userDemand');
    }
    
    protected function createComponentAcceptForm()
    {
        $form = new Nette\Application\UI\Form;
        
        if ($this->accept) {
            //$pole = $this->projects->seznamProjektuUzivateleEtapa($this->customer,'tvorba požadavků');
            $pole = $this->projects->seznamProjektuBezPoptavky($this->customer);
            $popis = 'Priradit k projektu';
        } else {
            $pole = $this->requests->listOfUsersActiveDemands($this->customer); 
            $popis = 'Priradit poptavku z data';
        }
        foreach ($pole as $prvek){
            if (!$this->accept) {
                $sel_pole[$prvek->ID] = $prvek->datum;                
            } else {
                $sel_pole[$prvek->ID] = $prvek->popis;
            }
        }
        
        $form->addHidden('accept', $this->accept);
        $form->addHidden('zakaznik',  $this->customer);
        $form->addHidden('identif', $this->identif);// id_project or id_demand
        $form->addSelect('prvek',$popis,$sel_pole)
                ->setPrompt('Vyberte')
                ->setRequired('Musite vyvrat jednu polozku!');
        $form->addSubmit('ok', 'Priradit');
        $form->onSuccess[] = array($this, 'acceptFormSucceded');
        return $form;
    }
    
    public function acceptFormSucceded($form)
    {
        $hodnoty = $form->values;
        try {
            $this->database->beginTransaction();
            $id_zakaznik = $hodnoty->zakaznik;
            if ($hodnoty->accept){
                $id_demand = $hodnoty->identif;
                $id_project = $hodnoty->prvek;               
            } else {
                $id_project = $hodnoty->identif;
                $id_demand = $hodnoty->prvek;               
            }
            $proj = $this->projects->vratProjekt($id_project);
            //$doc = $this->documents->vratDokument($proj->dokument_id);
            $verze = $this->versions->vytvoritDalsiVerzi($proj->dokument_id);
            $this->documents->novaVerzeDokumentu($proj->dokument_id);
            $this->requests->priradPoptavkuDoPozadavku($id_demand, $verze->ID);
            $this->projects->priraditPoptavkuProjektu($id_project, $id_demand);
            $this->database->commit();
        } catch (Nette\Neon\Exception $ex) {
            $this->database->rollBack();
            $form->addError($ex->getMessage());           
        }
        $this->redirect('Version:version', $proj->dokument_id);
            
    }
    
    public function handleOdmitnout($id_demand)
    {
        if ($this->user->isInRole('admin') || $this->user->isInRole('manažer')){
            $this->requests->rejectDemand($id_demand);
            $this->redirect('Request:list');
        } else {
            $this->redirect('notAllowed');
        }
    }
    
    public function handleDokument($id_demand)
    {
        if ($this->user->isInRole('admin') || $this->user->isInRole('manažer')){
            $proj = $this->projects->vratProjektPoptavky($id_demand);
            if (!$proj) {
                $this->redirect('notFound');
            } else {
                $this->redirect('Version:version', $proj->dokument_id);
            }
        } else {
            $this->redirect('notAllowed');
        }        
    }

    public function actionEditService($id_service = NULL)
    {
        if ($this->user->isInRole('admin') || $this->user->isInRole('manažer')){
            if (isset($id_service)){
                $service = $this->requests->detailOfService($id_service);
                if ($service){
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
        if ($this->user->isLoggedIn() /*&& ($this->user->isInRole('zákazník') || $this->user->isInRole('admin') || $this->user->isInRole('manažer'))*/) {
            $request = $this->requests->vratDemand($request_id);
            if (!$request){
                $this->setView('notFound');
            }
        } else {
            $this->setView('notAllowed');
        }
    }
    
    public function actionAddDemand()
    {
        if (!$this->user->isLoggedIn() /*&& ($this->user->isInRole('zákazník') || $this->user->isInRole('admin') || $this->user->isInRole('manažer'))*/){
            $this->setView('notAllowed');
        }
    }
    
    public function actionUserDemand()
    {
        if (!$this->user->isLoggedIn() /*&& $this->user->isInRole('zákazník')*/){
            $this->setView('notAllowed');
        }
    }
    
    public function actionAcceptDemand($id_demnad)
    {
        if ($this->user->isInRole('admin') || $this->user->isInRole('manažer')){
            $dem = $this->requests->vratDemand($id_demnad);
            if (!$dem){
                $this->setView('notFound');
            } else {
                $proj = $this->projects->seznamProjektuBezPoptavky($dem->osoba_id);
                if ($proj->count() <= 0) {
                    $this->setView('notFound');
                } else {
                    $this->accept = true;
                    $this->customer = $dem->osoba_id;
                    $this->identif = $id_demnad;
                }
            }
        } else {
            $this->setView('notAllowed');
        }        
    }
    
    public function actionAddDemandProject($id_project)
    {
        if ($this->user->isInRole('admin') || $this->user->isInRole('manažer')){
            $proj = $this->projects->vratProjekt($id_project);
            if (!$proj){
                $this->setView('notFound');
            } else {
                $dem = $this->requests->listOfUsersActiveDemands($proj->zakaznik_id);
                if ($dem->count() <= 0) {
                    $this->setView('notFound');
                } else {
                    $this->accept = false;
                    $this->customer = $proj->zakaznik_id;
                    $this->identif = $id_project;
                    $this->setView('acceptDemand');
                }
            }            
        } else {
            $this->setView('notAllowed');
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
        $dem = $this->requests->vratDemand($request_id);
        $this->template->demand = $dem;
        $this->template->services = $this->requests->vratDemandServices($dem->ID); 
    }
    
    public function actionList()
    {
        if (!$this->user->isInRole('admin') && !$this->user->isInRole('manažer')){
            $this->setView('notAllowed');
        }
    }

    public function renderList() {
        $this->template->demands = $this->requests->listOfActiveDemands();
        $this->template->rejected = $this->requests->listOdDeactiveDemands();
    }
    
    public function renderServices()
    {
        $this->template->sluzby = $this->requests->listOfServices();
    }
    
    public function renderAddDemand()
    {
        
    }
    
    public function renderUserDemand()
    {
        $dem = $this->requests->listOfUsersDemands($this->user->id);
        $this->template->demands = $dem;
    }
    
    public function renderAcceptDemand()
    {
        if ($this->accept){
            $nadpis = 'Akceptovat poptavku';
        } else {
            $nadpis = 'Prirazeni poptavky';
        }
        $this->template->nadpis = $nadpis;
    }
}
