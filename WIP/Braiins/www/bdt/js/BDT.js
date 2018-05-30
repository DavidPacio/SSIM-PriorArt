/* BDT.js Started 22.02.11

Properties of AppsA objects, index AppN App's enum, var ao and Tao:
* Id     EC etc
* AppN   App's enum
* AP     App's Access Permission bit setting, always AP_Read
* title
* state  values: 0: closed, 1: minimised, 2: open blurred, 3: open and focussed (= active and top), 4: top with alert up
* DatChanged  true if data changed i.e. have unsaved data; 0 or null = unchanged; -1 = changed and trying to close
o w      default 600
o h      default 400
n scrollBody
o js[]   array of js modiles to be loaded in addition to /apps/App/App.js
o tbCap  optional toolbar button caption o'wise title
j Z      app win's current zIndex
j p{}    x, y, w, h of the window
j SavBtnState true when enabled
j SaveNOK     true if not ok to enable Save btn. Set if form can't be saved for some reason other than the form verifying e.g. credits insufficient.
j $a          jQuery object for the App's window (div)
j jo          js object for the app's js, created by global (no var) object in /apps/App/App.js with the same name as Id
j $wm         jQuery object for the app's winmask div
j $Inputs     Inputs to use when asssembling data & Tab/Enter mvt. Includes disabled but not readonly ones
j InputTypes  Array of IT_ input types corresponding to the $Inputs
j $Tips     Tips
j $FocEl    $ input el of last focus
j $RevertEl $ input el to focus on after revert (= initial $FocEl)
j ODatA     App's Original data from server in array form - only one value for a RB group
j ODatS     App's Original data from server in string form for SetBtns comparison use
j $RevBtn   App's Revert or Reset Btn
j $SavBtn   App's Save Btn
j SetBtnsCBFn Optional callback fn for App specific btn setting
|- * = mandatory, defined by BDT.Menu()
   o = optional
   n = not in use
   j = created and used by the js code when app is current. From $a down are deleted/reset on close.

Globals:
Call - Ajax call info. see fn Call()
Tao - top app obj = current app.
Met - instance key for localStorage updates
V   - VisitId via localStorage
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

// End of code from jquery-migrate-1.0.0.js

var Calls={},BusyO={num:0},Tao=null,Met,V,Now=Math.round(new Date().getTime()/1000), // Now in seconds
DName,MLevel,MBits,AName,ERef,EName,ELevel,R,AOnCloseFn,
AP_Read=256,AP_AccData=512,AP_Entity=1024,AP_Up=2048,AP_Down=4096,AP_Agent=8192,AP_Members=16384,AP_Credits=32768,AP_Delete=65536,AP_Admin=131072,IT_Text=1,IT_Num=2,IT_CB=3,IT_RB=4,
AppsA=[];

$(function(){
  // Boot
  var $Mg,i,mnuTo;
  if (!(i=sessionStorage.i))
    return CloseNoU(); // Must log-in at Braiins.com first. Chrome case on reload of browser if closed with BDT open
  V=sessionStorage.v;
  localStorage[Met='t'+i]=Now;
  setInterval(TS, 1000);
  Call('B',8,[document.referrer,$('meta')[0].id]);
  // UI
  $('#topbar li').each(function(){
    //$(this).hover( mouseeneter via hover() didn't always work when coming down from the top tho it was better when a margin was added to the li
    //  function(){$(this).stop().animate({color:'#e33'},700, 'easeInQuint',function(){$(this).css({background:'#333 url(css/img/tabon.png) repeat-x', padding:0, borderLeft:'1px solid #222', borderRight:'1px solid #666'})})},
    //  function(){$(this).stop().animate({color:'#eee'},700, 'easeOutSine',function(){$(this).css({background:'none', padding:'0 1px', 'border':'none'})})}
    //)
    $(this).mouseover(function(){
      $(this).stop().animate({color:'#06c'}, 300,'easeInSine', function(){$(this).css({background:'#ddd',padding:0,borderLeft:'1px solid #222',borderRight:'1px solid #666'});
      TopBar($(this).attr('id'))})
    });
    $(this).mouseleave(function(){
      $(this).stop().animate({color:'#2F2F2F'},300,'easeOutSine',function(){$(this).css({background:'none',padding:'0 1px','border':'none'})})
    });
  });
  $.fx.speeds._default = 500;

  // Menu Dialog
  ($Mg=$('#Md').dialog({
    //autoOpen:false,
    title:'The Braiins Desktop Menu',
    position:{
      my:'left top',
      at:'left+134 top+44'
    },
    height: 435,
    width: 380,
    show: 'blind',
    hide: 'fade'
//})).dialog('widget').mouseleave(function(){$Mg.dialog('close')});
  })).dialog('widget').hover(
    function(){clearTimeout(mnuTo);},
    function(){mnuTo=setTimeout(function(){$Mg.dialog('close');},1000)}
  );

  // Alert Dialog
  $Ag=$('#A').dialog({
    autoOpen:false,
    resizable: false,
    modal:true,
    minHeight:130,
    //show: 'blind',
    //hide: 'fade',
    close:AOnClose
  });
  // Override the _title function for jui 1.10 to allow html as per upgrade notes and http://stackoverflow.com/questions/4103964/icons-in-jquery-ui-dialog-title
  // djh?? Use a dialog title class instead as so far anyway is only used to set title to red, not to use an icon as in the stackoverflow example?
  $Ag.data('uiDialog')._title = function(title) {
    title.html(this.options.title);
  };

  // Btns
  $('#LO').click(LOut);
  $('#CL').click(Close);
  $('#Mb').click(MOpen).css('color','#000');
  $('#Md').on('click','.ua,a',Menu);
  window.onunload=Unload;

  // Context Menu
  $.contextMenu({
    selector:'#main',
/*  autoHide:true, */
    zIndex:500,
    items: {
      1: {name:'Close Desktop', icon: 'close'},
      2: {name:'Logout'},
      3: {name:'Minimise all Wins'},
      4: {name:'Close all Wins'},
      s1: '---------',
      C:{
        name:'Current Entity',
        items:{
          10: {name:'Upload Data'},
          13: {name:'Data Trail'},
          15: {name:'Financial State'},
          16: {name:'FS Download'},
          17: {name:'Set to Final'}
        }
      },
      s2: '---------',
      E:{
        name:'Entities',
        items:{
          20: {name:'Change'},
          21: {name:'List'},
          22: {name:'New'},
          23: {name:'Edit Current'},
          24: {name:'Reset'},
          25: {name:'Delete'}
        }
      },
      s3: '---------',
      D:{
        name:'Admin',
        items:{
          40: {name:'Snapshot'},
          43: {name:'Account Details'},
          44: {name:'Members'},
          45: {name:'Headings'}
        }
      }
    },
    callback: function(key) {Menu(+key);}
  });

  function TopBar(id) {
    switch (id) {
      case 'Mb': MOpen(); break
      case 'FS': break
      case 'LO': break
    }
  }

  function MOpen() {
    clearTimeout(mnuTo);
    $Mg.dialog('open');
  }

  function Menu(n) {
    var t=0;
    if (Busy()) return;
    if (typeof n != 'number') n=+this.dataset.app;
    switch(n) {
      case  1: Close(); break
      case  2: LOut(); break
      case  3: BDT.MinAll(); break
      case  4: BDT.CloseAll(); break
      default: if (AppsA[n] === undefined || ((t=AppsA[n].AP)&MBits)!=t) return;
    }
    if (t) {
      $Mg.dialog('close');
      BDT.LoadApp(n);
    }
    window.status='';
  }
})

