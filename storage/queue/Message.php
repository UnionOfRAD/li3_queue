<?php

namespace li3_queue\storage\queue;

use BadMethodCallException;

class Message extends \lithium\core\Object {

	protected $_id = null;

	protected $_queue = null;

	protected $_contentType = 'text/plain';

	protected $_data = '';

	protected $_priority = 0;

	protected $_timestamp = null;

	protected $_expiration = null;

	protected $_redelivery = false;

	protected $_autoConfig = array(
		'id',
		'queue',
		'data',
		'priority',
		'redelivery'
	);

	public function __construct(array $config = array()) {
		$defaults = array(
			'id' => null,
			'queue' => null,
			'data' => '',
			'priority' => 0,
			'redelivery' => false
		);

		$this->id(sha1(uniqid('JvKnrQWPsThuJteNQAuH' . mt_rand(), true)));

		parent::__construct($config + $defaults);
	}

	public function id($id = null) {
		if($id) {
			$this->_id = $id;
		}
		return $this->_id;
	}

	public function contentType($type = null) {
		return $this->_contentType;
	}

	public function data($data = null) {
		if($data) {
			$this->_data = $data;
		}
		return $this->_data;
	}

	public function priority() {
		return $this->_priority;
	}

	public function timestamp() {
		return $this->_timestamp;
	}

	public function expiration() {
		return $this->_expiration;
	}

	public function isRedelivery() {
		return $this->_redelivery;
	}

	public function confirm() {
		$method = __FUNCTION__;
		if ($queue = $this->_queue) {
			return call_user_func_array(array(&$queue, $method), array($this));
		}
		$message = "No queue bound to call `{$method}`.";
		throw new BadMethodCallException($message);
	}

	public function requeue() {
		$method = __FUNCTION__;
		if ($queue = $this->_queue) {
			return call_user_func_array(array(&$queue, $method), array($this));
		}
		$message = "No queue bound to call `{$method}`.";
		throw new BadMethodCallException($message);
	}

}

?>