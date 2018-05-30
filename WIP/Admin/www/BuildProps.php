<?php /* Copyright 2011-2013 Braiins Ltd

BuildProps.php

Temporary to build a start to SIM.Properties

History:
15.03.13 Written in UK-GAAP-DPL version using ExpDims.txt
16.04.13 Re-written using ExpProps.txt from the SIM Roles Folios Property Members.xlsx SS
02.07.13 Changed from B R L to SIM

*/
require 'BaseSIM.inc';

Head('Build SIM Properties', true);
echo "<br><b>Building the SIM Properties Table</b><br>";

/*
CREATE TABLE IF NOT EXISTS Properties (
  Id        tinyint unsigned not null auto_increment,
  Name      varchar(20)      not null, #
  Label     varchar(50)      not null, #
  RoleId    tinyint unsigned     null, # Roles.Id foreign key
  Primary Key (Id),
  Unique Key (Name)
) Engine = InnoDB DEFAULT CHARSET=utf8;

Max length Name  = 15
Max length Label = 44

*/

$file = 'PropsI.txt';
$linesA = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
#    0     1     2
# Name	Label	Role
$maxLenName = $maxLenLabel = $id = 0;
$DB->StQuery("Truncate Properties");
foreach ($linesA as $row => $line) {
  ++$id;
  $propA  = explode(TAB, $line);
  $name  = $propA[0];
  $label = $propA[1];
  $role  = $propA[2];
  if (strlen($name)  > 20) die("Name $name too long");
  if (strlen($label) > 50) die("Label $label too long");
  $maxLenName  = max($maxLenName,  strlen($name));
  $maxLenLabel = max($maxLenLabel, strlen($label));
  $set = "Name='$name',Label='$label'";
  if ($role) {
    if (!$roleId = $DB->OneQuery("Select Id from Roles Where Role='$role'"))
      die ("Role $role in row $row $line not found");
    $set .= ",RoleId=$roleId";
  }
  $DB->StQuery("Insert into Properties Set $set");
  echo "$name<br>";
}
echo "<br>Done<br>Max length Name = $maxLenName<br>
Max length Label = $maxLenLabel<br>";

Footer();

