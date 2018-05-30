<?php /* Copyright 2011-2013 Braiins Ltd

Test.php

*/

require 'BaseTx.inc';
#equire 'BaseBraiins.inc';
require Com_Inc_Tx.'ConstantsTx.inc';

Head('Test');
echo '<br>';
#phpinfo();

$HyElIdsA = [265, 2351, 241, 1592, 802, 3702, 3431, 2863, 308, 302, 303, 304, 301, 3255, 2557, 3476, 2550, 2288, 1830, 1828, 1825, 866, 2887, 1051, 3490, 791, 3320, 3722, 3751, 3578, 3894, 212, 2755, 4848, 928, 4487, 4588, 4466, 4598, 4374, 4381, 4590, 4511, 4459];

$res = $DB->ResQuery('Select Id from Elements Where SubstGroupN=4');
while ($o = $res->fetch_object()) {
  $Id=(int)$o->Id;
  if (!in_array($Id, $HyElIdsA))
    echo $Id,BR;
}

# 6901, 8085, 8381, 8383, 8385


Footer();


function MTime($tsecs) {
  return number_format(round($tsecs*1000000)) . ' usecs';
}

function DebugMsg($msg) {
  echo $msg.'<br>';
}
