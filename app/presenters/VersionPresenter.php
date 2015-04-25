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
    /** @var \App\Model\Projects @inject */
    public $projekty;
	/** @var \App\Model\Users @inject */
    public $users;
    /** @var Nette\Database\Context */
    private $database;

    private $id_dok;
    private $ver;
    private $aktualni;
    //private $poz;
    
    
    protected function startup()
    {
        parent::startup();         
    }
    
    public function __construct(Nette\Database\Context $database)
    {
            $this->database = $database;
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
    
    protected function createComponentEditRequestForm()
    {
        $form = new Nette\Application\UI\Form;
        
        $demand = $this->pozadavky->listOfServices();
        
        foreach ($demand as $dem){
            $sel_dem[$dem->ID] = $dem->nazev;
        }
        
        $form->addHidden('id_doc', 0);
        
        $form->addCheckboxList('services', 'Vyber sluzeb', $sel_dem)
                ;//->setRequired('Musite vybrat aspon jednu sluzbu!');
        
        $form->addText('spec','Upresneni poptavky')
                ->addRule(Form::MAX_LENGTH, 'Text je příliš dlouhy', 255)
                ->setRequired('Uvedte prosim upresnujici popis!');
        $form->addText('uprava', 'Popis uprav')
                ->addRule(Form::MAX_LENGTH, 'Text je příliš dlouhy', 255)
                ->setRequired('Uvedte prosim popis uprav!');
        $form->addSubmit('send', 'Ulozit novou verzi');
        
        $form->onSuccess[] = array($this, 'editRequestFormSucceded');
        return $form;        
    }
    
    public function editRequestFormSucceded($form)
    {
        $hodnoty = $form->values;
        
        try {
            $this->database->beginTransaction();
            
            $special = array('text' => $hodnoty->spec);
            $db_spec = $this->pozadavky->addSpecial($special);          
            $db_ver = $this->verze->vytvoritDalsiVerzi($hodnoty->id_doc, $this->user->getId());
            $this->verze->pridejUpravu($hodnoty->uprava, $db_ver->ID);
            $db_doc = $this->dokumenty->novaVerzeDokumentu($hodnoty->id_doc);
            $db_poz = $this->pozadavky->addRequest($db_ver->ID, $db_spec->ID);
            foreach ($hodnoty->services as $service){
                $this->pozadavky->addRequestService($db_poz->ID, $service);
            }                   
            $this->database->commit();
        } catch (Nette\Neon\Exception $ex) {
            $this->database->rollBack();
            $form->addError($ex->getMessage());
        } 
        $this->redirect('Version:version', $hodnoty->id_doc);
    }
    
    public function handleFinalize($id_verze)
    {
        if ($this->user->isInRole('zákazník') || $this->user->isInRole('manažer') || $this->user->isInRole('admin')){
            $verze = $this->verze->vratVerziProID($id_verze);
            if ($verze){
                try {
                    $this->database->beginTransaction();
                    if ($this->user->isInRole('zákazník')){
                        $stav = $this->dokumenty->finalizeZakaznik($verze->dokument_id);
                    } else {
                        $stav = $this->dokumenty->finalizeZamestnanec($verze->dokument_id);
                    }
                    if ($stav){
                        $proj = $this->dokumenty->vratProjektDokumentu($verze->dokument_id);
                        $this->projekty->novaEtapaProjektu($proj->ID);
                    }
                    $this->database->commit();
                } catch (Exception $ex) {
                    $this->database->rollBack();
                }                
            } else {
                $this->setView('notFound');
            }
        } else {
            $this->setView('notAllowed');
        }
    }


    public function actionVersion($id_dokument, $verze = NULL)
    {
        $pom = $this->dokumenty->vratDokument($id_dokument);
        if (!$pom) {
            $this->setView('notFound');
        } else {
			
			if ($this->user->isInRole('zákazník')) {
				$maPravo = FALSE;
				$projekty = $this->users->vratProjektyZakaznika($this->user->getIdentity()->getId());
				foreach ($projekty as $projekt) {
					if ($projekt->dokument_id == $id_dokument) {
						$maPravo = TRUE;
					}
				}
				if ($maPravo == FALSE) {
					$this->setView('notAllowed');
				}
			}
			
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
    
    public function actionEditRequest($id_verze)
    {
        if ($this->user->isInRole('zákazník') || $this->user->isInRole('manažer') || $this->user->isInRole('admin')){
            $verze = $this->verze->vratVerziProID($id_verze);
            if ($verze){
                $poz = $this->pozadavky->vratPozadavkyProVerzi($id_verze);
                if ($poz) {
                    $popis = $poz->specialni->text;
                    $sluzby = $this->pozadavky->vratSeznamSluzebProPozadavky($poz->ID);
                    foreach ($sluzby as $sluzba) {
                        $sel_sluzba[] = $sluzba->sluzba_id;
                    }
                    if (isset($sel_sluzba)) {
                        $data = array(
                            'id_doc' => $verze->dokument_id,
                            'spec' => $popis,
                            'services' => $sel_sluzba,
                        );
                    } else {
                        $data = array(
                            'id_doc' => $verze->dokument_id,
                            'spec' => $popis,
                        );
                    }
                    $this['editRequestForm']->setDefaults($data);
                } else {
                    $this->setView('notFound');
                }
            } else {
                $this->setView('notFound');
            }
        } else {
            $this->setView('notAllowed');
        }
    }
    
    public function renderVersion($id_dokument)
    {
        $proj = $this->dokumenty->vratProjektDokumentu($id_dokument);
        if ($this->ver == $this->aktualni && !$this->dokumenty->kompletniFinalizace($id_dokument, 'tvorba požadavků')){
            //$this->template->aktualni = true;
            //$this->flashMessage('a');
            if ($this->user->isInRole('zákazník')){
                //$this->flashMessage('b');
                if ($this->dokumenty->jeFinalizaceOsoba($id_dokument, $proj->etapa, 'zakaznik')){
                    $this->template->aktualni = false;
                } else {
                    $this->template->aktualni = true;
                }
            } else {
                if ($this->dokumenty->jeFinalizaceOsoba($id_dokument, $proj->etapa, 'manazer')){
                    $this->template->aktualni = false;
                } else {
                    $this->template->aktualni = true;
                }
            }
        } else {
            $this->template->aktualni = false; 
        }
        $this->template->projekt = $proj;
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
