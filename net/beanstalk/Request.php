<?php

namespace li3_queue\net\beanstalk;

class Request extends \lithium\core\Object {

	/**
	 * The method of the request, typically one of the following: `CHOOSE`, `PUT`, `RESERVE`.
	 *
	 * @var string
	 */
	protected $_method = null;

	protected $_options = array();

	protected $_data = null;

	protected $_autoConfig = array(
		'method',
		'data',
		'options'
	);

	/**
	 * Array of closures that return various request strings for Beanstalk.
	 *
	 * @var array
	 */

	protected $_requestTypes = array();

	public function __construct(array $config = array()) {
		$defaults = array(
			'method' => null,
			'data' => null,
			'options' => array()
		);

		$this->_requestTypes = array(
			'use' => function($tube, $options) {
				return sprintf('use %s', $tube);
			},
			'put' => function($data, $options) {
				$defaults = array(
					'pri' => 0,
					'delay' => 0,
					'ttl' => 0
				);
				$options += $defaults;
				extract($options, EXTR_SKIP);

				$bytes = strlen($data);

				$command = sprintf('put %d %d %d %d', $pri, $delay, $ttl, $bytes);
				return join("\r\n", array($command, $data));
			},
			'reserve' => function($data, $options) {
				return 'reserve';
			},
			'reserve-with-timeout' => function($data, $options) {
				$defaults = array(
					'timeout' => 0
				);
				$options += $defaults;
				extract($options, EXTR_SKIP);

				return sprintf('reserve-with-timeout %d', $timeout);
			},
			'release' => function($id, $options) {
				$defaults = array(
					'pri' => 0,
					'delay' => 0
				);
				$options += $defaults;
				extract($options, EXTR_SKIP);

				return sprintf('release %d %d %d', $id, $pri, $delay);
			},
			'delete' => function($id, $options) {
				return sprintf('delete %d', $id);
			},
			'list-tubes' => function($data, $options) {
				return 'list-tubes';
			},
			'stats' => function($data, $options) {
				return 'stats';
			}
		);

		return parent::__construct($config + $defaults);
	}

	public function body() {
		$method = $this->_method;
		$handlers = $this->_requestTypes;
		$handler = isset($handlers[$method]) ? $handlers[$method] : null ;
		return (string) $handler($this->_data, $this->_options);
	}

	/**
	 * Magic method to convert object to string.
	 * Finishes request with CR-LF pair
	 *
	 * @return string
	 */
	public function __toString() {
		return (string) $this->body() . "\r\n";
	}

}

?>