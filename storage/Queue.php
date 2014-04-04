<?php

namespace li3_queue\storage;

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
	 * Re-define configurations to avoid overwrite configurations of other adapters
	 * @var array the configurations
	 */
	protected static $_configurations = array();

	/**
	 * Reads from the specified queue configuration
	 *
	 * @param string $name Configuration to be used for reading
	 * @param mixed $data Data to be queued
	 * @param mixed $options Options for the method
	 * @return boolean True on successful cache write, false otherwise
	 */
	public static function write($name, $data, array $options = array()) {
		$settings = static::config();

		if(!isset($settings[$name])) {
			return false;
		}

		$method = static::adapter($name)->write($data, $options);
		return $method;
	}

	/**
	 * Reads from the specified queue configuration
	 *
	 * @param string $name Configuration to be used for reading
	 * @param mixed $options Options for the method
	 * @return mixed Read results on successful queue read, null otherwise
	 */
	public static function read($name, array $options = array()) {
		$settings = static::config();

		if(!isset($settings[$name])) {
			return false;
		}

		$method = static::adapter($name)->read($options);
		return $method;
	}

	/**
	 * Consume from the specified queue configuration
	 *
	 * @param string $name Configuration to be used for consuming
	 * @return non returning
	 */
	public static function consume($name, $callback, array $options = array()) {
		$settings = static::config();

		if(!isset($settings[$name])) {
			return false;
		}

		$method = static::adapter($name)->consume($callback, $options);
		return $method;
	}

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