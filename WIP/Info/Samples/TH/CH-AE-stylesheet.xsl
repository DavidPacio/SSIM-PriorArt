<?xml version="1.0" ?>

<!-- Copyright (c) 2004-2005 Companies House -->
                                                                                
<xsl:stylesheet version="1.0" xmlns="http://www.w3.org/1999/xhtml" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:pt="http://www.xbrl.org/uk/fr/gaap/pt/2004-12-01" xmlns:ae='http://www.companieshouse.gov.uk/ef/xbrl/uk/fr/gaap/ae/2009-06-21' xmlns:xbrli='http://www.xbrl.org/2003/instance' xmlns:iso4217='http://www.xbrl.org/2003/iso4217' xmlns:link='http://www.xbrl.org/2003/linkbase' xmlns:xlink='http://www.w3.org/1999/xlink' xmlns:gc="http://www.xbrl.org/uk/fr/gcd/2004-12-01" xmlns:html="http://www.w3.org/1999/xhtml" xmlns:date="http://exslt.org/dates-and-times" extension-element-prefixes="date" exclude-result-prefixes="html pt link xlink iso4217 xbrli ae gc">

  <xsl:output method="xml" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" />

  <xsl:variable name="pl_present">0<xsl:if test="//pt:ProfitLossOnOrdinaryActivitiesBeforeTax">1</xsl:if></xsl:variable>

  <!-- Start by examining the contexts. guessContexts will call main for us. -->
  <xsl:template match="/">
    <xsl:call-template name="guessContexts" />
  </xsl:template>

<date:month-lengths>
   <date:month>31</date:month>
   <date:month>28</date:month>
   <date:month>31</date:month>
   <date:month>30</date:month>
   <date:month>31</date:month>
   <date:month>30</date:month>
   <date:month>31</date:month>
   <date:month>31</date:month>
   <date:month>30</date:month>
   <date:month>31</date:month>
   <date:month>30</date:month>
   <date:month>31</date:month>
</date:month-lengths>

<xsl:template name="date:add">
	<xsl:param name="date-time" />
   <xsl:param name="duration" />
   <xsl:variable name="dt-neg" select="starts-with($date-time, '-')" />
   <xsl:variable name="dt-no-neg">
      <xsl:choose>
         <xsl:when test="$dt-neg or starts-with($date-time, '+')">
            <xsl:value-of select="substring($date-time, 2)" />
         </xsl:when>
         <xsl:otherwise>
            <xsl:value-of select="$date-time" />
         </xsl:otherwise>
      </xsl:choose>
   </xsl:variable>
   <xsl:variable name="dt-no-neg-length" select="string-length($dt-no-neg)" />
   <xsl:variable name="timezone">
      <xsl:choose>
         <xsl:when test="substring($dt-no-neg, $dt-no-neg-length) = 'Z'">Z</xsl:when>
         <xsl:otherwise>
            <xsl:variable name="tz" select="substring($dt-no-neg, $dt-no-neg-length - 5)" />
            <xsl:if test="(substring($tz, 1, 1) = '-' or 
                           substring($tz, 1, 1) = '+') and
                          substring($tz, 4, 1) = ':'">
               <xsl:value-of select="$tz" />
            </xsl:if>
         </xsl:otherwise>
      </xsl:choose>
   </xsl:variable>
   <xsl:variable name="new-dt">
      <xsl:if test="not(string($timezone)) or
                    $timezone = 'Z' or 
                    (substring($timezone, 2, 2) &lt;= 23 and
                     substring($timezone, 5, 2) &lt;= 59)">
         <xsl:variable name="dt" select="substring($dt-no-neg, 1, $dt-no-neg-length - string-length($timezone))" />
         <xsl:variable name="dt-length" select="string-length($dt)" />
         <xsl:variable name="du-neg" select="starts-with($duration, '-')" />
         <xsl:variable name="du">
            <xsl:choose>
               <xsl:when test="$du-neg"><xsl:value-of select="substring($duration, 2)" /></xsl:when>
               <xsl:otherwise><xsl:value-of select="$duration" /></xsl:otherwise>
            </xsl:choose>
         </xsl:variable>
         <xsl:if test="starts-with($du, 'P') and
                       not(translate($du, '0123456789PYMDTHS.', ''))">
            <xsl:variable name="du-date">
               <xsl:choose>
                  <xsl:when test="contains($du, 'T')"><xsl:value-of select="substring-before(substring($du, 2), 'T')" /></xsl:when>
                  <xsl:otherwise><xsl:value-of select="substring($du, 2)" /></xsl:otherwise>
               </xsl:choose>
            </xsl:variable>
            <xsl:variable name="du-time">
               <xsl:if test="contains($du, 'T')"><xsl:value-of select="substring-after($du, 'T')" /></xsl:if>
            </xsl:variable>
            <xsl:if test="(not($du-date) or
                           (not(translate($du-date, '0123456789YMD', '')) and
                            not(substring-after($du-date, 'D')) and
                            (contains($du-date, 'D') or 
                             (not(substring-after($du-date, 'M')) and
                              (contains($du-date, 'M') or
                               not(substring-after($du-date, 'Y'))))))) and
                          (not($du-time) or
                           (not(translate($du-time, '0123456789HMS.', '')) and
                            not(substring-after($du-time, 'S')) and
                            (contains($du-time, 'S') or
                             not(substring-after($du-time, 'M')) and
                             (contains($du-time, 'M') or
                              not(substring-after($du-time, 'Y'))))))">
               <xsl:variable name="duy-str">
                  <xsl:choose>
                     <xsl:when test="contains($du-date, 'Y')"><xsl:value-of select="substring-before($du-date, 'Y')" /></xsl:when>
                     <xsl:otherwise>0</xsl:otherwise>
                  </xsl:choose>
               </xsl:variable>
               <xsl:variable name="dum-str">
                  <xsl:choose>
                     <xsl:when test="contains($du-date, 'M')">
                        <xsl:choose>
                           <xsl:when test="contains($du-date, 'Y')"><xsl:value-of select="substring-before(substring-after($du-date, 'Y'), 'M')" /></xsl:when>
                           <xsl:otherwise><xsl:value-of select="substring-before($du-date, 'M')" /></xsl:otherwise>
                        </xsl:choose>
                     </xsl:when>
                     <xsl:otherwise>0</xsl:otherwise>
                  </xsl:choose>
               </xsl:variable>
               <xsl:variable name="dud-str">
                  <xsl:choose>
                     <xsl:when test="contains($du-date, 'D')">
                        <xsl:choose>
                           <xsl:when test="contains($du-date, 'M')"><xsl:value-of select="substring-before(substring-after($du-date, 'M'), 'D')" /></xsl:when>
                           <xsl:when test="contains($du-date, 'Y')"><xsl:value-of select="substring-before(substring-after($du-date, 'Y'), 'D')" /></xsl:when>
                           <xsl:otherwise><xsl:value-of select="substring-before($du-date, 'D')" /></xsl:otherwise>
                        </xsl:choose>
                     </xsl:when>
                     <xsl:otherwise>0</xsl:otherwise>
                  </xsl:choose>
               </xsl:variable>
               <xsl:variable name="duh-str">
                  <xsl:choose>
                     <xsl:when test="contains($du-time, 'H')"><xsl:value-of select="substring-before($du-time, 'H')" /></xsl:when>
                     <xsl:otherwise>0</xsl:otherwise>
                  </xsl:choose>
               </xsl:variable>
               <xsl:variable name="dumin-str">
                  <xsl:choose>
                     <xsl:when test="contains($du-time, 'M')">
                        <xsl:choose>
                           <xsl:when test="contains($du-time, 'H')"><xsl:value-of select="substring-before(substring-after($du-time, 'H'), 'M')" /></xsl:when>
                           <xsl:otherwise><xsl:value-of select="substring-before($du-time, 'M')" /></xsl:otherwise>
                        </xsl:choose>
                     </xsl:when>
                     <xsl:otherwise>0</xsl:otherwise>
                  </xsl:choose>
               </xsl:variable>
               <xsl:variable name="dus-str">
                  <xsl:choose>
                     <xsl:when test="contains($du-time, 'S')">
                        <xsl:choose>
                           <xsl:when test="contains($du-time, 'M')"><xsl:value-of select="substring-before(substring-after($du-time, 'M'), 'S')" /></xsl:when>
                           <xsl:when test="contains($du-time, 'H')"><xsl:value-of select="substring-before(substring-after($du-time, 'H'), 'S')" /></xsl:when>
                           <xsl:otherwise><xsl:value-of select="substring-before($du-time, 'S')" /></xsl:otherwise>
                        </xsl:choose>
                     </xsl:when>
                     <xsl:otherwise>0</xsl:otherwise>
                  </xsl:choose>
               </xsl:variable>
               <xsl:variable name="mult" select="($du-neg * -2) + 1" />
               <xsl:variable name="duy" select="$duy-str * $mult" />
               <xsl:variable name="dum" select="$dum-str * $mult" />
               <xsl:variable name="dud" select="$dud-str * $mult" />
               <xsl:variable name="duh" select="$duh-str * $mult" />
               <xsl:variable name="dumin" select="$dumin-str * $mult" />
               <xsl:variable name="dus" select="$dus-str * $mult" />

               <xsl:variable name="year" select="substring($dt, 1, 4) * (($dt-neg * -2) + 1)" />
               <xsl:choose>
                  <xsl:when test="$year and
                                  string($duy) = 'NaN' or 
                                  string($dum) = 'NaN' or 
                                  string($dud) = 'NaN' or 
                                  string($duh) = 'NaN' or 
                                  string($dumin) = 'NaN' or 
                                  string($dus) = 'NaN'" />
                  <xsl:when test="$dt-length > 4 or
                                  $dum or $dud or $duh or $dumin or $dus">
                     <xsl:variable name="month">
                        <xsl:choose>
                           <xsl:when test="$dt-length > 4">
                              <xsl:if test="substring($dt, 5, 1) = '-'">
                                 <xsl:value-of select="substring($dt, 6, 2)" />
                              </xsl:if>
                           </xsl:when>
                           <xsl:otherwise>1</xsl:otherwise>
                        </xsl:choose>
                     </xsl:variable>
                     <xsl:choose>
                        <xsl:when test="not($month) or $month > 12" />
                        <xsl:when test="$dt-length > 7 or
                                        $dud or $duh or $dumin or $dus">
                           <xsl:variable name="day">
                              <xsl:choose>
                                 <xsl:when test="$dt-length > 7">
                                    <xsl:if test="substring($dt, 8, 1) = '-'">
                                       <xsl:value-of select="substring($dt, 9, 2)" />
                                    </xsl:if>
                                 </xsl:when>
                                 <xsl:otherwise>1</xsl:otherwise>
                              </xsl:choose>
                           </xsl:variable>
                           <xsl:choose>
                              <xsl:when test="not($day) or $day > 31" />
                              <xsl:when test="$dt-length > 10 or
                                              $duh or $dumin or $dus">
                                 <xsl:if test="$dt-length = 10 or
                                               (substring($dt, 11, 1) = 'T' and
                                                substring($dt, 14, 1) = ':' and
                                                substring($dt, 17, 1) = ':')">
                                    <xsl:variable name="hour">
                                       <xsl:choose>
                                          <xsl:when test="$dt-length > 10"><xsl:value-of select="substring($dt, 12, 2)" /></xsl:when>
                                          <xsl:otherwise>0</xsl:otherwise>
                                       </xsl:choose>
                                    </xsl:variable>
                                    <xsl:variable name="minute">
                                       <xsl:choose>
                                          <xsl:when test="$dt-length > 10"><xsl:value-of select="substring($dt, 15, 2)" /></xsl:when>
                                          <xsl:otherwise>0</xsl:otherwise>
                                       </xsl:choose>
                                    </xsl:variable>
                                    <xsl:variable name="second">
                                       <xsl:choose>
                                          <xsl:when test="$dt-length > 10"><xsl:value-of select="substring($dt, 18)" /></xsl:when>
                                          <xsl:otherwise>0</xsl:otherwise>
                                       </xsl:choose>
                                    </xsl:variable>
                                    <xsl:if test="$hour &lt;= 23 and $minute &lt;= 59 and $second &lt;= 60">
                                       <xsl:variable name="new-second" select="$second + $dus" />
                                       <xsl:variable name="new-minute" select="$minute + $dumin + floor($new-second div 60)" />
                                       <xsl:variable name="new-hour" select="$hour + $duh + floor($new-minute div 60)" />
                                       <xsl:variable name="new-month" select="$month + $dum" />
                                       <xsl:call-template name="date:_add-days">
                                          <xsl:with-param name="year" select="$year + $duy + floor(($new-month - 1) div 12)" />
                                          <xsl:with-param name="month">
                                             <xsl:variable name="m">
                                                <xsl:choose>
                                                   <xsl:when test="$new-month &lt; 1"><xsl:value-of select="$new-month + 12" /></xsl:when>
                                                   <xsl:otherwise><xsl:value-of select="$new-month" /></xsl:otherwise>
                                                </xsl:choose>
                                             </xsl:variable>
                                             <xsl:choose>
                                                <xsl:when test="$m mod 12">
                                                   <xsl:value-of select="format-number($m mod 12, '00')" />
                                                </xsl:when>
                                                <xsl:otherwise>12</xsl:otherwise>
                                             </xsl:choose>
                                          </xsl:with-param>
                                          <xsl:with-param name="day" select="$day" />
                                          <xsl:with-param name="days" select="$dud + floor($new-hour div 24)" />
                                       </xsl:call-template>
                                       <xsl:text>T</xsl:text>
                                       <xsl:value-of select="format-number(($new-hour + 24) mod 24, '00')" />
                                       <xsl:text>:</xsl:text>
                                       <xsl:value-of select="format-number($new-minute mod 60, '00')" />
                                       <xsl:text>:</xsl:text>
                                       <xsl:if test="$new-second mod 60 &lt; 10">0</xsl:if>
                                       <xsl:value-of select="$new-second mod 60" />
                                       <xsl:value-of select="$timezone" />
                                    </xsl:if>
                                 </xsl:if>
                              </xsl:when>
                              <xsl:otherwise>
                                 <xsl:variable name="new-month" select="$month + $dum" />
                                 <xsl:call-template name="date:_add-days">
                                    <xsl:with-param name="year" select="$year + $duy + floor(($new-month - 1) div 12)" />
                                    <xsl:with-param name="month">
                                       <xsl:variable name="m">
                                          <xsl:choose>
                                             <xsl:when test="$new-month &lt; 1"><xsl:value-of select="$new-month + 12" /></xsl:when>
                                             <xsl:otherwise><xsl:value-of select="$new-month" /></xsl:otherwise>
                                          </xsl:choose>
                                       </xsl:variable>
                                       <xsl:choose>
                                          <xsl:when test="$m mod 12">
                                             <xsl:value-of select="format-number($m mod 12, '00')" />
                                          </xsl:when>
                                          <xsl:otherwise>12</xsl:otherwise>
                                       </xsl:choose>
                                    </xsl:with-param>
                                    <xsl:with-param name="day" select="$day" />
                                    <xsl:with-param name="days" select="$dud" />
                                 </xsl:call-template>
                                 <xsl:value-of select="$timezone" />
                              </xsl:otherwise>
                           </xsl:choose>
                        </xsl:when>
                        <xsl:otherwise>
                           <xsl:variable name="new-month" select="$month + $dum" />
                           <xsl:value-of select="format-number($year + $duy + floor(($new-month - 1) div 12), '0000')" />
                           <xsl:text>-</xsl:text>
                           <xsl:variable name="m">
                              <xsl:choose>
                                 <xsl:when test="$new-month &lt; 1"><xsl:value-of select="$new-month + 12" /></xsl:when>
                                 <xsl:otherwise><xsl:value-of select="$new-month" /></xsl:otherwise>
                              </xsl:choose>
                           </xsl:variable>
                           <xsl:choose>
                              <xsl:when test="$m mod 12">
                                 <xsl:value-of select="format-number($m mod 12, '00')" />
                              </xsl:when>
                              <xsl:otherwise>12</xsl:otherwise>
                           </xsl:choose>
                           <xsl:value-of select="$timezone" />
                        </xsl:otherwise>
                     </xsl:choose>
                  </xsl:when>
                  <xsl:otherwise>
                     <xsl:value-of select="format-number($year + $duy, '0000')" />
                     <xsl:value-of select="$timezone" />
                  </xsl:otherwise>
               </xsl:choose>
            </xsl:if>
         </xsl:if>
      </xsl:if>
   </xsl:variable>
   <xsl:choose>
     <xsl:when test="string-length($date-time) > 10">
       <xsl:value-of select="$new-dt" />
     </xsl:when>
     <xsl:otherwise>
       <xsl:value-of select="substring($new-dt, 1, string-length($date-time))" />
     </xsl:otherwise>
   </xsl:choose>
</xsl:template>

<xsl:template name="date:_add-days">
   <xsl:param name="year" />
   <xsl:param name="month" />
   <xsl:param name="day" />
   <xsl:param name="days" />
   <xsl:param name="new-day" select="'NaN'" />
   <xsl:variable name="leap" select="(not($year mod 4) and $year mod 100) or not($year mod 400)" />
   <xsl:variable name="month-days" select="document('')/*/date:month-lengths/date:month" />
   <xsl:variable name="days-in-month">
      <xsl:choose>
         <xsl:when test="$month = 2 and $leap">
            <xsl:value-of select="$month-days[number($month)] + 1" />
         </xsl:when>
         <xsl:otherwise>
            <xsl:value-of select="$month-days[number($month)]" />
         </xsl:otherwise>
      </xsl:choose>
   </xsl:variable>
   <xsl:choose>
      <xsl:when test="$new-day = 'NaN'">
         <xsl:call-template name="date:_add-days">
            <xsl:with-param name="year" select="$year" />
            <xsl:with-param name="month" select="$month" />
            <xsl:with-param name="new-day">
               <xsl:choose>
                  <xsl:when test="$day > $days-in-month">
                     <xsl:value-of select="$days-in-month + $days" />
                  </xsl:when>
                  <xsl:otherwise><xsl:value-of select="$day + $days" /></xsl:otherwise>
               </xsl:choose>
            </xsl:with-param>
         </xsl:call-template>
      </xsl:when>
      <xsl:otherwise>
         <xsl:choose>
            <xsl:when test="$new-day &lt; 1">
               <xsl:call-template name="date:_add-days">
                  <xsl:with-param name="year" select="$year - ($month = 1)" />
                  <xsl:with-param name="month">
                     <xsl:choose>
                        <xsl:when test="$month = 1">12</xsl:when>
                        <xsl:otherwise><xsl:value-of select="$month - 1" /></xsl:otherwise>
                     </xsl:choose>
                  </xsl:with-param>
                  <xsl:with-param name="new-day">
                     <xsl:variable name="days-in-new-month">
                        <xsl:choose>
                           <xsl:when test="$leap and $month = 3">29</xsl:when>
                           <xsl:when test="$month = 1">31</xsl:when>
                           <xsl:otherwise>
                              <xsl:value-of select="$month-days[$month - 1]" />
                           </xsl:otherwise>
                        </xsl:choose>
                     </xsl:variable>                     
                     <xsl:value-of select="$new-day + $days-in-new-month" />
                  </xsl:with-param>
               </xsl:call-template>
            </xsl:when>
            <xsl:when test="$new-day > $days-in-month">
               <xsl:call-template name="date:_add-days">
                  <xsl:with-param name="year" select="$year + ($month = 12)" />
                  <xsl:with-param name="month">
                     <xsl:choose>
                        <xsl:when test="$month = 12">1</xsl:when>
                        <xsl:otherwise><xsl:value-of select="$month + 1" /></xsl:otherwise>
                     </xsl:choose>
                  </xsl:with-param>
                  <xsl:with-param name="new-day" select="$new-day - $days-in-month" />
               </xsl:call-template>
            </xsl:when>
            <xsl:otherwise>
               <xsl:value-of select="format-number($year, '0000')" />
               <xsl:text>-</xsl:text>
               <xsl:value-of select="format-number($month, '00')" />
               <xsl:text>-</xsl:text>
               <xsl:value-of select="format-number($new-day, '00')" />
            </xsl:otherwise>
         </xsl:choose>
      </xsl:otherwise>
   </xsl:choose>
</xsl:template>

<!-- The main output routine -->
<xsl:template name="main">
  <xsl:param name="y1" />
  <xsl:param name="y1start" />
  <xsl:param name="y1end" />
  <xsl:param name="y1currency" />
  <xsl:param name="namey1" />
  <xsl:param name="y2" />
  <xsl:param name="y2start" />
  <xsl:param name="y2end" />
  <xsl:param name="y2currency" />
  <xsl:param name="namey2" />

  <!-- Explain how we allocated the contexts, to avoid confusion and ease debug -->
  <xsl:message>The following contexts are in year 1 (<xsl:value-of select="$namey1" />): <xsl:for-each select="$y1 | $y1start | $y1end"> <xsl:value-of select="." /><xsl:text> </xsl:text></xsl:for-each>
  </xsl:message>
  <xsl:message>The following contexts are in year 2 (<xsl:value-of select="$namey2" />): <xsl:for-each select="$y2 | $y2start | $y2end"> <xsl:value-of select="." /><xsl:text> </xsl:text></xsl:for-each>
  </xsl:message>

  <!-- Get currencies for the two years. -->
  <xsl:variable name="currencyUnitY1" select="$y1currency" />
  <xsl:variable name="currencyUnitY2" select="$y2currency" />

  <!-- And get the symbol for the years -->
  <xsl:variable name="currencyY1">
    <xsl:call-template name="getCurrencySymbol">
      <xsl:with-param name="uniqueUnits" select="$currencyUnitY1" />
      <xsl:with-param name="contexts" select="$y1" />
    </xsl:call-template>
  </xsl:variable>
  <xsl:variable name="currencyY2">
    <xsl:call-template name="getCurrencySymbol">
      <xsl:with-param name="uniqueUnits" select="$currencyUnitY2" />
      <xsl:with-param name="contexts" select="$y2" />
    </xsl:call-template>
  </xsl:variable>
                                                                                
  <!-- HTML header -->
<html>
  <head>
    <meta name="generator"><xsl:attribute name="content"><xsl:value-of select="concat('DecisionSoft, using ', system-property('xsl:vendor'),' ',system-property('xsl:version'))" /></xsl:attribute></meta>
    <style type="text/css">
    body { font-family: sans-serif; }
    .rightalign { text-align: right }

    h1 { font-size: 100%; font-weight: bold; color: black; }

    h2 { font-size: 100%; font-weight: bold; margin: 0.25em 0 1em 0; text-align: center; }

    h3 { font-size: 100%; font-weight: bold; margin: 0.5em 0 }
    .nospace { padding-top: 0 }
    

    .rnheader { border: 0; padding: 0.1em 0.5em; margin: 0 0 1em 0; }
    
   a.footnoteLink { text-decoration: none; }

   p { margin: 0.5em 0; }

   hr { background-color:#666666; }

   a { color: black;  }
   a:hover { text-decoration: none; }
    </style>
    <title>Accounts</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  </head>
  <body>

    <table border="0" width="100%">

<!-- $Id: CH-AE-stylesheet.dsl,v 1.212 2007-03-07 10:25:57 mlr Exp $ -->

<xsl:variable name="doc_title"><xsl:choose>
    <xsl:when test="//ae:DateApprovalDirectorsReport">Report and Accounts</xsl:when>
    <xsl:otherwise>Abbreviated Accounts</xsl:otherwise>
  </xsl:choose></xsl:variable>

<tr>
  <td align="center">
    <table><tr>
      <td style="whitespace: no-wrap; font-weight: bold;" align="left">
        Registered Number <xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:CompaniesHouseRegisteredNumber' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template><br/>
        <h1><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//gc:EntityCurrentLegalName' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></h1>
        <h1><xsl:value-of select="$doc_title"/></h1>
        <h1><xsl:call-template name="formatDate"><xsl:with-param name="date" select="//gc:BalanceSheetDate" /></xsl:call-template></h1>
      </td>
    </tr></table>
  </td>
</tr>
</table>
<br style="page-break-after: always"/>


<!-- fn_* are the footnote flags, 1=footnote present, 0=not present -->
<xsl:variable name="fn_accounting_policies">0<xsl:if test="//ae:AccountingPolicy or //ae:AccountsPreparedUnderHistoricalCostConventionInAccordanceWithFRSE or //ae:AccountsPreparedUnderHistoricalCostConventionInAccordanceWithFRSSE05 or //ae:DepreciationRate">1</xsl:if></xsl:variable>
<xsl:variable name="fn_exchange_rates">0<xsl:if test="//ae:ForeignExchangeRate">1</xsl:if></xsl:variable>
<xsl:variable name="fn_operating_profit">0<xsl:if test="//pt:DepreciationAmountsWrittenOffFixedAssets or //pt:OperatingLeaseExpenditure or //pt:TotalFeesToAuditors">1</xsl:if></xsl:variable>
<xsl:variable name="fn_taxation">0<xsl:if test="//pt:TotalUKCurrentCorporationTax or //pt:TotalDeferredTax">1</xsl:if></xsl:variable>
<xsl:variable name="fn_debtors">0<xsl:if test="(//pt:TradeDebtors or //pt:OtherDebtors or //pt:PrepaymentsAccruedIncomeCurrentAsset or //pt:CalledUpShareCapitalNotPaidCurrentAsset) or //ae:DebtorsFreeTextNote">1</xsl:if></xsl:variable>
<xsl:variable name="fn_creditors_gt_one_year">0<xsl:if test="(//pt:BankLoansOverdraftsAfterOneYear or //pt:OtherCreditorsAfterOneYear or //pt:ObligationsUnderFinanceLeaseHirePurchaseContractsAfterOneYear or //pt:LoansFromDirectors) or ((//ae:InstalmentDebtsFallingDueAfter5Years or //ae:Non-InstalmentDebtsFallingDueAfter5Years or //ae:SecuredDebts or //ae:CreditorsDueAfterOneYearFreeTextNote) and (not(//ae:DisplayTotalCreditorsNote='true')))">1</xsl:if></xsl:variable>
<xsl:variable name="fn_twd">0<xsl:if test="//ae:TransactionsWithDirectors or //ae:TransactionWithDirector">1</xsl:if></xsl:variable>
<xsl:variable name="fn_rpd">0<xsl:if test="//pt:StatementOnRelatedPartyDisclosure">1</xsl:if></xsl:variable>
<xsl:variable name="fn_capital">0<xsl:if test="//pt:NonEquityAuthorisedDetails or //pt:EquityAuthorisedDetails or //pt:DetailsOrdinarySharesAllotted or //pt:NonEquityAllotedDetails">1</xsl:if></xsl:variable>
<xsl:variable name="fn_fas">0<xsl:if test="//ae:TotalFixedAssetsCostOrValuation">1</xsl:if></xsl:variable>
<xsl:variable name="fn_ifas">0<xsl:if test="(//pt:IntangibleFixedAssetsCostOrValuation or //pt:IntangibleFixedAssetsCostOrValuation or //pt:IntangibleFixedAssetsAggregateAmortisationImpairment or //pt:IntangibleFixedAssetsAmortisationChargedInPeriod or //pt:IntangibleFixedAssetsAggregateAmortisationImpairment or //pt:IntangibleFixedAssets or //pt:IntangibleFixedAssets) and $fn_fas=0">1</xsl:if></xsl:variable>
<xsl:variable name="fn_tfas">0<xsl:if test="(//pt:LandBuildingsCostOrValuation or //pt:PlantMachineryCostOrValuation or //pt:FixturesFittingsToolsEquipmentCostOrValuation or //pt:TangibleFixedAssetsCostOrValuation or //pt:LandBuildingsAdditions or //pt:PlantMachineryAdditions or //pt:FixturesFittingsToolsEquipmentAdditions or //pt:TangibleFixedAssetsAdditions or //pt:LandBuildingsDisposals or //pt:PlantMachineryDisposals or //pt:FixturesFittingsToolsEquipmentDisposals or //pt:TangibleFixedAssetsDisposals or //pt:LandBuildingsCostOrValuation or //pt:PlantMachineryCostOrValuation or //pt:FixturesFittingsToolsEquipmentCostOrValuation or //pt:TangibleFixedAssetsCostOrValuation or //pt:LandBuildingsDepreciation or //pt:PlantMachineryDepreciation or //pt:FixturesFittingsToolsEquipmentDepreciation or //pt:TangibleFixedAssetsDepreciation or //pt:LandBuildingsDepreciationChargeForPeriod or //pt:PlantMachineryDepreciationChargeForPeriod or //pt:FixturesFittingsToolsEquipmentDepreciationChargeForPeriod or //pt:TangibleFixedAssetsDepreciationChargeForPeriod or //pt:LandBuildingsDepreciationDisposals or //pt:PlantMachineryDepreciationDisposals or //pt:FixturesFittingsToolsEquipmentDepreciationDisposals or //pt:TangibleFixedAssetsDepreciationDisposals or //pt:LandBuildingsDepreciation or //pt:PlantMachineryDepreciation or //pt:FixturesFittingsToolsEquipmentDepreciation or //pt:TangibleFixedAssetsDepreciation or //pt:LandBuildings or //pt:PlantMachinery or //pt:FixturesFittingsToolsEquipment or //pt:TangibleFixedAssets or //pt:LandBuildings or //pt:PlantMachinery or //pt:FixturesFittingsToolsEquipment or //pt:TangibleFixedAssets) and $fn_fas=0">1</xsl:if></xsl:variable>
<xsl:variable name="fn_fais">0<xsl:if test="(//pt:TotalFixedAssetInvestmentsCostOrValuation or //pt:TotalFixedAssetInvestmentsAdditions or //pt:TotalFixedAssetInvestmentsDisposals or //ae:InvestmentsFixedAssetsRevaluations or //ae:InvestmentsFixedAssetsIncreaseDecreaseFromTransfersBetweenItems or //ae:InvestmentsFixedAssetsDepreciation or //ae:InvestmentsFixedAssetsDepreciationChargedInPeriod or //ae:InvestmentsFixedAssetsDepreciationOnDisposals or //ae:InvestmentsFixedAssetsDepreciationOnTransfers or //ae:InvestmentsFixedAssetsDepreciationOtherAdjustments or //ae:InvestmentsFixedAssetsFreeTextNote) and $fn_fas=0">1</xsl:if></xsl:variable>
<xsl:variable name="fn_ip">0<xsl:if test="(//pt:InvestmentPropertiesCostOrValuation or //pt:InvestmentPropertiesAdditions or //pt:InvestmentPropertiesDisposals or //pt:InvestmentPropertiesIncreaseDecreaseFromRevaluations or //pt:InvestmentPropertiesIncreaseDecreaseFromTransfersBetweenItems or //pt:InvestmentPropertiesDepreciation or //pt:InvestmentPropertiesDepreciationChargeForPeriod or //pt:InvestmentPropertiesDepreciationDisposals or //pt:InvestmentPropertiesDepreciationIncreaseDecreaseFromTransfersBetweenItems or //ae:InvestmentPropertyDepreciationOtherAdjustments or //ae:InvestmentPropertyFreeTextNote) and $fn_fas=0">1</xsl:if></xsl:variable>
<xsl:variable name="fn_total_creditors">0<xsl:if test="//ae:InstalmentDebtsFallingDueAfter5Years or //ae:Non-InstalmentDebtsFallingDueAfter5Years or //ae:SecuredDebts">1</xsl:if></xsl:variable>
<xsl:variable name="fn_creditors_lt_one_year">0<xsl:if test="//pt:TaxationSocialSecurityDueWithinOneYear or //ae:ConvertibleLoansWithinOneYear or //pt:OtherCreditorsDueWithinOneYear or //pt:AccrualsDeferredIncomeWithinOneYear or //ae:AmountsOwedToGroupUndertakingsDueWithinOneYear or //pt:TradeCreditorsWithinOneYear or //pt:BankLoansOverdraftsWithinOneYear or //ae:CreditorsDueWithinOneYearFreeTextNote">1</xsl:if></xsl:variable>
<xsl:variable name="fn_total_creditors_note_display">0<xsl:if test="(//ae:DisplayTotalCreditorsNote='true') or //ae:AggregateCreditorsFreeTextNote">1</xsl:if></xsl:variable>
<xsl:variable name="fn_other_creditors_after_one_year">0<xsl:if test="0">1</xsl:if></xsl:variable>
<xsl:variable name="fn_pl">0<xsl:if test="$pl_present = 1">1</xsl:if></xsl:variable>
<xsl:variable name="fn_footnotes" select="count(//link:footnoteLink)"/>
<!-- ~fn_* -->

<!-- fnn_* are the actual footnote number -->
<xsl:variable name="fnn_accounting_policies" select="1"/>
<xsl:variable name="fnn_exchange_rates" select="$fnn_accounting_policies + $fn_accounting_policies"/>
<xsl:variable name="fnn_operating_profit" select="$fnn_exchange_rates + $fn_exchange_rates"/>
<xsl:variable name="fnn_taxation" select="$fnn_operating_profit + $fn_operating_profit"/>
<xsl:variable name="fnn_fas" select="$fnn_taxation + $fn_taxation"/>
<xsl:variable name="fnn_ifas" select="$fnn_fas + $fn_fas"/>
<xsl:variable name="fnn_tfas" select="$fnn_ifas + $fn_ifas"/>
<xsl:variable name="fnn_fais" select="$fnn_tfas + $fn_tfas"/>
<xsl:variable name="fnn_ip" select="$fnn_fais + $fn_fais"/>
<xsl:variable name="fnn_debtors" select="$fnn_ip + $fn_ip"/>
<!-- the Total Creditors footnote is now just a subsection of the creditors >12mo footnote -->
<xsl:variable name="fnn_creditors_lt_one_year" select="$fnn_debtors + $fn_debtors"/>
<xsl:variable name="fnn_other_creditors_after_one_year" select="$fnn_creditors_lt_one_year + $fn_creditors_lt_one_year"/>
<xsl:variable name="fnn_creditors_gt_one_year" select="$fnn_other_creditors_after_one_year + $fn_other_creditors_after_one_year"/>
<xsl:variable name="fnn_total_creditors_note_display" select="$fnn_creditors_gt_one_year + $fn_creditors_gt_one_year"/>
<xsl:variable name="fnn_capital" select="$fnn_total_creditors_note_display + $fn_total_creditors_note_display"/>
<xsl:variable name="fnn_pl" select="$fnn_capital + $fn_capital"/>
<xsl:variable name="fnn_twd" select="$fnn_pl + $fn_pl"/>
<xsl:variable name="fnn_rpd" select="$fnn_twd + $fn_twd"/>
<xsl:variable name="fnn_footnotes" select="$fnn_rpd + $fn_rpd"/>
<xsl:variable name="fnn_freenotes" select="$fnn_footnotes + $fn_footnotes"/>

<!-- ~fnn_* -->

<xsl:variable name="periodOrYear"><xsl:choose><xsl:when test="//ae:PeriodColumnHeadingsUsed='true'">period</xsl:when><xsl:otherwise>year</xsl:otherwise></xsl:choose></xsl:variable>

<xsl:if test="//ae:RegisteredOfficeAddress or //ae:BusinessAddress or //ae:ListDirectorsExecutives or //ae:Bankers or //ae:Solicitors">
<table width="100%" class="rnheader">
<tr><td style="font-weight: bold"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//gc:EntityCurrentLegalName' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td><td align="right" style="font-weight: bold">Registered Number <xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:CompaniesHouseRegisteredNumber' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td></tr>
</table>
<table width="100%">
<tr><td colspan="8" align="center"><h2>Company Information</h2></td></tr>
</table>
<br/>
<xsl:if test="//ae:RegisteredOfficeAddress">
<table cellpadding="0" cellspacing="0">
        <tr>
          <td style='font-weight: bold'>
Registered Office:
          </td>
          <td>

          </td>
          <td style='font-weight: bold'>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
        <tr>
          <td>
<xsl:call-template name="address"><xsl:with-param name="address" select="//ae:RegisteredOfficeAddress"/></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
</table>
<br/>
</xsl:if>
<xsl:if test="//ae:BusinessAddress">
<table cellspacing="0" cellpadding="0">
        <tr>
          <td style='font-weight: bold'>
Business Address:
          </td>
          <td>

          </td>
          <td style='font-weight: bold'>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
<tr><td>
<xsl:if test="//ae:DescriptionBusinessAddress"><xsl:value-of select="//ae:DescriptionBusinessAddress"/><br/></xsl:if>
<xsl:call-template name="address"><xsl:with-param name="address" select="//ae:BusinessAddress"/></xsl:call-template>
</td></tr>
</table>
<br/>
</xsl:if>
<br/>
<xsl:if test="//ae:ReportingAccountants">
<table cellspacing="0" cellpadding="0">
        <tr>
          <td style='font-weight: bold'>
Reporting Accountants:
          </td>
          <td>

          </td>
          <td style='font-weight: bold'>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
        <tr>
          <td>
<xsl:if test='//ae:ReportingAccountants//ae:NameAccountants/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:ReportingAccountants//ae:NameAccountants' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
        <tr>
          <td>
<xsl:if test='//ae:ReportingAccountants//ae:DescriptionAccountants/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:ReportingAccountants//ae:DescriptionAccountants' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
        <tr>
          <td>
<xsl:call-template name="address"><xsl:with-param name="address" select="//ae:ReportingAccountants"/></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
</table>
<br/>
</xsl:if>
<xsl:if test="//ae:Bankers">
<table cellspacing="0" cellpadding="0">
        <tr>
          <td style='font-weight:bold'>
Bankers:
          </td>
          <td>

          </td>
          <td style='font-weight:bold'>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
        <tr>
          <td>
<xsl:if test='//ae:Bankers//ae:NameBankers/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:Bankers//ae:NameBankers' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
        <tr>
          <td>
<xsl:call-template name="address"><xsl:with-param name="address" select="//ae:Bankers"/></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
</table>
<br/>
</xsl:if>
<xsl:if test="//ae:Solicitors">
<table cellspacing="0" cellpadding="0" width="25%">
        <tr>
          <td style='font-weight:bold'>
Solicitors:
          </td>
          <td>

          </td>
          <td style='font-weight:bold'>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
        <tr>
          <td>
<xsl:if test='//ae:Solicitors//ae:NameSolicitors/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:Solicitors//ae:NameSolicitors' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
        <tr>
          <td>
<xsl:call-template name="address"><xsl:with-param name="address" select="//ae:Solicitors"/></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
</table>
<br/>
</xsl:if>
<!-- VAT registration number: avoid including empty table -->
<xsl:if test='//ae:VATRegistrationNumber'>
<table width="30%">
<tr><td style="font-weight:bold">VAT registration number</td><td><xsl:value-of select="//ae:VATRegistrationNumber"/></td></tr>
</table>
</xsl:if>
<br style="page-break-after: always" />
</xsl:if>

<!-- Following block needs to be removed when next block is re-instated for CAP09) -->
<xsl:if test="//ae:DateApprovalDirectorsReport">
<xsl:if test="not(//ae:NamePersonApprovingDirectorsReport and //ae:PositionPersonApprovingDirectorsReport)">
	<xsl:message terminate="yes">ae:DateApprovalDirectorsReport is present but ae:NamePersonApprovingDirectorsReport and ae:PositionPersonApprovingDirectorsReport are not</xsl:message>
