<?php /* Copyright 2011-2013 Braiins Ltd

/Com/inc/UK-IFRS-DPL/TxElementsSkipped.inc

List of Elements to be excluded (skipped) when building the UK-IFRS-DPL DB unless the checkbox controlling this is unchecked.

The list started life as the list of elements to be ignored (skipped) during processing of Bros etc.
Its purpose has now been moved back a stage to the actual DB build, so that elements listed here are not added to the DB at all.
Element Ids are incremented for all skipped elements though, so that the GAAP elements which are in the DB have the same Id as in the UK-GAAP DB.

History:
26.06.13 Started based on the UK-GAAP-DPL one

*/

# Tx Concrete Elements skipped
$TxElementsSkippedA = array(

);

