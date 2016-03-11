<?php

namespace Drupal\iucn_search\Edw\Facets;

class Facet {
  protected $field;
  protected $operator;
  protected $limit;
  protected $min_count;

  function __construct($field, $operator = 'AND', $limit = '10', $min_count = '1') {
    $this->field = $field;
    $this->operator = $operator;
    $this->limit = $limit;
    $this->min_count = $min_count;
  }

  public function getArray() {
    return [
      'field' => $this->field,
      'operator' => $this->operator,
      'limit' => $this->limit,
      'min_count' => $this->min_count,
    ];
  }
}