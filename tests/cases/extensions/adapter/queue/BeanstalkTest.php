<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_queue\tests\cases\extensions\adapter\queue;

use li3_queue\extensions\adapter\queue\Beanstalk;
use lithium\core\NetworkException;

class BeanstalkTest extends \lithium\test\Unit {

	public $beanstalk = null;

	protected $_testConfig = array(
		'host' => '127.0.0.1',
		'port' => 11300,
		'autoConnect' => true
	);

	public function skip() {
		$config = $this->_testConfig;

		try {
			$conn = new Beanstalk($config);
		} catch (NetworkException $e) {
			$message  = "A Beanstalk server does not appear to be running on ";
			$message .= $config['host'] . ':' . $config['port'];
			$hasBeanstalk = ($e->getCode() != 503) ? true : false;
			$this->skipIf(!$hasBeanstalk, $message);
		}
		unset($conn);
	}

	public function testInitialize() {
		$beanstalk = new Beanstalk();
		$this->assertInternalType('object', $beanstalk);

		$this->beanstalk = &$beanstalk;
	}

	public function testConnect() {
		$beanstalk = &$this->beanstalk;

		$result = $beanstalk->connect();
		$this->assertTrue($result);
	}

	public function testPurgeQueue() {
		$beanstalk = &$this->beanstalk;

		for($x=0; $x<10; $x++) {
			$beanstalk->write('message_'.$x);
		}

		$result = $beanstalk->purge();
		$this->assertTrue($result);
	}

	public function testWrite() {
		$beanstalk = &$this->beanstalk;

		$result = $beanstalk->write('message');
		$this->assertTrue($result);
	}

	public function testReadWithRequeue() {
		$beanstalk = &$this->beanstalk;

		$expected = 'message';

		$message = $beanstalk->read();

		$this->assertInternalType('object', $message);
		$this->assertEqual($expected, $message->data());

		$result = $message->requeue();
		$this->assertTrue($result);
	}

	public function testReadWithConfirm() {
		$beanstalk = &$this->beanstalk;

		$expected = 'message';

		$message = $beanstalk->read();

		$this->assertInternalType('object', $message);
		$this->assertEqual($expected, $message->data());

		$result = $message->confirm();
		$this->assertTrue($result);
	}

	public function testConsume() {
		$beanstalk = &$this->beanstalk;

		$beanstalk->write('kill_consume');

		$result = $beanstalk->consume(function($msg) {
			$msg->confirm();
			if($msg->data() == 'kill_consume') {
				return false;
			}
		});
		$this->assertFalse($result);
	}

	public function testDisconnect() {
		$beanstalk = &$this->beanstalk;

		$result = $beanstalk->disconnect();
		$this->assertTrue($result);
	}

}

?>
