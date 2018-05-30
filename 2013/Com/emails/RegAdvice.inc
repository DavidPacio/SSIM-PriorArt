<?php
//
// RegAdvice.inc
// Email E_Reg_Advice Revision 0
// Email sent to EMAIL_ADVISE to advise that an agent has registered
//
// Copyright 2011-2013 Braiins Ltd
//
// Associated: Reg.php
// Author    : David Hartley
// History:
// 18.02.11 Created from the NMS version

// Uses
// $aName, $agentId, $pName, $pId, $adminId, $email

// Creates
// $emailId, $rev, $to, $from, $subject, $body

$emailRef = E_Reg_Advice;
$rev  = 0;
$to   = EMAIL_ADVISE;
$from = EMAIL_ADMIN;
$aNameHe = htmlentities($aName);
$pNameHe = htmlentities($pName);

////////////////////////
// $subject and $body //
////////////////////////

$subject = "New Braiins Registration";
$body = <<< BODY
We have a new Braiins Registration:
 Agent: $aNameHe (Id $agentId)
 Administrator: $pNameHe (People Id $peoId, Member Id $adminId)
 Email: $email
BODY;

//ndEmail($peoId, $emailRef, $rev, $to, $from, $subject, $body)
SendEmail(0,      $emailRef, $rev, $to, $from, $subject, $body);

?>
