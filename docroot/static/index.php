<?php
$filename = 'elis_isis3w.php?' . $_SERVER['QUERY_STRING'];
if (is_readable($filename)) {
  print file_get_contents($filename);
}
else {
  print "<result db=\"Faolex\"> <error> File not found $filename </error></result>";
}
