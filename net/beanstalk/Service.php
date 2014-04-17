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
		$config = $this->_config;

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

	public function put($data, $pri, $delay, $ttr) {
		$options = array('pri' => $pri, 'delay' => $delay, 'ttr' => $ttr);
		return $this->send('put', $data, $options);
	}

	public function reserve($timeout = null) {
		if(!is_null($timeout)) {
			$options = array('timeout' => $timeout);
			$response = $this->send('reserve-with-timeout', null, $options);
		} else {
			$response = $this->send('reserve');
		}
		return $response;
	}

	public function delete($id) {
		return $this->send('delete', $id);
	}

	public function release($id, $pri, $delay) {
		$options = array('pri' => $pri, 'delay' => $delay);
		return $this->send('release', $id, $options);
	}

	public function bury($id, $pri) {
		$options = array('pri' => $pri);
		return $this->send('bury', $id, $options);
	}

	public function touch($id) {
		return $this->send('touch', $id);
	}

	public function watch($tube) {
		return $this->send('watch', $tube);
	}

	public function ignore($tube) {
		return $this->send('ignore', $tube);
	}

	public function peek($type) {
		if(in_array($type, array('ready', 'delayed', 'buried'))) {
			return $this->send('peek-' . $type);
		}
		return $this->send('peek', $type);
	}

	public function kick($bound, $id = null) {
		if(in_array($bound, array('job'))) {
			return $this->send('kick-' . $bound, $id);
		}
		return $this->send('kick', $bound);
	}

	public function statsJob($id) {
		return $this->send('stats-job', $id);
	}

	public function statsTube($tube) {
		return $this->send('stats-tube', $tube);
	}

	public function stats() {
		return $this->send('stats');
	}

	public function listTubes() {
		return $this->send('list-tubes');
	}

	public function listTubeUsed() {
		return $this->send('list-tube-used');
	}

	public function listTubesWatched() {
		return $this->send('list-tubes-watched');
	}

	public function pauseTube($tube, $delay) {
		$options = array('delay' => $delay);
		return $this->send('pause-tube', $tube, $options);
	}

	public function send($method, $data = null, array $options = array()) {
		$config = array('method' => $method, 'data' => $data, 'options' => $options);
		$request = $this->_instance('request', $config);
		return $this->connection->send($request);
	}

}

?>