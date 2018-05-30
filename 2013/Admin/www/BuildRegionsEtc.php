<?php /* Copyright 2011-2013 Braiins Ltd

BuildRegionsEtc.php

Build
 Regions
 Countries
 Currencies
 StockExchanges
 Languages

tables.

History:
26.04.13 Started

*/
require 'BaseSIM.inc';

Head('Build Regions Etc', true);

echo "<br><br><b>Building the SIM Regions table</b><br>";
/*
#############
## Regions ## Regions
#############
CREATE TABLE IF NOT EXISTS Regions (
  Id       tinyint unsigned not null auto_increment, # = RegionId
  Name     varchar(40)      not null, # Full name of region
  Ref      varchar(32)      not null, # Short name of region if in use e.g. EU, o'wise Name stripped of spaces, used as the # Ref of Property Region
  SortKey  char(4)          not null, #
  AliasId  tinyint unsigned     null, # Regions.Id of full form of the region's name. Used for common 'nicknames' for regions
  PartOf   char(4)              null, # Chr list of RegionIds if also part of another region/other regions
 #Bits     tinyint unsigned not null, # Bit settings. None as of 26.04.13
  Primary Key (Id),     # unique
   Unique Key (Ref)
   #      Key (SortKey) Only used when listing regions, which will be rare vs use as a Property Ref so there is no need for this index
) Engine = InnoDB DEFAULT CHARSET=utf8;

Max length Name = 38
Max length Ref = 24
Max length Sortkey = 3
Max length PartOf = 3

*/

#    0         1     2        3       4
# Name | SortKey | Ref | PartOf | Alias
$rowsS = '
Africa|Af1
Central Africa|Af2||Africa
East Africa|Af3||Africa
North Africa|Af4||Africa
Southern Africa|Af5||Africa
West Africa|Af6||Africa
America|Am1
Americas|Am2|||America
Central America|Am3||America
North America|Am4||America
South America|Am5||America
Latin America and Caribbean|Am6|LatinAmericaAndCaribbean|America
Latin America|Am7||America
Caribbean|Am8||America,Latin America and Caribbean,North America
North America Free Trade Area|Am9|NAFTA|America,North America
Asia|As1
Central Asia|As2||Asia
East Asia|As3||Asia
South Asia|As4||Asia
South East Asia|As5|SEA|Asia
Association of Southeast Asian Nations|As6|ASEAN|Asia,South East Asia
Australasia|Au1
Europe|Eu1
European Economic Community|Eu2|EEC|Europe,European Union|European Union
European Community|Eu3|EC|Europe,European Union|European Union
European Union|Eu4|EU|Europe
Middle East|Me1
Atlantic Ocean|O1
North Atlantic Ocean|O2||Atlantic Ocean
South Atlantic Ocean|O3||Atlantic Ocean
Indian Ocean|O4
Pacific Ocean|O5
North Pacific Ocean|O6||Pacific Ocean
South Pacific Ocean|O7||Pacific Ocean
Other Regions|Or
Rest of World|Re1|RoW
Rest of Region|Re2|RoR
';
$rowsA = explode(NL, substr($rowsS, 1, -1));

