<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings,
	Nette\Security\Passwords;


/**
 * Users management.
 */
class UserManager extends Nette\Object implements Nette\Security\IAuthenticator
{
	const
		TABLE_NAME = 'Osoby',
		COLUMN_ID = 'ID',
		COLUMN_NAME = 'login',
		COLUMN_PASSWORD_HASH = 'heslo',
		COLUMN_ROLE = 'role_id';


	/** @var Nette\Database\Context */
	private $database;


	public function __construct(Nette\Database\Context $database)
	{
		$this->database = $database;
	}


	/**
	 * Performs an authentication.
	 * @return Nette\Security\Identity
	 * @throws Nette\Security\AuthenticationException
	 */
	public function authenticate(array $credentials)
	{
		list($username, $password) = $credentials;

		$row = $this->database->table(self::TABLE_NAME)->where(self::COLUMN_NAME, $username)->fetch();

		if (!$row) {
			throw new Nette\Security\AuthenticationException('The username is incorrect.', self::IDENTITY_NOT_FOUND);

		} elseif (!Passwords::verify($password, $row[self::COLUMN_PASSWORD_HASH])) {
			throw new Nette\Security\AuthenticationException('The password is incorrect.', self::INVALID_CREDENTIAL);

		} elseif (Passwords::needsRehash($row[self::COLUMN_PASSWORD_HASH])) {
			$row->update(array(
				self::COLUMN_PASSWORD_HASH => Passwords::hash($password),
			));
		}
                
                /* zjištení všech rolí uživatele */
                $rows2 = $this->database->table('prirazeniRole')->where('Osoby.login', $username)->fetchAll();
                
                if(!empty($rows2)){
                    $first = TRUE;
                    $vsechnyRole = '';
                    foreach($rows2 as $row2){
                        if($first){
                            $vsechnyRole .= $row2['role_id'];
                            $first = FALSE;
                        } else{
                            $vsechnyRole .= ',' . $row2;
                        }
                    }
                } else
                    throw new Nette\Security\AuthenticationException('Uživatel nemá žádnou roli.', self::NOT_APPROVED);
                /* --------------------------- */

                /*echo $vsechnyRole;
                exit();*/
                
		$arr = $row->toArray();
		unset($arr[self::COLUMN_PASSWORD_HASH]);
                unset($arr[self::COLUMN_ROLE]);
		return new Nette\Security\Identity($row[self::COLUMN_ID], $vsechnyRole, $arr);
	}


	/**
	 * Adds new user.
	 * @param  string
	 * @param  string
	 * @return void
	 */
	public function add($username, $password)
	{
		$this->database->table(self::TABLE_NAME)->insert(array(
			self::COLUMN_NAME => $username,
			self::COLUMN_PASSWORD_HASH => Passwords::hash($password),
		));
	}

}
