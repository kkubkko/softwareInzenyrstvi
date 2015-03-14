<?php
/**
 * Description of Projects
 *
 * @author Luky
 */
namespace App\Model;
use Nette;

        
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
}