// Enable Menu options according to permissions
function SetMenu() {
  var n,ap;
  $('#Md li').each(function() {
    if ((n=+this.dataset.app)>9 && AppsA[n] !== undefined && ((ap=AppsA[n].AP)&MBits)==ap) $(this).addClass('ua'); else $(this).removeClass('ua');
  });
}

function BackB(d) {
  // Have in Dat: DName | MLevel | MBits | AName | ERef | EName | ELevel | AppsA
  DName =  d[0];
  MLevel= +d[1];
  MBits = +d[2];
  AName =  d[3];
  ERef  =  d[4];
  EName =  d[5];
  ELevel= +d[6];
  // Create AppsA
  $.parseJSON(d[7]).forEach(function(ao){
    if (!ao.AP) ao.AP=0;
    ao.AP|=AP_Read;
    if (!ao.js) ao.js=[]; // no js[] defined in AppsA djh?? Never a js[]?
    ao.js.push('apps/'+ao.Id+'/'+ao.Id+'.js'); // /apps/App/App.js
    ao.state=ao.DatChanged=0;
    AppsA[ao.AppN]=ao;
  });
  $('#Ag span').html(AName+', '+DName);
  $('.ERef').html(ERef);
  $('.EName').html(EName);
  window.status='The Braiins Desktop - Copyright 2011-2013 Braiins Ltd. All Rights Reserved.';
  SetMenu();
}

/* Alert() to open a dialog using div A, and to give focus to the last button.
Arguments:
c  content of the dialog /- [AName] etc expanded
t  dialog title          |
w  width
b  array of button objects, which must contain
  text: for the button text. The hint (button title) is also set to this.
  Other possible properties are:
  click: for the function to run on click. If not passed, click: is set to AClose
  id:, class:, style:, etc
Optional:
cf fn to be called on close -> global AOnCloseFn. This gets called for all closes i.e. including Esc and dialog X. App can set AOnCloseFn to 0 to prevent fn being called.
i  file name of icon to be inserted */
function Alert(c,t,w,b,cf,i) {
  var p=Tao.p;
  c = BReplace(c);
  b.forEach(function(bo){bo.title=bo.text;if (!bo.click)bo.click=AClose});
  AOnCloseFn=cf;
  $('#A').html(i?sprintf('<img class="fl w32x" src=img/%s width=32 height=32><div class=fr style=width:%spx>%s</div>',i,w-60,c):c);
  $Ag.dialog('option',{title:BReplace(t), width:w, buttons:b, position:{my:'center', at:'center', of:Tao.$a}});
  $Ag.dialog('open');
  $('#A+div button:last').focus(); // djh?? Use jui 1.10 autofocus option instead? Starting with 1.10.0, if there is an element inside the content area with the autofocus attribute, that element will gain focus; if there is none, then the previous logic will be used.
}
function AClose() {
  $Ag.dialog('close');
}
function AOnClose() { // dialog close fn i.e.always called on close
  if (AOnCloseFn) AOnCloseFn();
}

// BDT Close wo Unload call
function CloseNoU() {
  CloseTabs();
  window.onunload=null;
  Home();
}
// BDT Close
function Close() {
  BDT.CloseAll(function(){CloseTabs();Home();});
}

function CloseTabs() {
  $('body').css('background','#cef')
  var c, call
  for (c in Calls)
  if ((call=Calls[c]).cd && call.wo && call.wo.Close)
    call.wo.Close()
  Calls={};
}

function LOut() {
  BDT.CloseAll(function(){Call('O');CloseNoU();});
}

function Home() {
  location='../';
}

function Unload() {
  Call('u');
  CloseTabs();
}

