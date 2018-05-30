<?php /* Copyright 2011-2013 Braiins Ltd

/Com/inc/Constants.inc
Constants that are common to applications or apps (Admin and all sites), or which form a sequence across sites,
or which are used by multiple sites, even if not all are used by each site.
Included by Base.inc modules which should set $AppAid (Alpha App 'id') before loading this

History:
04.02.11 Started from the LL/TARI version
19.10.12 Com_Inc_Tx and Com_Str_Tx defining added based on $TxName
28.04.13 Tidied up for Base.inc and B R L DB changes, removing Tx specific stuff
03.07.13 B R L -> SIM

ToDo djh??
====
Remove defining of Com_Inc_Tx and Com_Str_Tx

*/

error_reporting(E_ALL | E_STRICT);
# date_default_timezone_set('UTC'); # K was set to CST6CDT. Changed to UTC by K 03.01.13. PHP.ini should set this to UTC.

# Application Enums
# -----------------
#onst APP_APonline = 0; # djh?? Payment Processor
const APP_Admin    = 1; # Admin
const APP_Braiins  = 2; # Braiins main site
const APP_BDT      = 3; # Braiins DeskTop

# BDT App Enums using same #s as in BDT.js. Always >=10 so no conflict with APP_ values and so can be used in same DB AppN field.
# -------------    AppAid
const BDT_CUD = 10; # CUD Current entity Upload Data
const BDT_CDT = 13; # CDT                Data Trail
const BDT_CFS = 15; # CFS                Financial Statements
const BDT_CFD = 16; # CFD                Financial statements Download
const BDT_CSF = 17; # CSF                Set to Final

const BDT_EC  = 20; # EC  Entities Change
const BDT_EL  = 21; # EL           List
const BDT_EN  = 22; # EN           New
const BDT_EE  = 23; # EE           Edit Current
const BDT_ER  = 24; # ER           Reset
const BDT_ED  = 25; # ED           Delete

const BDT_AS  = 40; # AS  Admin Snapshot
#onst BDT_AR  = 41; # AR        Reports
#onst BDT_AR  = 42; # AC        Credits
const BDT_AA  = 43; # AA        Account
const BDT_AM  = 44; # AM        Members
const BDT_AH  = 45; # AH        Headings
#onst BDT_AL  = 46; # AL        Layout Options
#onst BDT_AX  = 47; # AX        Download Export Format

# Braiis
# ------
const BID = 1; # Braiins Agents.Id
const DJH = 1;
const CWW = 2;
const DEV_PERSON = DJH;

# Hosting specific defines
# ------------------------
if ($_SERVER['DOCUMENT_ROOT'][0] === '/') {
  # Hosted Braiins, either Braiins or Admin
  # /home/braiinsc/Braiins/Admin/www   = http://admin.braiins.com/
  # /home/braiinsc/Braiins/Braiins/www = http://www.braiins.com/
  define('Root_Path', '/home/braiinsc/Braiins/');
  define('WS_USER',   'braiinsc_Braiins');
  define('WS_PW',     '22iX45Cbh');
  define('DB_Prefix', 'braiinsc_');
  define('EMAIL_ADVISE','david@braiins.com,charles@braiins.com');
 #define('SERVER_TZOs', 0); # For Server Time Zone of UTC Removed 05.01.13 with both Php and Mysql time zones set to UTC
  define('REPORT_DateTimeFormat', '%e %b %Y %H:%M'); # dd MMM YYYY HH:MM = 17 characters
  define('REPORT_DateFormat',     '%e %b %Y');       # dd MMM YYYY HH:MM = 11 characters
  define('DEV', 1); # 12.07.11 djh?? Temporary. To be changed back to 0.
  define('LIVE',1);
  # $UrlCompactA for use by FuncsServer.inc.CompactUrl() which is not called by Admin modules
  $UrlCompactA = ['&amp;', 'http://', 'www.', 'braiins.com/', 'srv/', 'bdt/', 'apps/', "$AppAid/"];
}else{ # DOCUMENT_ROOT = C:/wamp/www
  # Braiins local, either Braiins or Admin
  define('Root_Path', 'C:/wamp/www/');
  define('WS_USER',   'root');
  define('WS_PW',     '');
  define('DB_Prefix', '');
  define('EMAIL_ADVISE','david@braiins.com');
 #define('SERVER_TZOs',  -36000); # For local in E Australian Time TZO = -600 mins = -36000 secs. Removed 05.01.13 with both Php and Mysql time zones set to UTC
  define('REPORT_DateTimeFormat', '%#d %b %Y %H:%M'); # %#d is Windows equivalent of %e
  define('REPORT_DateFormat', '%#d %b %Y');
  define('DEV', 1);
  define('LIVE',0);
  # $UrlCompactA for use by FuncsServer.inc.CompactUrl() which is not called by Admin modules
  $UrlCompactA = ['&amp;', 'http://localhost/', 'Braiins/www/', 'srv/', 'bdt/', 'apps/', "$AppAid/"];
}
define('DB_SIM',     DB_Prefix.'SIM');
define('DB_Braiins', DB_Prefix.'Braiins');
define('Com_Inc', Root_Path.'Com/inc/');
define('Com_Str', Root_Path.'Com/str/');
define('RG_Path', Root_Path.'Braiins/Rg/');

# Common hosting constants
# ------------------------
const WS_HOST     = '';  # '' = localhost
const APP_DOMAIN  = 'braiins.com'; # only used for email sender
const EMAIL_From  = 'Braiins Admin <admin@braiins.com>';
const EMAIL_ADMIN = 'Braiins Admin <admin@braiins.com>';
const EMAIL_LOG   = '../../Logs/Email.log';

# Common Characters
const D1 = ''; # Delimiter \1
const D2 = ''; # Delimiter \2
const D3 = ''; # Delimiter \3
const D4 = ''; # Delimiter \4
const SP = ' ';
const TAB= '	';
const DOT= '.';
const COM= ',';
const SQ = '\'';
const QSQ= '\\\''; # \' Quoted Single Quote
const DQ = '"';
const BR = '<br>';
const NL = '
';

