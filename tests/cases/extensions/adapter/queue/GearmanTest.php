<?php

namespace li3_queue\tests\cases\extensions\adapter\queue;

use li3_queue\extensions\adapter\queue\Gearman;

class GearmanTest extends \lithium\test\Unit {
	protected $_testConfig = array(
		'host' => '127.0.0.1',
		'port' => '4730',
		'classes' => array(
			'client' => '\li3_queue\tests\mocks\extensions\adapter\queue\MockGearmanClient',
			'worker' => '\li3_queue\tests\mocks\extensions\adapter\queue\MockGearmanWorker'
		)
	);

	public function skip() {
		$hasGearman = class_exists('\GearmanClient');
		$this->skipIf(!$hasGearman, 'Skipping because Gearman Library is not installed.');
	}

	public function setUp() {
		$this->handle = 'lithium-tests-' . base_convert(rand(10e16, 10e20), 10, 36);
		$this->queue = new Gearman($this->_testConfig);
	}

	public function tearDown() {
		unset($this->queue);
	}

	public function testAdd() {
		$this->assertTrue($this->queue->add('hello', array('unique' => $this->handle)));
		$this->assertTrue($this->queue->isQueued($this->handle));
	}

	public function testAddWithPayload() {
		$this->assertTrue($this->queue->add('hello', array(
			'payload' => 'Tester',
			'unique' => $this->handle
		)));
		$this->assertTrue($this->queue->isQueued($this->handle));
	}

	public function testAddHigh() {
	}

	public function testAddLow() {
	}

	public function testAddNonExistantTask() {
	}

	public function testRun() {
	}

	public function testRunOnce() {
	}
}

?>