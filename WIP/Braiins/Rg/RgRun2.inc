<?php /* Copyright 2011-2013 Braiins Ltd

Braiins/Rg/RgRun2

Performs Pass 2 through $P1LinesA[] generating $P2LinesA[] started by RgGaapHead.inc
Then     Pass 3 through $P2LinesA[] to insert the xrefs
Finishes with Accounts in $Html and RT messages in $RunMsg for enclosing module to handle.

djh?? Merge this into RgRun.php?

*/

# Resolve the Xrefs
# Special start of line characters:
#  N : new page
#  P : line contains  page number marker(s) to be replaced
#  T : target xref
#  X : line contains  xref marker(s) to be resolved

# Will need to work out pagination in this pass.
$page = 1;
$lines = count($P1LinesA);
foreach ($P1LinesA as $i => $line) {
  switch ($line[0]) {
    case 'N': # New page
      $page++;
      $P2LinesA[] = "</div>\n<div class='page break'>";
      break;
    case 'P':
      while ($p = strpos($line, ''))
        $line = str_replace('', $page, $line);
      $P2LinesA[] = substr($line, 1);
      break;
    case 'T': # Target xref
      $name = substr($line, 1);
      #echo "Target $name<br/>";
      $XrefsA[$name][XrefI_Page] = $page;
      if ($XrefsA[$name][XrefI_Bits] & (XrefB_Text | XrefB_Link)) {  # djh?? Improve this re proper bits handling
        # Text is required
        # echo "Target $name text req<br/>";
        for (; $i<$lines; $i++) {  # djh?? Do this in first pass?
          $line = $P1LinesA[$i];   # djh?? Use preg_split for this?
          if (($p = strpos($line, '<h')) !== false || ($p = strpos($line, '<p')) !== false || ($p = strpos($line, '<td')) !== false) {
            if ($p = strpos($line, '>', $p)) { # expected to be true <h...> <p...> <td...>
              $line = substr($line, $p+1);
              if (($s = strpos($line, '<span')) !== false) { # take the span contents
                if ($s = strpos($line, '>', $s)) { # expected to be true <span...>
                  $span = substr($line, $s+1);
                  if ($s = strpos($span, '<')) { # expected to be true
                    if ($txt = substr($span, 0, $s)) {
                      $XrefsA[$name][XrefI_Text] = $txt;
                      break;
                    }
                  }
                }
              }
              if ($p = strpos($line, '<')) { # expected to be true
                if ($txt = substr($line, 0, $p)) {
                  $XrefsA[$name][XrefI_Text] = $txt;
                  break;
                }
              }
            }
          }
        }
      }
      if ($XrefsA[$name][XrefI_Bits] & XrefB_Link)
        # Xref is target of a link or links
        $P2LinesA[] = "<a id='$name'></a>";
      break;
    default:
      $P2LinesA[] = $line; # Nothing done for X in this pass as need to go all the way through to resolve all targets first
  }
}
# Pass 3
# Insert xrefs
foreach ($P2LinesA as $i => $line) {
  # echo htmlspecialchars($line), '<br>';
  if ($line[0] == 'X') { # xref(s) to be inserted
    $line = substr($line, 1);
    while ($p = strpos($line, '')) {
      # \1blnnnnn
      $p1   = $p+1; # pointing to bits
      $bits = (int)$line[$p1++]; # p1 points to len
      $len  = ord($line[$p1++]); # p1 points to name
      $name = substr($line, $p1, $len);
      $text = $XrefsA[$name][XrefI_Text];
      $page = $XrefsA[$name][XrefI_Page];
      switch ($bits) {
        case XrefB_Text: $rep = $text; break;
        case XrefB_Page: $rep = $page; break;
        case XrefB_Both: $rep = $text . ' on page ' . $page; break;
        case XrefB_Link: $rep = "<a href='#$name'>$text</a>"; break;
      }
      $line = substr_replace($line, $rep, $p, $len+3);
    }
    $P2LinesA[$i] = $line;
  }
}
$P2LinesA[] = '</div>';
if ($AppN != BDT_CFS)
  $P2LinesA[] = '</body>
</html>';
$Html = implode(NL, $P2LinesA);
# Finishing with Accounts in $Html and RT messages in $RunMsg for enclosing module to handle.

