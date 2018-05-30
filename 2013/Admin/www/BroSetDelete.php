<?php /* Copyright 2011-2013 Braiins Ltd

Admin/www/BroSetDelete.php

Delete a BroSet

History:
06.04.13 Written
03.07.13 B R L -> SIM

ToDo
----

*/

require 'BaseSIM.inc';
require './inc/BroInfo.inc';
require Com_Inc.'FuncsSIM.inc';

Head('Delete BroSet', true);
echo "<h2 class=c>BroSet Delete</h2>
";

if (!isset($_POST['BroSet']))
  Form(false);
  ####

$BroSetId = Clean($_POST['BroSet'], FT_INT);

$DB->autocommit(false);
# Delete the Bros
$DB->StQuery("Delete from BroInfo Where BroSetId=$BroSetId");
# There is no need to reset AutoIncrement for BroInfo as ImportBroSet.php does that
# Delete the BroSet
$DB->StQuery(sprintf("Delete from %s.BroSets Where Id=$BroSetId", DB_Braiins));
# Reset BroSets AutoIncrement
$DB->StQuery(sprintf('Alter Table %s.BroSets Auto_Increment=%d', DB_Braiins, $DB->OneQuery(sprintf('Select Id from %s.BroSets Order by Id Desc Limit 1', DB_Braiins)) + 1));
# Set the status of any other BroSet which included this one to not ok
$res=$DB->ResQuery(sprintf('Select Id,Data from %s.BroSets', DB_Braiins));
while ($bsO = $res->fetch_object()) {
  $BSDataA  = json_decode($bsO->Data);
  if (in_array($BroSetId, $BSDataA[BSI_IncludesA], true))
    $DB->StQuery(sprintf("Update %s.BroSets Set Status=0,EditT=$DB->TnS Where Id=$bsO->Id", DB_Braiins));
}
$res->free();
$DB->commit(); # Commit

# Delete the Str directory
$BroSetPath = Com_Str."BroSet$BroSetId/";
if (is_dir($BroSetPath))
  rrmdir($BroSetPath);

Form(true);

function rrmdir($path) { # From http://www.php.net/manual/en/function.unlink.php
  return is_file($path) ? unlink($path) : array_map('rrmdir', glob($path.'/*')) == rmdir($path);
}

function Form($timeB) {
  global $DB, $BroSetId;
  echo "<p class=c>Select the BroSet to Delete and click Delete BroSet</p>
<form method=post>
<table class=mc>
<tr class='b bg0'><td></td><td>Id</td><td>Type</td><td>Name</td><td>Description</td><td>Taxonomies</td></tr>
";
  $res=$DB->ResQuery(sprintf('Select * from %s.BroSets Order By SortKey', DB_Braiins));
  while ($bsO = $res->fetch_object()) {
    $id      = (int)$bsO->Id;
    $typeS   = BroSetTypeStr($bsO->BTypeN);
    $BSDataA = json_decode($bsO->Data);
    $taxns   = IdArraysStrViaFn($BSDataA[BSI_TaxnIdsA],  $BSDataA[BSI_NotTaxnIdsA], 'TaxnStr');
    echo "<tr><td><input id=f$id type=radio class=radio name=BroSet value=$id></td><td class=c><label for=f$id>$id</label></td><td><label for=f$id>$typeS</label></td><td><label for=f$id>$bsO->Name</label></td><td><label for=f$id>$bsO->Descr</label></td><td>$taxns</td><tr>\n";
  }
  $res->free();
  echo "</table>
<p class=c><button class=on>Delete BroSet</button></p>
</form>
";
  Footer($timeB); # Footer($timeB=true, $topB=false, $notCentredB=false) {
}
