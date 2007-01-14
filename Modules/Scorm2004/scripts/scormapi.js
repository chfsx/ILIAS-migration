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
 * Note: This code derives from other work by the original author that has been
 * published under Common Public License (CPL 1.0). Please send mail for more
 * information to <alfred.kohnert@bigfoot.com>.
 * 
 * You must not remove this notice, or any other, from this software.
 * 
 * PRELIMINARY EDITION
 * This is work in progress and therefore incomplete and buggy ...
 * 
 * Derived from OpenPALMS Humbaba
 * An ECMAScript/JSON Implementation of the ADL-RTE Version API.
 * 
 * Content-Type: application/x-javascript; charset=ISO-8859-1
 * Modul: ADL SCORM Generic Runtime API Implementation
 *  
 * @author Alfred Kohnert <alfred.kohnert@bigfoot.com>
 * @version $Id$
 * @copyright: (c) 2005-2007 Alfred Kohnert
 */ 

/**
 * ADL SCORM RTE API constructor 
 * @param {object} required; a copy (not a reference) of item in CmiDataStore
 * 	it has to be of prototype CMIData13
 * @nonSco {boolean} in non-sco or asset mode we only support the 
 * 	init and the terminate method as an extension to ADL SCORM 
 * @param {function} required; listener for commit, to update CmiDataStore
 * 	a function that take modified CMIData13 as input 
 * @param {function} optional; listener for debug information generated by API
 * 	should of following signature 
 * 		function(methodName, methodArgs, methodReturn, error, diagnostic, debugMessage)
 * 			methodReturn can be passed through or altered for API return 
 **/
