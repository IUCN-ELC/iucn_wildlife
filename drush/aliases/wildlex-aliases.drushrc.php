<?php
$aliases['prod'] = array(
  'root' => '/var/local/wildlex/docroot',
  'uri' => 'http://www.wildlex.org',
  'remote-host' => 'www.wildlex.org',
  'remote-user' => '',
);

// Add your local aliases.
if (file_exists(dirname(__FILE__) . '/aliases.local.php')) {
  include dirname(__FILE__) . '/aliases.local.php';
}