$maxLenName = $maxLenRef = $maxLenSKey = $maxLenPartOf = 0;
$DB->StQuery("Truncate Regions");
$DB->autocommit(false);
foreach ($rowsA as $rowi => $row) {
  $colsA = explode('|', $row);
  $name    = trim($colsA[0]);
  $sortKey = trim($colsA[1]);
  if (!isset($colsA[2]) || !($ref = trim($colsA[2])))
    $ref = str_replace(SP, '', $name);
  $set = "Name='$name',Ref='$ref',SortKey='$sortKey'";
  $maxLenName = max($maxLenName,  strlen($name));
  $maxLenRef  = max($maxLenRef, strlen($ref));
  $maxLenSKey = max($maxLenSKey,  strlen($sortKey));
  $DB->StQuery("Insert into Regions Set $set");
  echo "$name<br>";
}
foreach ($rowsA as $rowi => $row) {
  $set = '';
  $colsA = explode('|', $row);
  if (isset($colsA[3]) && ($v = trim($colsA[3]))) {
    $partOf='';
    foreach (explode(COM, $v) as $name) {
      $name = trim($name);
      #echo "PartOf Name=$name<br>";
      if (!$id = $DB->OneQuery("Select Id from Regions Where Name='$name'"))
        if (!$id = $DB->OneQuery("Select Id from Regions Where Ref='$name'"))
          die("Region $v in row $rowi $row not found");
      $partOf .= IntToChr($id);
    }
    $maxLenPartOf = max($maxLenPartOf, strlen($partOf));
    $set .= ",PartOf='$partOf'";
  }
  if (isset($colsA[4]) && ($v = trim($colsA[4]))) {
    if (!$id = $DB->OneQuery("Select Id from Regions Where Name='$v'"))
      if (!$id = $DB->OneQuery("Select Id from Regions Where Ref='$v'"))
        die("Alias $v in row $rowi $row not found");
    $set .= ",AliasId=$id";
  }
  if ($set) {
    $Id = $rowi+1;
    $set = substr($set, 1);
    $DB->StQuery("Update Regions Set $set Where Id=$Id");
    #echo "$Id PartOf=$partOf<br>";
  }
}
$DB->commit();
echo "<br>Done<br>Max length Name = $maxLenName<br>
Max length Ref = $maxLenRef<br>
Max length Sortkey = $maxLenSKey<br>
Max length PartOf = $maxLenPartOf<br>";

###############
## Countries ##
###############
echo "<br><br><b>Building the SIM Countries table</b><br>";
#      0      1     2        3       4        5
# ISOnum | Name | Ref | PartOf | Assoc| Regions
$rowsS = "
826	United Kingdom	UK			Europe,EU
	England		UK
	Scotland		UK
	Wales		UK
	Northern Ireland	NI	UK
833	Isle of Man	IoM		UK		Not part of the UK. EU?
832	Jersey			UK		Not part of the UK or EU
831	Guernsey			UK		Not part of the UK or the EU

36	Australia
528	Netherlands
840	United States of America	USA

4	Afghanistan
248	Åland Islands
8	Albania
12	Algeria
16	American Samoa
20	Andorra
24	Angola
660	Anguilla			UK
10	Antarctica
28	Antigua and Barbuda	Antigua
32	Argentina
51	Armenia
533	Aruba
40	Austria
31	Azerbaijan
44	Bahamas
48	Bahrain
50	Bangladesh
52	Barbados
112	Belarus
56	Belgium
84	Belize
204	Benin
60	Bermuda
64	Bhutan
68	Bolivia
535	Bonaire, Sint Eustatius and Saba	Bonaire				Missing from UK-GAAP as previously part of Nertherlands Antilles
70	Bosnia and Herzegovina	Bosnia
72	Botswana
76	Brazil
86	British Indian Ocean Territory	BIOT		UK		Also known as Chagos Islands
96	Brunei Darussalam	Brunei
100	Bulgaria
854	Burkina Faso
108	Burundi
116	Cambodia
120	Cameroon
124	Canada
132	Cape Verde
136	Cayman Islands			UK
140	Central African Republic	CAR
148	Chad
152	Chile
156	China
162	Christmas Island		Australia		Indian Ocean
166	Cocos (Keeling) Islands	Cocos Islands	Australia		Indian Ocean
170	Colombia				South America
174	Comoros				Indian Ocean
178	Congo				Central Africa
180	Congo, The Democratic Republic of the	DR Congo			Central Africa
184	Cook Islands
188	Costa Rica
384	Côte d'Ivoire
191	Croatia
192	Cuba
531	Curaçao					Missing from UK-GAAP as previously part of Nertherlands Antilles
196	Cyprus
203	Czech Republic
208	Denmark
262	Djibouti
212	Dominica
214	Dominican Republic
218	Ecuador
818	Egypt
222	El Salvador
226	Equatorial Guinea
232	Eritrea
233	Estonia
231	Ethiopia
238	Falkland Islands
234	Faroe Islands
242	Fiji
246	Finland
250	France
254	French Guiana
258	French Polynesia
260	French Southern Territories					Has no permanent civilian population
266	Gabon
270	Gambia
268	Georgia
276	Germany
288	Ghana
292	Gibraltar			UK
300	Greece
304	Greenland
308	Grenada
312	Guadeloupe		France
316	Guam
320	Guatemala
324	Guinea
624	Guinea-Bissau
328	Guyana
332	Haiti
336	Holy See (Vatican City State)	Vatican
340	Honduras
344	Hong Kong
348	Hungary
352	Iceland
356	India
360	Indonesia
364	Iran
368	Iraq
372	Ireland
376	Israel
380	Italy
388	Jamaica
392	Japan
400	Jordan
398	Kazakhstan
404	Kenya
296	Kiribati
408	Korea, Democratic People's Republic of	North Korea
410	Korea, Republic of	South Korea
	Kosovo					Missing from UK-GAAP and ISO 3166-1
