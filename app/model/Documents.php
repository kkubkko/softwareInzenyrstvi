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
    
    public function vratProjektDokumentu($id_doc)
    {
        $pom = $this->database->table('Projekty')->where('dokument_id = ?', $id_doc)->fetch();
        return $pom;
    } 
    
    public function vytvoritInfoFinalizace($id_dokument, $etapa)
    {
        $pom = $this->database->table('Finalizace')->insert(array(
            'dokument_id' => $id_dokument,
            'etapa' => $etapa,
            'zakaznik' => false,
            'manazer' => false,
        ));
        return $pom;
    }
    
    public function vratFinalizaciDokumentu($id_dokument, $etapa)
    {
        return $this->database->table('Finalizace')->where('dokument_id = ? AND etapa = ?', $id_dokument, $etapa)->fetch();
    }
    
    public function kompletniFinalizace($id_dokument, $etapa)
    {
        $pom = $this->vratFinalizaciDokumentu($id_dokument, $etapa);
        if ($pom->zakaznik && $pom->manazer){
            return true;
        } else {
            return false;
        }
    }

    public function finalizeZakaznik($id_dokument)
    {
        //$doc = $this->vratDokument($id_dokument);
        $proj = $this->vratProjektDokumentu($id_dokument);
        //$fin = $this->vratFinalizaciDokumentu($id_dokument, $proj->etapa);
        $this->database->table('Finalizace')->where('dokument_id = ? AND etapa = ?', $id_dokument, $proj->etapa)->update(array(
            'zakaznik' => true,
        ));
        if ($this->kompletniFinalizace($id_dokument, $proj->etapa)){
            return true;
        } else {
            return false;
        }        
    }
    
    public function finalizeZamestnanec($id_dokument)
    {
        //$doc = $this->vratDokument($id_dokument);
        $proj = $this->vratProjektDokumentu($id_dokument);
        //$fin = $this->vratFinalizaciDokumentu($id_dokument, $proj->etapa);
        $this->database->table('Finalizace')->where('dokument_id = ? AND etapa = ?', $id_dokument, $proj->etapa)->update(array(
            'manazer' => true,
        ));
        if ($this->kompletniFinalizace($id_dokument, $proj->etapa)){
            return true;
        } else {
            return false;
        }        
    }
    
    public function zrusFinalizaci($id_dokument)
    {
        $proj = $this->vratProjektDokumentu($id_dokument);
        $this->database->table('Finalizace')->where('dokument_id = ? AND etapa = ?', $id_dokument, $proj->etapa)->update(array(
            'zakaznik' => false,
            'manazer' => false,
        ));
    }
    
    public function jeFinalizaceOsoba($id_dokument, $etapa, $osoba)
    {
        $fin = $this->vratFinalizaciDokumentu($id_dokument, $etapa);
        if ($fin->$osoba) {
            return true;
        } else {
            return false;
        }
    }
}

