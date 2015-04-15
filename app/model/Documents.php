<?php

/*
 * IMPLEMENTOVANO:
 * vytvoreni dokumentu
 * zruseni dokumentu - TODO ruseni verzi
 * vraceni nejvyssiho id dokumentu
 * vytvoreni pocatecni verze po vytvoreni dokumentu
 * pridani pozadavku do noveho dokumentu
 * aktualni verze
 * vraceni dokumentu
 * nova verze
 */


namespace App\Model;
use Nette;
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
class Documents extends Nette\Object {
    /** @var Nette\Database\Context */
	private $database;
//------------------------------------------------------------------------------
    
	public function __construct(Nette\Database\Context $database)
	{
		$this->database = $database;
	}
//------------------------------------------------------------------------------  
    
    public function vytvorDokument()
    {
        $pom = $this->database->table('Dokumenty')->insert(
                array(
                    'aktualni_verze'   => 0,
                    'datum_zalozeni'   => date('Y-m-d'),
                    'posledni_editace' => date('Y-m-d'),
                ));
        return $pom->ID;
    }
 //------------------------------------------------------------------------------
    
    public function zrusitDokument($id_dokument)
    {
        // TODO zde se budou dale rusit verze...
        $this->database->table('Dokumenty')->where('ID = ?', $id_dokument)->delete();
    }
//------------------------------------------------------------------------------
    
    public function vratMaxIdDokumnetu()
    {
        return $this->database->table('Dokumenty')->max('ID');
    }
//------------------------------------------------------------------------------
    
    public function novaVerzeDokumentu($id_dokument)
    {
        $pom = $this->vratDokument($id_dokument);
        $this->database->table('Dokumenty')->where('ID = ?', $id_dokument)->update(array(
            'aktualni_verze' => $pom->aktualni_verze + 1,
            'posledni_editace' => date('Y-m-d'),
        ));
        
    }
//------------------------------------------------------------------------------
    
    public function vratDokument($id_dokument)
    {
        $pom = $this->database->table('Dokumenty')->where('ID = ?', $id_dokument)->fetch();
        return $pom;        
    }
//------------------------------------------------------------------------------
    
    public function aktualniVerze($id_dokument)
    {
        $pom = $this->database->table('Dokumenty')->where('ID = ?', $id_dokument)->fetch();
        return $pom->aktualni_verze;
    }
//------------------------------------------------------------------------------
}

