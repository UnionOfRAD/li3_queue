<?php

namespace li3_queue\extensions\adapter\queue;

class Gearman extends \li3_queue\extensions\adapter\Queue {
	protected $_autoConfig = array('classes' => 'merge');

	protected $_classes = array(
		'client' => '\GearmanClient',
		'worker' => '\GearmanWorker'
	);

	public function __construct(array $options = array()) {
		$default = array('host' => '127.0.0.1', 'port' => '4730');
		$options += $default;
		return parent::__construct($options);
	}

	/**
	 * @param string $name
	 * @return object
	 */
	public function __get($name) {
		if (isset($this->_classes[$name])) {
			$class = $this->_classes[$name];

			$object = new $class();
			$object->addServer($this->_config['host'], $this->_config['port']);

			$this->$name = $object;
			return $this->$name;
		}

		throw new InvalidArgumentException("Property {$property} doesn't exist");
	}

	/**
	 * @param string $task
	 * @param array $options
	 *              - `'priority'` _string_: Priority of the task
	 *              - `'payload'` _array_: Data to be processed
	 *              - `'unique'` _string_: A unique ID used to identify a particular task
	 * @return boolean
	 */
	public function add($task, array $options = array()) {
		$default = array('priority' => 'normal', 'payload' => '', 'unique' => null);
		$options += $default;

		$payload = json_encode($options['payload']);
		$unique = $options['unique'];
		switch ($options['priority']) {
			case 'high':
				$handle = $this->client->doHighBackground($task, $payload, $unique);
				break;
			case 'low':
				$handle = $this->client->doLowBackground($task, $payload, $unique);
				break;
			case 'normal':
			default:
				$handle = $this->client->doBackground($task, $payload, $unique);
				break;
		}

		return (strlen($handle) != 0);
	}

	/**
	 * Gearman doesn't allow for deleting jobs from the queue.
	 *
	 * @param array $options
	 * @return boolean
	 */
	public function reset(array $options = array()) {
		return false;
	}

	/**
	 * @param array $options
	 *              - `'once'` _string_: Run one task
	 * @return boolean
	 */
	public function run(array $options = array()) {
		$default = array('once' => false);
		$options += $default;

		$tasks = $this->getTask();
		foreach ($tasks as $task) {
			$this->worker->addFunction($task::name(), array($task, 'run'));
		}

		if ($option['once']) {
			return $this->worker->work();
		} else {
			while ($this->worker->work()) {
				if ($worker->returnCode() != GEARMAN_SUCCESS) {
					return false;
				}
			}
		}

		return true;
	}

	public function isQueued($handle) {
		$status = $this->client->jobStatus($handle);
		if ($status[0] && $status[1] == false) {
			return true;
		}

		return false;
	}
}

?>