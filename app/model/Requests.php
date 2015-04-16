<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
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
	
	public function listOfDemands() 
    {		
		return $this->database->table('Poptavka');		
	}
    
    public function listOfUsersDemands($id_user)
    {
        return $this->database->table('Poptavka')->where('osoba_id = ?', $id_user);
    }
	
	public function vratRequest($request_id) {
		return $this->database->table('Poptavka')->where('ID = ?', $request_id);
	}
    
    public function priradPoptavkuDoPozadavku($id_poptavky, $id_verze)
    {
        $poptavka = $this->database->table('Poptavka')->get($id_poptavky);
        
        $this->database->table('Pozadavky')->insert(
                array(
                    'sluzby_id'    => $poptavka->sluzby_id,
                    'specialni_id' => $poptavka->specialni_id,
                    'verze_id'     => $id_verze,
                ));        
    }
	
}