function TS() {
  localStorage[Met]=Now+=1
}

/*
// Ajax
Call to initiate a 'Call' Ajax Data Exchange with server, called in context of the app
 op  = op code, also appended to Back as the co callback fn after return with data
 optional:
 nr  = expected number of return values
 dat = array of the data being sent or 0 if none
 tn  = tab name of the separate tab (window.name) if call is for a separate tab
 c   = Calls obj key for a repeat tn call
 Calls = Object of Ajax calls objects by # (var c) with numbers being re-used after call objects have been deleted.
 Properties per call object:
  co: js calling object, window for BDT calls, App JS object (jo) for apps e.g. EC. Really only needed for Tab.js use. Could use Tao here.
  op: the op code
  nr: number of  separated return data values expected
  and for tn calls:
  cd: [c,AppN,tn]
  cn: count of the call/tab load base 0
  wo: windows object for the tab, set by TabLoaded() callback
  rd: return dat set by Back
Returns c
*/
function Call(op, nr, dat, tn, c) {
  var d='',i=0,n,call,co,u;
  switch (op) {
    case 'u':
    case 'O':Tao=null;break;
    case 'D':
    case 'E':i='Delet';break;
    case 'N':
    case 'S':
    case 'U':i='Sav';break;
    case 'W':i="D'load";break;
    default:i='Load';
  }
  if (i!==0) {
    $('#B>div').html(i+'ing');
    Busy(1);
  }
  if (Tao) {
    u = 'apps/'+Tao.Id+'/'+Tao.Id+'.php';
    co=Tao.jo;
  }else{
    u='srv/BDT.php',
    co=window;
  }
  if (!nr) nr=0;
  if (!c || !(call=Calls[c]) || !call.wo || !call.wo.name || call.wo.name!=tn) { // c not passed or no tab or tab closed or reused so get new c
    for (c=1;c in Calls;c++) {
      if ((call=Calls[c]).wo && !call.wo.name) { // tab for c existed but has been closed
        delete Calls[c];
        break;
      }
    }
    call={co:co,op:op,nr:nr,cn:0}
  }
  if (dat) { // expected to be an array
    n=dat.length;
    for (i=0;i<n;i++)
      d+=''+encodeURIComponent(dat[i]);
  }else
    n=0;
  n='0'+n
  if (tn) { // Sep tab app
    if (call.cd)
      call.cn++ // incr cn
    else{
      call.cd=[c,Tao.AppN,tn] // Tao.AppN is the tab module # set via Menu() and BDT.LoadApp()
      $('#T input').val(call.cd.join('')) // c | AppN | tn
      $('#T').attr('target',tn).submit()
    }
  }
  Calls[c]=call
  $.ajax({
   type: 'POST',
   url:u,
   data:'Dat='+op+n.slice(-2)+V+d, // djh?? could also pass AppN
   success: Back,
   complete: Complete,
   error: AjError,
   timeout: 15000,
   dataType: 'text',
   async:op!='u'&&op!='O', // false = sync for ops which don't return: unload and Logout
   cc:c // context: Calls[c] makes this in Back the call object
  })
  return c;
}

// Ajax return
function Back(d) {
  var c=this.cc,fn,call=Calls[c],op=call.op,rd=d.split('') // rd=returned data
  delete d
  if (call.cd) {
    call.rd=rd
    if (call.cn)
      fn=call.wo.Set
  }else
    fn=call.co['Back'+op]
  R=IsNum(rd.pop())
  if (R>=0 && rd.length!=call.nr) {
    R=-9;
    rd[0]='Invalid data received from server.';
  }
  if (R<0) { // Error
    alert(BReplace(rd[0]));
    if (Tao) CloseApp();
  }else // else R >= 0 = OK or +ve flag return
    if (fn)
    //fn.call(call.co,rd) // could use this to pass object back as this
      fn(rd) // done in TabLoaded() for an initial tab app call for which fn is not defined here, and which will not be an 'I' call. R for those Back fns which can have a ret value other than OK
  if (op=='I') // Init call so complete App Init
    InitApp();
}

function TabLoaded(c,wo) {
  var call=Calls[c],fn=call.co.BackT // always BackT for a Tab app
  if (fn) fn(call.rd) // only what is left after Tab.js and possibly /tab/js/App.js have shifted theirs off
    delete call.rd
  if (call.wo) // only on repeats as focus shifts to tab first time
    //TabFocus(c,1) // 1 to force use of alert from here as focus() doesn't work from here for Chrome tho it does work via btn
    setTimeout(TabFocus,100,c,1); // timeout to let Complete run wo being held up by the possible alert. djh?? Switch to anon fn if IE doesn't like this
  call.wo=wo
}

function Complete() { // Run on completion of an Ajax call
  var c=this.cc,call=Calls[c];
  if (Tao && Tao.jo.After) Tao.jo.After(); // Call App's After fn if it has one. Diff use from OnFocus as this is called after every Ajax call.
  if (!call.cd) delete Calls[c] // Delete the Call if not a Tab op
  Busy(-1);
}

function AjError() {
//if (!NoU) // a status 0 error occurs during a 'u' ajax call and unload. Not needed when the call is sync
  Busy(-1);
  alert('Sorry there has been a communications failure with the server.\n\nPlease try again.')
}

function TabFocus(c,fa) { // fa = force alert
  var wo=Calls[c].wo
  if (!$.browser.webkit || fa)
    wo.TabAlert()
  else
    wo.focus()
}

function BdtFocus() {
  //self.blur();
  //setTimeout(self.focus, 0);
  //Tao.$a.FocusWin();
  //Focus();
  alert(DName+"'s Braiins Desktop");
}

