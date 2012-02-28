<?php

namespace li3_queue\extensions\adapter\queue;

class Gearman extends \li3_queue\storage\Queue {
	public function add($task, array $options = array()) {
		return true;
	}

	public function reset(array $options = array()) {
		return true;
	}

	public function run(array $options = array()) {
		return true;
	}
}

?>