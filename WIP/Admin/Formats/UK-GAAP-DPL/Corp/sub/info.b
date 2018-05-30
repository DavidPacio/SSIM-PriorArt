© Braiins Ltd 2011

Format: Corp/info.b
        Company Information Page

Main format: LtdFullGAAP.bp
Sub-formats: None

History:
02.02.11 djh Started

====
Footer unchanged from contents.b footer

[np]
[lines 10]
[h1 c:c SchInputEntity.Names.CurrentLegalOrRegistered]
[lines 2]
[xref target infoX]
[h2 c:c CompanyInformationH [nl] "for the Period Ended " [date f SchInputBRI.DatesPeriods.End]]
[lines 2]
[table]
  [col c:b DirectorsS ":"][col SchInputEntity.Officers.Name@Directors]
  [lines 2]
  [col c:b 'Company Secretary:'][col SchInputEntity.Officers.Name,.CoSec]
  [lines 2]
  [col c:b 'Registered Office Address:'][col SchInputEntity.MeansContact.Address,ContactType.RegisteredOffice@Address]
  [lines 2]
  [col c:b CompanyRegistrationNumberH ":"][col SchInputEntity.IdentifyingCodes.UKCompaniesHouseRegisteredNumber]
[end]
[line]
[p "This is some p text"]
[p c:i "This is some more p text with class i for italics attached."]
[p c:s "This is some small p text via class s."]
[p c:i,s "This is some small italic text via classes i,s."]
[p c:l "And some large p text via class l."]
[span 'Stand alone span which should end up in a p tag']
[p  'p text ' [span c:b,s 'now an embedded b,s span'] ' plus more p text, followed on the next line by a stand alone date statement which should end up in a p tag:']
[date f SchInputBRI.DatesPeriods.End]
[zones Cover]
[p SchInputEntity.IdentifyingCodes.UKCompaniesHouseRegisteredNumber]
[p SchInputEntity.Officers.Name,.Director1]
[p SchInputEntity.Officers.Name,.Director2]