//  1 : incr Busy counter & start timer on to show B div on counter == 1 call
// -1 : decr Busy counter & cancel timer & hide div on counter < 1 (not 0 in case of more decr calls than incr ones
// undef : return counter for test as to whether busy is set
function Busy(b) {
  if (!b) {
    if (BusyO.num) {
      if (!BusyO.flashTo) {
        BusyO.flashTo=setInterval(function() {
          if (BusyO.num<1) {
            clearTimeout(BusyO.flashTo);
            BusyO.flashTo=null;
            $('#B').hide();
          }else
            $('#B').toggle();
        },200);
      }
    }
    return BusyO.num;
  }
  if (b>0) {
    if (++BusyO.num==1 && !BusyO.NoBusy) {
      if (Tao) {
      //Tao.$wm.css({opacity:0}).show(); // show app mask to disable buttons but not visible yet djh?? Re SlickGrid navigation?
        Tao.$wm.show().css({zIndex:999999});
        var p=Tao.p;
        $('#B').css({left:p.x+(p.w-68)/2,top:p.y+(p.h-68)/2}); // djh?? Use jui position?
      }
      //BusyO.showTo=setTimeout(function(){$('#B').show();if (Tao) Tao.$wm.css({opacity:.6})},1000);
      $('#B').show(500);
    }
  }else{
    if ((BusyO.num+=b)<=0) {
      //clearTimeout(BusyO.showTo);
      BusyO.num=BusyO.NoBusy=0;
      $('#B').hide();
      if (Tao)
        Tao.$wm.hide().css({zIndex:''}); // .css({opacity:.6}); // restore opacity for other uses of mask
    }
  }
}

// Replace [AName], [EName], [DName], [ERef], [MLevel] in the title attributes of the inputs contained in el id sel typically an app div
// djh?? Use php.js str_replace()?
function InPropsReplace(ids) {
  $(ids+' :input').each(function(){
    if (this.title) this.title = BReplace(this.title);
  });
}

// Show/Hide els with selector a or els with selector a and b
// If 2 sels (b is passed) then if B is true Show sel a els and Hide sel b els, and vv
// If 1 sel (no b) then if B is true show sel a else else hide sel a els
function ShowHide(B,a,b) {
  if (b) {
    $(B?b:a).hide();
    $(B?a:b).show();
  }else
    B?$(a).show():$(a).hide();
}

function SetFocusEl($el) {
  Tao.$FocEl=$el;
}

// Record the el with focus for RestoreWin use & show tip
function FocusTip() {
  BTip.Show(Tao.$FocEl=$(this));
}

// Focus of a input in a span -> 'focus' the span and show the span's tip. Mostly for CBs and RBs, but is used for some text inputs e.g. AM Password and the Gen PW Btn.
function SpanFocus() {
  BTip.Show((Tao.$FocEl=$(this)).parent().addClass('childHasFocus'));
}

function SpanBlur() {
  $(this).parent().removeClass('childHasFocus');
}

function CBChange() { // For Chome which doesn't focus on a CB when it is clicked.
  this.focus();
}

// Focus back on Tao.$FocEl
function Focus() {
  if (Tao.$FocEl) Tao.$FocEl.focus();
}

// keydown handler to trap tab and enter
function TabOrEnter(e) {
  if (e.which==9 || e.which==13) {
    e.preventDefault();
    var len=Tao.$Inputs.length,i=Tao.$Inputs.index(this);
    do {
      i+=1-2*e.shiftKey
      if (i < 0) i=len-1;
      if (i==len) i = 0;
    }while (Tao.$Inputs[i].disabled);
    Tao.$Inputs[i].focus();
  }
}


// Enable/disable jui button $b according to boolean B, remove its hover class
function SetBtn($b,B) {
  if ($b) $b.button(B?'enable':'disable').removeClass('ui-state-hover');
}

// Call the apps SetBtns fn if data has changed and state is different with true to enable btns, false to disable them. The app fn calls SetBtn()
function SetBtns() {
  if (Tao.ODatS) {
    var ch=DataStr()!==Tao.ODatS;
    // console.log('SetBtns()efore ch=',ch,'Tao.ODatS',Tao.ODatS,'DataStr()',DataStr(),'Tao.DatChanged',Tao.DatChanged,'Tao.SavBtnState',Tao.SavBtnState,'Tao.SaveNOK',Tao.SaveNOK);
    if (ch!==Tao.DatChanged) {
      SetBtn(Tao.$RevBtn,Tao.DatChanged=ch);
      if (Tao.SetBtnsCBFn) Tao.SetBtnsCBFn(ch);
    }
    if ((ch=(ch && !Tao.SaveNOK) ? VerifyDat():false)!==Tao.SavBtnState) SetBtn(Tao.$SavBtn,Tao.SavBtnState=ch);
  }
}

function SetSaveNOK(B) {
  return Tao.SaveNOK=B;
}

/* Input AlNum to remove non AlNum characters in an input, called on keyup, paste, or blur
function InputAlNum() {
  var v,r=/[^a-zA-Z0-9]/g;
  if (r.test(v=this.value))
    this.value=v.replace(r,'');
  SetBtns();
} */
// Input Alpha to remove leading space, non Alhpa (not 32 to 127 ASCII), £... characters in an input, to be called on keyup
function InputAlpha() {
  var r=/(^\s+)|[^ -~£€]/g;
  if (r.test(this.value)) // test avoids need to skip the replace for nav keys
    this.value=this.value.replace(r,'');
  SetBtns();
}
// As for InputAlpha plus Trim to be called on blur
function InputTrimAlpha() {
  this.value=this.value.replace(/(^\s+)|[^ -~£€]|(\s+$)/g,'');
  SetBtns();
}

