<?php
/**
 * Created by PhpStorm.
 * User: stefan
 * Date: 7/19/18
 * Time: 3:41 PM
 */

namespace Drupal\edw_healthcheck\Render;
use Drupal\edw_healthcheck\Helper\EDWHealthCheckHelper;

/**
 * Renders the edw_healthcheck data in the table format.
 *
 * This format is used to print in Console output, when executing drush commands.
 */
class ConsoleEDWHealthCheckRender implements EDWHealthCheckRenderInterface {

  /**
   * Processes the information into a table format used in console output.
   *
   * @param array $data
   *   An array containing the data required to be processed for rendering.
   *
   * @return string
   *   A string containing the processed data in a printable format.
   */
  public function render(array $data) {
    $output = '';
    foreach ($data as $name => $info) {
      switch ($info['project_type']) {
        case 'core':
          $status = EDWHealthCheckHelper::getStatusText($info['status']);

          $output .= '"Drupal Core" :' . "\n";

          if ($status == 'unknown') {
            $output .= '   - Core update status unknown (Custom module)' . "\n";
          }
          elseif ($status == 'current') {
            $output .= '   - Core up to date (Version ' . $info['existing_version'] . ')' . "\n";
          }
          else {
            $current = isset($info['existing_version']) ? $info['existing_version'] : NULL;
            $latest = isset($info['latest_version']) ? $info['latest_version'] : NULL;
            $output .= '   - Core (' . $current . ') - ' . $status . ' - latest : ' . $latest . "\n";
          }
          break;

        case 'module':
          $status = EDWHealthCheckHelper::getStatusText($info['status']);

          $output .= 'Module "' . $name . '" :' . "\n";

          if ($status == 'unknown') {
            $output .= '   - Module update status unknown' . "\n";
          }
          elseif ($status == 'current') {
            $output .= '   - Module up to date (Version ' . $info['existing_version'] . ')' . "\n";
          }
          else {
            $current = isset($info['existing_version']) ? $info['existing_version'] : NULL;
            $latest = isset($info['latest_version']) ? $info['latest_version'] : NULL;
            $output .= '   - Module "' . $name . '" (' . $current . ') - ' . $status . ' - latest : ' . $latest . "\n";
          }
          break;

        case 'theme':
          $output .= 'Theme "' . $name . '" :' . "\n";
          if ($info['status'] == 1) {
            $output .= '   - ENABLED : Version (' . $info['info']['version'] . ')' . "\n";
          }
          else {
            $output .= '   - DISABLED : Version (' . $info['info']['version'] . ')' . "\n";
          }
          break;

      }
    }
    return $output;
  }
}
