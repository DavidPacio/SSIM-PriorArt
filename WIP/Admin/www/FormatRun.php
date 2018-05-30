<?php /* Copyright 2011-2013 Braiins Ltd

FormatRun.php

History:
02.12.11 djh Written

*/

require 'BaseBraiins.inc';

# djh?? Temp for Utils
$AgentId  = 1; # Braiins
$EntityId = 2; # AAAAA
$DB->Bits = MB_OK | AP_All | AP_Compile;

if (!isset($_POST['Format']))
  Form();
  #######

$AppN     = APP_Admin;
$FormatId = Clean($_POST['Format'], FT_INT); # $FormatId is the Formats.Id of the selected format
chdir(RG_Path);
require 'RgRun.inc'; # Generates accounts in $Html with RT messages in $RunMsg. Requires $AgentId, $EntityId, $AppN, $FormatId to have been set
                     #  and a change to RG_Path dir to have been made, with LOG_FILE defined using an absolute path.
echo $Html;
file_put_contents("../Out/$FFileName.htm", $Html); # RgRun.inc sets $FFileName to the format FileName
echo '<p><br/>', str_replace(NL, '<br/>', trim($RunMsg)), '</p>
';
echo 'Memory usage: ', number_format(memory_get_usage()/1024000,1) , ' Mb<br>',
     'Peak memory usage: ', number_format(memory_get_peak_usage()/1024000,1) , ' Mb<br>',
     'Use Browser Back to return to the Run Format selection screen<br>';

exit;
#####

function Form() {
  global $DB, $FormatId;
  Head('Run Format', true);
  echo '<h2 class=c>Run Format</h2>
<p class=c>Select the Format to Run and click Run</p>
<div class=mc style=width:450px>
<form method=post>
';
  $res=$DB->ResQuery('Select * from Formats Where (Status&1) Order By SortKey');
  while ($o = $res->fetch_object()) {
    $id = (int)$o->Id;
    $checked = $FormatId==$id ? ' checked' : '';
    echo "<input id=f$id type=radio class=radio name=Format value=$id$checked> <label for=f$id>$o->Name, $o->Descr</label><br>\n";
  }
  $res->free();
  echo "<p class=c><button class='on mt10'>Run</button></p>
</form>
</div>
";
  Footer(false); # Footer($timeB=true, $topB=false, $notCentredB=false) {
}



