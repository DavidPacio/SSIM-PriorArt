<?php
/*
/Com/inc/Email.inc

Copyright (c) 2000-2011 ElanElan Ltd

Re-worked from the NMS version for Braiins as a temporary expedient until
the cron queue based email sending system as per Tari is implemented

Required when an email is to be sent.
Defines function SendEmail() which does the work.

Technology: PHP, HTML
Author    : David Hartley
History:
18.02.11 djh Started

SendEmail
---------
Parameters:
peoId    : Id of the 'to' person, 0 if not a member (user)
emailRef : Ref number of the email
rev      : revision number of the email, zero base
to       : to email address string
from     : sender email address string
subject  : subject string
body     : Email body
Optional from here onwards
html     : True (not null) if email is in Html
cc       : CC list (comma sep), default null
bc       : BC list (comma sep), default null
priority : True (not null) to set email to Urgent
*/

// Email Refs
// ---------
define('E_Reg_Welcome', 1); // RegWelcome.inc   Registration Welcome or Acknowledgement
define('E_Reg_Advice',  2); // RegAdvice.inc    Advise EMAIL_ADVISE of a Registration


function SendEmail($peoId, $emailRef, $rev, $to, $from, $subject, $body, $html=0, $cc=0, $bc=0, $priority=0) {
  $log = gmstrftime('%Y.%m.%d %H:%M:%S') .
" Email sent --------
To: $to
People Id: $peoId
Email Ref: $emailRef (Rev $rev)
";
$domain = APP_DOMAIN;
$headers = "From: $from
Reply-To: $from
Organization: Braiins Limited
Return-Path: <admin@$domain>
X-Sender: <web.server@$domain>
X-Mailer: PHP " . phpversion() .NL;

  if ($cc)
    $headers .= "cc:$cc\n";

  if ($bc)
    $headers .= "Bcc:$bc\n";

  if ($html)
    $headers .= "MIME-Version: 1.0\nContent-type: text/html; charset=utf-8\n";

  if ($priority)
    $headers .= "X-Priority: 1\n";

  $log .= "Headers:\n{$headers}Subject: $subject\nBody:\n$body\n";
  if (LIVE && !mail($to, $subject, $body, $headers)) {
   #Error("Unable to send email to '$to'.");
    LogIt("Unable to send email to '$to'.");
    $log = "Error: Email not sent.\n".$log;
  }
  if ($fh = fopen(EMAIL_LOG, 'a')) {
    if (fwrite($fh, $log) === false)
      LogIt('SendEmail() was unable to write to ' . EMAIL_LOG . ". Data follows:\n$log");
    fclose($fh);
  }else{
    LogIt('SendEmail() was unable to open ' . EMAIL_LOG . ". Data follows:\n$log");
  }
}

// EmailTo
// -------
// To be called to quote a To address to cope with characters such as . in the names which cause email sending to fail.
function EmailTo($name, $email) {
  return "\"$name\" <$email>";
}

?>
