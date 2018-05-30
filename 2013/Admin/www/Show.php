<?php /* Copyright 2011-2013 Braiins Ltd

Show.php

Expect a (txt) file name to be passed for echoing.

*/

$qry = $_SERVER['REQUEST_URI'];
if (($t = strpos($qry, '?')) !== false)
  $file = urldecode(substr($qry, $t + 1));
else
  die('Die - no query string passed to Show.php as expected');
header('Content-Type: text/plain');
header('Content-Disposition: attachment; filename="'.substr(strrchr($file, '/'), 1).'"');
readfile('..'.$file);

