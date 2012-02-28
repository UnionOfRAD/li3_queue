<?php

namespace li3_queue\extensions\command;

/**
 * The Queue command lets you interact with background tasks
 */
class Queue extends \lithium\console\Command {
	/**
	 * Queues a task
	 *
	 * @see li3_queue\Queue::run()
	 * @param string $task The name of the task
	 * @param string $payload JSON Serialized string of the workload
	 * @param string $config The configuration to use
	 * @return boolean
	 */
	public function add($task, $payload = '', $config = 'default') {
		return li3_queue\Queue::add($task, compact('payload', 'config'));
	}

	/**
	 * Performs a task
	 *
	 * @see li3_queue\Queue::run()
	 * @param string $config The configuration to use
	 * @return mixed The return value of the task
	 */
	public function run($config = 'default') {
		return li3_queue\Queue::run(compact('config'));
	}
}

?>