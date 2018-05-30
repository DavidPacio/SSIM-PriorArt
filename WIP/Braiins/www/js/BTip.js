/*
 * BTip for Braiins Tips, much simplified derivation of Poshy Tip http://vadikom.com/tools/poshy-tip-jquery-plugin-for-stylish-tooltips/
 * Copyright 2010-2011, Vasil Dinkov, http://vadikom.com/
 * For changes along the way see poshytip.js wip files.
 * 14.06.12
 */

(function($) {
  var TipsA,
    ctipO, // current tip obj
    ftipO, // the focus tip as per the last Show() call from the app
    mtipO, // tip to show after mouse timeout
    $tip = $('#H'),
    $arrow = $tip.find('div.tip-arrow'),
    $inner = $tip.find('div.tip-inner'),
    ShowTimeout,
    HideTimeout,
    opts = { // default settings
      showTimeout:400,// time before showing the tip
      hideTimeout:100,// time before hiding the tip
      offsetX:      8,// offset X pixels from the default position
      showAniTime:300 // show animation duration
    };

  // Update tip position on resize
  function handleWindowResize() {
    if (ctipO && ctipO.active)
      refresh(ctipO);
  }
  $(window).resize(handleWindowResize);

  BTip = { // Global
    Build:function($Tips,$a) { // $a = $ div for mouse events
      TipsA=[];
      $Tips.each(function() {
        // Save the original title
        var o,$this=$(this),title = $this.attr('title');
        title=(title !== undefined ? title : '');
        $this.attr({title:'','data-tipi':TipsA.length}).addClass('tip');
        o={
          $elm:$this,
          active:0,
          ctitle:title,
          otitle:title}
        TipsA.push(o);
      });
      ctipO=TipsA[0]; // ready for the first show call
      $a.on('mouseenter.tip','.tip',mouseEnter);
      $a.on('mouseleave.tip','.tip',mouseLeave);
    },

    Destroy:function($a) {
      BTip.Hide();
      if (TipsA)
        TipsA.forEach(function(o) {
          o.$elm.attr('title', o.otitle);
        });
      $a.off('.tip');
      TipsA=ctipO=ftipO=null;
    },

    Show:function($el) {
      if (TipsA)
        show(ftipO=TipsA[+$el.data('tipi')]);
      return $el;
    },

    Hide:function() {
      clearTimeouts();
      if (ctipO && ctipO.active) {
        ctipO.active=0;
        $tip.css({opacity:1}).animate({opacity:0}, 500, function(){$tip.css({display:'none'});}); // none to prevent tip blocking other mouseenters
      }
    },

    Append:function($el,t) {
      var o=TipsA[+$el.data('tipi')];
      o.ctitle=o.otitle+t;
      return $el;
    },

    Revert:function($el) {
      var o=TipsA[+$el.data('tipi')];
      o.ctitle=o.otitle;
      return $el;
    },

    Update:function($el,c) {
      TipsA[+$el.data('tipi')].ctitle=c;
      return $el;
    }

  }; // end of BTip

  // Local functions

  function show(o) {
    if (!o || o.active) return;
    $tip.stop();
    ctipO.active=0;
    ctipO=o;
    o.active=1;
    refresh(o);
    $tip.css({display:'block'});
    $tip.css({opacity:0}).animate({opacity:1}, opts.showAniTime);
  }

  function refresh(o) {
    $inner.html(o.ctitle);
    // reset position to avoid text wrapping, etc.
    $tip.css({left:0,top:0});
    var l, t, arrow='left',
      $win = $(window),
      win = {
        l: $win.scrollLeft(),
        t: $win.scrollTop(),
        w: $win.width(),
        h: $win.height()
      },
      tipOuterW = $tip.outerWidth(),
      tipOuterH = $tip.outerHeight(),
      elmOffset = o.$elm.offset(), // align to target
      elm = {
        l: elmOffset.left,
        t: elmOffset.top,
        w: o.$elm.outerWidth(),
        h: o.$elm.outerHeight()
      },
      xL = elm.l,     // left edge
      xR = xL + elm.w,// right edge
      yT = elm.t,     // top edge
      yC = yT + Math.floor(elm.h / 2); // v center

    // keep in viewport and calc arrow position
    l = xR + opts.offsetX;
    // console.log('l',l,'tipOuterW',tipOuterW,'win.l',win.l,'win.w',win.w,'l+tipOuterW',l+tipOuterW,'win.l+win.w',win.l+win.w);
    if (l+tipOuterW > win.l+win.w && xL-tipOuterW-opts.offsetX>=win.l) {
    //l = win.l + win.w - tipOuterW; 01.05.12 djh Replaced by following to switch tip to the left on win overflow provided there is room wo overlapping the field, o'wise let tip squash up on the right
      l = xL-tipOuterW-opts.offsetX;
      arrow = 'right';
    }
    t = yC - Math.floor(tipOuterH / 2);
    if (t + tipOuterH > win.t + win.h)
      t = win.t + win.h - tipOuterH;
    else if (t < win.t)
      t = win.t;
    // position and show the arrow image
    $arrow[0].className = 'tip-arrow tip-arrow-' + arrow;
    $tip.css({left:l, top:t});
  }

  // Show tip on mouseenter/leave after timeout with mtipO the tip to show
  mouseShow=function() {
    mtipO ? show(mtipO) : BTip.Hide();
  }

  function mouseEnter() {
    clearTimeouts();
    mtipO=TipsA[this.dataset.tipi]; // here use of dataset works. this passed from jQuery?
    ShowTimeout = setTimeout(mouseShow, opts.showTimeout);
  }

  function mouseLeave() {
    clearTimeouts();
    mtipO=ftipO;
    HideTimeout = setTimeout(mouseShow, opts.hideTimeout);
  }

  function clearTimeouts() {
    clearTimeout(ShowTimeout);
    clearTimeout(HideTimeout);
  }

})(jQuery)
