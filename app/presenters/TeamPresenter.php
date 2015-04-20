<?php



namespace App\Presenters;

use Nette,
	App\Model;
/**
 * Description of teamPresenter
 *
 * @author Luky
 */
class TeamPresenter extends BasePresenter {
    
    /** @var \App\Model\Teams @inject */
    public $tymy;
    /** @var \App\Model\Users @inject */
    public $uzivatele;
    /** @var Nette\Database\Context */
    private $database;
    private $pozice;
    private $tym;
    
    
    protected function startup()
    {
        parent::startup();         
    }
    
    public function __construct(Nette\Database\Context $database)
	{
		$this->database = $database;
	}
    
    protected function createComponentMemberForm()
    {
        $form = new Nette\Application\UI\Form;
        
        $form->addHidden('tym', $this->tym);
        if (isset($this->pozice)) {
            $form->addHidden('role', $this->pozice);
            $users = $this->uzivatele->listOfUsersWithRole($this->pozice);
        } else {
            $roles = $this->uzivatele->roles();
            foreach ($roles as $role){
                $sel_role[$role->ID] = $role->nazev;
            }
            $form->addSelect('role', 'Role v tymu', $sel_role)
                    ->setPrompt('Volba role v tymu')
                    ->setRequired('Musite vyplnit roli v tymu!');
            $users = $this->uzivatele->listOfEmployes();
        }        
        
        foreach ($users as $user){
            $sel_user[$user->ID] = $user->jmeno;
        }
        $form->addSelect('osoba', 'Zamestnanec', $sel_user)
                ->setPrompt('Volba zamestnance')
                ->setRequired('Musite vybrat zamestnance');        
        $form->addSubmit('ok', 'Pridat');
        $form->onSuccess[] = array($this, 'memberFormSucceded');
        
        return $form;        
    }
    
    public function memberFormSucceded($form){
        $hodnoty = $form->values;
        $this->tymy->pridejClenaDoTymu($hodnoty->osoba, $hodnoty->tym, $hodnoty->role);
        $this->flashMessage('Clen tymu byl pridan!');
        $this->redirect('Team:detail',$hodnoty->tym);
    }

    protected function createComponentAddTeamForm()
    {
        $form = new Nette\Application\UI\Form;
        $architekti = $this->uzivatele->listOfUsersWithRole('architekt');
        foreach ($architekti as $arch) {
            $sel_arch[$arch->ID] = $arch->jmeno;            
        }
        
        $projektanti = $this->uzivatele->listOfUsersWithRole('projektant');
        foreach ($projektanti as $proj){
            $sel_proj[$proj->ID] = $proj->jmeno;
        }
        
        $stavbyvedouci = $this->uzivatele->listOfUsersWithRole('stavbyvedoucí');
        foreach ($stavbyvedouci as $stav) {
            $sel_stav[$stav->ID] = $stav->jmeno;
        }
        
        $manazeri = $this->uzivatele->listOfUsersWithRole('manažer');
        foreach ($manazeri as $man) {
            $sel_man[$man->ID] = $man->jmeno;
        }
        
        $form->addTextArea('popis', 'Popis tymu')
                ->setRequired('Zadejte popis prosim!');
        $form->addCheckboxList('man', 'Manazeri', $sel_man)
                ->setRequired('Musite vybrat aspon jednoho manazera');
        $form->addCheckboxList('arch', 'Architekti', $sel_arch)
                ->setRequired('Musite vybrat aspon jednoho architekta');
        $form->addCheckboxList('proj', 'Projektanti', $sel_proj)
                ->setRequired('Musite vybrat aspon jednoho projektanta');
        $form->addCheckboxList('stav', 'Stavbyvedouci', $sel_stav)
                ->setRequired('Musite vybrat aspon jednoho stavbyvedouciho');
        $form->addSubmit('ok', 'Pridat');
        
        $form->onSuccess[] = array($this, 'addTeamFromSucceded');        
        return $form;        
    }
    
