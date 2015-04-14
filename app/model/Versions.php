<?php
/**
 * IMPLEMENTOVANO:
 * vraceni aktualni verze
 * nacteni verze
 * vytvoreni prvni verze
 * vytvoreni dalsi verze
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
    
    public function vytvoritPrvniVerzi($id_dokumentu)
    {            
        $pom = $this->database->table('Verze')->insert(array(
            'dokument_id' => $id_dokumentu,
            'akt_etapa' => 'tvorba požadavků',
            'verze' => 1,
            'datum_vytvoreni' => date('Y-m-d'),
        ));
        return $pom;
    }
    
    public function vytvoritDalsiVerzi($id_dokumentu)
    {
        $akt = $this->aktualniVerze($id_dokumentu);
        $pom2 = $this->nactiVerzi($id_dokumentu, $akt);
        $pom = $this->database->table('Verze')->insert(array(
            'dokument_id' => $id_dokumentu,
            'akt_etapa' => $pom2->akt_etapa,
            'verze' => $akt + 1,
            'datum_vytvoreni' => date('Y-m-d'),
        ));
        return $pom;
    }
    
    public function seznamPripominekVerzeDoc($id_dokument, $verze)
    {
        $ver = $this->nactiVerzi($id_dokument, $verze);
        if (isset($ver)){
            return $this->seznamPripominek($ver->ID);
        }        
    }
    
    public function seznamUpravVerzeDoc($id_dokument, $verze)
    {
        $ver = $this->nactiVerzi($id_dokument, $verze);
        if (isset($ver)){
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
}
