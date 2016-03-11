<?php

namespace Drupal\iucn_search\Edw\Facets;

class Facet {
  protected $title;
  protected $field;
  protected $operator;
  protected $limit;
  protected $min_count;
  protected $values = [];

  function __construct($title, $field, $operator = 'AND', $limit = '10', $min_count = '1') {
    $this->title = $title;
    $this->field = $field;
    $this->operator = $operator;
    $this->limit = $limit;
    $this->min_count = $min_count;
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
    // @ToDo: Handle facet values for terms/nodes
    // @ToDo: Make display type configurable
    $return = [];
    foreach ($this->values as $value) {
      // @ToDo: Check why there are "" in string
      $tid = str_replace('"', '', $value['filter']);
      $term = \Drupal\taxonomy\Entity\Term::load($tid);
      if ($term) {
        $return[$tid] = "{$term->getName()} ({$value['count']})";
      }
    }
    return [
      '#type' => 'checkboxes',
      '#title' => $this->title,
      '#options' => $return,
    ];
  }
}