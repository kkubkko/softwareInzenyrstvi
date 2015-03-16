<?php
/**
 * IMPLEMENTOVANO:
 * vraceni seznamu projektu
 * vytvoreni noveho projektu
 * editace tymu u projektu
 * editace zakaznika u projektu
 * funkce, ktere edituji osobu - budou smazany!
 */
namespace App\Model;
use Nette,
    Nette\Utils\Strings,
	Nette\Security\Passwords;        
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------        
class Projects extends Nette\Object {
    
    /** @var Nette\Database\Context */
	private $database;
//------------------------------------------------------------------------------

	public function __construct(Nette\Database\Context $database)
	{
		$this->database = $database;
	}
//------------------------------------------------------------------------------
    
    public function seznamProjektu()
    {
        $pom = $this->database->table('Projekty');
        return $pom;
    }
//------------------------------------------------------------------------------
    
    public function vytvoritProjekt($id_dokumentu, $id_zakaznika, $id_tymu, $popis)
    {
        $this->database->table('Projekty')->insert(
                array(
                    'zakaznik_id' =>$id_zakaznika,
                    'tym_id' => $id_tymu,
                    'dokument_id' => $id_dokumentu,
                    'popis' => $popis,
                    'etapa' => 'zahajeni',
                ));
    }
//------------------------------------------------------------------------------
    
    public function zmenitTym($id_projekt, $id_novy_tym)
    {
        $this->database->table('Projekty')->where('ID = ?', $id_projekt)->update(
                array(
                    'tym_id' => $id_novy_tym,
                ));        
    }
//------------------------------------------------------------------------------
    
    public function zmenitZakaznika($id_projekt, $id_novy_zakaznik)
    {
        $this->database->table('Projekty')->where('ID = ?', $id_projekt)->update(
                array(
                    'zakaznik_id' => $id_novy_zakaznik,
                ));
    }
//------------------------------------------------------------------------------
    
    public function vratHeslo($heslo)
    {
        return Passwords::hash($heslo);
    }
//------------------------------------------------------------------------------
    
    public function ulozLoginAheslo($jmeno, $login, $heslo)
    {
        $this->database->table('Osoby')->where('jmeno = ?', $jmeno)->update(
                array(
                    'jmeno' => $jmeno,
                    'heslo' => $this->vratHeslo($heslo),
                ));
    }
}
