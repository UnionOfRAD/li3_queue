<?php

namespace li3_queue\tests\cases\net\beanstalk;

use li3_queue\net\beanstalk\Service;

class ServiceTest extends \lithium\test\Unit {

	public function testInit() {
		$service = new Service();
		$conn = $service->connect();

		$result = $service->choose();
		var_dump($result);

		$result = $service->put("message");
		var_dump($result);

		//$result = $service->reserve();
	}

}

?>