# IP Source Enums
#----------------
const IPS_REMOTE_ADDR              = 1;
const IPS_HTTP_CLIENT_IP           = 2;
const IPS_HTTP_X_FORWARDED_FOR     = 3;
const IPS_HTTP_X_FORWARDED         = 4;
const IPS_HTTP_X_CLUSTER_CLIENT_IP = 5;
const IPS_HTTP_FORWARDED_FOR       = 6;
const IPS_HTTP_FORWARDED           = 7;

# DB Tables
# ---------
# Enums for DB Tables involving master ops where the table is passed to the ee function by enum, and the enum is used in field TableN of DBLog if logging is involved.
# The enums are also used with locks via the Status and Locks Memory tables.
# Not for BroSets, Formats, Registry, Status, Text, Visits, VisitTrans, BDTsessions, DBLog
const T_B_People      = 1; # /- Used in Locks table Login locks
const T_B_Agents      = 2; # |
const T_B_Entities    = 3; # |
const T_B_AgentData   = 4;
const T_B_AgentTrans  = 5;
const T_B_EntityTrans = 6;
const T_B_Dbos        = 7;
const T_B_DboTrans    = 8;
const T_B_Bros        = 9;
const T_B_BroTrans    = 10;
const T_B_Refs        = 11;
const T_B_ERefs       = 12;
const T_Sim_Regions    = 13;
const T_Sim_Countries  = 14;
const T_Sim_Currencies = 15;
const T_Sim_Exchanges  = 16;
const T_Sim_Languages  = 17;
const T_Sim_Industries = 18;
# DB_Braiins_Tables below is the default list of tables used by the BDB constructor. If the Braiins DB is not the Default DB and if
# any of these tables are being used then a modified version of this string with 'DbName." prefix before table names should be passed to BDB().
#onst DB_Braiins_Tables = ',People,Agents,Entities,AgentData,AgentTrans,EntityTrans,Bros,BroTrans,Refs,ERefs,SIM.Regions,SIM.Countries,SIM.Currencies,SIM.Exchanges,SIM.Languages';
define ('DB_Braiins_Tables', sprintf(',People,Agents,Entities,AgentData,AgentTrans,EntityTrans,Dbos,DboTrans,Bros,BroTrans,Refs,ERefs,%s.Regions,%s.Countries,%s.Currencies,%s.Exchanges,%s.Languages,%s.Industries', DB_SIM, DB_SIM, DB_SIM, DB_SIM, DB_SIM, DB_SIM));

# DBLog.TypeN Enums
const DBLOG_Insert = 1;
const DBLOG_Update = 2;
const DBLOG_Incr   = 3; # Atomic increment of column
const DBLOG_Delete = 4;
const DBLOG_Delete_GarbageCollect = 5; # Garbage Collect
const DBLOG_Delete_ReplaceImport  = 6; # Data deleted by a Replace type import

# Field Types re checking/cleaning data from client
# -----------
const FT_INT_EQ   =  1; # Int which could be '=' as passed from client for an unchanged or = field
const FT_INT      =  2;
const FT_BOOL     =  3;
const FT_PW       =  4;
const FT_EMAIL    =  5;
const FT_STR      =  6;
const FT_TEXTAREA =  7;
#onst FT_DEC     =  32;
#onst FT_DATE    =  64;
#onst FT_MINUS   = 256;
#onst FT_THOUSEP = 512;
#onst FT_SPACES  =1024;

# Pages pageN/100 -> AppN
# =====
# Admin
const PG_Admin  = 100; # Admin (all)

# Site
const PG_B_HP       = 201; # Braiins Home Page
const PG_B_About    = 202; #         About Us
const PG_B_Contact  = 203; #         Contact Us
const PG_B_FAQs     = 204; #         FAQs
const PG_B_FnB      = 205; #         Features and Benefits
const PG_B_Policies = 206; #         Policies
const PG_B_Sitemap  = 207; #         Sitemap
const PG_B_ToS      = 208; #         Terms of Service
const PG_B_Register = 209; #         Register
const PG_B_Trial    = 210; #         Free Trial
const PG_B_Prices   = 211; #         Prices
const PG_D_BDT      = 301; # BDT

# Time and DATE constants
# -----------------------
#onst STD_DTF_GMT   '%d %b %Y %H:%M GMT'); # standard date time format with GMT
#onst STD_DTF       '%d %b %Y %H:%M');     # standard date time format w.o. GMT
const MINUTE =    60;          # time in seconds
const HOUR   =    3600;
#onst HOURS6 =   21600;
#onst HOURS8 =   28800;
const DAY    =   86400;
const CENTURY_BREAK_2DIGIT_YEAR = 15; # Used by eeYmdToD() re 2 digit years. > CENTURY_BREAK_2DIGIT_YEAR -> + 1900
                                      #                                     <= CENTURY_BREAK_2DIGIT_YEAR -> + 2000
                                      # Should be rolled forward as the century advances. Could make this automatic...

# International Settings - Fixed here until such time as real international versions are needed
# ----------------------
const IS_ThouSep = ',';
const IS_DecChar = '.';

# Session Constants djh?? Change encoding randomly
# -----------------
#onst SESS_GC_NUM  =     100; # Max number of Visits records to process in one GC loop. Limit used to avoid hogging Visits
#onst SESS_GC_TIME =   86400; # Garbage Collect Time - day
const SESS_MN1     =      17; # /- 'Magic Numbers' used in formatting VisId for client trip
const SESS_MN2     = 7518623; # |  Number transferred = VisId * SESS_MN1 + SESS_MN2

# Master DB Table Standard Bits
# -----------------------------
const MB_DELETE   =  1; # 0 Flagged for deletion. In an Archive DB table means 'has been deleted'. Also unset MB_OK if set.
const MB_OK       =  2; # 1 OK or Valid. Unset to restrict access for whatever reason, without deletion.
const MB_STD_BITS =  3; #   Delete and OK
const MB_MASTER   =  4; # 2 Set for Braiins Master Agent or Entity. Also set for Member on BDT boot if Agent is a Master.

# LoginN status 'enum'  - must match JS equivalents in Constants.js
const LGN_NOT   = 0; # Not
const LGN_LIST  = 1; # List (Newsletter) member
const LGN_TENT  = 2; # Tentative - session information obtained from cookie, with Email address known, but user hasn't confirmed identity by entering password.
const LGN_FULL  = 3; # Full login
const LGN_GUEST = 4; # Guest

const LGN_MaxFails = 3; # Block login after this number of fails

