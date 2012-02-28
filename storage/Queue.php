<?php

namespace li3_queue\storage;

abstract class Queue extends \lithium\core\Object {
	abstract public function add($job, array $options = array());

	abstract public function reset(array $options = array());

	abstract public function run(array $options = array());
}

?>