<?php
//
// RegWelcome.inc
// Email E_Reg_Welcome Revision 0
// Email sent to acknowledge and welcome a new Braiins Registration
//
// CCopyright 2011-2013 Braiins Ltd
//
// Associated: Reg.php
// Author    : David Hartley
// History:
// 18.02.11 Created

// Uses
// $aName, $gName, $pName, $email, $uname, $pw, $pId

// Creates
// $emailId, $rev, $to, $from, $subject, $body

$emailRef = E_Reg_Welcome;
$rev  = 0;
$to   = EmailTo($pName, $email);
$from = EMAIL_From;
$aNameHe = htmlentities($aName);
$gNameHe = htmlentities($gName);
$pNameHe = htmlentities($pName);

////////////////////////
// $subject and $body //
////////////////////////

$subject = "Thank you $gName. Welcome to Braiins";
$body = <<< BODY
<html>
<head>
<style type='text/css'>
body {font:11pt Arial,Verdana,Tahoma,sans-serif}
</style>
</head>
<img src='http://www.braiins.com/img/Logo.jpg' alt='Braiins Logo' width="225" height="100">
<h3>Dear $gNameHe</h3>
<p>Thank you for registering $aNameHe with Braiins (Business Reporting and Accounting Inline InterNet Service), the online service for producing iXBRL accounts for online submission to the United Kingdom&rsquo;s HM Revenue &amp; Customs (HMRC).</p>
<p>You ($pNameHe) are now the Administrator for $aNameHe.</p>
<p><b>Gift of Credits</b><br>
$aNameHe has been gifted with 10 credits (explained at <a href='http://www.braiins.com/Prices.htm'>Braiins Prices</a>) to enable you to produce your first set of accounts free.</p>
<p><b>Login Details</b><br>
Please keep the following information safe as you need it to login to Braiins:<br>
&nbsp;&nbsp;Email: $email<br>
&nbsp;&nbsp;Password: $pw</p>
<p>Please remember your password, or keep this email, because for security reasons Braiins does not include a "password forgetten" facility. Braiins support cannot tell what it is either. (Your password is stored one way encrypted in our database i.e. we cannot get it back in its original form. All that support can do if you do forget your password is set up a new one for you, once your identity has been confirmed.) You can change your password via the Braiins Desktop, and as Administator you can change the passwords of any staff members you may choose to set up. (Staff members cannot change their own password. Only the Administrator can do that.)</p>
<p>You login at <a href='http://www.braiins.com/'>www.braiins.com</a>, at the top right, using the email and password above.</p>
<p>Once logged in to Braiins, you can go to the Braiins Desktop to get started.</p>
<p><b>Getting Started</b><br>
A number of sample or demo entities have been set up in the $aNameHe account, using references starting with ZZZZ. You can use these demos at will to try things out, and to experiment with the preferences. You can reset these demo entities at any time, so it doesn&#39;t matter what you do with them.</p>
<p>Take a look at the tagging reports in particular. You should find that the audit one, which is for your internal use, should help with reviewing accounts, without lots of time being wasted using a taggibg tool. The entity (or client) one can be given to your client as part of the accounts package. You may even want to consider mentioning it in the iXBRL section of your letter of engagement.</p>
<p>Then when you are ready, follow the instructions in the Braiins Desktop to upload data and produce iXBRL accounts for your first entity, which you can do at no cost, using the gift credits.</p>
<p>If you want to continue with Braiins to produce more sets of accounts, as we trust you will, you will need to buy more credits, as few or a as many as you decide. Whereas if you conclude that Braiins is not what you are seeking, then just delete your account when logged in to the Braiins Desktop, and that is it.</p>
<p><b>Guarantee</b><br>
Braiins backs its service with this guarantee:</p>
<ul>
<li>If you are unable to achieve 100% tagging of a set of accounts through Brains, with no manual tagging after the event, the credits charged for those accounts will be refunded.</li>
<li>If you find the tagging audit report produced by Braiins to be inadequate, and you are forced to waste time using a manual tagging too to review what has been iXBRL tagged in a set of accounts, the credits charged for those accounts will be refunded.</li>
<li>If any accounts produced via Braiins should be rejected by HMRC as either invalid iXBRL or for non-compliance with HMRC requirements, <b>double</b> the credits charged for those accounts will be refunded.</li>
<li>If you stop using Braiins, and you have unused credits in your account, the cost of those credits will be refunded. There is no time limit on this guarantee.</li>
</ul>
<p>---<br><br>We look forward to helping you produce good quality iXBRL accounts for HMRC submission efficiently, with proper management controls in place and no manual tagging.</p>
<p>Best wishes from the Braiins Team.</p>
</body>
</html>
BODY;

SendEmail($peoId, $emailRef, $rev, $to, $from, $subject, $body, 1);
