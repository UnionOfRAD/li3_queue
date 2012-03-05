<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_queue\tests\cases\extensions\adapter\queue;

use li3_queue\storage\Queue;
use li3_queue\extensions\adapter\queue\Beanstalk;

class BeanstalkTest extends \lithium\test\Unit {

	protected $_testConfig = array(
		'adapter' => 'Beanstalk',
		'host' => '127.0.0.1',
		'port' => 11300
	);

	protected $_testTube = '_test';
	protected $_uniqueJob = '_test';

	public function skip() {
		$message = "Beanstalk server is not running.";
		$this->skipIf(!$this->_hasNetwork($this->_testConfig), $message);
	}

	protected function _hasNetwork($config = array()) {
		$socket = fsockopen($config['host'], $config['port']);
		if($socket) fclose($socket);
		return !!$socket;
	}

	public function setUp() {
		$this->_uniqueJob = array('foo' => 'bar', 'time' => time());
		Queue::config(array('default' => $this->_testConfig));
		/*if(!ini_get('safe_mode')) {
            set_time_limit(3);
        }*/
		//Queue::reset(array('tube' => $this->_testTube)); //@todo clean queue before tests (bug: reset is blocking, timeout not working.)
	}

	public function tearDown() {
	}


	public function testConfig() {
		$this->assertTrue((boolean)Queue::getConfig('default'));
	}

	public function testAdd() {
		$result = Queue::add($this->_uniqueJob, array('tube' => $this->_testTube));
		$this->assertTrue(is_numeric($result));
	}

	public function testRun() {
		$result = Queue::run(array('tube' => $this->_testTube));
		$this->assertTrue(is_array($result) && !empty($result['id']));
		//$this->assertEqual($result, $this->_uniqueJob); //@todo
	}

	public function testReset() {
		//@todo
	}

}

?>
