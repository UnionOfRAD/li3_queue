<?php

namespace li3_queue\extensions\adapter\queue;

use AMQPConnection;
use AMQPChannel;
use AMQPExchange;
use AMQPQueue;
use AMQPEnvelope;

class AMQP extends \lithium\core\Object {

	/**
	 * `AMQPConnection` object instance used by this adapter.
	 *
	 * @var object
	 */
	public $connection = null;

	/**
	 * `AMQPChannel` object instance used by this adapter.
	 *
	 * @var object
	 */
	public $channel = null;

	/**
	 * `AMQPExchange` object instance used by this adapter.
	 *
	 * @var object
	 */
	public $exchange = null;

	/**
	 * `AMQPQueue` object instance used by this adapter.
	 *
	 * @var object
	 */
	public $queue = null;

	/**
	 * Messages.
	 *
	 * @var object
	 */
	public $messages = array();

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
		$config = $this->_config;
		$defaults = array(
			'exchange' => $config['exchange'],
			'queue' => $config['queue'],
			'routingKey' => $config['routingKey']
		);
		$options += $defaults;

		$routing_key = $options['routingKey'] ?: $options['queue'];

		$exchange = $this->exchange($options['exchange'], array('queue' => $options['queue'], 'routingKey' => $routing_key));
		return $exchange->publish($data, $routing_key);
	}

	/**
	 * Read value(s) from the queue.
	 *
	 * @return .
	 */
	public function read(array $options = array()) {
		$config = $this->_config;
		$defaults = array(
			'exchange' => $config['exchange'],
			'queue' => $config['queue'],
			'flag' => $config['autoAck']
		);
		$options += $defaults;

		$queue = $this->queue($options['queue']);
		$envelope = $queue->get($options['flag']);
		$message = array();

		if(is_object($envelope)) {

			$message = array(
				'body' => $envelope->getBody(),
				'timestamp' => $envelope->getTimestamp(),
				'expiration' => $envelope->getExpiration(),
				'priority' => $envelope->getPriority(),
				'isRedelivery' => $envelope->isRedelivery()?:0
			);

			if($options['flag'] != AMQP_AUTOACK) {
				$this->messages[$options['queue']] = $envelope->getDeliveryTag();
			}

		}

		return $message;
	}

	/**
	 * Acknowledge a message has been processed.
	 *
	 * @return .
	 */
	public function ack($options = array()) {
		$config = $this->_config;
		$defaults = array(
			'queue' => $config['queue'],
			'flag' => AMQP_NOPARAM
		);
		$options += $defaults;

		if(!empty($this->messages[$options['queue']])) {
			$queue = $this->queue($options['queue']);
			$delivery_tag = $this->messages[$options['queue']];
			unset($this->messages[$options['queue']]);
			return $queue->ack($delivery_tag, $options['flag']);
		}
		return null;
	}

	/**
	 * Acknowledge a message has failed to be processed.
	 *
	 * @return .
	 */
	public function nack($options = array()) {
		$config = $this->_config;
		$defaults = array(
			'queue' => $config['queue'],
			'flag' => AMQP_NOPARAM
		);
		$options += $defaults;

		if(!empty($this->acknowledge[$options['queue']])) {
			$queue = $this->queue($options['queue']);
			$delivery_tag = $this->acknowledge[$options['queue']];
			unset($this->acknowledge[$options['queue']]);
			return $queue->nack($delivery_tag, $options['flag']);
		}
		return null;
	}

	/**
	 * Consume job(s) from the queue.
	 *
	 * @return .
	 */
	public function consume() {
	}

	/**
	 * Initialize AMQPChannel.
	 *
	 * @return .
	 */
	public function channel($connection) {
		if($connection) {
			if(!$this->channel) {
				$this->channel = new AMQPChannel($this->connection);
			}
			return $this->channel;
		}
		return false;
	}

	/**
	 * Initialize AMQPExchange.
	 *
	 * @return .
	 */
	public function exchange($name = 'default', $options = array()) {
		$defaults = array(
			'type' => AMQP_EX_TYPE_DIRECT,
			'flags' => AMQP_DURABLE,
			'queue' => 'default',
			'routingKey' => null
		);
		$options = $options + $defaults;
		$channel = $this->channel($this->connection);

		if($channel) {
			$exchange = $this->exchange;
			if(!$exchange) {
				$exchange = new AMQPExchange($channel);
				$exchange->setName($name);
				$exchange->setType($options['type']);
				$exchange->setFlags($options['flags']);
				$exchange->declareExchange();
				$this->exchange = $exchange;
			}
			$this->queue($options['queue'], array('exchange' => $name, 'routingKey' => $options['routingKey']));
			return $exchange;
		}
		return false;
	}

	/**
	 * Initialize AMQPQueue.
	 *
	 * @return .
	 */
	public function queue($name, $options = array()) {
		$defaults = array(
			'flags' => AMQP_DURABLE,
			'exchange' => null,
			'routingKey' => null
		);
		$options = $options + $defaults;
		$channel = $this->channel($this->connection);

		if($channel) {
			$queue = $this->queue;
			if(!$queue) {
				$queue = new AMQPQueue($channel);
				$queue->setName($name);
				$queue->setFlags($options['flags']);
				$queue->declareQueue();
				if($options['exchange'] && $options['routingKey']) {
					$queue->bind($options['exchange'], $options['routingKey']);
				}
				$this->queue = $queue;
			}
			return $queue;
		}
		return false;
	}

	/**
	 * Purge queue.
	 *
	 * @return .
	 */
	public function purge() {

	}

	protected function _encode($data) {
		return json_encode($data);
	}

	protected function _decode($data) {
		return json_decode($data);
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