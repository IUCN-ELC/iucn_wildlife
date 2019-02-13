<?php
/**
 * Created by PhpStorm.
 * User: stefan
 * Date: 7/19/18
 * Time: 3:34 PM
 */

namespace Drupal\edw_healthcheck\Render;

/**
 * An interface for the EDWHealthCheck rendering.
 */
interface EDWHealthCheckRenderInterface {

  /**
   * Prepares the information for the printable format.
   *
   * @param array $data
   *   An array containing the data required to be processed for rendering.
   *
   * @return string
   *   An array containing the processed data in a printable format.
   */
  public function render(array $data);

}