414	Kuwait
417	Kyrgyzstan
418	Lao
428	Latvia
422	Lebanon
426	Lesotho
430	Liberia
434	State of Libya	Libya				Name changed 2011 and 2013
438	Liechtenstein
440	Lithuania
442	Luxembourg
446	Macau
807	Macedonia, Republic of	Macedonia
450	Madagascar
454	Malawi
458	Malaysia
462	Maldives
466	Mali
470	Malta
584	Marshall Islands				North Pacific Ocean
474	Martinique
478	Mauritania
480	Mauritius
175	Mayotte
484	Mexico
583	Micronesia, Federated States of	Micronesia			North Pacific Ocean
498	Moldova, Republic of	Moldova
492	Monaco
496	Mongolia
499	Montenegro					Missing from UK-GAAP as new
500	Montserrat			UK
504	Morocco
508	Mozambique
104	Myanmar
516	Namibia
520	Nauru
524	Nepal
540	New Caledonia
554	New Zealand
558	Nicaragua
562	Niger
566	Nigeria
570	Niue
574	Norfolk Island
580	Northern Mariana Islands			USA	North Pacific Ocean
578	Norway
512	Oman
586	Pakistan
585	Palau, Replublic of	Palau			North Pacific Ocean
275	Palestinian Territory
591	Panama
598	Papua New Guinea
600	Paraguay
604	Peru
608	Philippines
612	Pitcairn
616	Poland
620	Portugal
630	Puerto Rico			USA
634	Qatar
638	Réunion				Indian Ocean
642	Romania
643	Russian Federation	Russia
646	Rwanda
652	Saint Barthélemy	St. Barthélemy				Missing from UK-GAAP as previously part of Nertherlands Antilles
654	Saint Helena	St. Helena
659	Saint Kitts and Nevis	St. Kitts & Nevis
662	Saint Lucia	St. Lucia
663	Saint Martin (French part)	St. Martin - French
666	Saint Pierre and Miquelon	St. Pierre & Miquelon
670	Saint Vincent and the Grenadines	St. Vincent & Grenadines
882	Samoa
674	San Marino
678	São Tomé and Príncipe
682	Saudi Arabia
686	Senegal
688	Serbia					Serbia and Montenegro is no more
690	Seychelles
694	Sierra Leone
702	Singapore
534	Sint Maarten (Dutch part)	St. Martin - Dutch
703	Slovakia
705	Slovenia
90	Solomon Islands
706	Somalia
710	South Africa
728	South Sudan					new since UK-GAAP
724	Spain
144	Sri Lanka
729	Sudan
740	Suriname
744	Svalbard and Jan Mayen
748	Swaziland
752	Sweden
756	Switzerland
760	Syrian Arab Republic	Syria
158	Taiwan, Province of China	Taiwan
762	Tajikistan
834	Tanzania, United Republic of	Tanzania
764	Thailand
626	Timor-Leste
768	Togo
772	Tokelau
776	Tonga
780	Trinidad and Tobago
788	Tunisia
792	Turkey
795	Turkmenistan
796	Turks and Caicos Islands
798	Tuvalu
800	Uganda
804	Ukraine
784	United Arab Emirates
581	United States Minor Outlying Islands
858	Uruguay
860	Uzbekistan
548	Vanuatu
862	Venezuela, Bolivarian Republic of	Venezuela
704	Viet Nam
92	Virgin Islands, British			UK
850	Virgin Islands, U.S.			USA
876	Wallis and Futuna				South Pacific Ocean
732	Western Sahara
887	Yemen
894	Zambia
716	Zimbabwe
";
/*
###############
## Countries ## Countries
###############
CREATE TABLE IF NOT EXISTS Countries (
  Id       smallint unsigned not null auto_increment, # = CountryId or CtryId
  Name     varchar(40)       not null, # Full name of country
  Ref      varchar(24)       not null, # Short name of country if in use e.g. UK, o'wise Name stripped of spaces, used as the # Ref of Property Country
  SortKey  char(10)          not null, #
  ISOnum   smallint unsigned     null, # ISO 31661-1 number. Can be undefined as for Kosovo in 2013
  AliasId  smallint unsigned     null, # CountryId of official form of the country name. Used for common 'nicknames' for countries
  PartOfId smallint unsigned     null, # CountryId of another country the country is part of e.g. England is part of the UK
  AssocId  smallint unsigned     null, # CountryId of another country the country is associated with e.g. Isle of Man is not part of the UK but is 'associated' with it.
  Regions  char(4)               null, # Chr list of up to 4 RegionIds the country is a part of
  Bits     tinyint  unsigned     null, # Bit settings. 1 = Break before in listing
  Primary Key (Id),
   Unique Key (Ref)
   #      Key (SortKey) Only used when listing countries, which will be rare vs use as a Property Ref so not need for this index
) Engine = InnoDB DEFAULT CHARSET=utf8;
) Engine = InnoDB DEFAULT CHARSET=utf8;
*/

