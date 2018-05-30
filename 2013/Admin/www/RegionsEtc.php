<?php /* Copyright 2011-2013 Braiins Ltd

Admin/www/TxsETsESs.php

Lists the SIM Regions, Countries, Currencies,StockExchanges and Languages Tables

History:
26.04.13 Started
03.07.13 B R L -> SIM

*/
require 'BaseSIM.inc';
require Com_Inc.'FuncsSIM.inc';

Head('SIM Regions Etc', true);

/* Regions
   =======
CREATE TABLE IF NOT EXISTS Regions (
  Id       tinyint unsigned not null auto_increment, # = RegionId
  Name     varchar(40)      not null, # Full name of region
  Ref      varchar(30)      not null, # Short name of region if in use e.g. EU, o'wise Name stripped of spaces, used as the # Ref of Property Region
  SortKey  char(4)          not null, #
  AliasId  tinyint unsigned     null, # Regions.Id of full form of the region's name. Used for common 'nicknames' for regions
  PartOf   char(4)              null, # Chr list of RegionIds if also part of another region/other regions
 #Bits     tinyint unsigned not null, # Bit settings. None as of 26.04.13
  Primary Key (Id),     # unique
   Unique Key (Ref)
   #      Key (SortKey) Only used when listing regions, which will be rare vs use as a Property Ref so there is no need for this index
) Engine = InnoDB DEFAULT CHARSET=utf8;
*/
echo "<h1 class=c>SIM Regions, Countries, Currencies, Stock Exchanges, Languages, and Industries</h1>
<table class='itran mc'>
<tr><td><ul class=mt0>
<li><a href=#SR>SIM Regions and Groupings</a></li>
<li><a href=#SC>SIM Countries</a></li>
<li><a href=#SU>SIM Currencies</a></li>
<li><a href=#SX>SIM Stock Excganges</a></li>
<li><a href=#SL>SIM Languages</a></li>
<li><a href=#SI>SIM Industries</a></li>
</ul></td></tr>
</table>
<a name=SR></a>
<h2 class=c>SIM Regions and Groupings</h2>
<table class=mc>
<tr class='b bg0 c'><td>Id</td><td>SortKey</td><td>Name</td><td>Ref</td><td>Part Of</td><td>Alias For</td></tr>
";
$RegionsA = [];
$res = $DB->ResQuery('Select * From Regions Order by SortKey');
while ($o = $res->fetch_object())
  $RegionsA[(int)$o->Id] = $o;
$res->free();
foreach ($RegionsA as $o) {
  $partOf = '';
  if ($o->PartOf) {
    foreach (ChrListToIntA($o->PartOf) as $id)
      $partOf .= sprintf('<br>%s', $RegionsA[$id]->Name);
    $partOf = substr($partOf, 4);
  }
  $aliasOf = $o->AliasId ? $RegionsA[$o->AliasId]->Name : '';
  echo "<tr><td class='c top'>$o->Id</td><td class=top>$o->SortKey</td><td class=top>$o->Name</td><td class=top>$o->Ref</td><td>$partOf</td><td class=top>$aliasOf</td></tr>\n";
}
echo "</table>
<a name=SC></a>
<div class=topB onclick=scroll(0,0)>Top</div><br>
";

/*#############
## Countries ## Countries
###############
CREATE TABLE IF NOT EXISTS Countries (
  Id       smallint unsigned not null auto_increment, # = CountryId or CtryId
  Name     varchar(40)       not null, # Full name of country
  Ref      varchar(32)       not null, # Short name of country if in use e.g. UK, o'wise Name stripped of spaces, used as the # Ref of Property Country
  SortKey  char(10)          not null, #
  ISOnum   smallint unsigned     null, # ISO 31661-1 number. Can be undefined as for Kosovo in 2013
  AliasId  smallint unsigned     null, # CountryId of official form of the country name. Used for common 'nicknames' for countries
  PartOfId smallint unsigned     null, # CountryId of another country the country is part of e.g. England is part of the UK
  AssocId  smallint unsigned     null, # CountryId of another country the country is associated with e.g. Isle of Man is not part of the UK but is 'associated' with it.
  Regions  char(4)               null, # Chr list of up to 4 RegionIds the country is a part of
  Bits     tinyint  unsigned     null, # Bit settings. 1 = Break before in listing
  Primary Key (Id),
   Unique Key (Ref)
   #      Key (SortKey) Only used when listing countries, which will be rare vs use as a Property Ref so there is no need for this index
) Engine = InnoDB DEFAULT CHARSET=utf8;
*/

echo '<h2 class=c>SIM Countries</h2>
<table class=mc>
';
$res = $DB->ResQuery('Select * From Countries Order by SortKey');
$CountriesA = [];
while ($o = $res->fetch_object())
  $CountriesA[(int)$o->Id] = $o;