// Input Digits check to remove non digit characters in an input, called on keyup, paste, or blur
function InputDigits() {
  var r=/[^0-9]/g;
  if (r.test(this.value))
    this.value=this.value.replace(r,'');
  SetBtns();
}
// Limit input type=number to min-max range on blur or paste. Only needed while FF lacks input type=number support
function InputNumber() {
  this.value=Range(+this.value.replace(r=/[^0-9]/g,''),+this.dataset.min,+this.dataset.max);
  SetBtns();
}

function SetFormBtns($rev,$sav) {
  Tao.$RevBtn=$rev; // null for report App e.g. CDT
  Tao.$SavBtn=$sav;
}

// Start of form handling. Args:
// - $Inputs the inputs excl readonly
// - The tips
// - dat array of initial form data or undefined if there is no form data e.g. EL
// Sets own field types re browser differences, and for speed later
// Sets the fields and buttons via Revert() id dat is passed
function SetForm($Inputs, $Tips, dat) {
  Tao.$Inputs=$Inputs;
  Tao.$Tips=$Tips;
  Tao.InputTypes=[];
  Tao.$RevertEl=Tao.$FocEl=$($Inputs[0]);
  $Inputs.each(function(){
    var t,n,x;
    switch (this.type) {
      case 'text':
      case 'email':
        if (!this.dataset.min) { // as FF treats number as type text
          t=IT_Text;
          if (!this.pattern) {
            n=this.dataset.minl;
            x=this.maxLength;
            this.pattern=sprintf('[ -~£€]{%s}(?=.*\\S)[ -~£€]{1,%s}',n-1,x-n+1); // [ -~£€]{2}(?=.*\S)[ -~£€]{1,98}
            this.title=this.title+sprintf(', %s to %s characters. %s',n,x,this.required?'Required.':'Optional.');
          }
          break;
        }
        //console.log('Applying number event to type',this.type,'due to this.dataset.min=',this.dataset.min);
      case 'number':  t=IT_Num;break;
      case 'checkbox':t=IT_CB;break;
      case 'radio':   t=IT_RB;break;
      default: t=0;
    }
    Tao.InputTypes.push(t);
  });
  if (dat) SetData(dat);
}

function SetData(dat) {
  Tao.ODatA=dat;
  Tao.DatChanged=Tao.SaveNOK=Tao.SavBtnState=null; // null so neither true nor false and thus btns will be set in first SetBtns() call
  Revert(); // sets Tao.ODatS via Revert->SetInputs
}

function Revert() {
  SetInputs();
  if (Tao.jo.Revert) Tao.jo.Revert();
  SetBtns();
  Tao.$FocEl=Tao.$RevertEl;
  Focus();
  return false;
}

// Sets inputs and Tao.ODatS from Tao.ODatA
function SetInputs() {
  var v,i=0,r=0,t; // i diff from k re RBs
  Tao.$Inputs.each(function(k){
    t=Tao.InputTypes[k];
    if (r) {
      if (t == IT_RB) {this.checked=+this.value==v; return;} // wo incr i
      r=0;i++; // finished with the radio btn group
    }
    v=Tao.ODatA[i];
    switch (t) {
      case IT_Text:
      case IT_Num:this.value=v;      break;
      case IT_CB: this.checked=v==1; break;
      case IT_RB: this.checked=+this.value==v;r=1;return; // wo incr i
      default: this.value=v; // select. Expect numeric values for B
    }
    i++;
  });
  Tao.ODatS=DataStr();
}

// Makes an Ajax Call to save data from the non-readonly inputs in Tao.$Inputs if data ready for Ajax call to server,
// or a boolean as the return for the save button click:
// - true on a verify failure for html5 form error handling to happen tho should only happen on a forced click as button should be disabled if data not ok.
// - false if data unchanged after trim -> no action, and if save Ajax call made.
// Passes back only changed fields in non empty string cases with others as  to reduce bandwidth usage.
// Optional arguments:
// op default 'S' /- passed thru to Call(). Default with no args is Call('S',1,dat)
// nr default 1   |
// pu - value to push onto dat
function Save(op,nr,pu) {
  var dat=0;
  if (DataStr()===Tao.ODatS) { // Should not happen
    console.log('DataStr()===Tao.ODatS in Save()');
    SetBtns();
    return false;
  }
  if (!VerifyDat()) { // Should not happen
    console.log('VerifyDat() failed in Save()');
    return true; // for html form handling
  }
  dat=Data(1); // 1 to ->  for unchanged string fields
  if (pu !== undefined) dat.push(pu);
  Call(op?op:'S',nr?nr:1,dat);
  return false;
}

function VerifyDat() {
  var v,ok=true;
  Tao.$Inputs.each(function(k){
    switch (Tao.InputTypes[k]) {
      case IT_Text: return ok=this.checkValidity();
      case IT_Num:  return ok=(v=IsNum(this.value))>=this.dataset.min && v<=this.dataset.max;
    }
  });
  return ok;
}

// Returns an array of the data from non-readonly inputs in Tao.$Inputs,
// with string fields set to  if equal to original and eq is passed.
function Data(eq) {
  var v,dat=[],i=0;
  Tao.$Inputs.each(function(k){
    switch (Tao.InputTypes[k]) {
      case IT_Text:
        v=$.trim(this.value); // trim should not be necessary
        if (eq && v.length && v==Tao.ODatA[i]) v='';
        break;
      case IT_CB:v=this.checked?1:0;break;
      case IT_RB:if (!this.checked) return;
      default:v=+this.value; // number, checked radio & select. Expect numeric values for B
    }
    dat.push(v);
    i++;
  });
  return dat;
}

