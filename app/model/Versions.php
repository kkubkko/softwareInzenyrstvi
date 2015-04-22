<?php
/**
 * IMPLEMENTOVANO:
 * vraceni cisla aktualni verze
 * nacteni verze
 * nacteni aktualni verze
 * vytvoreni prvni verze
 * vytvoreni dalsi verze
 * seznam pripominek k dane verzi dokumentu
 * seznam uprav k dane verzi dokumentu
 * seznam uprav a pripominek
 */


namespace App\Model;

use Nette;

/**
 * Description of Version
 *
 * @author Luky
 */
class Versions extends Nette\Object
{
    /** @var Nette\Database\Context */
    private $database;
    /** @var Documents */
    private $dokumenty;
    
    public function __construct(Nette\Database\Context $database, Documents $documents)
    {
            $this->database = $database;
            $this->dokumenty = $documents;
    }
    
    public function aktualniVerze($id_dokument)
    {
        return $this->dokumenty->aktualniVerze($id_dokument);
    }
    
    public function nactiVerzi($id_dokument, $verze)
    {
        $pom = $this->database->table('Verze')->where('dokument_id = ? AND verze = ?', $id_dokument, $verze)->fetch();
        return $pom;
    }
    
    public function nactiAktualniVerzi($id_dokument)
    {
        $verze = $this->aktualniVerze($id_dokument);
        return $this->nactiVerzi($id_dokument, $verze);
    }
    
    public function vytvoritPrvniVerzi($id_dokumentu, $upravil)
    {            
        $pom = $this->database->table('Verze')->insert(array(
            'dokument_id' => $id_dokumentu,
            'akt_etapa' => 'tvorba požadavků',
            'verze' => 1,
            'datum_vytvoreni' => date('Y-m-d'),
            'upravil_id' => $upravil, 
        ));
        return $pom;
    }
    
    public function vytvoritDalsiVerzi($id_dokumentu, $upravil)
    {
        $akt = $this->aktualniVerze($id_dokumentu);
        $pom2 = $this->nactiVerzi($id_dokumentu, $akt);
        $pom = $this->database->table('Verze')->insert(array(
            'dokument_id' => $id_dokumentu,
            'akt_etapa' => $pom2->akt_etapa,
            'verze' => $akt + 1,
            'datum_vytvoreni' => date('Y-m-d'),
            'upravil_id' => $upravil, 
        ));
        return $pom;
    }
    
    public function seznamPripominekVerzeDoc($id_dokument, $verze)
    {
        $ver = $this->nactiVerzi($id_dokument, $verze);
        if ($ver){
            return $this->seznamPripominek($ver->ID);
        }        
    }
    
    public function seznamUpravVerzeDoc($id_dokument, $verze)
    {
        $ver = $this->nactiVerzi($id_dokument, $verze);
        if ($ver){
            return $this->seznamUprav($ver->ID);
        }        
    }
    
    public function seznamPripominek($id_verze)
    {
        $pom = $this->database->table('Pripominky')->where('verze_id = ?', $id_verze);
        return $pom;        
    }
    
    public function seznamUprav($id_verze)
    {
        $pom = $this->database->table('Upravy')->where('verze_id = ?', $id_verze);
        return $pom;        
    }
    
    public function pridejPripominku($text, $id_verze){
        $pom = $this->database->table('Pripominky')->insert(array(
            'text' => $text,
            'verze_id' => $id_verze,
        ));
        return $pom;
    }
    
    public function pridejUpravu($text, $id_verze)
    {
        $pom = $this->database->table('Upravy')->insert(array(
            'verze_id' => $id_verze,
            'text' => $text,
        ));
        return $pom;
    }
    
    public function vratVerziProID($id_verze)
    {
        $pom = $this->database->table('Verze')->where('ID = ?', $id_verze)->fetch();
        return $pom;
    }
}