# Locks.LockN Enums
const Lock_Login   = 1;
const Lock_Read    = 2;
const Lock_Write   = 3;

# VisitTrans.ActionN enum values
# ------------------  Op code
const V_SS  =  1; #   Session Start  = SessionStart and new Visits record created on an 'i' or 'I' initialisation op
const V_SSL =  2; #   Session Start via session Lite
const V_SR  =  3; #   Session Resume = SessionStart and previous Visits record used on an 'i' or 'I' initialisation op
const V_SO  =  4; #   Session Open == an operation on a page after an 'i' or 'I' initialisation op
const V_SOL =  5; #   Session Open via session Lite
const V_LI  =  6; # l Login OK
const V_LIF =  7; # l Login fail
const V_LO  =  8; # O LogOut       -> LGN_TENT
const V_LT  =  9; # T Logout Totally i.e. -> LGN_NOT with new LGN_NOT session.
const V_UP  = 10; #   Visit update for logged in member e.g. bits change
const V_UN  = 11; # u Page Unload - not a menu/btn caused page unload. Tran could be deleted by cron task if immediately followed by a V_SR tran
                  #   Get on          FF  Chrome
                  #    browser close Yes   Yes
                  #    tab close     Yes   Yes
                  #    reload tab    Yes   Yes
                  #    close btn     Yes   Yes
                  #    nav away      Yes   Yes
                  #    logout btn     No    No
//nst V_GC  = 12; #   Garbage collected termination

####################
# People Constants #
####################

#onst MT_Member  = 1; # Normal Member
#onst MT_Advisor = 2; # Advisor
#onst MT_List    = 3; # In the List = Newsletter subscriber but nothing else. AgentId = 0
#onst MT_Guest   = 4; # During a free trial

# People.Bits
# -----------
# Bits 0, 1 and 2 are Master DB Table Standard Bits
#                           Bit Byte
#onst MB_MASTER     =   4; #  2 0 Set for Person (a Member) on BDT boot if Agent or Entity is a Master.
const PB_Member     =   8; #  3 0 Set if a Member of an Agency. Unset with AgentId set if added by an Agent as a person for use as an Officer or Contact
const PB_FNameFirst =  16; #  4 0 Set if family name comes first as for Asian names
const PB_EmailOK    =  32; #  5 0 Set when email address has been confirmed
#onst PB_News       =  64; #  6 0 Set if to receive newsletter
#                          #  7 0 Spare
# Access Permission bits for Person who is a Member, which work in conjunction with Levels
const AP_Read    =    256; #  8 1 Read              Read or View incl TB, Data Trail, Financial Statements, Change Entity. Turning this off effectively prevents a member from doing anything.
const AP_AccData =    512; #  9 1 Accounting Data   Add (Post) and Edit current entity accounting data, incl Data Check
const AP_Entity  =   1024; # 10 1 Entities          Set to Final, Start Next Year, Add, Edit Entities. Start Next Year and Add involve a credits charge. Edit can do also.
const AP_Up      =   2048; # 11 1 Upload            Upload Accounting Data
const AP_Down    =   4096; # 12 1 Download          Download Financial Statements
const AP_Agent   =   8192; # 13 1 Agent Data        View and Edit Agent Data - Snapshot, Admin Reports, Account Details, Headings, Layout Options, and Download Export Format
const AP_Members =  16384; # 14 1 Members           Add/Edit Members
const AP_Credits =  32768; # 15 1 Purchase Credits  Purchase Credits
const AP_Delete  =  65536; # 16 2 Delete            Reset Entities, Delete Entities, Delete Members
const AP_Admin   = 131072; # 17 2 Administrator     All of the above plus Set/Unset these Access Permissions for Self and Others. There must always be at least one Administrator Member with this permission set.
const AP_All     =0x3FF00; #      All the AP bits except for AP_Compile
# Braiins Member Access Permissions
const AP_Compile = 262144; # 18 2 Able to compile formats
const AP_First   = AP_Read;
const AP_Last    = AP_Admin;

# People.SexN Enums with NONE or null meaning undefined
const MaleN   = 1;
const FemaleN = 2;

# People.Level values (for Members)
const MLevel_Max =  9; # Max access level = default for an Administrator
const MLevel_Min =  1; # Min access level

# People.RoleN           0  # Undefined
const ROL_SnrPartner  =  1; # Senior Partner
const ROL_Partner     =  2; # Partner
const ROL_Manager     =  3; # Manager
const ROL_Senior      =  4; # Staff Senior
const ROL_Staff       =  5; # Staff Member
const ROL_Owner       =  6; # Owner
const ROL_CEO         =  7; # MD or CEO
const ROL_FinDirector =  8; # Finance Director
const ROL_Director    =  9; # Director
const ROL_FinControl  = 10; # Financial Controller
const ROL_Contractor  = 11; # Contractor
const ROL_Consultant  = 12; # Consultant
const ROL_Other       = 13; # Other

# People.DeptN        0  # Undefined
const DEP_Management = 1; # Management
const DEP_Finance    = 2; # Finance
const DEP_Accounting = 3; # Accounting
const DEP_Audit      = 4; # Audit
const DEP_Tax        = 5; # Tax
const DEP_External   = 6; # External
const DEP_Other      = 7; # Other

# Person Dbo Indices
const DboI_P_DName      =  0; # DName      str    Display Name                                                   /- Also in People table but in Dbos too to keep a record by period
const DboI_P_Email      =  1; # Email      str    Email address which can be null for a person added as a Dbo    |   as these things can change
const DboI_P_Level      =  2; # Level      int  M Level re access control, from MLevel_Min (1) to MLevel_Max (9) |
                              #                   + Bits                                                         |
