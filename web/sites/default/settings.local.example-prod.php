<?php
/** Configure database connection */
$databases['default']['default'] = array (
  'database' => 'drupal',
  'username' => 'root',
  'password' => 'root',
  'prefix' => '',
  'host' => 'db',
  'port' => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
);

/** Which environment type are we running? */
$settings['environment'] = 'live'; // 'live', 'test' or 'dev'

/** Hash salt used to protect sensitive data */
$settings['hash_salt'] = '@TODO - Add here a string of about 70 characters';

/** Add here the known domains for this Drupal */
$settings['trusted_host_patterns'] = ['(www\.)?wildlex.org'];

/** Hide errors to users */
$config['system.logging']['error_level'] = 'hide';

/** Set temporary path */
$config['system.file']['path']['temporary'] = '/tmp';

/** Performance tuning */
$config['system.performance']['cache']['page']['max_age'] = 3600;
$config['system.performance']['css']['preprocess'] = TRUE;
$config['system.performance']['js']['preprocess'] = TRUE;

// Disabling stage file proxy if enabled on production
$config['stage_file_proxy.settings']['origin'] = FALSE;
$config_directories['sync'] = '../config/default';
