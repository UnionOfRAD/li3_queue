<?php
/**
 * li3_queue: queue plugin for the lithium framework
 *
 * @copyright     Copyright 2012, Olivier Louvignes for Union of RAD (http://union-of-rad.org)
 * @copyright     Inspired by David Persson's Queue plugin for CakePHP.
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 *
 */

namespace li3_queue\extensions\adapter\queue;

use lithium\core\NetworkException;
use li3_queue\extensions\adapter\net\socket\Beanstalk as BeanstalkSocket;

class Beanstalk extends \li3_queue\extensions\adapter\Queue {

	/**
	 * Stores a connection to a remote resource. Usually a database connection (`resource` type),
	 * or an HTTP connection object ('object' type).
	 *
	 * @var mixed
	 */
	public $connection = null;

	/**
	 * Stores the status of this object's connection. Updated when `connect()` or `disconnect()` are
	 * called, or if an error occurs that closes the object's connection.
	 *
	 * @var boolean
	 */
	protected $_isConnected = false;

	public function __construct(array $config = array()) {
		$defaults = array(
			'autoConnect'	=> true,
			'persistent'	=> true,
			'format'		=> 'php',
			'kickBound'		=> 100,
			'host'			=> BeanstalkSocket::DEFAULT_HOST . ':' . BeanstalkSocket::DEFAULT_PORT,
			'timeout'		=> 60
		);
		parent::__construct($config + $defaults);
	}

	protected function _init() {
		parent::_init();
		if ($this->_config['autoConnect']) {
			$this->connect();
		}
	}

	/* Connection Protocol */

	/**
	 * Connect to the Beanstalk server.
	 *
	 * @see lithium\data\source\MongoDb::__construct()
	 * @link http://php.net/manual/en/mongo.construct.php PHP Manual: Mongo::__construct()
	 * @return boolean Returns `true` the connection attempt was successful, otherwise `false`.
	 */
	public function connect() {
		$cfg = $this->_config;
		$this->_isConnected = false;

		$socketOptions = array('persistent' => $cfg['persistent'], 'host' => $cfg['host'], 'port' => $cfg['port'], 'timeout' => -1);
		$this->connection = new BeanstalkSocket($socketOptions);

		try {
			if ($this->connection->open($socketOptions)) {
				$this->_isConnected = true;
			}
		} catch (Exception $e) {
			throw new NetworkException("Could not connect to Beanstalk.", 503, $e);
		}

		return $this->_isConnected;
	}

	/**
	 * Checks the connection status of this data source. If the `'autoConnect'` option is set to
	 * true and the source connection is not currently active, a connection attempt will be made
	 * before returning the result of the connection status.
	 *
	 * @param array $options The options available for this method:
	 *        - 'autoConnect': If true, and the connection is not currently active, calls
	 *        `connect()` on this object. Defaults to `false`.
	 * @return boolean Returns the current value of `$_isConnected`, indicating whether or not
	 *         the object's connection is currently active.  This value may not always be accurate,
	 *         as the connection could have timed out or otherwise been dropped by the remote
	 *         resource during the course of the request.
	 */
	public function isConnected(array $options = array()) {
		$defaults = array('autoConnect' => false);
		$options += $defaults;

		if (!$this->_isConnected && $options['autoConnect']) {
			try {
				$this->connect();
			} catch (NetworkException $e) {
				$this->_isConnected = false;
			}
		}
		return $this->_isConnected;
	}

	/**
	 * Disconnect from the Beanstalk server.
	 *
	 * @return boolean True on successful disconnect, false otherwise.
	 */
	public function disconnect() {
		if ($this->isConnected()) {
			try {
				$this->_isConnected = !$this->connection->close();
			} catch (Exception $e) {}
			unset($this->connection);
			return !$this->_isConnected;
		}
		return true;
	}

	/* Queue Protocol */

	public function write($data, array $options = array()) {
		return $this->put($data, $options);
	}

	public function read(array $options = array()) {

	}

	public function confirm($message, array $options = array()) {

	}

	public function requeue($message, array $options = array()) {

	}

	public function consume($callback, array $options = array()) {

	}

	public function purge() {

	}

	public function add($task, array $options = array()) {
		return $this->put($task, $options);
	}

	public function reset(array $options = array()) {
		$defaults = array(
			'timeout' => 1,
			'tube' => 'default'
		);
		$options += $defaults;

		while($job = $this->reserve($options)) {
			$this->delete($job['id']);
		}
		return true;
	}

	public function run(array $options = array()) {
		return $this->reserve($options);
	}

	/* Beanstalk Commands */