#ISO-31661-1	Name	Short Name if Different	Part Of	Associated With	Region(s)	Comments

#      0      1     2        3       4        5
# ISOnum | Name | Ref | PartOf | Assoc| Regions

$rowsA = explode(NL, substr($rowsS, 1, -1));
$maxLenName = $maxLenRef = $maxLenSKey = $maxLenRegions = 0;
$DB->StQuery("Truncate Countries");
$DB->autocommit(false);
$Id = $blankBefore = 0;
foreach ($rowsA as $rowi => $row) {
  if (!strlen($row)) {
    $blankBefore = 1;
    continue;
  }
  ++$Id;
  # echo "$Id $rowi $row<br>";
  $colsA = explode(TAB, $row);
  $isoNum  = (int)($colsA[0]);
  $name    = trim($colsA[1]);
  $sortKey = substr("00$Id", -3);
  if (!isset($colsA[2]) || ! ($ref = trim($colsA[2])))
    $ref = str_replace(SP, '', $name);
  $set = "Name=\"$name\",Ref=\"$ref\",SortKey='$sortKey'";
  $maxLenName = max($maxLenName, strlen($name));
  $maxLenRef  = max($maxLenRef,  strlen($ref));
  $maxLenSKey = max($maxLenSKey, strlen($sortKey));
  if ($isoNum)
    $set .= ",ISOnum=$isoNum";
  if ($blankBefore) {
    $set .= ',Bits=1';
    $blankBefore = 0;
  }
  $DB->StQuery("Insert into Countries Set $set");
  echo "$name<br>";
}
$Id = 0;
foreach ($rowsA as $rowi => $row) {
  if (!strlen($row))
    continue;
  ++$Id;
  $set = '';
  $colsA = explode(TAB, $row);
  # PartOf
  if (isset($colsA[3]) && ($v = trim($colsA[3]))) {
    if (!$id = $DB->OneQuery("Select Id from Countries Where Name='$v'"))
      if (!$id = $DB->OneQuery("Select Id from Countries Where Ref='$v'"))
        die("PartOf $v in row $rowi $row not found");
    $set .= ",PartOfId=$id";
  }
  # Assoc
  if (isset($colsA[4]) && ($v = trim($colsA[4]))) {
    if (!$id = $DB->OneQuery("Select Id from Countries Where Name='$v'"))
      if (!$id = $DB->OneQuery("Select Id from Countries Where Ref='$v'"))
        die("Assoc $v in row $rowi $row not found");
    $set .= ",AssocId=$id";
  }
  if (isset($colsA[5]) && ($v = trim($colsA[5]))) {
    $Regions='';
    foreach (explode(COM, $v) as $name) {
      $name = trim($name);
      if (!$id = $DB->OneQuery("Select Id from Regions Where Name='$name'"))
        if (!$id = $DB->OneQuery("Select Id from Regions Where Ref='$name'"))
          die("Region $v in row $rowi $row not found");
      $Regions .= IntToChr($id);
    }
    $maxLenRegions = max($maxLenRegions, strlen($Regions));
    $set .= ",Regions='$Regions'";
  }
  if ($set) {
    $set = substr($set, 1);
    $DB->StQuery("Update Countries Set $set Where Id=$Id");
    #echo "$Id PartOf=$partOf<br>";
  }
}
$DB->commit();
echo "<br>Done<br>Max length Name = $maxLenName<br>
Max length Ref = $maxLenRef<br>
Max length Sortkey = $maxLenSKey<br>
Max length Regions = $maxLenRegions<br>";


