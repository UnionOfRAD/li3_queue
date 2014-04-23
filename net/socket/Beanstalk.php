<?php

namespace li3_queue\net\socket;

class Beanstalk extends \lithium\net\socket\Stream {

	public function open(array $options = array()) {
		$resource = parent::open($options);
		stream_set_blocking($resource, 0);
		return $resource;
	}

}

?>