</xsl:if>

<!-- following commented out until CAP09 implemented Oct09
<xsl:if test="//ae:DateApprovalDirectorsReport">
<xsl:if test="not(//ae:NamePersonApprovingDirectorsReport)">
	<xsl:message terminate="yes">ae:DateApprovalDirectorsReport is present but ae:NamePersonApprovingDirectorsReport is not</xsl:message>
</xsl:if>
-->


<table width="100%" class="rnheader">
<tr><td style="font-weight: bold"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//gc:EntityCurrentLegalName' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td><td align="right" style="font-weight: bold">Registered Number <xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:CompaniesHouseRegisteredNumber' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td></tr>
</table>
<table width="100%"><tr><td colspan="8" align="center"><h2>Directors' Report</h2></td></tr></table>
<table >
<tr><td colspan="8" >The directors present their report and accounts for the <xsl:value-of select="$periodOrYear"/> ended <xsl:call-template name="formatDate"><xsl:with-param name="date" select="//gc:BalanceSheetDate" /></xsl:call-template>.</td></tr>
<tr><td colspan="8"><h3>Principal Activity</h3></td></tr>
<tr><td colspan="8"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//gc:EntityBusinessDescription' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td></tr>
<xsl:if test="//ae:DirectorsReportGeneralText">
<tr><td colspan="8" ></td></tr>
<tr><td colspan="8"><xsl:value-of select="//ae:DirectorsReportGeneralText" /></td></tr>
</xsl:if>
<xsl:if test="//ae:PoliticalCharitableDonationsText">
<tr><td colspan="8"><h3>Political And Charitable Donations</h3><p><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:PoliticalCharitableDonationsText' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></p></td></tr>
</xsl:if>

</table>
<table>
<xsl:for-each select="//ae:ListDirectorsExecutives">
        <tr>
          <td style='font-weight:bold; padding-left: 5px;vertical-align:top'>
<xsl:if test="position()=1">Directors:</xsl:if>
          </td>
          <td>

          </td>
          <td style='font-weight:bold; padding-left: 5px;vertical-align:top'>

          </td>
          <td style='font-weight:bold; padding-left: 5px;vertical-align:top'>
<xsl:value-of select=".//pt:DirectorOrExecutivesName"/>
          </td>
          <td style='font-weight:bold; padding-left: 5px;vertical-align:top'>
<span style="font-weight:normal"><xsl:if test=".//pt:DateAssumedPosition">Appointed <xsl:call-template name="formatDate"><xsl:with-param name="date" select=".//pt:DateAssumedPosition" /></xsl:call-template></xsl:if><xsl:if test=".//pt:DateAssumedPosition and .//ae:DateResignation"><br/></xsl:if> <xsl:if test=".//ae:DateResignation">Resigned <xsl:call-template name="formatDate"><xsl:with-param name="date" select=".//ae:DateResignation" /></xsl:call-template></xsl:if></span>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
</xsl:for-each>
      <xsl:if test='//ae:CompanySecretarysName'>
        <tr>
          <td style='font-weight:bold; padding-left: 5px'>
Secretary:
          </td>
          <td>

          </td>
          <td style='font-weight:bold; padding-left: 5px'>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //ae:CompanySecretarysName/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //ae:CompanySecretarysName/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td style='font-weight:bold; padding-left: 5px'>
<xsl:if test='//ae:CompanySecretarysName/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:CompanySecretarysName' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      </xsl:if>
        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
</table>

<xsl:if test="//ae:DirectorShareholding">
<table>
        <tr>
          <td>
The following directors who served during the year and their interests in the share capital of the Company:
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
</table>
<br/>
<table>
<tr style="font-weight: bold">
	<td> </td>
	<td> </td>
	<td><div style="width:25px"> </div></td>
	<td><xsl:value-of select="$namey2"/></td>
	<td><div style="width:25px"> </div></td>
	<td><xsl:value-of select="$namey1"/></td>
</tr>
<xsl:for-each select="//ae:DirectorShareholding">
        <xsl:variable name="name" select="pt:DirectorOrExecutivesName"/>
        <xsl:for-each select="ae:Shareholding">
                <xsl:variable name="desc" select="./pt:DescriptionSharesOrDebentures"/>
                <!-- The following line selects all Shareholdings which either have a matching description, or no description, depending
                     on whether the current Shareholding has a description or not. -->
                <xsl:variable name="matchingDescriptions" select="../ae:Shareholding[(not($desc) and not(pt:DescriptionSharesOrDebentures)) or pt:DescriptionSharesOrDebentures=$desc]"/>
                <!-- The following condition performs an intersection of two node-sets by taking each node in the first set in turn, and performing a union of
                     it and the second set, and then seeing if the count of the resulting set is the same as the count of the vanilla second set. -->
                <xsl:if test="not(preceding-sibling::ae:Shareholding[count(.|$matchingDescriptions) = count($matchingDescriptions)])">
                     <tr>
                          <td><xsl:if test="position()=1"><xsl:value-of select="$name" /></xsl:if> </td>
                          <td><xsl:if test="$desc">(<xsl:value-of select="$desc" />)</xsl:if></td>
                          <td> </td>
                          <td><xsl:value-of select="$matchingDescriptions/pt:SharesDirectorOrExecutive[@contextRef=$y2end]" /></td>
                          <td> </td>
    	                  <td><xsl:value-of select="$matchingDescriptions/pt:SharesDirectorOrExecutive[@contextRef=$y1end]" /></td>
                     </tr>
                </xsl:if>
	</xsl:for-each>
</xsl:for-each>
</table>
</xsl:if>
<table>
<xsl:if test="//ae:DirectorsShareCapitalFreeTextNote"><tr><td colspan="6"><p><xsl:value-of select="//ae:DirectorsShareCapitalFreeTextNote"/></p></td></tr></xsl:if>
</table>

<xsl:if test="//ae:DirectorsReportFreeTextNote"><table><tr><td><p><xsl:value-of select="//ae:DirectorsReportFreeTextNote"/></p></td></tr></table></xsl:if>

<xsl:choose>
<xsl:when test="number(translate(//xbrli:context[@id=$y2]/xbrli:period/xbrli:startDate,'-:T', '')) &lt; number('20080406')">
<xsl:if test="//ae:AccountsAreInAccordanceWithPartVIICompaniesActRelatingToSmallCompanies = 'true'"><table><tr><td><p>The Directors' Report has been prepared in accordance with the special provisions in Part VII of the Companies Act 1985 relating to small companies.</p></td></tr></table></xsl:if>
</xsl:when>
<xsl:otherwise>
<xsl:if test="//ae:AccountsAreInAccordanceWithSpecialProvisionsCompaniesActRelatingToSmallCompanies-DirectorsReport = 'true'"><table><tr><td><p>These accounts have been prepared in accordance with the provisions applicable to companies subject to the small companies regime.</p></td></tr></table></xsl:if>
</xsl:otherwise>
</xsl:choose>

<p>This report was approved by the board on <xsl:call-template name="formatDate"><xsl:with-param name="date" select="//ae:DateApprovalDirectorsReport"/></xsl:call-template></p><p>And signed on their behalf by:<br/>
<xsl:for-each select="//ae:ApprovalDirectorsReport">
<xsl:choose>
    <xsl:when test="./ae:PositionPersonApprovingDirectorsReport">
    <span style="padding: 0 0 0 1em; font-weight:bold"><xsl:value-of select="./ae:NamePersonApprovingDirectorsReport"/>, <xsl:value-of select="./ae:PositionPersonApprovingDirectorsReport"/></span><br/>
  </xsl:when>
   <xsl:otherwise>
      <span style="padding: 0 0 0 1em; font-weight:bold"><xsl:value-of select="./ae:NamePersonApprovingDirectorsReport"/></span><br/>
   </xsl:otherwise>
</xsl:choose>
</xsl:for-each></p>

<!-- Following lines commented out as they apply to CAP09 and are not needed until 1 Oct 09. 
TO REINSTATE - just remove comment start and ends down to End of Directors Report  and remove this and line above - also Balance Sheet - line 2910

<xsl:choose>
    <xsl:when test="//ae:DateAccountsReceived">
         <xsl:choose>
             <xsl:when test="(number(translate(//xbrli:context[@id=$y2]/xbrli:period/xbrli:startDate,'-:T', '')) >= number('20080406')) and number(translate(//ae:DateAccountsReceived, '-:T', '')) >= number('20091001')">
                 <p style="font-weight: bold">This document was delivered using electronic communications and authenticated in accordance with the registrar's rules relating to electronic form, authentication and manner of delivery under section 1068 of the Companies Act 2006.</p>
             </xsl:when>
             <xsl:otherwise>
-->
                 <p style="font-weight: bold">This document was delivered using electronic communications and authenticated in accordance with section 707B(2) of the Companies Act 1985.</p>

<!--                 
                 
             </xsl:otherwise>
         </xsl:choose>
     </xsl:when>
</xsl:choose>

--> 
</xsl:if> 
<!-- End Directors' Report -->

<br style="page-break-after: always" />
<xsl:if test="$pl_present = 1">
<table width="100%" class="rnheader">
<tr><td style="font-weight: bold" align="left"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//gc:EntityCurrentLegalName' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td><td style="font-weight: bold" align="right">Registered Number <xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:CompaniesHouseRegisteredNumber' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td></tr>
</table>
<!-- P&L -->
<table width="100%">
<tr><td colspan="7" style="text-align:center">
	<h3>Profit and Loss Account for the <xsl:value-of select="$periodOrYear"/> ending <xsl:call-template name="formatDate"><xsl:with-param name="date" select="//xbrli:context[@id=$y2]//xbrli:endDate" /></xsl:call-template></h3>
</td></tr>
<tr><td width="51%"> </td><td width="1%"> </td><td width="7%">Notes</td><td width="19%" align="right" style="font-weight:bold"><xsl:value-of select="$namey2" /></td><td width="1%"> </td><td width="1%"> </td><td width="19%" align="right" style="font-weight:bold"><xsl:value-of select="$namey1" /></td><td width="1%"> </td></tr>

        <tr>
          <td>

          </td>
          <td>

          </td>
          <td style='text-align:right;font-weight:bold'>

          </td>
          <td style='text-align:right;font-weight:bold'>
<xsl:value-of select="$currencyY2" />
          </td>
          <td>

          </td>
          <td>

          </td>
          <td style='text-align:right;font-weight:bold'>
<xsl:value-of select="$currencyY1" />
          </td>
          <td>

          </td>
        </tr>


<!--
    Turnover
-->

      <xsl:if test='//pt:TurnoverGrossOperatingRevenue[@contextRef=$y2] or //pt:TurnoverGrossOperatingRevenue[@contextRef=$y1]'>
        <tr>
          <td>
Turnover
          </td>
          <td>

          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TurnoverGrossOperatingRevenue[@contextRef=$y2]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TurnoverGrossOperatingRevenue[@contextRef=$y1]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TurnoverGrossOperatingRevenue[@contextRef=$y2]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//pt:TurnoverGrossOperatingRevenue[@contextRef=$y2]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TurnoverGrossOperatingRevenue[@contextRef=$y2]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>
<xsl:if test='//pt:TurnoverGrossOperatingRevenue[@contextRef=$y1]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TurnoverGrossOperatingRevenue[@contextRef=$y1]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
        </tr>
      </xsl:if>

<!--
    Cost of Sales
-->

      <xsl:if test='//pt:CostSales[@contextRef=$y2] or //pt:CostSales[@contextRef=$y1]'>
        <tr>
          <td class=';'>
Cost of Sales
          </td>
          <td>

          </td>
          <td class=';'>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:CostSales[@contextRef=$y2]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:CostSales[@contextRef=$y1]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:CostSales[@contextRef=$y2]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td class=';'>
