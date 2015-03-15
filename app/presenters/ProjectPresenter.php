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
        //$this->projekty->ulozLoginAheslo('Lukás Junek', 'Junek', 'Lukas');
        //$this->projekty->ulozLoginAheslo('Kuba Kozák', 'Kozak', 'Jakub');
        //$this->projekty->ulozLoginAheslo('Michal Šturma', 'Sturma', 'Michal');
        $this->template->proj = $this->projekty->seznamProjektu();
        $this->template->heslo = $this->projekty->vratHeslo('Lukas');
    }
}
