<?php /* Copyright 2011-2013 Braiins Ltd

Admin/utils/BuildTxStructs.php

Build the Taxonomy Based Structs

History:
06,12,11 djh Created as a stand alone module

*/
require 'BaseTx.inc';
require Com_Inc_Tx.'ConstantsTx.inc';
require Com_Inc_Tx.'ConstantsRg.inc';
require Com_Inc_Tx.'TxElementsSkipped.inc';
require 'inc/BuildTxStructs.inc';

Head("Build $TxName Structs");

echo "<h2 class=c>Build the $TxName Taxonomy Based Structs</h2>
";

BuildTxBasedStructs();

Footer();

?>

