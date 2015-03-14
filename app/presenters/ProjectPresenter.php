<?php
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
    


    public function renderProjects(){
        $this->template->proj = $this->projekty->seznamProjektu();
    }
}
