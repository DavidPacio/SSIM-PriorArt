/*
FnB.js
13.02.11 djh Converted from MooTools to jQuery
*/
function Init() {
  $('#T1').click(function() {Show(1,676,680)}) // 698 720 Collage
  $('#T2').click(function() {Show(2,600,447,1)})
  $('#T3').click(function() {Show(3,600,506)})
  $('#T4').click(function() {Show(4,600,471,1)})
  $('#T5').click(function() {Show(5,600,486,1)})
  $('#T6').click(function() {Show(6,600,447,1)})
  $('#T7').click(function() {Show(7,600,545)})
  $('#T8').click(function() {Show(8,600,447)})
  $('#T9').click(function() {Show(9,600,430,1)})
  $('#T10').click(function(){Show(10,600,510,1)})
  $('#T11').click(function(){Show(11,600,532)})
  $('#T12').click(function(){Show(12,600,444)})
  $('#T13').click(function(){Show(13,600,312,1)})
  $('#T14').click(function(){Show(14,601,484,1)})
  $('#F1').mouseleave(X)
}

function Show(c,w,h,r) {
  w = (w?w:600)
  h = (h?h:447)
  var e=$('#T'+c), pos=e.position(), s = {top:pos.top+'px',left:'',right:'',width:(w+10)+'px',height:(h+40)+'px',display:'block'}, ie = $('#FI')
  ie.attr('src', e.attr('src'))
  ie.attr('width', w)
  ie.attr('height', h)
  ie.attr('alt', e.attr('alt'))
  ie.attr('title', e.attr('alt'))
  if (r) s.right='30px'; else s.left = (pos.left)+'px'
  $('#F1').css(s)
  $('#FH').html(e.alt)
}

function X() {
  $('#F1').hide(500)
}
