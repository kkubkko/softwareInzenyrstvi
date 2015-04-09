<?php

namespace App\Model;
use Nette;
/**
 * IMPLEMENTOVANO:
 * seznam tymu
 * seznam clenu tymu
 * seznam clenu podle aktivity
 * seznam aktivnich/neaktivnich clenu
 * ukonceni cinnosti tymu
 * pridat clena tymu
 * odebrat clena tymu 
 * 
 */
class Teams extends Nette\Object {
    /** @var Nette\Database\Context */
    private $database;
    
    private $projekty;
//------------------------------------------------------------------------------
    
    public function __construct(Nette\Database\Context $database, \App\Model\Projects $projects)
    {
            $this->database = $database;
            $this->projekty = $projects;
    }
//------------------------------------------------------------------------------
    
    public function vratTym($id_tym)
    {
        $pom = $this->database->table('Tymy')->where('ID = ?', $id_tym);
        if ($pom->count() > 0) {
            return $pom->fetch();
        } else {
            return NULL;
        }
    }
 //------------------------------------------------------------------------------
    
    public function seznamVsechTymu()
    {
        $pom = $this->database->table('Tymy');
        return $pom;
    }
//------------------------------------------------------------------------------ 
    
    public function seznamTymuPodleAktivity($ukoncen)
    {
        $pom = $this->database->table('Tymy')->where('ukoncen = ?', $ukoncen);
        return $pom;
    }
//------------------------------------------------------------------------------ 
    
    public function ukonciCinnostTymu($id_tym)
    {
        try {
            $this->database->beginTransaction();
            $clenove = $this->seznamAktivnichClenuTymu($id_tym);
            foreach ($clenove as $clen){
                $this->odeberClenaTymu($clen->osoba_id, $id_tym);
            }            
            $this->database->table('Tymy')->where('ID = ?', $id_tym)->update(array(
                'ukoncen' => true,
            ));            
            $this->database->commit();
        } catch (Exception $ex) {
            $this->database->rollBack();
        }
    }
//------------------------------------------------------------------------------
    
    public function seznamVsechClenuTymu($id_tymu)
    {
        $pom = $this->database->table('Tym_osoby')->where('tym_id = ?', $id_tymu);
        return $pom;
    }
//------------------------------------------------------------------------------
    
    public function seznamAktivnichClenuTymu($id_tym)
    {
        $pom = $this->database->table('Tym_osoby')->where('tym_id = ? AND datum_ukonceni IS NULL', $id_tym);
        return $pom;
    }
//------------------------------------------------------------------------------
    
    public function seznamNeaktivnichClenuTymu($id_tym)
    {
        $pom = $this->database->table('Tym_osoby')->where('tym_id = ? AND datum_ukonceni IS NOT NULL', $id_tym);
        return $pom;
    }
//------------------------------------------------------------------------------ 
    
    public function pridejClenaDoTymu($id_clen, $id_tym, $pozice = NULL) 
    {
        $pom = $this->database->table('Tym_osoby')->where('osoba_id = ? AND tym_id = ?', $id_clen, $id_tym);
        if ($pom->count() > 0){
            $this->database->table('Tym_osoby')->where('osoba_id = ? AND tym_id = ?', $id_clen, $id_tym)
                    ->update(array(
                        'datum_pripojeni' => date('Y-m-d'),
                        'datum_ukonceni' => NULL,
                    ));
        } else {        
            $this->database->table('Tym_osoby')->insert(array(
                'osoba_id'        => $id_clen,
                'tym_id'          => $id_tym,
                'datum_pripojeni' => date('Y-m-d'),
                'pozice'          => $pozice,
            )); 
        }
    }
//------------------------------------------------------------------------------
    
    public function odeberClenaTymu($id_clen, $id_tym)
    {
        $this->database->table('Tym_osoby')->where('osoba_id = ? AND tym_id = ?', $id_clen, $id_tym)
                ->update(array(
                    'datum_ukonceni' => date('Y-m-d'),
                ));
    }
//------------------------------------------------------------------------------
    
    public function zalozTym($popis)
    {
        $pom = $this->database->table('Tymy')->insert(array(
            'datum_zalozeni' => date('Y-m-d'),
            'popis' => $popis,
        ));
        return $pom;
    }
//------------------------------------------------------------------------------

    public function seznamProjektuTymu($id_tym)
    {
        $pom = $this->database->table('Projekty')->where('tym_id = ?', $id_tym);
        return $pom;
    }
//------------------------------------------------------------------------------
    
    public function vsechnyProjUkonceny($id_tym)
    {
        $proj = $this->projekty->seznamNeukoncenychProjektu($id_tym);
        if ($proj->count() > 0) {
            return false;
        } else {
            return true;
        }        
    }
//------------------------------------------------------------------------------    
}
