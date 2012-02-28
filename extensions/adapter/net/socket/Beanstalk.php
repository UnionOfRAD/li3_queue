<?php
/**
 * li3_queue: queue plugin for the lithium framework
 *
 * @copyright     Copyright 2012, Olivier Louvignes for Union of RAD (http://union-of-rad.org)
 * @copyright     Inspired by David Persson's Queue plugin for CakePHP.
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 *
 */

namespace li3_queue\extensions\adapter\net\socket;

use \lithium\net\socket\Stream;

/**
 * A Curl-based socket adapter
 *
 * This curl adapter provides the required method implementations of the abstract Socket class
 * for `open`, `close`, `read`, `write`, `timeout` `eof` and `encoding`.
 *
 * Your PHP installation must have been compiled with the `--with-curl[=DIR]` directive. If this
 * is not the case, you must either recompile PHP with the proper configuration flags to enable
 * curl, or you may use the `Stream` adapter that is also included with the Lithium core.
 *
 * @link http://www.php.net/manual/en/curl.installation.php
 * @see lithium\net\socket\Stream
 */
class Beanstalk extends \lithium\net\socket\Stream {

	const DEFAULT_HOST = '127.0.0.1';
	const DEFAULT_PORT = 11300;

	/**
	 * Reads data from the curl connection.
	 * The `read` method will utilize the curl options that have been set.
	 *
	 * @link http://php.net/manual/en/function.curl-exec.php PHP Manual: curl_exec()
	 * @return mixed Boolean false if the resource handle is unavailable, and the result
	 *         of `curl_exec` otherwise.
	 */
	public function read($length = null) {
		if (!is_resource($this->_resource)) {
			return false;
		}
		/*if (!$length) {
			return stream_get_contents($this->_resource);
		}
		return stream_get_contents($this->_resource, $length, $offset);*/
		if ($length) {
			if (feof($this->_resource)) {
				return false;
			}
			$data = fread($this->_resource, $length + 2);
			$meta = stream_get_meta_data($this->_resource);

			if ($meta['timed_out']) {
				$this->_errors[] = 'Connection timed out.';
				return false;
			}
			$packet = rtrim($data, "\r\n");
		} else {
			$packet = stream_get_line($this->_resource, 16384, "\r\n");
		}
		return $packet;
	}

	/**
	 * Writes data to curl options
	 *
	 * @param object $data a `lithium\net\Message` object or array
	 * @return boolean
	 */
	public function write($data = null) {
		if (!is_resource($this->_resource)) {
			return false;
		}
		$data .= "\r\n";
		return fwrite($this->_resource, (string) $data, strlen((string) $data));
	}


	/* Producer Commands */

	/**
	 * The "put" command is for any process that wants to insert a job into the queue.
	 *
	 * @param integer $priority Jobs with smaller priority values will be scheduled
	 *                     		before jobs with larger priorities.
	 *                     		The most urgent priority is 0; the least urgent priority is 4294967295.
	 * @param integer $delay 	Seconds to wait before putting the job in the ready queue.
	 *                       	The job will be in the "delayed" state during this time.
	 * @param integer $timeout 	Time to run - Number of seconds to allow a worker to run this job.
	 *                     		The minimum ttr is 1.
	 * @param string $data The job body
	 * @return integer|boolean False on error otherwise and integer indicating the job id
	 */
	public function put($priority, $delay, $timeout, string $data) {
		$this->write(sprintf('put %d %d %d %d', $priority, $delay, $timeout, strlen($data)));
		$this->write($data);
		$status = strtok($this->read(), ' ');

		switch ($status) {
			case 'INSERTED':
			case 'BURIED':
				return (integer)strtok(' ');
			case 'EXPECTED_CRLF':
			case 'JOB_TOO_BIG':
			default:
				$this->_errors[] = $status;
				return false;
		}
	}

