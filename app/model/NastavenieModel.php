<?php

namespace Model;

use Nette\Database\Connection;

class NastavenieModel extends BaseModel {
	function __construct(Connection $database) {
		parent::__construct($database);
		$this->table = "networklog";
	}


	public function getGameConfigs(){
		$sql = "SELECT * FROM configuration_game";
		$ret = $this->database->fetchAll($sql);
		return $ret;
	}

	public function getGamesWithConfig($id){

		$sql = "select g.id,g.name,a.* from games g
				LEFT JOIN (
					SELECT cgr.game_id ,cg.id as config_id FROM configuration_game_relation cgr
					LEFT JOIN configuration_game cg on cgr.configuration_game_id = cg.id
					WHERE cg.id = ? or cg.id IS NULL
				) a on g.id = a.game_id";

		$ret = $this->database->fetchAll($sql,$id);
		return $ret;
	}

	public function getGameConfig($id){
		$sql = "select * from configuration_game_relation cgr
				LEFT JOIN configuration_game cg on cgr.configuration_game_id = cg.id
				LEFT JOIN games g on cgr.game_id = g.id
				WHERE cg.id = ?";
		$ret = $this->database->fetchAll($sql,$id);
		return $ret;
	}

	public function setGameConfig($config_id,$config){

		$this->database->beginTransaction();

		$this->database->query("DELETE FROM configuration_game_relation WHERE configuration_game_id = ?",$config_id);

		$sql = 'INSERT INTO configuration_game_relation
		(configuration_game_id, game_id)
		VALUES (?, ?)';

		foreach ($config as $key => $value) {
			if($value == TRUE) {
				$this->database->query($sql,$config_id,$key);
			}
		}

		$this->database->commit();

	}

	public function getGameConfigsArray()
	{
		$sql = "SELECT * FROM configuration_game ORDER BY name";
		$ret = $this->database->query($sql);

		$ids = array();

		$ids[null] = null; //So we can set empty config

		while ($row = $ret->fetch()) {
			$ids[$row->id] = $row->name;
		}
		return $ids;
	}

	public function getConfigsArray()
	{
		$sql = "SELECT * FROM configuration ORDER BY name";
		$ret = $this->database->query($sql);

		$ids = array();

		$ids[null] = null; //So we can set empty config

		while ($row = $ret->fetch()) {
			$ids[$row->id] = $row->name;
		}
		return $ids;
	}


}