// Returns a string of the data from the non-readonly inputs in Tao.$Inputs for button setting purposes
function DataStr() {
  var dat='';
  Tao.$Inputs.each(function(k){
    switch (Tao.InputTypes[k]) {
      case IT_Text:dat+=$.trim(this.value);break; // Trim so as not to give difference on entry of a trailing space
      case IT_CB:dat+=this.checked?1:0;break;
      case IT_RB:if (!this.checked) return;
      default:dat+=this.value; // number, checked radio, select
    }
  });
  return dat;
}

/////////////
// Gen Fns //
/////////////

function IsNum(n) {
  if (+n==n) return +n;
  return -1;
}

// Add contains() to the String object
String.prototype.contains = function(s) {
  return this.indexOf(s)!=-1;
}

function Range(a,b,c) {
  if (a<b) a=b;
  if (a>c) a=c;
  return a;
}

function Nop() {
}

function GetEl(id) {
  return document.getElementById(id);
}
function GetElVal(id) {
  return document.getElementById(id).value;
}

// Simple sprintf for %s. See SnippetsJs.txt
function sprintf(format) {
  var arg=arguments,i=1;
  return format.replace(/%((%)|s)/g, function (m) { return m[2] || arg[i++] })
}

/*
 * jQuery jDesktop plugin version 1.0
 * http://fractalbrain.net/
 *
 * Copyright 2010,  Krtolica Vujadin - FractalBrain.net
 * Heavily modified by David Hartley for Braiins use.
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 *
 See /Doc/Dev/jDesktop.txt and /Doc/ToDo.txt re changes

 14.05.12 Moved into Bdt.js as so many vars are shared.

 */

