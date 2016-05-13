<?php

namespace Model;

use Nette\Database\Connection;

/**
 * Description of MachineModel
 *
 * @author e.szabo
 */
class MachineModel extends BaseModel {
	function __construct(Connection $database) {
		parent::__construct($database);
		$this->table = "machines";
	}
	
	public function getAllMachines()
	{
		$sql = "SELECT m.*,ml.user_id,ml.status,ml.version as onlineversion,ml.date, c.name as configuration_name, cg.name as configuration_game_name FROM {$this->table} m
				LEFT JOIN machines_online_log ml on m.id = ml.machine_id
				LEFT JOIN configuration c on m.configuration = c.id
				LEFT JOIN configuration_game cg on m.configuration_game = cg.id

				 WHERE (ml.id = (
					 SELECT MAX(id) FROM machines_online_log AS ml2
						 WHERE ml2.machine_id = ml.machine_id
				 )) OR  ml.id IS null
				ORDER BY description";
		return $this->database->fetchAll($sql);
	}
	
	public function getTransactionsByMachine($id, $offset, $limit)
	{
		$sql = "SELECT * FROM log_credit WHERE machine = ? ORDER BY id DESC LIMIT ? OFFSET ?";
		return $this->database->fetchAll($sql, $id, $limit, $offset);
	}
	
	public function getDepositCountByMachine($id)
	{
		$sql = "SELECT COUNT(*) as count FROM log_credit WHERE machine = ? AND increase > 0";
		return $this->database->fetch($sql, $id);
	}
	
	public function getPayoutCountByMachine($id)
	{
		$sql = "SELECT COUNT(*) as count FROM log_credit WHERE machine = ? AND increase < 0";
		return $this->database->fetch($sql, $id);
	}
	
	public function getDepositAmountByMachine($id)
	{
		$sql = "SELECT SUM(increase) as sum FROM log_credit WHERE machine = ? AND increase > 0";
		return $this->database->fetch($sql, $id);
	}
	
	public function getPayoutAmountByMachine($id)
	{
		$sql = "SELECT SUM(increase) as sum FROM log_credit WHERE machine = ? AND increase < 0";
		return $this->database->fetch($sql, $id);
	}
}
