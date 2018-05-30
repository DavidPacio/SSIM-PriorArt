<?php /* Copyright 2011-2013 Braiins Ltd

Admin/www/BroSets.php

List the BroSets

History:
01.04.13 Started
03.07.13 B R L -> SIM

ToDo
----

*/

require 'BaseSIM.inc';
require Com_Inc.'FuncsSIM.inc';
require Com_Inc.'DateTime.inc';

Head('BroSets', true);

echo "<h2 class=c>Braiins BroSets</h2>
<table class=mc>
<tr class='c b bg0'><td>Id</td><td>Status</td><td class=c>Type</td><td>Name</td><td>Sort<br>Key</td><td>Description</td><td>Taxonomies</td><td>Countries</td><td>Entity<br>Types</td><td>Includes</td><td>Date<br>From</td><td>Date<br>To</td><td class=c>Created</td><td class=c>Last Import</td></tr>
";
$res=$DB->ResQuery(sprintf('Select * from %s.BroSets Order By SortKey', DB_Braiins));
while ($bsO = $res->fetch_object()) {
  $BSDataA  = json_decode($bsO->Data);
  $status   = (int)$bsO->Status;
  $status   = $status === Status_OK ? 'OK' : ($status === Status_DefBroErrs ? 'Def Bro Warnings' : 'Errors');
  $typeS    = BroSetTypeStr($bsO->BTypeN);
  $sortKey  = $bsO->SortKey !== $bsO->Name ? $bsO->SortKey : '';
  $taxns    = IdArraysStrViaFn($BSDataA[BSI_TaxnIdsA],  $BSDataA[BSI_NotTaxnIdsA],  'TaxnStr');
  $ctrys    = IdArraysStrViaFn($BSDataA[BSI_CtryIdsA],  $BSDataA[BSI_NotCtryIdsA],  'CountryShortName');
  $ets      = IdArraysStrViaFn($BSDataA[BSI_ETypeIdsA], $BSDataA[BSI_NotETypeIdsA], 'EntityTypeStr');
  $includes = IdArraysStrViaFn($BSDataA[BSI_IncludesA], null,                       'BroSetName');
  $DateFrom = eeDtoStr($BSDataA[BSI_DateFrom]);
  $DateTo   = eeDtoStr($BSDataA[BSI_DateTo]);
  $created  = substr($bsO->AddT, 0, -3);
  $edited   = $bsO->EditT ? substr($bsO->EditT, 0, -3) : '';
  echo "<tr><td class=c>$bsO->Id</td><td class=c>$status</td><td>$typeS</td><td>$bsO->Name</td><td>$sortKey</td><td>$bsO->Descr</td><td>$taxns</td><td>$ctrys</td><td>$ets</td><td>$includes</td><td class=c>$DateFrom</td><td class=c>$DateTo</td><td>$created</td><td>$edited</td><tr>\n";
}
echo "</table>
";
Footer(); # Footer($timeB=true, $topB=false, $notCentredB=false) {

function BroSetName($id) {
  global $DB;
  return $DB->StrOneQuery(sprintf('Select Name from %s.BroSets Where Id=%d', DB_Braiins, $id));
}
