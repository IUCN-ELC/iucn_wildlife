<?php

namespace Drupal\iucn_search\edw\solr;

use Drupal\Core\Config\ConfigValueException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldStorageConfig;

class SolrFacet {

  public static $RENDER_CONTEXT_WEB = 'web';
  public static $RENDER_CONTEXT_SOLR = 'solr';
  public static $RENDER_CONTEXT_GET = 'get';

  public static $FACET_DEFAULT_LIMIT = 10;
  public static $FACET_DEFAULT_MIN_COUNT = -1;

  public static $OPERATOR_OR = 'OR';
  public static $OPERATOR_AND = 'AND';

  protected $id;
  protected $values = array();
  protected $config = array();

  public function __construct($drupal_field_id, $bundle, $solr_field_id, array $config) {
    $this->id = $drupal_field_id;
    $this->solr_field_id = $solr_field_id;
    $this->config = $config;
    $this->config['bundle'] = $bundle;
    $this->config['solr_field_id'] = $solr_field_id;
    if ($info = FieldStorageConfig::loadByName('node', $drupal_field_id)) {
      $this->config['entity_type'] = $info->getSetting('target_type');
    }
    $this->validate();
  }

  public function render($context) {
    $call = 'render_' . $context;
    if (method_exists($this, $call)) {
      $args = func_get_args();
      array_shift($args);
      return call_user_func(array($this, $call), $args);
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

  protected function render_solr(&$solarium_query, &$facet_set, $parameters, $solr_field_name) {
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
      switch ($this->getConfigValue('entity_type')) {
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
          '#title' => $this->getTitle(),
          '#options' => $options,
          '#default_value' => !empty($_GET[$this->id]) ? explode(',', $_GET[$this->id]) : [],
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
            '#markup' => "<h4 class='facet-title'>{$this->getTitle()}</h4>",
          ],
          $this->id . '_operator' => [
//            '#title' => $this->operator,
            '#type' => 'checkbox',
            '#default_value' => $this->getOperator() == 'AND',
            '#return_value' => 'AND',
          ],
          $this->id . '_values' => [
//            '#title' => $this->title,
            '#type' => $widget,
            '#options' => $options,
            '#default_value' => !empty($_GET[$this->id]) ? explode(',', $_GET[$this->id]) : [],
            '#multiple' => TRUE,
            '#attributes' => [
              'data-placeholder' => $this->getConfigValue('placeholder', ''),
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
    if(empty($this->getConfigValue('bundle'))) {
      throw new ConfigValueException('Missing bundle for facet');
    }
    if(empty($this->getConfigValue('entity_type'))) {
      throw new ConfigValueException("Could not determine entity_type for given field {$this->getConfigValue('bundle')}:{$this->id}");
    }
    if(empty($this->getConfigValue('solr_field_id'))) {
      throw new ConfigValueException("Could not determine entity_type for given field {$this->getConfigValue('bundle')}:{$this->id}");
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
    return $this->getConfigValue('title', $this->id);
  }

  function getPlaceholder() {
    return $this->getConfigValue('placeholder', '');
  }

  function getOperator() {
    return $this->getConfigValue('operator', 'OR');
  }

  function setOperator($operator) {
    if (!empty($operator)) {
      if (strtoupper($operator) == self::$OPERATOR_OR) {
        $this->config['operator'] = self::$OPERATOR_OR;
      }
      if (strtoupper($operator) == self::$OPERATOR_AND) {
        $this->config['operator'] = self::$OPERATOR_AND;
      }
    }
  }

  function getLimit() {
    return $this->getConfigValue('limit', -1);
  }

  function getMinCount() {
    return $this->getConfigValue('min_count', 1);
  }

  function getWidget() {
    return $this->getConfigValue('widget', 'select');
  }

  function getConfig() {
    return $this->config;
  }

  function getFacetedField() {
    return $this->id;
  }

  function getSolrFieldId() {
    return $this->getConfigValue('solr_field_id');
  }

  function getMissing() {
    return $this->getConfigValue('missing', FALSE);
  }
}
