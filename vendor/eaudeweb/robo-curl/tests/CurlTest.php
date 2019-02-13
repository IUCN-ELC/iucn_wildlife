<?php

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Robo;
use Robo\TaskAccessor;
use Symfony\Component\Console\Output\NullOutput;

class CurlTest extends \PHPUnit_Framework_TestCase implements ContainerAwareInterface {

	use \EauDeWeb\Robo\Task\Curl\loadTasks;
	use TaskAccessor;
	use ContainerAwareTrait;

	// Set up the Robo container so that we can create tasks in our tests.
	public function setUp() {
		$container = Robo::createDefaultContainer(null, new NullOutput());
		$this->setContainer($container);
	}

	// Scaffold the collection builder
	public function collectionBuilder() {
		$emptyRobofile = new \Robo\Tasks;
		return $this->getContainer()->get('collectionBuilder', [$emptyRobofile]);
	}

	public function testSimpleUrl() {
		$command = $this->taskCurl("https://www.slashdot.org/?index=''")->getCommand();
		$this->assertEquals("curl https://www.slashdot.org/?index=''", $command);
	}

	public function testFailOnHttpError() {
		$command = $this->taskCurl('url')->failOnHttpError()->getCommand();
		$this->assertEquals("curl -f url", $command);
	}

	public function testFollowRedirects() {
		$command = $this->taskCurl('url')->followRedirects()->getCommand();
		$this->assertEquals("curl -L url", $command);
	}

	public function testLocationTrusted() {
		$command = $this->taskCurl('url')->locationTrusted()->getCommand();
		$this->assertEquals("curl --location-trusted url", $command);
	}

	public function testBasicAuth() {
		$command = $this->taskCurl('url')->basicAuth('username', 'password')->getCommand();
		$this->assertEquals("curl -u 'username:password' url", $command);
	}

	public function testOutput() {
		$command = $this->taskCurl('url')->output('file.html')->getCommand();
		$this->assertEquals("curl -o file.html url", $command);
	}

	public function testHeader() {
		$command = $this->taskCurl('url')->header('Authentication: Bearer 123')->getCommand();
		$this->assertEquals("curl -H 'Authentication: Bearer 123' url", $command);

		$command = $this->taskCurl('url')
			->header('Authentication: Bearer 123')
			->header('Pragma: No-Cache')
			->getCommand();
		$this->assertEquals("curl -H 'Authentication: Bearer 123' -H 'Pragma: No-Cache' url", $command);
	}
}
