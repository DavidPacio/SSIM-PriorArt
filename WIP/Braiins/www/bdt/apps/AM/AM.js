// AM Admin Members

AM = {

App:function() {
  var Actn=1,MemId=0,SelfId,ELevel,forms=[{},{},{}],$AMiPW;
  Call('I',2);
  InPropsReplace('#AMdI');
  // Action radio buttons
  $('#AMA [name=AMAr]').change(Action);
  // Member Select Fetch
  $('#AMeM').change(function() {
    if (+this.value)
      Call('F',1,[MemId=+this.value]);
  });
  // Buttons
  SetFormBtns(
    $('#AMbRev').button().click(function() { // Reset & Revert
      if (!Actn) SetFocusEl($('#AMiGN')); // Focus on Name for Add
      return Revert();
    }),
    $('#AMbSav').button().click(function() { // Add (New) and Save
      return Actn ? Save(0,1,MemId) : Save('N');
    })
  );
  $('#AMbDel').button().click(function() { // Delete
    var n=$('#AMiPN').val();
    Alert("Click 'Delete' to confirm that you really want to delete "+n+'.',
      '<span class=wng>Delete '+n+'</span>',
      400,
      [{text:'Delete',
        click:function(){
          AClose();
          Call('D',1,[MemId]);
        },
        style:'color:red;font-weight:bold'},
       {text:'Cancel'}],
      0,'important.png');
    return false;
    }
  );
  // Gen PW
  $('#AMbPG').click(GenPw);
  // Selects
  $('#AMdI select').on('change',SetBtns);
  // Set Form Inputs, Tips. Inputs = all incl CBs, Selects. Tips incl for ctrls above form.
  SetForm($('#AMdI input,#AMdI select'), $('#AMdO :input:not(span>input,span>button),#AMdO span[title],#AMdI td[title]'));
  $AMiPW=$('#AMiPW');
  // Record empty Add form
  $('#AMnLev').val(MLevel);
  Tao.ODatA=Data();
  Tao.ODatS=DataStr();
  FormSave(1); // Record Add

  this.OnFocus=function() {
    SetApp();
    if (!this.AMAr1) // only want to do this on the first focus
      SpanFocus.call(GetEl('AMAr1'));
    this.AMAr1=1;
    // Build DName
    $('#AMiGN, #AMiFN, #AMiPN').on('blur',SetDName);
    $('#AMiPN').on('focus',SetDName);
    // Set Level number input attrs. Tips have been built at this point.
    LevelTip();
  }

  this.BackI=function(d) {
    // d: 0: Members select options, 1: Json array of the data for the current member
    SelfId=MemId=+$('#AMeM').html(d[0]).val();;
    SetData(d=$.parseJSON(d[1]));
    ELevel=+$('#AMnLev').val();
  //SetApp(); or following so initial FormLoad() -> SetBtns() disables Delete for Self
    Tao.SetBtnsCBFn=SetDelBtn;
    FormSave(0); // Record Self
    FormSave(2); // Record Edit form
    SetAPs();
    SetDelBtn();
  }

  this.BackF=function(d) { // Edit and Delete member fetched
    // d: Json array of the data for the member
    SetData($.parseJSON(d[0]));
    ELevel=+$('#AMnLev').val();
    FormSave(2); // Record Edit form
    SetLevel(ELevel>MLevel);
  }

  this.BackD=function(d) {
    // OK|1 <Alert Message> R is 1 if unable to delete member due to lack of a valid Admin
    if (R) {
      Alert(d[0],
        '<span class=wng>Unable to Delete Member</span>',
        440,
        [{text:'OK'}],
        0,'important.png');
    }else{
      var el=GetEl('AMeM');
      el.remove(el.selectedIndex);
      FormLoad(0); // Restore Self
      MemId=0;
      BTip.Show($('#AMeM')).focus();
    }
  }

  this.BackN=function(d) {
    // OK|1 <Alert Message | MemId> R is 1 if unable to add member due to a duplicate email
    if (R) {
      Alert(d[0],
        '<span class=wng>Unable to Add Member</span>',
        400,
        [{text:'OK'}],
        function(){$('#AMiE').focus();},'important.png');
    }else{
      $('#AMeM').append(sprintf('<option value=%s>%s</option>', d[0], $('#AMiPN').val()));
      Alert(sprintf("%s has been added.<br>Please note the password you have chosen:<br><span class='b L'>%s</span><br>It will not be displayed again.",$('#AMiPN').val(),$AMiPW.val()),
        'Note Password',
        400,
        [{text:'OK',click:function(){AClose();Revert();}}],
        0,'ok.png');
    }
  } // end of BackN()

  this.BackS=function(d) {
    // OK|1|2|3|4 <Alert Message | 0 | DName	MLevel	MBits when self | MBits when R==4> 1 if unable to save edits due to a duplicate email, 2 on Admin AP rejection, 3 on Admin level edit rejection, 4 lock fail
    if (R) {
      switch (R) {
        case 2: $('#AMcA').prop('checked',true);break;
        case 3: $('#AMnLev').val(ELevel);break;
      }
      Alert(d[0],
        '<span class=wng>Unable to Save Edits</span>',
        430,
        [{text:'OK'}
        ],
        function(){
          switch (R) {
            case 1: $('#AMiE').focus();break;
            default: $('#AMiGN').focus();
          }
        },'important.png');
    }else{
      var p=$AMiPW.val();
      if (p) $AMiPW.val('');
      if (d[0] != 0) { // Self: DName	MLevel	MBits
        d=d[0].split('	')
        DName =  d[0];
        MLevel=ELevel=+d[1];
        MBits = +d[2];
        LevelTip();
        $('#Ag span').html(AName+', '+DName);
        SetMenu();
        FormSave(0); // Record Self
        d=forms[0].CDatA;
        if (d[14] || (d[17] && MLevel==9))
          SetAPs();
        else{
          CloseApp();  // Close if no longer have Members Perm unless Admin Level 9
          return;
        }
      }
      Tao.ODatA=Data();
      Tao.ODatS=DataStr();
      BTip.Show($('#AMeM')).focus(); // need the show 'cos it isn't one of the inputs
      if (p)
        Alert(sprintf("The edits for %s have been saved.<br>Please note the password you have chosen:<br><span class='b L'>%s</span><br>It will not be displayed again.",$('#AMiPN').val(),p),
          'Note Password',
          400,
          [{text:'OK'}],
          0,'ok.png');
    }
    SetBtns();
  } // end of BackS()

  // Apps local functions
  function Action() {
    Actn=+this.value; // -> 0 Add or 1 Edit
    SetApp(); // Before FormSave/Load so that Tao.SetBtnsCBFn is set
    BTip.Hide(); // In case on Password firld where tip changes. Focus() on FormLoad() will then sow the revised tip.
    FormSave(2-Actn); // 0-> 2 or  1-> 1
    FormLoad(Actn+1); // 0-> 1 or  1-> 2
  }

  function SetApp() {
    var cr,cs,tr,ts,o1;
    switch (Actn) {
      case 0:o1=.2; // Add
        cr='Reset'; tr='Click to Reset the form';
        cs='Add';   ts='Click to Add the New Member';
      //$AMiPW.prop('required','required'); // ok in FF tho not '', but not ok in Chrome - can't add after removal. attr works in both.
        $AMiPW.attr('required','');
        BTip.Append($AMiPW.parent(),'<p class=mb0>Required.</p>');
        Tao.SetBtnsCBFn=null;
        SetLevel(false);
        break;
      case 1:o1=1; // Edit
        cr='Revert';tr='Click to Revert to the Saved state of the Member';
        cs='Save';  ts='Click to Save your Edits';
      //$AMiPW.removeProp('required');
        $AMiPW.removeAttr('required');
        BTip.Append($AMiPW.parent(),'<p class=mb0>Do not enter or generate a password unless you want to change the password.</p>');
        Tao.SetBtnsCBFn=SetDelBtn;
        SetLevel(ELevel>MLevel);
        break;
    }
    $('#AMA label').removeClass('b');
    $('#AMA label[for=AMAr'+Actn+']').addClass('b');
    $('#AMM').css('opacity', o1);
    $('#AMeM').prop('disabled', !Actn); // disabled for Add
    BTip.Update(Tao.$RevBtn,tr).find('span').html(cr);
    BTip.Update(Tao.$SavBtn,ts).find('span').html(cs);
    ShowHide(Actn,'#AMbDel');
  }

  function FormSave(f) {
    forms[f].ODatA=Tao.ODatA;
    forms[f].ODatS=Tao.ODatS;
    forms[f].CDatA=Data(); // Current data
    forms[f].FocusEl=Tao.FocusEl;
  }

  function FormLoad(f) {
    Tao.ODatA=forms[f].CDatA; // just for the SetInputs() call which uses Tao.ODatA
    SetInputs();
    Tao.ODatA=forms[f].ODatA;
    Tao.ODatS=forms[f].ODatS;
    Tao.FocusEl=forms[f].FocusEl;
    Tao.DatChanged=Tao.SavBtnState=null; // null so neither true nor false and thus btns will be set in first SetBtns() call
    SetBtns();
    Focus();
  }

  function GenPw(e) {
    e.stopPropagation();
    // 8 to 16 characters, at least one of each of upper case letter A-Z, lower case letter a-z, digit 0-9, and a symbol (e.g. # or % etc incl £) in any order with no spaces or non-keyboard characters
    // From LL PlayI.htm
    // Does between 0 and 1 for random() mean 0 < x < 1 or 0 <= x <= 1 ?
    // On testing millions of times it appears to mean 0 < x < 1
    var i,p,el=GetEl('AMiPW'),chs='123456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ',ca,l;
    el.focus();
    chs=chs+chs+chs+'~`@#$%^&*()_-+={}[]\:;<>,.?/£€';ca=chs.split('');l=ca.length; // 3 times weight to alnum chrs
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
    SetBtns();
    return false;
  }

  function SetDName() {
    var PnEl=GetEl('AMiPN'),GN=GetElVal('AMiGN'),FN=GetElVal('AMiFN'),PN=PnEl.value;
    if (!PN.length || PN==GN || PN==FN)
      PnEl.value=$.trim(GN+' '+FN);
  }

  // Set Level number input tip
  function LevelTip() {
    BTip.Update($('#AMnLev'),'Anyone with the required Access Permission(s) below and an Access Level equal to or greater than an Entity Access Level or other Member Access Level will be able to access and operate on that Entity or Member. You can change this setting to any value between 1 and your Member Access Level of '+MLevel+'.');
  }

  function SetLevel(dB) {
    var ml=dB?ELevel:MLevel; // max->ELevel when being disabled with ELevel>MLevel so that VerifyDat() in SetBtns() and Save() doesn't fail on this field
    $('#AMnLev').prop('disabled',dB).attr({max:ml,'data-max':ml});
  }

  // Disable perm CB if perm not available for self, perms = 8 to 17, unless Admin & Level 9
  function SetAPs() {
    var d=forms[0].CDatA, a=!d[17] || MLevel<9;
    $('#AMdI [type=checkbox]').each(function(i) {$(this).prop('disabled',a && d[i+8]==0);});
  }

  function SetDelBtn(ch) {
    // console.log(ch,SelfId,MemId)
    $('#AMbDel').button((ch || SelfId==MemId || !MemId || !(MBits&AP_Delete))?'disable':'enable');
    $('#AMeM').prop('disabled', ch); // disable Member Select if data changed
  }

} // end of App()
} // end of AM