const DboI_P_GName      =  3; # GName      str    Given name  = First name in US usage
const DboI_P_FName      =  4; # FName      str    Family name = Last name in US usage
const DboI_P_Title      =  5; # Title      str    Title
const DboI_P_CtryId     =  6; # CtryId     int    Foreign key SIM.Countries.Id Country
const DboI_P_SexN       =  7; # SexN       int    MaleN or FemaleN with NONE or null meaning undefined
const DboI_P_RoleN      =  8; # RoleN      int  M Role enum ROL_SnrPartner, ROL_Partner, ROL_Manager, ROL_Senior, ROL_Staff, ROL_Owner, ROL_CEO, ROL_FinDirector, ROL_Director, ROL_FinControl, ROL_Contractor, ROL_Consultant, ROL_Other
const DboI_P_Role       =  9; # Role       str    Role or Position. Can be overridden for a Member via Members RoleN. Required for member if RoleN = ROL_Other
const DboI_P_DeptN      = 10; # DeptN      int  M Dept enum DEP_Management, DEP_Finance, DEP_Accounting, DEP_Audit, DEP_Tax, DEP_External, DEP_Other
const DboI_P_Descr      = 11; # Descr      str    Description of the person
const DboI_P_AddressesA = 12; # AddressesA i []   of AddressIds which apply to the Person
const DboI_P_ContactsA  = 13; # ContactsA  i []   of ContactIds which apply to the Person
                              #                 |- M = applies only to Members

# People.Id Constants
const CM_DH_ID = 1;
const CM_CW_ID = 2;
#onst CM_CC_ID_LIST = '1,2'; # DH,CH

const WEBMASTER_EMAIL = 'David Hartley <David@ElanElan.com>';

###################
# Agent Constants #
###################

# Agents.Bits           Bit
#onst MB_DELETE      1    0
#onst MB_OK          2    1
#onst MB_MASTER      4    2 Braiins Master Agent
#                         3 Spare
const AB_TaxAgent = 16; # 4 Tax agent (HMRC)
const AB_RegAgent = 32; # 5 Company registry agent (CH)
const AB_Entity   = 64; # 6 Company or Single Entity

# EntityData.Data indices
const ADI_HeadingsAA    = 0;
const ADI_PreferencesAA = 1;

# AgentData.TypeN Enums OoS as of 06.05.13 with reorganisation of Agents and AgentData
#onst ADT_AgentInfo   = 1; # Name and address
#onst ADT_Headings    = 2;
#onst ADT_Preferences = 3;

# Credits
const A_InitialGiftCredits = 10; # Credits gifted on initial add

# AgentTrans.TypeN Enums                                                    Comment
const ATT_Add             =  1; #   Add    Agent                            aName	CtryId
const ATT_Edit            =  2; #   Edit   Agent
const ATT_Delete          =  3; #   Delete Agent
const ATT_AddPerson       =  4; #   Add    Person
const ATT_AddMember       =  5; #   Add    Member
const ATT_EdtMember       =  6; #   Edit   Member
const ATT_DelMember       =  7; #   Delete Member
#onst ATT_SetAdmin              #   Set Member as Administrator
#onst ATT_RemAdmin              #   Remove Administrator status from Member
const ATT_AddEntity       =  8; # - Add    Entity                           EntityType Credits	EntitySize Credits{	Dim Group	DG credits...}
const ATT_EdtEntity       =  9; # * Edit   Entity                           As for Add above but empty if no change to credits
const ATT_DelEntity       = 10; #   Delete Entity
const ATT_PaymentFailed   = 11; #   Payment attempt failed
const ATT_Payment         = 12; # + Payment for purchase of credits         Payment Processor ref
const ATT_CreditsGift     = 13; # + Credits gifted                          'Registration gift credits' or ....
const ATT_CreditsPromo    = 14; # + Credits issued for promotional purposes
const ATT_CreditsSupport  = 15; # + Credits issued for support purposes
const ATT_CreditsNewYear  = 16; # - Credits charged on Start of a New Year  Entity Type Credits	EntitySize Credits{	Dim Group	DG credits...}
const ATT_CreditsCharged  = 17; # - Credits charged for other reason        The reason
const ATT_CreditsJournal  = 18; # * Credits adjustment  /- Via Admin only
const ATT_MoneyJournal    = 19; #   Money adjustment    |
const ATT_Refund          = 20; # - Refund              |
                                # |- Credits sign with * = + or -

# AgentTrans.PayTypeN Enums
#onst ATPT_PAYPAL       = 1; # PayPal
#onst ATPT_ALERTPAY     = 2; # AlertPay

#################
# Dbo Constants #
#################
const Dbo_PersonN  = 1; # For a Dbo Table record corresponding to a People table record
const Dbo_EntityN  = 2; # Entities
const Dbo_AddressN = 3; # Addresses
const Dbo_ContactN = 4; # Contacts

####################
# Entity Constants # plus Global arrays re Entity Credits and Dimension Groups
####################

# Entities.Level values
const ELevel_Max    =  9; # Max access level
const ELevel_Min    =  1; # Min access level i.e. can be accessed by everyone

# djh?? To go...
const Max_StdRelYear  = 3; # Std years run from 0 to 3 (Max_StdRelYear)
const EData_NumYears  = 4; # Number of years for which entity data is held -> RelYear range of 0 - 3
const Pya_Year_Offset = 3; # Year used for storing pya or restated Bro data = RelYear + Pya_Year_Offset -> 4,5,6 (Never have pya data for year 0)
const Max_RelYear     = 6; # RelYear range is 0 to 6

# Taxonomy Enums
/*nst TxN_UK_GAAP     = 1;
const TxN_UK_GAAP_DPL = 2;
const TxN_UK_IFRS_DPL = 3;

# Entities.ETypeN Enums         Base Credits for creating a New Entity
const ET_Sole             = 1; #  1 Sole Proprietorship/Individual
const ET_Partnership      = 2; #  2 Partnership
const ET_LLP              = 3; #  2 Limited Liability Partnership
const ET_Charity          = 4; #  4 Charity
const ET_PrivateLtdCo     = 5; #  2 Private Limited Company
const ET_PrivateUnltdCo   = 6; #  2 Private Unlimited Company
const ET_PrivateLtdGuarCo = 7; #  2 Private Limited by Guarantee Company
const ET_CommInterestCo   = 8; #  2 Community Interest Company
const ET_PLC              = 9; # 30 Public Limited Company
const ET_Other           = 10; #  5 Other
const ET_Num             = 10; */

# Kept temporarily for Entity New until these become a choice djh??
const TxN_UK_GAAP_DPL = 2;
const ET_PrivateLtdCo = 5; #  Private Limited Company

# Entity Size Enums Keep temporarily until Entity New and Entity Edit are re-written
# -----------------
const ES_Small      = 1; # /- UK GAAP
const ES_SmallFRSSE = 2; # |
const ES_Medium     = 3; # |
const ES_Large      = 4; # |
const ES_IFRS_SME   = 5; # IFRS