	/**
	 * The "use" command is for producers. Subsequent put commands will put jobs into
	 * the tube specified by this command. If no use command has been issued, jobs
	 * will be put into the tube named "default".
	 *
	 * @param string $tube A name at most 200 bytes. It specifies the tube to use.
	 *                     If the tube does not exist, it will be created.
	 * @return string|boolean False on error otherwise the tube
	 */
	public function choose($tube) {
		$this->write(sprintf('use %s', $tube));
		$status = strtok($this->read(), ' ');

		switch ($status) {
			case 'USING':
				return strtok(' ') === $tube;
			default:
				$this->_errors[] = $status;
				return false;
		}
	}

	/**
	 * Alias for choose
	 */
	public function useTube($tube) {
		return $this->choose($tube);
	}

	/* Worker Commands */

	/**
	 * Reserve a job (with a timeout)
	 *
	 * @param integer $timeout If given specifies number of seconds to wait for a job. 0 returns immediately.
	 * @return array|false False on error otherwise an array holding job id and body
	 */
	public function reserve($timeout = null) {
		if (isset($timeout)) {
			$this->write(sprintf('reserve-with-timeout %d', $timeout));
		} else {
			$this->write('reserve');
		}
		$status = strtok($this->read(), ' ');

		switch ($status) {
			case 'RESERVED':
				return array(
					'id' => (integer)strtok(' '),
					'body' => $this->read((integer)strtok(' '))
				);
			case 'DEADLINE_SOON':
			case 'TIMED_OUT':
			default:
				$this->_errors[] = $status;
				return false;
		}
	}

	/**
	 * Removes a job from the server entirely
	 *
	 * @param integer $id The id of the job
	 * @return boolean False on error, true on success
	 */
	public function delete($id) {
		$this->write(sprintf('delete %d', $id));
		$status = $this->read();

		switch ($status) {
			case 'DELETED':
				return true;
			case 'NOT_FOUND':
			default:
				$this->_errors[] = $status;
				return false;
		}
	}

	/**
	 * Puts a reserved job back into the ready queue
	 *
	 * @param integer $id The id of the job
	 * @param integer $pri Priority to assign to the job
	 * @param integer $delay Number of seconds to wait before putting the job in the ready queue
	 * @return boolean False on error, true on success
	 */
	public function release($id, $pri, $delay) {
		$this->write(sprintf('release %d %d %d', $id, $pri, $delay));
		$status = $this->read();

		switch ($status) {
			case 'RELEASED':
			case 'BURIED':
				return true;
			case 'NOT_FOUND':
			default:
				$this->_errors[] = $status;
				return false;
		}
	}

	/**
	 * Puts a job into the "buried" state
	 *
	 * Buried jobs are put into a FIFO linked list and will not be touched
	 * until a client kicks them.
	 *
	 * @param mixed $id
	 * @param mixed $pri
	 * @return boolean False on error and true on success
	 */
	public function bury($id, $pri) {
		$this->write(sprintf('bury %d %d', $id, $pri));
		$status = $this->read();

		switch ($status) {
			case 'BURIED':
				return true;
			case 'NOT_FOUND':
			default:
				$this->_errors[] = $status;
				return false;
		}
	}

	/**
	 * Allows a worker to request more time to work on a job
	 *
	 * @param integer $id The id of the job
	 * @return boolean False on error and true on success
	 */
	public function touch($id) {
		$this->write(sprintf('touch %d', $id));
		$status = $this->read();

		switch ($status) {
			case 'TOUCHED':
				return true;
			case 'NOT_TOUCHED':
			default:
				$this->_errors[] = $status;
				return false;
		}
	}

	/**
	 * Adds the named tube to the watch list for the current
	 * connection.
	 *
	 * @param string $tube
	 * @return integer|boolean False on error otherwise number of tubes in watch list
	 */
	public function watch($tube) {
		$this->write(sprintf('watch %s', $tube));
		$status = strtok($this->read(), ' ');

		switch ($status) {
			case 'WATCHING':
				return (integer)strtok(' ');
			default:
				$this->_errors[] = $status;
				return false;
		}
	}

