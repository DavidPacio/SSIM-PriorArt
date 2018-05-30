<?php /* Copyright 2011-2013 Braiins Ltd

InitFormats.php

Initialise the Braiins DB Formats Table

History:
07.05.13 Extracted from previous InitHdgsPrefsFormats.php

*/

require 'BaseBraiins.inc';

Head('Braiins DB Formats Initialise');

if (!isset($_POST['Sure']) || strtolower($_POST['Sure']) != 'yes') {
  Form();
  exit;
}

echo "<br><h2>Initialising Braiins DB Formats</h2>\n";

$EntityId = 0;
$DB->autocommit(false);

# Formats
ZapTable('Formats');
#ddFormat($TaxnId,  $FTypeN, $ETypeId,       $FileName, $SortKey,$Name,           $Descr)
AddFormat(2,      RGFT_Stat,        5, 'PrivLtdFull.p', 'P',  'Priv Ltd Full',  'Full set of Private Limited Company Accounts');
AddFormat(2,    RGFT_Report,        5, 'Donations.p',   'T1', 'Donations Test', 'Test of Donations Bros and Tuples');
AddFormat(2,    RGFT_Report,        5, 'Test.p',        'T2', 'Test',           'Test of This and That as per the format');

# TaxnId   = 2 = UK-GAAP-DPL
# ETypeId = 5 = Private Limited Company

$DB->commit();

Footer();
exit;

function AddFormat($TaxnId, $FTypeN, $ETypeId, $FileName, $SortKey, $Name, $Descr) {
  global $DB;
  $set = "TaxnId=$TaxnId,FTypeN=$FTypeN,ETypeId=$ETypeId,FileName='$FileName',SortKey='$SortKey',Name='$Name',Descr='$Descr'";
 #$DB->eeInsertMaster('Formats', $colsAA);
  $DB->StQuery("Insert Formats Set $set,AddT=$DB->TnS");
}

function ZapTable($table) {
  global $DB;
  echo " $table<br>\n";
  $DB->StQuery("Truncate Table $table");
 #$DB->StQuery("Delete from DBLog Where Mensa='$table'");
}

function Form() {
  echo <<< FORM
<div style=margin-left:10em>
<h2>Initialise the Braiins Formats</h2>
<p>Running this will reset and initialise the Formats data of the Braiins DB.</p>
<form method=post>
Sure? (Enter Yes if you are.) <input name=Sure size=3 value=Yes> <button class=on>Go</button>
</div>
</form>
FORM;
  Footer(false);
}