# Kept temporarily until built by BuildSimStructs djh??
$EntityTypeCreditsA = [0,1,2,2,4,2,2,2,2,30,5];
$EntitySizeCreditsA = [0,0,0,5,10,0];

# Entities.Bits
# -------------                Bit
# Bits 0, 1 are Master DB Table bits MB_DELETE, MB_OK as above.
#onst MB_MASTER      =     4; #  2 Set for Braiins Master Agent or Entity. Also set for Member on BDT boot if Agent is a Master.
# Bit  3 is a spare so that the EB bits start in the second byte.
const EB_Demo        =    16; #  4 Demo
const EB_Test        =    32; #  5 Test
const EB_Dbo         =    64; #  6 Dbo Entity i.e. an Entity for which accounts can't be produced; Entity not a client; Property Dbo Entity only
const EB_Active      =   128; #  7 Active  - normal status for an Entity
const EB_OnHold      =   256; #  8 OnHold  - set by an Agent if Entity account is on hold

# DataState Bits for the Period. None set = no postings
const EB_DS_OK       =   512; #  9 OK
const EB_DS_Errors   =  1024; # 10 Critical errors found
const EB_DS_Warnings =  2048; # 11 Warnings found
define('EB_DS', EB_DS_OK | EB_DS_Errors | EB_DS_Warnings);

# AcctsState Bits for the Period set if format of FTypeN = RGFT_Stat run and state changes, accompanied by an ETA_RgStat tran
# None set = Not Run
const EB_AS_Draft    =  4096; # 12 Draft           /- Set by RgRun.php on         /- MUX
const EB_AS_FinalC   =  8192; # 13 Final Candidate |  running an RGFT_Stat format |
const EB_AS_Final    = 16384; # 14 Final = Closed  Set by user                    |
const EB_AS_Down     = 32768; # 15 Downloaded
define('EB_AS', EB_AS_Draft | EB_AS_FinalC | EB_AS_Final | EB_AS_Down);

# TradingStatus Bits for the Period. None set = Not Defined
const EB_TS_Trading =  65536; # 16
const EB_TS_Admin   = 131072; # 17 In Administration
const EB_TS_Dormant = 262144; # 18
define('EB_TS', EB_TS_Trading | EB_TS_Admin | EB_TS_Dormant);

# Entity Dbo Indices
const DboI_E_EName        =  0; # EName        str Entity full name. Is the Agent Name (AName) for an Agent. /- Also in Entities table but in Dbos too to keep a record by period
const DboI_E_Level        =  1; # Level        int Level re access control                                   |   as these things can change
                                #                  + Bits above                                              |
const DboI_E_ETypeId      =  2; # ETypeId      int Foreign key SIM.EntityTypes.Id which specifies Taxonomies amongst other things
const DboI_E_ESizeId      =  3; # ESizeId      int Foreign key SIM.EntitySizes.Id
const DboI_E_MngrId       =  4; # MngrId       int Foreign key People.Id Agent's Manager for the Entity Account
const DboI_E_PropsInUse   =  5; # PropsInUse   int Properties in use via PropId bit setting
const DboI_E_PropsAllowed =  6; # PropsAllowed int Properties allowable via PropId bit setting = at least PropsInUse. Requires 64 bit ints.
const DboI_E_SName        =  7; # SName        str Short name
const DboI_E_TName        =  8; # TName        str Trading name
const DboI_E_TaxNum       =  9; # TaxNum       str Corporate/income tax registration number
const DboI_E_VatNum       = 10; # VatNum       str VAT/GST/ST registration number
const DboI_E_Descr        = 11; # Descr        str Description of the nature of the business, or principal activity
const DboI_E_Comments     = 12; # Comments     str Comments about the entity D2 separated. (Internal Agency comments)
const DboI_E_SuperiorsA   = 13; # SuperiorsA   i [] of EntityIds of Superiors.      Info on the relationship is held in the Superior. This array is maintained via the Superior.
const DboI_E_AssocsA      = 14; # AssocsA      i [] of EntityIds of Associates.     Info on FromDate, ToDate etc is held in Bros.
const DboI_E_JVsA         = 15; # JVsA         i [] of EntityIds of Joint Ventures. Details are held in Bros.
const DboI_E_OPIsA        = 16; # OPIsA        i [] of EntityIds of Other Participating Interests. Details are held in Bros.
const DboI_E_SusidsA      = 17; # SubsidsA     i [] of EntityIds of Subsidiaries.   Info on BasisDominantInfluence, FromDate, ToDate, Percentages etc is held in Bros.
const DboI_E_SPEsA        = 18; # SPEsA        i [] of EntityIds of Special Purpose Entities. Details are held in Bros.
const DboI_E_TPAsA        = 19; # TPAsA        i [] of EntityIds of Third Party Agents.       Info on the type and classification is held in Bros.
const DboI_E_OfficersA    = 20; # OfficersA    i [] of PersonIds of Officers.       Info on FromDate, ToDate, FromReason, ToReason etc is held in Bros.
const DboI_E_AddressesA   = 21; # AddressesA   i [] of AddressIds of Addresses which apply to the Entity
const DboI_E_ContactsA    = 22; # ContactsA    i [] of ContactIds of Contacts  which apply to the Entity

#############
# Addresses #
#############

# Addresses.Bits             Bit
#onst MB_DELETE          1     0
#onst MB_OK              2     1
const AddrB_Main    =    4; #  2 Main address
const AddrB_Postal  =    8; #  3 Postal address
const AddrB_Street  =   16; #  4 Street address
const AddrB_RegOff  =   32; #  5 Registered Office address
const AddrB_Branch  =   64; #  6 Branch address
const AddrB_Factory =  128; #  7 Factory address
const AddrB_RnD     =  256; #  8 Research and Development Centre address
const AddrB_Other   =  512; #  9 Other address
const AddrB_Home    = 1024; # 10 Home address

# Addresses.Data and Contacts.Data indices
const ACI_EntityIdsA = 0;
const ACI_PeopleIdsA = 1;
const ACI_Descr      = 2;

##############
## Contacts ##
##############