(function($) {
  var jWin = {
     Z: 100, // next zIndex to use
     num: 0, // count of windows created since last close all. Only used in auto positioning.
     ftrTop:$('#footer').position().top,
     $main: $('#main'),
     closeAll:0
    };

  $.fn.jWindow = function(AppN) { // Closure with local variables kept for each invocation, though no ref to a fn is returned. Kept because of the bindings presumably.
    var $tb, $wm, p, // tool bar btn (container) jel, winmask jel, p for {} of window x, y, h, w, Z
      ao = AppsA[AppN],
      Id = ao.Id,
      $a = this,
     mha = jWin.$main.height() - 134, // a=available 134 = 56 padding, 44 top bar, 35 task bar -1?
     mwa = jWin.$main.width() - 40,   // padding
       w = Range((ao.w?ao.w:600)-40, 200, mwa),
       h = Range((ao.h?ao.h:400)-56,  40, mha),
       t = jWin.num%7*40*(1-2*(jWin.num%2)),
       x = (mwa-w)/2+t*mwa/mha,
       y = (mha-h)/2+t;
    ao.p=p={x:Range(x, 0, mwa-w), y:Range(y+44, 44, mha-h), h:h, w:w};
    ao.Z=jWin.Z++;
    this.addClass('jwindow').css({left:p.x, top:p.y, height:h, width:w, zIndex:ao.Z})
       .html("<div class=top><div class=topc><div class=topr></div></div></div><div class=middle><div class=content><div class=container></div></div></div><div class=bottom><div class=bottomc><div class=bottomr></div></div></div><div class=wintitle><img alt='' title='' src=../favicon.png /><div class=titleleft></div><div class=titletext>"+ao.title+"</div><div class=titleright></div></div><div class=jwinhandle></div><span class=wincontrols><span class=winmin title=Minimise></span><span class=winclose title=Close></span></span><div class=winmask></div>");
    (ao.$wm=$wm=this.find('div.winmask')).css({width:w - 16, height:h + 16}); // showing
    this.find('span.winclose').on('click', {click:1}, BClick); // Close
    this.find('span.winmin').on('click', {click:2}, BClick); // Minimise

    //if (o.scrollBody) $a.find('div.container').css('overflow','auto');

    // Toolbar button in all cases
    $('#tcontainer').append('<div id='+Id+'tbcont class=wintbcont><span class=wintb>' + (ao.tbCap ? ao.tbCap : ao.title) + '</span><span class=wintbclose title=Close></span></div>');
    ($tb=$('#'+Id+'tbcont')).on('click', {click:3}, BClick)
      .hover(
      function() { // tb btn mouseenter
        $a.stop(true,true);
        if (ao.state>1) { // open or top
          //  bring to top, give it blue shadow, and flash it
          $wm.hide();
          $a.css('zIndex',jWin.Z)
             .addClass('blueShadow')
           //.animate({left:'+=5', top:'+=5'},150).animate({left:'-=5', top:'-=5'},150);
             .effect('bounce', {times:1, distance:5}, 150);
        }else{ // min
          var scale = Math.min(160 / (p.w > p.h ? p.w : p.h), 0.3),
            scaleDiff = (1-scale)/2,
            left = $tb.position().left + 60 - p.w/2,
            w = p.w + 40, // with padding
            h = p.h + 56,
            visl = left + w * scaleDiff, // visible left for off screen check
            top  = jWin.ftrTop - h + h*scaleDiff - 32; // 32=30 for animation and 2 separation
          if (visl < 0) left -= visl;
          $a.css({height:p.h, width:p.w, top:top,left:left,transform:'scale('+scale+')', display:'block', zIndex:6999}).animate({opacity:1, top:'+=30'}, 200);
        }
      },
      function() { // tb btn mouseleave
        $a.stop(true,true);
        if (ao.state>1) { // open or top
          $a.css('zIndex',ao.Z) // put orig Z back
             .removeClass('blueShadow');
          if (ao.state===2) $wm.show();
        }else
          $a.css({display:'none'});
      }
    );
    $tb.find('span.wintbclose').on('click', {click:1}, BClick);

    // Context menu
    // this.on('contextmenu',function(e) {e.preventDefault();});
    $.contextMenu({
      selector:'#'+Id,
      autoHide:true,
      zIndex:500,
      items: {
        1: {name:'Close', icon: 'close'},
        2: {name:'Minimise', icon: 'min'}
      },
      callback: function(key) {
        if (!Busy())
          switch (+key) {
            case 1: setTimeout(function() {$a.CloseWin();}, 0); break; // timeout because CloseWin destroys the context menu. Using just $a.CloseWin instead of the anon fn caused a value to be passed to noAni
            case 2: $a.MinWin(); break;
          }
      }
    });

    // Private Functions
    function BClick(e) {
    //console.log('BClick',e.data.click);
    //e.stopPropagation();
      if (!Busy())
        switch (e.data.click) {
          case 1: $a.CloseWin();break;
          case 2: $a.MinWin(); break;
          case 3: $a.TBBtnClick();break;
          case 4: $a.FocusWin(); break; // click -> top
        }
      //return false;
    }

    // Public Functions
    // CloseWin
    this.CloseWin = function(noAni) {
      BTip.Hide();
      if (ao.DatChanged && ao.$RevBtn) {
        // Unsaved changes && a DE App i.e. has Revert Btn, not a report App like CDT
        $a.FocusWin();
        Alert('You have unsaved data. You can abandon the data and continue with closing the window, or your can return to the window to continue working on it.',
          '<span class=wng>Unsaved Data</span>',
          375,
          [{text:'Abandon Unsaved Data',
            click:function(){
              AClose();
              CloseApp();
              jWin.closeAll=1; // CloseAll can continue
            }},
           {text:'Return to Window',
            click:function(){
              Focus();
              AClose();
              jWin.closeAll=3; // CloseAll aborted
            }},
          ],
          Focus,'important.png');
        jWin.closeAll=2; // CloseAll on hold for Alert
        return false; // Win not closed but topped with Save dialog up
      }
      BTip.Destroy(ao.$a);
      $tb.remove();
      if (ao.jo) { // re loadFail() call
        if (ao.jo.OnClose) ao.jo.OnClose(); // Call app's OnClose fn if it has one
        delete ao.jo;
      }
      for (var i in ao.js) $("script[src='"+ao.js[i]+"']").remove();
      ao.state=0;
      $.contextMenu('destroy','#'+Id);
      delete window[Id];
      ao.$a=ao.$wm=ao.$Inputs=ao.InputTypes=ao.$Tips=ao.$FocEl=ao.$RevertEl=ao.ODatA=ao.ODatS=ao.$RevBtn=ao.$SavBtn=ao.SetBtnsCBFn=ao.DatChanged=null; // djh?? Destroy the btns?
      Tao=null;
      if (noAni)
        $a.empty().remove(); // remove window from DOM. Some say it is faster to use empty() before remove(). See jQuery docs. Seems doubtful. djh?? Check this.
      else
        $a.hide(500,function(){
          $a.empty().remove();
          BDT.ShowTopWin();
        });
      return true; // Win closed
    } // end of CloseWin

    // MinWin
    this.MinWin = function() {
      $a.BlurWin(1); // -> 1
      $tb.addClass('wmin');
      $a.stop(true,true).animate({
        left: $tb.position().left,
        top: jWin.ftrTop-60,
        width: 122,
        height: 60,
        opacity: 0.3
      },500, function(){
        $a.css('display','none');
        BDT.ShowTopWin();
      });
    };

    // Toolbar Btn click
    this.TBBtnClick = function() {
      $a.stop(true,true);
      ao.state===3 ? $a.MinWin() : $a.FocusWin(); // MinWin if top, FocusWin if minimised or open
    };

    // FocusWin
    this.FocusWin = function() {
      if (Tao && Tao.state!==2) Tao.$a.BlurWin(2); // -> 2. Is already 2 for focus after load
      Tao=ao;
      switch (ao.state) { // 0: closed, 1: minimised, 2: open blurred, 3: top
        case 1: // min so restore it
          $tb.removeClass('wmin');
          ao.Z=jWin.Z++;
          $a.css({display:'block', left:$tb.position().left, top:jWin.ftrTop-60, width:122, height:60, transform:'scale(1)', opacity:0, zIndex:ao.Z})
            .animate({left:p.x, top:p.y, width:p.w, height:p.h, opacity:1},500,Focus);
          break;
        case 2: // blurred so just top it
          ao.Z=jWin.Z++;
          $a.css('zIndex',ao.Z);
          break;
      }
      if (ao.jo.ReInit && ao.ERef!=ERef) { // Reinit
        Busy(1);
        ao.state=2;
        ao.jo.ReInit(); // which should end up calling this fn again once the ReInit -> InitApp with ao.ERef==ERef
        return false;
      }
      // Add the tips and input events
      if (ao.$Tips) BTip.Build(ao.$Tips,ao.$a);
      if (ao.$Inputs) {
        ao.$Inputs.each(function(k){
          var $t=$(this);
          if ($t.parent().is('span'))
            $t.on({
              focus:SpanFocus,
              blur:SpanBlur,
              keydown:TabOrEnter
            });
          else
            $t.on({
              focus:FocusTip,
              keydown:TabOrEnter
            });

          switch (Tao.InputTypes[k]) {
            case IT_Text:
              $t.on({
                keyup:InputAlpha,
                paste:InputAlpha,
                 blur:InputTrimAlpha
              });
              break;
            case IT_Num:
              $t.on({
                keyup:InputDigits,
                paste:InputNumber,
                 blur:InputNumber
              });
              break;
            case IT_CB:
              $t.on('change',CBChange);
            case IT_RB:
            default: // select
              $t.on('change',SetBtns);
          }
        });
      }
      $wm.hide();
      if (ao.jo.OnFocus) ao.jo.OnFocus(); // Call app's OnFocus fn if it has one
      $tb.addClass('wtop');
      $a.off('click')
        .hover(
          function(){$a.addClass('shadow');},
          function(){$a.removeClass('shadow');}
        )
        .on({
         dragstart:function(e){ // dragging
          BTip.Hide();
          if ($(e.target).is('.jwinhandle')) return $wm.clone().insertAfter($a).css({zIndex:ao.Z+1,cursor:'move'});
          return false;
         },
         drag:function(e, dd){
          $(dd.proxy).css({display:'block', top:Range(dd.offsetY, 0, jWin.$main.height() - 100), left:Range(dd.offsetX, -p.w - 20, jWin.$main.width() - 100)});
         },
         dragend:function(e, dd){
          var proxy=$(dd.proxy), pos=proxy.css('backgroundColor','#00f').animate({opacity: 0}).position();
          p.x = pos.left;
          p.y = pos.top;
          $a.animate({left:p.x, top:p.y},function(){proxy.remove();Focus();});
         }
        });
      if (ao.state!=1) // focus if not being done after the restore ani
        Focus();
      ao.state=3; // top
    };

    // BlurWin
    this.BlurWin = function(toState) {
      $tb.removeClass('wtop');
      $a.removeClass('shadow')
        .off('dragstart drag dragend');
      if (toState === 2) { // no mask or top click when -> min
        $wm.show();
        $a.on('click', {click:4}, BClick); // click -> top
      }
      ao.state=toState;
      // Remove the tips and input events
      BTip.Destroy(ao.$a);
      if (ao.$Inputs)
        ao.$Inputs.each(function(){$(this).off();});
      Tao=null;
    };

    // finished creating jWindow
    jWin.num++;
    return this;

  }; // end of $.fn.jWindow = function(options)

  BDT = { // Global

    CloseAll: function(cbFn) {
      jWin.closeAll=1;
      closeAllTry();

      function closeAllTry() {
        var ok=0;
        if (jWin.closeAll==1) {
          ok=1;
          AppsA.forEach(function(ao){
            if (ok && ao.state && ao.$a.CloseWin(1)===false)  // state: min, open or top, 1=noAni
              ok=0;
          });
        }
        // console.log('closeAllTry jWin.closeAll=',jWin.closeAll, 'ok=',ok, 'cbFn=',cbFn);
        if (ok) {
          jWin.Z=100;
          jWin.num=0;
          Tao=null;
          if (cbFn) cbFn();
        }else if (jWin.closeAll!=3) // CloseAll not aborted = on hold for Alert or ok to continue
          setTimeout(closeAllTry, 250);
      }
    },

    MinAll: function() {
      AppsA.forEach(function(ao){if (ao.state>1) ao.$a.MinWin();});
    },

    // Find and 'show' the open window with largest zIndex = the most recent one which has been 'Top'
    // Called when a window is closed or minised with ani i.e. not if all being closed or minimised
    ShowTopWin: function() {
      var maxZ=0,maxi=0;
      AppsA.forEach(function(ao,i){
        if (ao.state>1 && ao.Z>maxZ) {
          maxZ=ao.Z;
          maxi=i;
        }
      });
      if (maxi)
        AppsA[maxi].$a.FocusWin();
    },

    LoadApp: function(n) {
      var ao=AppsA[n], Id=ao.Id, $a, to;
      if (ao.state) // min, open, or top
        return ao.$a.FocusWin();
      if (Tao) Tao.$a.BlurWin(2); // Blur the current Win -> no curr win until loading has finished
      $a=$(document.createElement('div')).appendTo('#main').attr({id:Id}).jWindow(n); // create the jQuery obj for the app
      Tao=ao;
      Busy(1);
      to=setTimeout(loadFail, 15000);
      ensure({js:ao.js}, function() {
        ao.jo=window[Id]; // js obj for the app defined by apps/App/App.js
        ao.$a=$a;
        $a.find('div.container').load('apps/'+Id+'/'+Id+'.htm', function() { // /apps/App/App.htm
          ao.state=2;  // open but not top until FocusWin() is called
          ao.jo.App(); // Must invoke an 'I' call which results in a call to InitApp() from BDT.Back() after the app's BackI() has run, or call InitApp() specifically e.g. EL
          clearInterval(to);
        });
      });
      function loadFail() {
        AjError();
        $a.CloseWin(1);
      }
    } // LoadApp

  } // end of BDT

})(jQuery)

function InitApp() {
  Busy(-1);
  if (Tao) {
    var $a=Tao.$a;
    $a.find('.AName').html(AName.length<50?AName:(AName.slice(0,46)+'...'));
    $a.find('.ERef').html(ERef);
    $a.find('.EName').html(EName);
    Tao.ERef=ERef;
    $a.FocusWin();
  }
}

// For calling from an active top window to close it esp after a Save
function CloseApp() {
  Busy(-2);
  Tao.DatChanged=0;
  Tao.$a.CloseWin();
}

// Replace [AName], [EName], [DName], [ERef], [MLevel]
function BReplace(s) {
  return s.replace('[AName]',AName).replace('[EName]',EName).replace('[DName]',DName).replace('[ERef]',ERef).replace('[MLevel]',MLevel);
}
