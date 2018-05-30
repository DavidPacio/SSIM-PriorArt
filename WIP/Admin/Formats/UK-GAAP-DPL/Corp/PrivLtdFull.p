© Braiins Ltd 2011,2012

Format: PrivLtdFull.p
        Private Ltd Co Accounts - Primary Format

Main format: This
Sub-formats:
 cover.b
 contents.b
 info.b
 dirReport.b
 pl.b
 bs.b
 notes.b

History :
27.01.11 djh Started

====
#constant ETypeC  = ET_PrivateLtdCo
#constant CoSizeC = ES_SmallFRSSE
//#constant fredC = 'tom'
//#constant fred2C='sam'
//#constant fred3C 'C'
//#constant fred4C "sam's name is "sam"'
//#constant fred5C = 1
//#constant fred6C=2
//#constant fred7C 3
//#constant fred7C absd
//#constant fred2C 0
//#constant fred12C fred4C
//#constant VisIdC 'VisId'

//[if CoSizeI == CoSizeC then] [p "CoSizeI == CoSizeC"]

//[if CoSizeI == ES_Small && NumDirectorsI == 3 then]
//  [p "if CoSizeI == ES_Small && NumDirectorsI == 3"]
//[else if CoSizeI == ES_Large or NumDirectorsI == 1]
//  [p "if CoSizeI == ES_Large or  NumDirectorsI == 1"]
//[else if 1]
//[else]
//  [p "NOT if CoSizeI == ES_Small && NumDirectorsI == 3"]
//[end]
////[if CoSizeI == ES_Large || NumDirectorsI == 1]
// if !AccountantsAndAuditorsSameB]
//if AccountantsAndAuditorsSameB == 0]
//[if !CoSizeI]
//[if CoSizeI == 0]
//[if CoSizeI == ES_Large and NumDirectorsI == 1]
//[if CoSizeI == ES_Large or  NumDirectorsI == 1]
//  [p "if CoSizeI == ES_Large or  NumDirectorsI == 1"]
//[end]
//[if CoSizeI == ES_Small][p "one line if for small"]
//[if CoSizeI == ES_Large then][p "one line if for large"]
//[xref page notesX]
//[p 'end of tests']
#include sub/cover.b      Cover page
#include sub/contents.b   Table of contents, including footer
#include sub/info.b       Company Information
#include sub/dirReport.b  Directors' Report
#include sub/pl.b         Profit & Loss
#include sub/bs.b         Balance Sheet
#include sub/notes.b      Notes
===
