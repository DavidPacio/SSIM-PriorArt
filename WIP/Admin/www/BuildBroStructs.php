<?php /* Copyright 2011-2013 Braiins Ltd

Admin/www/BuildStructs.php

Build the Bro structs

History:
30.03.13 Created
03.07.13 B R L -> SIM

*/
require 'BaseSIM.inc';
require './inc/BroInfo.inc';
require Com_Inc.'FuncsSIM.inc';

Head('Build Bro Structs', true);

echo "<h2 class=c>Build the Bro Structs</h2>
";

if (!isset($_POST['BroSet']))
  Form();
  #######

$BroSetId = Clean($_POST['BroSet'], FT_INT);

$bsO = $DB->ObjQuery(sprintf("Select * From %s.BroSets Where Id=$BroSetId", DB_Braiins));
$BTypeN = (int)$bsO->BTypeN;
$BroSetName = BroSetTypeStr($BTypeN).' BroSet '.$bsO->Name;
if ($BTypeN >= BroSet_Out_Main) {
  # Out-BroSet
  $BroSetTaxnId = json_decode($bsO->Data)[BSI_TaxnIdsA][0];
  # Out-BroSet
  $TxName = TaxnStr($BroSetTaxnId);
  define('DB_Tx', DB_Prefix.str_replace('-', '_', $TxName));
  define('Com_Inc_Tx', Com_Inc."$TxName/");
  define('Com_Str_Tx', Com_Str."$TxName/");
  require Com_Inc_Tx.'ConstantsRgWip.inc'; # djh?? Rename.
  require Com_Inc_Tx.'ConstantsTx.inc';
  require "./$TxName/inc/TxFuncs.inc"; # Tx specific funcs
}else{
  # In-BroSet
  $BroSetTaxnId = 0;
}

echo "<p>Building the Structs for $BroSetName, $bsO->Descr</p>";
unset($bsO);

require './inc/BuildBroStructs.inc';

echo 'Memory usage: ', number_format(memory_get_usage()/1024000,1) , ' Mb<br>',
     'Peak memory usage: ', number_format(memory_get_peak_usage()/1024000,1) , ' Mb<br>';

Footer();
######


function Form() {
  global $DB, $BroSetId;
  echo '<p class=c>Select the BroSet to Build and click Build Structs<br>(Only Main BroSets which have imported without error can be selected.)</p>
<form method=post>
<table class=mc>
';
  $res=$DB->ResQuery(sprintf('Select * from %s.BroSets Order By SortKey', DB_Braiins));
  while ($bsO = $res->fetch_object()) {
    $typeS = BroSetTypeStr($BTypeN = (int)$bsO->BTypeN);
    if (($BTypeN === BroSet_In_Main || $BTypeN === BroSet_Out_Main) && (int)$bsO->Status === Status_OK) {
      # Main that has imported without errors
      $id = (int)$bsO->Id;
      $checked = $BroSetId==$id ? ' checked' : '';
      echo "<tr><td><input id=f$id type=radio class=radio name=BroSet value=$id$checked></td><td class=c><label for=f$id>$id</label></td><td><label for=f$id>$typeS</label></td><td><label for=f$id>$bsO->Name, $bsO->Descr</label></td><tr>\n";
    }else
      echo "<tr><td></td><td class=c>$bsO->Id</td><td>$typeS</td><td>$bsO->Name, $bsO->Descr</td><tr>\n";
  }
  $res->free();
  echo "</table>
<p class=c><button class='on mt10'>Build Structs</button></p>
</form>
";
  Footer(false); # Footer($timeB=true, $topB=false, $notCentredB=false) {
}