<xsl:if test='//pt:CostSales[@contextRef=$y2]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:CostSales[@contextRef=$y2]' /><xsl:with-param name='flipSign'>(</xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td class=';'>
<xsl:if test='//pt:CostSales[@contextRef=$y1]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:CostSales[@contextRef=$y1]' /><xsl:with-param name='flipSign'>(</xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
        </tr>
      </xsl:if>

<!--
    Gross profit or loss
-->

      <xsl:if test='//pt:GrossProfitLoss[@contextRef=$y2] or //pt:GrossProfitLoss[@contextRef=$y1]'>
        <tr>
          <td>
Gross profit or loss
          </td>
          <td>

          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:GrossProfitLoss[@contextRef=$y2]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:GrossProfitLoss[@contextRef=$y1]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:GrossProfitLoss[@contextRef=$y2]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td style='border-top: solid medium black;'>
<xsl:if test='//pt:GrossProfitLoss[@contextRef=$y2]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:GrossProfitLoss[@contextRef=$y2]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td style='border-top: solid medium black;'>
<xsl:if test='//pt:GrossProfitLoss[@contextRef=$y1]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:GrossProfitLoss[@contextRef=$y1]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
        </tr>
      </xsl:if>

        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>


<!--
    Distribution costs
-->

      <xsl:if test='//pt:DistributionCosts[@contextRef=$y2] or //pt:DistributionCosts[@contextRef=$y1]'>
        <tr>
          <td>
Distribution costs
          </td>
          <td>

          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:DistributionCosts[@contextRef=$y2]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:DistributionCosts[@contextRef=$y1]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:DistributionCosts[@contextRef=$y2]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//pt:DistributionCosts[@contextRef=$y2]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:DistributionCosts[@contextRef=$y2]' /><xsl:with-param name='flipSign'>(</xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>
<xsl:if test='//pt:DistributionCosts[@contextRef=$y1]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:DistributionCosts[@contextRef=$y1]' /><xsl:with-param name='flipSign'>(</xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
        </tr>
      </xsl:if>

<!--
    Administration costs
-->

      <xsl:if test='//pt:AdministrativeExpenses[@contextRef=$y2] or //pt:AdministrativeExpenses[@contextRef=$y1]'>
        <tr>
          <td>
Administration costs
          </td>
          <td>

          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:AdministrativeExpenses[@contextRef=$y2]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:AdministrativeExpenses[@contextRef=$y1]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:AdministrativeExpenses[@contextRef=$y2]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//pt:AdministrativeExpenses[@contextRef=$y2]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:AdministrativeExpenses[@contextRef=$y2]' /><xsl:with-param name='flipSign'>(</xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>
<xsl:if test='//pt:AdministrativeExpenses[@contextRef=$y1]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:AdministrativeExpenses[@contextRef=$y1]' /><xsl:with-param name='flipSign'>(</xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
        </tr>
      </xsl:if>

      <xsl:if test='//pt:TotalOperatingProfitLoss[@contextRef=$y2] or //pt:TotalOperatingProfitLoss[@contextRef=$y1]'>
        <tr>
          <td>
Operating profit / (loss)
          </td>
          <td>

          </td>
          <td>
<xsl:if test="$fn_operating_profit=1"><a href="#fn_operating_profit" class="footnoteLink"><xsl:value-of select="$fnn_operating_profit"/></a></xsl:if><xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TotalOperatingProfitLoss[@contextRef=$y2]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TotalOperatingProfitLoss[@contextRef=$y1]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='extra_fn' select="$fn_operating_profit=1" /><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TotalOperatingProfitLoss[@contextRef=$y2]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td style='border-top: solid medium black;'>
<xsl:if test='//pt:TotalOperatingProfitLoss[@contextRef=$y2]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TotalOperatingProfitLoss[@contextRef=$y2]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td style='border-top: solid medium black;'>
<xsl:if test='//pt:TotalOperatingProfitLoss[@contextRef=$y1]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TotalOperatingProfitLoss[@contextRef=$y1]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
        </tr>
      </xsl:if>

        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>

<!--
    Other Income
-->

        <tr>
          <td>
Other Income
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>

<!--
    bank interest
-->

      <xsl:if test='//pt:TotalInterestPayableReceivableNet[@contextRef=$y2] or //pt:TotalInterestPayableReceivableNet[@contextRef=$y1]'>
        <tr>
          <td style='padding-left:10px;'>
bank interest
          </td>
          <td>

          </td>
          <td style='padding-left:10px;'>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TotalInterestPayableReceivableNet[@contextRef=$y2]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TotalInterestPayableReceivableNet[@contextRef=$y1]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TotalInterestPayableReceivableNet[@contextRef=$y2]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td style='padding-left:10px;'>
<xsl:if test='//pt:TotalInterestPayableReceivableNet[@contextRef=$y2]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TotalInterestPayableReceivableNet[@contextRef=$y2]' /><xsl:with-param name='flipSign'>(</xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td style='padding-left:10px;'>
<xsl:if test='//pt:TotalInterestPayableReceivableNet[@contextRef=$y1]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TotalInterestPayableReceivableNet[@contextRef=$y1]' /><xsl:with-param name='flipSign'>(</xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
        </tr>
      </xsl:if>
      <xsl:if test='//pt:ProfitLossOnOrdinaryActivitiesBeforeTax[@contextRef=$y2] or //pt:ProfitLossOnOrdinaryActivitiesBeforeTax[@contextRef=$y1]'>
        <tr>
          <td>
Profit / (loss) on ordinary activities before taxation
          </td>
          <td>

          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ProfitLossOnOrdinaryActivitiesBeforeTax[@contextRef=$y2]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ProfitLossOnOrdinaryActivitiesBeforeTax[@contextRef=$y1]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ProfitLossOnOrdinaryActivitiesBeforeTax[@contextRef=$y2]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td style='border-top: solid medium black;'>
<xsl:if test='//pt:ProfitLossOnOrdinaryActivitiesBeforeTax[@contextRef=$y2]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:ProfitLossOnOrdinaryActivitiesBeforeTax[@contextRef=$y2]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td style='border-top: solid medium black;'>
<xsl:if test='//pt:ProfitLossOnOrdinaryActivitiesBeforeTax[@contextRef=$y1]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:ProfitLossOnOrdinaryActivitiesBeforeTax[@contextRef=$y1]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
        </tr>
      </xsl:if>

<!--
    Taxation
-->

      <xsl:if test='//pt:TaxRelatedToProfitOrLossOnOrdinaryActivities[@contextRef=$y2] or //pt:TaxRelatedToProfitOrLossOnOrdinaryActivities[@contextRef=$y1]'>
        <tr>
          <td>
Taxation
          </td>
          <td>

          </td>
          <td>
<xsl:if test="$fn_taxation=1"><a href="#fn_taxation" class="footnoteLink"><xsl:value-of select="$fnn_taxation"/></a></xsl:if><xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TaxRelatedToProfitOrLossOnOrdinaryActivities[@contextRef=$y2]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TaxRelatedToProfitOrLossOnOrdinaryActivities[@contextRef=$y1]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='extra_fn' select="$fn_taxation=1" /><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TaxRelatedToProfitOrLossOnOrdinaryActivities[@contextRef=$y2]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//pt:TaxRelatedToProfitOrLossOnOrdinaryActivities[@contextRef=$y2]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TaxRelatedToProfitOrLossOnOrdinaryActivities[@contextRef=$y2]' /><xsl:with-param name='flipSign'>(</xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>
<xsl:if test='//pt:TaxRelatedToProfitOrLossOnOrdinaryActivities[@contextRef=$y1]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TaxRelatedToProfitOrLossOnOrdinaryActivities[@contextRef=$y1]' /><xsl:with-param name='flipSign'>(</xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
        </tr>
      </xsl:if>
      <xsl:if test='//pt:ProfitLossOnOrdinaryActivitiesAfterTax[@contextRef=$y2] or //pt:ProfitLossOnOrdinaryActivitiesAfterTax[@contextRef=$y1]'>
        <tr>
          <td>
Profit / (loss) on ordinary activities after taxation
          </td>
          <td>

          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ProfitLossOnOrdinaryActivitiesAfterTax[@contextRef=$y2]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ProfitLossOnOrdinaryActivitiesAfterTax[@contextRef=$y1]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ProfitLossOnOrdinaryActivitiesAfterTax[@contextRef=$y2]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td style='border-top: solid medium black;'>
<xsl:if test='//pt:ProfitLossOnOrdinaryActivitiesAfterTax[@contextRef=$y2]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:ProfitLossOnOrdinaryActivitiesAfterTax[@contextRef=$y2]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td style='border-top: solid medium black;'>
<xsl:if test='//pt:ProfitLossOnOrdinaryActivitiesAfterTax[@contextRef=$y1]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:ProfitLossOnOrdinaryActivitiesAfterTax[@contextRef=$y1]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
        </tr>
      </xsl:if>

        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>


<!--
    Dividends
-->

      <xsl:if test='//pt:GrossDividendPayment-AllShares[@contextRef=$y2] or //pt:GrossDividendPayment-AllShares[@contextRef=$y1]'>
        <tr>
          <td>
Dividends
          </td>
          <td>

          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:GrossDividendPayment-AllShares[@contextRef=$y2]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:GrossDividendPayment-AllShares[@contextRef=$y1]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:GrossDividendPayment-AllShares[@contextRef=$y2]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//pt:GrossDividendPayment-AllShares[@contextRef=$y2]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:GrossDividendPayment-AllShares[@contextRef=$y2]' /><xsl:with-param name='flipSign'>(</xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>
<xsl:if test='//pt:GrossDividendPayment-AllShares[@contextRef=$y1]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:GrossDividendPayment-AllShares[@contextRef=$y1]' /><xsl:with-param name='flipSign'>(</xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
        </tr>
      </xsl:if>
      <xsl:if test='//pt:RetainedProfitLossForFinancialYearTransferredToReserves[@contextRef=$y2] or //pt:RetainedProfitLossForFinancialYearTransferredToReserves[@contextRef=$y1]'>
        <tr>
          <td>
Retained profit/(loss) for the financial year
          </td>
          <td>

          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:RetainedProfitLossForFinancialYearTransferredToReserves[@contextRef=$y2]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:RetainedProfitLossForFinancialYearTransferredToReserves[@contextRef=$y1]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:RetainedProfitLossForFinancialYearTransferredToReserves[@contextRef=$y2]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td style='border-top: solid medium black;'>
<xsl:if test='//pt:RetainedProfitLossForFinancialYearTransferredToReserves[@contextRef=$y2]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:RetainedProfitLossForFinancialYearTransferredToReserves[@contextRef=$y2]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td style='border-top: solid medium black;'>
<xsl:if test='//pt:RetainedProfitLossForFinancialYearTransferredToReserves[@contextRef=$y1]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:RetainedProfitLossForFinancialYearTransferredToReserves[@contextRef=$y1]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
        </tr>
      </xsl:if>

        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>

      <xsl:if test='//pt:ProfitLossAccountReserve[@contextRef=$y2start and @unitRef=//xbrli:unit[.//xbrli:measure=$currencyUnitY2]/@id] or //pt:ProfitLossAccountReserve[@contextRef=$y1start and @unitRef=//xbrli:unit[.//xbrli:measure=$currencyUnitY1]/@id]'>
        <tr>
          <td>
Profit/(loss) brought forward
          </td>
          <td>

          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ProfitLossAccountReserve[@contextRef=$y2start and @unitRef=//xbrli:unit[.//xbrli:measure=$currencyUnitY2]/@id]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ProfitLossAccountReserve[@contextRef=$y1start and @unitRef=//xbrli:unit[.//xbrli:measure=$currencyUnitY1]/@id]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ProfitLossAccountReserve[@contextRef=$y2start and @unitRef=//xbrli:unit[.//xbrli:measure=$currencyUnitY2]/@id]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//pt:ProfitLossAccountReserve[@contextRef=$y2start and @unitRef=//xbrli:unit[.//xbrli:measure=$currencyUnitY2]/@id]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:ProfitLossAccountReserve[@contextRef=$y2start and @unitRef=//xbrli:unit[.//xbrli:measure=$currencyUnitY2]/@id]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>
<xsl:if test='//pt:ProfitLossAccountReserve[@contextRef=$y1start and @unitRef=//xbrli:unit[.//xbrli:measure=$currencyUnitY1]/@id]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:ProfitLossAccountReserve[@contextRef=$y1start and @unitRef=//xbrli:unit[.//xbrli:measure=$currencyUnitY1]/@id]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
        </tr>
      </xsl:if>
      <xsl:if test='//pt:ProfitLossAccountReserve[@contextRef=$y2end and @unitRef=//xbrli:unit[.//xbrli:measure=$currencyUnitY2]/@id] or //pt:ProfitLossAccountReserve[@contextRef=$y1end and @unitRef=//xbrli:unit[.//xbrli:measure=$currencyUnitY1]/@id]'>
        <tr>
          <td>
Retained profit/(loss) as of year end
          </td>
          <td>

          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ProfitLossAccountReserve[@contextRef=$y2end and @unitRef=//xbrli:unit[.//xbrli:measure=$currencyUnitY2]/@id]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ProfitLossAccountReserve[@contextRef=$y1end and @unitRef=//xbrli:unit[.//xbrli:measure=$currencyUnitY1]/@id]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ProfitLossAccountReserve[@contextRef=$y2end and @unitRef=//xbrli:unit[.//xbrli:measure=$currencyUnitY2]/@id]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td style='border-top: solid medium black;'>
<xsl:if test='//pt:ProfitLossAccountReserve[@contextRef=$y2end and @unitRef=//xbrli:unit[.//xbrli:measure=$currencyUnitY2]/@id]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:ProfitLossAccountReserve[@contextRef=$y2end and @unitRef=//xbrli:unit[.//xbrli:measure=$currencyUnitY2]/@id]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td style='border-top: solid medium black;'>
<xsl:if test='//pt:ProfitLossAccountReserve[@contextRef=$y1end and @unitRef=//xbrli:unit[.//xbrli:measure=$currencyUnitY1]/@id]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:ProfitLossAccountReserve[@contextRef=$y1end and @unitRef=//xbrli:unit[.//xbrli:measure=$currencyUnitY1]/@id]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
        </tr>
      </xsl:if>
<!-- ~P&L -->
</table>

</xsl:if>

<br style="page-break-after: always" />

<table width="100%" class="rnheader">
<tr><td style="font-weight: bold" align="left"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//gc:EntityCurrentLegalName' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td><td style="font-weight: bold" align="right">Registered Number <xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:CompaniesHouseRegisteredNumber' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td></tr>
</table>

<table width="100%">
<tr><td colspan="8" align="center"><h3>Balance Sheet as at <xsl:call-template name="formatDate"><xsl:with-param name="date" select="//gc:BalanceSheetDate"/></xsl:call-template></h3></td></tr>
<tr><td width="51%"> </td><td width="1%"> </td><td width="7%">Notes</td><td width="10%" style="font-weight:bold" align="right"><xsl:value-of select="$namey2" /></td><td width="10%"> </td><td width="1%"> </td><td width="10%" style="font-weight:bold" align="right"><xsl:value-of select="$namey1" /></td><td width="10%"> </td></tr>
        <tr>
          <td>

          </td>
          <td>

          </td>
          <td style='font-weight: bold;text-align:right'>
<xsl:if test="$fn_exchange_rates = 1"><div style="text-align:left"><a href="#fn_exchange_rates" style="font-weight:normal" class="footnoteLink"><xsl:value-of select="$fnn_exchange_rates"/></a></div></xsl:if>
          </td>
          <td style='font-weight: bold;text-align:right'>
<xsl:value-of select="$currencyY2"/>
          </td>
          <td style='font-weight: bold;text-align:right'>
<xsl:value-of select="$currencyY2"/>
          </td>
          <td>

          </td>
          <td style='font-weight: bold;text-align:right'>
<xsl:value-of select="$currencyY1"/>
          </td>
          <td style='font-weight: bold;text-align:right'>
<xsl:value-of select="$currencyY1"/>
          </td>
        </tr>

      <xsl:if test='//pt:CalledUpShareCapitalNotPaidNotExpressedAsCurrentAsset[@contextRef=$y2end] or //pt:CalledUpShareCapitalNotPaidNotExpressedAsCurrentAsset[@contextRef=$y1end]'>
        <tr>
          <td>
<b>Called up share capital not paid</b>
          </td>
          <td>

          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:CalledUpShareCapitalNotPaidNotExpressedAsCurrentAsset[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:CalledUpShareCapitalNotPaidNotExpressedAsCurrentAsset[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:CalledUpShareCapitalNotPaidNotExpressedAsCurrentAsset[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>

          </td>
          <td>
<xsl:if test='//pt:CalledUpShareCapitalNotPaidNotExpressedAsCurrentAsset[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:CalledUpShareCapitalNotPaidNotExpressedAsCurrentAsset[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>
<xsl:if test='//pt:CalledUpShareCapitalNotPaidNotExpressedAsCurrentAsset[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:CalledUpShareCapitalNotPaidNotExpressedAsCurrentAsset[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
        </tr>
      </xsl:if>
<xsl:if test="//pt:IntangibleFixedAssets or //pt:TangibleFixedAssets or //pt:TotalInvestmentsFixedAssets or //pt:FixedAssets">

<!--
    Fixed assets
-->

        <tr>
          <td style='font-weight: bold'>
Fixed assets
          </td>
          <td>

          </td>
          <td style='font-weight: bold'>
<xsl:if test="$fn_fas=1"><a href="#fn_fas" style="font-weight: normal" class="footnoteLink"><xsl:value-of select="$fnn_fas"/></a></xsl:if>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>

<!--
    Intangible
-->

      <xsl:if test='//pt:IntangibleFixedAssets[@contextRef=$y2end] or //pt:IntangibleFixedAssets[@contextRef=$y1end]'>
        <tr>
          <td class=';'>
Intangible
          </td>
          <td>

          </td>
          <td class=';'>
<xsl:if test="$fn_ifas=1"><a href="#fn_ifas" class="footnoteLink"><xsl:value-of select="$fnn_ifas"/></a></xsl:if><xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:IntangibleFixedAssets[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:IntangibleFixedAssets[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='extra_fn' select="$fn_ifas=1" /><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:IntangibleFixedAssets[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>

          </td>
          <td class=';'>
<xsl:if test='//pt:IntangibleFixedAssets[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:IntangibleFixedAssets[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td class=';'>
<xsl:if test='//pt:IntangibleFixedAssets[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:IntangibleFixedAssets[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
        </tr>
      </xsl:if>

        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>

<!--
    Tangible
-->

      <xsl:if test='//pt:TangibleFixedAssets[@contextRef=$y2end] or //pt:TangibleFixedAssets[@contextRef=$y1end]'>
        <tr>
          <td>
Tangible
          </td>
          <td>

          </td>
          <td>
<xsl:if test="$fn_tfas=1"><a href="#fn_tfas" class="footnoteLink"><xsl:value-of select="$fnn_tfas"/></a></xsl:if><xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TangibleFixedAssets[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TangibleFixedAssets[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='extra_fn' select="$fn_tfas=1" /><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TangibleFixedAssets[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>

          </td>
          <td>
<xsl:if test='//pt:TangibleFixedAssets[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TangibleFixedAssets[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>
<xsl:if test='//pt:TangibleFixedAssets[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TangibleFixedAssets[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
        </tr>
      </xsl:if>

        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>


<!--
    Investments
-->

      <xsl:if test='//pt:TotalInvestmentsFixedAssets[@contextRef=$y2end] or //pt:TotalInvestmentsFixedAssets[@contextRef=$y1end]'>
        <tr>
          <td>
Investments
          </td>
          <td>

          </td>
          <td>
<xsl:if test="$fn_fais=1"><a href="#fn_fais" style="font-weight: normal" class="footnoteLink"><xsl:value-of select="$fnn_fais"/></a></xsl:if><xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TotalInvestmentsFixedAssets[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TotalInvestmentsFixedAssets[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='extra_fn' select="$fn_fais=1" /><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TotalInvestmentsFixedAssets[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>

          </td>
          <td>
<xsl:if test='//pt:TotalInvestmentsFixedAssets[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TotalInvestmentsFixedAssets[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>
<xsl:if test='//pt:TotalInvestmentsFixedAssets[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TotalInvestmentsFixedAssets[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
        </tr>
      </xsl:if>

        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>

<!--
    Investment property
-->

      <xsl:if test='//pt:InvestmentProperties[@contextRef=$y2end] or //pt:InvestmentProperties[@contextRef=$y1end]'>
        <tr>
          <td>
Investment property
          </td>
          <td>

          </td>
          <td>
<xsl:if test="$fn_ip=1"><a href="#fn_ip" style="font-weight: normal" class="footnoteLink"><xsl:value-of select="$fnn_ip"/></a></xsl:if><xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:InvestmentProperties[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:InvestmentProperties[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='extra_fn' select="$fn_ip=1" /><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:InvestmentProperties[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>

          </td>
          <td>
<xsl:if test='//pt:InvestmentProperties[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:InvestmentProperties[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>
<xsl:if test='//pt:InvestmentProperties[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:InvestmentProperties[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
        </tr>
      </xsl:if>
        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      <xsl:if test='//pt:FixedAssets[@contextRef=$y2end] or //pt:FixedAssets[@contextRef=$y1end]'>
        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:FixedAssets[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:FixedAssets[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:FixedAssets[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>

          </td>
          <td style='border-top: solid medium black'>
<xsl:if test='//pt:FixedAssets[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:FixedAssets[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td style='border-top: solid medium black'>
<xsl:if test='//pt:FixedAssets[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:FixedAssets[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
        </tr>
      </xsl:if>
        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
</xsl:if> 
<xsl:if test="//pt:StocksInventory or //pt:Debtors or //pt:InvestmentsCurrentAssets or //pt:CashBankInHand or //pt:CurrentAssets or //ae:Stocks">

<!--
    Current assets
-->

        <tr>
          <td style='font-weight: bold'>
Current assets
          </td>
          <td>

          </td>
          <td style='font-weight: bold'>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>


<!--
    Stocks
-->

      <xsl:if test='//pt:StocksInventory[@contextRef=$y2end] or //pt:StocksInventory[@contextRef=$y1end]'>
        <tr>
          <td>
Stocks
          </td>
          <td>

          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:StocksInventory[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:StocksInventory[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:StocksInventory[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//pt:StocksInventory[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:StocksInventory[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>
<xsl:if test='//pt:StocksInventory[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:StocksInventory[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
        </tr>
      </xsl:if>

        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>

<!--
    Debtors
-->

      <xsl:if test='//pt:Debtors[@contextRef=$y2end] or //pt:Debtors[@contextRef=$y1end]'>
        <tr>
          <td>
Debtors
          </td>
          <td>

          </td>
          <td>
<xsl:if test="$fn_debtors=1"><a href="#fn_debtors" class="footnoteLink"><xsl:value-of select="$fnn_debtors"/></a></xsl:if><xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:Debtors[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:Debtors[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='extra_fn' select="$fn_debtors=1" /><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:Debtors[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//pt:Debtors[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:Debtors[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>
<xsl:if test='//pt:Debtors[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:Debtors[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
        </tr>
      </xsl:if>

        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>

<!--
    Investments
-->

      <xsl:if test='//pt:InvestmentsCurrentAssets[@contextRef=$y2end] or //pt:InvestmentsCurrentAssets[@contextRef=$y1end]'>
        <tr>
          <td>
Investments
          </td>
          <td>

          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:InvestmentsCurrentAssets[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:InvestmentsCurrentAssets[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:InvestmentsCurrentAssets[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//pt:InvestmentsCurrentAssets[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:InvestmentsCurrentAssets[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>
<xsl:if test='//pt:InvestmentsCurrentAssets[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:InvestmentsCurrentAssets[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
        </tr>
      </xsl:if>

        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>

<!--
    Cash at bank and in hand
-->

      <xsl:if test='//pt:CashBankInHand[@contextRef=$y2end] or //pt:CashBankInHand[@contextRef=$y1end]'>
        <tr>
          <td>
Cash at bank and in hand
          </td>
          <td>

          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:CashBankInHand[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:CashBankInHand[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:CashBankInHand[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//pt:CashBankInHand[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:CashBankInHand[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>
<xsl:if test='//pt:CashBankInHand[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:CashBankInHand[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
        </tr>
      </xsl:if>
        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>

<!--
    Total current assets
-->

      <xsl:if test='//pt:CurrentAssets[@contextRef=$y2end] or //pt:CurrentAssets[@contextRef=$y1end]'>
        <tr>
          <td class=';'>
Total current assets
          </td>
          <td>

          </td>
          <td class=';'>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:CurrentAssets[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:CurrentAssets[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:CurrentAssets[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td style=';border-top: solid thin black;border-bottom: solid thin black;'>
<xsl:if test='//pt:CurrentAssets[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:CurrentAssets[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td style=';border-top: solid thin black;border-bottom: solid thin black;'>
<xsl:if test='//pt:CurrentAssets[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:CurrentAssets[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
        </tr>
      </xsl:if>
        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
</xsl:if>
      <xsl:if test='//pt:PrepaymentsAccruedIncomeNotExpressedWithinCurrentAssetSub-total[@contextRef=$y2end] or //pt:PrepaymentsAccruedIncomeNotExpressedWithinCurrentAssetSub-total[@contextRef=$y1end]'>
        <tr>
          <td class=';'>
<b>Prepayments and accrued income</b>
          </td>
          <td>

          </td>
          <td class=';'>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:PrepaymentsAccruedIncomeNotExpressedWithinCurrentAssetSub-total[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:PrepaymentsAccruedIncomeNotExpressedWithinCurrentAssetSub-total[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:PrepaymentsAccruedIncomeNotExpressedWithinCurrentAssetSub-total[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td class=';'>
<xsl:if test='//pt:PrepaymentsAccruedIncomeNotExpressedWithinCurrentAssetSub-total[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:PrepaymentsAccruedIncomeNotExpressedWithinCurrentAssetSub-total[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td class=';'>
<xsl:if test='//pt:PrepaymentsAccruedIncomeNotExpressedWithinCurrentAssetSub-total[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:PrepaymentsAccruedIncomeNotExpressedWithinCurrentAssetSub-total[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
        </tr>
      </xsl:if>

        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      <xsl:if test='//pt:CreditorsDueWithinOneYearTotalCurrentLiabilities[@contextRef=$y2end] or //pt:CreditorsDueWithinOneYearTotalCurrentLiabilities[@contextRef=$y1end]'>
        <tr>
          <td class=';'>
<b>Creditors: amounts falling due within one year</b>
          </td>
          <td>

          </td>
          <td class=';'>
<xsl:choose><xsl:when test="($fn_creditors_lt_one_year=1) and ($fn_total_creditors_note_display=0)"><a href="#fn_creditors_lt_one_year" class="footnoteLink"><xsl:value-of select="$fnn_creditors_lt_one_year"/></a></xsl:when><xsl:when test="($fn_creditors_lt_one_year=0) and ($fn_total_creditors_note_display=1)"><a href="#fn_total_creditors_note_display" class="footnoteLink"><xsl:value-of select="$fnn_total_creditors_note_display"/></a></xsl:when><xsl:when test="($fn_creditors_lt_one_year=1) and ($fn_total_creditors_note_display=1)"><a href="#fn_creditors_lt_one_year" class="footnoteLink"><xsl:value-of select="$fnn_creditors_lt_one_year"/></a>, <a href="#fn_total_creditors_note_display" class="footnoteLink"><xsl:value-of select="$fnn_total_creditors_note_display"/></a></xsl:when></xsl:choose><xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:CreditorsDueWithinOneYearTotalCurrentLiabilities[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:CreditorsDueWithinOneYearTotalCurrentLiabilities[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='extra_fn' select="($fn_creditors_lt_one_year=1) and ($fn_total_creditors_note_display=0)" /><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:CreditorsDueWithinOneYearTotalCurrentLiabilities[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td class=';'>
<xsl:if test='//pt:CreditorsDueWithinOneYearTotalCurrentLiabilities[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:CreditorsDueWithinOneYearTotalCurrentLiabilities[@contextRef=$y2end]' /><xsl:with-param name='flipSign'>(</xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td class=';'>
<xsl:if test='//pt:CreditorsDueWithinOneYearTotalCurrentLiabilities[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:CreditorsDueWithinOneYearTotalCurrentLiabilities[@contextRef=$y1end]' /><xsl:with-param name='flipSign'>(</xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
        </tr>
      </xsl:if>

        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      <xsl:if test='//pt:NetCurrentAssetsLiabilities[@contextRef=$y2end] or //pt:NetCurrentAssetsLiabilities[@contextRef=$y1end]'>
        <tr>
          <td>
<span style="font-weight: bold">Net current assets (liabilities)</span>
          </td>
          <td>

          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:NetCurrentAssetsLiabilities[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:NetCurrentAssetsLiabilities[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:NetCurrentAssetsLiabilities[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>

          </td>
          <td>
<xsl:if test='//pt:NetCurrentAssetsLiabilities[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:NetCurrentAssetsLiabilities[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>
<xsl:if test='//pt:NetCurrentAssetsLiabilities[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:NetCurrentAssetsLiabilities[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
        </tr>
      </xsl:if>

        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      <xsl:if test='//pt:TotalAssetsLessCurrentLiabilities[@contextRef=$y2end] or //pt:TotalAssetsLessCurrentLiabilities[@contextRef=$y1end]'>
        <tr>
          <td>
<span style="font-weight: bold">Total assets less current liabilities</span>
          </td>
          <td>

          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TotalAssetsLessCurrentLiabilities[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TotalAssetsLessCurrentLiabilities[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TotalAssetsLessCurrentLiabilities[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>

          </td>
          <td style=' border-top: medium solid black'>
<xsl:if test='//pt:TotalAssetsLessCurrentLiabilities[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TotalAssetsLessCurrentLiabilities[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td style=' border-top: medium solid black'>
<xsl:if test='//pt:TotalAssetsLessCurrentLiabilities[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TotalAssetsLessCurrentLiabilities[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
        </tr>
      </xsl:if>

        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      <xsl:if test='//pt:CreditorsDueAfterOneYearTotalNoncurrentLiabilities[@contextRef=$y2end] or //pt:CreditorsDueAfterOneYearTotalNoncurrentLiabilities[@contextRef=$y1end]'>
        <tr>
          <td>
<span style="font-weight: bold">Creditors: amounts falling due after more than one year</span>
          </td>
          <td>

          </td>
          <td>
<xsl:choose><xsl:when test="($fn_creditors_gt_one_year=1) and ($fn_total_creditors_note_display=0)"><a href="#fn_creditors_gt_one_year" class="footnoteLink"><xsl:value-of select="$fnn_creditors_gt_one_year"/></a></xsl:when><xsl:when test="($fn_creditors_gt_one_year=0) and ($fn_total_creditors_note_display=1)"><a href="#fn_total_creditors_note_display" class="footnoteLink"><xsl:value-of select="$fnn_total_creditors_note_display"/></a></xsl:when><xsl:when test="($fn_creditors_gt_one_year=1) and ($fn_total_creditors_note_display=1)"><a href="#fn_creditors_gt_one_year" class="footnoteLink"><xsl:value-of select="$fnn_creditors_gt_one_year"/></a>, <a href="#fn_total_creditors_note_display" class="footnoteLink"><xsl:value-of select="$fnn_total_creditors_note_display"/></a></xsl:when></xsl:choose><xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:CreditorsDueAfterOneYearTotalNoncurrentLiabilities[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:CreditorsDueAfterOneYearTotalNoncurrentLiabilities[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='extra_fn' select="($fn_creditors_gt_one_year=1) and ($fn_total_creditors_note_display=0)" /><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:CreditorsDueAfterOneYearTotalNoncurrentLiabilities[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>

          </td>
          <td>
<xsl:if test='//pt:CreditorsDueAfterOneYearTotalNoncurrentLiabilities[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:CreditorsDueAfterOneYearTotalNoncurrentLiabilities[@contextRef=$y2end]' /><xsl:with-param name='flipSign'>(</xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>
<xsl:if test='//pt:CreditorsDueAfterOneYearTotalNoncurrentLiabilities[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:CreditorsDueAfterOneYearTotalNoncurrentLiabilities[@contextRef=$y1end]' /><xsl:with-param name='flipSign'>(</xsl:with-param></xsl:call-template>
          </td>
        </tr>
      </xsl:if>


        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
<xsl:choose>
<xsl:when test="translate(//gc:BalanceSheetDate,'-','') &gt; 20051230">
      <xsl:if test='//pt:ProvisionsForLiabilitiesCharges[@contextRef=$y2end] or //pt:ProvisionsForLiabilitiesCharges[@contextRef=$y1end]'>
        <tr>
          <td>
<span style="font-weight: bold">Provisions for liabilities</span>
          </td>
          <td>

          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ProvisionsForLiabilitiesCharges[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ProvisionsForLiabilitiesCharges[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ProvisionsForLiabilitiesCharges[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>

          </td>
          <td>
<xsl:if test='//pt:ProvisionsForLiabilitiesCharges[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:ProvisionsForLiabilitiesCharges[@contextRef=$y2end]' /><xsl:with-param name='flipSign'>(</xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>
<xsl:if test='//pt:ProvisionsForLiabilitiesCharges[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:ProvisionsForLiabilitiesCharges[@contextRef=$y1end]' /><xsl:with-param name='flipSign'>(</xsl:with-param></xsl:call-template>
          </td>
        </tr>
      </xsl:if>
</xsl:when>
<xsl:otherwise>
      <xsl:if test='//pt:ProvisionsForLiabilitiesCharges[@contextRef=$y2end] or //pt:ProvisionsForLiabilitiesCharges[@contextRef=$y1end]'>
        <tr>
          <td>
<span style="font-weight: bold">Provisions for liabilities and charges</span>
          </td>
          <td>

          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ProvisionsForLiabilitiesCharges[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ProvisionsForLiabilitiesCharges[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ProvisionsForLiabilitiesCharges[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>

          </td>
          <td>
<xsl:if test='//pt:ProvisionsForLiabilitiesCharges[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:ProvisionsForLiabilitiesCharges[@contextRef=$y2end]' /><xsl:with-param name='flipSign'>(</xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>
<xsl:if test='//pt:ProvisionsForLiabilitiesCharges[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:ProvisionsForLiabilitiesCharges[@contextRef=$y1end]' /><xsl:with-param name='flipSign'>(</xsl:with-param></xsl:call-template>
          </td>
        </tr>
      </xsl:if>
</xsl:otherwise>
</xsl:choose>

        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      <xsl:if test='//pt:AccrualsDeferredIncome[@contextRef=$y2end] or //pt:AccrualsDeferredIncome[@contextRef=$y1end]'>
        <tr>
          <td>
<span style="font-weight: bold">Accruals and deferred income</span>
          </td>
          <td>

          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:AccrualsDeferredIncome[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:AccrualsDeferredIncome[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:AccrualsDeferredIncome[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>

          </td>
          <td>
<xsl:if test='//pt:AccrualsDeferredIncome[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:AccrualsDeferredIncome[@contextRef=$y2end]' /><xsl:with-param name='flipSign'>(</xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>
<xsl:if test='//pt:AccrualsDeferredIncome[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:AccrualsDeferredIncome[@contextRef=$y1end]' /><xsl:with-param name='flipSign'>(</xsl:with-param></xsl:call-template>
          </td>
        </tr>
      </xsl:if>

        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      <xsl:if test='//pt:NetAssetsLiabilitiesIncludingPensionAssetLiability[@contextRef=$y2end] or //pt:NetAssetsLiabilitiesIncludingPensionAssetLiability[@contextRef=$y1end]'>
        <tr>
          <td>
<span style="font-weight: bold">Total net assets (liabilities)</span>
          </td>
          <td>

          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:NetAssetsLiabilitiesIncludingPensionAssetLiability[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:NetAssetsLiabilitiesIncludingPensionAssetLiability[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:NetAssetsLiabilitiesIncludingPensionAssetLiability[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>

          </td>
          <td style=' border-top: medium solid black;border-bottom: medium solid black'>
<xsl:if test='//pt:NetAssetsLiabilitiesIncludingPensionAssetLiability[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:NetAssetsLiabilitiesIncludingPensionAssetLiability[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td style=' border-top: medium solid black;border-bottom: medium solid black'>
<xsl:if test='//pt:NetAssetsLiabilitiesIncludingPensionAssetLiability[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:NetAssetsLiabilitiesIncludingPensionAssetLiability[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
        </tr>
      </xsl:if>

        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>

<!--
    Capital and reserves


-->

        <tr>
          <td style=' font-weight: bold'>
Capital and reserves
          </td>
          <td>

          </td>
          <td style=' font-weight: bold'>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>

<!--
    Called up share capital
-->

      <xsl:if test='//pt:CalledUpShareCapital[@contextRef=$y2end] or //pt:CalledUpShareCapital[@contextRef=$y1end]'>
        <tr>
          <td>
Called up share capital
          </td>
          <td>

          </td>
          <td>
<xsl:if test="$fn_capital=1"><a href="#fn_capital" class="footnoteLink"><xsl:value-of select="$fnn_capital"/></a></xsl:if><xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:CalledUpShareCapital[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:CalledUpShareCapital[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='extra_fn' select="$fn_capital=1" /><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:CalledUpShareCapital[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>

          </td>
          <td>
<xsl:if test='//pt:CalledUpShareCapital[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:CalledUpShareCapital[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>
<xsl:if test='//pt:CalledUpShareCapital[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:CalledUpShareCapital[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
        </tr>
      </xsl:if>

<!--
    Share premium account
-->

      <xsl:if test='//pt:SharePremiumAccount[@contextRef=$y2end] or //pt:SharePremiumAccount[@contextRef=$y1end]'>
        <tr>
          <td>
Share premium account
          </td>
          <td>

          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:SharePremiumAccount[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:SharePremiumAccount[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:SharePremiumAccount[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>

          </td>
          <td>
<xsl:if test='//pt:SharePremiumAccount[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:SharePremiumAccount[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>
<xsl:if test='//pt:SharePremiumAccount[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:SharePremiumAccount[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
        </tr>
      </xsl:if>

<!--
    Revaluation reserve
-->

      <xsl:if test='//pt:RevaluationReserve[@contextRef=$y2end] or //pt:RevaluationReserve[@contextRef=$y1end]'>
        <tr>
          <td>
Revaluation reserve
          </td>
          <td>

          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:RevaluationReserve[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:RevaluationReserve[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:RevaluationReserve[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>

          </td>
          <td>
<xsl:if test='//pt:RevaluationReserve[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:RevaluationReserve[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>
<xsl:if test='//pt:RevaluationReserve[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:RevaluationReserve[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
        </tr>
      </xsl:if>

<!--
    Other reserves
-->

      <xsl:if test='//pt:OtherAggregateReserves[@contextRef=$y2end] or //pt:OtherAggregateReserves[@contextRef=$y1end]'>
        <tr>
          <td>
Other reserves
          </td>
          <td>

          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:OtherAggregateReserves[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:OtherAggregateReserves[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:OtherAggregateReserves[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>

          </td>
          <td>
<xsl:if test='//pt:OtherAggregateReserves[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:OtherAggregateReserves[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>
<xsl:if test='//pt:OtherAggregateReserves[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:OtherAggregateReserves[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
        </tr>
      </xsl:if>

<!--
    Profit and loss account
-->

      <xsl:if test='//pt:ProfitLossAccountReserve[@contextRef=$y2end] or //pt:ProfitLossAccountReserve[@contextRef=$y1end and @unitRef=//xbrli:unit[.//xbrli:measure=$currencyUnitY1]/@id]'>
        <tr>
          <td>
Profit and loss account
          </td>
          <td>

          </td>
          <td>
<xsl:if test="$fn_pl=1"><a href="#fn_pl" class="footnoteLink"><xsl:value-of select="$fnn_pl"/></a></xsl:if><xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ProfitLossAccountReserve[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ProfitLossAccountReserve[@contextRef=$y1end and @unitRef=//xbrli:unit[.//xbrli:measure=$currencyUnitY1]/@id]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='extra_fn' select="$fn_pl=1" /><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ProfitLossAccountReserve[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>

          </td>
          <td>
<xsl:if test='//pt:ProfitLossAccountReserve[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:ProfitLossAccountReserve[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>
<xsl:if test='//pt:ProfitLossAccountReserve[@contextRef=$y1end and @unitRef=//xbrli:unit[.//xbrli:measure=$currencyUnitY1]/@id]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:ProfitLossAccountReserve[@contextRef=$y1end and @unitRef=//xbrli:unit[.//xbrli:measure=$currencyUnitY1]/@id]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
        </tr>
      </xsl:if>

        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      <xsl:if test='//pt:ShareholderFunds[@contextRef=$y2end] or //pt:ShareholderFunds[@contextRef=$y1end]'>
        <tr>
          <td>
<span style="font-weight:bold">Shareholders funds</span>
          </td>
          <td>

          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ShareholderFunds[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ShareholderFunds[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ShareholderFunds[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>

          </td>
          <td style='border-top: solid medium black; border-bottom: solid medium black;'>
<xsl:if test='//pt:ShareholderFunds[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:ShareholderFunds[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td style='border-top: solid medium black; border-bottom: solid medium black;'>
<xsl:if test='//pt:ShareholderFunds[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:ShareholderFunds[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
        </tr>
      </xsl:if>

</table>
<table width="100%" style="page-break-inside: avoid">
<tr><td colspan="8" >
<ol type="a">
<xsl:if test="//ae:CompanyEntitledToExemptionUnderSection249A1CompaniesAct1985 = 'true'"><li>For the year ending <xsl:call-template name="formatDate"><xsl:with-param name="date" select="//gc:BalanceSheetDate"/></xsl:call-template> the company was entitled to exemption under section 249A(1) of the Companies Act 1985.</li></xsl:if>
<xsl:if test="//ae:CompanyEntitledToExemptionUnderSection477CompaniesAct2006 = 'true'"><li>For the year ending <xsl:call-template name="formatDate"><xsl:with-param name="date" select="//gc:BalanceSheetDate"/></xsl:call-template> the company was entitled to exemption under section 477 of the Companies Act 2006 relating to small companies.</li></xsl:if>
<xsl:choose>
<xsl:when test="number(translate(//xbrli:context[@id=$y2]/xbrli:period/xbrli:startDate,'-:T', '')) &lt; number('20080406')">
<xsl:if test="//ae:MembersHaveNotRequiredCompanyToObtainAnAudit = 'true'"><li>The members have not required the company to obtain an audit in accordance with section 249B(2) of the Companies Act 1985.</li></xsl:if>
<xsl:if test="//ae:DirectorsAcknowledgeTheirResponsibilitiesUnderCompaniesAct = 'true'"><li>The directors acknowledge their responsibility for:
<ol type="i"><li>ensuring the company keeps  accounting records which comply with Section 221; and</li>
<li>preparing accounts which give a true and fair view of the state of affairs of the company as at the end of the financial year, and of its profit or loss for the financial year, in accordance with the requirements of section 226, and which otherwise comply with the requirements of the Companies Act relating to accounts, so far as is applicable to the company.</li></ol></li></xsl:if>
<xsl:if test="//ae:AccountsAreInAccordanceWithSpecialProvisionsCompaniesActRelatingToSmallCompanies = 'true'"><li>The accounts have been prepared in accordance with the special provisions in Part VII of the Companies Act 1985 relating to small companies.</li></xsl:if>
</xsl:when>
<xsl:otherwise>
<xsl:if test="//ae:MembersHaveNotRequiredCompanyToObtainAnAudit = 'true'"><li>The members have not required the company to obtain an audit in accordance with section 476 of the Companies Act 2006.</li></xsl:if>
<xsl:if test="//ae:DirectorsAcknowledgeTheirResponsibilitiesUnderCompaniesAct = 'true'"><li>The directors acknowledge their responsibilities for complying with the requirements of the Act with respect to accounting records and the preparation of accounts.</li></xsl:if>
<xsl:if test="//ae:AccountsAreInAccordanceWithSpecialProvisionsCompaniesActRelatingToSmallCompanies = 'true'"><li>These accounts have been prepared in accordance with the provisions applicable to companies subject to the small companies regime.</li></xsl:if>
<xsl:if test="//ae:AbridgedAccountsStatement = 'true'"><li>These accounts have been delivered in accordance with the provisions applicable to companies subject to the small companies regime.</li></xsl:if>
</xsl:otherwise>
</xsl:choose>
</ol>
<p>Approved by the board on <xsl:call-template name="formatDate"><xsl:with-param name="date" select="//pt:DateApproval"/></xsl:call-template></p>
<p>And signed on their behalf by:<br/>
<span style="padding: 0 0 0 1em; font-weight: bold"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:NameApprovingDirector' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>, Director</span><br />
<xsl:for-each select="//pt:AdditionalApprovingPerson">
<span style="padding: 0 0 0 1em; font-weight: bold"><xsl:value-of select="./pt:NameAdditionalApprovingPerson"/>, <xsl:value-of select="./pt:PositionAdditionalApprovingPerson"/></span><br />
</xsl:for-each>
</p>

<!-- Following lines commented out as they apply to CAP09 and are not needed until 1 Oct 09. 
TO REINSTATE - just remove comment start and ends down to "page-break-after: always" and remove this and line above - also Directors Report - line 740

<xsl:choose>
    <xsl:when test="//ae:DateAccountsReceived">
         <xsl:choose>
             <xsl:when test="(number(translate(//xbrli:context[@id=$y2]/xbrli:period/xbrli:startDate,'-:T', '')) >= number('20080406')) and         number(translate(//ae:DateAccountsReceived, '-:T', '')) >= number('20091001')">
                 <p style="font-weight: bold">This document was delivered using electronic communications and authenticated in accordance with the registrar's rules relating to electronic form, authentication and manner of delivery under section 1068 of the Companies Act 2006.</p>
             </xsl:when>
             <xsl:otherwise>

-->

                 <p style="font-weight: bold">This document was delivered using electronic communications and authenticated in accordance with section 707B(2) of the Companies Act 1985.</p>
                 
                 <!--
             </xsl:otherwise>
         </xsl:choose>
     </xsl:when>
</xsl:choose>
-->



</td></tr>


</table>
<br style="page-break-after: always" />


<table width="100%" class="rnheader">
<tr><td style="font-weight: bold" align="left"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//gc:EntityCurrentLegalName' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td><td style="font-weight: bold" align="right">Registered Number <xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:CompaniesHouseRegisteredNumber' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td></tr>
</table>
<table border="0" width="100%">
<tr><td colspan="7" align="center"><h2>Notes to the <xsl:if test="not(//ae:DateApprovalDirectorsReport)">Abbreviated </xsl:if>Accounts</h2></td></tr>
<tr><td width="2%"></td><td colspan="7">For the <xsl:value-of select="$periodOrYear"/> ending <xsl:call-template name="formatDate"><xsl:with-param name="date" select="//gc:BalanceSheetDate" /></xsl:call-template></td></tr>
<!-- footnote: Accounting Policies -->
<xsl:if test="$fn_accounting_policies=1">
<tr><td><xsl:value-of select="$fnn_accounting_policies"/></td><td><h3 class="nospace" style="page-break-avoid: begin">Accounting policies</h3></td></tr>
<tr><td></td><td colspan="4">

<xsl:if test="//ae:AccountingPolicySubTitle">
<p style="font-weight: bold"><xsl:value-of select="//ae:AccountingPolicySubTitle"/></p>
</xsl:if>
<xsl:if test="//ae:AccountsPreparedUnderHistoricalCostConventionInAccordanceWithFRSE = 'true'">
<p>The accounts have been prepared under the historical cost convention and in accordance with the Financial Reporting Standards for Small Entities (effective June 2002)</p>
</xsl:if>
<xsl:if test="//ae:AccountsPreparedUnderHistoricalCostConventionInAccordanceWithFRSSE05 = 'true'">
<p>The accounts have been prepared under the historical cost convention and in accordance with the Financial Reporting Standards for Small Entities (effective January 2005)</p>
</xsl:if>
<xsl:if test="//ae:FRSSEVersionDate">
<p>The accounts have been prepared under the historical cost convention and in accordance with the Financial Reporting Standards for Small Entities (effective <xsl:value-of select="//ae:FRSSEVersionDate"/>)</p>
</xsl:if>
<xsl:if test="//ae:CompanyNon-trading = 'true'">
<p>The company did not trade during the year ended <xsl:call-template name="formatDate"><xsl:with-param name="date" select="//gc:BalanceSheetDate" /></xsl:call-template>.</p>
</xsl:if>

<xsl:for-each select="//ae:AccountingPolicy">
  <p>
  <xsl:if test=".//ae:TitleAccountingPolicy">
    <b><xsl:value-of select=".//ae:TitleAccountingPolicy" /></b><br/>
  </xsl:if>
  <xsl:value-of select=".//ae:ContentAccountingPolicy" /></p>
</xsl:for-each>

<xsl:if test="//ae:DepreciationRate">
  <p><b>Depreciation</b></p>
  <p>Depreciation has been provided at the following rates in order to write off the assets over their estimated useful lives.</p>
  <table>
  <xsl:for-each select="//ae:DepreciationRate">
    <tr>
      <td><xsl:value-of select=".//ae:CategoryItem" /></td>
      <xsl:if test=".//ae:RateDepreciation">
      <td style="padding-left: 40px"><xsl:value-of select="format-number(.//ae:RateDepreciation, '##.######%')" /></td>
      </xsl:if>
      <td><xsl:value-of select=".//ae:TypeDepreciation" /></td>
    </tr>
  </xsl:for-each>
  </table><span style="page-break-avoid: end"/>
</xsl:if>
</td></tr>
</xsl:if>
<!-- ~footnote: Accounting Policies -->

<!-- footnote: Exchange Rates -->
<xsl:if test="$fn_exchange_rates=1">
<tr><td><a name="fn_exchange_rates"/><xsl:value-of select="$fnn_exchange_rates"/></td><td><h3 class="nospace" style="page-break-avoid: begin">Exchange rate</h3></td></tr>
<tr><td></td><td><p>
  <xsl:for-each select="//ae:ForeignExchangeRate">
    <xsl:value-of select="."/><br/>
  </xsl:for-each>
  </p><span style="page-break-avoid: end"/>
</td></tr>
</xsl:if>
<!-- ~footnote: Exchange Rates -->

<!-- footnote: Operating Profit -->

<xsl:if test="$fn_operating_profit=1">
<tr><td><a name="fn_operating_profit"/><xsl:value-of select="$fnn_operating_profit"/></td><td><h3 class="nospace" style="page-break-avoid: begin">Operating profit</h3></td></tr> 
        <tr>
          <td>

          </td>
          <td>

          </td>
          <td style='font-weight:bold; text-align: right'>

          </td>
          <td style='font-weight:bold; text-align: right'>
<xsl:value-of select="$namey2" />
          </td>
          <td style='font-weight:bold; text-align: right'>
<xsl:value-of select="$namey1" />
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
<tr style="font-weight: bold">
  <td></td>
  <td>This is stated after charging</td>
  <td></td>
  <td class="rightalign"><xsl:value-of select="$currencyY2"/></td>
  <td class="rightalign"><xsl:value-of select="$currencyY1"/></td>
</tr>

<!--
    Depreciation of owned fixed assets
-->

      <xsl:if test='//pt:DepreciationAmountsWrittenOffFixedAssets[@contextRef=$y2] or //pt:DepreciationAmountsWrittenOffFixedAssets[@contextRef=$y1]'>
        <tr>
          <td>

          </td>
          <td>
Depreciation of owned fixed assets
          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:DepreciationAmountsWrittenOffFixedAssets[@contextRef=$y2]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:DepreciationAmountsWrittenOffFixedAssets[@contextRef=$y1]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:DepreciationAmountsWrittenOffFixedAssets[@contextRef=$y2]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//pt:DepreciationAmountsWrittenOffFixedAssets[@contextRef=$y2]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:DepreciationAmountsWrittenOffFixedAssets[@contextRef=$y2]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>
<xsl:if test='//pt:DepreciationAmountsWrittenOffFixedAssets[@contextRef=$y1]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:DepreciationAmountsWrittenOffFixedAssets[@contextRef=$y1]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      </xsl:if>

<!--
    Operating lease costs
-->

      <xsl:if test='//pt:OperatingLeaseExpenditure[@contextRef=$y2] or //pt:OperatingLeaseExpenditure[@contextRef=$y1]'>
        <tr>
          <td>

          </td>
          <td>
Operating lease costs
          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:OperatingLeaseExpenditure[@contextRef=$y2]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:OperatingLeaseExpenditure[@contextRef=$y1]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:OperatingLeaseExpenditure[@contextRef=$y2]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//pt:OperatingLeaseExpenditure[@contextRef=$y2]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:OperatingLeaseExpenditure[@contextRef=$y2]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>
<xsl:if test='//pt:OperatingLeaseExpenditure[@contextRef=$y1]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:OperatingLeaseExpenditure[@contextRef=$y1]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      </xsl:if>

<!--
    Auditors remuneration
-->

      <xsl:if test='//pt:TotalFeesToAuditors[@contextRef=$y2] or //pt:TotalFeesToAuditors[@contextRef=$y1]'>
        <tr>
          <td>

          </td>
          <td>
Auditors remuneration
          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TotalFeesToAuditors[@contextRef=$y2]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TotalFeesToAuditors[@contextRef=$y1]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TotalFeesToAuditors[@contextRef=$y2]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//pt:TotalFeesToAuditors[@contextRef=$y2]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TotalFeesToAuditors[@contextRef=$y2]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>
<xsl:if test='//pt:TotalFeesToAuditors[@contextRef=$y1]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TotalFeesToAuditors[@contextRef=$y1]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      </xsl:if>
      <xsl:if test='//ae:ChargesBeforeOperatingProfit[@contextRef=$y2] or //ae:ChargesBeforeOperatingProfit[@contextRef=$y1]'>
        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //ae:ChargesBeforeOperatingProfit[@contextRef=$y2]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //ae:ChargesBeforeOperatingProfit[@contextRef=$y1]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //ae:ChargesBeforeOperatingProfit[@contextRef=$y2]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td style='border-top: solid thin black; border-bottom: solid medium black'>
<xsl:if test='//ae:ChargesBeforeOperatingProfit[@contextRef=$y2]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:ChargesBeforeOperatingProfit[@contextRef=$y2]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td style='border-top: solid thin black; border-bottom: solid medium black'>
<xsl:if test='//ae:ChargesBeforeOperatingProfit[@contextRef=$y1]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:ChargesBeforeOperatingProfit[@contextRef=$y1]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      </xsl:if>
<tr><td colspan="7"><span style="page-break-avoid: end" /></td></tr>
</xsl:if>
<!-- ~footnote: Operating Profit -->

<!-- footnote: Taxation -->
<xsl:if test="$fn_taxation=1">
<tr><td><a name="fn_taxation"/><xsl:value-of select="$fnn_taxation"/></td><td colspan="7"><h3 class="nospace" style="page-break-avoid: begin">Taxation</h3></td></tr>
        <tr>
          <td>

          </td>
          <td>

          </td>
          <td style='font-weight:bold; text-align: right'>

          </td>
          <td style='font-weight:bold; text-align: right'>
<xsl:value-of select="$namey2" />
          </td>
          <td style='font-weight:bold; text-align: right'>
<xsl:value-of select="$namey1" />
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
<tr style="font-weight: bold">
  <td></td>
  <td></td>
  <td></td>
  <td class="rightalign"><xsl:value-of select="$currencyY2"/></td>
  <td class="rightalign"><xsl:value-of select="$currencyY1"/></td>
</tr>

<!--
    UK Corporation tax
-->

      <xsl:if test='//pt:TotalUKCurrentCorporationTax[@contextRef=$y2] or //pt:TotalUKCurrentCorporationTax[@contextRef=$y1]'>
        <tr>
          <td>

          </td>
          <td>
UK Corporation tax
          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TotalUKCurrentCorporationTax[@contextRef=$y2]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TotalUKCurrentCorporationTax[@contextRef=$y1]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TotalUKCurrentCorporationTax[@contextRef=$y2]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//pt:TotalUKCurrentCorporationTax[@contextRef=$y2]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TotalUKCurrentCorporationTax[@contextRef=$y2]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>
<xsl:if test='//pt:TotalUKCurrentCorporationTax[@contextRef=$y1]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TotalUKCurrentCorporationTax[@contextRef=$y1]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      </xsl:if>

<!--
    Deferred tax
-->

      <xsl:if test='//pt:TotalDeferredTax[@contextRef=$y2] or //pt:TotalDeferredTax[@contextRef=$y1]'>
        <tr>
          <td>

          </td>
          <td>
Deferred tax
          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TotalDeferredTax[@contextRef=$y2]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TotalDeferredTax[@contextRef=$y1]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TotalDeferredTax[@contextRef=$y2]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//pt:TotalDeferredTax[@contextRef=$y2]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TotalDeferredTax[@contextRef=$y2]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>
<xsl:if test='//pt:TotalDeferredTax[@contextRef=$y1]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TotalDeferredTax[@contextRef=$y1]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      </xsl:if>
<xsl:for-each select="//ae:OtherTaxationDetails">
  <tr>
    <td></td>
    <td><xsl:value-of select=".//ae:OtherTaxationCategory " /></td>
    <td></td>
    <td class="rightalign"><xsl:value-of select=".//ae:OtherTaxationValue[@contextRef=$y2]" /></td>
    <td class="rightalign"><xsl:value-of select=".//ae:OtherTaxationValue[@contextRef=$y1]" /></td>
  </tr>
</xsl:for-each>
      <xsl:if test='//pt:TaxRelatedToProfitOrLossOnOrdinaryActivities[@contextRef=$y2] or //pt:TaxRelatedToProfitOrLossOnOrdinaryActivities[@contextRef=$y1]'>
        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TaxRelatedToProfitOrLossOnOrdinaryActivities[@contextRef=$y2]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TaxRelatedToProfitOrLossOnOrdinaryActivities[@contextRef=$y1]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TaxRelatedToProfitOrLossOnOrdinaryActivities[@contextRef=$y2]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td style='border-top: solid thin black; border-bottom: solid medium black'>
<xsl:if test='//pt:TaxRelatedToProfitOrLossOnOrdinaryActivities[@contextRef=$y2]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TaxRelatedToProfitOrLossOnOrdinaryActivities[@contextRef=$y2]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td style='border-top: solid thin black; border-bottom: solid medium black'>
<xsl:if test='//pt:TaxRelatedToProfitOrLossOnOrdinaryActivities[@contextRef=$y1]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TaxRelatedToProfitOrLossOnOrdinaryActivities[@contextRef=$y1]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      </xsl:if>
<xsl:if test="//ae:TaxationFreeTextNote"><tr><td></td><td colspan="4"><xsl:value-of select="//ae:TaxationFreeTextNote"/></td></tr></xsl:if>
<tr><td colspan="7"><span style="page-break-avoid: end" /></td></tr>
</xsl:if>
<!-- ~footnote: Taxation -->

<!-- footnote: Fixed Assets Summary -->
<xsl:if test="$fn_fas=1">
	<xsl:variable name="needCol_Intangible">0<xsl:if test="//pt:IntangibleFixedAssetsCostOrValuation[@contextRef=$y1end] or //pt:IntangibleFixedAssetsAdditions or //pt:IntangibleFixedAssetsDisposals or //pt:IntangibleFixedAssetsRevaluation or //pt:IntangibleFixedAssetsIncreaseDecreaseFromTransfersBetweenItems or //pt:IntangibleFixedAssetsCostOrValuation[@contextRef=$y2end] or //pt:IntangibleFixedAssetsAggregateAmortisationImpairment[@contextRef=$y1end] or //pt:IntangibleFixedAssetsAmortisationChargedInPeriod or //pt:IntangibleFixedAssetsAmortisationOnDisposals or //pt:IntangibleFixedAssetsAmortisationIncreaseDecreaseFromTransfersBetweenItems or //pt:IntangibleFixedAssetsAmortisationOtherAdjustments or //pt:IntangibleFixedAssetsAggregateAmortisationImpairment[@contextRef=$y2end]">1</xsl:if></xsl:variable>
	<xsl:variable name="needCol_Tangible">0<xsl:if test="//pt:TangibleFixedAssetsCostOrValuation[@contextRef=$y1end] or //pt:TangibleFixedAssetsAdditions or //pt:TangibleFixedAssetsDisposals or //pt:TangibleFixedAssetsIncreaseDecreaseFromRevaluations or //pt:TangibleFixedAssetsIncreaseDecreaseFromTransfersBetweenItems or //pt:TangibleFixedAssetsCostOrValuation[@contextRef=$y2end] or //pt:TangibleFixedAssetsDepreciation[@contextRef=$y1end] or //pt:TangibleFixedAssetsDepreciationChargeForPeriod or //pt:TangibleFixedAssetsDepreciationDisposals or //pt:TangibleFixedAssetsDepreciationIncreaseDecreaseFromTransfersBetweenItems or //ae:TangibleFixedAssetsDepreciationOtherAdjustments or //pt:TangibleFixedAssetsDepreciation[@contextRef=$y2end]">1</xsl:if></xsl:variable>
	<xsl:variable name="needCol_Investments">0<xsl:if test="//pt:TotalFixedAssetInvestmentsCostOrValuation[@contextRef=$y1end] or //pt:TotalFixedAssetInvestmentsAdditions or //pt:TotalFixedAssetInvestmentsDisposals or //ae:InvestmentsFixedAssetsRevaluations or //ae:InvestmentsFixedAssetsIncreaseDecreaseFromTransfersBetweenItems or //pt:TotalFixedAssetInvestmentsCostOrValuation[@contextRef=$y2end] or //ae:InvestmentsFixedAssetsDepreciation[@contextRef=$y1end] or //ae:InvestmentsFixedAssetsDepreciationChargedInPeriod or //ae:InvestmentsFixedAssetsDepreciationOnDisposals or //ae:InvestmentsFixedAssetsDepreciationOnTransfers or //ae:InvestmentsFixedAssetsDepreciationOtherAdjustments or //ae:InvestmentsFixedAssetsDepreciation[@contextRef=$y2end]">1</xsl:if></xsl:variable>
	<xsl:variable name="needCol_InvestmentProperty">0<xsl:if test="//pt:InvestmentPropertiesCostOrValuation[@contextRef=$y1end] or //pt:InvestmentPropertiesAdditions or //pt:InvestmentPropertiesDisposals or //pt:InvestmentPropertiesIncreaseDecreaseFromRevaluations or //pt:InvestmentPropertiesIncreaseDecreaseFromTransfersBetweenItems or //pt:InvestmentPropertiesCostOrValuation[@contextRef=$y2end] or //pt:InvestmentPropertiesDepreciation[@contextRef=$y1end] or //pt:InvestmentPropertiesDepreciationChargeForPeriod or //pt:InvestmentPropertiesDepreciationDisposals or //pt:InvestmentPropertiesDepreciationIncreaseDecreaseFromTransfersBetweenItems or //ae:InvestmentPropertyDepreciationOtherAdjustments or //pt:InvestmentPropertiesDepreciation[@contextRef=$y2end]">1</xsl:if></xsl:variable>
	<xsl:variable name="needCol_TotalFA">0<xsl:if test="//ae:TotalFixedAssetsCostOrValuation[@contextRef=$y1end] or //ae:TotalFixedAssetsAdditions or //ae:TotalFixedAssetsDisposals or //ae:TotalFixedAssetsRevaluation or //ae:TotalFixedAssetsIncreaseDecreaseFromTransfersBetweenItems or //ae:TotalFixedAssetsCostOrValuation[@contextRef=$y2end] or //ae:TotalFixedAssetsDepreciation[@contextRef=$y1end] or //ae:TotalFixedAssetsDepreciationChargeInPeriod or //ae:TotalFixedAssetsDepreciationDisposals or //ae:TotalFixedAssetsDepreciationOnTransfers or //ae:TotalFixedAssetsDepreciationOtherAdjustments or //ae:TotalFixedAssetsDepreciation[@contextRef=$y2end]">1</xsl:if></xsl:variable>
	<tr><td><a name="fn_fas"/><xsl:value-of select="$fnn_fas"/></td><td><h3 class="nospace" style="page-break-avoid: begin">Fixed Assets</h3></td></tr>
	<tr><td></td><td colspan="7">
		<table width="100%" style="text-align: right">
			<tr>
        <!-- 
          Columns in this table are a fixed width of 14%.  Adjust the size of
          the first column to match. 
        -->
        <xsl:variable name="tfa_number_columns" select="1 + $needCol_Intangible + $needCol_Tangible + $needCol_Investments + $needCol_InvestmentProperty" />
				<td width="{100 - $tfa_number_columns * 14}%"></td>
        <xsl:choose>
            <xsl:when test="$needCol_Intangible = 1">
              <th align="right" width="14%">Intangible Assets</th>
            </xsl:when>
            <xsl:otherwise>
              <th width="0"></th>
            </xsl:otherwise>
        </xsl:choose>
        <xsl:choose>
            <xsl:when test="$needCol_Tangible = 1">
              <th align="right" width="14%">Tangible Assets</th>
            </xsl:when>
            <xsl:otherwise>
              <th width="0"></th>
            </xsl:otherwise>
        </xsl:choose>
        <xsl:choose>
            <xsl:when test="$needCol_Investments = 1">
              <th align="right" width="14%">Investments</th>
            </xsl:when>
            <xsl:otherwise>
              <th width="0"></th>
            </xsl:otherwise>
        </xsl:choose>
        <xsl:choose>
            <xsl:when test="$needCol_InvestmentProperty = 1">
              <th align="right" width="14%">Investment Property</th>
            </xsl:when>
            <xsl:otherwise>
              <th width="0"></th>
            </xsl:otherwise>
        </xsl:choose>
				<th align="right" width="14%">Total</th>
			</tr>

			<xsl:variable name="tfa_s1" select="//pt:IntangibleFixedAssetsCostOrValuation[@contextRef=$y1end] or //pt:TangibleFixedAssetsCostOrValuation[@contextRef=$y1end] or //pt:TotalFixedAssetInvestmentsCostOrValuation[@contextRef=$y1end] or //pt:InvestmentPropertiesCostOrValuation[@contextRef=$y1end] or //pt:TangibleFixedAssetsCostOrValuation[@contextRef=$y1end]" />
			<xsl:variable name="tfa_s2" select="//pt:IntangibleFixedAssetsAdditions or //pt:TangibleFixedAssetsAdditions or //pt:TotalFixedAssetInvestmentsAdditions or //pt:InvestmentPropertiesAdditions or //pt:TangibleFixedAssetsAdditions" />
			<xsl:variable name="tfa_s3" select="//pt:IntangibleFixedAssetsDisposals or //pt:TangibleFixedAssetsDisposals or //pt:TotalFixedAssetInvestmentsDisposals or //pt:InvestmentPropertiesDisposals or //pt:TangibleFixedAssetsDisposals" />
			<xsl:variable name="tfa_s4" select="//pt:IntangibleFixedAssetsCostOrValuation[@contextRef=$y2end] or //pt:TangibleFixedAssetsCostOrValuation[@contextRef=$y2end] or //pt:TotalFixedAssetInvestmentsCostOrValuation[@contextRef=$y2end] or //pt:InvestmentPropertiesCostOrValuation[@contextRef=$y2end] or //pt:TangibleFixedAssetsCostOrValuation[@contextRef=$y2end]" />
			<xsl:variable name="tfa_s11" select="//pt:IntangibleFixedAssetsRevaluations or //pt:TangibleFixedAssetsIncreaseDecreaseFromRevaluations or //ae:InvestmentsFixedAssetsRevaluations or //pt:InvestmentPropertiesIncreaseDecreaseFromRevaluations or //pt:TangibleFixedAssetsIncreaseDecreaseFromRevaluations" />
			<xsl:variable name="tfa_s12" select="//pt:IntangibleFixedAssetsIncreaseDecreaseFromTransfersBetweenItems or //pt:TangibleFixedAssetsIncreaseDecreaseFromTransfersBetweenItems or //ae:InvestmentsFixedAssetsIncreaseDecreaseFromTransfersBetweenItems or //pt:InvestmentPropertiesIncreaseDecreaseFromTransfersBetweenItems or //pt:TangibleFixedAssetsIncreaseDecreaseFromTransfersBetweenItems" />

			<tr>
				<td align="left" style="font-weight:bold"><xsl:if test="$tfa_s1 or $tfa_s2 or $tfa_s3 or $tfa_s4">Cost or valuation</xsl:if></td>
				<th align="right">
					<xsl:if test="$needCol_Intangible = 1"><xsl:value-of select="$currencyY2"/></xsl:if>
				</th>
				<th align="right">
					<xsl:if test="$needCol_Tangible = 1"><xsl:value-of select="$currencyY2"/></xsl:if>
				</th>
				<th align="right">
					<xsl:if test="$needCol_Investments = 1"><xsl:value-of select="$currencyY2"/></xsl:if>
				</th>
				<th align="right">
					<xsl:if test="$needCol_InvestmentProperty = 1"><xsl:value-of select="$currencyY2"/></xsl:if>
				</th>
				<th align="right"><xsl:value-of select="$currencyY2"/></th>
			</tr>

			<xsl:if test="$tfa_s1">
			<tr>
				<td align="left">At <xsl:call-template name="formatDate"><xsl:with-param name="date"><xsl:call-template name="date:add"><xsl:with-param name="date-time" select="//xbrli:context[@id=$y1end]//xbrli:instant"/><xsl:with-param name="duration" select="'P1D'"/></xsl:call-template></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:IntangibleFixedAssetsCostOrValuation[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TangibleFixedAssetsCostOrValuation[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TotalFixedAssetInvestmentsCostOrValuation[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:InvestmentPropertiesCostOrValuation[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:TotalFixedAssetsCostOrValuation[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="$tfa_s2">
			<tr>
				<td align="left">Additions</td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:IntangibleFixedAssetsAdditions' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TangibleFixedAssetsAdditions' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TotalFixedAssetInvestmentsAdditions' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:InvestmentPropertiesAdditions' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:TotalFixedAssetsAdditions' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="$tfa_s3">
			<tr>
				<td align="left">Disposals</td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:IntangibleFixedAssetsDisposals' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TangibleFixedAssetsDisposals' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TotalFixedAssetInvestmentsDisposals' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:InvestmentPropertiesDisposals' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:TotalFixedAssetsDisposals' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

      <xsl:if test="$tfa_s11">
      <tr>
				<td align="left">Revaluations</td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:IntangibleFixedAssetsRevaluation' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TangibleFixedAssetsIncreaseDecreaseFromRevaluations' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:InvestmentsFixedAssetsRevaluations' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:InvestmentPropertiesIncreaseDecreaseFromRevaluations' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:TotalFixedAssetsRevaluation' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
      </tr>
      </xsl:if>

      <xsl:if test="$tfa_s12">
      <tr>
				<td align="left">Transfers</td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:IntangibleFixedAssetsIncreaseDecreaseFromTransfersBetweenItems' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TangibleFixedAssetsIncreaseDecreaseFromTransfersBetweenItems' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:InvestmentsFixedAssetsIncreaseDecreaseFromTransfersBetweenItems' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:InvestmentPropertiesIncreaseDecreaseFromTransfersBetweenItems' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:TotalFixedAssetsIncreaseDecreaseFromTransfersBetweenItems' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
      </tr>
      </xsl:if>


			<xsl:if test="$tfa_s4">
			<tr>
				<td align="left">At <xsl:call-template name="formatDate"><xsl:with-param name="date" select="//xbrli:context[@id=$y2end]//xbrli:instant" /></xsl:call-template></td>
				<td style="border-top: solid thin black; border-bottom: solid medium black"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:IntangibleFixedAssetsCostOrValuation[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td style="border-top: solid thin black; border-bottom: solid medium black"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TangibleFixedAssetsCostOrValuation[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td style="border-top: solid thin black; border-bottom: solid medium black"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TotalFixedAssetInvestmentsCostOrValuation[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td style="border-top: solid thin black; border-bottom: solid medium black"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:InvestmentPropertiesCostOrValuation[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
        <td style="border-top: solid thin black; border-bottom: solid medium black; padding-left: 5px"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:TotalFixedAssetsCostOrValuation[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:variable name="tfa_s5" select="//pt:IntangibleFixedAssetsAggregateAmortisationImpairment[@contextRef=$y1end] or //pt:TangibleFixedAssetsDepreciation[@contextRef=$y1end] or //ae:InvestmentsFixedAssetsDepreciation[@contextRef=$y1end] or //pt:InvestmentPropertiesDepreciation[@contextRef=$y1end] or //pt:TangibleFixedAssetsDepreciation[@contextRef=$y1end]"/>
			<xsl:variable name="tfa_s6" select="//pt:IntangibleFixedAssetsAmortisationChargedinPeriod or //pt:TangibleFixedAssetsDepreciationChargeForPeriod or //ae:InvestmentsFixedAssetsDepreciationChargedInPeriod or //pt:InvestmentPropertiesDepreciationChargeForPeriod or //pt:TangibleFixedAssetsDepreciationChargeForPeriod"/>
			<xsl:variable name="tfa_s7" select="//pt:IntangibleFixedAssetsAmortisationOnDisposals or //pt:TangibleFixedAssetsDepreciationDisposals or //ae:InvestmentsFixedAssetsDepreciationOnDisposals or //pt:InvestmentPropertiesDepreciationDisposals or //pt:TangibleFixedAssetsDepreciationDisposals"/>
			<xsl:variable name="tfa_s8" select="//pt:IntangibleFixedAssetsAggregateAmortisationImpairment[@contextRef=$y2end] or //pt:TangibleFixedAssetsDepreciation[@contextRef=$y2end] or //pt:InvestmentPropertiesDepreciation[@contextRef=$y2end] or //ae:OtherTangibleFixedAssetDepreciation[@contextRef=$y2end] or //pt:TangibleFixedAssetsDepreciation[@contextRef=$y2end]"/>
			<xsl:variable name="tfa_s13" select="//pt:IntangibleFixedAssetsAmortisationIncreaseDecreaseFromTransfersBetweenItems or //pt:TangibleFixedAssetsDepreciationIncreaseDecreaseFromTransfersBetweenItems or //ae:InvestmentsFixedAssetsDepreciationOnTransfers or //pt:InvestmentPropertiesDepreciationIncreaseDecreaseFromTransfersBetweenItems or //ae:TotalFixedAssetsDepreciationOnTransfers"/>
			<xsl:variable name="tfa_s14" select="//ae:IntangibleFixedAssetsAmortisationOtherAdjustments or //ae:TangibleFixedAssetsDepreciationOtherAdjustments or //ae:InvestmentsFixedAssetsDepreciationOtherAdjustments or //ae:InvestmentPropertyDepreciationOtherAdjustments or //ae:TangibleFixedAssetsDepreciationOtherAdjustments"/>

			<xsl:if test="$tfa_s5 or $tfa_s6 or $tfa_s7 or $tfa_s8 or $tfa_s13">
			<tr>
				<td align="left" colspan="5" style="font-weight:bold"><xsl:if test="$tfa_s1 or $tfa_s2 or $tfa_s3 or $tfa_s4"><br/></xsl:if>Depreciation</td>
			</tr>
			</xsl:if>

			<xsl:if test="$tfa_s5">
			<tr>
				<td align="left">At <xsl:call-template name="formatDate"><xsl:with-param name="date"><xsl:call-template name="date:add"><xsl:with-param name="date-time" select="//xbrli:context[@id=$y1end]//xbrli:instant"/><xsl:with-param name="duration" select="'P1D'"/></xsl:call-template></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:IntangibleFixedAssetsAggregateAmortisationImpairment[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TangibleFixedAssetsDepreciation[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:InvestmentsFixedAssetsDepreciation[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:InvestmentPropertiesDepreciation[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:TotalFixedAssetsDepreciation[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="$tfa_s6">
			<tr>
				<td align="left">Charge for year</td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:IntangibleFixedAssetsAmortisationChargedInPeriod' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TangibleFixedAssetsDepreciationChargeForPeriod' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:InvestmentsFixedAssetsDepreciationChargedInPeriod' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:InvestmentPropertiesDepreciationChargeForPeriod' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:TotalFixedAssetsDepreciationChargeInPeriod' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="$tfa_s7">
			<tr>
				<td align="left">On disposals</td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:IntangibleFixedAssetsAmortisationOnDisposals' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TangibleFixedAssetsDepreciationDisposals' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:InvestmentsFixedAssetsDepreciationOnDisposals' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:InvestmentPropertiesDepreciationDisposals' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:TotalFixedAssetsDepreciationDisposals' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="$tfa_s13">
			<tr>
				<td align="left">Transfers</td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:IntangibleFixedAssetsAmortisationIncreaseDecreaseFromTransfersBetweenItems' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TangibleFixedAssetsDepreciationIncreaseDecreaseFromTransfersBetweenItems' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:InvestmentsFixedAssetsDepreciationOnTransfers' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:InvestmentPropertiesDepreciationIncreaseDecreaseFromTransfersBetweenItems' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:TotalFixedAssetsDepreciationOnTransfers' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="$tfa_s14">
			<tr>
				<td align="left">Other adjustments</td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:IntangibleFixedAssetsAmortisationOtherAdjustments' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:TangibleFixedAssetsDepreciationOtherAdjustments' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:InvestmentsFixedAssetsDepreciationOtherAdjustments' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:InvestmentPropertyDepreciationOtherAdjustments' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:TotalFixedAssetsDepreciationOtherAdjustments' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="$tfa_s8">
			<tr>
				<td align="left">At <xsl:call-template name="formatDate"><xsl:with-param name="date" select="//xbrli:context[@id=$y2end]//xbrli:instant" /></xsl:call-template></td>
				<td style="border-top: solid thin black; border-bottom: solid medium black"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:IntangibleFixedAssetsAggregateAmortisationImpairment[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td style="border-top: solid thin black; border-bottom: solid medium black"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TangibleFixedAssetsDepreciation[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td style="border-top: solid thin black; border-bottom: solid medium black"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:InvestmentsFixedAssetsDepreciation[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td style="border-top: solid thin black; border-bottom: solid medium black"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:InvestmentPropertiesDepreciation[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td style="border-top: solid thin black; border-bottom: solid medium black"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:TotalFixedAssetsDepreciation[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:variable name="tfa_s9" select="//pt:IntangibleFixedAssets[@contextRef=$y1end] or //pt:TangibleFixedAssets[@contextRef=$y1end] or //pt:TotalInvestmentsFixedAssets[@contextRef=$y1end] or //ae:InvestmentProperties[@contextRef=$y2start] or //pt:TangibleFixedAssets[@contextRef=$y1end]"/>
			<xsl:variable name="tfa_s10" select="//pt:IntangibleFixedAssets[@contextRef=$y2end] or //pt:TangibleFixedAssets[@contextRef=$y2end] or //pt:TotalInvestmentsFixedAssets[@contextRef=$y2end] or //pt:InvestmentProperties[@contextRef=$y2end] or //pt:TangibleFixedAssets[@contextRef=$y2end]"/>

			<xsl:if test="$tfa_s9 or $tfa_s10">
			<tr>
				<td align="left" colspan="5" style="font-weight:bold"><xsl:if test="$tfa_s5 or $tfa_s6 or $tfa_s7 or $tfa_s8"><br/></xsl:if>Net Book Value</td>
			</tr>
			</xsl:if>

			<xsl:if test="$tfa_s10">
			<tr>
				<td align="left">At <xsl:call-template name="formatDate"><xsl:with-param name="date" select="//xbrli:context[@id=$y2end]//xbrli:instant" /></xsl:call-template></td>
				<td><xsl:if test="$needCol_Intangible=1"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:IntangibleFixedAssets[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></xsl:if></td>
				<td><xsl:if test="$needCol_Tangible=1"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TangibleFixedAssets[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></xsl:if></td>
				<td><xsl:if test="$needCol_Investments=1"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TotalInvestmentsFixedAssets[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></xsl:if></td>
				<td><xsl:if test="$needCol_InvestmentProperty=1"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:InvestmentProperties[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></xsl:if></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:FixedAssets[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="$tfa_s9">
			<tr>
				<td align="left">At <xsl:call-template name="formatDate"><xsl:with-param name="date" select="//xbrli:context[@id=$y1end]//xbrli:instant" /></xsl:call-template></td>
				<td style="border-bottom: solid medium black"><xsl:if test="$needCol_Intangible=1"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:IntangibleFixedAssets[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></xsl:if></td>
				<td style="border-bottom: solid medium black"><xsl:if test="$needCol_Tangible=1"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TangibleFixedAssets[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></xsl:if></td>
				<td style="border-bottom: solid medium black"><xsl:if test="$needCol_Investments=1"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TotalInvestmentsFixedAssets[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></xsl:if></td>
				<td style="border-bottom: solid medium black"><xsl:if test="$needCol_InvestmentProperty=1"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:InvestmentProperties[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></xsl:if></td>
				<td style="border-bottom: solid medium black"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:FixedAssets[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>
      
      <xsl:if test="//ae:TotalFixedAssetsFreeTextNote">
        <tr>
          <td align="left" colspan="7">
            <p><xsl:value-of select="//ae:TotalFixedAssetsFreeTextNote"/></p>
          </td>
        </tr>
      </xsl:if>
		</table><span style="page-break-avoid: end" />
	</td></tr>
</xsl:if>
<!-- ~footnote: Fixed Assets Summary -->

<!-- footnote: Intangible Fixed Assets -->
<xsl:if test="$fn_ifas=1">
	<tr><td><xsl:value-of select="$fnn_ifas"/></td><td><a name="fn_ifas"/><h3 class="nospace" style="page-break-avoid: begin">Intangible fixed assets</h3></td></tr>
	<tr><td></td><td colspan="6">
		<table width="100%">
			<xsl:variable name="ifa_s1" select="//pt:IntangibleFixedAssetsCostOrValuation[@contextRef=$y1end] or //pt:IntangibleFixedAssetsCostOrValuation[@contextRef=$y2end]"/>
			<xsl:variable name="ifa_s2" select="//pt:IntangibleFixedAssetsAggregateAmortisationImpairment[@contextRef=$y1end] or //pt:IntangibleFixedAssetsAmortisationChargedInPeriod[@contextRef=$y2] or //pt:IntangibleFixedAssetsAggregateAmortisationImpairment[@contextRef=$y2end]"/>

			<tr>
				<td align="left" style="font-weight:bold" width="39%"><xsl:if test="$ifa_s1">Cost or valuation</xsl:if></td>
				<th align="right" width="15%"><xsl:value-of select="$currencyY2"/></th>
				<td width="46%" rowspan="10"></td>
			</tr>
			<xsl:if test="//pt:IntangibleFixedAssetsCostOrValuation[@contextRef=$y1end]">
			<tr>
				<td align="left">At <xsl:call-template name="formatDate"><xsl:with-param name="date"><xsl:call-template name="date:add"><xsl:with-param name="date-time" select="//xbrli:context[@id=$y1end]//xbrli:instant"/><xsl:with-param name="duration" select="'P1D'"/></xsl:call-template></xsl:with-param></xsl:call-template></td>
				<td align="right"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:IntangibleFixedAssetsCostOrValuation[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="//pt:IntangibleFixedAssetsAdditions[@contextRef=$y2]">
			<tr>
				<td align="left">Additions</td>
				<td align="right"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:IntangibleFixedAssetsAdditions[@contextRef=$y2]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="//pt:IntangibleFixedAssetsDisposals[@contextRef=$y2]">
			<tr>
				<td align="left">Disposals</td>
				<td align="right"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:IntangibleFixedAssetsDisposals[@contextRef=$y2]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="//ae:IntangibleFixedAssetsRevaluation[@contextRef=$y2]">
			<tr>
				<td align="left">Revaluations</td>
        <td align="right"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:IntangibleFixedAssetsRevaluation[@contextRef=$y2]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="//pt:IntangibleFixedAssetsIncreaseDecreaseFromTransfersBetweenItems[@contextRef=$y2]">
			<tr>
				<td align="left">Transfers</td>
				<td align="right"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:IntangibleFixedAssetsIncreaseDecreaseFromTransfersBetweenItems[@contextRef=$y2]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="//pt:IntangibleFixedAssetsCostOrValuation[@contextRef=$y2end]">
			<tr>
				<td align="left">At <xsl:call-template name="formatDate"><xsl:with-param name="date" select="//xbrli:context[@id=$y2end]//xbrli:instant" /></xsl:call-template></td>
				<td align="right" style="border-top: solid thin black; border-bottom: solid medium black"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:IntangibleFixedAssetsCostOrValuation[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="$ifa_s2">
			<tr>
				<td colspan="2" style="font-weight:bold"><xsl:if test="$ifa_s1"><br/></xsl:if>Amortisation</td>
			</tr>
			</xsl:if>

			<xsl:if test="//pt:IntangibleFixedAssetsAggregateAmortisationImpairment[@contextRef=$y1end]">
			<tr>
				<td align="left">At <xsl:call-template name="formatDate"><xsl:with-param name="date"><xsl:call-template name="date:add"><xsl:with-param name="date-time" select="//xbrli:context[@id=$y1end]//xbrli:instant"/><xsl:with-param name="duration" select="'P1D'"/></xsl:call-template></xsl:with-param></xsl:call-template></td>
				<td align="right"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:IntangibleFixedAssetsAggregateAmortisationImpairment[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="//pt:IntangibleFixedAssetsAmortisationChargedInPeriod[@contextRef=$y2]">
			<tr>
				<td>Charge for year</td>
				<td align="right"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:IntangibleFixedAssetsAmortisationChargedInPeriod[@contextRef=$y2]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="//pt:IntangibleFixedAssetsAmortisationOnDisposals[@contextRef=$y2]">
			<tr>
				<td>On disposals</td>
				<td align="right"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:IntangibleFixedAssetsAmortisationOnDisposals[@contextRef=$y2]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="//pt:IntangibleFixedAssetsAmortisationIncreaseDecreaseFromTransfersBetweenItems[@contextRef=$y2]">
			<tr>
				<td>Transfers</td>
				<td align="right"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:IntangibleFixedAssetsAmortisationIncreaseDecreaseFromTransfersBetweenItems[@contextRef=$y2]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="//ae:IntangibleFixedAssetsAmortisationOtherAdjustments[@contextRef=$y2]">
			<tr>
				<td>Other adjustments</td>
				<td align="right"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:IntangibleFixedAssetsAmortisationOtherAdjustments[@contextRef=$y2]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="//pt:IntangibleFixedAssetsAggregateAmortisationImpairment[@contextRef=$y2end]">
			<tr>
				<td align="left">At <xsl:call-template name="formatDate"><xsl:with-param name="date" select="//xbrli:context[@id=$y2end]//xbrli:instant" /></xsl:call-template></td>
				<td align="right" style="border-top: solid thin black; border-bottom: solid medium black"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:IntangibleFixedAssetsAggregateAmortisationImpairment[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="//pt:IntangibleFixedAssets">
			<tr>
				<td colspan="2" style="font-weight:bold"><xsl:if test="$ifa_s2"><br/></xsl:if>Net Book Value</td>
			</tr>
			</xsl:if>

			<xsl:if test="//pt:IntangibleFixedAssets[@contextRef=$y2end]">
			<tr>
				<td align="left">At <xsl:call-template name="formatDate"><xsl:with-param name="date" select="//xbrli:context[@id=$y2end]//xbrli:instant" /></xsl:call-template></td>
				<td align="right"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:IntangibleFixedAssets[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="//pt:IntangibleFixedAssets[@contextRef=$y1end] ">
			<tr>
				<td align="left">At <xsl:call-template name="formatDate"><xsl:with-param name="date" select="//xbrli:context[@id=$y1end]//xbrli:instant" /></xsl:call-template></td>
				<td align="right" style="border-bottom: solid medium black"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:IntangibleFixedAssets[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>
      <xsl:if test="//ae:IntangibleAssetsFreeTextNote">
        <tr>
          <td colspan="2">
            <p><xsl:value-of select="//ae:IntangibleAssetsFreeTextNote"/></p>
          </td>
        </tr>
      </xsl:if>

		</table><span style="page-break-avoid: end"/>
	</td></tr>
</xsl:if>
	
<!-- ~footnote: Intangible Fixed Assets -->


<!-- footnote@: Tangible Fixed Assets -->
<xsl:if test="$fn_tfas=1">
	<xsl:variable name="needCol_LandBuildings">0<xsl:if test="//pt:LandBuildingsCostOrValuation[@contextRef=$y1end] or //pt:LandBuildingsAdditions or //pt:LandBuildingsDisposals or //pt:LandBuildingsCostOrValuation[@contextRef=$y2end] or //pt:LandBuildingsDepreciation[@contextRef=$y1end] or //pt:LandBuildingsDepreciationChargeForPeriod or //pt:LandBuildingsDepreciationDisposals or //pt:LandBuildingsDepreciation[@contextRef=$y2end] or //pt:LandBuildings[@contextRef=$y1end] or //pt:LandBuildings[@contextRef=$y2end]">1</xsl:if></xsl:variable>
	<xsl:variable name="needCol_PlantMachinery">0<xsl:if test="//pt:PlantMachineryCostOrValuation[@contextRef=$y1end] or //pt:PlantMachineryAdditions or //pt:PlantMachineryDisposals or //pt:PlantMachineryCostOrValuation[@contextRef=$y2end] or //pt:PlantMachineryDepreciation[@contextRef=$y1end] or //pt:PlantMachineryDepreciationChargeForPeriod or //pt:PlantMachineryDepreciationDisposals or //pt:PlantMachineryDepreciation[@contextRef=$y2end] or //pt:PlantMachinery[@contextRef=$y1end] or //pt:PlantMachinery[@contextRef=$y2end]">1</xsl:if></xsl:variable>
	<xsl:variable name="needCol_FixturesFittings">0<xsl:if test="//pt:FixturesFittingsToolsEquipmentCostOrValuation[@contextRef=$y1end] or //pt:FixturesFittingsToolsEquipmentAdditions or //pt:FixturesFittingsToolsEquipmentDisposals or //pt:FixturesFittingsToolsEquipmentCostOrValuation[@contextRef=$y2end] or //pt:FixturesFittingsToolsEquipmentDepreciation[@contextRef=$y1end] or //pt:FixturesFittingsToolsEquipmentDepreciationChargeForPeriod or //pt:FixturesFittingsToolsEquipmentDepreciationDisposals or //pt:FixturesFittingsToolsEquipmentDepreciation[@contextRef=$y2end] or //pt:FixturesFittingsToolsEquipment[@contextRef=$y1end] or //pt:FixturesFittingsToolsEquipment[@contextRef=$y2end]">1</xsl:if></xsl:variable>
	<tr><td><a name="fn_tfas"/><xsl:value-of select="$fnn_tfas"/></td><td><h3 class="nospace" style="page-break-avoid: begin">Tangible fixed assets</h3></td></tr>
	<tr><td></td><td colspan="7">
		<table width="100%" style="text-align: right">
			<tr>
        <!-- 
          Columns in this table are a fixed width of 14%.  Adjust the size of
          the first column to match. 
        -->
        <xsl:variable name="tfa_number_columns" select="1 + $needCol_LandBuildings + $needCol_PlantMachinery + $needCol_FixturesFittings + count(//ae:OtherTypeTangibleFixedAsset-Analysis)" />
        <xsl:if test="tfa_number_columns > 5">
				<xsl:message terminate="yes">REJECTION - Only 4 different types of Tangible Assets are allowed plus the total column - REJECTION </xsl:message>
		</xsl:if>
				<td width="{100 - $tfa_number_columns * 14}%"></td>
        <xsl:choose>
            <xsl:when test="$needCol_LandBuildings = 1">
              <th align="right" width="14%">Land &amp; Buildings</th>
            </xsl:when>
            <xsl:otherwise>
              <th width="0"></th>
            </xsl:otherwise>
        </xsl:choose>
        <xsl:choose>
            <xsl:when test="$needCol_PlantMachinery = 1">
              <th align="right" width="14%">Plant &amp; Machinery</th>
            </xsl:when>
            <xsl:otherwise>
              <th width="0"></th>
            </xsl:otherwise>
        </xsl:choose>
        <xsl:choose>
            <xsl:when test="$needCol_FixturesFittings = 1">
              <th align="right" width="14%">Fixtures &amp; Fittings</th>
            </xsl:when>
            <xsl:otherwise>
              <th width="0"></th>
            </xsl:otherwise>
        </xsl:choose>
				<xsl:for-each select="//ae:OtherTypeTangibleFixedAsset-Analysis">
					<th align="right" width="14%"><xsl:value-of select=".//ae:NameTypeTangibleFixedAsset" /></th>
				</xsl:for-each>
				<th align="right" width="14%">Total</th>
			</tr>

			<xsl:variable name="tfa_s1" select="//pt:LandBuildingsCostOrValuation[@contextRef=$y1end] or //pt:PlantMachineryCostOrValuation[@contextRef=$y1end] or //pt:FixturesFittingsToolsEquipmentCostOrValuation[@contextRef=$y1end] or //ae:OtherTangibleFixedAssetCostOrValuation[@contextRef=$y1end] or //pt:TangibleFixedAssetsCostOrValuation[@contextRef=$y1end]" />
			<xsl:variable name="tfa_s2" select="//pt:LandBuildingsAdditions or //pt:PlantMachineryAdditions or //pt:FixturesFittingsToolsEquipmentAdditions or //ae:OtherTangibleFixedAssetAdditions or //pt:TangibleFixedAssetsAdditions" />
			<xsl:variable name="tfa_s3" select="//pt:LandBuildingsDisposals or //pt:PlantMachineryDisposals or //pt:FixturesFittingsToolsEquipmentDisposals or //ae:OtherTangibleFixedAssetDisposals or //pt:TangibleFixedAssetsDisposals" />
			<xsl:variable name="tfa_s4" select="//pt:LandBuildingsCostOrValuation[@contextRef=$y2end] or //pt:PlantMachineryCostOrValuation[@contextRef=$y2end] or //pt:FixturesFittingsToolsEquipmentCostOrValuation[@contextRef=$y2end] or //ae:OtherTangibleFixedAssetCostOrValuation[@contextRef=$y2end] or //pt:TangibleFixedAssetsCostOrValuation[@contextRef=$y2end]" />
			<xsl:variable name="tfa_s11" select="//pt:LandBuildingsIncreaseDecreaseFromRevaluations or //pt:PlantMachineryIncreaseDecreaseFromRevaluations or //pt:FixturesFittingsToolsEquipmentIncreaseDecreaseFromRevaluations or //ae:OtherTangibleFixedAssetRevaluation or //pt:TangibleFixedAssetsIncreaseDecreaseFromRevaluations" />
			<xsl:variable name="tfa_s12" select="//pt:LandBuildingsIncreaseDecreaseFromTransfersBetweenItems or //pt:PlantMachineryIncreaseDecreaseFromTransfersBetweenItems or //pt:FixturesFittingsToolsEquipmentIncreaseDecreaseFromTransfersBetweenItems or //ae:OtherTangibleFixedAssetTransfers or //pt:TangibleFixedAssetsIncreaseDecreaseFromTransfersBetweenItems" />

			<tr>
				<td align="left" style="font-weight:bold"><xsl:if test="$tfa_s1 or $tfa_s2 or $tfa_s3 or $tfa_s4">Cost</xsl:if></td>
				<th align="right">
					<xsl:if test="$needCol_LandBuildings = 1"><xsl:value-of select="$currencyY2"/></xsl:if>
				</th>
				<th align="right">
					<xsl:if test="$needCol_PlantMachinery = 1"><xsl:value-of select="$currencyY2"/></xsl:if>
				</th>
				<th align="right">
					<xsl:if test="$needCol_FixturesFittings = 1"><xsl:value-of select="$currencyY2"/></xsl:if>
				</th>
				<xsl:for-each select="//ae:OtherTypeTangibleFixedAsset-Analysis">
					<th align="right"><xsl:value-of select="$currencyY2"/></th>
				</xsl:for-each>
				<th align="right"><xsl:value-of select="$currencyY2"/></th>
			</tr>

			<xsl:if test="$tfa_s1">
			<tr>
				<td align="left">At <xsl:call-template name="formatDate"><xsl:with-param name="date"><xsl:call-template name="date:add"><xsl:with-param name="date-time" select="//xbrli:context[@id=$y1end]//xbrli:instant"/><xsl:with-param name="duration" select="'P1D'"/></xsl:call-template></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:LandBuildingsCostOrValuation[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:PlantMachineryCostOrValuation[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:FixturesFittingsToolsEquipmentCostOrValuation[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<xsl:for-each select="//ae:OtherTypeTangibleFixedAsset-Analysis">
					<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='.//ae:OtherTangibleFixedAssetCostOrValuation[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				</xsl:for-each>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TangibleFixedAssetsCostOrValuation[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="$tfa_s2">
			<tr>
				<td align="left">Additions</td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:LandBuildingsAdditions' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:PlantMachineryAdditions' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:FixturesFittingsToolsEquipmentAdditions' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<xsl:for-each select="//ae:OtherTypeTangibleFixedAsset-Analysis">
					<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='.//ae:OtherTangibleFixedAssetAdditions' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				</xsl:for-each>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TangibleFixedAssetsAdditions' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="$tfa_s3">
			<tr>
				<td align="left">Disposals</td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:LandBuildingsDisposals' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:PlantMachineryDisposals' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:FixturesFittingsToolsEquipmentDisposals' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<xsl:for-each select="//ae:OtherTypeTangibleFixedAsset-Analysis">
					<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='.//ae:OtherTangibleFixedAssetDisposals' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				</xsl:for-each>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TangibleFixedAssetsDisposals' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

      <xsl:if test="$tfa_s11">
      <tr>
				<td align="left">Revaluations</td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:LandBuildingsIncreaseDecreaseFromRevaluations' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:PlantMachineryIncreaseDecreaseFromRevaluations' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:FixturesFittingsToolsEquipmentIncreaseDecreaseFromRevaluations' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<xsl:for-each select="//ae:OtherTypeTangibleFixedAsset-Analysis">
					<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='.//ae:OtherTangibleFixedAssetRevaluation' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				</xsl:for-each>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TangibleFixedAssetsIncreaseDecreaseFromRevaluations' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
      </tr>
      </xsl:if>

      <xsl:if test="$tfa_s12">
      <tr>
				<td align="left">Transfers</td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:LandBuildingsIncreaseDecreaseFromTransfersBetweenItems' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:PlantMachineryIncreaseDecreaseFromTransfersBetweenItems' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:FixturesFittingsToolsEquipmentIncreaseDecreaseFromTransfersBetweenItems' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<xsl:for-each select="//ae:OtherTypeTangibleFixedAsset-Analysis">
					<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='.//ae:OtherTangibleFixedAssetTransfers' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				</xsl:for-each>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TangibleFixedAssetsIncreaseDecreaseFromTransfersBetweenItems' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
      </tr>
      </xsl:if>


			<xsl:if test="$tfa_s4">
			<tr>
				<td align="left">At <xsl:call-template name="formatDate"><xsl:with-param name="date" select="//xbrli:context[@id=$y2end]//xbrli:instant" /></xsl:call-template></td>
				<td style="border-top: solid thin black; border-bottom: solid medium black"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:LandBuildingsCostOrValuation[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td style="border-top: solid thin black; border-bottom: solid medium black"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:PlantMachineryCostOrValuation[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td style="border-top: solid thin black; border-bottom: solid medium black"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:FixturesFittingsToolsEquipmentCostOrValuation[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<xsl:for-each select="//ae:OtherTypeTangibleFixedAsset-Analysis">
					<td style="border-top: solid thin black; border-bottom: solid medium black"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='.//ae:OtherTangibleFixedAssetCostOrValuation[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
        </xsl:for-each>
        <td style="border-top: solid thin black; border-bottom: solid medium black; padding-left: 5px"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TangibleFixedAssetsCostOrValuation[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:variable name="tfa_s5" select="//pt:LandBuildingsDepreciation[@contextRef=$y1end] or //pt:PlantMachineryDepreciation[@contextRef=$y1end] or //pt:FixturesFittingsToolsEquipmentDepreciation[@contextRef=$y1end] or //ae:OtherTangibleFixedAssetDepreciation[@contextRef=$y1end] or //pt:TangibleFixedAssetsDepreciation[@contextRef=$y1end]"/>
			<xsl:variable name="tfa_s6" select="//pt:LandBuildingsDepreciationChargeForPeriod or //pt:PlantMachineryDepreciationChargeForPeriod or //pt:FixturesFittingsToolsEquipmentDepreciationChargeForPeriod or //ae:OtherTangibleFixedAssetDepreciationChargeForPeriod or //pt:TangibleFixedAssetsDepreciationChargeForPeriod"/>
			<xsl:variable name="tfa_s7" select="//pt:LandBuildingsDepreciationDisposals or //pt:PlantMachineryDepreciationDisposals or //pt:FixturesFittingsToolsEquipmentDepreciationDisposals or //ae:OtherTangibleFixedAssetDepreciationDisposals or //pt:TangibleFixedAssetsDepreciationDisposals"/>
			<xsl:variable name="tfa_s8" select="//pt:LandBuildingsDepreciation[@contextRef=$y2end] or //pt:PlantMachineryDepreciation[@contextRef=$y2end] or //pt:FixturesFittingsToolsEquipmentDepreciation[@contextRef=$y2end] or //ae:OtherTangibleFixedAssetDepreciation[@contextRef=$y2end] or //pt:TangibleFixedAssetsDepreciation[@contextRef=$y2end]"/>
			<xsl:variable name="tfa_s13" select="//pt:LandBuildingsDepreciationIncreaseDecreaseFromTransfersBetweenItems or //pt:PlantMachineryDepreciationIncreaseDecreaseFromTransfersBetweenItems or //pt:FixturesFittingsToolsEquipmentDepreciationIncreaseDecreaseFromTransfersBetweenItems or //ae:OtherTangibleFixedAssetDepreciationTransfers or //pt:TangibleFixedAssetsDepreciationIncreaseDecreaseFromTransfersBetweenItems"/>
			<xsl:variable name="tfa_s14" select="//ae:LandBuildingsDepreciationOtherAdjustments or //ae:PlantMachineryDepreciationOtherAdjustments or //ae:FixturesFittingsToolsEquipmentDepreciationOtherAdjustments or //ae:OtherTangibleFixedAssetDepreciationOtherAdjustments or //ae:TangibleFixedAssetsDepreciationOtherAdjustments"/>

			<xsl:if test="$tfa_s5 or $tfa_s6 or $tfa_s7 or $tfa_s8 or $tfa_s13">
			<tr>
				<td align="left" colspan="5" style="font-weight:bold"><xsl:if test="$tfa_s1 or $tfa_s2 or $tfa_s3 or $tfa_s4"><br/></xsl:if>Depreciation</td>
			</tr>
			</xsl:if>

			<xsl:if test="$tfa_s5">
			<tr>
				<td align="left">At <xsl:call-template name="formatDate"><xsl:with-param name="date"><xsl:call-template name="date:add"><xsl:with-param name="date-time" select="//xbrli:context[@id=$y1end]//xbrli:instant"/><xsl:with-param name="duration" select="'P1D'"/></xsl:call-template></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:LandBuildingsDepreciation[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:PlantMachineryDepreciation[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:FixturesFittingsToolsEquipmentDepreciation[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<xsl:for-each select="//ae:OtherTypeTangibleFixedAsset-Analysis">
					<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='.//ae:OtherTangibleFixedAssetDepreciation[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				</xsl:for-each>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TangibleFixedAssetsDepreciation[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="$tfa_s6">
			<tr>
				<td align="left">Charge for year</td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:LandBuildingsDepreciationChargeForPeriod' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:PlantMachineryDepreciationChargeForPeriod' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:FixturesFittingsToolsEquipmentDepreciationChargeForPeriod' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<xsl:for-each select="//ae:OtherTypeTangibleFixedAsset-Analysis">
					<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='.//ae:OtherTangibleFixedAssetDepreciationChargeForPeriod' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				</xsl:for-each>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TangibleFixedAssetsDepreciationChargeForPeriod' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="$tfa_s7">
			<tr>

				<td align="left">On disposals</td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:LandBuildingsDepreciationDisposals' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:PlantMachineryDepreciationDisposals' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:FixturesFittingsToolsEquipmentDepreciationDisposals' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<xsl:for-each select="//ae:OtherTypeTangibleFixedAsset-Analysis">
					<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='.//ae:OtherTangibleFixedAssetDepreciationDisposals' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				</xsl:for-each>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TangibleFixedAssetsDepreciationDisposals' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="$tfa_s13">
			<tr>
				<td align="left">Transfers</td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:LandBuildingsDepreciationIncreaseDecreaseFromTransfersBetweenItems' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:PlantMachineryDepreciationIncreaseDecreaseFromTransfersBetweenItems' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:FixturesFittingsToolsEquipmentDepreciationIncreaseDecreaseFromTransfersBetweenItems' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<xsl:for-each select="//ae:OtherTypeTangibleFixedAsset-Analysis">
					<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='.//ae:OtherTangibleFixedAssetDepreciationTransfers' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				</xsl:for-each>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TangibleFixedAssetsDepreciationIncreaseDecreaseFromTransfersBetweenItems' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="$tfa_s14">
			<tr>
				<td align="left">Other adjustments</td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:LandBuildingsDepreciationOtherAdjustments' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:PlantMachineryDepreciationOtherAdjustments' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:FixturesFittingsToolsEquipmentDepreciationOtherAdjustments' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<xsl:for-each select="//ae:OtherTypeTangibleFixedAsset-Analysis">
					<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='.//ae:OtherTangibleFixedAssetDepreciationOtherAdjustments' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				</xsl:for-each>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:TangibleFixedAssetsDepreciationOtherAdjustments' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="$tfa_s8">
			<tr>
				<td align="left">At <xsl:call-template name="formatDate"><xsl:with-param name="date" select="//xbrli:context[@id=$y2end]//xbrli:instant" /></xsl:call-template></td>
				<td style="border-top: solid thin black; border-bottom: solid medium black"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:LandBuildingsDepreciation[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td style="border-top: solid thin black; border-bottom: solid medium black"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:PlantMachineryDepreciation[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td style="border-top: solid thin black; border-bottom: solid medium black"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:FixturesFittingsToolsEquipmentDepreciation[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<xsl:for-each select="//ae:OtherTypeTangibleFixedAsset-Analysis">
					<td style="border-top: solid thin black; border-bottom: solid medium black"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='.//ae:OtherTangibleFixedAssetDepreciation[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				</xsl:for-each>
				<td style="border-top: solid thin black; border-bottom: solid medium black"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TangibleFixedAssetsDepreciation[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:variable name="tfa_s9" select="//pt:LandBuildings[@contextRef=$y1end] or //pt:PlantMachinery[@contextRef=$y1end] or //pt:FixturesFittingsToolsEquipment[@contextRef=$y1end] or //ae:OtherTangibleFixedAsset[@contextRef=$y2start] or //pt:TangibleFixedAssets[@contextRef=$y1end]"/>
			<xsl:variable name="tfa_s10" select="//pt:LandBuildings[@contextRef=$y2end] or //pt:PlantMachinery[@contextRef=$y2end] or //pt:FixturesFittingsToolsEquipment[@contextRef=$y2end] or //ae:OtherTangibleFixedAsset[@contextRef=$y2end] or //pt:TangibleFixedAssets[@contextRef=$y2end]"/>

			<xsl:if test="$tfa_s9 or $tfa_s10">
			<tr>
				<td align="left" colspan="5" style="font-weight:bold"><xsl:if test="$tfa_s5 or $tfa_s6 or $tfa_s7 or $tfa_s8"><br/></xsl:if>Net Book Value</td>
			</tr>
			</xsl:if>

			<xsl:if test="$tfa_s10">
			<tr>
				<td align="left">At <xsl:call-template name="formatDate"><xsl:with-param name="date" select="//xbrli:context[@id=$y2end]//xbrli:instant" /></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:LandBuildings[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:PlantMachinery[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:FixturesFittingsToolsEquipment[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<xsl:for-each select="//ae:OtherTypeTangibleFixedAsset-Analysis">
					<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='.//ae:OtherTangibleFixedAsset[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				</xsl:for-each>
				<td><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TangibleFixedAssets[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="$tfa_s9">
			<tr>
				<td align="left">At <xsl:call-template name="formatDate"><xsl:with-param name="date" select="//xbrli:context[@id=$y1end]//xbrli:instant" /></xsl:call-template></td>
				<td style="border-bottom: solid medium black"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:LandBuildings[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td style="border-bottom: solid medium black"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:PlantMachinery[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<td style="border-bottom: solid medium black"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:FixturesFittingsToolsEquipment[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				<xsl:for-each select="//ae:OtherTypeTangibleFixedAsset-Analysis">
					<td style="border-bottom: solid medium black"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='.//ae:OtherTangibleFixedAsset[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
				</xsl:for-each>
				<td style="border-bottom: solid medium black"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TangibleFixedAssets[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>
      
      <xsl:if test="//ae:TangibleAssetsFreeTextNote">
        <tr>
          <td align="left" colspan="7">
            <p><xsl:value-of select="//ae:TangibleAssetsFreeTextNote"/></p>
          </td>
        </tr>
      </xsl:if>
		</table><span style="page-break-avoid: end" />
	</td></tr>
</xsl:if>
<!-- ~footnote: Tangible Fixed Assets -->

<!-- footnote: Fixed Assets Investments -->
<xsl:if test="$fn_fais=1">
	<tr><td><xsl:value-of select="$fnn_fais"/></td><td><a name="fn_fais"/><h3 class="nospace" style="page-break-avoid: begin">Investments (Fixed Assets)</h3></td></tr>
	<tr><td></td><td colspan="6">
		<table width="100%">
			<xsl:variable name="fai_s1" select="//pt:TotalFixedAssetInvestmentsCostOrValuation[@contextRef=$y1end] or //pt:TotalFixedAssetInvestmentsCostOrValuation[@contextRef=$y2end]"/>
			<xsl:variable name="fai_s2" select="//ae:InvestmentsFixedAssetsDepreciation[@contextRef=$y1end] or //ae:InvestmentsFixedAssetsDepreciationChargedInPeriod[@contextRef=$y2] or //ae:InvestmentsFixedAssetsDepreciation[@contextRef=$y2end]"/>

			<tr>
				<td align="left" style="font-weight:bold" width="39%"><xsl:if test="$fai_s1">Cost Or Valuation</xsl:if></td>
				<th align="right" width="15%"><xsl:if test="$fai_s1"><xsl:value-of select="$currencyY2"/></xsl:if></th>
				<td width="46%" rowspan="10"></td>
			</tr>
			<xsl:if test="//pt:TotalFixedAssetInvestmentsCostOrValuation[@contextRef=$y1end]">
			<tr>
				<td align="left">At <xsl:call-template name="formatDate"><xsl:with-param name="date"><xsl:call-template name="date:add"><xsl:with-param name="date-time" select="//xbrli:context[@id=$y1end]//xbrli:instant"/><xsl:with-param name="duration" select="'P1D'"/></xsl:call-template></xsl:with-param></xsl:call-template></td>
				<td align="right"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TotalFixedAssetInvestmentsCostOrValuation[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="//pt:TotalFixedAssetInvestmentsAdditions[@contextRef=$y2]">
			<tr>
				<td align="left">Additions</td>
				<td align="right"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TotalFixedAssetInvestmentsAdditions[@contextRef=$y2]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="//pt:TotalFixedAssetInvestmentsDisposals[@contextRef=$y2]">
			<tr>
				<td align="left">Disposals</td>
				<td align="right"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TotalFixedAssetInvestmentsDisposals[@contextRef=$y2]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="//ae:InvestmentsFixedAssetsRevaluations[@contextRef=$y2]">
			<tr>
				<td align="left">Revaluations</td>

        <td align="right"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:InvestmentsFixedAssetsRevaluations[@contextRef=$y2]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="//ae:InvestmentsFixedAssetsIncreaseDecreaseFromTransfersBetweenItems[@contextRef=$y2]">
			<tr>
				<td align="left">Transfers</td>
				<td align="right"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:InvestmentsFixedAssetsIncreaseDecreaseFromTransfersBetweenItems[@contextRef=$y2]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="//pt:TotalFixedAssetInvestmentsCostOrValuation[@contextRef=$y2end]">
			<tr>
				<td align="left">At <xsl:call-template name="formatDate"><xsl:with-param name="date" select="//xbrli:context[@id=$y2end]//xbrli:instant" /></xsl:call-template></td>
				<td align="right" style="border-top: solid thin black; border-bottom: solid medium black"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TotalFixedAssetInvestmentsCostOrValuation[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="$fai_s2">
			<tr>
				<td colspan="2" style="font-weight:bold"><xsl:if test="$fai_s2"><br/></xsl:if>Depreciation</td>
			</tr>
			</xsl:if>

			<xsl:if test="//ae:InvestmentsFixedAssetsDepreciation[@contextRef=$y1end]">
			<tr>
				<td align="left">At <xsl:call-template name="formatDate"><xsl:with-param name="date"><xsl:call-template name="date:add"><xsl:with-param name="date-time" select="//xbrli:context[@id=$y1end]//xbrli:instant"/><xsl:with-param name="duration" select="'P1D'"/></xsl:call-template></xsl:with-param></xsl:call-template></td>
				<td align="right"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:InvestmentsFixedAssetsDepreciation[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="//ae:InvestmentsFixedAssetsDepreciationChargedInPeriod[@contextRef=$y2]">
			<tr>
				<td>Charge for year</td>
				<td align="right"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:InvestmentsFixedAssetsDepreciationChargedInPeriod[@contextRef=$y2]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="//ae:InvestmentsFixedAssetsDepreciationOnDisposals[@contextRef=$y2]">
			<tr>
				<td>On disposals</td>
				<td align="right"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:InvestmentsFixedAssetsDepreciationOnDisposals[@contextRef=$y2]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="//ae:InvestmentsFixedAssetsDepreciationOnTransfers[@contextRef=$y2]">
			<tr>
				<td>Transfers</td>
				<td align="right"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:InvestmentsFixedAssetsDepreciationOnTransfers[@contextRef=$y2]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="//ae:InvestmentsFixedAssetsDepreciationOtherAdjustments[@contextRef=$y2]">
			<tr>
				<td>Other adjustments</td>
				<td align="right"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:InvestmentsFixedAssetsDepreciationOtherAdjustments[@contextRef=$y2]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="//ae:InvestmentsFixedAssetsDepreciation[@contextRef=$y2end]">
			<tr>
				<td align="left">At <xsl:call-template name="formatDate"><xsl:with-param name="date" select="//xbrli:context[@id=$y2end]//xbrli:instant" /></xsl:call-template></td>
				<td align="right" style="border-top: solid thin black; border-bottom: solid medium black"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:InvestmentsFixedAssetsDepreciation[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="$fai_s1 and //pt:TotalInvestmentsFixedAssets">
			<tr>
				<td colspan="2" style="font-weight:bold"><xsl:if test="$fai_s2"><br/></xsl:if>Net Book Value</td>
			</tr>
			</xsl:if>


			<xsl:if test="$fai_s1 and //pt:TotalInvestmentsFixedAssets[@contextRef=$y2end]">
			<tr>
				<td align="left">At <xsl:call-template name="formatDate"><xsl:with-param name="date" select="//xbrli:context[@id=$y2end]//xbrli:instant" /></xsl:call-template></td>
				<td align="right"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TotalInvestmentsFixedAssets[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="$fai_s1 and //pt:TotalInvestmentsFixedAssets[@contextRef=$y1end] ">
			<tr>
				<td align="left">At <xsl:call-template name="formatDate"><xsl:with-param name="date" select="//xbrli:context[@id=$y1end]//xbrli:instant" /></xsl:call-template></td>
				<td align="right" style="border-bottom: solid medium black"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TotalInvestmentsFixedAssets[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>
      <xsl:if test="//ae:InvestmentsFixedAssetsFreeTextNote">
        <tr>
          <td colspan="2">
            <p><xsl:value-of select="//ae:InvestmentsFixedAssetsFreeTextNote"/></p>
          </td>
        </tr>
      </xsl:if>

		</table><span style="page-break-avoid: end"/>
	</td></tr>
</xsl:if>
	
<!-- ~footnote: Fixed Asset Investments -->

<!-- footnote: Investment Properties -->
<xsl:if test="$fn_ip=1">
	<tr><td><xsl:value-of select="$fnn_ip"/></td><td><a name="fn_ip"/><h3 class="nospace" style="page-break-avoid: begin">Investment Property</h3></td></tr>
	<tr><td></td><td colspan="6">
		<table width="100%">
			<xsl:variable name="ip_s1" select="//pt:InvestmentPropertiesCostOrValuation[@contextRef=$y1end] or //pt:InvestmentPropertiesCostOrValuation[@contextRef=$y2end]"/>
			<xsl:variable name="ip_s2" select="//pt:InvestmentPropertiesDepreciation[@contextRef=$y1end] or //pt:InvestmentPropertiesDepreciationChargeForPeriod[@contextRef=$y2] or //pt:InvestmentPropertiesDepreciation[@contextRef=$y2end]"/>

			<tr>
				<td align="left" style="font-weight:bold" width="39%"><xsl:if test="$ip_s1">Cost Or Valuation</xsl:if></td>
				<th align="right" width="15%"><xsl:if test="$ip_s1"><xsl:value-of select="$currencyY2"/></xsl:if></th>
				<td width="46%" rowspan="10"></td>
			</tr>
			<xsl:if test="//pt:InvestmentPropertiesCostOrValuation[@contextRef=$y1end]">
			<tr>
				<td align="left">At <xsl:call-template name="formatDate"><xsl:with-param name="date"><xsl:call-template name="date:add"><xsl:with-param name="date-time" select="//xbrli:context[@id=$y1end]//xbrli:instant"/><xsl:with-param name="duration" select="'P1D'"/></xsl:call-template></xsl:with-param></xsl:call-template></td>
				<td align="right"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:InvestmentPropertiesCostOrValuation[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="//pt:InvestmentPropertiesAdditions[@contextRef=$y2]">
			<tr>
				<td align="left">Additions</td>
				<td align="right"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:InvestmentPropertiesAdditions[@contextRef=$y2]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="//pt:InvestmentPropertiesDisposals[@contextRef=$y2]">
			<tr>
				<td align="left">Disposals</td>
				<td align="right"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:InvestmentPropertiesDisposals[@contextRef=$y2]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="//ae:InvestmentPropertiesIncreaseDecreaseFromRevaluations[@contextRef=$y2]">
			<tr>
				<td align="left">Revaluations</td>
        <td align="right"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:InvestmentPropertiesIncreaseDecreaseFromRevaluations[@contextRef=$y2]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="//ae:InvestmentPropertiesIncreaseDecreaseFromTransfersBetweenItems[@contextRef=$y2]">
			<tr>
				<td align="left">Transfers</td>
				<td align="right"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:InvestmentPropertiesIncreaseDecreaseFromTransfersBetweenItems[@contextRef=$y2]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="//pt:InvestmentPropertiesCostOrValuation[@contextRef=$y2end]">
			<tr>
				<td align="left">At <xsl:call-template name="formatDate"><xsl:with-param name="date" select="//xbrli:context[@id=$y2end]//xbrli:instant" /></xsl:call-template></td>
				<td align="right" style="border-top: solid thin black; border-bottom: solid medium black"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:InvestmentPropertiesCostOrValuation[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="$ip_s2">
			<tr>
				<td colspan="2" style="font-weight:bold"><xsl:if test="$ip_s2"><br/></xsl:if>Depreciation</td>
			</tr>
			</xsl:if>

			<xsl:if test="//pt:InvestmentPropertiesDepreciation[@contextRef=$y1end]">
			<tr>
				<td align="left">At <xsl:call-template name="formatDate"><xsl:with-param name="date"><xsl:call-template name="date:add"><xsl:with-param name="date-time" select="//xbrli:context[@id=$y1end]//xbrli:instant"/><xsl:with-param name="duration" select="'P1D'"/></xsl:call-template></xsl:with-param></xsl:call-template></td>
				<td align="right"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:InvestmentPropertiesDepreciation[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="//pt:InvestmentPropertiesDepreciationChargeForPeriod[@contextRef=$y2]">
			<tr>
				<td>Charge for year</td>
				<td align="right"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:InvestmentPropertiesDepreciationChargeForPeriod[@contextRef=$y2]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="//pt:InvestmentPropertiesDepreciationDisposals[@contextRef=$y2]">
			<tr>
				<td>On disposals</td>
				<td align="right"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:InvestmentPropertiesDepreciationDisposals[@contextRef=$y2]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="//pt:InvestmentPropertiesDepreciationIncreaseDecreaseFromTransfersBetweenItems[@contextRef=$y2]">
			<tr>
				<td>Transfers</td>
				<td align="right"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:InvestmentPropertiesDepreciationIncreaseDecreaseFromTransfersBetweenItems[@contextRef=$y2]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="//ae:InvestmentPropertyDepreciationOtherAdjustments[@contextRef=$y2]">
			<tr>
				<td>Other adjustments</td>
				<td align="right"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:InvestmentPropertyDepreciationOtherAdjustments[@contextRef=$y2]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="//pt:InvestmentPropertiesDepreciation[@contextRef=$y2end]">
			<tr>
				<td align="left">At <xsl:call-template name="formatDate"><xsl:with-param name="date" select="//xbrli:context[@id=$y2end]//xbrli:instant" /></xsl:call-template></td>
				<td align="right" style="border-top: solid thin black; border-bottom: solid medium black"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:InvestmentPropertiesDepreciation[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="$ip_s1 and //pt:InvestmentProperties">
			<tr>
				<td colspan="2" style="font-weight:bold"><xsl:if test="$ip_s2"><br/></xsl:if>Net Book Value</td>
			</tr>
			</xsl:if>


			<xsl:if test="$ip_s1 and //pt:InvestmentProperties[@contextRef=$y2end]">
			<tr>
				<td align="left">At <xsl:call-template name="formatDate"><xsl:with-param name="date" select="//xbrli:context[@id=$y2end]//xbrli:instant" /></xsl:call-template></td>
				<td align="right"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:InvestmentProperties[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>

			<xsl:if test="$ip_s1 and //pt:InvestmentProperties[@contextRef=$y1end] ">
			<tr>
				<td align="left">At <xsl:call-template name="formatDate"><xsl:with-param name="date" select="//xbrli:context[@id=$y1end]//xbrli:instant" /></xsl:call-template></td>
				<td align="right" style="border-bottom: solid medium black"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:InvestmentProperties[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td>
			</tr>
			</xsl:if>
      <xsl:if test="//ae:InvestmentPropertyFreeTextNote">
        <tr>
          <td colspan="2">
            <p><xsl:value-of select="//ae:InvestmentPropertyFreeTextNote"/></p>
          </td>
        </tr>
      </xsl:if>

		</table><span style="page-break-avoid: end"/>
	</td></tr>
</xsl:if>
	
<!-- ~footnote: Investment Properties -->

<!-- footnote: Debtors -->
<xsl:if test="$fn_debtors=1">
	<tr>
		<td><xsl:value-of select="$fnn_debtors"/></td><td><a name="fn_debtors"/><h3 class="nospace" style="page-break-avoid: begin">Debtors</h3></td>
	</tr>
<xsl:if test="//ae:DebtorsFreeTextNote"><tr><td></td><td colspan="4"><xsl:value-of select="//ae:DebtorsFreeTextNote"/></td></tr></xsl:if>
  <xsl:if test="//pt:TradeDebtors or //pt:CalledUpShareCapitalNotPaidCurrentAsset or //pt:PrepaymentsAccruedIncomeCurrentAsset or //pt:OtherDebtors or //pt:Debtors">
	<tr><td></td><td width="50%" colspan="2"></td><th align="right" width="15%"><xsl:value-of select="$namey2" /></th><th align="right" width="15%"><xsl:value-of select="$namey1" /></th><td colspan="3" width="1%"></td></tr>
<tr style="font-weight: bold">
  <td></td>
  <td></td>
  <td></td>
  <td class="rightalign"><xsl:value-of select="$currencyY2"/></td>
  <td class="rightalign"><xsl:value-of select="$currencyY1"/></td>
</tr>
  </xsl:if>
        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>

<!--
    Trade debtors
-->

      <xsl:if test='//pt:TradeDebtors[@contextRef=$y2end] or //pt:TradeDebtors[@contextRef=$y1end]'>
        <tr>
          <td>

          </td>
          <td>
Trade debtors
          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TradeDebtors[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TradeDebtors[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TradeDebtors[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//pt:TradeDebtors[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TradeDebtors[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>
<xsl:if test='//pt:TradeDebtors[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TradeDebtors[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      </xsl:if>

<!--
    Called up share capital not paid (Current Asset)
-->

      <xsl:if test='//pt:CalledUpShareCapitalNotPaidCurrentAsset[@contextRef=$y2end] or //pt:CalledUpShareCapitalNotPaidCurrentAsset[@contextRef=$y1end]'>
        <tr>
          <td>

          </td>
          <td>
Called up share capital not paid (Current Asset)
          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:CalledUpShareCapitalNotPaidCurrentAsset[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:CalledUpShareCapitalNotPaidCurrentAsset[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:CalledUpShareCapitalNotPaidCurrentAsset[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//pt:CalledUpShareCapitalNotPaidCurrentAsset[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:CalledUpShareCapitalNotPaidCurrentAsset[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>
<xsl:if test='//pt:CalledUpShareCapitalNotPaidCurrentAsset[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:CalledUpShareCapitalNotPaidCurrentAsset[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      </xsl:if>

<!--
    Prepayments and accrued income
-->

      <xsl:if test='//pt:PrepaymentsAccruedIncomeCurrentAsset[@contextRef=$y2end] or //pt:PrepaymentsAccruedIncomeCurrentAsset[@contextRef=$y1end]'>
        <tr>
          <td>

          </td>
          <td>
Prepayments and accrued income
          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:PrepaymentsAccruedIncomeCurrentAsset[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:PrepaymentsAccruedIncomeCurrentAsset[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:PrepaymentsAccruedIncomeCurrentAsset[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//pt:PrepaymentsAccruedIncomeCurrentAsset[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:PrepaymentsAccruedIncomeCurrentAsset[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>
<xsl:if test='//pt:PrepaymentsAccruedIncomeCurrentAsset[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:PrepaymentsAccruedIncomeCurrentAsset[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      </xsl:if>

<!--
    Other debtors
-->

      <xsl:if test='//pt:OtherDebtors[@contextRef=$y2end] or //pt:OtherDebtors[@contextRef=$y1end]'>
        <tr>
          <td>

          </td>
          <td>
Other debtors
          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:OtherDebtors[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:OtherDebtors[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:OtherDebtors[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//pt:OtherDebtors[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:OtherDebtors[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>
<xsl:if test='//pt:OtherDebtors[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:OtherDebtors[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      </xsl:if>
      <xsl:if test='//pt:Debtors[@contextRef=$y2end] or //pt:Debtors[@contextRef=$y1end]'>
        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:Debtors[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:Debtors[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:Debtors[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td style='border-top: solid thin black'>
<xsl:if test='//pt:Debtors[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:Debtors[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td style='border-top: solid thin black'>
<xsl:if test='//pt:Debtors[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:Debtors[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      </xsl:if>
</xsl:if>
<tr><td colspan="7"><span style="page-break-avoid: end" /></td></tr>
<!-- ~footnote: Debtors -->

<!-- footnote: Creditors: amounts falling due within one year -->
<xsl:if test="$fn_creditors_lt_one_year=1">
  <tr>
    <td><xsl:value-of select="$fnn_creditors_lt_one_year"/></td><td><a name="fn_creditors_lt_one_year"/><h3 class="nospace" style="page-break-avoid: begin">Creditors: amounts falling due within one year</h3></td>
  </tr>
<xsl:if test="//ae:CreditorsDueWithinOneYearFreeTextNote"><tr><td></td><td colspan="4"><xsl:value-of select="//ae:CreditorsDueWithinOneYearFreeTextNote"/></td></tr></xsl:if>
  <xsl:if test="//pt:BankLoansOverdraftsWithinOneYear or //pt:TradeCreditorsWithinOneYear or //pt:AccrualsDeferredIncomeWithinOneYear or //pt:TaxationSocialSecurityDueWithinOneYear or //pt:OtherCreditorsDueWithinOneYear">
  <tr><td></td><td width="50%" colspan="2"></td><th align="right" width="15%"><xsl:value-of select="$namey2" /></th><th align="right" width="15%"><xsl:value-of select="$namey1" /></th><td colspan="3" width="1%"></td></tr>
  <tr style="font-weight: bold">
    <td></td>
    <td></td>
    <td></td>
    <td class="rightalign"><xsl:value-of select="$currencyY2"/></td>
    <td class="rightalign"><xsl:value-of select="$currencyY1"/></td>
  </tr>

<!--
    Bank loans and overdrafts
-->

      <xsl:if test='//pt:BankLoansOverdraftsWithinOneYear[@contextRef=$y2end] or //pt:BankLoansOverdraftsWithinOneYear[@contextRef=$y1end]'>
        <tr>
          <td>

          </td>
          <td>
Bank loans and overdrafts
          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:BankLoansOverdraftsWithinOneYear[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:BankLoansOverdraftsWithinOneYear[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:BankLoansOverdraftsWithinOneYear[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//pt:BankLoansOverdraftsWithinOneYear[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:BankLoansOverdraftsWithinOneYear[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>
<xsl:if test='//pt:BankLoansOverdraftsWithinOneYear[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:BankLoansOverdraftsWithinOneYear[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      </xsl:if>

<!--
    Trade creditors
-->

      <xsl:if test='//pt:TradeCreditorsWithinOneYear[@contextRef=$y2end] or //pt:TradeCreditorsWithinOneYear[@contextRef=$y1end]'>
        <tr>
          <td>

          </td>
          <td>
Trade creditors
          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TradeCreditorsWithinOneYear[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TradeCreditorsWithinOneYear[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TradeCreditorsWithinOneYear[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//pt:TradeCreditorsWithinOneYear[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TradeCreditorsWithinOneYear[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>
<xsl:if test='//pt:TradeCreditorsWithinOneYear[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TradeCreditorsWithinOneYear[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      </xsl:if>

<!--
    Accruals and deferred income
-->

      <xsl:if test='//pt:AccrualsDeferredIncomeWithinOneYear[@contextRef=$y2end] or //pt:AccrualsDeferredIncomeWithinOneYear[@contextRef=$y1end]'>
        <tr>
          <td>

          </td>
          <td>
Accruals and deferred income
          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:AccrualsDeferredIncomeWithinOneYear[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:AccrualsDeferredIncomeWithinOneYear[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:AccrualsDeferredIncomeWithinOneYear[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//pt:AccrualsDeferredIncomeWithinOneYear[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:AccrualsDeferredIncomeWithinOneYear[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>
<xsl:if test='//pt:AccrualsDeferredIncomeWithinOneYear[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:AccrualsDeferredIncomeWithinOneYear[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      </xsl:if>

<!--
    Taxation and Social Security
-->

      <xsl:if test='//pt:TaxationSocialSecurityDueWithinOneYear[@contextRef=$y2end] or //pt:TaxationSocialSecurityDueWithinOneYear[@contextRef=$y1end]'>
        <tr>
          <td>

          </td>
          <td>
Taxation and Social Security
          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TaxationSocialSecurityDueWithinOneYear[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TaxationSocialSecurityDueWithinOneYear[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:TaxationSocialSecurityDueWithinOneYear[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//pt:TaxationSocialSecurityDueWithinOneYear[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TaxationSocialSecurityDueWithinOneYear[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>
<xsl:if test='//pt:TaxationSocialSecurityDueWithinOneYear[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:TaxationSocialSecurityDueWithinOneYear[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      </xsl:if>

<!--
    Other creditors
-->

      <xsl:if test='//pt:OtherCreditorsDueWithinOneYear[@contextRef=$y2end] or //pt:OtherCreditorsDueWithinOneYear[@contextRef=$y1end]'>
        <tr>
          <td>

          </td>
          <td>
Other creditors
          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:OtherCreditorsDueWithinOneYear[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:OtherCreditorsDueWithinOneYear[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:OtherCreditorsDueWithinOneYear[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//pt:OtherCreditorsDueWithinOneYear[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:OtherCreditorsDueWithinOneYear[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>
<xsl:if test='//pt:OtherCreditorsDueWithinOneYear[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:OtherCreditorsDueWithinOneYear[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      </xsl:if>
      <xsl:if test='//pt:CreditorsDueWithinOneYearTotalCurrentLiabilities[@contextRef=$y2end] or //pt:CreditorsDueWithinOneYearTotalCurrentLiabilities[@contextRef=$y1end]'>
        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:CreditorsDueWithinOneYearTotalCurrentLiabilities[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:CreditorsDueWithinOneYearTotalCurrentLiabilities[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:CreditorsDueWithinOneYearTotalCurrentLiabilities[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td style='border-top: solid thin black'>
<xsl:if test='//pt:CreditorsDueWithinOneYearTotalCurrentLiabilities[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:CreditorsDueWithinOneYearTotalCurrentLiabilities[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td style='border-top: solid thin black'>
<xsl:if test='//pt:CreditorsDueWithinOneYearTotalCurrentLiabilities[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:CreditorsDueWithinOneYearTotalCurrentLiabilities[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      </xsl:if>
</xsl:if>
<tr><td colspan="7"><span style="page-break-avoid: end" /></td></tr>
</xsl:if>
<!-- ~footnote: Creditors: amounts falling due within one year -->

<!-- footnote: creditors -->
<!-- section: Creditors due after 12 months -->
<xsl:if test="$fn_creditors_gt_one_year=1">
	<tr>
		<td><xsl:value-of select="$fnn_creditors_gt_one_year"/></td><td colspan="6"><a name="fn_creditors_gt_one_year"/><h3 class="nospace" style="page-break-avoid: begin">Creditors: amounts falling due after more than one year</h3></td>
	</tr>
<!-- section: Total Creditors -->
<xsl:if test="($fn_total_creditors=1) and ($fn_total_creditors_note_display=0)">
  <tr><td></td><td width="50%" colspan="2"></td><th align="right" width="15%"><xsl:value-of select="$namey2" /></th><th align="right" width="15%"><xsl:value-of select="$namey1" /></th><td colspan="3" width="1%"></td></tr>
  <tr style="font-weight: bold">
    <td></td>
    <td></td>
    <td></td>
    <td class="rightalign"><xsl:value-of select="$currencyY2"/></td>
    <td class="rightalign"><xsl:value-of select="$currencyY1"/></td>
  </tr>
      <xsl:if test='//ae:InstalmentDebtsFallingDueAfter5Years[@contextRef=$y2end] or //ae:InstalmentDebtsFallingDueAfter5Years[@contextRef=$y1end]'>
        <tr>
          <td>

          </td>
          <td>
Instalment debts falling due after 5 years
          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //ae:InstalmentDebtsFallingDueAfter5Years[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //ae:InstalmentDebtsFallingDueAfter5Years[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //ae:InstalmentDebtsFallingDueAfter5Years[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//ae:InstalmentDebtsFallingDueAfter5Years[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:InstalmentDebtsFallingDueAfter5Years[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>
<xsl:if test='//ae:InstalmentDebtsFallingDueAfter5Years[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:InstalmentDebtsFallingDueAfter5Years[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      </xsl:if>
      <xsl:if test='//ae:Non-InstalmentDebtsFallingDueAfter5Years[@contextRef=$y2end] or //ae:Non-InstalmentDebtsFallingDueAfter5Years[@contextRef=$y1end]'>
        <tr>
          <td>

          </td>
          <td>
Non-instalment debts falling due after 5 years
          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //ae:Non-InstalmentDebtsFallingDueAfter5Years[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //ae:Non-InstalmentDebtsFallingDueAfter5Years[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //ae:Non-InstalmentDebtsFallingDueAfter5Years[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//ae:Non-InstalmentDebtsFallingDueAfter5Years[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:Non-InstalmentDebtsFallingDueAfter5Years[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>
<xsl:if test='//ae:Non-InstalmentDebtsFallingDueAfter5Years[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:Non-InstalmentDebtsFallingDueAfter5Years[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      </xsl:if>

<!--
    Secured Debts
-->

      <xsl:if test='//ae:SecuredDebts[@contextRef=$y2end] or //ae:SecuredDebts[@contextRef=$y1end]'>
        <tr>
          <td>

          </td>
          <td>
Secured Debts
          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //ae:SecuredDebts[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //ae:SecuredDebts[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //ae:SecuredDebts[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//ae:SecuredDebts[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:SecuredDebts[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>
<xsl:if test='//ae:SecuredDebts[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:SecuredDebts[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      </xsl:if>
<tr><td colspan="7"><span style="page-break-avoid: end" /></td></tr>
</xsl:if>
        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
<!-- ~section: Total Creditors -->
<xsl:if test="//ae:CreditorsDueAfterOneYearFreeTextNote"><tr><td></td><td colspan="4"><xsl:value-of select="//ae:CreditorsDueAfterOneYearFreeTextNote"/></td></tr></xsl:if>
        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
<xsl:if test="//pt:BankLoansOverdraftsAfterOneYear or //pt:ObligationsUnderFinanceLeaseHirePurchaseContractsAfterOneYear or //ae:LoansFromDirectors or //pt:OtherCreditorsAfterOneYear">
<tr style="font-weight: bold">
  <td></td>
  <td></td>
  <td></td>
  <td class="rightalign"><xsl:value-of select="$currencyY2"/></td>
  <td class="rightalign"><xsl:value-of select="$currencyY1"/></td>
</tr>

<!--
    Bank loans and overdrafts
-->

      <xsl:if test='//pt:BankLoansOverdraftsAfterOneYear[@contextRef=$y2end] or //pt:BankLoansOverdraftsAfterOneYear[@contextRef=$y1end]'>
        <tr>
          <td>

          </td>
          <td>
Bank loans and overdrafts
          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:BankLoansOverdraftsAfterOneYear[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:BankLoansOverdraftsAfterOneYear[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:BankLoansOverdraftsAfterOneYear[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//pt:BankLoansOverdraftsAfterOneYear[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:BankLoansOverdraftsAfterOneYear[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>
<xsl:if test='//pt:BankLoansOverdraftsAfterOneYear[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:BankLoansOverdraftsAfterOneYear[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      </xsl:if>

<!--
    Obligations under finance leases
-->

      <xsl:if test='//pt:ObligationsUnderFinanceLeaseHirePurchaseContractsAfterOneYear[@contextRef=$y2end] or //pt:ObligationsUnderFinanceLeaseHirePurchaseContractsAfterOneYear[@contextRef=$y1end]'>
        <tr>
          <td>

          </td>
          <td>
Obligations under finance leases
          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ObligationsUnderFinanceLeaseHirePurchaseContractsAfterOneYear[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ObligationsUnderFinanceLeaseHirePurchaseContractsAfterOneYear[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ObligationsUnderFinanceLeaseHirePurchaseContractsAfterOneYear[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//pt:ObligationsUnderFinanceLeaseHirePurchaseContractsAfterOneYear[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:ObligationsUnderFinanceLeaseHirePurchaseContractsAfterOneYear[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>
<xsl:if test='//pt:ObligationsUnderFinanceLeaseHirePurchaseContractsAfterOneYear[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:ObligationsUnderFinanceLeaseHirePurchaseContractsAfterOneYear[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      </xsl:if>

<!--
    Loans from directors
-->

      <xsl:if test='//ae:LoansFromDirectors[@contextRef=$y2end] or //ae:LoansFromDirectors[@contextRef=$y1end]'>
        <tr>
          <td>

          </td>
          <td>
Loans from directors
          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //ae:LoansFromDirectors[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //ae:LoansFromDirectors[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //ae:LoansFromDirectors[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//ae:LoansFromDirectors[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:LoansFromDirectors[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>
<xsl:if test='//ae:LoansFromDirectors[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:LoansFromDirectors[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      </xsl:if>

<!--
    Other creditors
-->

      <xsl:if test='//pt:OtherCreditorsAfterOneYear[@contextRef=$y2end] or //pt:OtherCreditorsAfterOneYear[@contextRef=$y1end]'>
        <tr>
          <td>

          </td>
          <td>
Other creditors
          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:OtherCreditorsAfterOneYear[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:OtherCreditorsAfterOneYear[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:OtherCreditorsAfterOneYear[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//pt:OtherCreditorsAfterOneYear[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:OtherCreditorsAfterOneYear[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>
<xsl:if test='//pt:OtherCreditorsAfterOneYear[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:OtherCreditorsAfterOneYear[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      </xsl:if>
      <xsl:if test='//pt:CreditorsDueAfterOneYearTotalNoncurrentLiabilities[@contextRef=$y2end] or //pt:CreditorsDueAfterOneYearTotalNoncurrentLiabilities[@contextRef=$y1end]'>
        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:CreditorsDueAfterOneYearTotalNoncurrentLiabilities[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:CreditorsDueAfterOneYearTotalNoncurrentLiabilities[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:CreditorsDueAfterOneYearTotalNoncurrentLiabilities[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td style='border-top: solid thin black'>
<xsl:if test='//pt:CreditorsDueAfterOneYearTotalNoncurrentLiabilities[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:CreditorsDueAfterOneYearTotalNoncurrentLiabilities[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td style='border-top: solid thin black'>
<xsl:if test='//pt:CreditorsDueAfterOneYearTotalNoncurrentLiabilities[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:CreditorsDueAfterOneYearTotalNoncurrentLiabilities[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      </xsl:if>
</xsl:if>
<tr><td colspan="7"><span style="page-break-avoid: end" /></td></tr>
<!-- ~section: Creditors due after 12 months -->

<tr><td colspan="7"><span style="page-break-avoid: end" /></td></tr>
</xsl:if>
<!-- ~footnote: creditors -->

<!-- footnote: total creditors note display -->
<xsl:if test="$fn_total_creditors_note_display=1">
	<tr>
		<td><xsl:value-of select="$fnn_total_creditors_note_display"/></td><td colspan="6"><a name="fn_total_creditors_note_display"/><h3 class="nospace" style="page-break-avoid: begin">Creditors</h3></td>
	</tr>
  <xsl:if test="//ae:InstalmentDebtsFallingDueAfter5Years or //ae:Non-InstalmentDebtsFallingDueAfter5Years or //ae:SecuredDebts">
  <tr><td></td><td width="50%" colspan="2"></td><th align="right" width="15%"><xsl:value-of select="$namey2" /></th><th align="right" width="15%"><xsl:value-of select="$namey1" /></th><td colspan="3" width="1%"></td></tr>
  <tr style="font-weight: bold">
    <td></td>
    <td></td>
    <td></td>
    <td class="rightalign"><xsl:value-of select="$currencyY2"/></td>
    <td class="rightalign"><xsl:value-of select="$currencyY1"/></td>
  </tr>
  </xsl:if>
      <xsl:if test='//ae:InstalmentDebtsFallingDueAfter5Years[@contextRef=$y2end] or //ae:InstalmentDebtsFallingDueAfter5Years[@contextRef=$y1end]'>
        <tr>
          <td>

          </td>
          <td>
Instalment debts falling due after 5 years
          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //ae:InstalmentDebtsFallingDueAfter5Years[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //ae:InstalmentDebtsFallingDueAfter5Years[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //ae:InstalmentDebtsFallingDueAfter5Years[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//ae:InstalmentDebtsFallingDueAfter5Years[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:InstalmentDebtsFallingDueAfter5Years[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>
<xsl:if test='//ae:InstalmentDebtsFallingDueAfter5Years[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:InstalmentDebtsFallingDueAfter5Years[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      </xsl:if>
      <xsl:if test='//ae:Non-InstalmentDebtsFallingDueAfter5Years[@contextRef=$y2end] or //ae:Non-InstalmentDebtsFallingDueAfter5Years[@contextRef=$y1end]'>
        <tr>
          <td>

          </td>
          <td>
Non-instalment debts falling due after 5 years
          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //ae:Non-InstalmentDebtsFallingDueAfter5Years[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //ae:Non-InstalmentDebtsFallingDueAfter5Years[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //ae:Non-InstalmentDebtsFallingDueAfter5Years[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//ae:Non-InstalmentDebtsFallingDueAfter5Years[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:Non-InstalmentDebtsFallingDueAfter5Years[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>
<xsl:if test='//ae:Non-InstalmentDebtsFallingDueAfter5Years[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:Non-InstalmentDebtsFallingDueAfter5Years[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      </xsl:if>

<!--
    Secured Debts
-->

      <xsl:if test='//ae:SecuredDebts[@contextRef=$y2end] or //ae:SecuredDebts[@contextRef=$y1end]'>
        <tr>
          <td>

          </td>
          <td>
Secured Debts
          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //ae:SecuredDebts[@contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //ae:SecuredDebts[@contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //ae:SecuredDebts[@contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//ae:SecuredDebts[@contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:SecuredDebts[@contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>
<xsl:if test='//ae:SecuredDebts[@contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:SecuredDebts[@contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      </xsl:if>
<tr><td colspan="7"><span style="page-break-avoid: end" /></td></tr>
        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
<xsl:if test="//ae:AggregateCreditorsFreeTextNote"><tr><td></td><td colspan="4"><xsl:value-of select="//ae:AggregateCreditorsFreeTextNote"/></td></tr></xsl:if>
</xsl:if>
<!-- ~footnote: total creditors note display -->

<!-- footnote: Share Capital -->
<xsl:if test="$fn_capital=1">
	<tr>
		<td><xsl:value-of select="$fnn_capital"/></td><td><a name="fn_capital"/><h3 class="nospace" style="page-break-avoid: begin">Share capital</h3></td>
	</tr>
	<tr><td></td><td width="50%" colspan="2"></td><th align="right" width="15%"><xsl:value-of select="$namey2" /></th><th align="right" width="15%"><xsl:value-of select="$namey1" /></th><td colspan="3" width="1%"></td></tr>
<tr style="font-weight: bold">
  <td></td>
  <td></td>
  <td></td>
  <td class="rightalign"><xsl:value-of select="$currencyY2"/></td>
  <td class="rightalign"><xsl:value-of select="$currencyY1"/></td>
</tr>
<xsl:if test="//pt:EquityAuthorisedDetails">
        <tr>
          <td>

          </td>
          <td style='font-weight:bold'>
Authorised share capital:
          </td>
          <td style='font-weight:bold'>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
</xsl:if>

<xsl:for-each select="//pt:EquityAuthorisedDetails">
  <xsl:variable name="t" select="." />
  <xsl:variable name="numy2" select="./pt:NumberOrdinarySharesAuthorised[@contextRef=$y2end]"/>
  <xsl:variable name="numy1" select="./pt:NumberOrdinarySharesAuthorised[@contextRef=$y1end]"/>
      <xsl:if test='//pt:ValueOrdinarySharesAuthorised[..=$t and @contextRef=$y2end] or //pt:ValueOrdinarySharesAuthorised[..=$t and @contextRef=$y1end]'>
        <tr>
          <td>

          </td>
          <td>
<xsl:value-of select="concat($numy2,' ')"/> <xsl:value-of select=".//pt:TypeOrdinaryShare[@contextRef=$y2]"/> of <xsl:value-of select="concat(normalize-space($currencyY2), ./pt:ParValueOrdinaryShare[@contextRef=$y2])"/> each
          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ValueOrdinarySharesAuthorised[..=$t and @contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ValueOrdinarySharesAuthorised[..=$t and @contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ValueOrdinarySharesAuthorised[..=$t and @contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//pt:ValueOrdinarySharesAuthorised[..=$t and @contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:ValueOrdinarySharesAuthorised[..=$t and @contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>
<xsl:if test='//pt:ValueOrdinarySharesAuthorised[..=$t and @contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:ValueOrdinarySharesAuthorised[..=$t and @contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      </xsl:if>
</xsl:for-each>

<xsl:for-each select="//pt:NonEquityAuthorisedDetails">
  <xsl:variable name="t" select="." />
  <xsl:variable name="numy2" select="./pt:NumberNonEquitySharesAuthorised[@contextRef=$y2end]"/>
  <xsl:variable name="numy1" select="./pt:NumberNonEquitySharesAuthorised[@contextRef=$y1end]"/>
      <xsl:if test='//pt:ValueNonEquitySharesAuthorised[..=$t and @contextRef=$y2end] or //pt:ValueNonEquitySharesAuthorised[..=$t and @contextRef=$y1end]'>
        <tr>
          <td>

          </td>
          <td>
<xsl:value-of select="concat($numy2,' ')"/> <xsl:value-of select=".//pt:TypeNonEquityShare[@contextRef=$y2]"/> of <xsl:value-of select="concat(normalize-space($currencyY2), ./pt:ParValueNonEquityShare[@contextRef=$y2])"/> each
          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ValueNonEquitySharesAuthorised[..=$t and @contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ValueNonEquitySharesAuthorised[..=$t and @contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ValueNonEquitySharesAuthorised[..=$t and @contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//pt:ValueNonEquitySharesAuthorised[..=$t and @contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:ValueNonEquitySharesAuthorised[..=$t and @contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>
<xsl:if test='//pt:ValueNonEquitySharesAuthorised[..=$t and @contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:ValueNonEquitySharesAuthorised[..=$t and @contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      </xsl:if>
</xsl:for-each>
        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>

        <tr>
          <td>

          </td>
          <td style='font-weight:bold'>
Allotted, called up and fully paid:
          </td>
          <td style='font-weight:bold'>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
<xsl:for-each select="//pt:DetailsOrdinarySharesAllotted">
  <xsl:variable name="t" select="." />
  <xsl:variable name="numy2" select="./pt:NumberOrdinarySharesAllotted[@contextRef=$y2end]"/>
  <xsl:variable name="numy1" select="./pt:NumberOrdinarySharesAllotted[@contextRef=$y1end]"/>
      <xsl:if test='//pt:ValueOrdinarySharesAllotted[..=$t and @contextRef=$y2end] or //pt:ValueOrdinarySharesAllotted[..=$t and @contextRef=$y1end]'>
        <tr>
          <td>

          </td>
          <td>
<xsl:value-of select="concat($numy2,' ')"/> <xsl:value-of select=".//pt:TypeOrdinaryShare[@contextRef=$y2]"/> of <xsl:value-of select="concat(normalize-space($currencyY2), ./pt:ParValueOrdinaryShare[@contextRef=$y2])"/> each
          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ValueOrdinarySharesAllotted[..=$t and @contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ValueOrdinarySharesAllotted[..=$t and @contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ValueOrdinarySharesAllotted[..=$t and @contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//pt:ValueOrdinarySharesAllotted[..=$t and @contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:ValueOrdinarySharesAllotted[..=$t and @contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>
<xsl:if test='//pt:ValueOrdinarySharesAllotted[..=$t and @contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:ValueOrdinarySharesAllotted[..=$t and @contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      </xsl:if>
</xsl:for-each>

<xsl:for-each select="//pt:NonEquityAllottedDetails">
  <xsl:variable name="t" select="." />
  <xsl:variable name="numy2" select="./pt:NumberNonEquitySharesAllotted[@contextRef=$y2end]"/>
  <xsl:variable name="numy1" select="./pt:NumberNonEquitySharesAllotted[@contextRef=$y1end]"/>
      <xsl:if test='//pt:ValueNonEquitySharesAllotted[..=$t and @contextRef=$y2end] or //pt:ValueNonEquitySharesAllotted[..=$t and @contextRef=$y1end]'>
        <tr>
          <td>

          </td>
          <td>
<xsl:value-of select="concat($numy2,' ')"/><xsl:value-of select="./pt:TypeNonEquityShare[@contextRef=$y2]"/> of <xsl:value-of select="concat(normalize-space($currencyY2), ./pt:ParValueNonEquityShare[@contextRef=$y2])"/> each
          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ValueNonEquitySharesAllotted[..=$t and @contextRef=$y2end]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ValueNonEquitySharesAllotted[..=$t and @contextRef=$y1end]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ValueNonEquitySharesAllotted[..=$t and @contextRef=$y2end]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//pt:ValueNonEquitySharesAllotted[..=$t and @contextRef=$y2end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:ValueNonEquitySharesAllotted[..=$t and @contextRef=$y2end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>
<xsl:if test='//pt:ValueNonEquitySharesAllotted[..=$t and @contextRef=$y1end]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:ValueNonEquitySharesAllotted[..=$t and @contextRef=$y1end]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      </xsl:if>
</xsl:for-each>

        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
<xsl:if test="//pt:OrdinarySharesIssuedInPeriod-Details">
        <tr>
          <td>

          </td>
          <td style='font-weight:bold'>
Ordinary shares issued in the year:
          </td>
          <td style='font-weight:bold'>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
</xsl:if>
<xsl:for-each select="//pt:OrdinarySharesIssuedInPeriod-Details">
  <xsl:variable name="t" select="." />
<tr><td></td><td colspan="7">
<xsl:value-of select="pt:TotalNumberSharesIssued[@contextRef=$y2]"/><xsl:value-of select="' '"/><xsl:value-of select=".//pt:TypeOrdinaryShare[@contextRef=$y2]"/> of <xsl:value-of select="normalize-space($currencyY2)" /><xsl:value-of select="./pt:ParValueOrdinaryShare[@contextRef=$y2]" /> each were issued in the year with a nominal value
of <xsl:value-of select="normalize-space($currencyY2)" /><xsl:value-of select="pt:TotalNominalValue[@contextRef=$y2]"/>,
for a consideration of <xsl:value-of select="normalize-space($currencyY2)" /><xsl:value-of select="pt:TotalConsideration[@contextRef=$y2]"/>
</td></tr>
</xsl:for-each>

        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
<xsl:if test="//pt:NonEquitySharesDetailsAllotmentsDuringPeriod">
        <tr>
          <td>

          </td>
          <td style='font-weight:bold'>
Non-equity shares issued in the year:
          </td>
          <td style='font-weight:bold'>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
</xsl:if>
<xsl:for-each select="//pt:NonEquitySharesDetailsAllotmentsDuringPeriod">
  <xsl:variable name="t" select="." />

<tr><td></td><td colspan="7">
<xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:NumberNonEquitySharesAllottedInPeriod[..=$t and @contextRef=$y2]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
<xsl:text> </xsl:text>
<xsl:value-of select="./pt:TypeNonEquityShare[@contextRef=$y2]"/> of <xsl:value-of select="concat(normalize-space($currencyY2), ./pt:ParValueNonEquityShare[@contextRef=$y2])" /> each were issued in the year with a nominal value
of <xsl:value-of select="concat(normalize-space($currencyY2), .//pt:ValueNonEquitySharesAllottedInPeriod[@contextRef=$y2])" />,
for a consideration of <xsl:value-of select="concat(normalize-space($currencyY2), .//pt:ConsiderationForNonEquitySharesAllottedInPeriod[@contextRef=$y2])" />
</td></tr>

</xsl:for-each>
        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
<xsl:if test="//ae:RedeemableSharesFreeTextNote">
        <tr>
          <td>

          </td>
          <td style='font-weight:bold'>
Redeemable shares:
          </td>
          <td style='font-weight:bold'>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
<xsl:for-each select="//ae:RedeemableSharesFreeTextNote">
<tr><td></td><td colspan="7"><xsl:value-of select="."/></td></tr>
</xsl:for-each>
</xsl:if>

        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
<xsl:if test="//ae:ShareCapitalFreeTextNote">
<xsl:if test="//ae:ShareCapitalFreeTextNote"><tr><td></td><td colspan="7"><xsl:value-of select="//ae:ShareCapitalFreeTextNote"/></td></tr></xsl:if>
</xsl:if>
</xsl:if>
<tr><td colspan="7"><span style="page-break-avoid: end" /></td></tr>
<!-- ~footnote: Share Capital -->

<!-- footnote: Profit and Loss Account -->
<xsl:if test="$fn_pl = 1">
<tr><td><xsl:value-of select="$fnn_pl" /></td><td><a name="fn_pl"/><h3 class="nospace" style="page-break-avoid: begin">Profit and Loss Account</h3></td></tr>
		<tr><td></td><td width="50%" colspan="2"></td><th align="right" width="15%"><xsl:value-of select="$namey2" /></th><th align="right" width="15%"><xsl:value-of select="$namey1" /></th><td colspan="3" width="1%"></td></tr>
<tr style="font-weight: bold">
  <td></td>
  <td></td>
  <td></td>
  <td class="rightalign"><xsl:value-of select="$currencyY2"/></td>
  <td class="rightalign"><xsl:value-of select="$currencyY1"/></td>
</tr>


<!--
    Balance brought forward
-->

      <xsl:if test='//pt:ProfitLossAccountReserve[@contextRef=$y1end and @unitRef=//xbrli:unit[.//xbrli:measure=$currencyUnitY2]/@id] or //pt:ProfitLossAccountReserve[@contextRef=$y1start]'>
        <tr>
          <td>

          </td>
          <td>
Balance brought forward
          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ProfitLossAccountReserve[@contextRef=$y1end and @unitRef=//xbrli:unit[.//xbrli:measure=$currencyUnitY2]/@id]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ProfitLossAccountReserve[@contextRef=$y1start]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:ProfitLossAccountReserve[@contextRef=$y1end and @unitRef=//xbrli:unit[.//xbrli:measure=$currencyUnitY2]/@id]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//pt:ProfitLossAccountReserve[@contextRef=$y1end and @unitRef=//xbrli:unit[.//xbrli:measure=$currencyUnitY2]/@id]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:ProfitLossAccountReserve[@contextRef=$y1end and @unitRef=//xbrli:unit[.//xbrli:measure=$currencyUnitY2]/@id]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>
<xsl:if test='//pt:ProfitLossAccountReserve[@contextRef=$y1start]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:ProfitLossAccountReserve[@contextRef=$y1start]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      </xsl:if>

<!--
    Profit or Loss for the current year
-->

      <xsl:if test='//pt:RetainedProfitLossForFinancialYearTransferredToReserves[@contextRef=$y2] or //pt:RetainedProfitLossForFinancialYearTransferredToReserves[@contextRef=$y1]'>
        <tr>
          <td>

          </td>
          <td>
Profit or Loss for the current year
          </td>
          <td>
<xsl:if test='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:RetainedProfitLossForFinancialYearTransferredToReserves[@contextRef=$y2]/@id)] or //link:loc[@xlink:href=concat(&quot;#&quot;, //pt:RetainedProfitLossForFinancialYearTransferredToReserves[@contextRef=$y1]/@id)]'><xsl:call-template name='footnoteLinkFor'><xsl:with-param name='fn_adjust' select='$fnn_footnotes - 1'/><xsl:with-param name='loc' select='//link:loc[@xlink:href=concat(&quot;#&quot;, //pt:RetainedProfitLossForFinancialYearTransferredToReserves[@contextRef=$y2]/@id)]'/></xsl:call-template></xsl:if>
          </td>
          <td>
<xsl:if test='//pt:RetainedProfitLossForFinancialYearTransferredToReserves[@contextRef=$y2]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:RetainedProfitLossForFinancialYearTransferredToReserves[@contextRef=$y2]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>
<xsl:if test='//pt:RetainedProfitLossForFinancialYearTransferredToReserves[@contextRef=$y1]/@unitRef'><xsl:attribute name='class'>rightalign</xsl:attribute></xsl:if><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:RetainedProfitLossForFinancialYearTransferredToReserves[@contextRef=$y1]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template>
          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
      </xsl:if>

<tr><td></td><td>Balance carried forward</td><td></td><td align="right" style="border-top:thin solid black"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:ProfitLossAccountReserve[@contextRef=//xbrli:context[.//xbrli:instant = //xbrli:context[@id=$y2]//xbrli:endDate]/@id]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></td><td align="right" style="border-top:thin solid black"><span style="page-break-avoid: end"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:ProfitLossAccountReserve[@contextRef=//xbrli:context[.//xbrli:instant = //xbrli:context[@id=$y1]//xbrli:endDate]/@id and @unitRef=//xbrli:unit[.//xbrli:measure=$currencyUnitY1]/@id]' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template></span></td></tr>
<tr><td colspan="7"><span style="page-break-avoid: end" /></td></tr>
<!-- ~footnote: Profit and Loss Account -->
</xsl:if>

<!-- Transactions with Directors -->
<xsl:if test="$fn_twd=1">
	<tr>
		<td><xsl:value-of select="$fnn_twd"/></td><td><h3 class="nospace" style="page-break-avoid: begin">Transactions with directors</h3></td>
	</tr>
  <xsl:if test="//ae:TransactionsWithDirectors">
	<tr>
		<td></td>
		<td colspan="3">
			<xsl:call-template name='displayItem'><xsl:with-param name='item' select='//ae:TransactionsWithDirectors' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template><span style="page-break-avoid: end" />
		</td>
	</tr>
  </xsl:if>
  <xsl:if test="//ae:TransactionWithDirector">
  <tr><td></td><td style="font-weight: bold">Name</td><td align="right" style="font-weight: bold">Liability at start of period</td><td align="right" style="font-weight: bold">Max. liability during the year</td><td align="right" style="font-weight: bold">Liability at end of period</td></tr>
  <xsl:for-each select="//ae:TransactionWithDirector">
        <tr>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
          <td>

          </td>
        </tr>
  <tr>
  <td></td>
    <td><xsl:value-of select="./ae:DirectorsName"/></td><td align="right"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='./ae:DirectorsLiability[@contextRef=$y1end]'/></xsl:call-template></td><td align="right"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='./ae:DirectorsMaximumLiability'/></xsl:call-template></td><td align="right"><xsl:call-template name='displayItem'><xsl:with-param name='item' select='./ae:DirectorsLiability[@contextRef=$y2end]'/></xsl:call-template></td>
  </tr>
  </xsl:for-each>
  </xsl:if>
</xsl:if>
<!-- ~Transactions with Directors -->

<!-- Related Party Disclosures -->
<xsl:if test="$fn_rpd=1">
	<tr>
		<td><xsl:value-of select="$fnn_rpd"/></td><td><h3 class="nospace" style="page-break-avoid: begin">Related party disclosures</h3></td>
	</tr>
	<tr>
		<td></td>
		<td colspan="3">
			<xsl:call-template name='displayItem'><xsl:with-param name='item' select='//pt:StatementOnRelatedPartyDisclosure' /><xsl:with-param name='flipSign'></xsl:with-param></xsl:call-template><span style="page-break-avoid: end" />
		</td>
	</tr>
</xsl:if>
<!-- ~Related Party Disclosures -->

<!-- footnote: (show other footnotes embedded in instance -->
<xsl:call-template name="showFootnotes">
<xsl:with-param name="fn_adjust" select="$fnn_footnotes - 1"/>
</xsl:call-template>
<!-- ~footnote: (show other footnotes embedded in instance -->


<!-- Free text note -->
<xsl:for-each select="//ae:AdditionalNote">
<tr><td><xsl:value-of select="$fnn_freenotes + (position() - 1)" /></td><td><h3 class="nospace" style="page-break-avoid: begin"><xsl:value-of select="ae:TitleAdditionalNote"/></h3></td></tr>
<tr><td></td><td colspan="4"><span style="page-break-avoid: end"><xsl:value-of select="ae:ContentAdditionalNote"/></span></td><td colspan="3" width="1%"></td></tr>
</xsl:for-each>
<!-- ~Free text note -->
<tr><td colspan="7"><br style="page-break-after: always" /></td></tr>
<!-- ae:RejectionReferenceCode is not displayed -->

    </table>

  <xsl:if test="//gc:BalanceSheetDate != //xbrli:context[@id=$y2end]//xbrli:instant">
    <h1 style="color: red">Warnings</h1>
    <xsl:message>BalanceSheetDate does not equal the Y2 context end date</xsl:message>
    <p style="color: red">BalanceSheetDate does not equal the Y2 context end date (BalanceSheetDate = <xsl:call-template name="formatDate"><xsl:with-param name="date" select="//gc:BalanceSheetDate" /></xsl:call-template>, Y2 context end = <xsl:call-template name="formatDate"><xsl:with-param name="date" select="//xbrli:context[@id=$y2end]//xbrli:instant" /></xsl:call-template>)</p>
  </xsl:if>

    <xsl:call-template name="printUnusedElements" />
    <xsl:call-template name="printDuplicateElements" />

  </body>
</html>

</xsl:template>

  <!-- Display numbered footnotes.
       @param fn_adjust Numbering starts at this value +1
  -->
  <xsl:template name='showFootnotes'>
    <xsl:param name='fn_adjust' />
    <xsl:for-each select='//link:footnote'>
      <tr><td>
          <xsl:value-of select='position() + $fn_adjust' />
        </td>
        <td colspan="5">
          <h3 class="nospace" style="page-break-avoid: begin"><xsl:value-of select='../@xlink:title' /></h3>
        </td>
        <td>
          <a><xsl:attribute name='name'><xsl:value-of select='@xlink:label' /></xsl:attribute></a>
        </td>
      </tr>
      <tr>
      <td></td>
      <td>
          <xsl:copy-of select='./node()' /><span style="page-break-avoid: end" />
        </td>
      </tr>
    </xsl:for-each>
  </xsl:template>

  <!-- Displays an XBRL item value.
       @param item Item whose value to display
       @param flipSign If this is (, negate the value
  -->
  <xsl:template name="displayItem">
    <xsl:param name="item" />
    <xsl:param name="flipSign" />
    <xsl:for-each select="$item">
      <xsl:choose>
        <!-- If it contains HTML, display as-is -->
        <xsl:when test="./html:*"><xsl:copy-of select="." /></xsl:when>
        <!-- If there is no unitRef, this must be a string item -->
        <xsl:when test="not(./@unitRef)"><xsl:value-of select="." /></xsl:when>
        <!-- If it doesn't look like a number, treat it as a string -->
        <xsl:when test="string(number(.)) = 'NaN'"><xsl:value-of select="." /></xsl:when>
        <!-- If we were expecting a bracketed number and the XBRL contains a
             negative number, print the number without brackets -->
        <xsl:when test="$flipSign='(' and . &lt;= 0"><xsl:number value="0-." grouping-separator="," grouping-size="3" /></xsl:when>
        <!-- If we were expecting a positive number, and the XBRL contains a
             negative one, print the number with brackets -->
        <xsl:when test=". &lt; 0">(<xsl:number value="0-." grouping-separator="," grouping-size="3" />)</xsl:when>
        <!-- If we were expecting a bracketed number, and the XBRL contains a
             positive one, print the number with brackets -->
        <xsl:when test="$flipSign='('">(<xsl:number value="." grouping-separator="," grouping-size="3" />)</xsl:when>
        <!-- Otherwise is must be a positive number where we expect a positive
             number, so print as is -->
        <xsl:otherwise><xsl:number value="number(.)" grouping-separator="," grouping-size="3"/></xsl:otherwise>
      </xsl:choose>
    </xsl:for-each>
  </xsl:template>

<!-- Displays a footnote.
     @param fn_adjust Added on to the footnote number to adjust for fixed footnotes
     @param loc The link:loc tag for this footnote
-->
  <xsl:template name='footnoteLinkFor'>
    <xsl:param name='extra_fn' />
    <xsl:param name='fn_adjust' />
    <xsl:param name='loc' />
    <xsl:param name='footnoteNum' select='0' />
    <xsl:variable name='footnoteName' select='
  //link:footnoteArc[
    @xlink:from = $loc/@xlink:label
  ]/@xlink:to
  ' />
    <xsl:variable name="first_fn" select="//link:footnote[@xlink:label=$footnoteName and (position() = 1)]"/>
    <xsl:for-each select='//link:footnote'>
      <xsl:if test='@xlink:label=$footnoteName'>
        <xsl:if test="not(. | $first_fn) or ($extra_fn and (. | $first_fn))">, </xsl:if>
        <xsl:element name='a'>
          <xsl:attribute name='href'>#<xsl:value-of select='$footnoteName' /></xsl:attribute>
          <xsl:attribute name='class'>footnoteLink</xsl:attribute>
          <xsl:value-of select='position()+$fn_adjust' />
        </xsl:element>
      </xsl:if>
    </xsl:for-each>
  </xsl:template>

<!-- Determine which contexts exist in the instance.
-->
  <xsl:template name="guessContexts">
		<!-- determine number of periods in the instance by counting pt:NetAssetsLiabilitiesIncludingPensionAssetLiability -->
		<xsl:choose>
			<xsl:when test="count(//pt:NetAssetsLiabilitiesIncludingPensionAssetLiability)=1">
				<!-- one period -->

    				<xsl:if test="count(//xbrli:unit[not(.//xbrli:measure=preceding::xbrli:unit//xbrli:measure) and @id=//*/@unitRef and starts-with(.//xbrli:measure, 'iso4217')]) != 1">
					<xsl:message terminate="yes">
						There must be one currency used.
					</xsl:message>
				</xsl:if>

				<xsl:variable name="numDurationContexts" select="count(//xbrli:context[.//xbrli:startDate and //*/@contextRef=./@id])" />
				<xsl:if test="$numDurationContexts &gt; 1">
					<xsl:message terminate="yes">
					There must be zero or one duration contexts (found <xsl:value-of select="$numDurationContexts"/>)
					</xsl:message>
				</xsl:if>

				<xsl:if test="$numDurationContexts = 1">
					<xsl:for-each select="//xbrli:context[xbrli:period/xbrli:instant and @id = //*/@contextRef]">
				      <xsl:variable name="c1" select="." />
				      <xsl:variable name="c1pp">
							<xsl:call-template name="datePlusOne">
								<xsl:with-param name="date" select="translate($c1/xbrli:period/xbrli:instant,'-T:','')" />
							</xsl:call-template>
				      </xsl:variable>
						<xsl:if test="not(//xbrli:context[xbrli:period/xbrli:startDate and translate(xbrli:period/xbrli:startDate,'-T:','') &lt;= $c1pp and translate(xbrli:period/xbrli:endDate,'-T:','') &gt;= translate($c1/xbrli:period/xbrli:instant,'-T:','')])">
							<xsl:message terminate="yes">
		Could not match up the instant context <xsl:value-of select="@id" />
		to a suitable period context.
				        </xsl:message>
						</xsl:if>
					</xsl:for-each>
				</xsl:if>

				<!-- take y2end from NetAssets context -->
 				<xsl:variable name="y2end" select="//pt:NetAssetsLiabilitiesIncludingPensionAssetLiability/@contextRef" />
				<!-- the remaining instant context must be the start of the period -->
				<xsl:variable name="y1end" select="//xbrli:context/@id[(./xbrli:period/xbrli:instant) and (@id != $y2end)]" />

				<!-- take y2 from the only duration context -->
				<xsl:variable name="y2" select="//xbrli:context/@id[./xbrli:period/xbrli:startDate]" />

				<xsl:call-template name="main">
					<xsl:with-param name="y2" select="//xbrli:context[(//*/@contextRef = @id) and .//xbrli:startDate]/@id" />
					<xsl:with-param name="y2start" select="/.." />
					<xsl:with-param name="y2end" select="//pt:NetAssetsLiabilitiesIncludingPensionAssetLiability/@contextRef" />
					<xsl:with-param name="y2currency" select="//xbrli:unit[@id=//pt:NetAssetsLiabilitiesIncludingPensionAssetLiability/@unitRef]/xbrli:measure" />
					<xsl:with-param name="namey2"><xsl:value-of select="substring(normalize-space(//xbrli:context[@id=$y2end]//xbrli:instant),1,4)" /></xsl:with-param>

					<xsl:with-param name="y1" select="/.." />
					<xsl:with-param name="y1start" select="/.." />
					<xsl:with-param name="y1end" select="/.." />
					<xsl:with-param name="y1currency" select="/.." />
					<xsl:with-param name="namey1" select="/.." />
				</xsl:call-template>
			</xsl:when>

			<xsl:when test="count(//pt:NetAssetsLiabilitiesIncludingPensionAssetLiability)=2">
				<!-- two of NetAssets -->

				<!-- The ID of the first NetAssets context -->
				<xsl:variable name="cr1id"><xsl:for-each select="//xbrli:context[@id = //pt:NetAssetsLiabilitiesIncludingPensionAssetLiability/@contextRef]"><xsl:sort select=".//xbrli:instant" /><xsl:if test="position()=1"><xsl:value-of select="@id" /></xsl:if></xsl:for-each></xsl:variable>

				<!-- The contexts used by the two periods NetAssets -->
				<xsl:variable name="cr1" select="//xbrli:context[@id=$cr1id]" />
				<xsl:variable name="cr2" select="//xbrli:context[@id=//pt:NetAssetsLiabilitiesIncludingPensionAssetLiability/@contextRef and @id!=$cr1id]" />

				<!-- Check that there aren't too many currencies -->
  				<xsl:if test="count(//xbrli:unit[not(.//xbrli:measure=preceding::xbrli:unit//xbrli:measure) and @id=//*/@unitRef and starts-with(.//xbrli:measure, 'iso4217')]) > 2">
					<xsl:message terminate="yes">
					There must be one or two currencies used.
					</xsl:message>
				</xsl:if>

				<!-- Build up a colon-seperated list of context IDs in period 2,
						whilst checking that there are no instants after period 2 -->
				<xsl:variable name="y2ids">
				<xsl:for-each select="//xbrli:context[@id = //*/@contextRef]">
					<xsl:choose>
						<xsl:when test=".//xbrli:instant">
							<xsl:if test="translate(.//xbrli:instant,'-T:','') &gt; translate($cr2//xbrli:instant,'-T:','')">
								<xsl:message terminate="yes">
All instant contexts must be before or at the end of period 2.
								</xsl:message>
							</xsl:if>
							<xsl:if test="translate(.//xbrli:instant,'-T:','') &gt;= translate($cr1//xbrli:instant,'-T:','')">
								:<xsl:value-of select="@id" />:
							</xsl:if>
						</xsl:when>
						<xsl:when test=".//xbrli:endDate">
							<xsl:if test="translate(.//xbrli:endDate,'-T:','') &gt; translate($cr2//xbrli:instant,'-T:','')">
								<xsl:message terminate="yes">
All duration contexts must end before or at the end of period 2.
								</xsl:message>
							</xsl:if>
							<xsl:if test="translate(.//xbrli:startDate,'-T:','') &gt; translate($cr1//xbrli:instant,'-T:','')">
								:<xsl:value-of select="@id" />:
							</xsl:if>
						</xsl:when>
						<xsl:otherwise>
Cannot handle context <xsl:value-of select="./@id" />.
						</xsl:otherwise>
					</xsl:choose>
				</xsl:for-each>
				</xsl:variable>

				<!-- Get the colon-seperated list of context IDs in period 1 -->
				<xsl:variable name="y1ids">
				<xsl:for-each select="//xbrli:context[@id = //*/@contextRef]">
					<xsl:choose>
						<xsl:when test=".//xbrli:instant">
							<xsl:if test="translate(.//xbrli:instant,'-T:','') &lt;= translate($cr1//xbrli:instant,'-T:','')">
								:<xsl:value-of select="@id" />:
							</xsl:if>
						</xsl:when>
						<xsl:when test=".//xbrli:endDate">
							<xsl:if test="translate(.//xbrli:startDate,'-T:','') &lt;= translate($cr1//xbrli:instant,'-T:','')">
								:<xsl:value-of select="@id" />:
							</xsl:if>
						</xsl:when>
						<xsl:otherwise>
Cannot handle context <xsl:value-of select="./@id" />.
						</xsl:otherwise>
					</xsl:choose>
				</xsl:for-each>
				</xsl:variable>

				<!-- Turn the lists above into a nodeset of IDs -->
				<xsl:variable name="y2" select="//xbrli:context[contains($y2ids, concat(':',@id,':'))]/@id" />
				<xsl:variable name="y1" select="//xbrli:context[contains($y1ids, concat(':',@id,':'))]/@id" />

				<!-- Pick out the start and end contexts based on the context lists
					and the contexts used on NetAssets -->
				<xsl:variable name="y1end" select="//xbrli:context[@id=$y1 and .//xbrli:instant=$cr1//xbrli:instant]/@id" />
				<xsl:variable name="y2end" select="//xbrli:context[@id=$y2 and .//xbrli:instant=$cr2//xbrli:instant]/@id" />
				<xsl:variable name="y1start" select="//xbrli:context[@id=$y1 and .//xbrli:instant and not (.//xbrli:instant=$cr1//xbrli:instant)]/@id" />
				<xsl:variable name="y2start" select="//xbrli:context[@id=$y2 and .//xbrli:instant and not (.//xbrli:instant=$cr2//xbrli:instant)]/@id" />

        <!--Code added to display full date if the current period year and previous period year are the same-->
          <xsl:variable name='y1Year' select='substring(normalize-space($cr1//xbrli:instant),1,4)'></xsl:variable>
          <xsl:variable name='y2Year' select='substring(normalize-space($cr2//xbrli:instant),1,4)'></xsl:variable>
          <xsl:variable name='y2Month' select='substring(normalize-space($cr2//xbrli:instant),6,2)'></xsl:variable>
          <xsl:variable name='y2Day' select='substring(normalize-space($cr2//xbrli:instant),9,2)'></xsl:variable>
          <xsl:variable name='y2Full'>
            <xsl:value-of select="concat($y2Day,'/')"></xsl:value-of>
            <xsl:value-of select="concat($y2Month,'/')"></xsl:value-of>
            <xsl:value-of select='$y2Year'></xsl:value-of>
          </xsl:variable>
          <xsl:variable name='ColumnHeading2'>
            <xsl:choose>
              <xsl:when test='$y2Year = $y1Year'>
                <xsl:value-of select='$y2Full'></xsl:value-of>
              </xsl:when>
              <xsl:otherwise>
                <xsl:value-of select='$y2Year'></xsl:value-of>
              </xsl:otherwise>
            </xsl:choose>
          </xsl:variable>
          <xsl:variable name='y1Month' select='substring(normalize-space($cr1//xbrli:instant),6,2)'></xsl:variable>
          <xsl:variable name='y1Day' select='substring(normalize-space($cr1//xbrli:instant),9,2)'></xsl:variable>
          <xsl:variable name='y1Full'>
            <xsl:value-of select="concat($y1Day,'/')"></xsl:value-of>
            <xsl:value-of select="concat($y1Month,'/')"></xsl:value-of>
            <xsl:value-of select='$y1Year'></xsl:value-of>
          </xsl:variable>
          <xsl:variable name='ColumnHeading1'>
            <xsl:choose>
              <xsl:when test='$y2Year = $y1Year'>
                <xsl:value-of select='$y1Full'></xsl:value-of>
              </xsl:when>
              <xsl:otherwise>
                <xsl:value-of select='$y1Year'></xsl:value-of>
              </xsl:otherwise>
            </xsl:choose>
          </xsl:variable>
        <!-- End of Code added-->

				
				<!-- Call main using this information. The currency per period
					is that that NetAssets is quoted in -->
				<xsl:call-template name="main">
					<xsl:with-param name="y2" select="//xbrli:context[@id=$y2 and .//xbrli:startDate]/@id" />
					<xsl:with-param name="y1" select="//xbrli:context[@id=$y1 and .//xbrli:startDate]/@id" />
					<xsl:with-param name="y2start" select="$y2start" />
					<xsl:with-param name="y1start" select="$y1start" />
					<xsl:with-param name="y2end" select="$y2end" />
					<xsl:with-param name="y1end" select="$y1end" />
<!--					<xsl:with-param name="namey2" select="substring(normalize-space($cr2//xbrli:instant),1,4)" />
					<xsl:with-param name="namey1" select="substring(normalize-space($cr1//xbrli:instant),1,4)" /> -->

          <!-- Added by CH -->
          <xsl:with-param name='namey2' select='$ColumnHeading2'></xsl:with-param>
          <xsl:with-param name='namey1' select='$ColumnHeading1'></xsl:with-param>
          <!-- ~Added by CH -->

					<xsl:with-param name="y1currency" select="//xbrli:unit[@id = //pt:NetAssetsLiabilitiesIncludingPensionAssetLiability[@contextRef=$cr1/@id]/@unitRef]//xbrli:measure" />
					<xsl:with-param name="y2currency" select="//xbrli:unit[@id = //pt:NetAssetsLiabilitiesIncludingPensionAssetLiability[@contextRef=$cr2/@id]/@unitRef]//xbrli:measure" />
				</xsl:call-template>
			</xsl:when>

			<!-- The number of NetAssets is wrong -->
			<xsl:otherwise>
				<xsl:message terminate="yes">
					Found <xsl:value-of select="count(//pt:NetAssetsLiabilitiesIncludingPensionAssetLiability)"/>, instance must have either one or two of pt:NetAssetsLiabilitiesIncludingPensionAssetLiability
				</xsl:message>
			</xsl:otherwise>
		</xsl:choose>
  </xsl:template>

<!-- Displays a date formatted as eg 29 February 2004.
     @param date Date to write
-->
  <xsl:template name='formatDate'>
    <xsl:param name='date' />
    <xsl:choose>
      <!-- Debug option: dumps contents of tag unaltered if content starts with lt -->
      <xsl:when test="starts-with($date, '&lt;')">
        <xsl:value-of select="$date" />
      </xsl:when>
      <!-- Formats date -->
      <xsl:otherwise>
        <xsl:value-of select="concat(substring($date, 9, 2), ' ')" />
        <xsl:choose>
          <xsl:when test="substring($date, 6, 2)='01'">January</xsl:when>
          <xsl:when test="substring($date, 6, 2)='02'">February</xsl:when>
          <xsl:when test="substring($date, 6, 2)='03'">March</xsl:when>
          <xsl:when test="substring($date, 6, 2)='04'">April</xsl:when>
          <xsl:when test="substring($date, 6, 2)='05'">May</xsl:when>
          <xsl:when test="substring($date, 6, 2)='06'">June</xsl:when>
          <xsl:when test="substring($date, 6, 2)='07'">July</xsl:when>
          <xsl:when test="substring($date, 6, 2)='08'">August</xsl:when>
          <xsl:when test="substring($date, 6, 2)='09'">September</xsl:when>
          <xsl:when test="substring($date, 6, 2)='10'">October</xsl:when>
          <xsl:when test="substring($date, 6, 2)='11'">November</xsl:when>
          <xsl:when test="substring($date, 6, 2)='12'">December</xsl:when>
        </xsl:choose>
        <xsl:value-of select="concat(' ', substring($date, 1, 4))" />
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  
<!-- Display existing lines of an address tuple with line breaks.
     @param address Address to write
-->
  <xsl:template name='address'>
    <xsl:param name='address' />
    <xsl:if test='$address/ae:AddressCH'>
      <xsl:for-each select='$address/ae:AddressCH'>
        <xsl:if test='gc:AddressLine1'>
          <xsl:value-of select='gc:AddressLine1' />
          <br />
        </xsl:if>
        <xsl:if test='gc:AddressLine2'>
          <xsl:value-of select='gc:AddressLine2' />
          <br />
        </xsl:if>
        <xsl:if test='gc:AddressLine3'>
          <xsl:value-of select='gc:AddressLine3' />
          <br />
        </xsl:if>
        <xsl:if test='gc:CityOrTown'>
          <xsl:value-of select='gc:CityOrTown' />
          <br />
        </xsl:if>
        <xsl:if test='gc:County'>
          <xsl:value-of select='gc:County' />
          <br />
        </xsl:if>
        <xsl:if test='gc:Country'>
          <xsl:value-of select='gc:Country' />
          <br />
        </xsl:if>
        <xsl:if test='gc:Postcode'>
          <xsl:value-of select='gc:Postcode' />
          <br />
        </xsl:if>
      </xsl:for-each>
    </xsl:if>
  </xsl:template>

  <!-- Get the sign associated with a particular unit -->
  <xsl:template name="getCurrencySymbol">
    <xsl:param name="uniqueUnits" />
    <xsl:param name="contexts" />

    <xsl:choose>
      <!-- GBP -->
      <xsl:when test="$uniqueUnits = 'iso4217:GBP'">
        £
      </xsl:when>
      <!-- USD -->
      <xsl:when test="$uniqueUnits = 'iso4217:USD'">
        $
      </xsl:when>
      <!-- Euros -->
      <xsl:when test="$uniqueUnits = 'iso4217:EUR'">
        €
      </xsl:when>
      <!-- Other ISO units (XBRL uses ISO units with an iso4217: prefix) -->
      <xsl:when test="starts-with($uniqueUnits, 'iso4217:')">
        <xsl:value-of select="substring-after($uniqueUnits,'iso4217:')" />
      </xsl:when>
      <!-- Use the unit unprocessed -->
      <xsl:otherwise>
        <xsl:value-of select="$uniqueUnits" />
      </xsl:otherwise>
    </xsl:choose>
    <xsl:if test="($uniqueUnits != 'iso4217:GBP')"> <!-- this currency isn't GBP -->
      <xsl:if test="not(//ae:ForeignExchangeRate[@contextRef=$contexts])"> <!-- there're no exchange rate footnote/s -->
        <xsl:message terminate="yes">Non-GBP currency used but no exchange rate footnote/s provided</xsl:message>
      </xsl:if>
    </xsl:if>
  </xsl:template>
  <!-- Report any unused elements in the instance. Note that this must be the last
       template -->
  <xsl:template name="printUnusedElements">
  <xsl:variable name="usedNodes" select="//pt:IntangibleFixedAssetsAmortisationOtherAdjustments | //pt:TotalInvestmentsFixedAssets | //ae:FixturesFittingsToolsEquipmentDepreciationOtherAdjustments | //ae:InvestmentPropertiesIncreaseDecreaseFromTransfersBetweenItems | //ae:OtherTaxationCategory | //ae:DirectorsAcknowledgeTheirResponsibilitiesUnderCompaniesAct | //ae:AccountingPolicy | //ae:OtherTangibleFixedAssetDepreciationOtherAdjustments | //ae:DirectorsReportFreeTextNote | //ae:AggregateCreditorsFreeTextNote | //pt:TotalNumberSharesIssued | //pt:AccrualsDeferredIncome | //pt:ValueOrdinarySharesAuthorised | //gc:Postcode | //pt:InvestmentPropertiesCostOrValuation | //pt:InvestmentPropertiesDepreciationDisposals | //pt:ProfitLossOnOrdinaryActivitiesBeforeTax | //ae:TotalFixedAssetsCostOrValuation | //pt:FixturesFittingsToolsEquipmentDepreciation | //ae:OtherTangibleFixedAssetDepreciationDisposals | //ae:OtherTangibleFixedAssetTransfers | //pt:InvestmentsCurrentAssets | //pt:LandBuildingsDepreciationDisposals | //pt:ValueOrdinarySharesAllotted | //ae:TotalFixedAssetsDepreciationOnTransfers | //ae:ListDirectorsExecutives | //pt:TangibleFixedAssetsIncreaseDecreaseFromTransfersBetweenItems | //pt:TotalConsideration | //pt:PlantMachinery | //ae:AccountsPreparedUnderHistoricalCostConventionInAccordanceWithFRSSE05 | //pt:CashBankInHand | //ae:ReportingAccountants | //pt:NonEquityAuthorisedDetails | //pt:SharesDirectorOrExecutive | //pt:FixturesFittingsToolsEquipmentDepreciationIncreaseDecreaseFromTransfersBetweenItems | //pt:TotalOperatingProfitLoss | //pt:NetAssetsLiabilitiesIncludingPensionAssetLiability | //pt:CreditorsDueAfterOneYearTotalNoncurrentLiabilities | //pt:TotalAssetsLessCurrentLiabilities | //pt:ValueNonEquitySharesAllottedInPeriod | //pt:GrossProfitLoss | //pt:PlantMachineryIncreaseDecreaseFromTransfersBetweenItems | //pt:TangibleFixedAssetsDisposals | //pt:LandBuildingsDepreciationChargeForPeriod | //pt:CalledUpShareCapital | //pt:FixedAssets | //pt:InvestmentPropertiesIncreaseDecreaseFromTransfersBetweenItems | //ae:FRSSEVersionDate | //ae:AccountsPreparedUnderHistoricalCostConventionInAccordanceWithFRSE | //pt:IntangibleFixedAssetsAdditions | //pt:LandBuildingsDepreciationIncreaseDecreaseFromTransfersBetweenItems | //ae:InvestmentsFixedAssetsFreeTextNote | //gc:AddressLine1 | //pt:ObligationsUnderFinanceLeaseHirePurchaseContractsAfterOneYear | //ae:AccountsAreInAccordanceWithSpecialProvisionsCompaniesActRelatingToSmallCompanies | //pt:BankLoansOverdraftsAfterOneYear | //gc:AddressLine3 | //pt:LandBuildingsDepreciation | //ae:MembersHaveNotRequiredCompanyToObtainAnAudit | //ae:Shareholding | //ae:InvestmentProperties | //ae:NameBankers | //ae:Bankers | //pt:LoansFromDirectors | //pt:DepreciationAmountsWrittenOffFixedAssets | //ae:SecuredDebts | //pt:AdministrativeExpenses | //pt:ValueNonEquitySharesAllotted | //pt:FixturesFittingsToolsEquipmentCostOrValuation | //ae:ChargesBeforeOperatingProfit | //ae:ContentAdditionalNote | //pt:OperatingLeaseExpenditure | //pt:TangibleFixedAssets | //ae:NameSolicitors | //ae:RejectionReferenceCode | //ae:TotalFixedAssetsIncreaseDecreaseFromTransfersBetweenItems | //gc:BalanceSheetDate | //pt:ConsiderationForNonEquitySharesAllottedInPeriod | //ae:InstalmentDebtsFallingDueAfter5Years | //pt:CostSales | //ae:DescriptionAccountants | //ae:TotalFixedAssetsDepreciation | //pt:IntangibleFixedAssetsRevaluation | //gc:Country | //pt:CreditorsDueWithinOneYearTotalCurrentLiabilities | //pt:PlantMachineryDisposals | //pt:CalledUpShareCapitalNotPaidCurrentAsset | //ae:DirectorsName | //ae:TangibleAssetsFreeTextNote | //pt:IntangibleFixedAssetsIncreaseDecreaseFromTransfersBetweenItems | //ae:RateDepreciation | //pt:IntangibleFixedAssetsCostOrValuation | //pt:PlantMachineryDepreciationDisposals | //ae:InvestmentPropertyFreeTextNote | //ae:AccountsAreInAccordanceWithPartVIICompaniesActRelatingToSmallCompanies | //ae:ConvertibleLoansWithinOneYear | //ae:NameAccountants | //pt:IntangibleFixedAssetsAmortisationIncreaseDecreaseFromTransfersBetweenItems | //pt:TradeDebtors | //pt:LandBuildingsDisposals | //pt:RevaluationReserve | //pt:TotalUKCurrentCorporationTax | //pt:FixturesFittingsToolsEquipmentDepreciationDisposals | //ae:RedeemableSharesFreeTextNote | //pt:InvestmentPropertiesAdditions | //ae:PeriodColumnHeadingsUsed | //ae:BusinessAddress | //pt:InvestmentPropertiesDepreciation | //ae:CompanyNon-trading | //pt:LandBuildingsCostOrValuation | //ae:DirectorsMaximumLiability | //pt:TaxRelatedToProfitOrLossOnOrdinaryActivities | //ae:DateResignation | //pt:NumberOrdinarySharesAuthorised | //pt:DateApproval | //pt:IntangibleFixedAssetsDisposals | //pt:FixturesFittingsToolsEquipmentDisposals | //ae:AccountingPolicySubTitle | //pt:LandBuildingsAdditions | //pt:IntangibleFixedAssetsAmortisationOnDisposals | //ae:DateAccountsReceived | //ae:ShareCapitalFreeTextNote | //ae:NameTypeTangibleFixedAsset | //ae:InvestmentsFixedAssetsDepreciation | //ae:Non-InstalmentDebtsFallingDueAfter5Years | //ae:TotalFixedAssetsDisposals | //pt:ProfitLossOnOrdinaryActivitiesAfterTax | //ae:ForeignExchangeRate | //pt:ProvisionsForLiabilitiesCharges | //pt:RetainedProfitLossForFinancialYearTransferredToReserves | //ae:DirectorsReportGeneralText | //ae:DirectorShareholding | //pt:PlantMachineryDepreciationChargeForPeriod | //ae:AccountsAreInAccordanceWithSpecialProvisionsCompaniesActRelatingToSmallCompanies-DirectorsReport | //ae:TransactionsWithDirectors | //ae:OtherTangibleFixedAssetAdditions | //gc:AddressLine2 | //pt:FixturesFittingsToolsEquipmentDepreciationChargeForPeriod | //pt:IntangibleFixedAssetsAggregateAmortisationImpairment | //pt:NameApprovingDirector | //pt:TotalFixedAssetInvestmentsDisposals | //ae:OtherTangibleFixedAssetRevaluation | //gc:County | //pt:TotalFixedAssetInvestmentsCostOrValuation | //pt:IntangibleFixedAssetsRevaluations | //pt:LandBuildingsIncreaseDecreaseFromRevaluations | //pt:TurnoverGrossOperatingRevenue | //ae:InvestmentsFixedAssetsDepreciationOnDisposals | //pt:TypeNonEquityShare | //ae:TotalFixedAssetsDepreciationDisposals | //pt:TangibleFixedAssetsAdditions | //ae:DebtorsFreeTextNote | //pt:PositionAdditionalApprovingPerson | //ae:VATRegistrationNumber | //ae:CreditorsDueWithinOneYearFreeTextNote | //pt:Debtors | //ae:TypeDepreciation | //gc:EntityBusinessDescription | //pt:LandBuildings | //pt:NonEquityAllotedDetails | //ae:TitleAccountingPolicy | //pt:PlantMachineryDepreciationIncreaseDecreaseFromTransfersBetweenItems | //pt:NameAdditionalApprovingPerson | //ae:InvestmentsFixedAssetsIncreaseDecreaseFromTransfersBetweenItems | //pt:PlantMachineryIncreaseDecreaseFromRevaluations | //pt:ParValueNonEquityShare | //pt:SharePremiumAccount | //pt:TangibleFixedAssetsCostOrValuation | //pt:OtherAggregateReserves | //pt:DescriptionSharesOrDebentures | //ae:DirectorsShareCapitalFreeTextNote | //pt:OrdinarySharesIssuedInPeriod-Details | //ae:LoansFromDirectors | //ae:CreditorsDueAfterOneYearFreeTextNote | //ae:OtherTaxationValue | //ae:CompaniesHouseRegisteredNumber | //ae:InvestmentPropertyDepreciationOtherAdjustments | //ae:AddressCH | //pt:InvestmentPropertiesDisposals | //ae:InvestmentPropertiesIncreaseDecreaseFromRevaluations | //pt:DetailsOrdinarySharesAllotted | //ae:OtherTangibleFixedAsset | //pt:PlantMachineryCostOrValuation | //pt:FixturesFittingsToolsEquipment | //ae:TotalFixedAssetsRevaluation | //pt:OtherDebtors | //ae:OtherTypeTangibleFixedAsset-Analysis | //pt:InvestmentPropertiesIncreaseDecreaseFromRevaluations | //pt:StatementOnRelatedPartyDisclosure | //ae:OtherTangibleFixedAssetDepreciationTransfers | //ae:AdditionalNote | //ae:DateApprovalDirectorsReport | //ae:IntangibleFixedAssetsRevaluation | //pt:LandBuildingsIncreaseDecreaseFromTransfersBetweenItems | //pt:PlantMachineryDepreciation | //pt:OtherCreditorsDueWithinOneYear | //ae:ApprovalDirectorsReport | //ae:InvestmentsFixedAssetsRevaluations | //ae:Stocks | //pt:TangibleFixedAssetsDepreciationIncreaseDecreaseFromTransfersBetweenItems | //ae:PositionPersonApprovingDirectorsReport | //pt:TypeOrdinaryShare | //pt:TotalFeesToAuditors | //pt:StocksInventory | //pt:FixturesFittingsToolsEquipmentAdditions | //ae:CompanyEntitledToExemptionUnderSection477CompaniesAct2006 | //ae:TransactionWithDirector | //pt:TangibleFixedAssetsDepreciationChargeForPeriod | //pt:PlantMachineryAdditions | //gc:CityOrTown | //ae:TotalFixedAssetsAdditions | //pt:TradeCreditorsWithinOneYear | //ae:AmountsOwedToGroupUndertakingsDueWithinOneYear | //ae:TotalFixedAssetsDepreciationChargeInPeriod | //ae:CategoryItem | //gc:EntityCurrentLegalName | //ae:OtherTangibleFixedAssetDisposals | //pt:TangibleFixedAssetsDepreciationDisposals | //ae:ContentAccountingPolicy | //ae:TangibleFixedAssetsDepreciationOtherAdjustments | //ae:InvestmentsFixedAssetsDepreciationChargedInPeriod | //pt:TotalNominalValue | //pt:ProfitLossAccountReserve | //ae:OtherTangibleFixedAssetCostOrValuation | //pt:NumberNonEquitySharesAllottedInPeriod | //pt:NumberNonEquitySharesAllotted | //ae:LandBuildingsDepreciationOtherAdjustments | //pt:BankLoansOverdraftsWithinOneYear | //pt:TotalDeferredTax | //pt:ValueNonEquitySharesAuthorised | //pt:DateAssumedPosition | //pt:NetCurrentAssetsLiabilities | //pt:TotalFixedAssetInvestmentsAdditions | //ae:PlantMachineryDepreciationOtherAdjustments | //ae:OtherTaxationDetails | //ae:DirectorsLiability | //ae:NamePersonApprovingDirectorsReport | //pt:NumberNonEquitySharesAuthorised | //ae:OtherTangibleFixedAssetDepreciationChargeForPeriod | //pt:DistributionCosts | //pt:PrepaymentsAccruedIncomeNotExpressedWithinCurrentAssetSub-total | //ae:OtherTangibleFixedAssetDepreciation | //pt:FixturesFittingsToolsEquipmentIncreaseDecreaseFromRevaluations | //pt:TangibleFixedAssetsDepreciation | //pt:TotalInterestPayableReceivableNet | //pt:NonEquitySharesDetailsAllotmentsDuringPeriod | //pt:InvestmentProperties | //ae:InvestmentsFixedAssetsDepreciationOnTransfers | //ae:IntangibleAssetsFreeTextNote | //pt:TangibleFixedAssetsIncreaseDecreaseFromRevaluations | //ae:CompanyEntitledToExemptionUnderSection249A1CompaniesAct1985 | //pt:ParValueOrdinaryShare | //ae:TaxationFreeTextNote | //ae:DescriptionBusinessAddress | //ae:TitleAdditionalNote | //pt:IntangibleFixedAssets | //pt:AdditionalApprovingPerson | //ae:DisplayTotalCreditorsNote | //pt:CurrentAssets | //pt:CalledUpShareCapitalNotPaidNotExpressedAsCurrentAsset | //ae:TotalFixedAssetsDepreciationOtherAdjustments | //ae:Solicitors | //pt:InvestmentPropertiesDepreciationChargeForPeriod | //ae:RegisteredOfficeAddress | //pt:DirectorOrExecutivesName | //pt:NonEquityAllottedDetails | //ae:TotalFixedAssetsFreeTextNote | //pt:ShareholderFunds | //pt:AccrualsDeferredIncomeWithinOneYear | //ae:AbridgedAccountsStatement | //pt:IntangibleFixedAssetsAmortisationChargedInPeriod | //ae:IntangibleFixedAssetsAmortisationOtherAdjustments | //pt:IntangibleFixedAssetsAmortisationChargedinPeriod | //ae:DepreciationRate | //pt:FixturesFittingsToolsEquipmentIncreaseDecreaseFromTransfersBetweenItems | //ae:PoliticalCharitableDonationsText | //ae:InvestmentsFixedAssetsDepreciationOtherAdjustments | //pt:GrossDividendPayment-AllShares | //ae:CompanySecretarysName | //pt:OtherCreditorsAfterOneYear | //pt:TaxationSocialSecurityDueWithinOneYear | //pt:NumberOrdinarySharesAllotted | //pt:InvestmentPropertiesDepreciationIncreaseDecreaseFromTransfersBetweenItems | //pt:EquityAuthorisedDetails | //pt:PrepaymentsAccruedIncomeCurrentAsset | //ae:CompanyDormant | //ae:CompanyNotDormant | //ae:CompaniesHouseDocumentAuthentication" />
  <xsl:variable name="usedNodeList">
    <xsl:for-each select="$usedNodes">
      :<xsl:value-of select="name(.)" />:
    </xsl:for-each>
  </xsl:variable>
  <xsl:variable name="unusedNodes" select="//ae:*[@contextRef and not(contains($usedNodeList, concat(':',name(.),':'))) and not(child::*)] | //gc:*[@contextRef and not(contains($usedNodeList, concat(':',name(.),':'))) and not(child::*)] | //pt:*[@contextRef and not(contains($usedNodeList, concat(':',name(.),':'))) and not(child::*)]" />
  <xsl:if test="$unusedNodes">
    <h1 style="color: red">Unused Elements</h1>
    <xsl:message>Unused elements found.</xsl:message>
  <table style="border: none">
    <xsl:for-each select="$unusedNodes">
      <tr>
        <td style="color:red">
          <xsl:value-of select="local-name()" />
        </td>
        <td style="color:red">
          <xsl:value-of select="." />
        </td>
        <td style="color:red">
          <xsl:value-of select="@contextRef" />
        </td>
      </tr>
    </xsl:for-each>
  </table>
  </xsl:if>

  </xsl:template>
  <xsl:template name="printDuplicateElements">
  <xsl:variable name="duplicates">
    <xsl:for-each select="(//ae:* | //gc:* | //pt:*)[@contextRef]">
      <xsl:variable name="curNode" select="self::node()" />
      <xsl:variable name="curName" select="name(.)" />
      <xsl:variable name="curCon" select="./@contextRef" />
      <xsl:variable name="curUnit" select="./@unitRef" />
      <xsl:if test="following-sibling::*[(name() = $curName) and (./@contextRef = $curCon) and (./@unitRef = $curUnit)]">
	Element:'<xsl:value-of select="$curName"/>' with value: '<xsl:value-of select="."/>' in context '<xsl:value-of select="./@contextRef"/>' with unitRef '<xsl:value-of select="./@unitRef"/>'.</xsl:if>
    </xsl:for-each>
  </xsl:variable>

  <xsl:if test="$duplicates != ''">
    <xsl:message terminate="yes">Duplicate elements found.<xsl:value-of select="$duplicates"/></xsl:message>
  </xsl:if>

  <xsl:variable name="instantContexts" select="//xbrli:context[.//xbrli:instant]" />
  <xsl:variable name="iduplicates">
    <xsl:for-each select="(//ae:* | //gc:* | //pt:*)[@contextRef]">
      <xsl:variable name="contextRef" select="./@contextRef" />
      <xsl:variable name="icontext" select="$instantContexts[@id = $contextRef]" />
      <xsl:if test="$icontext"> <!-- is it in an instant context? -->
        <xsl:variable name="cdate" select="$icontext//xbrli:instant" /> <!-- context date -->
        <xsl:variable name="name" select="name()" /> <!-- qname of the item -->
        <xsl:variable name="unit" select="@unitRef" /> <!-- qname of the item -->
        <xsl:for-each select="following-sibling::*[(name() = $name) and (@unitRef = $unit)]">
        	<xsl:variable name="duplicate_contextRef" select="./@contextRef" /> 
        	<xsl:variable name="duplicate_date" select="$instantContexts[@id = $duplicate_contextRef]//xbrli:instant" />
          <xsl:if test="$duplicate_date = $cdate">
            Element: '<xsl:value-of select="name()" />' with value '<xsl:value-of select="."/>' and unitRef '<xsl:value-of select="./@unitRef"/>'.
          </xsl:if>
        </xsl:for-each>
      </xsl:if>
    </xsl:for-each>
  </xsl:variable>

  <xsl:if test="$iduplicates != ''">
    <xsl:message terminate="yes">Duplicate (different context, but same instant date) elements found.<xsl:value-of select="$iduplicates"/></xsl:message>
  </xsl:if>


  </xsl:template>


  <xsl:template name="datePlusOne">
    <xsl:param name="date" /> <!-- Must be in format yyyymmdd -->
    <xsl:if test="string-length($date) != 8">
      <xsl:message terminate="yes">
        Cannot increment date <xsl:value-of select="$date" />.
      </xsl:message>
    </xsl:if>

    <xsl:variable name="monthday" select="substring($date,5,4)" />
    <xsl:variable name="year" select="substring($date,1,4)" />

    <xsl:choose>
      <!-- If the end of a month, wrap around -->
      <xsl:when test="$monthday = '0131'"><xsl:value-of select="$year" />0201</xsl:when>
      <xsl:when test="$monthday = '0228'"><xsl:value-of select="$year" />0301</xsl:when>
      <xsl:when test="$monthday = '0229'"><xsl:value-of select="$year" />0301</xsl:when>
      <xsl:when test="$monthday = '0331'"><xsl:value-of select="$year" />0401</xsl:when>
      <xsl:when test="$monthday = '0430'"><xsl:value-of select="$year" />0501</xsl:when>
      <xsl:when test="$monthday = '0531'"><xsl:value-of select="$year" />0601</xsl:when>
      <xsl:when test="$monthday = '0630'"><xsl:value-of select="$year" />0701</xsl:when>
      <xsl:when test="$monthday = '0731'"><xsl:value-of select="$year" />0801</xsl:when>
      <xsl:when test="$monthday = '0831'"><xsl:value-of select="$year" />0901</xsl:when>
      <xsl:when test="$monthday = '0930'"><xsl:value-of select="$year" />1001</xsl:when>
      <xsl:when test="$monthday = '1031'"><xsl:value-of select="$year" />1101</xsl:when>
      <xsl:when test="$monthday = '1130'"><xsl:value-of select="$year" />1201</xsl:when>
      <xsl:when test="$monthday = '1231'"><xsl:value-of select="1+$year" />0101</xsl:when>
      <xsl:otherwise><xsl:value-of select="$date + 1" /></xsl:otherwise>
    </xsl:choose>
  </xsl:template>

</xsl:stylesheet>

