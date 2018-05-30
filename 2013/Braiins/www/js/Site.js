// Site.js
// 08.01.12
// Handle enter as for the BDT

$(function(){
  // Init
  // Test for localStorage
  if ('localStorage' in window && window['localStorage'] !== null) {
    if (localStorage.B!='2') {
      localStorage.clear()
      sessionStorage.clear()
      localStorage.B='2'
    }
    Now=new Date()
    var i, k, t, newB=0, tzo=Now.getTimezoneOffset()
    Now=Math.round(Now.getTime()/1000)
    if (Inst=sessionStorage.i) // #
      V=sessionStorage.v
    else{
      for (i=newB=1;1;i++)
        if (!(t=localStorage[k='t'+i]) || Now-t > 5) {
          Inst=i
          V=!t?0:localStorage['v'+i]
          break
        }
      if (Inst>9) return // limit active windows to 9, exiting with buttons disabled
    }
    if (!V) V=0 // in case it is null/undefined
    sessionStorage.i=Inst
    localStorage[Met='t'+Inst]=Now
    setInterval(TS, 1000)
    $.ajaxSetup({
      type: "POST",
      complete: Complete,
      error: AjError,
      timeout: 15000,
      dataType: 'text'
     });
    // Boot
    Post('Boot.php', BootBack, 'i', [Inst,newB,document.referrer,''+screen.width+screen.height,tzo,$('meta')[0].id])
    $('#LIB').click(LIn)
    $('#LOB').click(LOut)
    $('.topB').click(function() {
      scroll(0,0)
    })
    El=0
    EmEl=$('#email')[0]
    PwEl=$('#pw')[0]
    LiFieldsQA = $('#LI input')
    LiFieldsQA.keydown(LiTrapTabOrEnter)
    $('input').blur(function(){$(this).val($.trim($(this).val()))})
    if (self.Init)
      Init()
  }else
    $('#NotHtml5').removeClass('hide')
})

function BootBack(d) {
  //console.log($(this))
  Back(d,4)
  if (!R) {
    if ((V=IsNum(Rd[3]))>7518627) {
      sessionStorage.v=localStorage['v'+Inst]=V
      SetMI()
      if (self.AfterBoot)
        AfterBoot()
    }
  }
}

// Set Member Info
function SetMI() {
  // Have in Dat: Email | DName | LoginN [| Coded VisId in boot case]
  Email  =  Rd[0]
  DName  =  Rd[1]
  LoginN = +Rd[2]
  ShowMI()
}

function ShowMI() {
  var t
  switch (LoginN) { // 0: Not, 1: List, 2: Tentative, 3: Full, 4: Guest
    case 0: t = 'Welcome to Braiins. If you are a member of&nbsp;<br>the site please log in above for full access.';break
    case 3:
    case 4: t = 'Welcome <b>' + DName + "</b>. You are logged<br>in and may go to the Braiins Desktop."; break
    default:t = 'Hello <b>' + DName + '</b>. Please log in&nbsp;<br>above to have full site access.'; break // 1 & 2
  }
  if (LoginN < 3) {
    // Logged Out
    $('.DI').hide() // Desktop menu for logged Innners
    $('#LOB').hide() // Logout btn
    $('.DO').show() // Desktop menu for logged Outers
    $('#LI').show(500) // Log In div
    EmEl.value=Email
    PwEl.value=''
    //if (!Email.length)
    //  EmEl.focus()
    //else
    //  PwEl.focus()
  }else{
    // Logged In
    $('#LI').hide(500)
    $('.DI').show()
    $('#LOB').show()
    $('.DO').hide()
  }
  $('#MI').html(t) // Member Info
}

function LIn() {
  Msg=''
  if (ChTxt(EmEl, 3))  AddMsg('enter your username or email address')
  if (ChTxt(PwEl, 6)) AddMsg('enter your password which is expected to be at least 6 characters long')
  if (Msg) return NoGo()
  Post('Login.php', LiBack, 'l', [EmEl.value,PwEl.value]);
  $('#LIB').prop('disabled',true);
}

function LiBack(d) {
  $('#LIB').prop('disabled',false);
  Back(d,3)
  if (!R)
    SetMI()
  else if (R>0) {
    // Login attempt failed
    alert( Rd[0])
    EmEl.focus()
  }
}

function LOut() {
  Post('Logout.php',LoBack,'O');
  $('#LOB').prop('disabled',true);
}

function LoBack(d) {
  // New LoginN in Dat
  $('#LOB').prop('disabled',false);
  Back(d,1)
  if ((!R && Rd[0] == 2) || R == -1) {
    LoginN = 2
    ShowMI()
  }//else Logout error
}

//////////
// Ajax // + $.ajaxSetup() call in Init
//////////
// Post to initiate Ajax Data Exchange with server
// u   = url to post to
// fn  = callback fn
// op  = op code
// dat = array of the data being sent, optionally nothing
function Post(u, fn, op, dat) {
  var d='',i,n
  if (dat) {
    n=dat.length
    for (i=0;i<n;i++)
      d+=''+encodeURIComponent(dat[i])
  }else
    n=0
  n='0'+n
  $.post('./srv/'+u, 'Dat='+op+n.slice(-2)+V+d, fn) // nnVdat
}

// Process Ajax return where n is # of data fields
function Back(d,n) {
  Rd = d.split('') // Data from server A
  R=IsNum(Rd.pop())
  if (R>=0 && Rd.length!=n) {
    R=-9;
    Rd[0]='Invalid data received from server.';
  }
  if (R<0) // Error
    alert(Rd[0]);
  // else R >= 0 = OK or +ve flag return
}

function Complete() { // Run on completion of an Ajax call
  if (self.After)
    After()
}

function AjError() {
  alert('Sorry communications with the Braiins server failed.\n\nPlease try again.')
}

function TS() {
  localStorage[Met]=Now+=1
}

/////////////
// Gen Fns //
/////////////

function IsNum(n) {
  if (+n==n) return+n
  return -1
}

// Add contains() to the String object
String.prototype.contains = function(s) {
  return this.indexOf(s)!=-1
}

/*
function DisableButtons() {
  $('button').each(function(){$(this).prop('disabled',true)})
}
function EnableButtons() {
  $('button').each(function(){$(this).prop('disabled',false)})
}*/

// Login onkeydown fn to process Tab or Enter with Shift
function LiTrapTabOrEnter(evt) {
  var k=evt.which
  if (k==9 || k == 13) {
    LiFieldsQA[Roll(LiFieldsQA.index(this)+1-2*evt.shiftKey,2)].focus()
    evt.preventDefault();
    return false;
  }
  return true
}
function Roll(i, len) {
  if (i < 0) i = len-1
  if (i == len) i = 0
  return i
}

function GetEl(id) {
  return document.getElementById(id);
}
function GetElVal(id) {
  return document.getElementById(id).value;
}

// Input checking functions
function AddMsg(m) {
  Msg += ((Msg ? ',\nand ' : 'Please ') + m)
}

function NoGo() {
  El.focus()
  El=0
  alert(Msg+'.')
  return false
}

// Returns true on fails with input el of 1st one to fail in El
function ChkRet(e, b) {
  if (b && !El) El=e
  return b
}

// Check length of text field. Returns true if fails with input el in El
function ChTxt(e, l) {
  return ChkRet(e, e.value.length < l)
}

function ChEmail(e) {
  return ChkRet(e,!/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i.test(e.value))
}

function ChSel(e) {
  return ChkRet(e,!(+e.value))
}

