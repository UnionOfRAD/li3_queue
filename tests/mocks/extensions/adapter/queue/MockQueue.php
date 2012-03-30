<?php

namespace li3_queue\tests\mocks\extensions\adapter\queue;

class MockQueue extends \li3_queue\extensions\adapter\Queue {
	protected $_queue = array();

	public function add($task, array $options = array()) {
		$this->_queue[] = array('task' => $task, 'options' => $options);
		return true;
	}

	public function run(array $options = array()) {
		if (!empty($this->_queue)) {
			array_shift($this->_queue);
			return 'Success';
		}

		return false;
	}

	public function reset(array $options = array()) {
		$this->_queue = array();
	}
}

?>