$res->free();
$n = 0;
foreach ($CountriesA as $o) {
  if ($n<1) {
    echo "<tr class='b bg0 c'><td>Id</td><td>SortKey</td><td>Name</td><td>Ref</td><td>ISO<br>31661-1<br>number</td><td>Part Of</td><td>Associated With</td><td>Regions</td><td>Alias For</td></tr>\n";
    $n = 50;
  }
  --$n;
  $Regions = '';
  if ($o->Regions) {
    foreach (ChrListToIntA($o->Regions) as $id)
      $Regions .= sprintf('<br>%s', $RegionsA[$id]->Name);
    $Regions = substr($Regions, 4);
  }
  $name = $o->Name;
  if ($o->PartOfId) {
    $partOf = $CountriesA[$o->PartOfId]->Name;
    $name = "&nbsp;&nbsp;$name";
  }else
    $partOf = '';
  if ($o->AssocId) {
    $assoc = $CountriesA[$o->AssocId]->Name;
    $name = "&nbsp;&nbsp;$name";
  }else
    $assoc = '';
  $aliasOf = $o->AliasId  ? $CountriesA[$o->AliasId]->Name : '';
  $trClass = $o->Bits ? ' class=brdt2' : '';
 #echo "<tr$trClass><td class='c top'>$o->Id</td><td class=top>$o->SortKey</td><td class=top>$name</td><td class=top>$o->SName</td><td class='c top'>$o->ISOnum</td><td>$partOf</td><td>$assoc</td><td>$Regions</td><td class=top>$aliasOf</td></tr>\n";
  echo "<tr$trClass><td class=c>$o->Id</td><td>$o->SortKey</td><td>$name</td><td>$o->Ref</td><td class=c>$o->ISOnum</td><td class=c>$partOf</td><td class=c>$assoc</td><td class=c>$Regions</td><td class=c>$aliasOf</td></tr>\n";
}
echo '</table>
<a name=SU></a>
<div class=topB onclick=scroll(0,0)>Top</div><br>
<h2 class=c>SIM Currencies</h2>
<table class=mc>
';

##############
# Currencies #
##############
$res = $DB->ResQuery('Select * From Currencies Order by SortKey');
$n = 0;
while ($o = $res->fetch_object()) {
  if ($n<1) {
    echo "<tr class='b bg0 c'><td>Id</td><td>Sort<br>Key</td><td>Name</td><td>ISO<br>4217<br>Code</td></tr>\n";
    $n = 50;
  }
  --$n;
  $trClass = $o->Bits ? ' class=brdt2' : '';
  echo "<tr$trClass><td class=c>$o->Id</td><td>$o->SortKey</td><td>$o->Name</td><td class=c>$o->Ref</td></tr>\n";
}
$res->free();
echo '</table>
<a name=SX></a>
<div class=topB onclick=scroll(0,0)>Top</div><br>
<h2 class=c>SIM Stock Exchanges (To be updated as needed)</h2>
<table class=mc>
';

###################
# Stock Exchanges #
###################
$res = $DB->ResQuery('Select * From Exchanges Order by SortKey');
$n = 0;
while ($o = $res->fetch_object()) {
  if ($n<1) {
    echo "<tr class='b bg0 c'><td>Id</td><td>Sort<br>Key</td><td>Name</td><td>Ref</td></tr>\n";
    $n = 50;
  }
  --$n;
  $trClass = $o->Bits ? ' class=brdt2' : '';
  echo "<tr$trClass><td class=c>$o->Id</td><td>$o->SortKey</td><td>$o->Name</td><td>$o->Ref</td></tr>\n";
}
$res->free();
echo '</table>
<a name=SL></a>
<div class=topB onclick=scroll(0,0)>Top</div><br>
<h2 class=c>SIM Languages (To be updated as needed)</h2>
<table class=mc>
';

#############
# Languages #
#############
$res = $DB->ResQuery('Select * From Languages Order by SortKey');
$n = 0;
while ($o = $res->fetch_object()) {
  if ($n<1) {
    echo "<tr class='b bg0 c'><td>Id</td><td>Sort<br>Key</td><td>Name</td><td>ISO<br>639-1<br>Code</td></tr>\n";
    $n = 50;
  }
  --$n;
  $trClass = $o->Bits ? ' class=brdt2' : '';
  echo "<tr$trClass><td class=c>$o->Id</td><td>$o->SortKey</td><td>$o->Name</td><td class=c>$o->Ref</td></tr>\n";
}
$res->free();
echo '</table>
<a name=SI></a>
<div class=topB onclick=scroll(0,0)>Top</div><br>
<h2 class=c>SIM Industries (SIC Codes)</h2>
<table class=mc>
';

##############
# Industries #
##############
$res = $DB->ResQuery('Select * From Industries Order by Id');
$n = 0;
while ($o = $res->fetch_object()) {
  if ($n<1) {
    echo "<tr class='b bg0 c'><td>Id</td><td>SIC (Ref)</td><td>Description</td></tr>\n";
    $n = 50;
  }
  --$n;
  echo "<tr><td class=c>$o->Id</td><td>$o->Ref</td><td>$o->Descr</td></tr>\n";
}
$res->free();
echo '</table>
';


Footer(true,true);
exit;