# Contacts.TypeN Enum
const Contact_Fixed    = 1; # Fixed or landline phone number including area code but excluding country code
const Contact_Mobile   = 2; # Mobile or Cell phone number including area code, if any, but excluding country code
const Contact_Fax      = 3; # Fax number including area code but excluding country code
const Contact_Skype    = 4; # Skype
const Contact_iMessage = 5; # Apple iMessage Id
const Contact_BBM      = 6; # BlackBerry Messenger Id
const Contact_FB       = 7; # FaceBook url or email
const Contact_Other    = 8; # Other

# Contacts.Bits                Bit
#onst MB_DELETE            1     0
#onst MB_OK                2     1
const ContactB_Direct =    4; #  2 Direct
const ContactB_SBoard =    8; #  3 Switchboard
const ContactB_Main   =   16; #  4 Main
const ContactB_Office =   32; #  5 Office
const ContactB_Home   =   64; #  6 Home
const ContactB_Day    =  128; #  7 Day   /- Mux
const ContactB_Night  =  256; #  8 Night |
const ContactB_Emerg  =  512; #  9 Emergency
const ContactB_Alt    = 1024; # 10 Alternative

# Contacts.Data indices are the same as for Adresses - see above.


# Dimension Groups or Reporting Requirements
# Bits are used for Entities.DGsInUse and Entities.DGsAllowed. Set if Dim Group is allowed/in use, off if not allowed.
#                         # AEUS Cr Dimensions in the Dim Group          Comments                                          Bit value
const DG_Group      =  0; #  CU  20  1 Group, 2 Consol, 27 Subsidiaries  Only Dimension Group with both E and U properties.        1
const DG_Restated   =  1; #   U   3  3 Restated                                                                                    2
                          # A        4 OpActivities
const DG_Excepts    =  2; #   U   2  5 ExceptAdjusts, 6 AmortAdjusts                                                               4
const DG_BizSegs    =  3; #   US  3  7 BizSegs                                                                                     8
const DG_Provisions =  4; #   U   0  8 Provisions                                                                                 16
const DG_IFAs       =  5; #   U   2  9 IFAClasses                                                                                 32
const DG_TFAs       =  6; #   U   1 10 TFAClasses,  11 TFAOwnership                                                               64
const DG_FAIs       =  7; #   U   2 12 FAIHoldings, 13 FAITypes                                                                  128
#onst DG_Shares         ; #  C    0 14 Dividends,   31 ShareClasses, 32 ShareTypes
const DG_Pensions   =  8; #   U   3 15 PensionSchemes, 16 ShareBasedPaymentSchemes                                               256
const DG_FinInstrs  =  9; #   U   2 17 FinInstrValueType, 18 FinInstrCurrentNonCurrent, 19 FinInstrLevel, 20 FinInstrMvts        512
const DG_Maturities = 10; #   U   1 21 MPeriods                                                                                 1024
const DG_Acqs       = 11; #   US  3 22 Acqs, 23 AcqAssetsLiabilities                                                            2048
const DG_Disposals  = 12; #   US  3 24 Disposals                                                                                4096
const DG_JVs        = 13; #   U   5 25 JVs                                                                                      8192
const DG_Assocs     = 14; #   U   5 26 Assocs                                                                                  16384
const DG_OtherInts  = 15; #   U   5 28 OtherInterestsOrInvests                                                                 32768
                          # A       29 Officers
                          # A       30 OfficerTypes
                          # A       33 ContactType
                          # A       34 TPAType
                          # A       35 TPAStatus
                          # A       36 FormContact
                          # A       37 AddressType
                          # A       38 PhoneNumberType
const DG_Countries  = 16; #   U   5 39 Countries                         Off: Base = UK for Wales, England etc              0x010000
const DG_Currencies = 17; #   U   5 40 Currencies                        Off: Base = GBP                                    0x020000
const DG_Languages  = 18; #   U   5 42 Languages                         Off: Base => English for Wales or Welsh?           0x040000
#onst DG_Exchanges      ; #  P    0 41 Exchanges                         Can be set according to Entity Type
const DG_Num        = 19; # ||||                                         Zero base
/*                          ||||- S = To be Excluded if Small Company
                            |||- U = User option at New/Edit Entity
                            ||- Entity type base, where
                            |   C = a company type i.e. type >= ET_PrivateLtdCo
                            |   P = PLC
                            |- A = Always available i.e. for all entity types with no user option to turn off/on

21.04.12 DG_Shares and DG_Exchanges use removed as they have zero credits and their dimension checking role can be performed via the Dimension Member ETypeList property. See Dimension Map.
*/

###########
# WARNING # If U is turned off for any of the S dim groups, U will need to be passed to New Entity and Edit Entity
########### for use by the CoSize change fn re not enabling the checkbox when ESizeId is set to Small or SmallFRSSE

$DimGroupsA = [
                 # Name                                    Credits  U  S  Dims in DG   Tips
DG_Group      => ['Group',                                      20, 0, 0, [1, 2, 27], 'Holding or Subsidiary Company as defined by FRS2 (NB: FRSSE exemptions make Group Accounts optional.)'],
DG_Restated   => ['Restatements or PYAs',                        3, 1, 0, [3],'Requirement for Restatement or Prior Year Adjustments'],
DG_Excepts    => ['Exceptions',                                  2, 1, 0, [5,6], 'Ordinary business items that are exceptional due to their sheer size or frequency as defined by FRS 3'],
DG_BizSegs    => ['Business Segments',                           3, 1, 1, [7], 'Business segmental reporting as defined by SSAP 25'],
DG_Provisions => ['Provisions',                                  0, 1, 0, [8],'Provisions and contingencies as defined by FRS 12'],
DG_IFAs       => ['Intangible Fixed Assets',                     2, 1, 0, [9],'Non-current assets without physical substance, CA 1985 & FRS 10'],
DG_TFAs       => ['Tangible Fixed Assets',                       1, 1, 0, [10,11],'Property, Plant & Equipment, CA 1985 & FRS 15, SSAP 19'],
DG_FAIs       => ['Fixed Asset Investment Holdings',             2, 1, 0, [12,13],'As defined by CA 1985; excludes JVs and Associates (separate options)'],
#G_Shares     => ['Shares',                                      0, 0, 0, [14,31,32],,],
DG_Pensions   => ['Pension Schemes',                             3, 1, 0, [15,16],'Pension disclosures as required by SSAP 24 and FRS 17'],
DG_FinInstrs  => ['Financial Instruments',                       2, 1, 0, [17,18,19,20],'Cash, monetary assets and liabilities, including shares and derivatives as defined by FRS 13'],
DG_Maturities => ['Maturities',                                  1, 1, 0, [21],'Period separation as defined by IFRS 7 and common practice'],
DG_Acqs       => ['Acquisitions',                                3, 1, 1, [22,23],'Business Combination as defined by FRS 2'],
DG_Disposals  => ['Disposals',                                   3, 1, 1, [24],'Business Disposal as defined by FRS 2'],
DG_JVs        => ['Joint Ventures',                              5, 1, 0, [25],'Joint Venture as defined by FRS 9'],
DG_Assocs     => ['Associates',                                  5, 1, 0, [26],'Associate as defined by FRS 9'],
DG_OtherInts  => ['Other participating interests or investments',5, 1, 0, [28],'Participating Interest as defined by FRS 9'],
DG_Countries  => ['Countries',                                   5, 1, 0, [39], 'Geographical segmental reporting as defined by SSAP 25'],
DG_Currencies => ['Currencies',                                  5, 0, 0, [40], 'Needed if the entity uses currencies other than pound sterling'],
DG_Languages  => ['Languages',                                   5, 0, 0, [42], 'Needed if the entity uses languages other than English']
#G_Exchanges  => ['Stock Exchanges',                             0, 0, 0, [41]
];