function OP_SCORM_RUNTIME(cmiItem, nonSco, onCommit, onDebug)  
{ 
 
	// private constants: API states
	var NOT_INITIALIZED = 0;
	var RUNNING = 1;
	var TERMINATED = 2;

	// private constants: permission
	var READONLY  = 1;
	var WRITEONLY = 2;
	var READWRITE = 3;

	// private properties
	var state = NOT_INITIALIZED;
	var error = 0;
	var diagnostic = '';
	var dirty = false;
	var msec = null; 
	var me = this; // reference to API for use in methods
	var events = 
	{
		'Initialized' : [], 
		'Terminated' : [], 
		'Committed' : []
	};
	
	// possible public methods
	var methods = 
	{
		'Initialize' : Initialize,
		'Terminate' : Terminate,
		'GetValue' : GetValue,
		'SetValue' : SetValue,
		'Commit' : Commit,
		'GetLastError' : GetLastError,
		'GetErrorString' : GetErrorString,
		'GetDiagnostic' : GetDiagnostic,
		'LMSAddEventListener' : AddEventListener,
		'LMSRemoveEventListener' : RemoveEventListener
	};
		
	// bind public methods 
	for (var k in me.methods) 
	{
		me[k] = methods[k];
	}

	// implementation of public methods
	
	// public error code property getter
	function GetLastError() 
	{
		return error.toString();
	}

	/**
	 * public error description property getter
	 * error codes and descriptions (see "SCORM Run-Time Environment
	 * Version 1.3" on www.adlnet.org)
	 * @param {string} error number must be string!
	 */	 
	function GetErrorString(param) 
	{
		if (typeof param !== "string") 
		{
			return setReturn(201, 'GetError param must be empty string', '');
		}
		var e = me.errors[param]; 
		return e ? e.message : '';
	}

	/**
	 * public error details getter 
	 * my be useful in debugging
	 * @param {string} required; but not evaluated in this implementation 
	 */	 
	function GetDiagnostic(param) 
	{
		return typeof(param)==='string' && error ? diagnostic : '';
	}

	/**
	 * Open connection to data provider 
	 * @access private, but also bound to 'this'
	 * @param {string} required; must be '' 
	 */	 
	function Initialize(param) 
	{
		setReturn(-1, 'Initialize(' + param + ')');
		if (param!=='') 
		{
			return setReturn(201, 'param must be empty string', 'false');
		}
		switch (state) 
		{
			case NOT_INITIALIZED:
				dirty = false;
				if (cmiItem instanceof Object) 
				{
					state = RUNNING;
					msec = (new Date()).getTime();
					doEvents('Initialized');
					return setReturn(0, '', 'true');
				} 
				else 
				{
					return setReturn(101, '', 'false');
				}
				break;
			default:
				return setReturn(103, '', 'false');
		}
		setReturn(103, '', 'false');
	}	

	/**
	 * Sending changes to data provider 
	 * @access private, but also bound to 'this'
	 * @param {string} required; must be '' 
	 */	 
	function Commit(param) 
	{
		setReturn(-1, 'Commit(' + param + ')');
		if (param!=='') 
		{
			return setReturn(201, 'param must be empty string', 'false');
		}
		switch (state) 
		{
			case NOT_INITIALIZED:
				return setReturn(142, '', 'false');
			case TERMINATED:
				return setReturn(143, '', 'false');
			case RUNNING:
				var returnValue = onCommit(cmiItem);
				if (returnValue) 
				{
					dirty = false;
					doEvents('Committed');
					return setReturn(0, '', 'true');
				} 
				else
				{
					return setError(301, '', 'false');
				}
		}
	}

	/**
	 * Close connection to data provider 
	 * @access private, but also bound to 'this'
	 * @param {string} required; must be '' 
	 */	 
	function Terminate(param) {
		setReturn(-1, 'Terminate(' + param + ')');
		if (param!=='') 
		{
			return setReturn(201, 'param must be empty string', 'false');
		}
		switch (state) 
		{
			case NOT_INITIALIZED:
				return setReturn(112, '', 'false');
			case TERMINATED:
				return setReturn(113, '', 'false');
			case RUNNING:
				me.onTerminate(getValue, setValue, msec);
				setReturn(-1, 'Terminate(' + param + ') [after wrapup]');
				var returnValue = Commit('');
				state = TERMINATED;
				doEvents('Terminated');
				return setReturn(0, '', returnValue);
		}
	}
	
	/**
	 * Read data element entry 
	 * @access private, but also bound to 'this'
	 * @param {string} required; must be valid cmi element string 
	 */	 
	function GetValue(sPath) 
	{
		setReturn(-1, 'GetValue(' + sPath + ')');
		switch (state) 
		{
			case NOT_INITIALIZED:
				return setReturn(122, '', '');
			case TERMINATED:
				return setReturn(123, '', '');
			case RUNNING:
				if (typeof(sPath)!=='string') 
				{
					return setReturn(201, 'must be string', '');
				}
				try 
				{
					return setReturn(0, '', getValue(sPath, false));
				} catch (e) 
				{
					return setReturn(101, e.description, 'false');
				}
				doEvents('Terminated');
		}	
	}
	
	/**
	 * Read data element entry 
	 * @access private
	 * @param {string} required; must be valid cmi element string 
	 * @param {boolean} optional; if true all permissions are assumed as readwrite 
	 */
	function getValue(sPath, bSudo) 
	{
		var aPath = sPath.split(".");
		var i, ni, j, nj, k, n;
		var subPath, value;
		var aTyp = me.models[aPath[0]];
		if (!aTyp) 
		{
			return setError(301, "first element ne cmi", "");
		}
		value = data[sPath];
		for (i=1, ni=aPath.length; i<ni; i+=1) 
		{
			n = parseInt(aPath[i]);
			if (!isNaN(n)) // Number 
			{ 
				if (aTyp.type!=Array) 
				{
					return setError(301, "1", "");
				}
			} 
			else if (aPath[i]=="_count") // _count 
			{ 
				if (!aTyp.children) 
				{
					return setError(401, "", "");
				}
				j = 0;
				subPath = aPath.slice(0, i).join('.') + '.';
				for (k in data) 
				{
					if (k.indexOf(subPath)===0) 
					{
						if (parseInt(k.substring(subPath.length))===j) 
						{
							j+=1;
						}
					}
				}
				return j.toString();
			} 
			else if (aPath[i]=="_children") //_children 
			{ 
				if (aTyp.children && i==aPath.length-1) 
				{
					var c = [];
					for (j in aTyp.children) 
					{
						c.push(j);
					}
					return c.join(",");
				} 
				else 
				{
					return setError(401, "element cannot have children", "");
				}
			} 
			else if (i==1 && aPath[i]=="_version") // cmi._version 
			{ 
				return "1.0";
			} 
			else 
			{ // nodeName
				aTyp = aTyp.children[aPath[i]];
				if (!aTyp) 
				{
					return setError(401, "unsupported or unknown element", "");
				} 
				else if (aTyp.permission==WRITEONLY && !bSudo) 
				{
					return setError(405, "", "");
				} 
				else if (aTyp.type==Object || aTyp.type==Array) // complex element 
				{ 				
				} 
				else // terminal Attribute 
				{ 
					if (i < aPath.length-1) 
					{
						return setError((aPath[i+1]==="_count") ? 401 : 401, "this is terminal element", "");
					} 
					else if (value===undefined && aTyp["default"]) 
					{
						return aTyp["default"];
					} 
					else if (value===undefined) 
					{
						return setError(403, "element not initialized", "");
					} 
					else 
					{
						return setError(0, "", value);
					}
				}
			} // else number
		} // for path part
		return setError(0, "", value);
	}	

	/**
	 * Update or create data element entry 
	 * @access private, but also bound to 'this'
	 * @param {string} required; must be valid cmi element string
	 * @param {string} required; must be valid cmi element value
	 */	  
	function SetValue(sPath, sValue) 
	{
		setReturn(-1, 'SetValue(' + sPath + ', ' + sValue + ')');
		switch (state) 
		{
			case NOT_INITIALIZED:
				return setReturn(132, '', '');
			case TERMINATED:
				return setReturn(133, '', '');
			case RUNNING:
				if (typeof(sPath)!=='string') 
				{
					return setReturn(201, 'must be string', '');
				}
				try 
				{
					return setReturn(0, '', setValue(sPath, sValue, false));
				} catch (e) 
				{
					return setReturn(351, e.description, 'false');
				}
		}	
	}
	
	/**
	 * Update or create data element entry
	 * @access private
	 * @param {string} required; must be valid cmi element string 
	 * @param {string} required; must be valid cmi element value
	 * @param {boolean} optional; if true all permissions are assumed as readwrite 
	 */
	function setValue(sPath, sValue, bSudo) 
	{
		var aPath, aTyp, append, i, j, ni, nj, k, n, c, subPath, token;
		aPath = sPath.split(".");
		aTyp = me.models[aPath[0]];
		if (!aTyp) 
		{
			return setError(351, "", "false");
		}
		for (i=1, ni=aPath.length; i<ni; i+=1) 
		{
			token = aPath[i];
			n = parseInt(token);
			if (!isNaN(n)) // number 
			{ 
				if (aTyp.type!=Array) 
				{
					return setError(351, "", "false");
				}
				c = getValue(aPath.slice(0, i).join('.') + '._count');
				if (n>c) 
				{
					return setError(401, aPath[i-1] + "." + n + " not found", "false");
				}
				continue;
			} 
			else 
			{
				aTyp = aTyp.children[token];
			}
			if (!aTyp) 
			{
				return setError(401, "unsupported or unknown element", "false");
			} 
			else if (aTyp.type==Object || aTyp.type==Array) // complex element 
			{ 
				// ITERATION
			} 
			else if (aTyp.permission==READONLY && !bSudo) 
			{
				return setError(404, "", "false");
			} 
			else // terminal attribute 
			{ 
				if (i < aPath.length-1) // no children possible 
				{ 
					return setError(401, "", "false");
				} 
				else if (!aTyp.type.isValid(sValue, aTyp.min, aTyp.maa, aTyp.pattern, aPath.slice(0, i), data)) 
				{
					return setError(407, "value not valid", "false");
				} 
				else 
				{
					data[sPath] = sValue;
					dirty = true;
					return setReturn(0, '', 'true');
				}
			}
		}
		return setReturn(101, '', 'false');
	}	
	
	/**
	 * Append an event
	 * @param {string} required; must be valid event name 
	 * @param {function} required; handler (a function without parameters)
	 * @return {string} true if handler was added 
	 */
	function AddEventListener(type, listener) 
	{
		var event = events[type];
		if (!event) 
		{
			return 'false';
		}
		if (typeof(listener)==='string') 
		{
			listener = window[listener];
		}
		if (typeof(listener)!=='function') 
		{
			return setReturn(0, '', 'false');
		}
		event.push(listener);
		return 'true';
	}

	/**
	 * Delete an event
	 * call with exactly the same parameters as AddEventListener	 
	 * @param {string} required; must be valid event name 
	 * @param {function} required; handler (a function without parameters)
	 * @return {string} true if handler was removed 
	 */
	function RemoveEventListener(type, listener) 
	{
		var event = events[type];
		if (!event) 
		{
			return 'false';
		}
		if (typeof(listener)==='string') 
		{
			listener = window[listener];
		}
		if (typeof(listener)!=='function') 
		{
			return setReturn(0, '', 'false');
		}
		for (var i=event.length-1; i>-1; i-=1) 
		{
			if (event[i]===listener) 
			{
				delete event[i];
				break;
			} 
		}
		return (i>-1).toString();
	}

	/**
	 * run registered listeners on a event
	 * exceptions are caught, so you may not know if an event was successfully 
	 * executed, in debug mode you will be informed by onDebug handler
	 *	@access private
	 * @param {string} required; must be valid event name 
	 */	 
	function doEvents(type) 
	{
		var event = events[type];
		for (var i=event.length-1; i>-1; i-=1) 
		{
			try {
				event[i]();
			} 
			catch(e) 
			{
				setReturn(error, diagnostic);
			}
		};
	}

	/**
	 *	@access private
	 *	@param {number}  
	 *	@param {string}  
	 *	@param {string}  
	 *	@return {string} 
	 */	 
	function setReturn(errCode, errInfo, returnValue) 
	{
		if (errCode>-1 && typeof onDebug === 'function') 
		{
			var newReturnValue = onDebug(diagnostic, returnValue, errCode, errInfo);
			if (returnValue===undefined) 
			{
				// if no returnValue this is for debug only, so do not change an API
				// internal settings
				return; 
			} 
			else (newReturnValue!==undefined) 
			{
				returnValue = newReturnValue;
			} 
		}
		error = errCode;
		diagnostic = (typeof(errInfo)=='string') ? errInfo : '';
		return returnValue;
	}
}
