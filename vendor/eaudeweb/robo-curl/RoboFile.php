<?php
/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class RoboFile extends \Robo\Tasks {

	use \EauDeWeb\Robo\Task\Curl\loadTasks;

	public function test() {
		$this->stopOnFail(true);
		$this->taskPHPUnit()
			->option('disallow-test-output')
			->option('report-useless-tests')
			->option('strict-coverage')
			->option('-v')
			->option('-d error_reporting=-1')
			->bootstrap('vendor/autoload.php')
			->arg('tests')
			->run();
	}
}
