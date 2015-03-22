<?php
/// TODO autorizace uzivatele!
/// TODO zmenit nacitani infa do formulare
namespace App\Presenters;

use Nette,
	App\Model;
/**
 * Description of ProjectPresenter
 *
 * @author Luky
 */
class ProjectPresenter extends BasePresenter {
    
    /** @var \App\Model\Projects @inject */
    public $projekty;
    /** @var \App\Model\Users @inject */
    public $uzivatele;
    /** @var Nette\Database\Context */
    private $database;
    
    private $edit;
    

    public function __construct(Nette\Database\Context $database)
	{
		$this->database = $database;
	}
    
    protected function createComponentProjectForm()
    {
        $form = new Nette\Application\UI\Form;

        ///TODO zmenit - nacitat z modelu z Osoby (jen zakazniky) a Tymy (jen aktivni)...
        $tymy = $this->database->table('Tymy');
        $zakaznici = $this->uzivatele->listOfCustomers();
        
        foreach ($tymy as $tym) {
            $arr_tym[$tym->ID] = $tym->popis;
        }
        
        foreach ($zakaznici as $zakaznik) {
            $arr_zakaznik[$zakaznik->ID] = $zakaznik->jmeno;            
        }
        
        $form->addSelect('zakaznik', 'Jmeno zakaznika:', $arr_zakaznik)
                ->setPrompt('Volba zakaznika');
        $form->addSelect('tym', 'Tym:', $arr_tym)
                ->setPrompt('Volba tymu');
        $form->addTextArea('popis', 'Popis:')
                ->addRule(Nette\Application\UI\Form::MAX_LENGTH, 'Popis je prilis dlouhy!', 255);
        $form->addHidden('edit', false);
        $form->addHidden('id_projekt');
        if ($this->edit) {
            $form->addSubmit('ok', 'Zmenit');
        } else {
            $form->addSubmit('ok', 'Pridat');
        }
        $form->onValidate[] = array($this, 'validateProjectForm');
        $form->onSuccess[] = array($this, 'projectFormSucceded');
        return $form;       
    }
    
    /* Pripadna validace - popr smazat! */
    public function validateProjectForm($form)
    {
        $hodnoty = $form->getValues();
        if (!isset($hodnoty->zakaznik)){
            $form->addError('Polozka zakaznika musi byt vyplnena!');
        }
        if (!isset($hodnoty->tym)){
            $form->addError('Polozka tymu musi byt vyplnena!');
        }
        if ($hodnoty->popis == ''){
            $form->addError('Polozka popisu tymu musi byt vyplnena!');
        }
    }
    
    public function projectFormSucceded($form)
    {
        $hodnoty = $form->getValues();
        if (!$hodnoty->edit) {
            try {
                $this->database->beginTransaction();
                $this->projekty->vytvoritProjekt($hodnoty->zakaznik, $hodnoty->tym, $hodnoty->popis);            
                $this->database->commit();
            } catch (Exception $ex){
                $this->database->rollBack();
                $form->addError('Chyba pri praci s databazi, nepridalo se nic!');
            }
        } else {
            try {
                $this->database->beginTransaction();
                $this->projekty->zmenitProjekt($hodnoty->id_projekt, $hodnoty->tym, $hodnoty->zakaznik, $hodnoty->popis);
                $this->database->commit();
            } catch (Exception $ex) {
                $this->database->rollBack();
                $form->addError('Chyba pri praci s databazi, nepridalo se nic!');
            }
        }
        $this->redirect('Project:projects');
    }

    public function handleDelete($id_projekt)
    {
        if ($this->user->isInRole('admin')) {
            $this->projekty->zrusitProjekt($id_projekt);
            $this->flashMessage('Projekt byl uspesne smazan!');
        } else {
            $this->setView('notAllowed');
        }        
    }
    
    public function actionProjects()
    {
        if (!$this->user->isInRole('admin') && !$this->user->isInRole('manažer')) {
            $this->setView('notAllowed');
        }
    }


    public function actionAddProject()
    {
        if (!$this->user->isInRole('admin') && !$this->user->isInRole('manažer')) {
            $this->setView('notAllowed');
        }
    }
    
    public function actionEditProject($id_projekt, $id_tym, $id_zakaznik)
    {
        if ($this->user->isInRole('admin') || $this->user->isInRole('manažer')) {
            $this->edit = true;
            $this['projectForm']->setDefaults(array(
                'zakaznik' => $id_zakaznik,
                'tym' => $id_tym,
                'popis' => $this->projekty->vratPopisProjektu($id_projekt),
                'id_projekt' => $id_projekt,
                'edit' => true,
            ));
            $this->setView('AddProject');
        } else {
            $this->setView('notAllowed');
        }
    }

    public function renderProjects(){
        $this->template->proj = $this->projekty->seznamProjektu();
    }
    
    public function renderAddProject()
    {
        if ($this->edit) {
            $this->template->nadpis = 'Editace projektu';
        } else {
            $this->template->nadpis = 'Pridani projektu';
        }
    }
    
    public function renderEditProject()
    {
        
    }
}
