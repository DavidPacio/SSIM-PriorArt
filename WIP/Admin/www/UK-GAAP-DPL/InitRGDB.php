<?php /* Copyright 2011-2013 Braiins Ltd

InitRGDB.php

Initialise the Braiins RG DB

History:
13.04.11 Started
28.04.11 First working version for RG Names with Contexts and Zones revised.
30.04.11 Revised after Taxonomy DB clean up.
01.05.11 Third Party Agents added
05.05.11 Changed to use of adding via replication for some of the RG Names
10.05.11 Changed back to not using replication in order to get running Ids for each Entity Officer and Third Party Agent re the RG Set Address special
         Copy with replication kept in wip
xx.05.11 Numerous additions and changes....
18.05.11 Contexts and SapaRefs tables removed
01.06.11 Started the change from R.Name to BRO or Braiins Report Object
xx.06.11 Adding Bros...
xx.07.11 Changes for use by BrosEdit.php plus handling of inheritance
11.07.11 Removed prevention of adding duplicates here with the addition of global checking by TxId and Hypercubes to NewBro()
         Rearranged interest Bros as per email [A] of 2953 progress (?) Done
27.07.11 Revised for BRO/DiMe field changes, including removal of Assets Properties booleans
27.07.11 Removed Bros Y1EndDate, Y2EndDate, Y3EndDate as these dates should be handled in the data for the year.
         To go with this, changed the names Y0StartDate and Y0EndDate to YearStartDate and YearEndDate
06.08.11 Added setting of the SumToId values
12.08.11 Moved the DB commit calls back to here from BroNew.inc -> reduction in InitRGDB time locally from around 75 secs to 3.4 secs
12.08.11 Added some Bros as per C's SS.
16.09.11 Started on revision to use Braiins dimensions
xx.10.11 Revised Officers for sub sets, and tuples. Then TPAs.
18.10.11 Bros stuff removed with the advent of Bros maintenance via Export/Import. Copy kept as /Admin/www/utils/wip/InitRGDB111016.php
*/

require 'BaseTx.inc';
require Com_Inc_Tx.'ConstantsTx.inc';
require Com_Inc_Tx.'ConstantsRg.inc';

Head("$TxName RG DB Initialise");

if (!isset($_POST['Sure']) || strtolower($_POST['Sure']) != 'yes') {
  Form();
  exit;
}

$zonesB = isset($_POST['Zones']);

if (!$zonesB) {
  echo "<h2 style=margin-left:9em>No box was checked</h2><br>\n";
  Form();
  exit;
}

# if ($zonesB) {
#   // All
#   echo "<h2 class=c>Initialising the Braiins Report Generator DB Tables</h2>\n<b>Truncating table:</b><br>\n";
#   $tablesA = array(
#     'Zones',
#     'DBLog'
#   );
#   foreach ($tablesA as $table) {
#     echo $table, '<br>';
#     $DB->StQuery("Truncate Table $table");
#   }
# }else{
  // Not all
  echo "<h2 class=c>Initialising Braiins Report Generator DB Tables</h2><br>\n";
  if ($zonesB) ZapTable('Zones');
# }

echo "<br><b>Inserting Initial Entries for:</b><br>\n";

$DB->autocommit(false);

if ($zonesB) {
echo "Zones<br>\n";
$datA = array(
  array('Cover',    0, 'Cover page incl header and footer'),
  array('Contents', 0, 'Contents page'),
  array('Info',     0, 'Company Information'),
  array('DirRep',   0, 'Directors Report'),
  array('PL',       2, 'Profit and Loss'),
  array('BS',       1, 'Balance Sheet')
);
foreach ($datA as $Dat)
  $DB->eeInsertMaster('Zones', array('Ref' => $Dat[0], 'SignN' => $Dat[1], 'Descr' => $Dat[2]));
} // end $zonesB block

function ZapTable($table) {
  global $DB;
  $DB->StQuery("Truncate Table $table");
  $DB->StQuery("Delete from DBLog Where Mensa='$table'");
}

$DB->commit();

Footer();

function Form() {
  global $TxName;
  echo <<< FORM
<div style=margin-left:10em>
<h2>Initialise the Braiins $TxName Report Generator DB Tables</h2>
<p>Running this will reset and initialise the selected tables of the $TxName RG DB to their starting points.</p>
<form method=post>
<input type=checkbox name=Zones value=1"> Zones (which could affect Bros Zone data)<br><br>
<input type=hidden name=Sure size=3 value=Yes> <button class=on>Go</button>
</div>
</form>
FORM;
  Footer(false);
}
