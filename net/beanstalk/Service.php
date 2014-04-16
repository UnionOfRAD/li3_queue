<?php

namespace li3_queue\net\beanstalk;

use lithium\core\Libraries;
use lithium\core\ClassNotFoundException;

class Service extends \lithium\core\Object {

	/**
	 * The `Socket` instance used to send `Service` calls.
	 *
	 * @var lithium\net\Socket
	 */
	public $connection = null;

	/**
	 * Indicates whether `Service` is connected.
	 *
	 * @var boolean
	 */
	protected $_isConnected = false;

	protected $_classes = array(
		'request' => '\li3_queue\net\beanstalk\Request',
		'response' => '\li3_queue\net\beanstalk\Response'
	);

	/**
	 * Initializes a new `Service` instance.
	 *
	 * @param array $config
	 */
	public function __construct(array $config = array()) {
		$defaults = array(
			'persistent' => true,
			'scheme' => 'tcp',
			'host' => 'localhost',
			'port' => 11300,
			'timeout' => 30,
			'autoConnect' => true,
			'socket' => 'Stream'
		);
		parent::__construct($config + $defaults);
	}

	protected function _init() {
		$config = array('classes' => $this->_classes) + $this->_config;

		try {
			$this->connection = Libraries::instance('socket', $config['socket'], $config);
		} catch(ClassNotFoundException $e) {
			$this->connection = null;
		}
	}

	public function connect() {
		$config = &$this->_config;

		if(!$this->_isConnected) {
			if($this->connection->open($config)) {
				$this->_isConnected = true;
			}
		}
		return $this->_isConnected;
	}

	public function choose($tube) {
		return $this->send('use', $tube);
	}

	public function put($data, $options = array()) {
		return $this->send('put', $data, $options);
	}

	public function reserve($timeout = null) {
		if(!is_null($timeout)) {
			$response = $this->send('reserve-with-timeout', null, array('timeout' => $timeout));
		} else {
			$response = $this->send('reserve');
		}
		return $response;
	}

	public function release($id, array $options = array()) {
		return $this->send('release', $id, $options);
	}

	public function delete($id) {
		return $this->send('delete', $id);
	}

	public function listTubes() {
		return $this->send('list-tubes');
	}

	public function stats() {
		return $this->send('stats');
	}

	public function send($method, $data = null, array $options = array()) {
		$config = array('method' => $method, 'data' => $data, 'options' => $options);
		$request = $this->_instance('request', $config);
		var_dump($request);
		$response = $this->connection->send($request);
		return $response;
	}

}

?>