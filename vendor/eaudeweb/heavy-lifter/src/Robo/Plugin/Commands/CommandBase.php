<?php
/**
 * @file CommandBase.php
 */

namespace EauDeWeb\Robo\Plugin\Commands;

use EauDeWeb\Robo\InvalidConfigurationException;
use Robo\Robo;
use Symfony\Component\Process\Process;

/**
 * Class CommandBase for other commands.
 *
 * @package EauDeWeb\Robo\Plugin\Commands
 */
class CommandBase extends \Robo\Tasks {

  const FILE_FORMAT_VERSION = '2.1';

  /**
   * Check configuration file consistency.
   *
   * @throws \EauDeWeb\Robo\InvalidConfigurationException
   */
  protected function validateConfig() {
    $version = $this->config('project.version');
    if (empty($version)) {
      throw new InvalidConfigurationException(
        'Make sure robo.yml exists and configuration updated to format version: ' . static::FILE_FORMAT_VERSION
      );
    }
    if (!version_compare($version, static::FILE_FORMAT_VERSION, '>=')) {
      throw new InvalidConfigurationException(
        'Update your obsolete robo.yml configuration with changes from example.robo.yml to file format: ' . static::FILE_FORMAT_VERSION
      );
    }
    return TRUE;
  }

  /**
   * Get configuration value.
   *
   * @param string $key
   *
   * @return mixed
   */
  protected function config($key) {
    $config = Robo::config();
    return $config->get($key);
  }

  /**
   * Get configuration value.
   *
   * @param string $key
   * @param string $site
   *   Site config key from the config file (e.g. sites.default.sql.username).
   *
   * @return null|mixed
   */
  protected function configSite($key, $site = 'default') {
    $config = Robo::config();
    $full = 'project.sites.' . $site . '.' . $key;
    $value = $config->get($full);
    if ($value === NULL) {
      $this->yell('Missing configuration key: ' . $full);
    }
    return $value;
  }

  /**
   * Get temporary dir to download temporary files.
   *
   * @return string
   */
  protected function tmpDir() {
    return sys_get_temp_dir();
  }

  /**
   * Get project root directory.
   *
   * @return string
   */
  protected function projectDir() {
    return getcwd();
  }

  /**
   * Return absolute path to drush executable.
   *
   * @return string
   * @throws \EauDeWeb\Robo\InvalidConfigurationException
   */
  protected function drushExecutable() {
    /** @TODO Windows / Windows+BASH / WinBash / Cygwind not tested */
    if (realpath(getcwd() . '/vendor/bin/drush')) {
      return realpath(getcwd() . '/vendor/bin/drush');
    }
    else if (realpath(getcwd() . '/vendor/drush/drush/drush')) {
      realpath(getcwd() . '/vendor/drush/drush/drush');
    }
    throw new InvalidConfigurationException('Cannot find Drush executable inside this project');
  }

  /**
   * Find Drupal root installation.
   *
   * @return string
   * @throws \EauDeWeb\Robo\InvalidConfigurationException
   */
  protected function drupalRoot() {
    $drupalFinder = new \DrupalFinder\DrupalFinder();
    if ($drupalFinder->locateRoot(getcwd())) {
      return $drupalFinder->getDrupalRoot();
    }
    else {
      throw new InvalidConfigurationException("Cannot find Drupal root installation folder");
    }
  }


  /**
   * Detect drush version.
   *
   * @throws \EauDeWeb\Robo\InvalidConfigurationException
   */
  protected function getDrushVersion() {
    $drush = $this->drushExecutable();
    $p = new Process([$drush, 'version', '--format=json']);
    $p->run();
    if ($output = $p->getOutput()) {
      // Try Drush 9
      if ($version = json_decode($output, TRUE)) {
        if (isset($version['drush-version'])) {
          return $version['drush-version'];
        }
      }
      else {
        // Try Drush 8
        if (preg_match("/\d+\.\d+.\d+/", $output)) {
          return $output;
        }
      }
    }
    return FALSE;
  }


  /**
   * @return bool
   * @throws \EauDeWeb\Robo\InvalidConfigurationException
   */
  protected function isDrush9() {
    $drushVersion = $this->getDrushVersion();
    return version_compare($drushVersion, '9') >= 0;
  }
}
