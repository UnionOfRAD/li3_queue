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

	protected $_autoConfig = array('classes' => 'merge');

	protected $_classes = array(
		'message' => 'li3_queue\storage\queue\Message',
		'service' => '\li3_queue\net\beanstalk\Service'
	);

	/**
	 * The `Socket` instance used to send `Service` calls.
	 *
	 * @var lithium\net\Socket
	 */
	public $connection = null;

	/**
	 * Stores the status of this object's connection. Updated when `connect()` or `disconnect()` are
	 * called, or if an error occurs that closes the object's connection.
	 *
	 * @var boolean
	 */
	protected $_isConnected = false;

	/**
	 * Adds config values to the public properties when a new object is created.
	 *
	 * @param array $config Configuration options : default value
	 *        - `'host'` _string_: '127.0.0.1'
	 *        - `'port'` _interger_: 11300
	 *        - `'timeout'` _interger_: 60
	 *        - `'tube'` _string_: 'default'
	 *        - `'kickBound'` _interger_: 100
	 *        - `'persistent'` _boolean_: true
	 *        - `'autoConnect'` _boolean_: true
	 */
	public function __construct(array $config = array()) {
		$defaults = array(
			'host' => '127.0.0.1',
			'port' => 11300,
			'timeout' => 60,
			'tube' => 'default',
			'kickBound' => 100,
			'persistent' => true,
			'autoConnect' => true
		);
		parent::__construct($config + $defaults);
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
		$config = &$this->_config;

		if(!$this->connection) {
			$this->connection = $this->invokeMethod('_instance', array('service', $this->_config));

			if($this->connection->connect()) {
				$this->_isConnected = true;

				$this->connection->choose($config['tube']);
				$this->connection->watch($config['tube']);
			}
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
 		return $this->_isConnected;
	}

	/**
	 * Disconnect from the Beanstalk server.
	 *
	 * @return boolean True on successful disconnect, false otherwise.
	 */
	public function disconnect() {
		if($this->isConnected()) {
			return $this->connection->disconnect();
		}
	}

	/* Queue Protocol */

	/**
	 * Write method.
	 * Sends `put` command to write a message to the queue.
	 * Defaults are the following:
	 * - `priority`=0 Messages are processed by priority, lowest first
	 * - `delay`=0 Number of seconds before moving the message to the ready queue
	 * - `timeout`=30 Number of seconds a worker can process a message
	 *
	 * @param string $data
	 * @param array $options
	 * @return boolean
	 */
	public function write($data, array $options = array()) {
		$defaults = array(
			'priority' => 0,
			'delay' => 0,
			'timeout' => 30
		);
		$options += $defaults;
		extract($options, EXTR_OVERWRITE);

		$response = $this->connection->put($data, $priority, $delay, $timeout);
		if($response->status == 'INSERTED') {
			return true;
		}
		return false;
	}

	public function read(array $options = array()) {
		$defaults = array(
			'timeout' => 0
		);
		$options += $defaults;
		extract($options, EXTR_OVERWRITE);

		$response = $this->connection->reserve($options);

		if(is_object($response) && $response->status == 'RESERVED') {
			return $this->_message($response, $options);
		}
		return null;
	}

	public function confirm($message, array $options = array()) {
		$response = $this->connection->delete($message->id());

		if(is_object($response) && $response->status == 'DELETED') {
			return true;
		}
		return false;
	}

	public function requeue($message, array $options = array()) {
		$defaults = array(
			'priority' => 0,
			'delay' => 0
		);
		$options += $defaults;
		extract($options, EXTR_OVERWRITE);

		$response = $this->connection->release($message->id(), $priority, $delay);

		if(is_object($response) && $response->status == 'RELEASED') {
			return true;
		}
		return false;
	}

	public function consume($callback, array $options = array()) {
		while($response = $this->connection->reserve()) {
			if($response->id){
				$message = $this->_message($response);
				if($callback($message) === false) {
					break;
				}
			}
		}
		return false;
	}

	public function purge() {
		do {
			$response = $this->connection->reserve(0);
			if($response->id) {
				$this->connection->delete($response->id);
			}
		} while ($response->status == 'RESERVED');

		return true;
	}

	protected function _message($response, array $options = array()) {
		$defaults = array('class' => 'message');
		$options += $defaults;

		$class = $options['class'];
		$params = array(
			'id' => $response->id,
			'queue' => $this,
			'data' => trim($response->data)
		);
		return $this->invokeMethod('_instance', array($class, $params));
	}

}

?>
