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
		
		$insertedUser = $this->database->table('Osoby')->insert($data);
		
		return $insertedUser["ID"];
	}
	
	public function addContacts($data){
		
		$this->database->table('Kontakt')->insert($data);
		
	}
	
	public function addRole($data){
		
		$this->database->table('prirazeniRole')->insert($data);
		
	}

    public function  roles()
    {
        $role = $this->database->table('Role');
        return $role;		
	}
	
	public function rolesForEmployees ()
	{
		$role = $this->database->table('Role')->where('ID != ? AND ID != ?', '1', '2');
        return $role;
	}
	
	public function rolesForCustomer ()
	{
		$role = $this->database->table('Role')->where('ID = ?', '1');
        return $role;
	}

	public function listOfUsers()
    {
        $pom = $this->database->table('Osoby');
        return $pom;
    }
    
    public function listOfCustomers()
    {
        return $this->listOfUsersWithRole('zákazník');
    }
    
    public function listOfEmployes()
    {
        return $this->listOfUsersWithRole('zaměstnanec');
    }
    
    public function  listOfUsersWithRole($role)
    {
        $pom = $this->database->query(
                "SELECT a.ID,a.personal_cislo,a.jmeno,a.login FROM Osoby AS a "
                ."JOIN prirazeniRole as b On a.ID = b.osoby_id JOIN Role as c "
                ."ON b.role_id = c.ID WHERE c.nazev = '$role' AND a.inactive != 1"
            );
        return $pom;
    }
    
    public function listOfUserRoles($id_user)
    {
        $pom = $this->database->table('prirazeniRole')->where('osoby_id = ? AND role_id <> ?', $id_user, 2)
                ->order('role_id');
        return $pom;
    }

    public function deleteUser($id) {
		
		$this->database->query('UPDATE Osoby SET inactive=1 WHERE ID=?', $id);
		
	}
	
	public function vratRoluPreUsera ($id_user) {
		
		$roles = $this->database->table('prirazeniRole')->where('osoby_id = ?', $id_user);
		return $roles;
		
	}

		public function vratUser($user_id) {
		
        $user = $this->database->table('Osoby')->where('ID = ?', $user_id);

        if ($user->count() > 0) {
            return $user->fetch();
        } else {
            return NULL;
        }
		
	}
	
	public function vratKontakty($user_id) {
		
		$kontakty = $this->database->table('Kontakt')->where('osoba_id = ?',$user_id);
		
		if ($kontakty->count() > 0) {
            return $kontakty->fetch();
        } else {
            return NULL;
        }
	}
	
	public function vratProjektyZakaznika($user_id) {
		$projekty = $this->database->table('Projekty')->where('zakaznik_id = ?', $user_id);
		
        return $projekty;

	}
	
	public function vratTymy($user_id) {
		
		$tymy = $this->database->table('Tym_osoby')->where('osoba_id = ?', $user_id );
		
		return $tymy;
		
	}
}