################
## Currencies ##
################
echo "<br><br><b>Building the SIM Currencies table</b><br>";
#    0          1
# Name | ISO Code = Ref
$rowsS = "
Australian Dollar	AUD
Canadian Dollar	CAD
Chinese Yuan	CNY
Euro	EUR
Japanese Yen	JPY
New Zealand Dollar	NZD
Pound Sterling	GBP
United States Dollar	USD

Afghan Afghani	AFN
Albanian Lek	ALL
Algerian Dinar	DZD
Angolan Kwanza	AOA
Argentine Peso	ARS
Armenian Dram	AMD
Aruban Florin	AWG
Azerbaijani Manat	AZN
Bahamian Dollar	BSD
Bahraini Dinar	BHD
Bangladeshi Taka	BDT
Barbados Dollar	BBD
Belarusian Ruble	BYR
Belize Dollar	BZD
Bermuda Dollar	BMD
Bhutanese Ngultrum	BTN
Boliviano	BOB
Bolivian Mvdol (funds code)	BOV
Bosnia and Herzegovina Convertible Mark	BAM
Botswana Pula	BWP
Brazilian Real	BRL
Brunei Dollar	BND
Bulgarian Lev	BGN
Burundian Franc	BIF
Cambodian Riel	KHR
Cape Verde Escudo	CVE
Cayman Islands Dollar	KYD
CFA Franc BCEAO	XOF
CFA Franc BEAC	XAF
CFP Franc	XPF
Chilean Peso	CLP
Colombian Peso	COP
Comoro Franc	KMF
Congolese Franc	CDF
Costa Rica Colon	CRC
Croatian Kuna	HRK
Cuban convertible Peso	CUC
Cuban Peso	CUP
Czech Koruna	CZK
Danish Krone	DKK
Djiboutian Franc	DJF
Dominican Republic Peso	DOP
East Caribbean Dollar	XCD
Egyptian Pound	EGP
Eritrean Nakfa	ERN
Ethiopian Birr	ETB
European Composite Unit (EURCO) (bond market unit)	XBA
European Monetary Unit (E.M.U.-6) (bond market unit)	XBB
European Unit of Account 9 (E.U.A.-9) (bond market unit)	XBC
European Unit of Account 17 (E.U.A.-17) (bond market unit)	XBD
Falkland Islands Pound	FKP
Fiji Dollar	FJD
Gambian Dalasi	GMD
Georgian Lari	GEL
Ghanaian Cedi	GHS
Gibraltar Pound	GIP
Gold (one troy ounce)	XAU
Guatemalan Quetzal	GTQ
Guinean Franc	GNF
Guyanese Dollar	GYD
Haitian Gourde	HTG
Honduran Lempira	HNL
Hong Kong Dollar	HKD
Hungarian Forint	HUF
Icelandic Króna	ISK
Indian Rupee	INR
Indonesian Rupiah	IDR
Iranian Rial	IRR
Iraqi Dinar	IQD
Israeli New Shekel	ILS
Jamaican Dollar	JMD
Jordanian Dinar	JOD
Kazakhstani Tenge	KZT
Kenyan Shilling	KES
Kuwaiti Dinar	KWD
Kyrgyzstani Som	KGS
Lao Kip	LAK
Latvian Lats	LVL
Lebanese Pound	LBP
Lesotho Loti	LSL
Liberian Dollar	LRD
Libyan Dinar	LYD
Lithuanian Litas	LTL
Macanese Pataca	MOP
Macedonian Denar	MKD
Malagasy Ariary	MGA
Malawian Kwacha	MWK
Malaysian Ringgit	MYR
Maldivian Rufiyaa	MVR
Mauritanian Ouguiya	MRO
Mauritian Rupee	MUR
Mexican Peso	MXN
Mexican Unidad de Inversion (UDI) (funds code)	MXV
Moldovan Leu	MDL
Mongolian Tugrik	MNT
Moroccan Dirham	MAD
Mozambican Metical	MZN
Myanma Kyat	MMK
Namibian Dollar	NAD
Nepalese Rupee	NPR
Netherlands Antilles Guilder	ANG
New Taiwan Dollar	TWD
Nicaraguan Córdoba	NIO
Nigerian Naira	NGN
North Korean Won	KPW
Norwegian Krone	NOK
Omani Rial	OMR
Pakistani Rupee	PKR
Palladium (one troy ounce)	XPD
Panamanian Balboa	PAB
Papua New Guinean Kina	PGK
Paraguayan Guaraní	PYG
Peruvian Nuevo Sol	PEN
Philippine Peso	PHP
Platinum (one troy ounce)	XPT
Polish Złoty	PLN
Qatari Riyal	QAR
Romanian New Leu	RON
Russian Rouble	RUB
Rwandan Franc	RWF
Saint Helena Pound	SHP
Samoan Tala	WST
São Tomé and Príncipe Dobra	STD
Saudi Riyal	SAR
SDR (IMF Special Drawing Rights)	XDR
Serbian Dinar	RSD
Seychelles Rupee	SCR
Sierra Leonean Leone	SLL
Silver (one troy ounce)	XAG
Singapore Dollar	SGD
Solomon Islands Dollar	SBD
Somali Shilling	SOS
South Africa Rand	ZAR
South Korean Won	KRW
South Sudanese Pound	SSP
Sri Lankan Rupee	LKR
Sudanese Pound	SDG
Surinamese Dollar	SRD
Swazi Lilangeni	SZL
Swedish Krona/Kronor	SEK
Swiss Franc	CHF
Syrian Pound	SYP
Tajikistani Somoni	TJS
Tanzanian Shilling	TZS
Thai Baht	THB
Tongan Paʻanga	TOP
Trinidad and Tobago Dollar	TTD
Tunisian Dinar	TND
Turkish Lira	TRY
Turkmenistani Manat	TMT
Ugandan Shilling	UGX
UIC Franc (special settlement currency)	XFU
Ukrainian Hryvnia	UAH
Unidad de Fomento (funds code)	CLF
Unidad de Valor Real	COU
United Arab Emirates Dirham	AED
United States Dollar (next day) (funds code)	USN
United States Dollar (same day) (funds code)	USS
Uruguay Peso en Unidades Indexadas (URUIURUI) (funds code)	UYI
Uruguayan Peso	UYU
Uzbekistan Som	UZS
Vanuatu Vatu	VUV
Venezuelan Bolívar Fuerte	VEF
Vietnamese Dong	VND
WIR Euro	CHE
WIR Franc	CHW
Yemen Rial	YER
Zambia Kwacha	ZMW
";

