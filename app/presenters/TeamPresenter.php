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
    
    public function renderTeams()
    {
        $tymy = $this->tymy->seznamVsechTymu();
        $this->template->tymy = $tymy;
        foreach ($tymy as $tym){
            $clenove[$tym->ID] = $this->tymy->seznamClenuTymu($tym->ID);
        }
        $this->template->clenove = $clenove;
    }
}
