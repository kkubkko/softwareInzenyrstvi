<?php

/*
 * IMPLEMENTOVANO:
 * vytvoreni dokumentu
 * vraceni nejvyssiho id dokumentu
 * vytvoreni pocatecni verze po vytvoreni dokumentu
 * pridani pozadavku do noveho dokumentu
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
        $this->database->table('Dokumenty')->insert(
                array(
                    'aktualni_verze'   => 0,
                    'datum_zalozeni'   => time(),
                    'posledni_editace' => time(),
                ));        
    }
//------------------------------------------------------------------------------
    
    public function vratPosledniIdDokumnetu()
    {
        return $this->database->table('Dokumenty')->max('ID');
    }
//------------------------------------------------------------------------------
    
    public function vytvorNovouVerzi($id_dokumentu)
    {
        $this->database->table('Verze')->insert(
                array(
                    'dokument_id'     => $id_dokumentu, 
                    'akt_etapa'       => 'zacatek',
                    'verze'           => 0,
                    'datum_vytvoreni' => time(),
                ));                
    }
//------------------------------------------------------------------------------
    
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
//------------------------------------------------------------------------------
    
}

