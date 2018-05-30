<?php
# Cell Expression Functions
CreateRTFunctions(array('','CellDat(4,1)','','CellDat(4,3)','','CellDat(2,1)." times 2"','','2*CellDat(2,3)','','CellDat(3,2)','','2*CellDat(2,5)','$rowA','CellThis($rowA,4,1)-CellThis($rowA,3,1)','$rowA','CellThis($rowA,3,1)-CellThis($rowA,4,1)','$rowA','CellThis($rowA,3,1)','$rowA','CellThis($rowA,4,1)'));
# Start of Format PrivLtdFull.p with index 0 and level 0
# #constant ETypeC  = ET_PrivateLtdCo PrivLtdFull.p line 20
const ETypeC = 5;
# #constant CoSizeC = CoSize_SmallFRSSE PrivLtdFull.p line 21
const CoSizeC = 2;
  # Start of Format sub/cover.b with index 1 and level 1
  # [footer] sub/cover.b line 13
  $FooterFn='FooterFn1F';
  # [lines 10] sub/cover.b line 21
  Lines(10);
  # [h1 c:c EntityInfo.Names.CurrentLegalOrRegistered] sub/cover.b line 22
  H(1,DataWithTag(8212),'c');
  # [lines 2] sub/cover.b line 23
  Lines(2);
  # [h2 c:c CompanyRegistrationNumberH ":" [nl] EntityInfo.IdentifyingCodes.UKCompaniesHouseRegisteredNumber " (" IncorporationCountryS ")"] sub/cover.b line 24
  H(2,$HeadingsA['CompanyRegistrationNumberH'].":".'<br/>'.DataWithTag(8218)." (".$IncorporationCountryS.")",'c');
  # [lines 2] sub/cover.b line 25
  Lines(2);
  # [h2 c:c DraftS FullAccountsS] sub/cover.b line 26
  H(2,$DraftS.$FullAccountsS,'c');
  # [lines 2] sub/cover.b line 27
  Lines(2);
  # [h2 c:c AccountsPeriodH] sub/cover.b line 28
  H(2,$HeadingsA['AccountsPeriodH'],'c');
  # [line] sub/cover.b line 29
  Lines();
  # [h2 c:c "Start date: " [date c:nb BRI.DatesPeriods.Start]] sub/cover.b line 30
  H(2,"Start date: ".DateStrWithTag(8311,0,0,'nb'),'c');
  # [h2 c:c "End date: " [date c:nb BRI.DatesPeriods.End]] sub/cover.b line 31
  H(2,"End date: ".DateStrWithTag(8312,0,0,'nb'),'c');
  # End of Format sub/cover.b
  # Start of Format sub/contents.b with index 2 and level 1
  # [np] sub/contents.b line 13
  NewPage();
  # [footer] sub/contents.b line 14
  $FooterFn='FooterFn2F';
  # [lines 10] sub/contents.b line 18
  Lines(10);
  # [h1 c:c EntityInfo.Names.CurrentLegalOrRegistered] sub/contents.b line 19
  H(1,DataWithTag(8212),'c');
  # [lines 2] sub/contents.b line 20
  Lines(2);
  # [h2 c:c "Contents of the " DraftS AccountsShortH [nl] "for the Period Ended " [date BRI.DatesPeriods.End]] sub/contents.b line 21
  H(2,"Contents of the ".$DraftS.$HeadingsA['AccountsShortH'].'<br/>'."for the Period Ended ".DateStrWithTag(8312),'c');
  # [lines 4] sub/contents.b line 22
  Lines(4);
  # [block] sub/contents.b line 23
  BlockStart();
    # [toc] Start Table of Contents table sub/contents.b line 24
    TocStart();
      # [toc infoX] sub/contents.b line 25
      Toc('infoX');
      # [toc drX] sub/contents.b line 26
      Toc('drX');
      # [toc plX] sub/contents.b line 27
      Toc('plX');
      # [toc bsX] sub/contents.b line 28
      Toc('bsX');
      # [toc notesX] sub/contents.b line 29
      Toc('notesX');
    # [end] sub/contents.b line 30
    TableEnd();
  # [end] sub/contents.b line 31
  BlockEnd();
  # End of Format sub/contents.b
  # Start of Format sub/info.b with index 3 and level 1
  # [np] sub/info.b line 15
  NewPage();
  # [lines 10] sub/info.b line 16
  Lines(10);
  # [h1 c:c EntityInfo.Names.CurrentLegalOrRegistered] sub/info.b line 17
  H(1,DataWithTag(8212),'c');
  # [lines 2] sub/info.b line 18
  Lines(2);
  # [xref target infoX] sub/info.b line 19
  XrefSt('infoX',0);
  # [h2 c:c CompanyInformationH [nl] "for the Period Ended " [date f BRI.DatesPeriods.End]] sub/info.b line 20
  H(2,$HeadingsA['CompanyInformationH'].'<br/>'."for the Period Ended ".DateStrWithTag(8312),'c');
  # [lines 2] sub/info.b line 21
  Lines(2);
  # [table] sub/info.b line 22
  TableStart();
    # [col c:b DirectorsS ":"][col EntityInfo.Officers.Name@Directors] sub/info.b line 23
    Row(0,array(1,0,$DirectorsS.":",0,'b'));ColBroLoop(2,'8252,426',40,1,0);
    # [lines 2] sub/info.b line 24
    Lines(2);
    # [col c:b 'Company Secretary:'][col EntityInfo.Officers.Name:.CoSec] sub/info.b line 25
    Row(0,array(1,0,'Company Secretary:',0,'b'),array(2,9,'8252,423'));
    # [lines 2] sub/info.b line 26
    Lines(2);
    # [col c:b 'Registered Office Address:'][col EntityInfo.MeansContact.Address:ContactType.RegisteredOffice@Address] sub/info.b line 27
    Row(0,array(1,0,'Registered Office Address:',0,'b'));ColBroLoop(2,'8256,511',6,0,0);
    # [lines 2] sub/info.b line 28
    Lines(2);
    # [col c:b CompanyRegistrationNumberH ":"][col EntityInfo.IdentifyingCodes.UKCompaniesHouseRegisteredNumber] sub/info.b line 29
    Row(0,array(1,0,$HeadingsA['CompanyRegistrationNumberH'].":",0,'b'),array(2,1,8218));
  # [end] sub/info.b line 30
  TableEnd();
  # [line] sub/info.b line 31
  Lines();
  # [p "This is some p text"] sub/info.b line 32
  P("This is some p text");
  # [p c:i "This is some more p text with class i for italics attached."] sub/info.b line 33
  P("This is some more p text with class i for italics attached.",'i');
  # [p c:s "This is some small p text via class s."] sub/info.b line 34
  P("This is some small p text via class s.",'s');
  # [p c:i,s "This is some small italic text via classes i,s."] sub/info.b line 35
  P("This is some small italic text via classes i,s.",'i s');
  # [p c:l "And some large p text via class l."] sub/info.b line 36
  P("And some large p text via class l.",'l');
  # [span 'Stand alone span which should end up in a p tag'] sub/info.b line 37
  P(SpanStr('Stand alone span which should end up in a p tag'));
  # [p 'p text ' [span c:b,s 'now an embedded b,s span'] ' plus more p text, followed on the next line by a stand alone date statement which should end up in a p tag:'] sub/info.b line 38
  P('p text '.SpanStr('now an embedded b,s span','b s').' plus more p text, followed on the next line by a stand alone date statement which should end up in a p tag:');
  # [date f BRI.DatesPeriods.End] sub/info.b line 39
  P(DateStrWithTag(8312));
  # [zones Cover] sub/info.b line 40
  ZoneSign(0);
  # [p EntityInfo.IdentifyingCodes.UKCompaniesHouseRegisteredNumber] sub/info.b line 41
  P(DataWithTag(8218));
  # [p EntityInfo.Officers.Name:.Director1] sub/info.b line 42
  P(DataWithTag('8252,426'));
  # [p EntityInfo.Officers.Name:.Director2] sub/info.b line 43
  P(DataWithTag('8252,427'));
  # End of Format sub/info.b
  # Start of Format sub/dirReport.b with index 4 and level 1
  # [header] sub/dirReport.b line 15
  $HeaderFn='HeaderFn1F';
  # [np] sub/dirReport.b line 18
  NewPage();
  # [xref target drX] sub/dirReport.b line 19
  XrefSt('drX',0);
  # [h2 c:c DirectorsApostropheS' Report'[nl]'for the Period Ended ' [date f BRI.DatesPeriods.End]] sub/dirReport.b line 20
  H(2,$DirectorsApostropheS.' Report'.'<br/>'.'for the Period Ended '.DateStrWithTag(8312),'c');
  # [p DirectorsApostropheS" Report to come here...."] sub/dirReport.b line 21
  P($DirectorsApostropheS." Report to come here....");
  # [lines 10] sub/dirReport.b line 22
  Lines(10);
  # [p c:b 'Signed on behalf of the Board by:'] sub/dirReport.b line 23
  P('Signed on behalf of the Board by:','b');
  # [lines 2] sub/dirReport.b line 24
  Lines(2);
  # [p EntityInfo.Officers.Name:.CoSec [nl] CoSecStatusS] sub/dirReport.b line 25
  P(DataWithTag('8252,423').'<br/>'.$CoSecStatusS);
  # [p 'Approved by the directors on ' [date DirRep.DateSigningDirectorsReport]] sub/dirReport.b line 26
  P('Approved by the directors on '.DateStrWithTag(8567));
  # End of Format sub/dirReport.b
  # Start of Format sub/pl.b with index 5 and level 1
  # [np] sub/pl.b line 13
  NewPage();
  # [footer] sub/pl.b line 14
  $FooterFn='FooterFn3F';
  # [xref target plX] sub/pl.b line 19
  XrefSt('plX',0);
  # [h2 c:c "Profit and Loss"[nl]"for the Period Ended " [date f BRI.DatesPeriods.End]] sub/pl.b line 20
  H(2,"Profit and Loss".'<br/>'."for the Period Ended ".DateStrWithTag(8312),'c');
  # [line] sub/pl.b line 21
  Lines();
  # [zone PL] sub/pl.b line 22
  ZoneSign(2);
  # [table cols:dn01 center] sub/pl.b line 23
  TableStart('dn01',1);
    # [col2 "Note"] [col 3,4 c:b restatedHdg BRI.DatesPeriods.End] sub/pl.b line 24
    Row(0,array(2,0,"Note"),array(3,1,8312,0,'b'),array(4,1,8312,4096,'b'));
    # [col 3,4 c:b "£"] sub/pl.b line 25
    Row(0,array(3,0,"£",0,'b'),array(4,0,"£",0,'b'));
    # [col "Turnover - continuing operations"][col [xref link N1X]] [col y0,, PLRev.TradingIncome.TurnoverGrossOpRevenue] sub/pl.b line 26
    Row(0,array(1,0,"Turnover - continuing operations"),array(2,0,XrefSt('N1X',4)),array(3,1,3002),array(4,1,3002));
    # [row b:PLExps:ExpenseType.CoS ul] sub/pl.b line 27
    Row(array('3050,1319'),array(1,20),array(3,4,0,4),array(4,4,0,4));
    # [row b:PLExps.ProfitTaxTotals.GrossProfit] sub/pl.b line 28
    Row(array(3235),array(1,20),array(3,4),array(4,4));
    # [row b:PLExps:ExpenseType.Distrib rc:asp1] sub/pl.b line 29
    Row(array('3050,1320',0,0,0,0,0,'asp1'),array(1,20),array(3,4),array(4,4));
    # [row b:PLExps:ExpenseType.Admin ul] sub/pl.b line 30
    Row(array('3050,1321'),array(1,20),array(3,4,0,4),array(4,4,0,4));
    # [col "Operating profit - continuing operations"] [col 3,4 PLExps.ProfitTaxTotals.OperatingProfit][col n [xref link N2X]] sub/pl.b line 31
    Row(0,array(1,0,"Operating profit - continuing operations"),array(2,0,XrefSt('N2X',4)),array(3,1,3236),array(4,1,3236));
    # [line] sub/pl.b line 35
    Lines();
  # [end] sub/pl.b line 40
  TableEnd();
  # [h2 c:c "Profit and Loss Using Subtotals, Totals and Above Underlines"[nl]"for the Period Ended " [date f BRI.DatesPeriods.End]] sub/pl.b line 42
  H(2,"Profit and Loss Using Subtotals, Totals and Above Underlines".'<br/>'."for the Period Ended ".DateStrWithTag(8312),'c');
  # [line] sub/pl.b line 43
  Lines();
  # [table cols:dn01 center] sub/pl.b line 44
  TableStart('dn01',1);
    # [col2 "Note"] [col 3,4 c:b restatedHdg BRI.DatesPeriods.End] sub/pl.b line 45
    Row(0,array(2,0,"Note"),array(3,1,8312,0,'b'),array(4,1,8312,4096,'b'));
    # [col 3,4 c:b "£"] sub/pl.b line 46
    Row(0,array(3,0,"£",0,'b'),array(4,0,"£",0,'b'));
    # [col "Turnover - continuing operations"][col [xref link N1X]] [col y0,, PLRev.TradingIncome.TurnoverGrossOpRevenue] sub/pl.b line 47
    Row(0,array(1,0,"Turnover - continuing operations"),array(2,0,XrefSt('N1X',4)),array(3,1,3002),array(4,1,3002));
    # [row b:PLExps:ExpenseType.CoS] sub/pl.b line 48
    Row(array('3050,1319'),array(1,20),array(3,4),array(4,4));
    # [row b:PLExps.ProfitTaxTotals.GrossProfit subtotal aul] sub/pl.b line 49
    Row(array(3235,256),array(1,20),array(3,36,0,16),array(4,36,0,16));
    # [row b:PLExps:ExpenseType.Distrib rc:asp1] sub/pl.b line 50
    Row(array('3050,1320',0,0,0,0,0,'asp1'),array(1,20),array(3,4),array(4,4));
    # [row b:PLExps:ExpenseType.Admin] sub/pl.b line 51
    Row(array('3050,1321'),array(1,20),array(3,4),array(4,4));
    # [row b:PLExps.ProfitTaxTotals.OperatingProfit subtotal aul][col "Operating profit - continuing operations"][col n [xref link N2X]] sub/pl.b line 52
    Row(array(3236,256),array(1,0,"Operating profit - continuing operations"),array(2,0,XrefSt('N2X',4)),array(3,36,0,16),array(4,36,0,16));
    # [line] sub/pl.b line 56
    Lines();
  # [end] sub/pl.b line 60
  TableEnd();
  # [if STRGL.StatementThatThereWereNoGainsInPeriodOtherThanThoseInPL][p c:c "There were no recognised gains or losses other than the profit for the financial year."] sub/pl.b line 61
  if (Data(5430)) {P("There were no recognised gains or losses other than the profit for the financial year.",'c');}
  # [np] sub/pl.b line 127
  NewPage();
  # [h2 c:c "Alternative text Profit and Loss Test"[nl]"for the Period Ended " [date f BRI.DatesPeriods.End]] sub/pl.b line 128
  H(2,"Alternative text Profit and Loss Test".'<br/>'."for the Period Ended ".DateStrWithTag(8312),'c');
  # [table cols:dn01 center] sub/pl.b line 130
  TableStart('dn01',1);
    # [col3 restatedHdg [date y BRI.DatesPeriods.End]][col4 restatedHdg [date y y:1 BRI.DatesPeriods.End]] sub/pl.b line 131
    Row(0,array(3,0,DateStrWithTag(8312,3)),array(4,0,DateStrWithTag(8312,3,1),4096));
    # [col3,4 c:b "£"] sub/pl.b line 132
    Row(0,array(3,0,"£",0,'b'),array(4,0,"£",0,'b'));
    # [col1 'TURNOVER'] [col3 -16613552][col 4 -19195013] sub/pl.b line 133
    Row(0,array(1,0,'TURNOVER'),array(3,0,-16613552),array(4,0,-19195013));
    # [line] sub/pl.b line 134
    Lines();
    # [row dr][col1 'Cost of sales'] [col3 14651380][col 4 16666490] sub/pl.b line 135
    Row(0,array(1,0,'Cost of sales'),array(3,0,14651380,1),array(4,0,16666490,1));
    # [row subtotal aul][col 'GROSS PROFIT/(LOSS)'] sub/pl.b line 136
    Row(array(0,256),array(1,0,'GROSS PROFIT/(LOSS)'),array(3,32,0,16),array(4,32,0,16));
    # [line] sub/pl.b line 137
    Lines();
    # [row dr][col 'Distribution costs'] [col3 221780][col 4 260471] sub/pl.b line 138
    Row(0,array(1,0,'Distribution costs'),array(3,0,221780,1),array(4,0,260471,1));
    # [row dr][col 'Administrative expenses'] [col3 887119][col 4 1057994] sub/pl.b line 139
    Row(0,array(1,0,'Administrative expenses'),array(3,0,887119,1),array(4,0,1057994,1));
    # [row dr][col 'Test 0 row - should not appear'] [col 3 0][col 4 0] sub/pl.b line 140
    Row(0,array(1,0,'Test 0 row - should not appear'),array(3,0,0,1),array(4,0,0,1));
    # [row dr keep][col 'Test 0 row with keep - should appear'] [col 3 0][col 4 0] sub/pl.b line 141
    Row(array(0,64),array(1,0,'Test 0 row with keep - should appear'),array(3,0,0,1),array(4,0,0,1));
    # [row dr keepHide][col 'Test 0 row with keepHide - should be hidden'][col 3 0][col 4 0] sub/pl.b line 142
    Row(array(0,128),array(1,0,'Test 0 row with keepHide - should be hidden'),array(3,0,0,1),array(4,0,0,1));
    # [row total aul][col 'OPERATING PROFIT/(LOSS)'] sub/pl.b line 143
    Row(array(0,576),array(1,0,'OPERATING PROFIT/(LOSS)'),array(3,32,0,16),array(4,32,0,16));
    # [line] sub/pl.b line 144
    Lines();
    # [col 'Attributable to:'] sub/pl.b line 145
    Row(0,array(1,0,'Attributable to:'));
    # [col c:pl 'Operating profit before exceptional items'] [col3 -1709673][col 4 -1210058] sub/pl.b line 146
    Row(0,array(1,0,'Operating profit before exceptional items',0,'pl'),array(3,0,-1709673),array(4,0,-1210058));
    # [col c:pl 'Exceptional items'] [col 3 856400][col 4 0] sub/pl.b line 147
    Row(0,array(1,0,'Exceptional items',0,'pl'),array(3,0,856400),array(4));
    # [row subtotal aul] sub/pl.b line 148
    Row(array(0,256),array(3,32,0,16),array(4,32,0,16));
    # [line] sub/pl.b line 149
    Lines();
    # [col 'Loss on disposal of discontinued operations'][col 3 600000][col4 0] sub/pl.b line 150
    Row(0,array(1,0,'Loss on disposal of discontinued operations'),array(3,0,600000),array(4));
    # [col 'Cost of restructuring the company'] [col 3 0][col 4 1100000] sub/pl.b line 151
    Row(0,array(1,0,'Cost of restructuring the company'),array(3),array(4,0,1100000));
    # [row subtotal aul] sub/pl.b line 152
    Row(array(0,256),array(3,32,0,16),array(4,32,0,16));
    # [line] sub/pl.b line 153
    Lines();
    # [row subtotal aul n:PL.OrdinaryActivities][col 'PROFIT/(LOSS) ON ORDINARY ACTIVITIES BEFORE TAXATION'] sub/pl.b line 157
    Row(array(0,256,1),array(1,0,'PROFIT/(LOSS) ON ORDINARY ACTIVITIES BEFORE TAXATION'),array(3,32,0,16),array(4,32,0,16));
    # [line] sub/pl.b line 158
    Lines();
    # [row dr alt:PL.OrdinaryActivities][col 'Tax on profit/(loss) on ordinary activities'][col3 ul 212422][col ul 337173] sub/pl.b line 159
    Row(array(0,0,0,0,0,0,0,1),array(1,0,'Tax on profit/(loss) on ordinary activities'),array(3,0,212422,5),array(4,0,337173,5));
    # [row subtotal aul][col 'PROFIT/(LOSS) ON ORDINARY ACTIVITIES AFTER TAXATION'] sub/pl.b line 160
    Row(array(0,256),array(1,0,'PROFIT/(LOSS) ON ORDINARY ACTIVITIES AFTER TAXATION'),array(3,32,0,16),array(4,32,0,16));
    # [line] sub/pl.b line 161
    Lines();
    # [row dr][col 'Extraordinary items'] [col 3 0][col 4 -500000] sub/pl.b line 162
    Row(0,array(1,0,'Extraordinary items'),array(3,0,0,1),array(4,0,-500000,1));
    # [row total aul dul rc:b][col '(LOSS)/PROFIT FOR THE FINANCIAL YEAR'] sub/pl.b line 163
    Row(array(0,576,0,0,0,0,'b'),array(1,0,'(LOSS)/PROFIT FOR THE FINANCIAL YEAR'),array(3,32,0,24),array(4,32,0,24));
  # [end] sub/pl.b line 164
  TableEnd();
  # [np] sub/pl.b line 166
  NewPage();
  # [h2 c:c "Alternative text Profit and Loss Test 4 Column Layout"[nl]"for the Period Ended " [date f BRI.DatesPeriods.End]] sub/pl.b line 167
  H(2,"Alternative text Profit and Loss Test 4 Column Layout".'<br/>'."for the Period Ended ".DateStrWithTag(8312),'c');
  # [p c:c 'This test references row PL.OrdinaryActivities of the 2 column table for the alt: balances for the tax row.'] sub/pl.b line 168
  P('This test references row PL.OrdinaryActivities of the 2 column table for the alt: balances for the tax row.','c');
  # [table cols:dn0011 center] sub/pl.b line 169
  TableStart('dn0011',1);
    # [col y01,y02,y11,y12 c:b restatedHdg BRI.DatesPeriods.End] sub/pl.b line 170
    Row(0,array(3,1,8312,0,'b'),array(4,1,8312,0,'b'),array(5,1,8312,4096,'b'),array(6,1,8312,4096,'b'));
    # [col y01,,,, c:b "£"] sub/pl.b line 171
    Row(0,array(3,0,"£",0,'b'),array(4,0,"£",0,'b'),array(5,0,"£",0,'b'),array(6,0,"£",0,'b'));
    # [col 'TURNOVER'] [col y02 -16613552][col y12 -19195013] sub/pl.b line 172
    Row(0,array(1,0,'TURNOVER'),array(4,0,-16613552),array(6,0,-19195013));
    # [row dr][col 'Cost of sales'] [col y02 14651380][col y12 16666490] sub/pl.b line 173
    Row(0,array(1,0,'Cost of sales'),array(4,0,14651380,1),array(6,0,16666490,1));
    # [row subtotal aul cols:y02,y12][col 'GROSS PROFIT/(LOSS)'] sub/pl.b line 174
    Row(array(0,256),array(1,0,'GROSS PROFIT/(LOSS)'),array(4,32,0,16),array(6,32,0,16));
    # [line] sub/pl.b line 175
    Lines();
    # [row dr][col 'Distribution costs'] [col y01 221780][col y11 260471] sub/pl.b line 176
    Row(0,array(1,0,'Distribution costs'),array(3,0,221780,1),array(5,0,260471,1));
    # [row dr][col 'Administrative expenses'] [col y01 887119][col y11 1057994] sub/pl.b line 177
    Row(0,array(1,0,'Administrative expenses'),array(3,0,887119,1),array(5,0,1057994,1));
    # [row subtotal aul cols:y01->2,y11->2] sub/pl.b line 178
    Row(array(0,256,0,'3,5','4,6'),array(3,32,0,16),array(5,32,0,16));
    # [row total aul cols:y02,y12][col 'OPERATING PROFIT/(LOSS)'] sub/pl.b line 179
    Row(array(0,576),array(1,0,'OPERATING PROFIT/(LOSS)'),array(4,32,0,16),array(6,32,0,16));
    # [line] sub/pl.b line 180
    Lines();
    # [col 'Attributable to:'] sub/pl.b line 181
    Row(0,array(1,0,'Attributable to:'));
    # [col c:pl 'Operating profit before exceptional items'] [col y01 -1709673][col y11 -1210058] sub/pl.b line 182
    Row(0,array(1,0,'Operating profit before exceptional items',0,'pl'),array(3,0,-1709673),array(5,0,-1210058));
    # [col c:pl 'Exceptional items'] [col y01 856400][col y11 0] sub/pl.b line 183
    Row(0,array(1,0,'Exceptional items',0,'pl'),array(3,0,856400),array(5));
    # [row subtotal aul cols:y01->2,y11->2][col 'OPERATING PROFIT/(LOSS)'] sub/pl.b line 184
    Row(array(0,256,0,'3,5','4,6'),array(1,0,'OPERATING PROFIT/(LOSS)'),array(3,32,0,16),array(5,32,0,16));
    # [line] sub/pl.b line 185
    Lines();
    # [col 'Loss on disposal of discontinued operations'][col y01 600000][col y11 0] sub/pl.b line 186
    Row(0,array(1,0,'Loss on disposal of discontinued operations'),array(3,0,600000),array(5));
    # [col 'Cost of restructuring the company'] [col y01 0][col y11 1100000] sub/pl.b line 187
    Row(0,array(1,0,'Cost of restructuring the company'),array(3),array(5,0,1100000));
    # [row subtotal aul cols:y01->2,y11->2] sub/pl.b line 188
    Row(array(0,256,0,'3,5','4,6'),array(3,32,0,16),array(5,32,0,16));
    # [row subtotal aul cols:y02,y12] sub/pl.b line 189
    Row(array(0,256),array(4,32,0,16),array(6,32,0,16));
    # [line] sub/pl.b line 190
    Lines();
    # [row subtotal aul cols:y01->2,y11->2] sub/pl.b line 194
    Row(array(0,256,0,'3,5','4,6'),array(3,32,0,16),array(5,32,0,16));
    # [row subtotal aul cols:y02,y12][col 'PROFIT/(LOSS) ON ORDINARY ACTIVITIES BEFORE TAXATION'] sub/pl.b line 195
    Row(array(0,256),array(1,0,'PROFIT/(LOSS) ON ORDINARY ACTIVITIES BEFORE TAXATION'),array(4,32,0,16),array(6,32,0,16));
    # [line] sub/pl.b line 196
    Lines();
    # [row dr alt:PL.OrdinaryActivities][col 'Tax on profit/(loss) on ordinary activities'][col y02 ul 212422][col y12 337173] sub/pl.b line 197
    Row(array(0,0,0,0,0,0,0,1),array(1,0,'Tax on profit/(loss) on ordinary activities'),array(4,0,212422,5),array(6,0,337173,1));
    # [row subtotal aul cols:y02,y12][col 'PROFIT/(LOSS) ON ORDINARY ACTIVITIES AFTER TAXATION'] sub/pl.b line 198
    Row(array(0,256),array(1,0,'PROFIT/(LOSS) ON ORDINARY ACTIVITIES AFTER TAXATION'),array(4,32,0,16),array(6,32,0,16));
    # [line] sub/pl.b line 199
    Lines();
    # [row dr][col 'Extraordinary items'] [col y02 0][col y12 -500000] sub/pl.b line 200
    Row(0,array(1,0,'Extraordinary items'),array(4,0,0,1),array(6,0,-500000,1));
    # [row total aul dul rc:b cols:y02,y12][col '(LOSS)/PROFIT FOR THE FINANCIAL YEAR'] sub/pl.b line 201
    Row(array(0,576,0,0,0,0,'b'),array(1,0,'(LOSS)/PROFIT FOR THE FINANCIAL YEAR'),array(4,32,0,24),array(6,32,0,24));
  # [end] sub/pl.b line 202
  TableEnd();
  # [np] sub/pl.b line 204
  NewPage();
  # [h2 c:c "Alternative text Profit and Loss Test 3 Column Layout"[nl]"for the Period Ended " [date f BRI.DatesPeriods.End]] sub/pl.b line 205
  H(2,"Alternative text Profit and Loss Test 3 Column Layout".'<br/>'."for the Period Ended ".DateStrWithTag(8312),'c');
  # [p c:c 'This test references row PL.OrdinaryActivities of the 2 column table for the alt: balances for the tax row.'] sub/pl.b line 206
  P('This test references row PL.OrdinaryActivities of the 2 column table for the alt: balances for the tax row.','c');
  # [table cols:dn001 center] sub/pl.b line 207
  TableStart('dn001',1);
    # [col y01,y02,y11 c:b restatedHdg BRI.DatesPeriods.End] sub/pl.b line 208
    Row(0,array(3,1,8312,0,'b'),array(4,1,8312,0,'b'),array(5,1,8312,4096,'b'));
    # [col y01,,, c:b "£"] sub/pl.b line 209
    Row(0,array(3,0,"£",0,'b'),array(4,0,"£",0,'b'),array(5,0,"£",0,'b'));
    # [col 'TURNOVER'] [col y02 -16613552][col y1 -19195013] sub/pl.b line 210
    Row(0,array(1,0,'TURNOVER'),array(4,0,-16613552),array(5,0,-19195013));
    # [row dr][col 'Cost of sales'] [col y02 14651380][col y1 16666490] sub/pl.b line 211
    Row(0,array(1,0,'Cost of sales'),array(4,0,14651380,1),array(5,0,16666490,1));
    # [row subtotal aul cols:y02,y1][col 'GROSS PROFIT/(LOSS)'] sub/pl.b line 212
    Row(array(0,256),array(1,0,'GROSS PROFIT/(LOSS)'),array(4,32,0,16),array(5,32,0,16));
    # [line] sub/pl.b line 213
    Lines();
    # [row dr][col 'Distribution costs'] [col y01 221780][col y11 260471] sub/pl.b line 214
    Row(0,array(1,0,'Distribution costs'),array(3,0,221780,1),array(5,0,260471,1));
    # [row dr][col 'Administrative expenses'] [col y01 887119][col y11 1057994] sub/pl.b line 215
    Row(0,array(1,0,'Administrative expenses'),array(3,0,887119,1),array(5,0,1057994,1));
    # [row subtotal aul cols:y01->2,y11] sub/pl.b line 216
    Row(array(0,256,0,'3,5','4,5'),array(3,32,0,16),array(5,32,0,16));
    # [row total aul cols:y02,y1][col 'OPERATING PROFIT/(LOSS)'] sub/pl.b line 217
    Row(array(0,576),array(1,0,'OPERATING PROFIT/(LOSS)'),array(4,32,0,16),array(5,32,0,16));
    # [line] sub/pl.b line 218
    Lines();
    # [col 'Attributable to:'] sub/pl.b line 219
    Row(0,array(1,0,'Attributable to:'));
    # [col c:pl 'Operating profit before exceptional items'] [col y01 -1709673][col y11 -1210058] sub/pl.b line 220
    Row(0,array(1,0,'Operating profit before exceptional items',0,'pl'),array(3,0,-1709673),array(5,0,-1210058));
    # [col c:pl 'Exceptional items'] [col y01 856400][col y11 0] sub/pl.b line 221
    Row(0,array(1,0,'Exceptional items',0,'pl'),array(3,0,856400),array(5));
    # [row subtotal aul cols:y01->2,y11][col 'OPERATING PROFIT/(LOSS)'] sub/pl.b line 222
    Row(array(0,256,0,'3,5','4,5'),array(1,0,'OPERATING PROFIT/(LOSS)'),array(3,32,0,16),array(5,32,0,16));
    # [line] sub/pl.b line 225
    Lines();
    # [col 'Loss on disposal of discontinued operations'][col y01 600000][col y11 0] sub/pl.b line 226
    Row(0,array(1,0,'Loss on disposal of discontinued operations'),array(3,0,600000),array(5));
    # [col 'Cost of restructuring the company'] [col y01 0][col y11 1100000] sub/pl.b line 227
    Row(0,array(1,0,'Cost of restructuring the company'),array(3),array(5,0,1100000));
    # [row subtotal aul cols:y01->2,y11] sub/pl.b line 228
    Row(array(0,256,0,'3,5','4,5'),array(3,32,0,16),array(5,32,0,16));
    # [row subtotal aul cols:y02,y1] sub/pl.b line 229
    Row(array(0,256),array(4,32,0,16),array(5,32,0,16));
    # [line] sub/pl.b line 230
    Lines();
    # [row subtotal aul cols:y01->2,y11] sub/pl.b line 234
    Row(array(0,256,0,'3,5','4,5'),array(3,32,0,16),array(5,32,0,16));
    # [row subtotal aul cols:y02,y1][col 'PROFIT/(LOSS) ON ORDINARY ACTIVITIES BEFORE TAXATION'] sub/pl.b line 235
    Row(array(0,256),array(1,0,'PROFIT/(LOSS) ON ORDINARY ACTIVITIES BEFORE TAXATION'),array(4,32,0,16),array(5,32,0,16));
    # [line] sub/pl.b line 236
    Lines();
    # [row dr alt:PL.OrdinaryActivities][col 'Tax on profit/(loss) on ordinary activities'][col y02 ul 212422][col y1 337173] sub/pl.b line 237
    Row(array(0,0,0,0,0,0,0,1),array(1,0,'Tax on profit/(loss) on ordinary activities'),array(4,0,212422,5),array(5,0,337173,1));
    # [row subtotal aul cols:y02,y1][col 'PROFIT/(LOSS) ON ORDINARY ACTIVITIES AFTER TAXATION'] sub/pl.b line 238
    Row(array(0,256),array(1,0,'PROFIT/(LOSS) ON ORDINARY ACTIVITIES AFTER TAXATION'),array(4,32,0,16),array(5,32,0,16));
    # [line] sub/pl.b line 239
    Lines();
    # [row dr][col 'Extraordinary items'] [col y02 0][col y1 -500000] sub/pl.b line 240
    Row(0,array(1,0,'Extraordinary items'),array(4,0,0,1),array(5,0,-500000,1));
    # [row total aul dul rc:b cols:y02,y1][col '(LOSS)/PROFIT FOR THE FINANCIAL YEAR'] sub/pl.b line 241
    Row(array(0,576,0,0,0,0,'b'),array(1,0,'(LOSS)/PROFIT FOR THE FINANCIAL YEAR'),array(4,32,0,24),array(5,32,0,24));
  # [end] sub/pl.b line 242
  TableEnd();
  # [np] sub/pl.b line 245
  NewPage();
  # [h2 c:c "Alternative text Profit and Loss Test 4 Column Layout with col 3 unused apart from headings to test auto column suppression"[nl]"for the Period Ended " [date f BRI.DatesPeriods.End]] sub/pl.b line 246
  H(2,"Alternative text Profit and Loss Test 4 Column Layout with col 3 unused apart from headings to test auto column suppression".'<br/>'."for the Period Ended ".DateStrWithTag(8312),'c');
  # [p c:c 'This test references row PL.OrdinaryActivities of the 2 column table for the alt: balances for the tax row.'] sub/pl.b line 247
  P('This test references row PL.OrdinaryActivities of the 2 column table for the alt: balances for the tax row.','c');
  # [table cols:dn0011 center] sub/pl.b line 248
  TableStart('dn0011',1);
    # [col y01,y02,y11,y12 c:b restatedHdg BRI.DatesPeriods.End] sub/pl.b line 249
    Row(0,array(3,1,8312,0,'b'),array(4,1,8312,0,'b'),array(5,1,8312,4096,'b'),array(6,1,8312,4096,'b'));
    # [col y01,,,, c:b "£"] sub/pl.b line 250
    Row(0,array(3,0,"£",0,'b'),array(4,0,"£",0,'b'),array(5,0,"£",0,'b'),array(6,0,"£",0,'b'));
    # [col1 'TURNOVER'] [col y02 -16613552][col y12 -19195013] sub/pl.b line 251
    Row(0,array(1,0,'TURNOVER'),array(4,0,-16613552),array(6,0,-19195013));
    # [row dr][col1 'Cost of sales'] [col y02 14651380][col y12 16666490] sub/pl.b line 252
    Row(0,array(1,0,'Cost of sales'),array(4,0,14651380,1),array(6,0,16666490,1));
    # [row subtotal aul cols:y02,y12][col 'GROSS PROFIT/(LOSS)'] sub/pl.b line 253
    Row(array(0,256),array(1,0,'GROSS PROFIT/(LOSS)'),array(4,32,0,16),array(6,32,0,16));
    # [line] sub/pl.b line 254
    Lines();
    # [row dr][col 'Distribution costs'] [col y01 221780][col y12 260471] sub/pl.b line 255
    Row(0,array(1,0,'Distribution costs'),array(3,0,221780,1),array(6,0,260471,1));
    # [row dr][col 'Administrative expenses'] [col y01 887119][col y12 1057994] sub/pl.b line 256
    Row(0,array(1,0,'Administrative expenses'),array(3,0,887119,1),array(6,0,1057994,1));
    # [row subtotal aul cols:y01->2,y12] sub/pl.b line 257
    Row(array(0,256,0,'3,6','4,6'),array(3,32,0,16),array(6,32,0,16));
    # [row total aul cols:y02,y12][col 'OPERATING PROFIT/(LOSS)'] sub/pl.b line 258
    Row(array(0,576),array(1,0,'OPERATING PROFIT/(LOSS)'),array(4,32,0,16),array(6,32,0,16));
    # [line] sub/pl.b line 259
    Lines();
    # [col 'Attributable to:'] sub/pl.b line 260
    Row(0,array(1,0,'Attributable to:'));
    # [col c:pl 'Operating profit before exceptional items'] [col y01 -1709673][col y12 -1210058] sub/pl.b line 261
    Row(0,array(1,0,'Operating profit before exceptional items',0,'pl'),array(3,0,-1709673),array(6,0,-1210058));
    # [col c:pl 'Exceptional items'] [col y01 856400][col y12 0] sub/pl.b line 262
    Row(0,array(1,0,'Exceptional items',0,'pl'),array(3,0,856400),array(6));
    # [row subtotal aul cols:y01->2,y12][col 'OPERATING PROFIT/(LOSS)'] sub/pl.b line 263
    Row(array(0,256,0,'3,6','4,6'),array(1,0,'OPERATING PROFIT/(LOSS)'),array(3,32,0,16),array(6,32,0,16));
    # [line] sub/pl.b line 264
    Lines();
    # [col 'Loss on disposal of discontinued operations'][col y01 600000][col y12 0] sub/pl.b line 265
    Row(0,array(1,0,'Loss on disposal of discontinued operations'),array(3,0,600000),array(6));
    # [col 'Cost of restructuring the company'] [col y01 0][col y12 1100000] sub/pl.b line 266
    Row(0,array(1,0,'Cost of restructuring the company'),array(3),array(6,0,1100000));
    # [row subtotal aul cols:y01->2,y12] sub/pl.b line 267
    Row(array(0,256,0,'3,6','4,6'),array(3,32,0,16),array(6,32,0,16));
    # [row subtotal aul cols:y02,y12] sub/pl.b line 268
    Row(array(0,256),array(4,32,0,16),array(6,32,0,16));
    # [line] sub/pl.b line 269
    Lines();
    # [row subtotal aul cols:y01->2,y12] sub/pl.b line 273
    Row(array(0,256,0,'3,6','4,6'),array(3,32,0,16),array(6,32,0,16));
    # [row subtotal aul cols:y02,y12][col 'PROFIT/(LOSS) ON ORDINARY ACTIVITIES BEFORE TAXATION'] sub/pl.b line 274
    Row(array(0,256),array(1,0,'PROFIT/(LOSS) ON ORDINARY ACTIVITIES BEFORE TAXATION'),array(4,32,0,16),array(6,32,0,16));
    # [line] sub/pl.b line 275
    Lines();
    # [row dr alt:PL.OrdinaryActivities][col 'Tax on profit/(loss) on ordinary activities'][col y02 ul 212422][col y12 337173] sub/pl.b line 276
    Row(array(0,0,0,0,0,0,0,1),array(1,0,'Tax on profit/(loss) on ordinary activities'),array(4,0,212422,5),array(6,0,337173,1));
    # [row subtotal aul cols:y02,y12][col 'PROFIT/(LOSS) ON ORDINARY ACTIVITIES AFTER TAXATION'] sub/pl.b line 277
    Row(array(0,256),array(1,0,'PROFIT/(LOSS) ON ORDINARY ACTIVITIES AFTER TAXATION'),array(4,32,0,16),array(6,32,0,16));
    # [line] sub/pl.b line 278
    Lines();
    # [row dr][col 'Extraordinary items'] [col y02 0][col y12 -500000] sub/pl.b line 279
    Row(0,array(1,0,'Extraordinary items'),array(4,0,0,1),array(6,0,-500000,1));
    # [row total aul dul rc:b cols:y02,y12][col '(LOSS)/PROFIT FOR THE FINANCIAL YEAR'] sub/pl.b line 280
    Row(array(0,576,0,0,0,0,'b'),array(1,0,'(LOSS)/PROFIT FOR THE FINANCIAL YEAR'),array(4,32,0,24),array(6,32,0,24));
  # [end] sub/pl.b line 281
  TableEnd();
  # [np] sub/pl.b line 283
  NewPage();
  # [h2 c:c "Alternative text Profit and Loss Test (Part)"] sub/pl.b line 284
  H(2,"Alternative text Profit and Loss Test (Part)",'c');
  # [p c:c "4 Column Layout with col 3 unused apart from headings and with col 1 used only for single rows to test subtotal moving such balances to the 'to' column, which should result in the suppression of both columns 1 and 3"[nl] 'Plus cell reference tests including to the moved cell'] sub/pl.b line 285
  P("4 Column Layout with col 3 unused apart from headings and with col 1 used only for single rows to test subtotal moving such balances to the 'to' column, which should result in the suppression of both columns 1 and 3".'<br/>'.'Plus cell reference tests including to the moved cell','c');
  # [table cols:d0011 center] row # sub/pl.b line 286
  TableStart('d0011',1);
    # [col y01,y02,y11,y12 c:b restatedHdg BRI.DatesPeriods.End] 0 sub/pl.b line 287
    Row(0,array(2,1,8312,0,'b'),array(3,1,8312,0,'b'),array(4,1,8312,4096,'b'),array(5,1,8312,4096,'b'));
    # [col y01,,,, c:b "£"] 1 sub/pl.b line 288
    Row(0,array(2,0,"£",0,'b'),array(3,0,"£",0,'b'),array(4,0,"£",0,'b'),array(5,0,"£",0,'b'));
    # [row b:PLRev.TradingIncome.TurnoverGrossOpRevenue n:R.PLRev.TradingIncome.TurnoverGrossOpRevenue cols:y02,y12] 2 sub/pl.b line 289
    Row(array(3002,0,2),array(1,20),array(3,4),array(5,4));
    # [row n:R.WithMovedCell][col 'Cost of restructuring the company'][col y01 1][col y12 1100000] 3 sub/pl.b line 290
    Row(array(0,0,3),array(1,0,'Cost of restructuring the company'),array(2,0,1),array(5,0,1100000));
    # [row subtotal aul cols:y01->2,y12] 4 sub/pl.b line 291
    Row(array(0,256,0,'2,5','3,5'),array(2,32,0,16),array(5,32,0,16));
    # [row subtotal aul cols:y02,y12] 5 sub/pl.b line 292
    Row(array(0,256),array(3,32,0,16),array(5,32,0,16));
    # [line] 6 sub/pl.b line 293
    Lines();
    # [row subtotal aul cols:y01->2,y12] 9 sub/pl.b line 296
    Row(array(0,256,0,'2,5','3,5'),array(2,32,0,16),array(5,32,0,16));
    # [row subtotal aul cols:y02,y12 n:PL.OrdinaryActivitiesT42][col 'PROFIT/(LOSS) ON ORDINARY ACTIVITIES BEFORE TAXATION'] 10 sub/pl.b line 297
    Row(array(0,256,4),array(1,0,'PROFIT/(LOSS) ON ORDINARY ACTIVITIES BEFORE TAXATION'),array(3,32,0,16),array(5,32,0,16));
    # [line] 11 sub/pl.b line 298
    Lines();
    # [row dr alt:PL.OrdinaryActivitiesT42][col 'Tax on profit/(loss) on ordinary activities'][col y02 ul 212422][col y12 337173] 12 sub/pl.b line 299
    Row(array(0,0,0,0,0,0,0,4),array(1,0,'Tax on profit/(loss) on ordinary activities'),array(3,0,212422,5),array(5,0,337173,1));
    # [row subtotal aul cols:y02,y12][col 'PROFIT/(LOSS) ON ORDINARY ACTIVITIES AFTER TAXATION'] 13 sub/pl.b line 300
    Row(array(0,256),array(1,0,'PROFIT/(LOSS) ON ORDINARY ACTIVITIES AFTER TAXATION'),array(3,32,0,16),array(5,32,0,16));
    # [line] 14 sub/pl.b line 301
    Lines();
    # [row dr][col 'Extraordinary items'] [col y02 0][col y12 -500000] 15 sub/pl.b line 302
    Row(0,array(1,0,'Extraordinary items'),array(3,0,0,1),array(5,0,-500000,1));
    # [row total aul dul rc:b cols:y02,y12][col '(LOSS)/PROFIT FOR THE FINANCIAL YEAR'] 16 sub/pl.b line 303
    Row(array(0,576,0,0,0,0,'b'),array(1,0,'(LOSS)/PROFIT FOR THE FINANCIAL YEAR'),array(3,32,0,24),array(5,32,0,24));
    # [col PL.OrdinaryActivitiesT42:d][col y02 keep PL.OrdinaryActivitiesT42:y02] sub/pl.b line 304
    Row(array(0,1024),array(1,64,0,1024),array(3,64,1,1088));
    # [col R.PLRev.TradingIncome.TurnoverGrossOpRevenue:d " times 2"][col y02 2*R.PLRev.TradingIncome.TurnoverGrossOpRevenue:y02] sub/pl.b line 305
    Row(array(0,1024),array(1,64,2,1024),array(3,64,3,1024));
    # [col 'Reference to R.WithMovedCell:y01 which the subtotal moves to y:02'][col y02 R.WithMovedCell:y01] sub/pl.b line 306
    Row(array(0,1024),array(1,0,'Reference to R.WithMovedCell:y01 which the subtotal moves to y:02'),array(3,64,4,1024));
  # [end] sub/pl.b line 307
  TableEnd();
  # [p R.PLRev.TradingIncome.TurnoverGrossOpRevenue:d " from R.PLRev.TradingIncome.TurnoverGrossOpRevenue:d in a p statement"] sub/pl.b line 308
  P(CellDatWithTag(2,1)." from R.PLRev.TradingIncome.TurnoverGrossOpRevenue:d in a p statement");
  # [p ""R.PLRev.TradingIncome.TurnoverGrossOpRevenue:y02 " from R.PLRev.TradingIncome.TurnoverGrossOpRevenue:y02 in a p statement"] sub/pl.b line 309
  P("".CellDatWithTag(2,3)." from R.PLRev.TradingIncome.TurnoverGrossOpRevenue:y02 in a p statement");
  # [p ""R.PLRev.TradingIncome.TurnoverGrossOpRevenue:y12 " from R.PLRev.TradingIncome.TurnoverGrossOpRevenue:y12 in a p statement"] sub/pl.b line 310
  P("".CellDatWithTag(2,5)." from R.PLRev.TradingIncome.TurnoverGrossOpRevenue:y12 in a p statement");
  # [p R.PLRev.TradingIncome.TurnoverGrossOpRevenue:d" "R.PLRev.TradingIncome.TurnoverGrossOpRevenue:y02" "R.PLRev.TradingIncome.TurnoverGrossOpRevenue:y12 " from all three in a p statement"] sub/pl.b line 311
  P(CellDatWithTag(2,1)." ".CellDatWithTag(2,3)." ".CellDatWithTag(2,5)." from all three in a p statement");
  # [table cols:dn010 center] row # sub/pl.b line 313
  TableStart('dn010',1);
    # [col y01,y11 c:b restatedHdg BRI.DatesPeriods.End] 0 sub/pl.b line 314
    Row(0,array(3,1,8312,0,'b'),array(4,1,8312,4096,'b'));
    # [col y01,, c:b "£"] 1 sub/pl.b line 315
    Row(0,array(3,0,"£",0,'b'),array(4,0,"£",0,'b'));
    # [row b:PLRev.TradingIncome.TurnoverGrossOpRevenue] 2 sub/pl.b line 316
    Row(array(3002),array(1,20),array(3,4),array(4,4),array(5,4));
    # [col 'Cost of restructuring the company'] [col y0 0][col y1 1100000] 3 sub/pl.b line 317
    Row(0,array(1,0,'Cost of restructuring the company'),array(3),array(4,0,1100000));
    # [row subtotal aul] 4 sub/pl.b line 318
    Row(array(0,256),array(3,32,0,16),array(4,32,0,16),array(5,32,0,16));
    # [row subtotal aul] 5 sub/pl.b line 319
    Row(array(0,256),array(3,32,0,16),array(4,32,0,16),array(5,32,0,16));
    # [line] 6 sub/pl.b line 320
    Lines();
    # [row subtotal aul] 9 sub/pl.b line 323
    Row(array(0,256),array(3,32,0,16),array(4,32,0,16),array(5,32,0,16));
    # [row subtotal aul][col 'PROFIT/(LOSS) ON ORDINARY ACTIVITIES BEFORE TAXATION'] 10 sub/pl.b line 324
    Row(array(0,256),array(1,0,'PROFIT/(LOSS) ON ORDINARY ACTIVITIES BEFORE TAXATION'),array(3,32,0,16),array(4,32,0,16),array(5,32,0,16));
    # [line] 11 sub/pl.b line 325
    Lines();
    # [row dr alt:PL.OrdinaryActivitiesT42][col 'Tax on profit/(loss) on ordinary activities'][col y0 ul 212422][col y1 337173] 12 sub/pl.b line 326
    Row(array(0,0,0,0,0,0,0,4),array(1,0,'Tax on profit/(loss) on ordinary activities'),array(3,0,212422,5),array(4,0,337173,1));
    # [row subtotal aul][col 'PROFIT/(LOSS) ON ORDINARY ACTIVITIES AFTER TAXATION'] 13 sub/pl.b line 327
    Row(array(0,256),array(1,0,'PROFIT/(LOSS) ON ORDINARY ACTIVITIES AFTER TAXATION'),array(3,32,0,16),array(4,32,0,16),array(5,32,0,16));
    # [line] 14 sub/pl.b line 328
    Lines();
    # [row dr][col 'Extraordinary items'] [col y01 0][col y11 -500000] 15 sub/pl.b line 329
    Row(0,array(1,0,'Extraordinary items'),array(3,0,0,1),array(4,0,-500000,1));
    # [row total aul dul rc:b][col '(LOSS)/PROFIT FOR THE FINANCIAL YEAR'] 16 sub/pl.b line 330
    Row(array(0,576,0,0,0,0,'b'),array(1,0,'(LOSS)/PROFIT FOR THE FINANCIAL YEAR'),array(3,32,0,24),array(4,32,0,24),array(5,32,0,24));
    # [line] 14 sub/pl.b line 331
    Lines();
    # [col PL.OrdinaryActivitiesT42:d][col y0 keep PL.OrdinaryActivitiesT42:y02][col 5 '2008 - 2009'] sub/pl.b line 332
    Row(array(0,1024),array(1,64,0,1024),array(3,64,1,1088),array(5,0,'2008 - 2009'));
    # [col R.PLRev.TradingIncome.TurnoverGrossOpRevenue:d " times 2"][col y01 2*R.PLRev.TradingIncome.TurnoverGrossOpRevenue:y02][col y1 2*R.PLRev.TradingIncome.TurnoverGrossOpRevenue:y12][col y02 this:y1:colSum-this:y01:colsum] sub/pl.b line 333
    Row(array(0,1024),array(1,64,2,1024),array(3,64,3,1024),array(4,64,5,1024),array(5,64,6,2048));
    # [row subtotal aul] sub/pl.b line 334
    Row(array(0,256),array(3,32,0,16),array(4,32,0,16),array(5,32,0,16));
    # [col R.PLRev.TradingIncome.TurnoverGrossOpRevenue:d " times 2"][col y01 2*R.PLRev.TradingIncome.TurnoverGrossOpRevenue:y02][col y1 2*R.PLRev.TradingIncome.TurnoverGrossOpRevenue:y12][col y02 this:y1:colSum-this:y01:colsum] sub/pl.b line 335
    Row(array(0,1024),array(1,64,2,1024),array(3,64,3,1024),array(4,64,5,1024),array(5,64,6,2048));
    # [row total aul dul] sub/pl.b line 336
    Row(array(0,576),array(3,32,0,24),array(4,32,0,24),array(5,32,0,24));
    # [line] 14 sub/pl.b line 337
    Lines();
    # [col PL.OrdinaryActivitiesT42:d][col y0 keep PL.OrdinaryActivitiesT42:y02][col 5 '2009 - 2008'] sub/pl.b line 338
    Row(array(0,1024),array(1,64,0,1024),array(3,64,1,1088),array(5,0,'2009 - 2008'));
    # [col R.PLRev.TradingIncome.TurnoverGrossOpRevenue:d " times 2"][col y01 2*R.PLRev.TradingIncome.TurnoverGrossOpRevenue:y02][col y1 2*R.PLRev.TradingIncome.TurnoverGrossOpRevenue:y12][col y02 this:y01:colSum-this:y1:colsum] sub/pl.b line 339
    Row(array(0,1024),array(1,64,2,1024),array(3,64,3,1024),array(4,64,5,1024),array(5,64,7,2048));
    # [row subtotal cols:y01,y1][col y02 this:y01:colSum-this:y1:colsum] sub/pl.b line 341
    Row(array(0,256),array(3,32),array(4,32),array(5,64,7,2048));
    # [col R.PLRev.TradingIncome.TurnoverGrossOpRevenue:d " times 2"][col y01 2*R.PLRev.TradingIncome.TurnoverGrossOpRevenue:y02][col y1 2*R.PLRev.TradingIncome.TurnoverGrossOpRevenue:y12][col y02 this:y01:colSum-this:y1:colsum] sub/pl.b line 342
    Row(array(0,1024),array(1,64,2,1024),array(3,64,3,1024),array(4,64,5,1024),array(5,64,7,2048));
    # [row total aul dul cols:y01,y1][col y02 this:y01:colSum-this:y1:colsum] sub/pl.b line 343
    Row(array(0,576),array(3,32,0,24),array(4,32,0,24),array(5,64,7,2072));
  # [end] sub/pl.b line 344
  TableEnd();
  # [np] sub/pl.b line 346
  NewPage();
  # [h2 c:c "Alternative text Profit and Loss Test with Column Sums and Variance"[nl]"for the Period Ended " [date f BRI.DatesPeriods.End]] sub/pl.b line 347
  H(2,"Alternative text Profit and Loss Test with Column Sums and Variance".'<br/>'."for the Period Ended ".DateStrWithTag(8312),'c');
  # [table cols:dn01111 center] sub/pl.b line 348
  TableStart('dn01111',1);
    # [col3 restatedHdg [date y BRI.DatesPeriods.End]][col restatedHdg [date y y:1 BRI.DatesPeriods.End]][col restatedHdg ""[date y BRI.DatesPeriods.End][nl]"Col Sum"][col restatedHdg ""[date y y:1 BRI.DatesPeriods.End][nl]"Col Sum"][col "2009-2008"[nl]"Variance"] sub/pl.b line 349
    Row(0,array(3,0,DateStrWithTag(8312,3)),array(4,0,DateStrWithTag(8312,3,1),4096),array(5,0,"".DateStrWithTag(8312,3).'<br/>'."Col Sum",4096),array(6,0,"".DateStrWithTag(8312,3,1).'<br/>'."Col Sum",4096),array(7,0,"2009-2008".'<br/>'."Variance"));
    # [col 3,4,5,6,7 c:b "£"] sub/pl.b line 350
    Row(0,array(3,0,"£",0,'b'),array(4,0,"£",0,'b'),array(5,0,"£",0,'b'),array(6,0,"£",0,'b'),array(7,0,"£",0,'b'));
    # [col1 'TURNOVER'] [col 3 -16613552][col 4 -19195013][col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum] sub/pl.b line 351
    Row(0,array(1,0,'TURNOVER'),array(3,0,-16613552),array(4,0,-19195013),array(5,64,8,2048),array(6,64,9,2048),array(7,64,7,2048));
    # [line] sub/pl.b line 352
    Lines();
    # [col 'Cost of sales'] [col 3 dr 14651380][col 4 dr 16666490][col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum] sub/pl.b line 353
    Row(0,array(1,0,'Cost of sales'),array(3,0,14651380,1),array(4,0,16666490,1),array(5,64,8,2048),array(6,64,9,2048),array(7,64,7,2048));
    # [row subtotal][col 'GROSS PROFIT/(LOSS)'] [col 3 aul][col 4 aul][col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum] sub/pl.b line 354
    Row(array(0,256),array(1,0,'GROSS PROFIT/(LOSS)'),array(3,32,0,16),array(4,32,0,16),array(5,64,8,2048),array(6,64,9,2048),array(7,64,7,2048));
    # [line] sub/pl.b line 355
    Lines();
    # [row dr][col 'Distribution costs'] [col3 221780][col 4 260471] [col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum] sub/pl.b line 356
    Row(0,array(1,0,'Distribution costs'),array(3,0,221780,1),array(4,0,260471,1),array(5,64,8,2049),array(6,64,9,2049),array(7,64,7,2049));
    # [row dr][col 'Administrative expenses'] [col3 887119][col 4 1057994] [col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum] sub/pl.b line 357
    Row(0,array(1,0,'Administrative expenses'),array(3,0,887119,1),array(4,0,1057994,1),array(5,64,8,2049),array(6,64,9,2049),array(7,64,7,2049));
    # [row dr][col 'Test 0 row - should not appear'] [col 3 0][col 4 0][col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum] sub/pl.b line 358
    Row(0,array(1,0,'Test 0 row - should not appear'),array(3,0,0,1),array(4,0,0,1),array(5,64,8,2049),array(6,64,9,2049),array(7,64,7,2049));
    # [row dr keep][col 'Test 0 row with keep - should appear'] [col 3 0][col 4 0][col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum] sub/pl.b line 359
    Row(array(0,64),array(1,0,'Test 0 row with keep - should appear'),array(3,0,0,1),array(4,0,0,1),array(5,64,8,2049),array(6,64,9,2049),array(7,64,7,2049));
    # [row dr keepHide][col 'Test 0 row with keepHide - should be hidden'][col 3 0][col 4 0][col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum] sub/pl.b line 360
    Row(array(0,128),array(1,0,'Test 0 row with keepHide - should be hidden'),array(3,0,0,1),array(4,0,0,1),array(5,64,8,2049),array(6,64,9,2049),array(7,64,7,2049));
    # [row total][col 'OPERATING PROFIT/(LOSS)'] [col 3 aul][col aul] [col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum] sub/pl.b line 361
    Row(array(0,576),array(1,0,'OPERATING PROFIT/(LOSS)'),array(3,32,0,16),array(4,32,0,16),array(5,64,8,2048),array(6,64,9,2048),array(7,64,7,2048));
    # [line] sub/pl.b line 362
    Lines();
    # [col 'Attributable to:'] sub/pl.b line 363
    Row(0,array(1,0,'Attributable to:'));
    # [col c:pl 'Operating profit before exceptional items'][col3 -1709673][col 4 -1210058][col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum] sub/pl.b line 364
    Row(0,array(1,0,'Operating profit before exceptional items',0,'pl'),array(3,0,-1709673),array(4,0,-1210058),array(5,64,8,2048),array(6,64,9,2048),array(7,64,7,2048));
    # [col c:pl 'Exceptional items'] [col 3 856400][col 4 0] [col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum] sub/pl.b line 365
    Row(0,array(1,0,'Exceptional items',0,'pl'),array(3,0,856400),array(4),array(5,64,8,2048),array(6,64,9,2048),array(7,64,7,2048));
    # [row subtotal] [col 3 aul][col aul] [col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum] sub/pl.b line 366
    Row(array(0,256),array(3,32,0,16),array(4,32,0,16),array(5,64,8,2048),array(6,64,9,2048),array(7,64,7,2048));
    # [line] sub/pl.b line 367
    Lines();
    # [col 'Loss on disposal of discontinued operations'][col 3 600000][col4 0] [col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum] sub/pl.b line 368
    Row(0,array(1,0,'Loss on disposal of discontinued operations'),array(3,0,600000),array(4),array(5,64,8,2048),array(6,64,9,2048),array(7,64,7,2048));
    # [col 'Cost of restructuring the company'] [col 3 0][col 4 1100000][col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum] sub/pl.b line 369
    Row(0,array(1,0,'Cost of restructuring the company'),array(3),array(4,0,1100000),array(5,64,8,2048),array(6,64,9,2048),array(7,64,7,2048));
    # [row subtotal] [col 3 aul][col aul] [col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum] sub/pl.b line 370
    Row(array(0,256),array(3,32,0,16),array(4,32,0,16),array(5,64,8,2048),array(6,64,9,2048),array(7,64,7,2048));
    # [line] sub/pl.b line 371
    Lines();
    # [row subtotal][col 'PROFIT/(LOSS) ON ORDINARY ACTIVITIES BEFORE TAXATION'][col 3 aul][col aul][col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum] sub/pl.b line 374
    Row(array(0,256),array(1,0,'PROFIT/(LOSS) ON ORDINARY ACTIVITIES BEFORE TAXATION'),array(3,32,0,16),array(4,32,0,16),array(5,64,8,2048),array(6,64,9,2048),array(7,64,7,2048));
    # [line] sub/pl.b line 375
    Lines();
    # [row dr alt:PL.OrdinaryActivities][col 'Tax on profit/(loss) on ordinary activities'][col 3 212422][col 4 337173][col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum] sub/pl.b line 376
    Row(array(0,0,0,0,0,0,0,1),array(1,0,'Tax on profit/(loss) on ordinary activities'),array(3,0,212422,1),array(4,0,337173,1),array(5,64,8,2049),array(6,64,9,2049),array(7,64,7,2049));
    # [row subtotal][col 'PROFIT/(LOSS) ON ORDINARY ACTIVITIES AFTER TAXATION'][col 3 aul][col aul][col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum] sub/pl.b line 377
    Row(array(0,256),array(1,0,'PROFIT/(LOSS) ON ORDINARY ACTIVITIES AFTER TAXATION'),array(3,32,0,16),array(4,32,0,16),array(5,64,8,2048),array(6,64,9,2048),array(7,64,7,2048));
    # [line] sub/pl.b line 378
    Lines();
    # [row dr][col 'Extraordinary items'] [col 3 0][col 4 -500000][col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum] sub/pl.b line 379
    Row(0,array(1,0,'Extraordinary items'),array(3,0,0,1),array(4,0,-500000,1),array(5,64,8,2049),array(6,64,9,2049),array(7,64,7,2049));
    # [row total rc:b][col '(LOSS)/PROFIT FOR THE FINANCIAL YEAR'][col 3 aul dul][col aul dul][col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum] sub/pl.b line 380
    Row(array(0,576,0,0,0,0,'b'),array(1,0,'(LOSS)/PROFIT FOR THE FINANCIAL YEAR'),array(3,32,0,24),array(4,32,0,24),array(5,64,8,2048),array(6,64,9,2048),array(7,64,7,2048));
  # [end] sub/pl.b line 381
  TableEnd();
  # End of Format sub/pl.b
  # Start of Format sub/bs.b with index 6 and level 1
  # [np] sub/bs.b line 15
  NewPage();
  # [xref target bsX] sub/bs.b line 16
  XrefSt('bsX',0);
  # [h2 c:c "Balance Sheet"[nl]"as at " [date f BRI.DatesPeriods.End]] sub/bs.b line 17
  H(2,"Balance Sheet".'<br/>'."as at ".DateStrWithTag(8312),'c');
  # [p "BS to come here...."] sub/bs.b line 18
  P("BS to come here....");
  # [lines 10] sub/bs.b line 19
  Lines(10);
  # [p c:b "Signed on behalf of the Board by:"] sub/bs.b line 20
  P("Signed on behalf of the Board by:",'b');
  # [lines 2] sub/bs.b line 21
  Lines(2);
  # [table] sub/bs.b line 22
  TableStart();
    # [col1 hor c:w100 EntityInfo.Officers.Name@Directors@DirectorSigningAccounts] sub/bs.b line 23
    Row(0);ColBroLoop(1,'8252,426',40,1,1,'w100',8192);
    # [col1 repeat:NumDirectorsSigningAccountsI 'Director'] sub/bs.b line 24
    Row(0,array(1,0,'Director',0,0,$NumDirectorsSigningAccountsI));
  # [end] sub/bs.b line 25
  TableEnd();
  # End of Format sub/bs.b
  # Start of Format sub/notes.b with index 7 and level 1
  # [np] sub/notes.b line 15
  NewPage();
  # [footer] sub/notes.b line 16
  $FooterFn='FooterFn4F';
  # [xref target notesX] sub/notes.b line 19
  XrefSt('notesX',0);
  # [h2 c:c "Notes to the "AccountsShortH[nl]"for the Period Ended " [date f BRI.DatesPeriods.End]] sub/notes.b line 20
  H(2,"Notes to the ".$HeadingsA['AccountsShortH'].'<br/>'."for the Period Ended ".DateStrWithTag(8312),'c');
  # [line] sub/notes.b line 21
  Lines();
  # [xref target N1X] sub/notes.b line 22
  XrefSt('N1X',0);
  # [h2 "Note <span>1</span> Turnover"] sub/notes.b line 23
  H(2,"Note <span>1</span> Turnover");
  # [p "The analysis of turnover by geographical market by destination is as follows"] sub/notes.b line 24
  P("The analysis of turnover by geographical market by destination is as follows");
  # [table cols:d01] sub/notes.b line 25
  TableStart('d01');
    # [col2,3 c:b restatedHdg BRI.DatesPeriods.End] sub/notes.b line 26
    Row(0,array(2,1,8312,0,'b'),array(3,1,8312,4096,'b'));
    # [col2,3 c:b "£"] sub/notes.b line 27
    Row(0,array(2,0,"£",0,'b'),array(3,0,"£",0,'b'));
    # [col1 "United Kingdom"][col2,3 SegAnalysisRevCostsProfits.Geography.RevenueByDestination.Geo:Countries.UK] sub/notes.b line 28
    Row(0,array(1,0,"United Kingdom"),array(2,1,'9066,557'),array(3,1,'9066,557'));
    # [col1 "Europe"] [col2,3 SegAnalysisRevCostsProfits.Geography.RevenueByDestination.Geo:Countries.Europe] sub/notes.b line 29
    Row(0,array(1,0,"Europe"),array(2,1,'9066,570'),array(3,1,'9066,570'));
    # [col1 "North America"] [col2,3 SegAnalysisRevCostsProfits.Geography.RevenueByDestination.Geo:Countries.NorthAmerica] sub/notes.b line 30
    Row(0,array(1,0,"North America"),array(2,1,'9066,572'),array(3,1,'9066,572'));
    # [col1 "Rest of World"] [col2,3 ul SegAnalysisRevCostsProfits.Geography.RevenueByDestination.Geo:Countries.OtherRegions] sub/notes.b line 31
    Row(0,array(1,0,"Rest of World"),array(2,1,'9066,568',4),array(3,1,'9066,568',4));
    # [col2,3 dul PLRev.TradingIncome.TurnoverGrossOpRevenue] sub/notes.b line 32
    Row(0,array(2,1,3002,8),array(3,1,3002,8));
  # [end] sub/notes.b line 33
  TableEnd();
  # [if y:1 SegAnalysisRevCostsProfits.Geography.RevenueByDestination.Geo:Countries.UK:Restated || PLRev.TradingIncome.TurnoverGrossOpRevenue:Restated] sub/notes.b line 35
  if (Data('9066,557,77',1)||Data('3002,77',1)) {
    # [table cols:d1] sub/notes.b line 36
    TableStart('d1');
      # [if y:1 SegAnalysisRevCostsProfits.Geography.RevenueByDestination.Geo:Countries.UK:Restated] sub/notes.b line 38
      if (Data('9066,557,77',1)) {
        # [col2 c:b restatedhdg BRI.DatesPeriods.End] sub/notes.b line 39
        Row(0,array(2,1,8312,4096,'b'));
        # [col1 c:b 'Restated UK Turnover'] [col2 c:b "£"] sub/notes.b line 40
        Row(0,array(1,0,'Restated UK Turnover',0,'b'),array(2,0,"£",0,'b'));
        # [col1 'UK Turnover:Restated [OriginalAmount]'][col SegAnalysisRevCostsProfits.Geography.RevenueByDestination.Geo:Countries.UK:Restated] sub/notes.b line 41
        Row(0,array(1,0,'UK Turnover:Restated [OriginalAmount]'),array(2,1,'9066,557,77'));
        # [col1 'UK Turnover:Restated.AccountingPolicyIncr'][col SegAnalysisRevCostsProfits.Geography.RevenueByDestination.Geo:Countries.UK:Restated.AcctPolicyIncr] sub/notes.b line 42
        Row(0,array(1,0,'UK Turnover:Restated.AccountingPolicyIncr'),array(2,1,'9066,557,75'));
        # [col1 'UK Turnover:Restated.PriorPeriodIncr'][col SegAnalysisRevCostsProfits.Geography.RevenueByDestination.Geo:Countries.UK:Restated.PriorPeriodIncr] sub/notes.b line 43
        Row(0,array(1,0,'UK Turnover:Restated.PriorPeriodIncr'),array(2,1,'9066,557,74'));
        # [col1 'UK Turnover:Restated.Amount'][col aul SegAnalysisRevCostsProfits.Geography.RevenueByDestination.Geo:Countries.UK:Restated.Amount] sub/notes.b line 44
        Row(0,array(1,0,'UK Turnover:Restated.Amount'),array(2,1,'9066,557,73',16));
        # [line] sub/notes.b line 45
        Lines();
      # [end] sub/notes.b line 46
      }
      # [if y:1 PLRev.TradingIncome.TurnoverGrossOpRevenue:Restated] sub/notes.b line 47
      if (Data('3002,77',1)) {
        # [col1 c:b 'Restated Turnover'] sub/notes.b line 48
        Row(0,array(1,0,'Restated Turnover',0,'b'));
        # [col1 'Turnover:Restated [OriginalAmount]'][col PLRev.TradingIncome.TurnoverGrossOpRevenue:Restated] sub/notes.b line 49
        Row(0,array(1,0,'Turnover:Restated [OriginalAmount]'),array(2,1,'3002,77'));
        # [col1 'Turnover:Restated.AccountingPolicyIncr'][col PLRev.TradingIncome.TurnoverGrossOpRevenue:Restated.AcctPolicyIncr] sub/notes.b line 50
        Row(0,array(1,0,'Turnover:Restated.AccountingPolicyIncr'),array(2,1,'3002,75'));
        # [col1 'Turnover:Restated.PriorPeriodIncr'][col PLRev.TradingIncome.TurnoverGrossOpRevenue:Restated.PriorPeriodIncr] sub/notes.b line 51
        Row(0,array(1,0,'Turnover:Restated.PriorPeriodIncr'),array(2,1,'3002,74'));
        # [col1 'Turnover:Restated.Amount'][col aul PLRev.TradingIncome.TurnoverGrossOpRevenue:Restated.Amount] sub/notes.b line 52
        Row(0,array(1,0,'Turnover:Restated.Amount'),array(2,1,'3002,73',16));
      # [end] sub/notes.b line 53
      }
    # [end] sub/notes.b line 54
    TableEnd();
  # [end] sub/notes.b line 55
  }
  # [xref target N2X] sub/notes.b line 58
  XrefSt('N2X',0);
  # [h2 "Note <span>2</span>"] sub/notes.b line 59
  H(2,"Note <span>2</span>");
  # [xref target N3X] sub/notes.b line 60
  XrefSt('N3X',0);
  # [h2 "Note <span>3</span>"] sub/notes.b line 61
  H(2,"Note <span>3</span>");
  # [xref target N4X] sub/notes.b line 62
  XrefSt('N4X',0);
  # [h2 "Note <span>4</span>"] sub/notes.b line 63
  H(2,"Note <span>4</span>");
  # [xref target N5X] sub/notes.b line 64
  XrefSt('N5X',0);
  # [h2 "Note <span>5</span>"] sub/notes.b line 65
  H(2,"Note <span>5</span>");
  # [np] sub/notes.b line 66
  NewPage();
  # [p "Second page of Notes"] sub/notes.b line 67
  P("Second page of Notes");
  # [np] sub/notes.b line 68
  NewPage();
  # [p "Third page of Notes"] sub/notes.b line 69
  P("Third page of Notes");
  # [xref target notesEndX] sub/notes.b line 70
  XrefSt('notesEndX',0);
  # End of Format sub/notes.b
