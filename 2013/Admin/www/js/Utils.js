// Admin.js
// 07.04.11

$(function(){
  // Init
  $('.topB').click(function() {
    scroll(0,0)
  })
  El=0
  //$('input').blur(function(){$(this).val($.trim($(this).val()))})
  $.ajaxSetup({
     type: "POST",
     complete: Complete,
     error: AjError,
     timeout: 15000,
     dataType: 'text'
   });
  if (self.Init)
    Init()
})

//////////
// Ajax // + $.ajaxSetup() call in Init. djh?? This should be changed to match the main site Post() fns.
//////////
// Post to initiate Ajax Data Exchange with server
// u   = url to post to
// dat = the data being sent incl op code as 1st character
// fn  = callback fn
function Post(u, dat, fn) {
  if (dat.contains('&'))
    dat = encodeURIComponent(dat)
  $.post('./srv/'+u, 'Dat=' + dat, fn)
}

// Process Ajax return where n is # of data fields
function Back(d,n) {
  Rd = d.split('') // Returned data
  R=IsNum(Rd.pop())
  //console.log('R='+R)
  if (Rd.length!=n)
    R=-1
  if (R<0) { // Error
    switch (R) {
      case -1: // NOT_LOGGEDIN
        // left to the calling fn to handle
        break
      //case -2: // NOP_LOCK op not able to be performed because of lock
      //  alert(Rd[0])
      //  break
      default: // Any other error
        alert('Sorry, an error ' + Rd[0] + ' has occurred, and been reported to Braiins for correction. Please try again later.')
    }
  } // else R >= 0 = OK or +ve flag return
}

function Complete() { // Run on completion of an Ajax call
  if (self.After)
    After()
}

function AjError() {
  alert('Sorry there has been no response from the server for 15 seconds, which probably indicates an error, possibly a communication failure.\n\nPlease try again.')
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

function FoTrapTabOrEnter(evt) {
  var k=evt.which
  if (k==9 || k == 13) {
    FieldsQA[Roll(FieldsQA.index(this),1-2*evt.shiftKey,FieldsQA.length)].focus()
    evt.preventDefault();
    return false;
  }
  return true
}
function Roll(i,inc,len) {
  while(1) {
    i+=inc;
    if (i < 0) i = len-1
    if (i == len) i = 0
    if ($(FieldsQA[i]).prop('disabled') == false)
      return i
  }
}

