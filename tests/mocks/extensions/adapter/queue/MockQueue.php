<?php

namespace li3_queue\tests\mocks\extensions\adapter\queue;

class MockQueue extends \li3_queue\extensions\adapter\Queue {

	protected $_queue = array();

	protected $_unacked = array();

	public function connect() {
		return true;
	}

	public function isConnected() {
		return true;
	}

	public function disconnect() {
		return true;
	}

	public function write($data, array $options = array()) {
		$defaults = array('class' => 'message', 'priority' => 0);
		$options += $defaults;
		$queue = &$this->_queue;

		$config = array(
			'queue' => $this,
			'data' => $data,
			'priority' => $options['priority']
		);
		$message = $this->invokeMethod('_instance', array($options['class'], $config));
		array_push($queue, $message);
		return true;
	}

	public function read(array $options = array()) {
		$queue = &$this->_queue;

		if(!empty($queue)) {
			$message = array_shift($queue);
			$this->_unacked[$message->id()] = $message;

			return $message;
		}
		return null;
	}

	public function confirm($message, array $options = array()) {
		$unacked = &$this->_unacked;
		unset($unacked[$message->id()]);

		return true;
	}

	public function requeue($message, array $options = array()) {
		$queue = &$this->_queue;
		$unacked = &$this->_unacked;
		$message = $unacked[$message->id()];
		unset($unacked[$message->id()]);

		if(array_push($queue, $message)) {
			return true;
		}
		return false;
	}

	public function consume($callback, array $options = array()) {
		$queue = $this->_queue;

		if(!empty($queue)) {
			$result = array_shift($queue);
			return $callback($result);
		}
		return null;
	}

	public function purge() {
		$queue = &$this->_queue;
		$unacked = &$this->_unacked;

		$queue = array();
		$unacked = array();

		return true;
	}

	public function stats() {
		$queue = &$this->_queue;
		$unacked = &$this->_unacked;

		$stats = array(
			'queue' => count($queue),
			'unacknowledged' => count($unacked)
		);
		return $stats;
	}

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