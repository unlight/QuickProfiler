window.addEventListener('load', function(){
	
	var QuickProfiler = document.getElementById('QuickProfiler');
	
	document.addEventListener('keydown', function(event) {
		if (event.keyCode == 192) {
			if (HasClass(QuickProfiler, 'None')) {
				RemoveClass(QuickProfiler, 'None');
				Cookie('QuickProfilerVisible', 1);
			} else {
				Cookie('QuickProfilerVisible', 0);
				AddClass(QuickProfiler, 'None');
			}
		} else if (event.keyCode == 27) {
			if (!HasClass(QuickProfiler, 'None')) {
				AddClass(QuickProfiler, 'None');
				Cookie('QuickProfilerVisible', 0);
			}
		}
	});
	
	var HasClass = function(Element, ClassName) {
		return Element.className.match(new RegExp('(\\s|^)'+ClassName+'(\\s|$)'));
	}
	
	var AddClass = function(Element, ClassName) {
		var Expr = new RegExp("(^|\\s)" + ClassName + "(\\s|$)", "g");
		if (HasClass(Element, ClassName)) return;
		Element.className = (Element.className + " " + ClassName).replace(/\s+/g, " ").replace(/(^ | $)/g, "");
	};
 
	var RemoveClass = function(Element, ClassName) {
		var Expr = new RegExp("(^|\\s)" + ClassName + "(\\s|$)", "g");
		Element.className = Element.className.replace(Expr, "$1").replace(/\s+/g, " ").replace(/(^ | $)/g, "");
	};
	
	var RemoveClassAll = function(Collection, ClassName) {
		for (var i = 0; i < Collection.length; i++) RemoveClass(Collection[i], ClassName);
	}
	
	var AddClassAll = function(Collection, ClassName) {
		for (var i = 0; i < Collection.length; i++) AddClass(Collection[i], ClassName);
	}
	
	var Tab;
	var Tabs = document.querySelectorAll("#QuickProfiler div.Tab");
	var Panels = document.querySelectorAll("#QuickProfiler div.Panel");
	
	if (!Cookie('QuickProfilerPanel')) RemoveClass(Panels[0], 'None');
	
	for (var i = 0; i < Tabs.length; i++) {
		Tabs[i].addEventListener('click', function(){
			RemoveClassAll(Tabs, 'Active');
			AddClass(this, 'Active');
			var Panel = document.querySelector('#QuickProfiler div.Panel.' + this.id);
			AddClassAll(Panels, 'None');
			RemoveClass(Panel, 'None');
			Cookie('QuickProfilerPanel', this.id);
		});
	}

});

// http://plugins.jquery.com/files/jquery.cookie.js.txt
function Cookie(name, value, options) {
    if (typeof value != 'undefined') { // name and value given, set cookie
        options = options || {};
        if (value === null) {
            value = '';
            options.expires = -1;
        }
        var expires = '';
        if (options.expires && (typeof options.expires == 'number' || options.expires.toUTCString)) {
            var date;
            if (typeof options.expires == 'number') {
                date = new Date();
                date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
            } else {
                date = options.expires;
            }
            expires = '; expires=' + date.toUTCString(); // use expires attribute, max-age is not supported by IE
        }
        // CAUTION: Needed to parenthesize options.path and options.domain
        // in the following expressions, otherwise they evaluate to undefined
        // in the packed version for some reason...
        var path = options.path ? '; path=' + (options.path) : '';
        var domain = options.domain ? '; domain=' + (options.domain) : '';
        var secure = options.secure ? '; secure' : '';
        document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
    } else { // only name given, get cookie
        var cookieValue = null;
        if (document.cookie && document.cookie != '') {
            var cookies = document.cookie.split(';');
            for (var i = 0; i < cookies.length; i++) {
                var cookie = jQuery.trim(cookies[i]);
                // Does this cookie string begin with the name we want?
                if (cookie.substring(0, name.length + 1) == (name + '=')) {
                    cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
                    break;
                }
            }
        }
        return cookieValue;
    }
};