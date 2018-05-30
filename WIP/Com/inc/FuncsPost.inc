<?php /* Copyright 2011-2013 Braiins Ltd

/Com/inc/FuncsPost.inc

Posting related functions

ToDo djh??
====

Add check that there is only one post to a DBO Bro

History:
13.05.13 Started change to BRL version based on previous Tx based version

Bro Reference (BroRef) in Source Form as used in Formats or Data Import
-----------------------------------------------------------------------
BroName{,PropPMemRef...}{,<end|start>} where

• BroName is a full BroName, including dots between level sections, or a Bro Shortname

• PropPMemRef or PMemSrcRef is a Property Member reference consisting of PropertyName.PropertyPMemName chosen from those listed in Admin -> SIM -> Property Members
  e.g. TFAClasses.LandBuildings

  There can be any number of comma separated PropRefs in any order.

  Property Member references are converted to PMemIds for DB storage and processing.

  Validity checks are applied to PropPMemRef uses e.g. versus the Bro's allowable properties, potentially as modified for global options, etc.

  Mux checks also apply.

• <,end|start>
  ,end or ,start can optionally be used to specify end or start (closing or opening) period for SumEnd, PostEnd, or Acc type
  StartEnd Instant Bros, and only for such Bros.
  The default is end.

• The PropRefs and end/start Property are comma separated and may come in any order i.e. even with {,<end|start>} in the middle of a series of PropRefs.

Internal PropPMemRef Form
---------------------
The internal PropRef form used here and within Bro objects is:

PMemRef{,PMemRef...}
 with the PMemRefs sorted into:
 . M type PMem
 . Others in ascending PMemId order
 . Pya last

with Start and #s for D, I, E* type members being handled within the Bro Object and Bro Object BroDat keys.

The RG uses BroRefs of BroId{,Bro Object BroDat key} where the key is omitted if it is just BroDatT_End (2).

BroIds are 1-5 digits long, in the range 1 to 99999.


[] BroRefPost($broRef)
======================
where
 $broRef is a BroRef as per "Bro Reference (BroRef) in Source Form as used in Formats or Data Import"

Checks broRef for BroName, properties and for validity if found.

Returns an array of [BroId, pMemRefsA, RefBits] if all is ok.

On an error calls BError() which is expected to return false, which BroRefPost() then passes back to the caller.

Needs
  $GlobalDimGroupExclDimsChrList, and $ESizeId to have been set
  $BroInfoA, $BroNamesA, $BroNamesMapA, $PMemMapA, $PMemsA, $PMemNamesA, $DimGroupsA to have been loaded
Examples
  PL.Revenue.GeoSeg.ByDestination,Countries.UK
  SchInputEntity.Officers.Properties,Officers.Director1,OfficerType.Exec
  SchInputEntity.Officers.Name,.Director1

*/

# djh?? Add Zones check as per BroRef() in Compile.php  ?

