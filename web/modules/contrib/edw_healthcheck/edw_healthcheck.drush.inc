<?php
/**
 * Created by PhpStorm.
 * User: stefan
 * Date: 7/12/18
 * Time: 2:01 PM
 */

use Drupal\Component\Serialization\Json;
use Drupal\edw_healthcheck\Render\ConsoleEDWHealthCheckRender;

/**
 * Implements hook_drush_command().
 */
function edw_healthcheck_drush_command() {
  $items['edw_healthcheck-list'] = [
        'description' => dt('List all available edw_healthcheck commands.'),
        'arguments' => [],
        'aliases' => ['edw_healthcheck-ls'],
    ];
  $items['edw_healthcheck-status'] = [
        'description' => dt('Default edw_healthcheck command for the EDWHealthCheck Module. Returns the status of a drupal project.'),
        'arguments' => [],
        'aliases' => [],
    ];
  $items['edw_healthcheck-updates'] = [
        'description' => dt('Return a space delimited string of all monitored projects with available updates.'),
        'arguments' => [
            'update type' => 'Type of update available, accepts either all or security.',
        ],
        'aliases' => ['edw_healthcheck-up'],
    ];
  $items['edw_healthcheck-check'] = [
        'description' => dt('Run a edw_healthcheck specific command. It can also be used with the alias "edw_healthcheck [param]" . Parameter is always required!'),
        'arguments' => ['command' => 'The command to run'],
        'aliases' => ['edw_healthcheck'],
    ];
  return $items;
}

/**
 * Drush command callback: edw_healthcheck-list.
 */
function drush_edw_healthcheck_list() {
  // Invoke hook_edw_healthcheck_checks() so people can add in their own checks.
  $module_handler = \Drupal::service('module_handler');
  $commands = edw_healthcheck_drush_command();
  $check_commands = array_merge($module_handler->invokeAll('edw_healthcheck_checks'), drush_command_invoke_all('edw_healthcheck_checks'));
  $rows = [];
  foreach ($commands as $cmd => $val) {
    $rows[] = [$cmd, $val['description']];
  }
  foreach ($check_commands as $cmd => $desc) {
    $rows[] = ['edw_healthcheck ' . $cmd, $desc];
  }
  return drush_print_table($rows);
}

/**
 * Drush command callback: edw_healthcheck.
 */
function drush_edw_healthcheck_status() {
  $output = "EDWHealthCheck is up and running!";
  drush_print($output);
}

/**
 * Drush command callback: edw_healthcheck-check.
 *
 * @param null|string $check
 *   Function name to execute.
 */
function drush_edw_healthcheck_check($check = NULL) {
  $module_handler = \Drupal::service('module_handler');
  if ($check !== NULL) {
    foreach ($module_handler->getImplementations('edw_healthcheck_checks') as $module) {
      if (array_key_exists($check, $module_handler->invoke($module, 'edw_healthcheck_checks'))) {
        edw_healthcheck_check($module, $check);
        return;
      }
    }
    foreach (drush_command_implements('edw_healthcheck_checks') as $command) {
      $function = $command . '_edw_healthcheck_checks';
      if (array_key_exists($check, $function())) {
        edw_healthcheck_check($command, $check);
        return;
      }
    }
    drush_set_error(dt('The requested edw_healthcheck cmd does not exist: !check.', ['!check' => $check]));
  }
  else {
    drush_set_error(dt('Missing argument for edw_healthcheck-check'));
  }
}

/**
 * Helper function for drush_edw_healthcheck_check().
 *
 * @param string $module
 *   Module machine name.
 * @param string $check
 *   Function name to execute.
 */
function edw_healthcheck_check($module, $check) {
  $function = $module . '_edw_healthcheck_check_' . $check;
  if (function_exists($function)) {
    $edw_healthcheck_data = $function();
  }
  else {
    $function = $module . '_edw_healthcheck_check';
    $edw_healthcheck_data = $function($check);
  }
  $key = key($edw_healthcheck_data);

  // We want to exit with exit status so edw_healthcheck knows the outcome
  // of our drush edw_healthcheck check so set DRUSH_EXECUTION_COMPLETED
  // to true.
  drush_set_context('DRUSH_EXECUTION_COMPLETED', TRUE);
  drush_print(Json::encode($edw_healthcheck_data[$key]));
  exit(0);
}

/**
 * Drush command callback: edw_healthcheck-updates.
 */
function drush_edw_healthcheck_updates() {
  $plugin_manager = \Drupal::service('plugin.manager.edw_healthcheck');
  $output = '';
  foreach (['core', 'modules', 'themes'] as $type) {
    $plugin = $plugin_manager->createInstance($type . '_edw_healthcheck');
    $render_instance = new ConsoleEDWHealthCheckRender();
    $data = $plugin->getData();
    $output .= $render_instance->render($data);
  }
  drush_print($output);
}
