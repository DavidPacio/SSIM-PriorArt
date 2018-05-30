<?php /* Copyright 2011-2013 Braiins Ltd

Admin/www/inc/BuildStructsFuncs.inc

Inc for functions used in building structs.

History:
30.03.13 Started based on UK-GAAP-DPL BuildStructs.inc

void FinishArrayString(&$arrayS)

string Build1DimensionaliArrayString($aS, $aA)
string Build2DimensionalArrayString($aS, $aA)

*/


# Add the closing ];\n after chopping off:
#  - nothing for a final [ for an empty single line array
#  - a final , for a single line array
#  - a final \n if second last char is [ i.e. if nothing was added to the array
#  - the final , and \n if otherwise, assuming an array entry followed by ,\n
function FinishArrayString(&$arrayS) {
  $arrayS = (substr($arrayS,-1) === '[' ? $arrayS : ((substr($arrayS,-1) === ',' || substr($arrayS,-2,1) === '[') ? substr($arrayS,0,-1) : substr($arrayS,0,-2))) . '
];
';
}

# Chop off the final , and \n then add the clos
#  - just the final \n if second last char is [ i.e. if nothing was added to the array
#  - the final , and \n if otherwise, assuming an array entry followed by ,\n
#function FinishArrayString(&$arrayS) {
#  $arrayS = (substr($arrayS,-2,1) === '[' ? substr($arrayS,0,-1) : substr($arrayS,0,-2)) . '
#];
#';
#}


# Build the string form of a 1 dimensional i array with 0 index assumed to be included in start passed via $aS
# Used for arrays like $Names = [i => Name]
# $HyNamesAS = Build1DimensionaliArrayString('$HyNamesA=[0,'.NL, $HyNamesA);
function Build1DimensionaliArrayString($aS, $aA) {
#Dump($aS, $aA);
  foreach ($aA as $j => $a)
    $aS .= (is_int($a) ? $a : "'$a'").COM.NL;
  FinishArrayString($aS);
  return $aS;
}

# Build the string form of a 2 or 3 dimensional array. For the 3D form the elements should be arrays
# Used for arrays like $SumEndBrosA    array of StartEnd SumEnd type Bros  [DataTypeN => [BroId => [BroIds of Bros to sum]]]
function Build2DimensionalArrayString($aS, $aA) {
#Dump($aS, $aA);
  foreach ($aA as $a1Id => $a2A) {
    $aS .= "$a1Id=>";
    if (count($a2A)) {
      $aS .= '[';
      foreach ($a2A as $a2Id => $a3)
        $aS .= "$a2Id=>$a3,\n";
      $aS = substr($aS,0, -2) . "],\n";
    }else
      $aS .= "0,\n";
  }
  FinishArrayString($aS);
  return $aS;
}

