<?php

namespace Drupal\iucn_search\Edw\Facets;

use Drupal\Core\Config\ConfigValueException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldStorageConfig;

class Facet {

  public static $RENDER_CONTEXT_WEB = 'web';
  public static $RENDER_CONTEXT_SOLR = 'solr';
  public static $RENDER_CONTEXT_GET = 'get';

  public static $FACET_DEFAULT_LIMIT = 10;
  public static $FACET_DEFAULT_MIN_COUNT = -1;

  public static $OPERATOR_OR = 'OR';
  public static $OPERATOR_AND = 'AND';

  protected $id;
  protected $title;
  protected $placeholder;
  protected $facet_field;
  protected $operator;
  protected $limit;
  protected $min_count;
  protected $missing = FALSE;
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
    $this->missing = $this->getConfigValue('missing', FALSE);
    $this->bundle = $bundle;
    if ($info = FieldStorageConfig::loadByName('node', $field_id)) {
      $this->entity_type = $info->getSetting('target_type');
    }
    $this->validate();
  }

  public function render($context) {
    $call = 'render_' . $context;
    if (method_exists($this, $call)) {
      $args = func_get_args();
      array_shift($args);
      return $call($args);
    }
    else {
      throw new \Exception("Unknown rendering context: $context");
    }
  }

  /**
   * @param FormStateInterface $form_state
   */
  protected function render_get($form_state) {
    $query = array();
    $values = array();
    $id = $this->getId();
    foreach ($form_state->getValue($id . '_values') as $value => $selected) {
      if ($selected) {
        $values[] = $value;
      }
    }
    if (!empty($values)) {
      $query[$id] = implode(',', $values);
    }
    if ($op = $form_state->getValue($id . '_operator')) {
      $query[$id . '_operator'] = $op;
    }
  }

  protected function render_solr(&$solarium_query, $facet_set, $parameters, $solr_field_name) {
    $operator = $this->getOperator();
    if (!empty($parameters[$solr_field_name])) {
      $values = explode(',', $parameters[$solr_field_name]);
      if (count($values) > 1) {
        $val = '(' . implode(" {$operator} ", $values) . ')';
      }
      else {
        $val = reset($values);
      }
      $solarium_query->createFilterQuery("facet:{$solr_field_name}")
        ->setTags(["facet:{$solr_field_name}"])
        ->setQuery("{$solr_field_name}:$val");
    }
    $facet_field = $facet_set->createFacetField($solr_field_name)
      ->setField($solr_field_name);
    if (!empty($operator) && strtoupper($operator) === 'OR') {
      $facet_field->setExcludes(["facet:{$solr_field_name}"]);
    }
    // Set limit, unless it's the default.
    if ($this->getLimit() != self::$FACET_DEFAULT_LIMIT) {
      $facet_field->setLimit($this->getLimit());
    }
    // Set mincount, unless it's the default.
    if ($this->getMinCount() != self::$FACET_DEFAULT_MIN_COUNT) {
      $facet_field->setMinCount($this->getMinCount());
    }
    $facet_field->setMissing($this->getMissing());
  }

  protected function render_web() {
    $ret = array();
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
        $ret = array(
          '#type' => $widget,
          '#title' => $this->title,
          '#options' => $options,
          '#default_value' => !empty($_GET[$this->facet_field]) ? explode(',', $_GET[$this->facet_field]) : [],
        );
        break;
      case 'select':
        $ret = array(
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
        );
    }
    return $ret;
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
    if (!empty($operator)) {
      if (strtoupper($operator) == self::$OPERATOR_OR) {
        $this->operator = self::$OPERATOR_OR;
      }
      if (strtoupper($operator) == self::$OPERATOR_AND) {
        $this->operator = self::$OPERATOR_AND;
      }
    }
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

  function getFacetField() {
    return $this->facet_field;
  }

  function getMissing() {
    return $this->missing;
  }
}
