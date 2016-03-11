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

  function __construct($title, $field, $operator = 'AND', $limit = '10', $min_count = '1', $entity_type = 'term', $bundle = NULL) {
    $this->title = $title;
    $this->field = $field;
    $this->operator = $operator;
    $this->limit = $limit;
    $this->min_count = $min_count;
    $this->entity_type = $entity_type;
    $this->bundle = $bundle;
  }

  public function __toString() {
    return $this->title;
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
    // @ToDo: Make display type configurable
    $return = [];
    foreach ($this->values as $value) {
      // @ToDo: Check why there are "" in string
      $id = str_replace('"', '', $value['filter']);
      switch ($this->entity_type) {
        case 'term':
          $entity = \Drupal\taxonomy\Entity\Term::load($id);
          $display = "{$entity->getName()} ({$value['count']})";
          break;
        case 'node':
          $entity = \Drupal\node\Entity\Node::load($id);
          $display = "{$entity->getTitle()} ({$value['count']})";
          break;
        default:
          $entity = $display = NULL;
      }
      if ($entity) {
        $return[$id] = $display;
      }
    }
    return [
      '#type' => 'checkboxes',
      '#title' => $this->title,
      '#options' => $return,
    ];
  }
}