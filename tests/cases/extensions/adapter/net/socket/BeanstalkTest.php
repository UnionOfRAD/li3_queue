<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_queue\tests\cases\extensions\adapter\net\socket;

use li3_queue\extensions\adapter\net\socket\Beanstalk as BeanstalkSocket;

class BeanstalkTest extends \lithium\test\Unit {

	protected $_testConfig = array(
		'host' => '127.0.0.1',
		'port' => 11300
	);

	protected $_testTube = 'test_tube';

	public function skip() {
		$message = "Beanstalk server is not running.";
		$this->skipIf(!$this->_hasNetwork($this->_testConfig), $message);
	}

	public function testAllMethodsNoConnection() {
		$socketOptions = array('scheme' => null, 'host' => $this->_testConfig['host'], 'port' => $this->_testConfig['port']);
		$stream = new BeanstalkSocket($socketOptions);
		$this->assertFalse($stream->open());
		$this->assertTrue($stream->close());
		$this->assertFalse($stream->timeout(2));
		$this->assertFalse($stream->encoding('UTF-8'));
		$this->assertFalse($stream->write(null));
		$this->assertFalse($stream->read());
		$this->assertTrue($stream->eof());
	}

	protected function _hasNetwork($config = array()) {
		$socket = fsockopen($config['host'], $config['port']);
		if($socket) fclose($socket);
		return !!$socket;
	}

	public function testOpen() {
		$stream = new BeanstalkSocket($this->_testConfig);
		$result = $stream->open();
		$this->assertTrue($result);

		$result = $stream->resource();
		$this->assertTrue(is_resource($result));
	}

	public function testClose() {
		$stream = new BeanstalkSocket($this->_testConfig);
		$result = $stream->open();
		$this->assertTrue($result);

		$result = $stream->close();
		$this->assertTrue($result);

		$result = $stream->resource();
		$this->assertFalse(is_resource($result));
	}

	public function testTimeout() {
		$stream = new BeanstalkSocket($this->_testConfig);
		$result = $stream->open();
		$stream->timeout(10);
		$result = $stream->resource();
		$this->assertTrue(is_resource($result));
	}

	public function testWriteAndRead() {
		$stream = new BeanstalkSocket($this->_testConfig);
		$this->assertTrue(is_resource($stream->open()));
		$this->assertTrue(is_resource($stream->resource()));

		$result = $stream->write();
		$this->assertEqual(2, $result);
		$this->assertPattern("/^UNKNOWN_COMMAND/", (string) $stream->read());
	}

	public function testChoose() {
		$stream = new BeanstalkSocket($this->_testConfig);
		$this->assertTrue(is_resource($stream->open()));

		$result = $stream->choose($this->_testTube);
		$this->assertTrue($result);
	}

	public function testWatch() {
		$stream = new BeanstalkSocket($this->_testConfig);
		$this->assertTrue(is_resource($stream->open()));

		$result = $stream->watch($this->_testTube);
		$this->assertTrue(is_numeric($result));
	}

	public function testIgnore() {
		$stream = new BeanstalkSocket($this->_testConfig);
		$this->assertTrue(is_resource($stream->open()));

		$stream->watch($this->_testTube);
		$result = $stream->ignore($this->_testTube);
		$this->assertTrue($result);
	}

	public function testPut() {
		$stream = new BeanstalkSocket($this->_testConfig);
		$this->assertTrue(is_resource($stream->open()));

		$result = $stream->choose($this->_testTube);
		$result = $stream->put(1, 0, 60, 'foo');
		$this->assertTrue(is_numeric($result));
	}

	public function testReserve() {
		$stream = new BeanstalkSocket($this->_testConfig);
		$this->assertTrue(is_resource($stream->open()));

		$tubeId = '_test_' . time();
		$stream->choose($tubeId);
		$jobId = $stream->put(1, 0, 60, 'foo');
		$stream->watch($tubeId);
		$stream->ignore('default');
		$result = $stream->reserve();
		$this->assertTrue(is_array($result) && !empty($result['id']));
		$this->assertTrue($result['id'] === $jobId);
	}

	public function testPeek() {
		$stream = new BeanstalkSocket($this->_testConfig);
		$this->assertTrue(is_resource($stream->open()));

		$stream->choose($this->_testTube);
		$jobId = $stream->put(1, 0, 60, 'foo');
		$result = $stream->peek($jobId);
		$this->assertTrue(is_array($result) && !empty($result['id']));
		$this->assertTrue($result['id'] === $jobId);
	}

	public function testRelease() {
		$stream = new BeanstalkSocket($this->_testConfig);
		$this->assertTrue(is_resource($stream->open()));

		$stream->choose($this->_testTube);
		$jobId = $stream->put(1, 0, 60, 'foo');
		$stream->watch($this->_testTube);
		$job = $stream->reserve();
		$result = $stream->release($job['id']);
		$this->assertTrue($result);
	}

	public function testDelete() {
		$stream = new BeanstalkSocket($this->_testConfig);
		$this->assertTrue(is_resource($stream->open()));

		$stream->choose($this->_testTube);
		$jobId = $stream->put(1, 0, 60, 'foo');
		$result = $stream->delete($jobId);
		$this->assertTrue($result);
	}

	public function testBury() {
		$stream = new BeanstalkSocket($this->_testConfig);
		$this->assertTrue(is_resource($stream->open()));

		$stream->choose($this->_testTube);
		$jobId = $stream->put(1, 0, 60, 'foo');
		$stream->watch($this->_testTube);
		$job = $stream->reserve();
		$result = $stream->bury($job['id']);
		$this->assertTrue($result);
	}

	/*public function testTouch() {
		$stream = new BeanstalkSocket($this->_testConfig);
		$this->assertTrue(is_resource($stream->open()));

		//@todo
	}*/

}

?>
