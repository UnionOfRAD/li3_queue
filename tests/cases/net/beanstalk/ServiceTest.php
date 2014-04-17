<?php

namespace li3_queue\tests\cases\net\beanstalk;

use li3_queue\net\beanstalk\Service;

class ServiceTest extends \lithium\test\Unit {

	public $service = null;

	public function testConnection() {
		$service = new Service();
		$result = $service->connect();
		$this->assertTrue($result);

		$this->service = &$service;
	}

	public function testChoose() {
		$service = &$this->service;

		$result = $service->choose('default');
		$this->assertEqual('USING', $result->status);
	}

	public function testReserveTimedOut() {
		$service = &$this->service;

		$result = $service->reserve(0);
		$this->assertEqual('TIMED_OUT', $result->status);
	}

	public function testPut() {
		$service = &$this->service;

		$result = $service->put('message', 0, 0, 0);
		$this->assertEqual('INSERTED', $result->status);
	}

	public function testReserveAndRelease() {
		$service = &$this->service;

		$result = $service->reserve(0);
		$this->assertEqual('RESERVED', $result->status);

		$result = $service->release($result->id, 0, 0);
		$this->assertEqual('RELEASED', $result->status);
	}

	public function testReserveAndDelete() {
		$service = &$this->service;

		$result = $service->reserve(0);
		$this->assertEqual('RESERVED', $result->status);

		$result = $service->delete($result->id);
		$this->assertEqual('DELETED', $result->status);
	}

	public function testListTubes() {
		$service = &$this->service;

		$result = $service->listTubes();
		$this->assertEqual('OK', $result->status);
	}

	public function testStats() {
		$service = &$this->service;

		$result = $service->stats();
		$this->assertEqual('OK', $result->status);
	}

}

?>