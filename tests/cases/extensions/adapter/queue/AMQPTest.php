<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_queue\tests\cases\extensions\adapter\queue;

use AMQPConnection;
use AMQPConnectionException;
use li3_queue\extensions\adapter\queue\AMQP;

class AMQPTest extends \lithium\test\Unit {

	protected $_conn = null;

	protected $_testConfig = array(
		'host' => '127.0.0.1',
		'login' => 'guest',
		'password' => 'guest',
		'port' => 5672
	);

	public function skip() {
		$this->skipIf(!AMQP::enabled(), 'The `AMQP` adapter is not enabled.');

		$config = $this->_testConfig;
		$conn = new AMQPConnection($config);

		try {
			$result = $conn->connect();
		} catch (AMQPConnectionException $e) {
			$message  = "An AMQP server does not appear to be running on ";
			$message .= $config['host'] . ':' . $config['port'] . " with user `";
			$message .= $config['login'] . "` and password `" . $config['password'] . "`";
			$this->skipIf(!$e->getCode(), $message);
		}
		unset($conn);
	}

	public function testEnabled() {
		$this->assertTrue(AMQP::enabled());
	}

	public function testInit() {
		$amqp = new AMQP(array('host' => 'localhost'));
		$this->assertInternalType('object', $amqp);

		$expected = 'localhost';
		$result = $amqp->_config['host'];
		$this->assertEqual($expected, $result);

		$this->amqp = new AMQP();
	}

	public function testConnect() {
		$amqp = &$this->amqp;

		$result = $amqp->connect();
		$this->assertTrue($result);
	}

	public function testPurgeQueue() {
		$amqp = &$this->amqp;

		for($x=0; $x<10; $x++) {
			$amqp->write('message_'.$x);
		}

		$result = $amqp->purge();
		$this->assertTrue($result);
	}

	public function testWrite() {
		$amqp = &$this->amqp;

		$result = $amqp->write('message');
		$this->assertTrue($result);
	}

	public function testReadWithConfirm() {
		$amqp = &$this->amqp;

		$expected = array(
			'body' => 'message',
			'isRedelivery' => false
		);

		$result = $amqp->read();
		$this->assertEqual($expected, $result);

		$result = $amqp->confirm();
		$this->assertTrue($result);
	}

	public function testReadWithRequeue() {
		$amqp = &$this->amqp;

		$amqp->write('message');
		$amqp->read();

		$result = $amqp->requeue();
		$this->assertTrue($result);
	}

	public function testReadRedelivery() {
		$amqp = &$this->amqp;

		$result = $amqp->read();
		$expected = array(
			'body' => 'message',
			'isRedelivery' => true
		);
		$this->assertEqual($expected, $result);

		$amqp->confirm();
	}

	public function testAckWithoutMessage() {
		$result = $this->amqp->ack();
		$this->assertNull($result);
	}

	public function testNackWithoutMessage() {
		$result = $this->amqp->nack();
		$this->assertNull($result);
	}

	public function testConsume() {
		$amqp = &$this->amqp;

		$amqp->write('message');

		$result = $amqp->consume(function($m) {
			return false;
		}, array('return' => true));

		$result = $amqp->consume(function($m) {
			return true;
		}, array('return' => true));
	}

	public function testDisconnect() {
		$amqp = $this->amqp;

		$result = $amqp->disconnect();
		$this->assertTrue($result);
	}

}

?>