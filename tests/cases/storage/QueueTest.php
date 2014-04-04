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

	public function testWriteNoConfig() {
		$result = Queue::write('no-config', 'message');
		$this->assertFalse($result);
	}

	public function testReadNoConfig() {
		$result = Queue::read('no-config');
		$this->assertFalse($result);
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
