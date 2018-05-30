/*
Script: Bensure.js Braiins version of Ensure.js (Omar AL Zabir - http://msmvps.com/blogs/omar) without:
 - the MicrosoftAJAX.js and Prototype-1.6.0.js options
 - removed html and css loading which we don't use, leaving only js
 - data.test option
 - scope option
 - loadedUrls option as if this is called we want the file to be loaded, tho leave head check for now.
 - removed own browser detection

ToDo
----
Tidy it up more. See jqGrid grid.loader.js
Remove delegation, cloning, new, ....
Fix error handling

Ensure library
  A tiny javascript library that provides a handy function "ensure" which allows you to load
  Javascript, CSS on-demand and then execute your code. Ensure ensures that relevent
  Javascript and HTML snippets are already in the browser DOM before executing your code
  that uses them.

  To download last version of this script use this link: <http://www.codeplex.com/ensure>

Compatibility:
  FireFox - Version 2 and 3
  Internet Explorer - Version 6 and 7
  Opera - 9 (probably 8 too)
  Safari - Version 2 and 3
  Konqueror - Version 3 or greater

Dependencies:
  <jQuery.js>

Credits:
  - Global Javascript execution - <http://webreflection.blogspot.com/2007/08/global-scope-evaluation-and-dom.html>

License:
  Copyright (C) 2008 Omar AL Zabir - http://msmvps.com/blogs/omar
*/

