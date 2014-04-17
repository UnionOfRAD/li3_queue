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
				extract($options, EXTR_OVERWRITE);
				$cmd = sprintf('put %d %d %d %d', $pri, $delay, $ttr, strlen($data));
				return join("\r\n", array($cmd, $data));
			},
			'reserve' => function($data, array $options = array()) {
				return 'reserve';
			},
			'reserve-with-timeout' => function($data, array $options = array()) {
				extract($options, EXTR_OVERWRITE);
				return sprintf('reserve-with-timeout %d', $timeout);
			},
			'release' => function($id, array $options = array()) {
				extract($options, EXTR_OVERWRITE);
				return sprintf('release %d %d %d', $id, $pri, $delay);
			},
			'delete' => function($id, array $options = array()) {
				return sprintf('delete %d', $id);
			},
			'bury' => function($id, array $options = array()) {
				return sprintf('delete %d %d', $id, $pri);
			},
			'touch' => function($id, array $options = array()) {
				return sprintf('touch %d', $id);
			},
			'watch' => function($tube, array $options = array()) {
				return sprintf('watch %s', $tube);
			},
			'ignore' => function($tube, array $options = array()) {
				return sprintf('ignore %s', $tube);
			},
			'peek' => function($id, array $options = array()) {
				return sprintf('peek %d', $id);
			},
			'peek-ready' => function($data, array $options = array()) {
				return sprintf('peek-ready');
			},
			'peek-delayed' => function($data, array $options = array()) {
				return sprintf('peek-delayed');
			},
			'peek-buried' => function($data, array $options = array()) {
				return sprintf('peek-buried');
			},
			'kick' => function($bound, array $options = array()) {
				return sprintf('kick %d', $bound);
			},
			'kick-job' => function($id, array $options = array()) {
				return sprintf('kick-job %d', $id);
			},
			'stats-job' => function($id, array $options = array()) {
				return sprintf('stats-job %d', $id);
			},
			'stats-tube' => function($tube, array $options = array()) {
				return sprintf('stats-tube %s', $tube);
			},
			'stats' => function($data, array $options = array()) {
				return 'stats';
			},
			'list-tubes' => function($data, array $options = array()) {
				return 'list-tubes';
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