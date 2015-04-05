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
	
	public function addSpecial($data) {
		
		return $this->database->table('Specialni')->insert($data);
		
	}
	
	public function  addService($data) {
		
		return $this->database->table('Sluzby')->insert($data);
		
	}
	
	public function addRequest($special_id, $service_id, $user_id) {
		
		$data = array ( 'sluzby_id' => $service_id,
						'specialni_id' => $special_id,
						'osoba_id' => $user_id);
		
		$this->database->table('Poptavka')->insert($data);
	}
	
	public function listOfRequests() {
		
		return $this->database->table('Poptavka');
		
	}
	
	public function vratRequest($request_id) {
		return $this->database->table('Poptavka')->where('ID = ?', $request_id);
	}
	
}