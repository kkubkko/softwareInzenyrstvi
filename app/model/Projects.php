<?php
/**
 * IMPLEMENTOVANO:
 * vraceni seznamu projektu
 * vytvoreni noveho projektu
 * editace projetktu
 * vraceni popisu projektu
 * ruseni projektu
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
    /** @var Documents */
    private $dokumenty;
//------------------------------------------------------------------------------

	public function __construct(Nette\Database\Context $database, Documents $documents)
	{
		$this->database = $database;
        $this->dokumenty = $documents;
	}
//------------------------------------------------------------------------------
    
    public function seznamProjektu()
    {
        $pom = $this->database->table('Projekty');
        return $pom;
    }
//------------------------------------------------------------------------------
    
    public function vytvoritProjekt($id_zakaznika, $id_tymu, $popis)
    {
        $id_dokumentu = $this->dokumenty->vytvorDokument();
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
    
    public function zmenitProjekt($id_projekt, $id_novy_tym, $id_novy_zakaznik, $novy_popis)
    {
        $this->database->table('Projekty')->where('ID = ?', $id_projekt)->update(
                array(
                    'tym_id' => $id_novy_tym,
                    'zakaznik_id' => $id_novy_zakaznik,
                    'popis' => $novy_popis,
                ));        
    }
//------------------------------------------------------------------------------
    
    public function vratPopisProjektu($id_projekt)
    {
        $pom = $this->database->table('Projekty')->where('ID = ?', $id_projekt)->fetch();
        return $pom->popis;
    }
 //------------------------------------------------------------------------------
    
    public function zrusitProjekt($id_projekt)
    {
        $pom = $this->database->table('Projekty')->where('ID = ?', $id_projekt)->fetch();
        $pom2 = $pom->dokument_id;
        $this->database->table('Projekty')->where('ID = ?', $id_projekt)->delete();
        $this->dokumenty->zrusitDokument($pom2);        
    }
//------------------------------------------------------------------------------  
    
    public function seznamNeukoncenychProjektu($id_tym)
    {
        return $this->database->table('Projekty')
                ->where('etapa <> ? AND tym_id = ?', 'ukončen', $id_tym);        
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
