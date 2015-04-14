<?php


namespace App\Presenters;

use Nette,
    App\Model,    
	App\Model\Documents,
    App\Model\Versions;

/**
 * Description of VersionPresenter
 *
 * @author Luky
 */
class VersionPresenter extends BasePresenter 
{
    /** @var \App\Model\Documents @inject */
    private $dokumenty;
    /** @var \App\Model\Versions @inject */
    private $verze;
    
    private $id_dok;
    private $ver;
    
    protected function createComponentVersionForm()
    {
        $form = new Nette\Application\UI\Form;
        
        $akt = $this->verze->aktualniVerze($this->id_dok);
        for($i = 1; $i <= $akt; $i++){
            $pom_pole[$i] = $i;
        }
        $form->addSelect('version', 'Zobrazit verzi', $pom_pole);
        $form->addHidden('doc', $this->id_dok);
        $form->addSubmit('ok');
        $form->onSuccess[] = array($this, 'versionFormSucceded');       
        return $form;       
    }
    
    public function versionFormSucceded($form)
    {
        $hodnoty = $form->getValues();
        $this->redirect('Version:version', $hodnoty->doc, $hodnoty->version);        
    }
    
    public function actionVersion($id_dokument, $verze = NULL)
    {
        //if (!isset($this->dokumenty)){
            //$this->setView('notAllowed');
        //} else {
        $pom = $this->dokumenty->vratDokument($id_dokument);
            if (!isset($pom)) {
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
            }
        //}
    }
    
    public function renderVersion($id_dokument)
    {
        $this->template->pripominky = $this->verze->seznamPripominekVerzeDoc($id_dokument, $this->ver);
        $this->template->upravy = $this->verze->seznamUpravVerzeDoc($id_dokument, $this->ver);        
    }

}
