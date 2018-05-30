<?php /* Copyright 2011-2013 Braiins Ltd

Admin/www/UK-IFRS-DPL/BuildTxStructs.php

Build the Taxonomy Based Structs

History:
29.06.13 Created

*/
require 'BaseTx.inc';
require Com_Inc_Tx.'ConstantsTx.inc';
require Com_Inc_Tx.'TxElementsSkipped.inc';
require 'inc/BuildTxStructs.inc';

Head("Build $TxName Structs");

echo "<h2 class=c>Build the $TxName Taxonomy Based Structs</h2>
";

BuildTxBasedStructs();

Footer();

?>

