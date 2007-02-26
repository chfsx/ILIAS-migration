/**
 * ILIAS Open Source
 * --------------------------------
 * Implementation of ADL SCORM 2004
 * 
 * This program is free software. The use and distribution terms for this software
 * are covered by the GNU General Public License Version 2
 * 	<http://opensource.org/licenses/gpl-license.php>.
 * By using this software in any fashion, you are agreeing to be bound by the terms 
 * of this license.
 * 
 * You must not remove this notice, or any other, from this software.
 * 
 * PRELIMINARY EDITION
 * This is work in progress and therefore incomplete and buggy ...
 * 
 * Derived from ADL Pseudocode
 *   
 * Content-Type: application/x-javascript; charset=ISO-8859-1
 * Modul: Player User Interface Methods
 *  
 * @author Alfred Kohnert <alfred.kohnert@bigfoot.com>
 * @version $Id$
 * @copyright: (c) 2007 Alfred Kohnert
 */ 


function Gui(config, langstrings) 
{ 	
	/**
	 * @param {object} associative array with language data to be used by 
	 * 	"translate" function, one array per language
	 */	

	// Inner Functions
	
	var me = this;
	
	function setOuterHTML(elm, markup)
	{
		if (window.ScriptEngine && window.ScriptEngine()==='JScript') 
		{
			return elm.outerHTML = markup;
		}
		else
		{
			var range = elm.ownerDocument.createRange();
			range.setStartBefore(elm);
			var fragment = range.createContextualFragment(markup);
			elm.parentNode.replaceChild(fragment, elm);
		}
	}
		
	this.setInfo = function (name, values) 
	{
		var elm = this.all('introLabel');
		var txt = this.translate(name, values);
		elm.innerHTML = top.status = txt;		
	}; 
	
	this.trim = function (str) 
	{
		return str.replace(/^\s+|\s+$/g, "");
	}; 

	this.addClass = function (elm, name) 
	{
		if (!me.hasClass(elm, name)) elm.className = me.trim(elm.className + " " + name);
	}; 

	this.hasClass = function (elm, name) 
	{
		return (" " + elm.className + " ").indexOf(" " + name + " ")>-1;
	}; 

	this.removeClass = function (elm, name) 
	{
		elm.className = me.trim((" " + elm.className + " ").replace(name, " "));
	}; 
	
	this.show = function (newValue) 
	{
		var tableDisplay = document.body.currentStyle ? 'block' : 'table';
		this.all('introTable').style.display = newValue ? 'none' : tableDisplay;
		this.all('mainTable').style.display = newValue ? tableDisplay : 'none';
	};
	
	/**
	 * implements a method to call up a resource into user view
	 * @param {string} required, url to be opened
	 * @param {function} optional, callback function called when delivery was 
	 * 	successfully launched (a window reference is given to that function)
	 */
	this.deliver = function (id, url, hideLMSUI, callback) 
	{
		if (url.indexOf(":")===-1) 
		{
			url = config.base + url;
		}
		var elm = window.document.getElementById("tdResource");
		var h = elm.clientHeight-20;
		elm.innerHTML = '<iframe frameborder="0" name="frmResource" src="' + url + 
			'"  style="width: 100%; height:' + h + '" height="' + h + '"></iframe>';
		if (typeof(callback) === 'function') 
		{
			callback(window.frames.frmResource);
		}
		if (me.currentElm) 
		{
			me.removeClass(me.currentElm, "current");
		}
		me.currentElm = me.all("tre" + id);
		if (me.currentElm)
		{
			me.addClass(me.currentElm, "current");
		}
		me.all('navContinue').style.backgroundColor = 'continue' in hideLMSUI ? 'orange' : '';
		me.all('navPrevious').style.backgroundColor = 'previous' in hideLMSUI ? 'orange' : '';
		me.all('navAbandon').style.backgroundColor = 'abandon' in hideLMSUI ? 'orange' : '';
		me.all('navAbandonAll').style.backgroundColor = 'abandonAll' in hideLMSUI ? 'orange' : '';
		me.all('navExit').style.backgroundColor = 'exit' in hideLMSUI ? 'orange' : '';
		me.all('navExitAll').style.backgroundColor = 'exitAll' in hideLMSUI ? 'orange' : '';
		me.all('navSuspendAll').style.backgroundColor = 'suspendAll' in hideLMSUI ? 'orange' : '';
		// previous continue exit exitAll abandon abandonAll suspendAll
	};

	/**
	 * implements a method to take a resource out of user view
	 * @param {function} callback function called when undelivery has been
	 * 	executed
	 */	
	this.undeliver = function (callback) 
	{
		var elm = me.all("tdResource");
		if (elm)
		{
			elm.innerHTML = 'undelivered ...';
		}
		if (typeof(callback) === 'function') 
		{
			callback();
		}  
	};
	
	/**
	 * multifunctional function object used as function to retrieve one 
	 * HTMLElement by id, also used as an array to store javascript data 
	 * referring to an HTMLElement e.g. all[all('anyID')] = someDataObject
	 * javascript data should not be bound to HTMLElements directly	 
	 *	@param string id of an HTMLElement to look for. 
	 *	@param object Window reference, defaults to current window. Used to 
	 *		manage objects from different windows in one collection
	 *	@return {HTMLElement}	 
	 */	
	var all = this.all = function (s, w) 
	{
		if (!w) 
		{
			w = window;
		}
		return w.document ? w.document.getElementById(s) : null;
	};
	
	/**
	 * shows activity tree in one or more appropriate controls
	 * iterates through activity tree and writes items	  
	 * @param {object} Top of the activity tree (current organization) 
	 * @param {string} required, base href for package links, may be an empty string 
	 */
	this.render = function (organization) 
	{
		// TODO isvisible
		// TODO icons
		// TODO status
		// TODO toggle
		// TODO scrollIntoView
		// TODO readonly mit getItems([id, title, href, visible])
		
		/*
		basehref = organization.base && organization.base.indexOf(":")===-1 
		 	? config.base + organization.base 
		 	: organization.base; 
		*/
		
		// to build visual hierarchy by incremental spacing for OPTIONs
		var TAB = "&nbsp;" 

		// arrays to hold html strings for each view
		var listView = [];
		var treeView = [];
		var reportView = [];
		var stripView = [];

		/**
		 * recursive walk through tree
		 * modifies the arrays defined above
		 * @param {array} required, children of a manifest item
		 */		
		function walk(items) 
		{
			var tabs = typeof arguments[1] === "string" ? arguments[1] : '';
			for (var i=0, ni=items.length; i<ni; i+=1) 
			{
				var item = items[i];
				var classname = item.href ? 'content' : 'block';
				var href = "#"; // ????
				if (item.isvisible!=="false")
				{
					// fill the different views
					listView.push('<option class="nde ' + classname + '" id="lst' + 
						item.id + '">' + tabs + " " + item.title + '</option>');
					reportView.push('<tr><td>' + tabs + (tabs.length + 1) + 
						'</td><td width="90%"><a class="nde ' + classname + '" id="rpt' +
						item.id + '" href="' + href + '">' + item.title + 
						'</a></td><td>x</td></tr>');
					stripView.push('<td><a class="nde ' + classname + '" id="str' + 
						item.id + '" href="' + href + '">' + item.title + '</a></td>');
					treeView.push('<a class="nde ' + classname + '" id="tre' + 
						item.id + '" href="' + href + '">' + item.title + '</a>');
				} 
				if (item.item) 
				{
					treeView.push('<div>');
					walk(item.item, tabs + TAB); // RECURSION
					treeView.push('</div>');
				}
			}
		} // end walk
		
		me.all("mainTitle").innerHTML = organization.title; 
		
		// now run recursion
		walk(organization.item, '');
		
		setOuterHTML(this.all('listView'), '<select id="listView">' + listView.join('\n') + '</select>');

		this.all('treeView').innerHTML = treeView.join('\n');
	};

	/**
	 * localizes a string according to language settings
	 * @param string 
	 * @param object key values pairs to be replaced in the retrieved string used
	 * 	like in php templates "{name}"
	 */	
	this.translate = function (key, params) 
	{
		var value = key in langstrings ? langstrings[key] : key;
		if (typeof params === 'object') 
		{
			value = String(value).replace(/\{(\w+)\}/g, function (m) 
			{
				return m in params ? params[m] : m}
			);
		} 
		return value; 
	} ,
	
	/**
	 * Cross browser event registration
	 * @param object Object receiving the event
	 * @param string Event name, e.g. 'click'
	 * @param function Event handling function
	 */	
	this.attachEvent = function (obj, name, func) 
	{
		if (window.Event) 
		{
			obj.addEventListener(name, func, false);
		} 
		else if (obj.attachEvent) 
		{
			obj.attachEvent('on'+name, func);
		} 
		else 
		{
			obj[name] = func;
		}
	};

	/**
	 * Cross browser event un-registration for events attached with attachEvent
	 * @param object Object receiving the event
	 * @param string Event name, e.g. 'click'
	 * @param function Event handling function
	 */	
	this.detachEvent = function (obj, name, func) 
	{
		if (window.Event) 
		{
			obj.removeEventListener(name, func, false);
		} 
		else if (obj.attachEvent) 
		{
			obj.detachEvent('on'+name, func);
		} 
		else 
		{
			obj[name] = '';
		}
	};	
	
	this.stopEvent = function (e) 
	{
		if (e.preventDefault) 
		{ 
			e.preventDefault(); 
			e.stopPropagation(); 
		} 
		else 
		{
			e.returnValue = false;
			e.cancelBubble = true;
		}
	};

	
	/// for debugging only

	var onSequencerDebugStack;
	this.onSequencerDebug = function (msg, cll) 
	{
		var s = '-----------------------------' 
		if (msg=="exec") onSequencerDebugStack = []; 
		for (var i=onSequencerDebugStack.length-1; i>-1; i--)
		{
			if (onSequencerDebugStack[i]==cll) 
			{
				break;
			}
		}
		if (i>-1) 
		{
			onSequencerDebugStack = onSequencerDebugStack.slice(0, i);
		}
		onSequencerDebugStack.push(cll);
		var elm = window.document.getElementById("seqlog");
		var num = "[" + ((elm.options.length+1)/1000).toFixed(3).substr(2) + "] ";
		elm.insertBefore(elm.ownerDocument.createElement("option"), elm.firstChild).text =  
			num + s.substr(0, onSequencerDebugStack.length-1) + msg;
	};
	
	this.onAPIDebug = function (diagnostic, returnValue, errCode, errInfo, cmiItem) 
	{
		try 
		{
			var elm = document.getElementById("apilog");
			var num = "[" + ((elm.options.length+1)/1000).toFixed(3).substr(2) + "] ";
			var opt = elm.ownerDocument.createElement("option");
			elm.insertBefore(opt, elm.firstChild).text = num + diagnostic;
			var elm = document.getElementById("cmidata");
			elm.value = Remoting.toJSONString(cmiItem, " ");
		} 
		catch (e) 
		{
			alert("onAPIDebug error " + e)
		}
	};

}


function chkWebContent_click(newState) 
{
	parent.document.cookie = newState;
	if (newState) btnWebContent_onclick();
}