# Inner indices for $DimGroupsA
const DGI_Name    = 0; # Name of the Dim Group or Structure and Other Reporting Requirements Option
const DGI_Credits = 1; # Credits
const DGI_User    = 2; # 0|1 with 1 meaning is User config option via New/Edit Entity
const DGI_ExSmall = 3; # 0|1 with 1 meaning Dim Group is Excluded if Small Company
const DGI_DimsA   = 4; # array of the DimIds in the Dim Group
const DGI_Tip     = 5; # Tip for passing to New Entity/Edit Edtity via title attr
#onst DGI_ETsA    =  ; # optional array of entity types if the Dim group is allowable for only certain (these) entity types
# 21.04.12 DGI_ETsA use removed as the dimension checking role can be performed via the Dimension Member ETypeList property. See Dimension Map.


# EntityTrans.ActioN1/2 Enums
# ActioN1                                                                                    Info1
#onst ETA_Add             # Add    Entity  Covered by ATT_AddEntity Entity New
#onst ETA_Edit            # Edit   Entity  Covered by ATT_EdtEntity Entity Edit
#onst ETA_Delete          # Delete Entity  Covered by ATT_DelEntity Entity Delete
const ETA_Reset     =  1; # Reset  Entity
const ETA_Import    =  2; # Import data                                                      file # until storing in DB -> Imports.Id
const ETA_Post      =  3; # Post (Enter/Edit data)
const ETA_DataCheck =  4; # Data Check by specific prog rather than RG RGFT_Check format
const ETA_RgStat    =  5; # Report Generator RGFT_Stat run with Entities.AcctsState change   Formats.Id
const ETA_RgDown    =  6; # Report Generator Download                                        Formats.Id
const ETA_Final     =  7; # Set to Final
const ETA_FinalRev  =  8; # Final Setting Reversed

# ActioN2                                                                                    Info2
const ETA_SetDataState  = 1; # Set Entities.Bits re DataState   EB_DS_OK, EB_DS_Errors, EB_DS_Warnings  The new value
const ETA_SetAcctsState = 2; # Set Entities.Bits re AcctsState  EB_AS_Draft, EB_AS_FinalCand,...        The new value
# djh?? Could some of the ActioN1 enums be covered by EntityTrans.AppN?

# Format Enums and Bits
# ---------------------
# Formats.FTypeN Enums
const RGFT_Stat     = 1; # Statutory Accounts which can become Final
const RGFT_Report   = 2; # Schedule or supporting report
const RGFT_Check    = 3;
# Formats.Status Bits
const Status_OK       = 1; # Compiled and ready to run. Unset = not compiled or has compilation errors.
const Status_Prod     = 2; # Set for production. If unset only Braiins people can run it.
const Status_Archived = 4; # Set when replaced by a new version
const Status_Dormant  = 8; # Set if put to sleep = can't be used. May not be needed?

###########
# BroSets #
###########

# BroSets.BTypeN
# --------------
const BroSet_In_Main  = 1; # In MaIn-BroSet
const BroSet_In_Incl  = 2; # In Incl BroSet
const BroSet_Out_Main = 3; # Out MaIn-BroSet
const BroSet_Out_Incl = 4; # Out Incl BroSet

# BroSets.Status
#onst Status_OK         = 1; # As for Formats.Status Imported and ready to use. Unset = not imported successfully.
const Status_DefBroErrs = 2; # Imported ok except that there are Defined Bro warnings (errors really but shown in bros Import as warnings). Only applies to In-MaIn-BroSets. Allows WIP on the BroSet to be saved and exported, but not used for storing data.

# BroSets.Data indices
const BSI_InBroSetId   = 0;
const BSI_IncludesA    = 1;
const BSI_TaxnIdsA     = 2;
const BSI_CtryIdsA     = 3;
const BSI_ETypeIdsA    = 4;
const BSI_NotTaxnIdsA  = 5;
const BSI_NotCtryIdsA  = 6;
const BSI_NotETypeIdsA = 7;
const BSI_DateFrom     = 8;
const BSI_DateTo       = 9;
const BSI_OfficersBroId = 10;
const BSI_TPAsBroId     = 11;


# BroTrans.TypeN Enums
# --------------------
const BDTT_ImportInsert = 1;
const BDTT_ImportUpdate = 2;
const BDTT_Post         = 3;
const BDTT_Edit         = 4;

/* Email Bits
# ----------
#onst EMB_BOUNCED =  1; # 0 Not in use yet
const EMB_SERROR  =  2; # 1 Sending error
const EMB_TEXT    =  4; # 2
const EMB_HTML    =  8; # 3
const EMB_BOTH    = 16; # 4

# Email Enums
# -----------
const E_BOUNCED          = 1;
const E_REPEAT_REQUESTED = 2;
*/

# Countries
# ---------
const CTRY_UK = 1;

