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
			'timeout' => 5,
			'socket' => 'Stream',
			'eol' => "\r\n"
		);
		parent::__construct($config + $defaults);
	}

	protected function _init() {
		$config = &$this->_config;

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

	public function choose($tube = 'default') {
		$response = $this->write(sprintf('use %s', $tube));

		switch ($response[0]) {
			case 'USING':
				return true;
			default:
				return false;
		}
	}

	public function put($data, $priority = 0, $delay = 0, $ttr = 0) {
		$this->write(sprintf('put %d %d %d %d', $priority, $delay, $ttr, strlen($data)));
		$this->write($data);

		$status = strtok($this->read(), ' ');
		var_dump($stats);
exit;
		switch ($response[0]) {
			case 'INSERTED':
			case 'BURIED':
				return (integer) $response[1];
			case 'EXPECTED_CRLF':
			case 'JOB_TOO_BIG':
			default:
				return false;
		}
	}

	public function reserve() {
		$cmd = 'reserve';
		$response = $this->write($cmd);

		//$result = $this->write(sprintf('delete %d', $result));
		//$result = $this->read();
		//var_dump($result);
	}

	public function write($data, $ending = "\r\n") {
		return $this->connection->write(array('body' => $data . $ending));
	}

	public function read($length = 16384, $ending = "\r\n") {
		$resource = $this->connection->resource();
		return stream_get_line($resource, $length, $ending);
	}

}

?>