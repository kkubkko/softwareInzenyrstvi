<?php

namespace App\Presenters;

use Nette,
	App\Model;


/**
 * Homepage presenter.
 */
class HomepagePresenter extends BasePresenter
{
    
        protected function startup()
        {
            parent::startup();         
        }
    
	public function renderDefault()
	{
            
                /*if ($this->getUser()->isInRole(1)){                           //kontrola, zda je uživatel v roli toho čísla
		
		} else{
			$this->flashMessage('Na tuto stránku nemáte přístup.');
			$this->redirect('Admin:default');   //admin:default si přepiš na tu stránku jakou chceš
		}*/
            
		$this->template->anyVariable = 'any value';
                
    
	}

}
