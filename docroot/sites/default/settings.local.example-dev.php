<?php

/** Enable local development services. */
$settings['container_yamls'][] = DRUPAL_ROOT . '/sites/development.services.yml';

/** Show all error messages */
$config['system.logging']['error_level'] = 'all';

/** Disable CSS/JS aggregation. */
$config['system.performance']['css']['preprocess'] = FALSE;
$config['system.performance']['js']['preprocess'] = FALSE;

/** Disable the render cache (this includes the page cache). */
$settings['cache']['bins']['render'] = 'cache.backend.null';

/** Disable Dynamic Page Cache. */
$settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';

/** Allow test modules and themes to be installed. */
$settings['extension_discovery_scan_tests'] = TRUE;

/** Views show SQL and performance on dev */
$config['views.settings']['ui']['show']['sql_query']['enabled'] = TRUE;
$config['views.settings']['ui']['show']['performance_statistics'] = TRUE;

$databases['default']['default'] = [
  'database' => 'drupal',
  'username' => 'root',
  'password' => 'root',
  'prefix' => '',
  'host' => 'db',
  'port' => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
];

$settings['trusted_host_patterns'] = ['wildlex.local'];
