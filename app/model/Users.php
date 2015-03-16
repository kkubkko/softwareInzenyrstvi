<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Model;
use Nette,
    Nette\Utils\Strings,
	Nette\Security\Passwords;

class Users extends Nette\Object{
	/** @var Nette\Database\Context */
	private $database;


	public function __construct(Nette\Database\Context $database)
	{
		$this->database = $database;
	}
	
	public function addUser($data){
		
		$data['heslo'] = Passwords::hash($data['heslo']);
		
		$this->database->table('Osoby')->insert($data);
	}
	
	 public function listOfUsers()
    {
        $pom = $this->database->table('Osoby');
        return $pom;
    }
	
	public function deleteUser($id) {
		$this->database->table('Osoby')->where('ID = ?', $id)->delete();
	}
}