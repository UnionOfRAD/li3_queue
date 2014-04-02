<?php

namespace li3_queue\extensions\adapter\queue;

use AMQPConnection;
use AMQPChannel;
use AMQPExchange;

class AMQP extends \lithium\core\Object {

	/**
	 * `AMQPConnection` object instance used by this adapter.
	 *
	 * @var object
	 */
	public $connection = null;

	public function __construct(array $config = array()) {
		$defaults = array(
			'host' => '127.0.0.1',
			'login' => 'guest',
			'password' => 'guest',
			'port' => 5672,
			'vhost' => '/',
			'exchange' => 'li3.default',
			'queue' => 'li3.default',
			'routingKey' => null,
			'autoAck' => 0,
			'minMessages' => 0,
			'maxMessages' => 1,
			'prefetchCount' => 3,
			'autoConnect' => 1
		);
		parent::__construct($config + $defaults);
	}

	/**
	 * Handles the actual `AMQP` connection and server connection adding for the adapter
	 * constructor.
	 *
	 * @return void
	 */
	protected function _init() {
		parent::_init();
		if($this->_config['autoConnect']) {
			$this->connect();
		}
	}

	/**
	 * Connect to the queue.
	 *
	 * @return .
	 */
	public function connect() {
		if(!$this->connection) {
			$this->connection = new AMQPConnection($this->_config);
			$this->connection->connect();
		}
		return $this->isConnected();
	}

	/**
	 * Check if the the queue is connected.
	 *
	 * @return .
	 */
	public function isConnected() {
		return $this->connection->isConnected();
	}

	/**
	 * Disconnect from an AMQP server
	 *
	 * @return .
	 */
	public function disconnect() {
		return $this->connection->disconnect();
	}

	/**
	 * Write value(s) to the queue.
	 *
	 * @return .
	 */
	public function write($data, array $options = array()) {
	}

	/**
	 * Read value(s) from the queue.
	 *
	 * @return .
	 */
	public function read() {
	}

	/**
	 * Consume job(s) from the queue.
	 *
	 * @return .
	 */
	public function consume() {
	}

	/**
	 * Determines if the `AMQP` extension has been installed.
	 *
	 * @return boolean Returns `true` if the `AMQP` extension is installed and enabled, `false`
	 *         otherwise.
	 */
	public static function enabled() {
		return extension_loaded('amqp');
	}

}

?>