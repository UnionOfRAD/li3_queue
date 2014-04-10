<?php

namespace li3_queue\extensions\adapter\queue;

use AMQPConnection;
use AMQPChannel;
use AMQPExchange;
use AMQPQueue;
use AMQPEnvelope;
use lithium\core\NetworkException;

class AMQP extends \li3_queue\extensions\adapter\Queue {

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
	 * Adds config values to the public properties when a new object is created.
	 *
	 * @param array $config Configuration options : default value
	 *        - `'host'` _string_: '127.0.0.1'
	 *        - `'login'` _string_: 'guest'
	 *        - `'password'` _string_: 'guest'
	 *        - `'port'` _interger_: 5672
	 *        - `'vhost'` _string_: '/'
	 *        - `'exchange'` _string_: 'li3.default'
	 *        - `'queue'` _string_: 'li3.default'
	 *        - `'routingKey'` _mixed_: null
	 *        - `'autoAck'` _mixed_: 0
	 *        - `'minMessages'` _interger_: 0
	 *        - `'maxMessages'` _interger_: 1
	 *        - `'prefetchCount'` _interger_: 3
	 *        - `'autoConnect'` _interger_: 1
	 */
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
			'autoAck' => false,
			'minMessages' => 0,
			'maxMessages' => 1,
			'prefetchCount' => 3,
			'autoConnect' => 1
		);
		parent::__construct($config + $defaults);
	}

	/**
	 * Connect to the queue.
	 *
	 * @return .
	 */
	public function connect() {
		if(!$this->connection) {
			$this->connection = new AMQPConnection($this->_config);
			try {
				if($this->connection->connect()) {
					$this->_isConnected = true;
				}
			} catch (AMQPConnectionException $e) {
				throw new NetworkException("Could not connect to AMQP server.", 503, $e);
			}
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
		$config = &$this->_config;

		$defaults = array('class' => 'message');
		$options += $defaults;

		$class = $options['class'];
		$params = array('data' => $data) + $options;

		$message = $this->invokeMethod('_instance', array($class, $params));
		return $this->_publish($message);
	}

	/**
	 * Read value(s) from the queue.
	 *
	 * @return .
	 */
	public function read(array $options = array()) {
		$queue = &$this->queue;
		$envelope = $this->_get($options);

		if($envelope) {
			return $this->_message($envelope, $options);
		}
		return null;
	}

	/**
	 * Confirm message have been acknowledged.
	 *
	 * @return boolean.
	 */
	public function confirm($message, array $options = array()) {
		$queue = $this->_queue();
		$defaults = array(
			'flag' => AMQP_NOPARAM
		);
		$options += $defaults;

		return $queue->ack($message->id(), $options['flag']);
	}

	/**
	 * Requeue message for further processing.
	 *
	 * @return boolean.
	 */
	public function requeue($message, array $options = array()) {
		$queue = $this->_queue();
		$defaults = array(
			'flag' => AMQP_REQUEUE
		);
		$options += $defaults;

		return $queue->nack($message->id(), $options['flag']);
	}

	/**
	 * Consume job(s) from the queue.
	 *
	 * @return .
	 */
	public function consume($callback, array $options = array()) {
		$config = $this->_config;
		$queue = $this->_queue();

		$defaults = array(
			'flag' => AMQP_NOPARAM,
			'return' => false
		);
		$options += $defaults;

		return $queue->consume(function($envelope, $queue) use ($callback, &$options) {
			$message = $this->_message($envelope);

			if($result = $callback($message)) {
				$message->confirm();
			} else {
				$message->requeue();
			}

			if($options['return']) {
				return false;
			}
		}, $options['flag']);
	}

	/**
	 * Purge queue.
	 *
	 * @return .
	 */
	public function purge() {
		$queue = $this->_queue();
		return $queue->purge();
	}

	/**
	 * Initialize AMQPChannel.
	 *
	 * @return .
	 */
	protected function &_channel() {
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
	protected function &_exchange($options = array()) {
		$config = $this->_config;
		$defaults = array(
			'type' => AMQP_EX_TYPE_DIRECT,
			'flags' => AMQP_DURABLE
		);
		$options = $options + $defaults;
		$channel = $this->_channel();

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
			$this->_queue();
			return $exchange;
		}
		return false;
	}

	/**
	 * Initialize AMQPQueue.
	 *
	 * @return .
	 */
	protected function &_queue($options = array()) {
		$config = $this->_config;
		$defaults = array(
			'flags' => AMQP_DURABLE
		);
		$options = $options + $defaults;
		$channel = $this->_channel();

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
	 * Get message from the queue.
	 *
	 * @return .
	 */
	protected function _get(array $options = array()) {
		$config = $this->_config;
		$queue = $this->_queue();

		$defaults = array(
			'flag' => ($config['autoAck']) ? AMQP_AUTOACK : 0
		);
		$options = $options + $defaults;

		return $queue->get($options['flag']);
	}

	/**
	 * Publish message to the queue.
	 *
	 * @return .
	 */
	protected function _publish($message, array $options = array()) {
		$config = $this->_config;
		$defaults = array(
			'flags' => AMQP_NOPARAM,
			'attributes' => array(
				'content_type' => $message->contentType(),
				'content_encoding' => null,
				'message_id' => null,
				'user_id' => null,
				'app_id' => null,
				'delivery_mode' => 2,
				'priority' => $message->priority(),
				'timestamp' => null,
				'expiration' => null,
				'type' => null,
				'reply_to' => null,
			)
		);
		$options += $defaults;

		$routing_key = $config['routingKey'] ?: $config['queue'];

		$exchange = $this->_exchange(array(
			'queue' => $config['queue'],
			'routingKey' => $config['routingKey']
		));

		return $exchange->publish($message->data(), $routing_key, $options['flags'], $options['attributes']);
	}

	/**
	 * Converts AMQPEnvelope into Message object.
	 *
	 * @return object.
	 */
	protected function _message($envelope, array $options = array()) {
		$defaults = array('class' => 'message');
		$options += $defaults;

		$class = $options['class'];
		$params = array(
			'id' => $envelope->getDeliveryTag(),
			'queue' => $this,
			'data' => $envelope->getBody(),
			'priority' => $envelope->getPriority(),
			'redelivery' => $envelope->isRedelivery()
		);
		return $this->invokeMethod('_instance', array($class, $params));
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