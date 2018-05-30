<?php /* Copyright 2011-2013 Braiins Ltd

BuildFolios.php

Temporary to build a start to SIM.Folios based on UK-IFRS-DPL Hypercube info exported by ExpHys.php

History:
21.03.13 Written
16.04.13 Revised for table changes and using ExpFolios.txt
03.07.13 B R L -> SIM
19.07.12 I t e m -> Member

*/
require 'BaseSIM.inc';
require Com_Str.'PropNamesA.inc';    # $PropNamesA

Head('Build SIM Folios', true);

/*
CREATE TABLE IF NOT EXISTS Folios (
  Id        tinyint unsigned not null auto_increment,
  Name      varchar(20)      not null, #
  Label     varchar(80)      not null, #
  Props     char(20)         not null, # List of properties for this folio as Properties.Id in chr list form
  PMemsA    varchar(20)          null, # [i => [PMemsA as for BroInfo.PMemDiMesA below]] PMem overrides per property, stored in json form
  RoleId    tinyint unsigned not null, # Roles.Id foreign key
  Primary Key (Id),
  Unique Key (Name)
) Engine = InnoDB DEFAULT CHARSET=utf8;

Max length Name = 20
Max length Label = 72
Max Length Props = 13
Max Length PMemsA = 8

*/
echo "<br><b>Building the SIM Folios Table</b><br>";
#Dump('PropNamesA', $PropNamesA);
#$file  = 'FoliosI.txt';
$file   = 'ExpHys.txt'; # IFRS export
$linesA = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

# Name | BraiinsName | Label | Role | Dimensions in Cs Name form
$maxLenName = $maxLenLabel = $maxLenProps = $maxLenPMemsA = 0;
$DB->StQuery("Truncate Folios");
foreach ($linesA as $line) {
  $lineA = explode(TAB, $line);
 #$name  = $lineA[0];
  $name  = $lineA[1]; # BraiinsName used as Folio Name
  $label = $lineA[2];
  $role  = $lineA[3];
  $propsA = [];
  foreach (explode(COM, $lineA[4]) as $dimName) {
    $dimName = trim($dimName);
    if ($propId = array_search($dimName, $PropNamesA, true)) # failed without the true
      $propsA[] = $propId;
    #echo "$dimName -> $propId<br>";
  }
  $props = addslashes(IntAToChrList($propsA)); # addslahes re Prop 44 \
 #if ($name === 'Empty') continue;
 #$name  = str_replace(['Dimensions', 'Dimension', 'Hypercube'], ['Properties', 'Property', 'Folio'], $name);
 #$label = str_replace(['dimensions', 'dimension'], ['properties', 'property'], $label);
  $role  = str_replace(['Hypercube'], ['Folio'], $role);
  if (strlen($name)  > 20) die("Name $name too long");
  if (strlen($label) > 60) die("Label $label too long");
  if (strlen($props) > 10) die("Props $props too long");
 #$maxLenName  = max($maxLenName,  strlen($name));
  $maxLenName  = max($maxLenName, strlen($name));
  $maxLenLabel = max($maxLenLabel, strlen($label));
  $maxLenProps = max($maxLenProps, strlen($props));
  if (!$roleId = $DB->OneQuery("Select Id from Roles Where Role='$role'")) die ("Role $role not found");
  # PMemsA
  switch ($name) {
    case 'JVs':     $props = IntToChr(PropId_Subord).$props; $pMemsA = [[[PMemId_Subord_JV]]];     break; # Add Subord as 1st Prop, then PMem [0 => [II_MandatsA => [Subord.JV]]]  0 as Subord is first Property of the JVs Folio
    case 'Assocs':  $props = IntToChr(PropId_Subord).$props; $pMemsA = [[[PMemId_Subord_Assoc]]];  break;
    case 'Subsids': $props = IntToChr(PropId_Subord).$props; $pMemsA = [[[PMemId_Subord_Subsid]]]; break;
    default: $pMemsA = 0;
  }
  if ($pMemsA) {
    $pMems = json_encode($pMemsA, JSON_NUMERIC_CHECK);
    $maxLenPMemsA = max($maxLenPMemsA, strlen($pMems));
    $pMems = SQ.$pMems.SQ;
  }else
    $pMems = 'Null';
  $DB->StQuery("Insert into Folios Values(Null, '$name', '$label', '$props', $pMems, $roleId)");
  echo "$name<br>";
}
# Add IS
$name   = 'IS';
$label  = 'All Income & Expenses Properties as used by CW for CoA NonTx Bros prior to SIM/BRL';
# Group,Consol,Restated,OpActivs,ExceptAdjusts,AmortAdjusts,BizSegs,Countries,Industry,ExpenseType,ExceptNon,Analysis,GroupTrans,Ageing
# 9,10,11,13,14,15,16,34,35,39,40,41,42,43,44
$props  = addslashes(IntAToChrList([9,10,11,13,14,15,16,34,35,39,40,41,42,43,44]));
$roleId = 22; # 150 - Property - Operating Segments
$DB->StQuery("Insert into Folios Values(Null, '$name', '$label', '$props', Null, $roleId)");
$maxLenName  = max($maxLenName,  strlen($name));
$maxLenLabel = max($maxLenLabel, strlen($label));
$maxLenProps = max($maxLenProps, strlen($props));
echo "$name<br>";

# Add Instance
$name   = 'Instance';
$label  = 'An instance';
$props  = IntToChr(PropId_Instance);
$roleId = 22; # 150 - Property - Operating Segments
$DB->StQuery("Insert into Folios Values(Null, '$name', '$label', '$props', Null, $roleId)");
$maxLenName  = max($maxLenName,  strlen($name));
$maxLenLabel = max($maxLenLabel, strlen($label));
$maxLenProps = max($maxLenProps, strlen($props));
echo "$name<br>";

# Add Info
$name   = 'Info';
$label  = 'Entity Information, no properties';
$props  = IntToChr(PropId_None);
$roleId = 1; # 01 - Entity Information
$DB->StQuery("Insert into Folios Values(Null, '$name', '$label', '$props', Null, $roleId)");
$maxLenName  = max($maxLenName,  strlen($name));
$maxLenLabel = max($maxLenLabel, strlen($label));
$maxLenProps = max($maxLenProps, strlen($props));
echo "$name<br>";

# Add None
$name   = 'None';
$label  = 'No properties';
$props  = IntToChr(PropId_None);
$roleId = 22; # 150 - Property - Operating Segments
$DB->StQuery("Insert into Folios Values(Null, '$name', '$label', '$props', Null, $roleId)");
$maxLenName  = max($maxLenName,  strlen($name));
$maxLenLabel = max($maxLenLabel, strlen($label));
$maxLenProps = max($maxLenProps, strlen($props));
echo "$name<br>";

echo "<br>Done<br>Max length Name = $maxLenName<br>
Max length Label = $maxLenLabel<br>
Max Length Props = $maxLenProps<br>
Max Length PMemsA = $maxLenPMemsA<br>";

Footer();