# Operation Codes for Ajax calls from client to server modules. On 24.08.08 moved previous numeric code to Admin's Constants.inc
# ---------------
#   m Login ? when there are Multiple members with the same email address and password, as for an advisor to multiple businesses
# * v Page View i.e. loading a page that does not have its own Ajax call, or one that does but
#     Ajax calls are optional depending on user activity. When used this Op
#     -> /wrk/LogWrk.php to log the page view activity.
#     Used to log the Closing of a Popup page via ClosePop() for reporting time on page purposes.
# Ops < 'a' require a MemId i.e. SessionOpen() is expected to define a MemId -> error abort if not
#     Add
#     Delete
#   F Fetch
#     History i.e. get History
#   P Prev
#   T logout Totally i.e. -> LGN_NOT with new session
#     Transaction
#     Update
#     neW
#     eXecute a utility
#     copY
# |- ops which count as Page Views for Tracking. Other ops are additonal Actions
const Op_i  = 105; # i Initialisation (of module)
const Op_l  = 108; # l Login attempt
const Op_r  = 114; # r Registration
const Op_u  = 117; # u Unload page                   -
# Ops < 97 or 'a' require a MemId i.e. SessionOpen() is expected to define a MemId -> error abort if not
const Op_B  =  66; # B Boot                          Loading
const Op_C  =  67; # C Change (Entity)               Loading
const Op_D  =  68; # D Delete or Download            Deleting
const Op_E  =  69; # E rEsetting                     Deleting
const Op_F  =  70; # F Fetch                         Loadingg
const Op_I  =  73; # I Initialise an App             Loading
const Op_N  =  78; # N New or Next                   Saving
const Op_O  =  79; # O logOut LGN_FULL -> LGN_TENT   -
const Op_R  =  82; # R Refresh or Report             Loading
const Op_S  =  83; # S Save or Set                   Saving
const Op_U  =  85; # U Update                        Saving
const Op_W  =  87; # W doWnload                      Downloading

#########################
# Return or Error Codes # For all Apps as all errors are handled by the one Error() fn.
#########################
#
const OK                   =   0; # /- Error message returned to client when error >= ERR_RETURN_TO_CLIENT
const NOT_LOGGEDIN         =  -1; # | Not logged in for a module which expects this.
const NOP_LOCK             =  -2; # | Op unable to be performed due to DB or site lock. (Not BDB Lock fail returns which are handled as Alert() or Error(message) returns.)
const ERR_USE_MODULE_ErrS  =  -3; # | Used when error message text is passed to Error() rather than one of these constants
const ERR_CONNECT_DB       =  -4; # | Unable to connect to the database server
const ERR_NOT_AUTH         =  -5; # | Authorisation failure - access denied
const ERR_AgentLoginNok    =  -6; # | Agent  login is no longer valid
const ERR_EntityLoginNok   =  -7; # | Entity login is no longer valid
const ERR_RETURN_TO_CLIENT =  -8; # - Error message returned to client when error >= ERR_RETURN_TO_CLIENT
                                  # /- Message is returned to client if DEV is set, o'wise 'Sorry, a data, communications, or server processing error has occurred, and been reported to Braiins for correction. Please try again later.'
const ERR_NoDatPosted      =  -8; # | No $_POST['Dat'] or only 1 character (Op) of $_POST['Dat']
const ERR_SessionInfo      =  -9; # | Error in/with nnVi, the first Dat field after the Op code
const ERR_Session          = -10; # | Invalid Session = no VisId or invalid VisId when a good VisId is expected to be passed from client
const ERR_Op               = -11; # | Invalid or unexpected Op code
const ERR_Unclean          = -12; # | Data from client failed security check - in function Clean()
const ERR_CLIENT_DATA      = -13; # | Data from client failed a validity check
const ERR_DB_DATA          = -14; # | Error or inconsistency in data retrieved from DB

# mySQL Error Codes
const mySQL_ERROR_DUPLICATE_ENTRY = 1062;

# Enum to String Functions
# ========================

#function LockStr($lockN) {
#  static $sSA = [0, 'Login', 'Read', 'Write'];
#  return $sSA[$lockN];
#}

function AppStr($appN) {
  static $sSA = [0, 'Admin', 'Braiins', 'BDT'];
  return $sSA[(int)$appN];
}

function EntityStatusStr($StatusN) {
  static $sSA = ['Unknown', 'Master', 'Demo', 'Test', 'Active', 'Dormant', 'To be deleted'];
  return $sSA[(int)$StatusN];
}

# Bits for Entities.Bits re DataState
# DataState Bits. None set = no postings
#const EB_DS_OK       =   512; #  9 OK
#const EB_DS_Errors   =  1024; # 10 Critical errors found
#const EB_DS_Warnings =  2048; # 11 Warnings found
#define('EB_DS', EB_DS_OK | EB_DS_Errors | EB_DS_Warnings);
function EntityDataStateStr($Bits) {
  if ($Bits & EB_DS) {
    if ($Bits & EB_DS_OK) return 'OK';
    $s='';
    if ($Bits & EB_DS_Errors)   $s  = '  Errors';
    if ($Bits & EB_DS_Warnings) $s .= ', Warnings';
    return substr($s, 2);
  }
  return 'No data';
}

# Bits for Entities.Bits re AcctsState
# AcctsState Bits set if format of FTypeN = RGFT_Stat run and state changes, accompanied by an ETA_RgStat tran
#const EB_AS_Draft    =  4096; # 12 Draft           /- Set by RgRun.php on         /- MUX
#const EB_AS_FinalC   =  8192; # 13 Final Candidate |  running an RGFT_Stat format |
#const EB_AS_Final    = 16384; # 14 Final = Closed  Set by user                    |
#const EB_AS_Down     = 32768; # 15 Downloaded
#define('EB_AS', EB_AS_Draft | EB_AS_FinalC | EB_AS_Final | EB_AS_Down);
function EntityAcctsStateStr($Bits) {
  switch ($Bits & EB_AS) {
    case EB_AS_Draft:     $s = 'Draft'; break;
    case EB_AS_FinalCand: $s = 'Final Candidate'; break;
    case EB_AS_Final:     $s = 'Final'; break;
    default: return 'Accounts not run';
  }
  if ($Bits & EB_AS_Down)  $s .= ', Downloaded';
  return $s;
}

function BroTransTypeStr($TypeN) {
  static $sSA = [0, 'Import', 'Import Update', 'Post', 'Edit'];
  return $sSA[(int)$TypeN];
}

