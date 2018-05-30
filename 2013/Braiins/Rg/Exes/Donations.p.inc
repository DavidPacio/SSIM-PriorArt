<?php
# Start of Format Donations.p with index 0 and level 0
# [h2 c:c "Donations Test for the Period Ended " [date f SchInputBRI.DatesPeriods.End]] Donations.p line 8
H(2,"Donations Test for the Period Ended ".DateStrWithTag(5731),'c');
# [h2 c:c "Directors' Report"] Donations.p line 10
H(2,"Directors' Report",'c');
# [h4 "Political donations"] Donations.p line 12
H(4,"Political donations");
# [p "During the year the company made political donations of £" [span PLExps.OperationalAdministration.PoliticalDonations] ". Individual donations to EU political parties were:"] Donations.p line 13
P("During the year the company made political donations of £".SpanStr(DataWithTag(1161)).". Individual donations to EU political parties were:");
# [table cols:t01 center] Donations.p line 14
TableStart('t01',1);
  # [col 2,3 c:b restatedHdg SchInputBRI.DatesPeriods.End] Donations.p line 15
  Row(0,[2,1,5731,0,'b'],[3,1,5731,4096,'b']);
  # [col 2,3 c:b "£"] Donations.p line 16
  Row(0,[2,0,"£",0,'b'],[3,0,"£",0,'b']);
  # [col SchInputDirRep.PoliticalCharitableDonations.Political.EU.SpecificEUPoliticalDonation.NameOrDescrRecipientOrg,T.all][col 2,3 SchInputDirRep.PoliticalCharitableDonations.Political.EU.SpecificEUPoliticalDonation.AmountToEUOrg,T.all] Donations.p line 17
  Row(0,[1,1,'5755,9999,2'],[2,1,'5756,9999,2'],[3,1,'5756,9999,2']);
# [end] Donations.p line 18
TableEnd();
# [h4 "Charitable donations"] Donations.p line 22
H(4,"Charitable donations");
# [p "During the year the company made charitable donations of £" [span PLExps.OperationalAdministration.CharitableDonations] ". Individual donations were:"] Donations.p line 23
P("During the year the company made charitable donations of £".SpanStr(DataWithTag(1160)).". Individual donations were:");
# [table cols:t01 center] Donations.p line 24
TableStart('t01',1);
  # [col 2,3 c:b restatedHdg SchInputBRI.DatesPeriods.End] Donations.p line 25
  Row(0,[2,1,5731,0,'b'],[3,1,5731,4096,'b']);
  # [col 2,3 c:b "£"] Donations.p line 26
  Row(0,[2,0,"£",0,'b'],[3,0,"£",0,'b']);
  # [col SchInputDirRep.PoliticalCharitableDonations.Charitable.SpecificCharitableDonation.DescrPurpose,T.all][col 2,3 SchInputDirRep.PoliticalCharitableDonations.Charitable.SpecificCharitableDonation.Amount,T.all] Donations.p line 27
  Row(0,[1,1,'5750,9999,2'],[2,1,'5751,9999,2'],[3,1,'5751,9999,2']);
  # [col 'Total via SchInputDirRep.PoliticalCharitableDonations.<br/>Charitable.SpecificCharitableDonation.Amount'][col SchInputDirRep.PoliticalCharitableDonations.Charitable.SpecificCharitableDonation.Amount] Donations.p line 28
  Row(0,[1,0,'Total via SchInputDirRep.PoliticalCharitableDonations.<br/>Charitable.SpecificCharitableDonation.Amount'],[2,1,5751]);
# [end] Donations.p line 29
TableEnd();
# [h2 c:c "And in the Detailed Profit and Loss Account"] Donations.p line 31
H(2,"And in the Detailed Profit and Loss Account",'c');
# [zone PL] Donations.p line 33
ZoneSign(2);
# [table cols:d01 center] Donations.p line 34
TableStart('d01',1);
  # [col 2,3 c:b restatedHdg SchInputBRI.DatesPeriods.End] Donations.p line 35
  Row(0,[2,1,5731,0,'b'],[3,1,5731,4096,'b']);
  # [col 2,3 c:b "£"] Donations.p line 36
  Row(0,[2,0,"£",0,'b'],[3,0,"£",0,'b']);
  # [col c:b "Administrative expenses"] Donations.p line 37
  Row(0,[1,0,"Administrative expenses",0,'b']);
  # [row b:PLExps.OperationalAdministration.CharitableDonations] Donations.p line 38
  Row([1160],[1,20],[2,4],[3,4]);
  # [row b:PLExps.OperationalAdministration.PoliticalDonations] Donations.p line 39
  Row([1161],[1,20],[2,4],[3,4]);
  # [row subtotal aul dul] Donations.p line 40
  Row([0,256],[2,32,0,24],[3,32,0,24]);
# [end] Donations.p line 41
TableEnd();
# End of Format Donations.p
