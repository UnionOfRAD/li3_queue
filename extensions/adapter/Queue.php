<?php

namespace li3_queue\extensions\adapter;

abstract class Queue extends \lithium\core\Object {

	/**
	 * The list of object properties to be automatically assigned from configuration passed to
	 * `__construct()`.
	 *
	 * @var array
	 */
	protected $_autoConfig = array('classes' => 'merge');

	/**
	 * Class dependencies.
	 *
	 * @var array
	 */
	protected $_classes = array(
		'message' => 'li3_queue\storage\queue\Message'
	);

	/**
	 * Stores a connection to a remote resource. Usually a queue connection (`resource` type).
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

	/**
	 * Constructor. Sets defaults and returns object.
	 *
	 * Options defined:
	 * - 'autoConnect' `boolean` If true, a connection is made on initialization. Defaults to true.
	 *
	 * @param array $config
	 * @return Source object
	 */
	public function __construct(array $config = array()) {
		$defaults = array('autoConnect' => true);
		parent::__construct($config + $defaults);
	}

	/**
	 * Ensures the connection is closed, before the object is destroyed.
	 *
	 * @return void
	 */
	public function __destruct() {
		if ($this->isConnected()) {
			$this->disconnect();
		}
	}

	protected function _init() {
		parent::_init();
		if ($this->_config['autoConnect']) {
			$this->connect();
		}
	}

	/**
	 * Method for writing messages to the queue.
	 *
	 * @param mixed $data
	 * @return boolean.
	 */
	abstract public function write($data, array $options = array());

	/**
	 * Method for reading messages from the queue.
	 *
	 * @return Message object.
	 */
	abstract public function read();

	/**
	 * Method to confirm a message.
	 *
	 * @return boolean.
	 */
	abstract public function confirm($message);

	/**
	 * Method to requeue a failed message.
	 *
	 * @return boolean.
	 */
	abstract public function requeue($message);

	/**
	 * Blocking method to listen for messages.
	 *
	 * @return void.
	 */
	abstract public function consume($callback, array $options = array());

}

?>