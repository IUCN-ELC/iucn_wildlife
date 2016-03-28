<?php
use Drupal\taxonomy\Entity\Term;
$json = json_decode(file_get_contents('./modules/iucn/create_countries/countries.json'));
foreach ($json as $country) {
  $term = Term::create([
    'vid' => 'countries',
    'langcode' => 'en',
    'name' => $country->name,
    'weight' => 0,
    'parent' => array (0),
  ]);
  $term->save();
  print 'Created country: ' . $country->name . PHP_EOL;
}