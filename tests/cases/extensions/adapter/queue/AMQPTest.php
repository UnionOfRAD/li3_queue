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

	public function testConnect() {
		$amqp = new AMQP($this->_testConfig);
		$this->assertInternalType('object', $amqp);

		$result = $amqp->connect();
		$this->assertTrue($result);

		$this->amqp = &$amqp;
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

		$expected = 'message';

		$message = $amqp->read();

		$this->assertInternalType('object', $message);
		$this->assertEqual($expected, $message->data());

		$result = $message->confirm();
		$this->assertTrue($result);
	}

	public function testReadWithRequeue() {
		$amqp = &$this->amqp;

		$amqp->write('message');
		$message = $amqp->read();

		$result = $message->requeue();
		$this->assertTrue($result);
	}

	public function testReadRedelivery() {
		$amqp = &$this->amqp;

		$message = $amqp->read();

		$this->assertTrue($message->isRedelivery());

		$message->confirm();
	}

	public function testReadWithEmptyQueue() {
		$amqp = &$this->amqp;

		$message = $amqp->read();
		$this->assertNull($message);
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