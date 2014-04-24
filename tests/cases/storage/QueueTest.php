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
		$this->assertInternalType('object', $result);
		$this->assertEqual('message', $result->data());
	}

	public function testConsume() {
		Queue::write('default', 'message');

		Queue::consume('default', function($message) {
			$this->assertEqual('message', $message->data());
			return true;
		});
	}

	public function testPurge() {
		Queue::write('default', 'message');
		Queue::write('default', 'message');

		$result = Queue::purge('default');
		$this->assertTrue($result);
	}

}

?>
