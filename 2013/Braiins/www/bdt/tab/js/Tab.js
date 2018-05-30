/* Copyright 2011-2013 Braiins Ltd
 /bdt/tab/js/Tab.js
 */

// Code from jquery-migrate-1.0.0.js to provide $.browser
// Use of jQuery.browser is frowned upon.
// More details: http://api.jquery.com/jQuery.browser
// jQuery.uaMatch maintained for back-compat

jQuery.uaMatch = function( ua ) {
  ua = ua.toLowerCase();

  var match = /(chrome)[ \/]([\w.]+)/.exec( ua ) ||
    /(webkit)[ \/]([\w.]+)/.exec( ua ) ||
    /(opera)(?:.*version|)[ \/]([\w.]+)/.exec( ua ) ||
    /(msie) ([\w.]+)/.exec( ua ) ||
    ua.indexOf("compatible") < 0 && /(mozilla)(?:.*? rv:([\w.]+)|)/.exec( ua ) ||
    [];

  return {
    browser: match[ 1 ] || "",
    version: match[ 2 ] || "0"
  };
};

matched = jQuery.uaMatch( navigator.userAgent );
browser = {};

if ( matched.browser ) {
  browser[ matched.browser ] = true;
  browser.version = matched.version;
}

// Chrome is Webkit, but Webkit is also Safari.
if ( browser.chrome ) {
  browser.webkit = true;
} else if ( browser.webkit ) {
  browser.safari = true;
}

jQuery.browser = browser;

$(function(){
  P=window.opener;
  if (Id=$('meta')[0].id) {
    Id=Id.split('.'); // [c, tm, tn]
    if ((C=IsNum(Id[0])) < 1 || window.name!=Id[2] || !OK())
      return Close(2); // Please refresh/reload Braiins tabs only via your Braiins Desktop.
  }else
    return Close(3); // Invalid html received
  IT=setInterval(Set, 50);
  $(window).unload(DelBdtCall);
  $('.topB').click(function(){scroll(0,0)});
  $('.bBDT').click(BdtFocus).hover(
    function(){$(this).addClass('ui-state-hover');},
    function(){$(this).removeClass('ui-state-hover');}
  );
})

function OK() {
  return !!(P && P.Calls && Id && (Call=P.Calls[C]) && Call.cd.join()==Id.join());
}

function Set() {
  if (OK()) {
    if (Call.rd) {
      clearInterval(IT);
      //console.log(Call);
      $('#Page').width($(window).width()); // For repeats re SetWidth()
      Tit=P.BReplace(Call.rd.shift()); // 0
      Hdg=P.BReplace(Call.rd.shift()); // 1
      $('header h2 span').html(Hdg);
      $('#Ante').html(Call.rd.shift()); // 2
      $('#Main').html(Call.rd.shift()); // 3
      $('#Post').html(Call.rd.shift()); // 4
      $('.AName').html(P.AName);
      $('.EName').html(P.EName);
      $('.ERef').html(P.ERef);
      $('.DName').html(P.DName);
      document.title=Tit;
      if (self.Init)
        Init(Call.rd); // 3 upwards now 0 upwards. DataTable() calls here
      SetWidth();
      P.TabLoaded(C,self);
      $('#Main').height() >= 0.9*$(window).height() ? $('.topB').show() : $('.topB').hide();
    }
  }else
    Close(0+!!(P && P.Calls)); // Your Braiins Desktop has closed || Server or communication problem
}

function SetWidth() {
  var maxWidth=0;
  $('.w').each(function(){maxWidth=Math.max(maxWidth, $(this).outerWidth(true));});
  $('#Page').width(Math.min(maxWidth,$(window).width()));
}

function BdtFocus() {
//console.log('$.browser.webkit',$.browser.webkit,'P',P);
  if (!OK())
    Close()
/*else if ($.browser.webkit) Worked early in 2012 but not on 20.10.12.
    P.focus() */
  else
    P.BdtFocus()
}

function TabAlert() { // called back from bdt.TabFocus(c) for cases when wo.focus() doesn't work
  alert(Hdg)
}

function Close(n) { // Also called from Bdt.CloseTabs()
  if (!n) n = 0
  DelBdtCall()
  document.title='Closed'
  $('header h2 span').html(['Your Braiins Desktop has closed','Server or communication problem','Please refresh/reload Braiins tabs only via your Braiins Desktop.','Invalid html received'][n])
  $('#Ante').html('<p class=c>This tab can be closed.</p>')
  $('#Main').empty()
  $('#Post').empty()
  $('#Btns').empty()
  $('footer').empty()
  SetWidth()
  if (!n) self.close()
}

function DelBdtCall() {
  if (OK()) {
    var fn=Call.co.TabClose; // to hide Report Btn in calling window if there is one
    if (fn) fn();
    delete P.Calls[C];
  }
}

function IsNum(n) {
  if (+n==n) return+n;
  return -1;
}

