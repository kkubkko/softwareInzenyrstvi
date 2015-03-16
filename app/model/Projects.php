<?php
/**
 * Description of Projects
 *
 * @author Luky
 */
namespace App\Model;
use Nette,
    Nette\Utils\Strings,
	Nette\Security\Passwords;        

        
class Projects extends Nette\Object {
    
    /** @var Nette\Database\Context */
	private $database;


	public function __construct(Nette\Database\Context $database)
	{
		$this->database = $database;
	}
    
    public function seznamProjektu()
    {
        $pom = $this->database->table('Projekty');
        return $pom;
    }
    
    public function vratHeslo($heslo)
    {
        return Passwords::hash($heslo);
    }
    
    public function ulozLoginAheslo($jmeno, $login, $heslo)
    {
        $this->database->table('Osoby')->where('jmeno = ?', $jmeno)->update(
                array(
                    'jmeno' => $jmeno,
                    'heslo' => $this->vratHeslo($heslo),
                ));
    }
}