	public function put($data, $options = array()) {
		$defaults = array(
			'priority' => 0,
			'delay' => 0,
			'timeout' => $this->_config['timeout'],
			'tube' => 'default'
		);
		$options += $defaults;
		extract($options, EXTR_OVERWRITE);

		if($tube && !$this->choose($tube)) {
			return false;
		}

		return $this->connection->put($priority, $delay, $timeout, $this->_encode($data));
	}

	public function choose($tube) {
		return $this->connection->choose($tube);
	}

	/**
	 * Reserve a job (with a timeout)
	 */
	public function reserve($options = array()) {
		$defaults = array(
			'timeout' => null,
			'tube' => null
		);
		$options += $defaults;
		extract($options, EXTR_OVERWRITE);

		if($tube && !$this->watch($tube)) {
			return false;
		}
		$result = $this->connection->reserve($timeout);
		if(!$result) {
			return false;
		}

		return array_merge((array)$this->_decode($result['body']), array('id' => $result['id']));
	}

	/**
	 * Adds the named tube to the watch list for the current
	 * connection.
	 */
	public function watch($tubes) {
		foreach((array)$tubes as $tube) {
			if (!$this->connection->watch($tube)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Puts a reserved job back into the ready queue
	 */
	public function release($options = array()) {
		if (!is_array($options)) {
			$options = array('id' => $options);
		}

		$defaults = array(
			'id' => null,
			'priority' => 0,
			'delay' => 0
		);
		$options += $defaults;
		extract($options, EXTR_OVERWRITE);

		return $this->connection->release($id, $priority, $delay);
	}

	/**
	 * Deletes a job
	 *
	 * @param mixed $id
	 */
	function delete($id) {
		return $this->connection->delete($id);
	}

	/**
	 * Allows a worker to request more time to work on a job
	 */
	public function touch($options = array()) {
		if (!is_array($options)) {
			$options = array('id' => $options);
		}

		$defaults = array(
			'id' => null
		);
		$options += $defaults;
		extract($options, EXTR_OVERWRITE);

		return $this->connection->touch($id);
	}

	/**
	 * Puts a job into the "buried" state
	 *
	 * Buried jobs are put into a FIFO linked list and will not be touched
	 * until a client kicks them.
	 */
	public function bury($options = array()) {
		if (!is_array($options)) {
			$options = array('id' => $options);
		}

		$defaults = array(
			'id' => null,
			'priority' => 0
		);
		$options += $defaults;
		extract($options, EXTR_OVERWRITE);

		return $this->connection->bury($id, $priority);
	}

	/**
	 * Moves jobs into the ready queue (applies to the current tube)
	 *
	 * If there are buried jobs those get kicked only otherwise
	 * delayed jobs get kicked.
	 */
	function kick($options = array()) {
		if (!is_array($options)) {
			$options = array('bound' => $options);
		}

		$defaults = array(
			'bound' => $this->_config['kickBound'],
			'tube' => null
		);
		$options += $defaults;
		extract($options, EXTR_OVERWRITE);

		if ($tube && !$this->choose($Model, $tube)) {
			return false;
		}
		return $this->connection->kick($bound);
	}

	/**
	 * Inspect a job by id
	 */
	function peek($id) {
		return $this->connection->peek($id);
	}

	function next($type, $options = array()) {
		$defaults = array(
			'tube' => null
		);
		$options += $defaults;
		extract($options, EXTR_OVERWRITE);

		if ($options['tube'] && !$this->choose($options['tube'])) {
			return false;
		}

		$method = 'peek' . ucfirst($type);
		$result = $this->connection->{$method}();
		if (!$result) {
			return false;
		}

		return array_merge($this->_decode($result['body']), array('id' => $result['id']));
	}

	/**
	 * Gives statistical information about the system as a whole
	 */
	function statistics($type = null, $key = null) {
		if ($type == 'job') {
			return $this->connection->statsJob($key);
		} elseif ($type == 'tube') {
			$key = $key !== null ? $key : $this->connection->listTubeChosen();
			return $this->connection->statsTube($key);
		}
		return $this->connection->stats();
	}

	protected function _encode($data) {
		switch ($this->_config['format']) {
			case 'json':
				return json_encode($data);
			case 'php':
			default:
				return serialize($data);
		}
	}

	protected function _decode($data) {
		switch ($this->_config['format']) {
			case 'json':
				return json_decode($data);
			case 'php':
			default:
				return unserialize($data);
		}
	}

	/* Stats Commands */

	public function stats() {
		return $this->connection->stats();
	}

}

?>