    public function addTeamFromSucceded($form)
    {
        $hodnoty = $form->values;
        try {
            $this->database->beginTransaction();
            $tym = $this->tymy->zalozTym($hodnoty->popis);
            
            foreach ($hodnoty->man as $man) {
                $this->tymy->pridejClenaDoTymu($man, $tym, 'manažer');                
            }
            
            foreach ($hodnoty->arch as $arch) {
                $this->tymy->pridejClenaDoTymu($arch, $tym, 'architekt');                
            }
            
            foreach ($hodnoty->stav as $stav) {
                $this->tymy->pridejClenaDoTymu($stav, $tym, 'stavbyvedoucí');                
            }
            
            foreach ($hodnoty->proj as $proj) {
                $this->tymy->pridejClenaDoTymu($proj, $tym, 'projektant');                
            }           
            
            $this->database->commit();
        } catch (Exception $ex) {
            $this->database->rollBack();
            $form->addError('Chyba pri praci s databazi, nepridalo se nic!');
        }
        $this->flashMessage('Tym uspesne pridan!');
        $this->redirect('Team:teams');        
    }


    public function handleDelUser($id_tym, $id_zamestnanec)
    {
        if (!$this->user->isInRole('admin') && !$this->user->isInRole('manažer')) {
            $this->setView('notAllowed');
        } else {
            $this->tymy->odeberClenaTymu($id_zamestnanec, $id_tym);
            $this->flashMessage('Clen tymu byl odebran!');
        }
    }
    
    public function handleAddUser($id_tym, $id_zamestnanec)
    {
        if (!$this->user->isInRole('admin') && !$this->user->isInRole('manažer')) {
            $this->setView('notAllowed');
        } else {
            $this->tymy->pridejClenaDoTymu($id_zamestnanec, $id_tym);
            $this->flashMessage('Clen tymu byl opetovne pridan!');
        }
    }
    
    public function handleDelTeam($id_tym)
    {
        if (!$this->user->isInRole('admin') && !$this->user->isInRole('manažer')) {
            $this->setView('notAllowed');
        } else {
            if ($this->tymy->vsechnyProjUkonceny($id_tym)){
                $this->tymy->ukonciCinnostTymu($id_tym);
                $this->flashMessage('Cinnost tymu byla ukoncena.');
            } else {
                $this->flashMessage('Nejsou dokonceny vsechny projekty!', 'error');
            }
        }
    }    

    public function actionAddMember($id_tym, $pozice)
    {
        if (!$this->user->isInRole('admin') && !$this->user->isInRole('manažer')) {
            $this->setView('notAllowed');
        } 
        $tym = $this->tymy->vratTym($id_tym);
        if (!isset($tym)){
            $this->setView('notFound');
        } 
        if (isset($pozice)){
            $this->pozice = $pozice;
        }
        $this->tym = $id_tym;
    }
    
    public function actionAddTeam()
    {
        if (!$this->user->isInRole('admin') && !$this->user->isInRole('manažer')) {
            $this->setView('notAllowed');
        } 
    }
    
    public function actionTeams()
    {
        if (!$this->user->isInRole('admin') && !$this->user->isInRole('manažer')) {
            $this->setView('notAllowed');
        }        
    }
    
    public function actionDetail($id_tym)
    {
        if (!$this->user->isInRole('admin') && !$this->user->isInRole('manažer')) {
            $this->setView('notAllowed');
        }
        $tym = $this->tymy->vratTym($id_tym);
        if (!isset($tym)){
            $this->setView('notFound');
        } 
    }
    
    public function renderAddMember($id_tym)
    {
        $tym = $this->tymy->vratTym($id_tym);
        $this->template->popisTymu = $tym->popis;
        if (isset($this->pozice)){
            $this->template->nadpis = "Pridani clena na pozici $this->pozice";
        } else {
            $this->template->nadpis = "Pridani clena";
        }        
    }
    
    public function renderAddTeam()
    {
        
    }
    
    public function renderTeams()
    {
        $tymy = $this->tymy->seznamTymuPodleAktivity(false);
        $ukoncene = $this->tymy->seznamTymuPodleAktivity(true);
        $this->template->tymy = $tymy;
        $this->template->ukoncene = $ukoncene;
    }
    
    public function renderDetail($id_tym)
    {
        $this->template->tym = $this->tymy->vratTym($id_tym);
        $clenove = $this->tymy->seznamAktivnichClenuTymu($id_tym);
        $this->template->akt_clenove = $clenove;
        
        $byvaly = $this->tymy->seznamNeaktivnichClenuTymu($id_tym);
        $this->template->byv_clenove = $byvaly;
        $this->template->sez_projektu = $this->tymy->seznamProjektuTymu($id_tym);
    }
}
