<?php

use Drupal\node\Entity\Node;

$json = json_decode(file_get_contents('./modules/iucn/create_countries/countries.json'));
foreach ($json as $country) {
  $node = Node::create(array(
    'type' => 'country',
    'uid' => 1,
    'status' => 'TRUE',
    'title' => $country->name,
    'field_official_name' => $country->name_official,
    'field_country_iso2' => $country->code2l,
    'field_country_iso3' => $country->code3l,
    'field_latitude' => $country->latitude,
    'field_longitude' => $country->longitude,
    'field_zoom' => $country->zoom,
  ));
  print 'Created country: ' . $country->name . PHP_EOL;
  $node->save();
}