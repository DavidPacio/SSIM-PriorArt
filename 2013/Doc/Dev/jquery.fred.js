// Via http://starter.pixelgraphics.us/
(function($){
    $.Fred = function(el, options){
        // To avoid scope issues, use 'base' instead of 'this'
        // to reference this class from internal events and functions.
        var base = this;
        
        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;
        
        // Add a reverse reference to the DOM object
        base.$el.data("Fred", base);
        
        base.init = function(){
            base.options = $.extend({},$.Fred.defaultOptions, options);
            
            // Put your initialization code here
        };
        
        // Sample Function, Uncomment to use
        // base.functionName = function(paramaters){
        // 
        // };
        
        // Run initializer
        base.init();
    };
    
    $.Fred.defaultOptions = {
    };
    
    $.fn.fred = function(options){
        return this.each(function(){
            (new $.Fred(this, options));
        });
    };
    
})(jQuery);
