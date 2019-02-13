<?php
/**
 * Created by PhpStorm.
 * User: stefan
 * Date: 7/23/18
 * Time: 10:25 AM
 */

namespace Drupal\edw_healthcheck\Render;

use Drupal\Component\Serialization\Json;

/**
 * Renders the edw_healthcheck data in the JSON format.
 *
 * This format is used to print in JSON format so that the informations
 * can be easily imported from there.
 */
class JsonEDWHealthCheckRender implements EDWHealthCheckRenderInterface {

  /**
   * Processes the information into a json format used in application output.
   *
   * @param array $data
   *   An array containing the data required to be processed for rendering.
   *
   * @return string
   *   An array containing the processed data in a printable format.
   */
  public function render(array $data) {
    return Json::encode($data);
  }
}
