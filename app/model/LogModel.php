<?php

namespace Model;

use Model\BaseModel;
use Nette\Database\Connection;

/**
 * Description of LogModel
 *
 * @author e.szabo
 */
class LogModel extends BaseModel {
	private static $EVENT_TEXTS = array(
		"login" => "Používateľ {user} sa prihlásil.",
		"logout" => "Používateľ {user} sa odhlásil.",
		"restart_generators_success" => "Používateľ {user} úspešne reštartoval generátory.",
		"restart_generators_failure" => "Používateľ {user} sa neúspešne pokúsil reštartovať generátory.",
		"winning_line_added" => "Používateľ {user} pridal novú výhernú líniu s ID {id}.",
		"winning_line_removed" => "Používateľ {user} odstránil výhernú líniu s ID {id}.",
		"special_symbol" => "Používateľ {user} označil alebo zrušil označenie špeciálneho symbolu s ID {id}.",
		"combination_added" => "Používateľ {user} vytvoril výhernú kombináciu s ID {id}.",
		"combination_removed" => "Používateľ {user} odstránil výhernú kombináciu s ID {id}.",
		"user_added" => "Používateľ {user} vytvoril používateľa s ID {id}.",
		"user_removed" => "Používateľ {user} odstránil používateľa s ID {id}."
	);
	
	public static $EVENT_TYPES = array(
		"login" => "Prihlásenie",
		"logout" => "Odhlásenie",
		"restart_generators_success" => "Reštartovanie generátorov - OK",
		"restart_generators_failure" => "Reštartovanie generátorov - CHYBA",
		"winning_line_added" => "Pridaná výherná línia",
		"winning_line_removed" => "Odstránená výherná línia",
		"special_symbol" => "Špeciálny symbol",
		"combination_added" => "Pridaná kombinácia",
		"combination_removed" => "Odstránená kombinácia",
		"user_added" => "Pridaný používateľ",
		"user_removed" => "Odstránený používateľ"
	);
	
	function __construct(Connection $database) {
		parent::__construct($database);
		$this->table = "event_log";
	}
	
	/**
	 * Create an event record.
	 * @param string $action
	 * @param int $objectId
	 * @param int $userId
	 * @param string $username
	 * @return int
	 */
	public function save($action, $objectId, $userId, $username)
	{
		$this->database->query("INSERT INTO {$this->table}", array(
			"action" => $action,
			"objectId" => $objectId,
			"user" => $userId,
			"username" => $username,
			"time" => new \DateTime
		));
		
		return $this->database->getInsertId();
	}
	
	/**
	 * Finds last events.
	 * @param string $type
	 * @param int $count
	 * @return array
	 */
	public function getLastEvents($type, $count = 50)
	{
		if (empty($type) || !array_key_exists($type, static::$EVENT_TEXTS)) {
			// fetch from all events
			$events = $this->database->fetchAll("SELECT * FROM {$this->table} ORDER BY id DESC LIMIT ?", $count);
		} else {
			// filter events based on type
			$events = $this->database->fetchAll("SELECT * FROM {$this->table} WHERE action = ? ORDER BY id DESC LIMIT ?", $type, $count);
		}
		
		$eventsFormatted = array();
		foreach ($events as $event) {
			if (array_key_exists($event->action, static::$EVENT_TEXTS)) {
				$text = str_replace("{user}", $event->username, static::$EVENT_TEXTS[$event->action]);
				
				if (strpos($text, "{id}") !== false) {
					$text = str_replace("{id}", $event->objectId, $text);
				}
				
				$eventText = '<span class="event-time">' . $event->time->format("H:i:s d.m.Y") . "</span>&nbsp;&nbsp;&nbsp;" . $text;
				$eventsFormatted[] = $eventText;
			}
		}
		
		return $eventsFormatted;
	}
}