function BroRefPost($broRef) {
  global $DB, $AgentId, $GlobalDimGroupExclDimsChrList, $ESizeId, $BroInfoA, $BroNamesA, $BroNamesMapA, $PMemMapA, $PMemsA, $DimGroupsA;
  $refBits = $prop = $manPMemRef = $manPMemj = $pyaPMemRef = $pyaPMemj = 0;
  $pMemRefsA = $stdPMemPropsA = [];
  # BroName{,PropPMemRef...}{,<end|start>}
  $broRefSegsA = explode(COM, $broRef);
  $bName = $broRefSegsA[0];
  if (!isset($BroNamesMapA[$bName]))
    return BError("BroName <i>$bName</i> not known");
  $broId = $BroNamesMapA[$bName];
  $broA  = $BroInfoA[$broId];
  $bits  = $broA[BroI_Bits];
  $bName = $BroNamesA[$broId]; # to get full name in case Shortname was used
  # Bro checks
  if ($broA[BroI_PeriodSEN] === BPT_InstSumEnd && !($refBits & BRefB_Start)) return BError("Posting an end value to <i>$broId $bName</i> is not permitted as the Bro is a 'SumEnd' type whose end value is calculated from its Start value and its StartEnd Sum List");
 #if (!$broA[BroI_DataTypeN]) return BError("Bro has no DataType so cannot be used for posting"); Now covered by following
  if (($bits & BroB_Set) && !($bits & BroB_Summing)) return BError("Bro is a non-summing Set Bro which cannot be used for posting");
  if ($bits & BroB_RO) return BError("Bro is Report Only so cannot be used for posting"); # Not applicable to the compiler version

  if (count($broRefSegsA) > 1) {
    # Have PropPMemRef(s)...
    array_shift($broRefSegsA); # shift off BroName/ShortName leaving just {,PropPMemRef...}{,<end|start>} in $broRefSegsA
    # $manPMemj, $pyaPMemj = index in $broRefSegsA for M use and pya members
    foreach ($broRefSegsA as $j => $pMemSrcRef) {
      #echo "pMemSrcRef=|$pMemSrcRef|".BR;
      $pMemSrcRef = trim($pMemSrcRef);
      if (!$pMemSrcRef) return BError('Empty BroRef segment');
      # start|end?
      if (($s = array_search($pMemSrcRef, ['end', 'start'])) !== false) {
        if ($s) { # 0 for End, 1 for Start
          if ($broA[BroI_PeriodSEN] !== BPT_InstSumEnd) return BError("Posting a start value to <i>$broId $bName</i> is not permitted as the Bro is not a StartEnd SumEnd type");
          $refBits |= BRefB_Start;
        }
        # Check for SumEnd also done below to cover case of no <Start|End> specified as default End posting is invalid in that case
        continue;
      }
      # PropPMemRef expected with 0, 1, or 2 dots expected = 1, 2, or 3 segments
      # Officer.CoSec or just CoSec for Officer repeated and omitted
      # Officer.# e.g. Officer.SmithCC
      # PensionSchemes.ExclMedical.#
      $pMemId = 0;
      $pMemRefSegsA = explode(DOT, $pMemSrcRef);
      if (count($pMemRefSegsA) === 1) {
        # No dot. Could be pMem name missing or just the member name e.g. just CoSec so try with previous Property if there is one
        if ($prop) {
          $pMemSrcRef = $broRefSegsA[$j] = $prop.DOT.$pMemSrcRef;
          array_unshift($pMemRefSegsA, $prop);
        }else
          return BError("Property Member reference <i>$pMemSrcRef</i> is incomplete, and there is no previous Property in the BroRef to try with it.");
      }else
        $prop = $pMemRefSegsA[0];
      # Expect 2 or 3 segments       # Segs
      # Officer.CoSec                   2
      # Officer.# e.g. Officer.SmithCC  2
      # PensionSchemes.ExclMedical.#    3
      if (isset($PMemMapA[$pMemSrcRef]))
        # Match e.g. Officer.CoSec
        $pMemId = $pMemRef = $PMemMapA[$pMemSrcRef];
      else{
        # Not a match as a straight PMem so try for a # Ref
        $hashRef = array_pop($pMemRefSegsA); # SmithCC
        # leaving 1 or 2 segments
        # Officer  PensionSchemes.ExclMedical
        if (isset($PMemMapA[$pMemSrcRef = implode(DOT,$pMemRefSegsA)])) { # PMems with a final .# are in $PMemMapA wo the final .#
          $pMemId = $PMemMapA[$pMemSrcRef];
          if ($tableN = $PMemsA[$pMemId][PMemI_RefTableN]) { # Table enum T_B_* (T_B_People, T_B_Entities, T_B_ERefs, T_Sim_*) if the PMem is a Type D, I, Ei, Ee, R, C, U, X, L which have a # Ref, o'wise 0 which means pMem cannot have a # Ref
            # PMem can have a # Ref
            if ($tableN !== T_B_ERefs) { # Not Ei or Ee which can be defined during posting
              if (!$id = $DB->OneQuery("Select Id from %s Where Ref='$hashRef'".($tableN < T_Sim_Regions ? " And AgentId=$AgentId" : ''), $tableN))
                return PIError($pMemId, "does not have a # Ref <i>$hashRef</i> defined");
            }else{
              $id = $hashRef;
              $refBits |= BRefB_PMemERef;
            }
          }else
            return BError("Property Member reference <i>$pMemSrcRef</i> not known");
          $pMemRef = "$pMemId:$id"; # id is to be converted to Refs.RefId when doing DB inserts
          $refBits |= BRefB_PMemRef;
        } # else $pMemId remains 0 = not known
      }
      if (!$pMemId) return BError("Property Member reference <i>$pMemSrcRef</i> not known");
      if (in_array($pMemRef, $pMemRefsA)) return PIError($pMemId, "is a duplicate. A Property Member can be included only once per BroRef");
      $pMemRefsA[] = $pMemRef;
    }
  }
  # Bro.BroI_PMemDiMesA: PMemDiMesA = Bro PMems Overrides array [i => MandatsA, DefaultsA, ExcludesA, AllowsA]
  if ($broA[BroI_PMemDiMesA]) { # Have Bro PMems
    $PMemDiMesA = $broA[BroI_PMemDiMesA];
    $allowPMemsA = $PMemDiMesA[II_AllowsA];
    if ($PMemDiMesA[II_MandatsA]) # Got Mandatory PMems
      foreach ($PMemDiMesA[II_MandatsA ] as $broPMemsPMemId)
        if (!in_array($broPMemsPMemId, $pMemRefsA, true)) {
          # Mandatory PMem not included. Error if PMem from same Prop is included. Add it o'wise.
          $broPMemsPropId = $PMemsA[$broPMemsPMemId][PMemI_PropId];
          foreach ($pMemRefsA as $pMemId)
            if ($PMemsA[$pMemId][PMemI_PropId] === $broPMemsPropId) return PIError([$pMemId, "Prop$pMemId", $broPMemsPMemId], "is not allowable for Bro <i>$broId $bName</i> as the %s PMem to be used with it must be %s");
          # Add $broPMemsPMemId
          $pMemRefsA[] = $broPMemsPMemId;
        }
    if ($PMemDiMesA[II_DefaultsA]) # Got Default PMems
      foreach ($PMemDiMesA[II_DefaultsA] as $broPMemsPMemId)
        if (!in_array($broPMemsPMemId, $pMemRefsA, true)) {
          # Default PMem not included. Add it if don't have another PMem from same Prop.
          $broPMemsPropId = $PMemsA[$broPMemsPMemId][PMemI_PropId];
          foreach ($pMemRefsA as $pMemId)
            if ($PMemsA[$pMemId][PMemI_PropId]===$broPMemsPropId) {
              $broPMemsPMemId = 0; # already have a PMem in the Default's Prop so skip it
              break;
            }
          if ($broPMemsPMemId) # Add the Default
            $pMemRefsA[] = $broPMemsPMemId;
        }
    if ($PMemDiMesA[II_ExcludesA]) { # Got Exclude PMems
      $excludedA = array_intersect($pMemRefsA, $PMemDiMesA[II_ExcludesA]);
      if (count($excludedA))
        return PIError(current($excludedA), "is excluded from use with Bro <i>$broId $bName</i>");
    }
  }else
    $allowPMemsA = 0;

  # PMems checks
  if (count($pMemRefsA)) {
    $usableProps = $broA[BroI_SumUsablePropDims] ? : $broA[BroI_BroUsablePropDims];
    foreach ($pMemRefsA as $j => $pMemRef) {
      $pMemId = (int)$pMemRef; # re #s
      $propId = $PMemsA[$pMemId][PMemI_PropId];
     #if (!(($usableProps && InChrList($propId, $usableProps)) ||
     #    ($allowPMemsA && in_array($pMemId, $allowPMemsA, true))))
     #  # Not (Prop in UsableProps || PMem in Allow PMems)
      if ((!$usableProps || !InChrList($propId, $usableProps)) &&
          (!$allowPMemsA || !in_array($pMemId, $allowPMemsA, true)))
        # Prop not in UsableProps && PMem not in Allow PMems
        return PIError([$pMemId, "Prop$pMemId"], "from %s is not usable with Bro <i>$broId $bName</i>");
      $pMemA    = $PMemsA[$pMemId]; # [PMemI_PropId, PMemI_Bits, PMemI_MuxListA, PMemI_AddListA, PMemI_ReqListA, PMemI_RefTableN]
      $propId   = $pMemA[PMemI_PropId];
      $pMemBits = $pMemA[PMemI_Bits];
      if ($pMemA[PMemI_MuxListA])
        $refBits |= BRefB_PMemMux;
      if ($pMemBits & PMemB_Zilch) return PIError($pMemId, "is a 'Z' type reserved for RG internal use");
      if ($pMemBits & PMemB_RO)    return PIError($pMemId, "is an 'R' type for RG reporting use which cannot be used for posting");
      /* djh?? Fix DimGroups
      if (InChrList($propId, $GlobalDimGroupExclDimsChrList)) { # all cases now need to work which case it is: U, S. See $DimGroupsA in Constants.inc
        for ($dg=0; $dg<DG_Num; ++$dg) {
          if (in_array($propId, $DimGroupsA[$dg][DGI_DimsA], true)) { # Prop is in this Prop Group
            $t = "involves {$DimGroupsA[$dg][DGI_Name]} which is not a Reporting Requirement " ; # Name of the Reporting Requirement (Prop Group)
            if ($ESizeId<=ES_SmallFRSSE && $DimGroupsA[$dg][DGI_ExSmall])
              return PIError($pMemId, $t.'applicable to a '.EntitySizeStr($ESizeId).' Company');
            return PIError($pMemId, $t.'set for this entity. Go to Entities Edit if this should be changed');
          }
        }
      } */
      # Check special PMem cases
      if ($pMemBits & PMemB_UseM) {
        if ($manPMemRef) return PIError($pMemId, "is a duplicate Mandatory 'M' Use member but only one such member can be used per BroRef");
        $manPMemRef = $pMemRef;
        $manPMemj   = $j;
       #$refBits |= BRefB_PMemMan;
      }else if ($pMemBits & PMemB_pYa) {
        $pyaPMemRef = $pMemRef;
        $pyaPMemj   = $j;
        $refBits |= BRefB_PMemPya;
      }
      $reqListA = $pMemA[PMemI_ReqListA];
      if ($pMemA[PMemI_AddListA]) {
        # PMem has an Additional To list. One of the PMems in the List must be present
        $addListA = $pMemA[PMemI_AddListA];
        $nok=1;
        foreach ($addListA as $addToId)
          if (in_array($addToId, $pMemRefsA)) { # matches refs like '30:4' as loose comparison is being used
            $nok=0;
            break;
          }
        if ($nok)
          return PIError([$pMemId, $addListA], "requires ".(count($addListA) > 1 ? 'one of PMems %s but none of those are included' : 'PMem %s which is not included'));
      }else if (!$reqListA) {
        # PMem has no Additional To nor Required lists = a std PMem for which there should be only one per Property
        if (in_array($propId, $stdPMemPropsA, true))
          return PIError([$broRefSegsA[$j], "Prop$pMemId"], "is a second Property Member Ref for %s which can have only one");
        $stdPMemPropsA[] = $propId;
      }
      if ($reqListA && ($propId >= PropId_FirstNonDbo || ($bits & BroB_DBO))) {
        # PMem has a Required List && (Prop is Non-DBO for which RL applies or (is a DBO Prop and) Bro is a DBO Bro for which RL applies)
        # but skipped for a DBO Prop if not a DBO Bro for which just the # Ref PMem is allowable as will have been set by BroSet Import using AllowPMems with the Prop removed from UsableProps.
        # Count the number of PMems in the list to compare with the Uses rule.
        # BuildPMembers.php ensures that any PMem with a related list has a Member Use rule of either +RL1 or +RLn
        $num=0;
        foreach ($reqListA as $addToId)
          if (in_array($addToId, $pMemRefsA))
            ++$num;
        if (!$num) {
          # none which is always an error
          if ($pMemBits & PMemB_UseRL1)
            # +RL1
            return PIError([$broRefSegsA[$j], $reqListA], sprintf('requires %s', count($reqListA) > 1 ? 'one Additional Member from its Required List of %s but none are included' : 'Member %s which is not included'));
          # +RLn
          return PIError([$broRefSegsA[$j], $reqListA], 'requires one or more Additional Members from its Required List of %s but none are included');
        }
        if ($num > 1) {
          # ok if Use is +RLn
          if ($pMemBits & PMemB_UseRL1)
            return PIError([$broRefSegsA[$j], $reqListA], "can only have one additional Member from its Required List of %s but $num Members have been included");
        } # else $num === 1 which is always ok - ok for +RL1 or +RLn
      }
    }
  }

  # M Check
  if (($bits & BroB_UseM) && !$manPMemRef) # Bro is not RO and the Bro's UsableProps includes one of the Props which include PMems with Member Use 'M' codes
    # No M Use PMem
    return BError("Bro <i>$broId $bName</i> requires a Mandatory 'M' Use Property Member but this is missing");

  if (count($pMemRefsA)) {
    # all OK so sort PMems to get $manPMemRef first in the array, with $pyaPMemRef last, and any others sorted in between so that refs will be consistent
    if ($manPMemRef) unset($pMemRefsA[$manPMemj]);
    if ($pyaPMemRef) unset($pMemRefsA[$pyaPMemj]);
    count($pMemRefsA) ? sort($pMemRefsA) : $pMemRefsA=[]; # if just sort() and $pMemRefsA -> empty, first i afterwards is not 0
    if ($manPMemRef) array_unshift($pMemRefsA, $manPMemRef);
    if ($pyaPMemRef) $pMemRefsA[] = $pyaPMemRef;
  }else
    $pMemRefsA = 0;
  return [$broId, $pMemRefsA, $refBits];
}

function PIError($argsA, $msg) {
  global $PMemsA, $PMemNamesA;
  if (!is_array($argsA))
    $argsA = [$argsA];
  foreach ($argsA as $i => $arg) {
    if (is_array($arg)) # []
      if (count($arg) === 1)
        $arg = $arg[0];
      else{
        $argsA[$i] = implode(', ', $arg);
        continue;
      }
    if (is_int($arg)) # PMemId
      $argsA[$i] = rtrim($PMemNamesA[$arg], '.')." ($arg)";
    else if (!strncmp($arg, 'Prop', 4)) {
      # The Property for the PMem
      $pMemId = (int)substr($arg,4);
      $propId = $PMemsA[$pMemId][PMemI_PropId];
      $argsA[$i] = "Property ".StrField($PMemNamesA[$pMemId], '.', 0);
    }
  }
  return BError(vsprintf("Property Member <i>%s</i> ".$msg, $argsA));
}