(function(){

window.ensure = function(data, callback) {
  new ensureExecutor(data, callback);
}

// ensureExecutor is the main class that does the job of ensure.
window.ensureExecutor = function(data, callback) {
  this.data = this.clone(data)
  this.callback = callback
//this.loadStack = []

  //if ( data.js && data.js.constructor != Array ) this.data.js = [data.js];
  //if ( typeof data.js == "undefined" ) this.data.js = []; // We always call ensure with js defined as an array

  data.error = function(a,b) { alert(a+' '+b) }

  this.init();
  this.load();
}

window.ensureExecutor.prototype = {
    init : function() {
      // Fetch Javascript using Framework specific library
      this.getJS   = HttpLibrary.loadJavascript_jQuery;
      this.httpGet = HttpLibrary.httpGet_jQuery;
    },
    getJS : function(data) {
      // abstract function to get Javascript and execute it
    },
    httpGet : function(url, callback) {
      // abstract function to make HTTP GET call
    },
    load : function() {
      this.loadJavascripts(this.delegate( function() {
        this.callback();
      } ) )
    },
    loadJavascripts : function(complete) {
      var scriptsToLoad = this.data.js.length;
      if ( 0 === scriptsToLoad ) return complete();

      this.forEach(this.data.js, function(href) {
        if (this.isTagLoaded('script', 'src', href))
          scriptsToLoad --;
        else{
          this.getJS({
            url:     href,
            success: this.delegate(function(content) {
              scriptsToLoad --;
            }),
            error: this.delegate(function(msg) {
              scriptsToLoad --;
              if (typeof this.data.error == "function") this.data.error(href, msg);
            })
          });
        }
      });

      // wait until all the external scripts are downloaded
      this.until({
        test:     function() { return scriptsToLoad === 0; },
        delay:    50,
        callback: this.delegate(function() {
          complete();
        })
      });
    },

    clone : function(obj) {
      var cloned = {};
      for ( var p in obj ) {
        var x = obj[p];
        if ( typeof x == "object" ) {
          if ( x.constructor == Array ) {
            var a = [];
            for ( var i = 0; i < x.length; i++ ) a.push(x[i]);
            cloned[p] = a;
          }else{
            cloned[p] = this.clone(x);
          }
        }else
          cloned[p] = x;
      }
      return cloned;
    },

    forEach : function(arr, callback) {
      var self = this;
      for ( var i = 0; i < arr.length; i++ )
        callback.apply(self, [arr[i]]);
    },

    delegate : function( func, obj ) {
      var context = obj || this;
      return function() { func.apply(context, arguments); }
    },
    until : function(o) { // /* o = { test: function(){...}, delay:100, callback: function(){...} } */)
      if ( o.test() === true ) o.callback();
      else window.setTimeout( this.delegate( function() { this.until(o); } ), o.delay || 50);
    },
    isTagLoaded : function(tagName, attName, value) {
      // Create a temporary tag to see what value browser eventually
      // gives to the attribute after doing necessary encoding
      var tag = document.createElement(tagName);
      tag[attName] = value;
      var tagFound = false;
      var tags = document.getElementsByTagName(tagName);
      this.forEach(tags, function(t) {
        if ( tag[attName] === t[attName] ) { tagFound = true; return false }
      });
      return tagFound;
    }
}

//var userAgent = navigator.userAgent.toLowerCase();

// HttpLibrary is a cross browser, cross framework library to perform common operations
// like HTTP GET, injecting script into DOM, keeping track of loaded url etc. It provides
// implementations for various frameworks including jQuery, MSAJAX or Prototype
var HttpLibrary = {
  /*browser : {
      version: (userAgent.match( /.+(?:rv|it|ra|ie)[\/: ]([\d.]+)/ ) || [])[1],
      safari: /webkit/.test( userAgent ),
      opera: /opera/.test( userAgent ),
      msie: /msie/.test( userAgent ) && !/opera/.test( userAgent ),
      mozilla: /mozilla/.test( userAgent ) && !/(compatible|webkit)/.test( userAgent )
    }, */
    //loadedUrls : {},

    //isUrlLoaded : function(url) {
    //  return false; // djh HttpLibrary.loadedUrls[url] === true;
    //},
    //unregisterUrl : function(url) {
    //  HttpLibrary.loadedUrls[url] = false;
    //},
    //registerUrl : function(url) {
    //  HttpLibrary.loadedUrls[url] = true;
    //},
    createScriptTag : function(url, success, error) {
      var scriptTag = document.createElement("script");
      scriptTag.setAttribute("type", "text/javascript");
      scriptTag.setAttribute("src", url);
      scriptTag.onload = scriptTag.onreadystatechange = function() {
        if ( (!this.readyState || this.readyState == "loaded" || this.readyState == "complete") ) {
        success();
      }
    };

    scriptTag.onerror = function() {
      error(url + " failed to load");
    };

    var head = HttpLibrary.getHead();
      head.appendChild(scriptTag);
    },
    getHead : function() {
      return document.getElementsByTagName("head")[0] || document.documentElement
    },
    globalEval : function(data) {
      var script = document.createElement("script");
      script.type = "text/javascript";
    //if ( HttpLibrary.browser.msie )
      if ( $.browser.msie )
        script.text = data;
      else
        script.appendChild( document.createTextNode( data ) );

      var head = HttpLibrary.getHead();
      head.appendChild( script );
    //head.removeChild( script );
    },
    loadJavascript_jQuery : function(data) {
    //if (HttpLibrary.browser.safari) {
      if ($.browser.safari) {
        return jQuery.ajax({
          type:    "GET",
          url:     data.url,
          data:    null,
          success: function(content) {
            HttpLibrary.globalEval(content);
            data.success();
          },
          error: function(xml, status, e) {
            if ( xml && xml.responseText )
              data.error(xml.responseText);
            else
              data.error(url +'\n' + e.message);
          },
          dataType: "html"
        });
      }else // not Safari
        HttpLibrary.createScriptTag(data.url, data.success, data.error);
    },
    httpGet_jQuery: function(data) {
      return jQuery.ajax({
      type:    "GET",
      url:     data.url,
      data:    null,
      success: data.success,
      error:   function(xml, status, e) {
        if ( xml && xml.responseText )
          data.error(xml.responseText);
        else
          data.error("Error occured while loading: " + url +'\n' + e.message);
      },
      dataType: data.type || "html"
    });
    }
};

})()
