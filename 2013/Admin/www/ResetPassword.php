<?php /* Copyright 2011-2013 Braiins Ltd

ResetPassword.php

Reset a person's password.
Will need a similar routine for Member password when that is operational.

History:
02.02.12 djh Started

*/
require 'BaseBraiins.inc';

$EntityId = 0;

Head("Reset Person's Password", true);
/*
$pA = $DB->AaQuery("Select Id,DName,PW,Fails From People Where Email='fred@braiins.com'");
echo '<br>', strlen($pA['PW']), '<br>';
  foreach (str_split($pA['PW']) as $i => $c)
    echo "$i $c ",ord($c),'<br>';
*/

echo "<h2 class=c>Reset Person's Password</h2>\n";
if (!isset($_POST['Email']))
  Form();
  #######

$email = Clean($_POST['Email'], FT_EMAIL, true, $emailEsc);
$pw    = Clean($_POST['Pw'],    FT_PW);

if ($pA = $DB->OptAaQuery("Select Id,DName,PW,Fails From People Where Email='$emailEsc'")) {
  $DB->UpdateMaster(T_B_People, ['PW' => GenPw($pw, (int)$pA['Id']), 'Fails' => 0], $pA);
  echo "<p class=c>Password for $pA[DName] with Email $email has been reset to $pw</p>";
}else
  echo "<p class=c>No Person's record found for Email '$email'</p>";

Form();
#######

function Form() {
echo <<< FORM
<div class=mc style=width:300px>
<form method=post>
<table class=itran>
<tr><td class=r>Email:</td><td><input type=text name=Email autofocus size=40 maxlength=40></td></tr>
<tr><td class=r>Password:</td><td><input type=text name=Pw size=16 maxlength=16></td></tr>
</table>
<p class='c mb0'><button class='c on m10'>Reset Password</button></p>
</form>
</div>
FORM;
Footer();
exit;
}
