<?php

namespace li3_queue\tests\mocks\extensions\adapter\queue;

class MockGearmanClient extends \GearmanClient {
	public function doBackground($function_name, $workload, $unique = null) {
		return true;
	}
	public function doHighBackground($function_name, $workload, $unique = null) {
		return true;
	}
	public function doLowBackground($function_name, $workload, $unique = null) {
		return true;
	}
}

?>