# End of Format PrivLtdFull.p

# Format Functions
# [footer] sub/cover.b line 13
function FooterFn1F() {
  # [line] sub/cover.b line 14
  Lines();
  # [p c:c,m0 "Cover page footer"] sub/cover.b line 15
  P("Cover page footer",'c m0');
  # [p c:c,m0,b EntityInfo.ThirdPartyAgents.Name:TPAType.Accountants] sub/cover.b line 16
  P(DataWithTag('8278,522'),'c m0 b');
  # [p c:c,m0 EntityInfo.ThirdPartyAgents.MeansContact.Address:TPAType.Accountants@Address] sub/cover.b line 17
  PBroLoop('8285,522',6,0,0,'c m0');
# [end] sub/cover.b line 18
}
# [footer] sub/contents.b line 14
function FooterFn2F() {
  # [lines 5] sub/contents.b line 15
  Lines(5);
  # [p c:c,m0 'Page '[page#]] sub/contents.b line 16
  P('Page '.PageStr(),'c m0');
# [end] sub/contents.b line 17
}
# [header] sub/dirReport.b line 15
function HeaderFn1F() {
  # [h1 c:c EntityInfo.Names.CurrentLegalOrRegistered] sub/dirReport.b line 16
  H(1,DataWithTag(8212),'c');
# [end] sub/dirReport.b line 17
}
# [footer] sub/pl.b line 14
function FooterFn3F() {
  # [lines 5] sub/pl.b line 15
  Lines(5);
  # [p c:c "The notes on pages "[xref page notesX] " to "[xref page notesEndX] " form an integral part of these financial statements."] sub/pl.b line 16
  P("The notes on pages ".XrefSt('notesX',2)." to ".XrefSt('notesEndX',2)." form an integral part of these financial statements.",'c');
  # [p c:c,m0 'Page '[page#]] sub/pl.b line 17
  P('Page '.PageStr(),'c m0');
# [end] sub/pl.b line 18
}
# [footer] sub/notes.b line 16
function FooterFn4F() {
  # [p c:c,m0 'Page '[page#]] sub/notes.b line 17
  P('Page '.PageStr(),'c m0');
# [end] sub/notes.b line 18
}