# Currencies
#    0          1
# Name | ISO Code = Ref
$rowsA = explode(NL, substr($rowsS, 1, -1));
$DB->StQuery("Truncate Currencies");
$Id = $blankBefore = $maxLenName = 0;
$DB->autocommit(false);
foreach ($rowsA as $rowi => $row) {
  if (!strlen($row)) {
    $blankBefore = 1;
    continue;
  }
  ++$Id;
  # echo "$Id $rowi $row<br>";
  $colsA = explode(TAB, $row);
  $name  = trim($colsA[0]);
  $ref   = trim($colsA[1]);
  $sortKey = substr("00$Id", -3);
  $maxLenName =  max($maxLenName,  strlen($name));
  $set = "Name=\"$name\",SortKey='$sortKey',Ref='$ref'";
  if ($blankBefore) {
    $set .= ',Bits=1';
    $blankBefore = 0;
  }
  $DB->StQuery("Insert into Currencies Set $set");
  echo "$name<br>";
}
$DB->commit();
echo "<br>Done<br>Max length Name = $maxLenName<br>";

####################
## StockExchanges ##
####################
echo "<br><br><b>Building the SIM StockExchanges table</b><br>";
#    0     1
# Name | Ref
$rowsS = "
Australia - ASX, Australian Securities Exchange|ASX
Belgium - Euronext, Brussels|EuronextBrussels
Brazil - BM&F Bovespa|Brazil
Canada - TMX Group|TMX
China - Shanghai Stock Exchange|Shanghai
China - Shenzhen Stock Exchange|Shenzhen
France - Euronext, Paris|EuronextParis
Germany - Deutsche Boerse|Deutsche
Hong Kong Stock Exchange|HKSE
India - Bombay Stock Exchange|Bombay
India - National Stock Exchange of India|NSEI
Japan - Tokyo Stock Exchange|Tokyo
Netherlands - Euronext, Amsterdam|EuronextAmsterdam
Russia - Moscow Exchange|Moscow
Singapore Exchange|Singapore
South Africa - JSE Limited|SouthAfrica
South Korea - Korea Exchange|SouthKorea
Spain - BME Spanish Exchanges|Spain
Switzerland - SIX Swiss Exchange|Switzerland
Taiwan Stock Exchange|Taiwan
UK - Alternate Investment Market|AIM
UK - London Stock Exchange|LSE
US - NASDAQ Stock Exchange - NASDAQ OMX Group|NASDAQ
US - New York Stock Exchange - NYSE Euronext|NYSE
";
# StockExchanges
#    0     1
# Name | Ref
$rowsA = explode(NL, substr($rowsS, 1, -1));
$DB->StQuery("Truncate Exchanges");
$Id = $blankBefore = $maxLenName = $maxLenRef = 0;
$DB->autocommit(false);
foreach ($rowsA as $rowi => $row) {
  if (!strlen($row)) {
    $blankBefore = 1;
    continue;
  }
  ++$Id;
  # echo "$Id $rowi $row<br>";
  $colsA = explode('|', $row);
  $name    = trim($colsA[0]);
  $ref     = trim($colsA[1]);
  $sortKey = substr("00$Id", -3);
  $set = "Name=\"$name\",Ref='$ref',SortKey='$sortKey'";
  $maxLenName = max($maxLenName, strlen($name));
  $maxLenRef  = max($maxLenRef, strlen($ref));
  if ($blankBefore) {
    $set .= ',Bits=1';
    $blankBefore = 0;
  }
  $DB->StQuery("Insert into Exchanges Set $set");
  echo "$name<br>";
}
$DB->commit();
echo "<br>Done<br>Max length Name = $maxLenName<br>
Max length Ref = $maxLenRef<br>";

