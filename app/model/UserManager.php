<?php

namespace Model;

use Nette\Database\Context;
use Nette\Object;
use Nette\Security\AuthenticationException;
use Nette\Security\IAuthenticator;
use Nette\Security\Identity;
use Nette\Security\Passwords;

/**
 * Users management.
 */
class UserManager extends Object implements IAuthenticator
{
	const
		COLUMN_ID = 'id',
		COLUMN_USERNAME = 'username',
		COLUMN_PASSWORD = 'password';

	/** @var Context */
	private $database;
	private $table = "web_users";

	public function __construct(Context $database) {
		$this->database = $database;
	}

	/**
	 * Performs an authentication.
	 * @return Identity
	 * @throws AuthenticationException
	 */
	public function authenticate(array $credentials)
	{
		list($username, $password) = $credentials;

		$row = $this->database->table($this->table)->where(self::COLUMN_USERNAME, $username)->fetch();

		if (!$row) {
			throw new AuthenticationException("Nesprávne prihlasovacie údaje", self::IDENTITY_NOT_FOUND); 
		} elseif (!Passwords::verify($password, $row[self::COLUMN_PASSWORD])) {
			throw new AuthenticationException("Heslo je nesprávne.", self::INVALID_CREDENTIAL);
		} elseif (Passwords::needsRehash($row[self::COLUMN_PASSWORD])) {
			$row->update(array(
				self::COLUMN_PASSWORD => Passwords::hash($password),
			));
		}

		$user = array();
		foreach($row as $k => $v) {
			$user[$k] = $v;
		}

		unset($user[self::COLUMN_PASSWORD]);

		return new Identity($user[self::COLUMN_ID], $user["role"], $user);
	}
}
