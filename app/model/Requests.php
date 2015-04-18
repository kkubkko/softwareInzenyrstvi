<?php

/* 
 * IMPLEMENTOVANO:
 * Pridani upresnujicich informaci
 * Pridani sluzby
 * Editace sluzby
 * seznam sluzeb
 * vraceni konkretni sluzby
 * pridani poptavky
 * propojeni poptavky a sluzeb
 * seznam neodmitnutych poptavek
 * seznam odmitnutych poptavek
 * seznam uzivatelovych poptavek
 * seznam uzivatelovych aktivnich poptavek
 * vraceni konktetni poptaky
 * vraceni konktertniho propojeni poptavky a sluzby
 * odmitnuti poptavky 
 * prirazeni poptavky do pozadavku
 * vraceni konkretniho pozadavku
 * vraceni sluzeb pozadavku
 */

namespace App\Model;
use Nette,
    Nette\Utils\Strings;

class Requests extends Nette\Object{
    /** @var Nette\Database\Context */
    private $database;


    public function __construct(Nette\Database\Context $database)
    {
        $this->database = $database;
    }
	
    public function addSpecial($data) 
    {		
        return $this->database->table('Specialni')->insert($data);		
    }
	
    public function  addService($data) 
    {		
        return $this->database->table('Sluzby')->insert($data);		
    }
    
    public function editService($data, $id_service)
    {
        return $this->database->table('Sluzby')->where('ID = ?', $id_service)->update(array($data));
    }
    
    public function listOfServices()
    {
        $pom = $this->database->table('Sluzby');
        return $pom;
    }
    
    public function detailOfService($id_service)
    {
        return $this->database->table('Sluzby')->where('ID = ?', $id_service)->fetch();
    }

    public function addDemand($special_id, $user_id) 
    {		
        $data = array ( 'specialni_id' => $special_id,
                'osoba_id' => $user_id,
                'datum' => date('Y-m-d'),	
                'stav' => 'čeká na posouzení');
        return $this->database->table('Poptavka')->insert($data);
	}
    
    public function addDemandService($id_service, $id_demand)
    {
        return $this->database->table('Sluzba_poptavka')->insert(array(
            'sluzba_id' => $id_service,
            'poptavka_id' => $id_demand,
            'datum' => date('Y-m-d'),
        ));
    }
	
    public function listOfActiveDemands() 
    {		
        return $this->database->table('Poptavka')->where('stav <> ?', 'odmitnuto');		
    }
    
    public function listOdDeactiveDemands()
    {
        return $this->database->table('Poptavka')->where('stav = ?', 'odmitnuto');
    }
    
    public function listOfUsersDemands($id_user)
    {
        return $this->database->table('Poptavka')->where('osoba_id = ?', $id_user);
    }
    
    public function listOfUsersActiveDemands($id_user)
    {
        return $this->database->table('Poptavka')->where('osoba_id = ? AND stav <> ?', $id_user, 'odmitnuto');
    }
	
    public function vratDemand($request_id) {
        return $this->database->table('Poptavka')->where('ID = ?', $request_id)->fetch();
    }
    
    public function vratDemandServices($id_demand)
    {
        $pom = $this->database->table('Sluzba_poptavka')->where('poptavka_id = ?', $id_demand);
        return $pom;
    }
    
    public function rejectDemand($id_demand)
    {
        $this->database->table('Poptavka')->where('ID = ?', $id_demand)->update(array(
            'stav' => 'odmitnuto',
            'datum' => date('Y-m-d'),
        ));
    }
    
    public function priradPoptavkuDoPozadavku($id_poptavky, $id_verze)
    {
        $poptavka = $this->database->table('Poptavka')->get($id_poptavky);
        $services = $this->vratDemandServices($poptavka->ID);
        
        $pom = $this->database->table('Pozadavky')->insert(
            array(
                'specialni_id' => $poptavka->specialni_id,
                'verze_id'     => $id_verze,
            ));
        foreach ($services as $service) {
            $this->database->table('Sluzba_pozadaky')->insert(array(
                'sluzba_id' => $service->ID,
                'pozadavky_id' => $pom->ID,
            ));
        }
        $this->database->table('Upravy')->insert(array(
            'verze_id' => $id_verze,
            'text' => 'Prirazeni poptavky k pozadavkum',
        ));
        return $pom;
    }
    
    public function vratPozadavkyProVerzi($id_verze)
    {
        $pom = $this->database->table('Pozadavky')->where('verze_id = ?', $id_verze)->fetch();
        return $pom;
    }
    
    public function vratSeznamSluzebProPozadavky($id_pozadavky)
    {
        $pom = $this->database->table('Sluzba_pozadavky')->where('pozadavky_id = ?', $id_pozadavky);
        return $pom;
    }
	
}