###############
## Languages ##
###############
echo "<br><br><b>Building the SIM Languages table</b><br>";
#    0          1
# Name | ISO Code
$rowsS = "
English	en
Chinese	zh
Dutch	nl
French	fr
German	de
Italian	it
Japanese	ja
Korean	ko
Portuguese	pt
Spanish	es
Welsh	cy
";

# Languages
#    0          1
# Name | ISO Code = Ref
$rowsA = explode(NL, substr($rowsS, 1, -1));
$DB->StQuery("Truncate Languages");
$Id = $blankBefore = $maxLenName = 0;
$DB->autocommit(false);
foreach ($rowsA as $rowi => $row) {
  if (!strlen($row)) {
    $blankBefore = 1;
    continue;
  }
  ++$Id;
  # echo "$Id $rowi $row<br>";
  $colsA = explode(TAB, $row);
  $name  = trim($colsA[0]);
  $ref   = trim($colsA[1]);
  $sortKey = substr("00$Id", -3);
  $maxLenName =  max($maxLenName,  strlen($name));
  $set = "Name=\"$name\",SortKey='$sortKey',Ref='$ref'";
  if ($blankBefore) {
    $set .= ',Bits=1';
    $blankBefore = 0;
  }
  $DB->StQuery("Insert into Languages Set $set");
  echo "$name<br>";
}
$DB->commit();
echo "<br>Done<br>Max length Name = $maxLenName<br>";