	/**
	 * Remove the named tube from the watch list
	 *
	 * @param string $tube
	 * @return integer|boolean False on error otherwise number of tubes in watch list
	 */
	public function ignore($tube) {
		$this->write(sprintf('ignore %s', $tube));
		$status = strtok($this->read(), ' ');

		switch ($status) {
			case 'WATCHING':
				return (integer)strtok(' ');
			case 'NOT_IGNORED':
			default:
				$this->_errors[] = $status;
				return false;
		}
	}

	/* Other Commands */

	/**
	 * Inspect a job by id
	 *
	 * @param integer $id The id of the job
	 * @return string|boolean False on error otherwise the body of the job
	 */
	public function peek($id) {
		$this->write(sprintf('peek %d', $id));
		return $this->_peekRead();
	}

	/**
	 * Inspect the next ready job
	 *
	 * @return string|boolean False on error otherwise the body of the job
	 */
	public function peekReady() {
		$this->write('peek-ready');
		return $this->_peekRead();
	}

	/**
	 * Inspect the job with the shortest delay left
	 *
	 * @return string|boolean False on error otherwise the body of the job
	 */
	public function peekDelayed() {
		$this->write('peek-delayed');
		return $this->_peekRead();
	}

	/**
	 * Inspect the next job in the list of buried jobs
	 *
	 * @return string|boolean False on error otherwise the body of the job
	 */
	public function peekBuried() {
		$this->write('peek-buried');
		return $this->_peekRead();
	}

	/**
	 * Handles response for all peek methods
	 *
	 * @return string|boolean False on error otherwise the body of the job
	 */
	protected function _peekRead() {
		$status = strtok($this->read(), ' ');

		switch ($status) {
			case 'FOUND':
				return array(
					'id' => (integer)strtok(' '),
					'body' => $this->read((integer)strtok(' '))
				);
			case 'NOT_FOUND':
			default:
				$this->_errors[] = $status;
				return false;
		}
	}

	/**
	 * Moves jobs into the ready queue (applies to the current tube)
	 *
	 * If there are buried jobs those get kicked only otherwise
	 * delayed jobs get kicked.
	 *
	 * @param integer $bound Upper bound on the number of jobs to kick
	 * @return integer|boolean False on error otherwise number of job kicked
	 */
	public function kick($bound) {
		$this->write(sprintf('kick %d', $bound));
		$status = strtok($this->read(), ' ');

		switch ($status) {
			case 'KICKED':
				return (integer)strtok(' ');
			default:
				$this->_errors[] = $status;
				return false;
		}
	}

	/* Stats Commands */

	/**
	 * Gives statistical information about the specified job if it exists
	 *
	 * @param integer $id The job id
	 * @return string|boolean False on error otherwise a string with a yaml formatted dictionary
	 */
	public function statsJob($id) {}

	/**
	 * Gives statistical information about the specified tube if it exists
	 *
	 * @param string $tube Name of the tube
	 * @return string|boolean False on error otherwise a string with a yaml formatted dictionary
	 */
	public function statsTube($tube) {}

	/**
	 * Gives statistical information about the system as a whole
	 *
	 * @return string|boolean False on error otherwise a string with a yaml formatted dictionary
	 */
	public function stats() {
		$this->write('stats');
		$status = strtok($this->read(), ' ');

		switch ($status) {
			case 'OK':
				return $this->read((integer)strtok(' '));
			default:
				$this->_errors[] = $status;
				return false;
		}
	}

	/**
	 * Returns a list of all existing tubes
	 *
	 * @return string|boolean False on error otherwise a string with a yaml formatted list
	 */
	public function listTubes() {}

	/**
	 * Returns the tube currently being used by the producer
	 *
	 * @return string|boolean False on error otherwise a string with the name of the tube
	 */
	public function listTubeUsed() {}

	/**
	 * Alias for listTubeUsed
	 */
	public function listTubeChosen() {
		return $this->listTubeUsed();
	}

	/**
	 * Returns a list of tubes currently being watched by the worker
	 *
	 * @return string|boolean False on error otherwise a string with a yaml formatted list
	 */
	public function listTubesWatched() {}

}
