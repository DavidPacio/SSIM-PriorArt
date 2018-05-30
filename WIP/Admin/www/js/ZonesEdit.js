/*
/js/ZonesEdit.js

07.04.11 djh Started

*/

function Init() {
  F = document.forms[0]
  Post('ZonesEdit.php','I',InitBack)
}

function InitBack(d) { // Also New
  Back(d,1) // Body of table
  if (R == 1) {
    FieldsQA.prop('disabled', false).removeClass('grey7')
    alert(Rd[0]) // Duplicate Ref or AllowDims error
  }else {
    $('#ETbody').html(Rd[0])
    FieldsQA = $('form input:text,form select').keydown(FoTrapTabOrEnter).change(Change)
    $('#NewB').click(Add)
  }
  $('#nr').focus()
}

/*
function Nop() {
} */

function Change() {
  je=$(this)
  var id = je.attr('id'), v = $.trim(je.val())
  if (id.contains('r'))
    v = v.replace(/\s/g,'') // remove internal (all) ws
  je.val(v)
  if (!id.contains('n'))
    Post('ZonesEdit.php','S'+id+''+v, ChangeBack)
}

function ChangeBack(d) {
  Back(d,1)
  if (R)
    alert(Rd[0])
  else if (Rd[0]) // only for AllowDims
    je.val(Rd[0])
}

function Add(evt) {
  evt.preventDefault();
  var nrv=$('#nr').val()
  if (nrv > '') {
    FieldsQA.prop('disabled', true).addClass('grey7')
    Post('ZonesEdit.php','N'+nrv+''+$('#ns').val()+''+$('#na').val()+''+$('#nd').val(), InitBack)
  }
}
