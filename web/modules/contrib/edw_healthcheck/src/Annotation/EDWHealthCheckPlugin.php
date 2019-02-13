<?php

namespace Drupal\edw_healthcheck\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a EDWHealthCheck annotation object.
 *
 * @Annotation
 */
class EDWHealthCheckPlugin extends Plugin {
  /**
   * A brief, human readable, description of the edw_healthcheck type.
   *
   * This property is designated as being translatable because it will appear
   * in the user interface. This provides a hint to other developers that they
   * should use the Translation() construct in their annotation when declaring
   * this property.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The type of the component that it handles.
   *
   * There are three posibilities : Core, Modules and Themes
   *
   * @var string
   */
  public $type;

}
