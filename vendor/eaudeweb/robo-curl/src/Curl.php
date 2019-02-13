<?php
/**
 * @file Curl.php
 */

namespace EauDeWeb\Robo\Task\Curl;

use Robo\Contract\CommandInterface;
use Robo\Task\BaseTask;

/**
 * Class Curl is a wrapper around curl command line HTTP client.
 *
 * @package EauDeWeb\Robo\Task\Curl
 */
class Curl extends BaseTask implements CommandInterface {

	use \Robo\Common\ExecOneCommand;

	protected $command;

	protected $url;

	/**
	 * Curl constructor.
	 *
	 * @param string $url
	 *   Request URL.
	 */
	public function __construct($url) {
		$this->url = $url;
		$this->command = 'curl';
	}

	/**
	 * Process exits with error if HTTP error occurs. Useful for scripting.
	 *
	 * @return \EauDeWeb\Robo\Task\Curl\Curl
	 */
	public function failOnHttpError() {
		$this->option('-f');
		return $this;
	}

	/**
	 * Follow redirects.
	 *
	 * @return \EauDeWeb\Robo\Task\Curl\Curl
	 */
	public function followRedirects() {
		$this->option('-L');
		return $this;
	}

	/**
	 * Accept redirects to different domains (e.g. example.com => www.example.com).
	 *
	 * @return \EauDeWeb\Robo\Task\Curl\Curl
	 */
	public function locationTrusted() {
		$this->option('--location-trusted');
		return $this;
	}

	/**
	 * Send Basic Authentication headers.
	 *
	 * @param string $username
	 *   Username in clear.
	 * @param $password
	 *   Password in clear.
	 *
	 * @return \EauDeWeb\Robo\Task\Curl\Curl
	 */
	public function basicAuth($username, $password) {
		$this->option('-u', $username . ':' . $password);
		return $this;
	}

	/**
	 * Write output to file.
	 *
	 * @param string $file
	 *   Output file.
	 *
	 * @return \EauDeWeb\Robo\Task\Curl\Curl
	 */
	public function output($file) {
		$this->option('-o', $file);
		return $this;
	}

	/**
	 * Add HTTP header to the request.
	 *
	 * @param string $header
	 *   HTTP header (i.e. Accept: application/javascript')
	 *
	 * @return \EauDeWeb\Robo\Task\Curl\Curl
	 */
	public function header($header) {
		$this->option('-H', $header);
		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getCommand() {
		$this->rawArg($this->url);
		return $this->command . $this->arguments;
	}

	/**
	 * @return \Robo\Result
	 */
	public function run() {
		$command = $this->getCommand();
		return $this->executeCommand($command);
	}
}