/*##############
## Industries ## Industries - SIC Codes
################
CREATE TABLE IF NOT EXISTS Industries (
  Id      smallint unsigned not null auto_increment,
  Ref     char(8)           not null, # SIC Code
  Descr   varchar(210)      not null, #
  Primary Key (Id),
   Unique Key (Ref)
) Engine = InnoDB DEFAULT CHARSET=utf8;

Max length Ref   = 8
Max length Descr = 206

0 1 2   3  4    5  6
A	AGRICULTURE, FORESTRY AND FISHING
		01	Crop and animal production, hunting and related service activities
		  		01.1	Growing of non-perennial crops
		  		  	  	01.11	Growing of cereals (except rice), leguminous crops and oil seeds

A	AGRICULTURE, FORESTRY AND FISHING
		01	Crop and animal production, hunting and related service activities
				01.1	Growing of non-perennial crops
						01.11	Growing of cereals (except rice), leguminous crops and oil seeds
0 1 2  3  4   5   6
*/
echo "<br><br><b>Building the SIM Industries table</b><br>";
$DB->StQuery("Truncate Industries");
$file = 'Industries.txt';
$rowsA = file($file, FILE_IGNORE_NEW_LINES);
$maxLenRef = $maxLenDescr = $ref0 = 0;
$DB->autocommit(false);
foreach ($rowsA as $rowi => $row) {
  $row = rtrim($row);
  if (!strlen($row))
    continue;
  # echo "$rowi $row<br>";
  $colsA = explode(TAB, $row);
  if ($colsA[0]) {
    $ref = $colsA[0];
    $ref0 = $ref.DOT; # A.
    $d = 1;
  }else if ($colsA[2]) {
    $ref = $ref0.$colsA[2]; # A.01
    $d = 3;
  }else if ($colsA[4]) {
    $ref = $ref0.$colsA[4]; # A.01.1
    $d = 5;
  }else if ($colsA[6]) {
    $ref = $ref0.$colsA[6]; # A.01.11
    $d = 7;
  }
  $descr = $colsA[$d];
  $maxLenRef   = max($maxLenRef,   strlen($ref));
  $maxLenDescr = max($maxLenDescr, strlen($descr));
  $set = "Ref='$ref',Descr=\"$descr\"";
  $DB->StQuery("Insert into Industries Set $set");
  echo "$ref $descr<br>";
}
$DB->commit();
echo "<br>Done<br>Max length Ref = $maxLenRef<br>
Max length Descr = $maxLenDescr<br>";

Footer();
