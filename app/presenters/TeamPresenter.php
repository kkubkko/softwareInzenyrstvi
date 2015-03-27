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
        $this->template->akt_clenove = $this->tymy->seznamAktivnichClenuTymu($id_tym);
        $this->template->byv_clenove = $this->tymy->seznamNeaktivnichClenuTymu($id_tym);
        $this->template->sez_projektu = $this->tymy->seznamProjektuTymu($id_tym);
    }
}
