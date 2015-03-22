<?php

namespace App\Model;
use Nette;
/**
 * IMPLEMENTOVANO:
 * seznam tymu
 * seznam clenu tymu
 * 
 * 
 */
class Teams extends Nette\Object {
    /** @var Nette\Database\Context */
    private $database;
//------------------------------------------------------------------------------
    
	public function __construct(Nette\Database\Context $database)
	{
		$this->database = $database;
	}
//------------------------------------------------------------------------------
    
    public function seznamVsechTymu()
    {
        $pom = $this->database->table('Tymy');
        return $pom;
    }
//------------------------------------------------------------------------------
    
    public function seznamClenuTymu($id_tymu)
    {
        $pom = $this->database->table('Tym_osoby')->where('tym_id = ?', $id_tymu);
        return $pom;
    }
//------------------------------------------------------------------------------
    
}
