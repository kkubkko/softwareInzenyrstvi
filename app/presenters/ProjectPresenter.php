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
    /** @var \App\Model\Documents @inject */
    public $dokumenty;
    /** @var Nette\Database\Context */
    private $database;
    
    private $editTym;
    

    public function __construct(Nette\Database\Context $database)
	{
		$this->database = $database;
	}
    
    protected function createComponentEditProjectForm()
    {
        $form = new Nette\Application\UI\Form;
        
        ///TODO zmenit - nacitat z modelu z Osoby (jen zakazniky) a Tymy (jen aktivni)...
        if ($this->editTym) {
            $poms = $this->database->table('Tymy');
            foreach ($poms as $pom) {
                $arr[$pom->ID] = $pom->popis;
            }
            $form->addSelect('polozka', 'Novy tym', $arr)
                    ->setPrompt('Nastav novy tym');
        } else {
            $poms = $this->database->table('Osoby');
            foreach ($poms as $pom) {
                $arr[$pom->ID] = $pom->jmeno;
            }
            $form->addSelect('polozka', 'Novy zakaznik', $arr)
                    ->setPrompt('Nastav noveho zakaznika');
        }
        $form->addHidden('editTym');
        $form->addHidden('id_projekt');
        $form->addSubmit('ok', 'Zmenit');
        $form->onValidate[] = array($this, 'validateEditProjectForm');
        $form->onSuccess[] = array($this, 'editProjectFormSucceded');
        return $form;        
    }
    
    public function validateEditProjectForm($form)
    {
        $hodnoty = $form->getValues();
        if (!isset($hodnoty->polozka)){
            $form->addError('Formular musi byt vyplnen!');
        }
    }
    
    public function editProjectFormSucceded($form)
    {
        $hodnoty = $form->getValues();
        if ($hodnoty->editTym){
            $this->projekty->zmenitTym($hodnoty->id_projekt, $hodnoty->polozka);
        } else {
            $this->projekty->zmenitZakaznika($hodnoty->id_projekt, $hodnoty->polozka); 
        }
        $this->redirect('Project:projects');
    }

    protected function createComponentProjectForm()
    {
        $form = new Nette\Application\UI\Form;

        ///TODO zmenit - nacitat z modelu z Osoby (jen zakazniky) a Tymy (jen aktivni)...
        $tymy = $this->database->table('Tymy');
        $zakaznici = $this->database->table('Osoby');
        
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
        $form->addSubmit('ok', 'Pridat');
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
        try {
            $this->database->beginTransaction();
            
            $this->dokumenty->vytvorDokument();
            $id_dok = $this->dokumenty->vratPosledniIdDokumnetu();
            $this->projekty->vytvoritProjekt($id_dok, $hodnoty->zakaznik, $hodnoty->tym);
            
            $this->database->commit();
        } catch (Exception $ex){
            $this->database->rollBack();
            $form->addError('Chyba pri praci s databazi, nepridalo se nic!');
        }
        $this->redirect('Project:projects');
    }

    public function actionEditProject($id_projekt, $editTym, $id_polozka)
    {
        if ($editTym){
            $this->editTym = true;            
        }
        $this['editProjectForm']->setDefaults(array(
            'polozka' => $id_polozka,
            'id_projekt' => $id_projekt,
            'editTym' => $editTym,
        ));
    }

    public function renderProjects(){
        //$this->projekty->ulozLoginAheslo('Lukás Junek', 'Junek', 'Lukas');
        //$this->projekty->ulozLoginAheslo('Kuba Kozák', 'Kozak', 'Jakub');
        //$this->projekty->ulozLoginAheslo('Michal Šturma', 'Sturma', 'Michal');
        $this->template->proj = $this->projekty->seznamProjektu();
        $this->template->heslo = $this->projekty->vratHeslo('Lukas');
    }
    
    public function renderAddProject()
    {
        
    }
    
    public function renderEditProject()
    {
        
    }
}
