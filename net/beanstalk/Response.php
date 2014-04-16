<?php

namespace li3_queue\net\beanstalk;

class Response extends \lithium\core\Object {

	public $id = null;

	public $status = null;

	public $bytes = null;

	public $data = null;

	protected $_responseTypes = array(
		'/(?<status>OUT_OF_MEMORY)/',
		'/(?<status>INTERNAL_ERROR)/',
		'/(?<status>BAD_FORMAT)/',
		'/(?<status>UNKNOWN_COMMAND)/',
		'/(?<status>INSERTED)\s(?<id>\d+)/',
		'/(?<status>BURIED)\s(?<id>\d+)/',
		'/(?<status>EXPECTED_CRLF)/',
		'/(?<status>JOB_TOO_BIG)/',
		'/(?<status>DRAINING)/',
		'/(?<status>USING)\s(?<tube>.+)/',
		'/(?<status>DEADLINE_SOON)/',
		'/(?<status>TIMED_OUT)/',
		'/(?<status>RESERVED)\s(?<id>\d+)\s(?<bytes>\d+)\r\n(?<data>.*)/',
		'/(?<status>DELETED)/',
		'/(?<status>NOT_FOUND)/',
		'/(?<status>RELEASED)/',
		'/(?<status>TOUCHED)/',
		'/(?<status>WATCHING)\s(?<count>\d+)/',
		'/(?<status>NOT_IGNORED)/',
		'/(?<status>KICKED)\s(?<count>\d+)/',
		'/(?<status>KICKED)/',
		'/(?<status>OK)\s(?<bytes>\d+)\r\n(?<data>.*)/s',
	);

	public function __construct(array $config = array()) {
		parent::__construct($config);

		if($this->_config['message']) {
			$this->data = $this->_parseResponse($this->_config['message']);
		}
	}

	protected function _parseResponse($message) {
		foreach ($this->_responseTypes as $pattern) {
			if(preg_match($pattern, $message, $match)) {
				$this->id = (isset($match['id'])) ? $match['id'] : null ;
				$this->status = (isset($match['status'])) ? $match['status'] : null ;
				$this->tube = (isset($match['tube'])) ? $match['tube'] : null ;
				$this->bytes = (isset($match['bytes'])) ? $match['bytes'] : null ;
				$this->count = (isset($match['count'])) ? $match['count'] : null ;

				if($this->status == "OK" && $this->bytes > 0) {
					$match['data'] = $this->_parseYaml($match['data']);
				}

				return (isset($match['data'])) ? $match['data'] : null ;
			}
		}
		return null;
	}

	protected function _parseYaml($data) {
		if(extension_loaded('yaml')) {
			return yaml_parse($data);
		}
		return $data;
	}

}

?>