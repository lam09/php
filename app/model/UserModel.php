<?php

namespace Model;

use Nette\Database\Connection;
use Nette\Database\Row;
use Nette\Security\Passwords;

/**
 * Description of UserModel
 *
 * @author e.szabo
 */
class UserModel extends BaseModel {
	function __construct(Connection $database) {
		parent::__construct($database);
		$this->table = "web_users";
	}
	
	/**
	 * Adds new user.
	 * @param string $username
	 * @param string $password
	 * @param string $name
	 * @param string $role
	 * @return int
	 */
	public function addUser($username, $password, $name, $role)
	{
		return $this->add(array(
			"username" => $username,
			"password" => Passwords::hash($password),
			"name" => $name,
			"role" => $role
		));
	}
	
	/**
	 * Finds user by username.
	 * @param string $username
	 * @return Row
	 */
	public function findByUsername($username)
	{
		return $this->database->fetch("SELECT * FROM {$this->table} WHERE username = ?", $username);
	}
	
	/**
	 * Fetches all users.
	 * @return array
	 */
	public function findAll()
	{
		return $this->database->fetchAll("SELECT * FROM {$this->table}");
	}
}
