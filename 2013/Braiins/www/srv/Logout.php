<?php /*

/srv/Logout.php
Logout server ops

History:
13.02.11 Written
08.01.12 Revised for Session.inc changes re switch from cookies to localStorage
18.10.12 Changed to use of global $AppName
03.03.13 Added Release of Member Login Lock
*/
$AppName = 'Braiins Logout';
require 'Base.inc';
# Open a Session
SessionOpen();

# Op:
# O Logout

# LGN_FULL -> LGN_TENT
# Every other state remains unchanged

# Expect to be logged in
if ($DB->LoginN < LGN_FULL) # not LGN_FULL or LGN_GUEST
  # Should not be here if not logged in
  LogIt("Logout: Was not logged in");

# Release Member Login Lock
$DB->RelBDbLoginLock(T_B_People, $DB->MemId);

SessionStatusChange(V_LO);

# Dat = LoginN
AjaxReturn(OK, $DB->LoginN);

