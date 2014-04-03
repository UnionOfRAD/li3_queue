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
	 * `AMQPEnvelope` object instance used by this adapter.
	 *
	 * @var object
	 */
	public $envelope = null;

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
		);
		$options += $defaults;

		$routing_key = $config['routingKey'] ?: $config['queue'];

		$exchange = $this->exchange(array('queue' => $config['queue'], 'routingKey' => $config['routingKey']));
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
			'flag' => $config['autoAck']
		);
		$options += $defaults;

		$this->nack();
		$queue = $this->queue();
		$envelope = $queue->get($options['flag']);
		$message = array();

		if($envelope instanceof AMQPEnvelope) {
			$message = array(
				'body' => $envelope->getBody(),
				'isRedelivery' => $envelope->isRedelivery()?:false
			);

			if($options['flag'] != AMQP_AUTOACK) {
				$this->envelope = $envelope;
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
			'flag' => AMQP_NOPARAM
		);
		$options += $defaults;

		if($this->envelope instanceof AMQPEnvelope) {
			$queue = $this->queue();
			$tag = $this->envelope->getDeliveryTag();

			if($queue->ack($tag, $options['flag'])) {
				$this->envelope = null;
				return true;
			}
		}
		return null;
	}

	/**
	 * Unacknowledge a message if it has failed to be processed.
	 *
	 * @return .
	 */
	public function nack($options = array()) {
		$config = $this->_config;
		$defaults = array(
			'flag' => AMQP_REQUEUE
		);
		$options += $defaults;

		if($this->envelope instanceof AMQPEnvelope) {
			$queue = $this->queue();
			$tag = $this->envelope->getDeliveryTag();

			if($queue->nack($tag, $options['flag'])) {
				$this->envelope = null;
				return true;
			}
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
	public function channel() {
		if($this->connection) {
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
	public function exchange($options = array()) {
		$config = $this->_config;
		$defaults = array(
			'type' => AMQP_EX_TYPE_DIRECT,
			'flags' => AMQP_DURABLE
		);
		$options = $options + $defaults;
		$channel = $this->channel();

		if($channel) {
			$exchange = $this->exchange;
			if(!$exchange) {
				$exchange = new AMQPExchange($channel);
				$exchange->setName($config['exchange']);
				$exchange->setType($options['type']);
				$exchange->setFlags($options['flags']);
				$exchange->declareExchange();
				$this->exchange = $exchange;
			}
			$this->queue();
			return $exchange;
		}
		return false;
	}

	/**
	 * Initialize AMQPQueue.
	 *
	 * @return .
	 */
	public function queue($options = array()) {
		$config = $this->_config;
		$defaults = array(
			'flags' => AMQP_DURABLE
		);
		$options = $options + $defaults;
		$channel = $this->channel();

		if($channel) {
			$queue = $this->queue;
			if(!$queue instanceof AMQPQueue) {
				$queue = new AMQPQueue($channel);
				$queue->setName($config['queue']);
				$queue->setFlags($options['flags']);
				$queue->declareQueue();
				if($config['exchange'] && $config['routingKey']) {
					$queue->bind($config['exchange'], $config['routingKey']);
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