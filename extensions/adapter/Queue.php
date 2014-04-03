<?php

namespace li3_queue\extensions\adapter;

abstract class Queue extends \lithium\core\Object {

	abstract public function add($task, array $options = array());

	abstract public function reset(array $options = array());

	abstract public function run(array $options = array());

}

?>