<?php

namespace li3_queue;

use lithium\core\ConfigException;

class Queue extends \lithium\core\Adaptable {
	/**
	 * Libraries::locate() compatible path to adapters for this class.
	 *
	 * @see lithium\core\Libraries::locate()
	 * @var string Dot-delimited path.
	 */
	protected static $_adapters = 'adapter.queue';

	/**
	 * @param string $task task name to queue
	 * @param array $options Extra options
	 * @return mixed Returned value by adapter's add() method
	 */
	public static function add($task, array $options = array()) {
		$defaults = array('config' => 'default', 'payload' => '');
		$options += $defaults;
		$params   = compact('task', 'options');

		return static::_filter(__FUNCTION__, $params, function($self, $params) {
			$task     = $params['task'];
			$options = $params['options'];

			return $self::adapter($options['config'])->add($task, $options);
		});
	}

	/**
	 * Gets the given config, checking for validity
	 *
	 * @param string $name Configuration name
	 * @return array Configuration
	 */
	public static function getConfig($name) {
		if (($config = static::_config($name)) === null) {
			throw new ConfigException("Configuration {$config} has not been defined.");
		} elseif (!is_array($config)) {
			throw new ConfigException('Invalid configuration: not an array');
		}
		return $config;
	}

	public static function reset(array $options = array()) {
		$defaults = array('config' => 'default');
		$options += $defaults;
		$params   = compact('options');

		return static::_filter(__FUNCTION__, $params, function($self, $params) {
			$options = $params['options'];

			return $self::adapter($options['config'])->reset($options);
		});
	}

	/**
	 * @param array $options
	 * @return mixed Returned value by adapter's run method
	 */
	public static function run(array $options = array()) {
		$defaults = array('config' => 'default');
		$options += $defaults;
		$params   = compact('options');

		return static::_filter(__FUNCTION__, $params, function($self, $params) {
			$options = $params['options'];

			return $self::adapter($options['config'])->run($options);
		});
	}
}

?>