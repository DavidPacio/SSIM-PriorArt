<?php /* Copyright 2011-2013 Braiins Ltd

Admin/utils/BuildStructs.php

Build the Bro and Zone structs

History:
31.05.11 Started using code from Compile.php
17.09.12 Body split off to BuildStructs.inc for use by BrosImport.php as well. History comments are there.


*/
require 'BaseTx.inc';
require Com_Inc_Tx.'ConstantsTx.inc';
require Com_Inc_Tx.'ConstantsRg.inc';
require Com_Str_Tx.'DiMesA.inc';        # $DiMesA
require Com_Str_Tx.'NamespacesRgA.inc'; # $NamespacesRgA

Head("Build Structs $TxName");

# BuildStructs.inc
require 'inc/BuildStructs.inc';

echo "<h2 class=c>Build the Bro and Zone Structs</h2>
";

BuildStructs();

echo 'Memory usage: ', number_format(memory_get_usage()/1024000,1) , ' Mb<br>',
     'Peak memory usage: ', number_format(memory_get_peak_usage()/1024000,1) , ' Mb<br>';

Footer();
######


