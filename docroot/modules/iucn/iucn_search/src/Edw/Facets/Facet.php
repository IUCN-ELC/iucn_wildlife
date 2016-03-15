<?php

namespace Drupal\iucn_search\Edw\Facets;

class Facet {
  protected $title;
  protected $field;
  protected $operator;
  protected $limit;
  protected $min_count;
  protected $values = [];
  protected $entity_type;
  protected $bundle;
  protected $display_type;

  function __construct($title, $field, $operator = 'AND', $limit = '10', $min_count = '1', $display_type = 'checkboxes', $entity_type = 'term', $bundle = NULL) {
    $this->title = $title;
    $this->field = $field;
    $this->operator = $operator;
    $this->limit = $limit;
    $this->min_count = $min_count;
    $this->display_type = $display_type;
    $this->entity_type = $entity_type;
    $this->bundle = $bundle;
  }

  public function getField() {
    return $this->field;
  }

  public function getOperator() {
    return $this->operator;
  }

  public function getArray() {
    return [
      'field' => $this->field,
      'operator' => $this->operator,
      'limit' => $this->limit,
      'min_count' => $this->min_count,
    ];
  }

  public function setValues(array $values) {
    $this->values = $values;
  }

  public function render() {
    $return = [];
    foreach ($this->values as $id => $count) {
      // @ToDo: Check why there are "" in string
      $id = str_replace('"', '', $id);
      switch ($this->entity_type) {
        case 'term':
          $entity = \Drupal\taxonomy\Entity\Term::load($id);
          $display = !empty($entity) ? "{$entity->getName()} ({$count})" : NULL;
          break;
        case 'node':
          $entity = \Drupal\node\Entity\Node::load($id);
          $display = !empty($entity) ? "{$entity->getTitle()} ({$count})" : NULL;
          break;
        default:
          $entity = $display = NULL;
      }
      if ($entity) {
        $return[$id] = $display;
      }
    }
    switch ($this->display_type) {
      case 'checkboxes':
        return [
          '#type' => $this->display_type,
          '#title' => $this->title,
          '#options' => $return,
          '#default_value' => !empty($_GET[$this->field]) ? explode(',', $_GET[$this->field]) : [],
        ];
      case 'select':
        return [
          '#type' => $this->display_type,
          '#title' => $this->title,
          '#options' => $return,
          '#default_value' => !empty($_GET[$this->field]) ? explode(',', $_GET[$this->field]) : [],
          '#multiple' => TRUE,
          '#size' => 5,
        ];
    }
  }
}