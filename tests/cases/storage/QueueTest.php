<?php

namespace li3_queue\tests\cases\storage;

use li3_queue\storage\Queue;
use li3_queue\tests\mocks\extensions\adapter\queue\MockQueue;

class QueueTest extends \lithium\test\Unit {

	public function setUp() {
		Queue::config(array(
			'default' => array('adapter' => new MockQueue())
		));
	}

	public function testWrite() {
		$result = Queue::write('default', 'message');
		$this->assertTrue($result);
	}

	public function testRead() {
		$result = Queue::write('default', 'message');
		$this->assertTrue($result);

		$result = Queue::read('default');
		$this->assertEqual('message', $result['data']);
	}

	public function testConfirm() {
		$result = Queue::confirm('default');
		$this->assertNull($result);
	}

	public function testRequeue() {
		$result = Queue::requeue('default');
		$this->assertNull($result);
	}

	public function testConsume() {
		Queue::write('default', 'message');

		$expected = array(
			'data' => 'message',
			'options' => array()
		);

		Queue::consume('default', function($message) use ($expected) {
			$this->assertEqual($expected, $message);
			return true;
		});
	}

	public function testNoConfig() {
		$result = Queue::write('no-config', 'message');
		$this->assertFalse($result);

		$result = Queue::read('no-config');
		$this->assertFalse($result);

		$result = Queue::confirm('no-config');
		$this->assertFalse($result);

		$result = Queue::requeue('no-config');
		$this->assertFalse($result);

		$result = Queue::consume('no-config', function() {});
		$this->assertFalse($result);
	}

	public function testAdd() {
		$this->assertTrue(Queue::add('test'));
		$this->assertTrue(Queue::add('test', array('payload' => '{}')));
	}

	public function testRun() {
		$this->assertTrue(Queue::add('test'));

		$expected = 'Success';
		$result = Queue::run();
		$this->assertEqual($expected, $result);
		$this->assertFalse(Queue::run());
	}
}

?>
