<?php

namespace App\Presenters;

use Nette,
	App\Model;



/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{
    
    protected $user_role;
    protected $user_name;
    
    protected function startup()
    {
        parent::startup();
        
        if ($this->getUser()->isLoggedIn()) {									//kontrola přihlášení uživatele
            
            $this->user_role = $this->getUser()->getIdentity()->getId();     //TOTO VYPÍŠE POUZE (ASI DO ARRAYE) IDČKA TĚCH ROLÍ CO MÁ
            //$this->user_name = $this->getUser()->identity->data->jmeno;
            
            $this->template->user_role = $this->user_role;
                     
        } else{																	//pokud není uživatel přihlášen -> redirect na login stránku
            //$this->redirect('Sign:default');  
        }
    }
}
