/*
/js/Reg.js

14.02.11 djh Started

djh??
Strip spaces from pw
Get rid of form
Handle enter as for the BDT

*/

var SavBtnState=null,$SavBtn;

function Init() {
  // Button & Form Fields
  ($SavBtn=$('#RB')).click(Reg)
  $Inputs = $('form input');
  // Gen PW
  $('#PG').click(GenPw);
  // Build DName
  $('#GN, #FN, #PN').on('blur',SetDName);
  $('#PN').on('focus',SetDName);
  // Add input events and Tips
  $Inputs.each(function(){
    var $t=$(this),n,x;
    if ($t.parent().is('span'))
      $t.on({
        focus:SpanFocus,
        blur:SpanBlur
      });
    else
      $t.on('focus', FocusTip);
    $t.on({
      keyup:InputAlpha,
      paste:InputAlpha,
       blur:InputTrimAlpha,
      keydown:TabOrEnter
    });
    if (!this.pattern) {
      n=this.dataset.minl;
      x=this.maxLength;
      this.pattern=sprintf('[ -~£€]{%s}(?=.*\\S)[ -~£€]{1,%s}',n-1,x-n+1); // [ -~£€]{2}(?=.*\S)[ -~£€]{1,98}
      this.title=this.title+sprintf(', %s to %s characters.',n,x);
    }
  });
  BTip.Build($('#tReg :input:not(span>input,span>button),#tReg span[title]'),$('#tReg'));
  $('#AN').focus();
}

function AfterBoot() {
  if (LoginN) {
    var F = document.forms[0],
      nA = DName.split(' '),fn;
    F.E.value=Email
    F.PN.value=DName
    if (nA.length>=2) {
      F.FN.value=fn=nA[nA.length-1];
      F.GN.value=$.trim(DName.slice(0,-fn.length));
    }
  }
  SetBtns();
}

// Register
function Reg() {
  if (!VerifyDat()) { // Should not happen
    console.log('VerifyDat() failed in Save()');
    return true; // for html form handling
  }
  Post('Reg.php',RegBack,'r',Data())
  $('#L').show()
  return false
}

function RegBack(d) {
  console.log(d)
  After();
  Back(d,4); // Email | DName | LoginN | Welcome message
  if (R>0)
    alert(Rd[0]);
  else if (!R){
    SetMI();
    BTip.Hide();
    $('#Main').html(Rd[3]);
    $('#Bottom').empty();
  }
}

function After() {
  $('#L').hide()
}

function Data() {
  var dat=[];
  $Inputs.each(function(){
    dat.push($.trim(this.value)); // trim should not be necessary
  });
  return dat;
}

function TabOrEnter(evt) {
  var k=evt.which
  if (k==9 || k == 13) {
    $Inputs[Roll($Inputs.index(this)+1-2*evt.shiftKey,$Inputs.length)].focus()
    evt.preventDefault();
    return false;
  }
  return true
}

function GenPw(e) {
  e.stopPropagation();
  // 8 to 16 characters, at least one of each of upper case letter A-Z, lower case letter a-z, digit 0-9, and a symbol (e.g. # or % etc incl £) in any order with no spaces or non-keyboard characters
  // From LL PlayI.htm
  // Does between 0 and 1 for random() mean 0 < x < 1 or 0 <= x <= 1 ?
  // On testing millions of times it appears to mean 0 < x < 1
  var i,p,el=GetEl('P'),chs='123456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ',ca,l;
  el.focus();
  chs=chs+chs+chs+'~`@#$%^&*()_-+={}[]\:;<>,.?/£€';ca=chs.split('');l=ca.length; // 3 times weight to alnum chrs
  //console.log(ca);
  e=0;
  do{
    p=[];
    for (i=Math.floor(Math.random()*9)+8;i;i--)
      p.push(ca[Math.floor(Math.random()*l)]);
    p=p.join('');
    el.value=p;
    e++;
  }while(e<999 && !el.checkValidity());
  if (e==999)
    el.value='bB1#cC2£';
  //console.log(p,' in '+e+' tries');
  SetBtns();
  return false;
}

function SetDName() {
  var PnEl=GetEl('PN'),GN=GetElVal('GN'),FN=GetElVal('FN'),PN=PnEl.value;
  if (!PN.length || PN==GN || PN==FN)
    PnEl.value=$.trim(GN+' '+FN);
}

// Record the el with focus for RestoreWin use & show tip
function FocusTip() {
  BTip.Show($(this));
}

// Focus of a input in a span -> 'focus' the span and show the span's tip
function SpanFocus() {
  BTip.Show($(this).parent().addClass('childHasFocus'));
}

function SpanBlur() {
  $(this).parent().removeClass('childHasFocus');
}

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

function SetBtns() {
  var ch=VerifyDat();
  if (ch!==SavBtnState) $SavBtn.prop('disabled',!(SavBtnState=ch));
}

function VerifyDat() {
  var ok=true;
  $Inputs.each(function(){
    return ok=this.checkValidity();
  });
  return ok;
}

// Simple sprintf for %s. See SnippetsJs.txt
function sprintf(format) {
  var arg=arguments,i=1;
  return format.replace(/%((%)|s)/g, function (m) { return m[2] || arg[i++] })
}

