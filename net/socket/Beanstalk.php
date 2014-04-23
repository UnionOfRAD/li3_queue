<?php

namespace li3_queue\net\socket;

class Beanstalk extends \lithium\net\socket\Stream {

	public function read($length = 3600, $ending = "\r\n") {
		if (!is_resource($this->_resource)) {
			return false;
		}
		return stream_get_line($this->_resource, $length, $ending);
	}

}

?>