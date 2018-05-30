<?php /* Copyright 2011-2013 Braiins Ltd

/bdt/tab/Tab.php

Stub for separate tab modules

window.opener == bdt window if bdt created the window, incl on reload and repeat from bdt,
                 but not if bdt win closed with this left open and bdt opened and report sent again even with same window name
window.parent == self

History:
31.12.11 Started
19/20.10.12 Updated for change from DataTables to SlickGrid

*/
# Expect Call # (c) | tab module # (tm) | tab name (tn) {| optional options according to module}
require '../../../../Com/inc/Constants.inc';
require '../../../../Com/inc/FuncsGen.inc'; # For the Log functions. /- djh?? Temporary
const LOG_FILE = '../../../Logs/Tab.log';   #   |
if (isset($_POST['Dat']))
  LogIt('Tab.php _POST[Dat]='.$_POST['Dat']); # |
else
  LogIt('Tab.php No _POST[Dat]'); # |

if (isset($_POST['Dat']) && (count($DatA = explode('', $_POST['Dat'])) == 3)) {
  $c  = (int)$DatA[0];
  $tm = (int)$DatA[1];
  $tn = (int)$DatA[2];
  $ti = $ante = 'Generating';
}else
  $tm = 0;
$jsA  = [];
$cssA = ['../css/cobalt/jquery-wijmo.css']; # always as of 20.10.12
$sg = $jui = 0;
switch ($tm) {
  case BDT_CDT: # CDT Current entity Data Trail
    $id  = 'CDT';
    $cssA[] = 'css/Tab.css';
    break;
  case BDT_CFS: # CFS Current entity Financial Statements = RG report
    $id  = 'CFS';
    $cssA[] = 'css/CFS.css';
    break;
  case BDT_EL:  # EL  Entities List
    $id  = 'EL';
    $sg  = 1;
    $cssA[] = 'css/Tab.css';
    break;
  default: # unknown Module #
    $id = $ti = 'Closed';
    $c  = $tn = 0;
    $ante = 'My originating Braiins Desktop has been closed.';
    $cssA[] = 'css/Tab.css';
}
if ($jui) {
 #$jsA[]  = '//ajax.googleapis.com/ajax/libs/jqueryui/1.8.17/jquery-ui.min.js';
 # <script>$.uiBackCompat = false;</script> <!-- To be removed with jui 1.11 --> Wrong but not needed yet as jui isn't yet being used for a Tab app
  $jsA[]  = '../../js/jquery-ui-1.10.1.min.js';
}

if ($sg) {
  $cssA[] = '../pis/sg/bGrid.css';
  $jsA[]  = '../pis/sg/slick.core.js';
  $jsA[]  = '../pis/sg/bGrid.js';
}

$css=$js='';
foreach ($cssA as $file)
  $css .= "<link rel=stylesheet type=text/css href=$file>
";
foreach ($jsA as $file)
  $js .= "<script src=$file></script>
";

echo <<< TABP
<!DOCTYPE html>
<html lang=en>
<head>
<title>$ti</title>
<meta id=$c.$tm.$tn charset=utf-8>
<meta name=author content='Braiins Ltd Copyright 2011-2013'>
<link rel='shortcut icon' href=../../favicon.png>
$css<script src=../../js/jquery-1.9.1.min.js></script>
$js<script src=js/Tab.js></script>
</head>
<body id=$id>
<div id=Page>
<header>
<h2 class='c mb0'><span class=w></span></h2>
<h3 id=Ante class=c>$ante</h3>
</header>
<div id=Main></div>
<div id=Post></div>
<div id=Btns>
<div class='topB hide'>Top</div>
<div class=c><button class='bGo bBDT ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only' role='button' title='Click to go to your Braiins Desktop'><span class=ui-button-text>Braiins Desktop</span></button></div>
</div>
<footer>
Produced for <span class=AName></span> by Braiins.
</footer>
</div>
</body>
</html>
TABP;
