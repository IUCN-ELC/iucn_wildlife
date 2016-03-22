<?php

namespace Drupal\iucn_search\Edw\Facets;

use Drupal\Core\Config\ConfigValueException;
use Drupal\field\Entity\FieldStorageConfig;

class Facet {

  public static $RENDER_CONTEXT_WEB = 'web';
  public static $RENDER_CONTEXT_SOLR = 'solr';

  protected $id;
  protected $title;
  protected $placeholder;
  protected $facet_field;
  protected $operator;
  protected $limit;
  protected $min_count;
  protected $values = array();
  protected $entity_type;
  protected $bundle;
  protected $widget = 'select';
  protected $config = array();

  public function __construct($field_id, $bundle, array $config) {
    $this->id = $field_id;
    $this->field = $field_id;
    $this->config = $config;
    $this->placeholder = $this->getConfigValue('placeholder', '');
    $this->limit = $this->getConfigValue('limit', -1);
    $this->min_count = $this->getConfigValue('min_count', 1);
    $this->widget = $this->getConfigValue('widget', 'select');
    $this->title = $this->getConfigValue('title', $field_id);
    $this->operator = $this->getConfigValue('operator', 'OR');
    $this->bundle = $bundle;
    if ($info = FieldStorageConfig::loadByName('node', $field_id)) {
      $this->entity_type = $info->getSetting('target_type');
    }
    $this->validate();
  }

  public function render($context) {
    $call = 'render_' . $context;
    if (method_exists($this, $call)) {
      return $call();
    }
    else {
      throw new \Exception("Unknown rendering context: $context");
    }
  }

  protected function render_web() {
    $options = array();
    foreach ($this->values as $id => $count) {
      $id = str_replace('"', '', $id);
      $entity = NULL;
      switch ($this->entity_type) {
        case 'term':
          $entity = \Drupal\taxonomy\Entity\Term::load($id);
          break;
        case 'node':
          $entity = \Drupal\node\Entity\Node::load($id);
          break;
      }
      $label = $entity->getTitle();
      if ($label) {
        if ($count > 0) {
          $label .= " ({$count})";
        }
        $options[$id] = $label;
      }
    }
    asort($options);
    $widget = $this->getWidget();
    switch ($widget) {
      case 'checkboxes':
        return [
          '#type' => $widget,
          '#title' => $this->title,
          '#options' => $options,
          '#default_value' => !empty($_GET[$this->facet_field]) ? explode(',', $_GET[$this->facet_field]) : [],
        ];
      case 'select':
        return [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'facet-container',
            ],
          ],
          'title' => [
            '#markup' => "<h4 class='facet-title'>{$this->title}</h4>",
          ],
          $this->facet_field . '_operator' => [
//            '#title' => $this->operator,
            '#type' => 'checkbox',
            '#default_value' => $this->operator == 'AND',
            '#return_value' => 'AND',
          ],
          $this->facet_field . '_values' => [
//            '#title' => $this->title,
            '#type' => $widget,
            '#options' => $options,
            '#default_value' => !empty($_GET[$this->facet_field]) ? explode(',', $_GET[$this->facet_field]) : [],
            '#multiple' => TRUE,
            '#attributes' => [
              'data-placeholder' => $this->placeholder,
            ],
          ],
        ];
    }
  }

  protected function render_solr() {

  }

  protected function validate() {
    if(empty($this->id)) {
      throw new ConfigValueException('Missing field ID for facet');
    }
    if(empty($this->bundle)) {
      throw new ConfigValueException('Missing bundle for facet');
    }
    if(empty($this->entity_type)) {
      throw new ConfigValueException("Could not determine entity_type for given field {$this->bundle}:{$this->id}");
    }
  }

  public function setValues(array $values) {
    $this->values = $values;
  }

  function getConfigValue($key, $default = NULL) {
    $ret = $default;
    if (!empty($this->config[$key])) {
      $ret = $this->config[$key];
    }
    return $ret;
  }

  function getId() {
    return $this->id;
  }

  function getTitle() {
    return $this->title;
  }

  function getPlaceholder() {
    return $this->placeholder;
  }

  function getOperator() {
    return $this->operator;
  }

  function setOperator($operator) {
    $this->operator = $operator;
  }

  function getLimit() {
    return $this->limit;
  }

  function getMinCount() {
    return $this->min_count;
  }

  function getWidget() {
    return $this->widget;
  }

  function getConfig() {
    return $this->config;
  }
}
