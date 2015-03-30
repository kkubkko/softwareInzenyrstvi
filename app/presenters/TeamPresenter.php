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
    
    public function renderTeams()
    {
        $tymy = $this->tymy->seznamTymuPodleAktivity(false);
        $this->template->tymy = $tymy;
        foreach ($tymy as $tym){
            $clenove[$tym->ID] = $this->tymy->seznamVsechClenuTymu($tym->ID);
        }
        $this->template->clenove = $clenove;
    }
    
    public function renderDetail($id_tym)
    {
        $this->template->tym = $this->tymy->vratTym($id_tym);
        $clenove = $this->tymy->seznamAktivnichClenuTymu($id_tym);
        $this->template->akt_clenove = $clenove;
        $pom_i = 0; 
        foreach ($clenove as $clen) {
            $role[$pom_i] = $this->uzivatele->listOfUserRoles($clen->osoba_id);
            $pom_i++;
        }
        if (isset($role)) { $this->template->role = $role; }
        
        $byvaly = $this->tymy->seznamNeaktivnichClenuTymu($id_tym);
        $this->template->byv_clenove = $byvaly;
        $pom_i = 0;
        foreach ($byvaly as $byv) {
            $roles[$pom_i] = $this->uzivatele->listOfUserRoles($byv->osoba_id);
            $pom_i++;
        }
        if (isset($roles)) { $this->template->byv_role = $roles; }
        $this->template->sez_projektu = $this->tymy->seznamProjektuTymu($id_tym);
    }
}
