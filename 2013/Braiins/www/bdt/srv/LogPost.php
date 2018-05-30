<?php /*

/bdt/srv/LogPost.php
Just to log posts for testing and return an OK

History:
31.12.11 djh Written

*/
echo 'ok';
exit();
require 'Base.inc';

foreach ($_POST as $k => $v)
  LogIt("$URI Post $k=$v");

# AjaxReturn(OK, $_POST['value']);
echo $_POST['value'];
