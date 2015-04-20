<?php


namespace App\Presenters;

use Nette,
    Nette\Application\UI\Form,
    App\Model;

/**
 * Description of VersionPresenter
 *
 * @author Luky
 */
class VersionPresenter extends BasePresenter 
{
    /** @var \App\Model\Documents @inject */
    public $dokumenty;
    /** @var \App\Model\Versions @inject */
    public $verze;
    /** @var \App\Model\Requests @inject */
    public $pozadavky;
    
    private $id_dok;
    private $ver;
    private $aktualni;
    //private $poz;
    
    
    protected function startup()
    {
        parent::startup();         
    }
    
    protected function createComponentVersionForm()
    {
        $form = new Nette\Application\UI\Form;
        
        $akt = $this->verze->aktualniVerze($this->id_dok);
        for($i = 1; $i <= $akt; $i++){
            $pom_pole[$i] = $i;
        }
        $form->addSelect('version', 'Zobrazit verzi', $pom_pole);
        $form->addHidden('doc', $this->id_dok);
        $form->addSubmit('ok', 'Nacist verzi');
        $form->onSuccess[] = array($this, 'versionFormSucceded');       
        return $form;       
    }
    
    public function versionFormSucceded($form)
    {
        $hodnoty = $form->getValues();
        $this->redirect('Version:version', $hodnoty->doc, $hodnoty->version);        
    }
    
    protected function createComponentPromptForm()
    {
        $form = new Nette\Application\UI\Form;
        
        $form->addHidden('id_verze', 0);
        $form->addText('text', 'Text pripominky')
                ->addRule(Form::MAX_LENGTH, 'Pripominka je příliš dlouhá', 255)
                ->setRequired('Prosim uvedte text!');
        $form->addSubmit('ok', 'Ulozit');
        $form->onSuccess[] = array($this, 'promptFormSucceded');
        return $form;
    }
    
    public function promptFormSucceded($form)
    {
        $hodnoty = $form->values;
        $this->verze->pridejPripominku($hodnoty->text, $hodnoty->id_verze);
        $ver = $this->verze->vratVerziProID($hodnoty->id_verze);
        $this->redirect('Version:version', $ver->dokument_id);
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
    
    public function actionVersion($id_dokument, $verze = NULL)
    {
        $pom = $this->dokumenty->vratDokument($id_dokument);
        if (!$pom) {
            $this->setView('notFound');
        } else {
            $this->id_dok = $id_dokument;
            if (isset($verze) && $verze <= $pom->aktualni_verze && $verze > 0){
                $this['versionForm']->setDefaults(array(
                    'version' => $verze,
                )); 
                $this->ver = $verze;
            } else {
                $this['versionForm']->setDefaults(array(
                    'version' => $pom->aktualni_verze,
                ));
                $this->ver = $pom->aktualni_verze;
            }
            $this->aktualni = $pom->aktualni_verze;
        }
    }
    
    public function actionAddPrompt($id_verze)
    {
        if ($this->user->isLoggedIn()) {
            $ver = $this->verze->vratVerziProID($id_verze);
            if ($ver){
                $this['promptForm']->setDefaults(array(
                    'id_verze' => $id_verze,
                ));
            } else {
                $this->setView('notFound');
            }
        } else {
            $this->setView('notAllowed');
        }
    }
    
    public function renderVersion($id_dokument)
    {
        if ($this->ver == $this->aktualni){
            $this->template->aktualni = true;
        } else {
            $this->template->aktualni = false; 
        }
        $this->template->projekt = $this->dokumenty->vratProjektDokumentu($id_dokument);
        $this->template->pripominky = $this->verze->seznamPripominekVerzeDoc($id_dokument, $this->ver);
        $this->template->upravy = $this->verze->seznamUpravVerzeDoc($id_dokument, $this->ver);
        $prac_verze = $this->verze->nactiVerzi($id_dokument, $this->ver);
        $this->template->verze = $prac_verze;
        $jina_prom = $this->pozadavky->vratPozadavkyProVerzi($prac_verze->ID);
        //$this->poz = $jina_prom;
        if ($jina_prom) {
            $this->template->pozadavky = $jina_prom;
            $this->template->sluzby = $this->pozadavky->vratSeznamSluzebProPozadavky($jina_prom->ID);
        } else {
            $this->template->pozadavky = null;
            $this->template->sluzby = $this->pozadavky->vratSeznamSluzebProPozadavky(-1);;
        }
    }

}
