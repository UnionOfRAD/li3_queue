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
			'socket' => 'Beanstalk'
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

	/**
	 * Choose method.
	 * Sends `use` command to set the tube.
	 *
	 * @param string $tube
	 * @return object Returns an instance of `beanstalk\Response`
	 */
	public function choose($tube) {
		return $this->send('use', $tube);
	}

	/**
	 * Put method.
	 * Sends `put` command to write a message to the tube.
	 *
	 * @param string $data
	 * @param string $pri
	 * @param string $delay
	 * @param string $ttr
	 * @return object Returns an instance of `beanstalk\Response`
	 */
	public function put($data, $pri, $delay, $ttr) {
		$options = array('pri' => $pri, 'delay' => $delay, 'ttr' => $ttr);
		return $this->send('put', $data, $options);
	}

	/**
	 * Reserve method.
	 * If a timeout is set it sends `reserve-with-timeout` else it sends `reserve`
	 *
	 * @param integer $timeout
	 * @return object Returns an instance of `beanstalk\Response`
	 */
	public function reserve($timeout = null) {
		if(!is_null($timeout)) {
			$options = array('timeout' => $timeout);
			$response = $this->send('reserve-with-timeout', null, $options);
		} else {
			$response = $this->send('reserve');
		}
		return $response;
	}

	/**
	 * Reserve method.
	 * If a timeout is set it sends `reserve-with-timeout` else it sends `reserve`
	 *
	 * @param integer $timeout
	 * @return object Returns an instance of `beanstalk\Response`
	 */
	public function delete($id) {
		return $this->send('delete', $id);
	}

	/**
	 * Release method.
	 *
	 * @param integer $id
	 * @param integer $pri P
	 * @param integer $delay
	 * @return object Returns an instance of `beanstalk\Response`
	 */
	public function release($id, $pri, $delay) {
		$options = array('pri' => $pri, 'delay' => $delay);
		return $this->send('release', $id, $options);
	}

	/**
	 * Bury method.
	 *
	 * @param integer $id
	 * @param integer $pri
	 * @return object Returns an instance of `beanstalk\Response`
	 */
	public function bury($id, $pri) {
		$options = array('pri' => $pri);
		return $this->send('bury', $id, $options);
	}

	/**
	 * Touch method.
	 *
	 * @param integer $id
	 * @return object Returns an instance of `beanstalk\Response`
	 */
	public function touch($id) {
		return $this->send('touch', $id);
	}

	/**
	 * Watch method.
	 *
	 * @param string $tube
	 * @return object Returns an instance of `beanstalk\Response`
	 */
	public function watch($tube) {
		return $this->send('watch', $tube);
	}

	/**
	 * Ignore method.
	 *
	 * @param string $tube
	 * @return object Returns an instance of `beanstalk\Response`
	 */
	public function ignore($tube) {
		return $this->send('ignore', $tube);
	}

	/**
	 * Peek method.
	 * `$type` can contain an integer <id> or a string of
	 * `ready`, `delayed`, `buried`
	 *
	 * @param mixed $type
	 * @return object Returns an instance of `beanstalk\Response`
	 */
	public function peek($type) {
		if(in_array($type, array('ready', 'delayed', 'buried'))) {
			return $this->send('peek-' . $type);
		}
		return $this->send('peek', $type);
	}

	/**
	 * Kick method.
	 * <bound> is an integer upper bound on the number of jobs to kick. The server
	 * will kick no more than <bound> jobs. If `$bound` is `job` and `$id` set it
	 * sends `kick-job`
	 *
	 * @param mixed $bound
	 * @param mixed $id
	 * @return object Returns an instance of `beanstalk\Response`
	 */
	public function kick($bound, $id = null) {
		if(in_array($bound, array('job'))) {
			return $this->send('kick-' . $bound, $id);
		}
		return $this->send('kick', $bound);
	}

	/**
	 * Stats job method.
	 * The stats-job command gives statistical information about the specified job if
	 * it exists
	 *
	 * @param integer $id
	 * @return object Returns an instance of `beanstalk\Response`
	 */
	public function statsJob($id) {
		return $this->send('stats-job', $id);
	}

	/**
	 * Stats tube method.
	 * The stats-tube command gives statistical information about the specified tube if
	 * it exists
	 *
	 * @param string $tube
	 * @return object Returns an instance of `beanstalk\Response`
	 */
	public function statsTube($tube) {
		return $this->send('stats-tube', $tube);
	}

	/**
	 * Stats method.
	 * The stats command gives statistical information about the system as a whole.
	 *
	 * @param integer $id
	 * @return object Returns an instance of `beanstalk\Response`
	 */
	public function stats() {
		return $this->send('stats');
	}

	/**
	 * List tubes method.
	 * The list-tubes command returns a list of all existing tubes.
	 *
	 * @return object Returns an instance of `beanstalk\Response`
	 */
	public function listTubes() {
		return $this->send('list-tubes');
	}

	/**
	 * List tubes used method.
	 * The list-tube-used command returns the tube currently being used by the client.
	 *
	 * @return object Returns an instance of `beanstalk\Response`
	 */
	public function listTubeUsed() {
		return $this->send('list-tube-used');
	}

	/**
	 * List tubes watched method.
	 * The list-tubes-watched command returns a list tubes currently being watched by
	 * the client.
	 *
	 * @return object Returns an instance of `beanstalk\Response`
	 */
	public function listTubesWatched() {
		return $this->send('list-tubes-watched');
	}

	/**
	 * Pause tube method.
	 * The pause-tube command can delay any new job being reserved for a given time.
	 *
	 * @param string $tube
	 * @param integer $delay
	 * @return object Returns an instance of `beanstalk\Response`
	 */
	public function pauseTube($tube, $delay) {
		$options = array('delay' => $delay);
		return $this->send('pause-tube', $tube, $options);
	}

	/**
	 * Send method.
	 * Constructs a request object to send to the socket
	 *
	 * @param string $method
	 * @param string $data
	 * @param array $options
	 * @return object Returns an instance of `beanstalk\Response`
	 */
	public function send($method, $data = null, array $options = array()) {
		$config = array('method' => $method, 'data' => $data, 'options' => $options);
		$request = $this->_instance('request', $config);
		$response = $this->connection->send($request);

		if($response->bytes > 0) {
			$response->data = $this->connection->read();
		}
		return $response;
	}

	public function disconnect() {
		return $this->connection->close();
	}

}

?>