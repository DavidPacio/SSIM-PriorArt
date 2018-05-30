<?php /* Copyright 2011-2013 Braiins Ltd

DateTime.inc

Reduced version of the ElanElan DateTime.inc for Braiins use.

Calendar Days start with the 00:00:00 second as per strftime() and gmstrftime()

smallint eeYmdToD($y, $m, $d)   Returns an eeDate for a valid $y, $m, $d date, 0 for an invalid or out of range date
smallint StrToDate($ds)         Returns an eeDate eeDate for a valid date in yy{yy}-mm-dd or yy{yy}.mm.dd or yy{yy}/mm/dd form, 0 for an invalid or out of range date
string   eeDtoStr($D, $fmtS='') Return the eeDate $D formatted according to $fmtS, default '%Y-%m-%d'

History
08.05.11 djh Started
2
*/

# eeYmdToD
#
# Returns : Date in ee form for a valid date
#         : 0 for an invalid or out of range date
function eeYmdToD($y, $m, $d) {
  if ( $y < 1900 )
    $y += ($y > CENTURY_BREAK_2DIGIT_YEAR) ? 1900 : 2000;
  # valid date range is 01.03.1900 to 31.12.2078
  if ($m < 1 || $m > 12 || $y < 1900 || $y > 2078 || ($y == 1900 && $m <= 2)) # Invalid date
    return 0;
  # Calculate DaysInMonth
  $daysInMonth = 31 - ((int)(abs($m - 7.5)) % 2);
  if ($m==2) $daysInMonth -= 2 - !($y % 4);       # calculated # for Feb is 30 so subtract 2 or 1

  if ($d < 1 || $d > $daysInMonth)
    return 0;

  if ($m <= 2) {
    $y-- ; $m += 12;
  }
  return (int)((365.25 * ($y-1900)) + (int)(30.6001 * ($m + 1)) + $d - 62);
}

# smallint StrToDate($ds)
# Returns eeDate for date in {yy}yy-mm-dd or {yy}yy.mm.dd or yy{yy}/mm/dd form, 0 for an invalid or out of range date
function StrToDate($ds) {
  $dA  = preg_split("/[-.\/]+/", $ds);
  if (count($dA) == 3)
    return eeYmdToD($dA[0], $dA[1], $dA[2]);
  return 0;
}

/* eeDtoStr

Format the eeDate $D according to the format string $fmtS, or the default, returning the result.

Format string options as per PHP Manual for strftime() and gmstrftime()
• %a - abbreviated weekday name according to the current locale
• %A - full weekday name according to the current locale
• %b - abbreviated month name according to the current locale
• %B - full month name according to the current locale
• %c - preferred date and time representation for the current locale
• %C - century number (the year divided by 100 and truncated to an integer, range 00 to 99)
• %d - day of the month as a decimal number (range 01 to 31)
• %D - same as %m/%d/%y
• %e - day of the month as a decimal number, a single digit is preceded by a space (range ’ 1’ to ’31’)
• %h - same as %b
• %H - hour as a decimal number using a 24-hour clock (range 00 to 23)
• %I - hour as a decimal number using a 12-hour clock (range 01 to 12)
• %j - day of the year as a decimal number (range 001 to 366)
• %m - month as a decimal number (range 01 to 12)
• %M - minute as a decimal number
• %n - newline character
• %p - either ‘am’ or ‘pm’ according to the given time value, or the corresponding strings for the current locale
• %r - time in a.m. and p.m. notation
• %R - time in 24 hour notation
• %S - second as a decimal number
• %t - tab character
• %T - current time, equal to %H:%M:%S
• %u - weekday as a decimal number [1,7], with 1 representing Monday
• %U - week number of the current year as a decimal number, starting with the first Sunday as the first day of the first week
• %V - The ISO 8601:1988 week number of the current year as a decimal number, range 01 to 53, where week 1 is the
       first week that has at least 4 days in the current year, and with Monday as the first day of the week.
• %W - week number of the current year as a decimal number, starting with the first Monday as the first day of the first week
• %w - day of the week as a decimal, Sunday being 0
• %x - preferred date representation for the current locale without the time
• %X - preferred time representation for the current locale without the date
• %y - year as a decimal number without a century (range 00 to 99)
• %Y - year as a decimal number including the century
*/

function eeDtoStr($D, $fmtS='') {
  if (!$fmtS)
    $fmtS = REPORT_DateFormat; # '%Y-%m-%d';  # default format string
  if ($D >= 25569 && $D < 50424) # < 19.01.2038 the Unix time limit
    # On or after 01.01.1970 so gmstrftime() can be used directly
    # Use gmstrftime() rather than strftime() to avoid the locale time offset affecting things.
    # ($D - 25569) * DAY gives the Unix timestamp for midnight on the start of the day in question
    # i.e. eeDtoStr('%d.%m.%y %T', 25569) gives 01.01.70 00:00:00
    return gmstrftime($fmtS, ($D - 25569) * DAY);
  return ''; # Out of range
}
