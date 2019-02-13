<?php

namespace EauDeWeb\Robo\Task\Curl;

trait loadTasks {

	/**
	 * @param string $url
	 * @return DrushStack
	 */
	protected function taskCurl($url) {
		return $this->task(Curl::class, $url);
	}
}
