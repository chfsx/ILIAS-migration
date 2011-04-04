var ilCOPage =
{
	content_css: '',
	edit_status: false,
	insert_status: false,
	minwidth: 50,
	minheight: 20,
	current_td: "",
	edit_ghost: null,
	drag_contents: [],
	drag_targets: [],

	setContentCss: function (content_css)
	{
		this.content_css = content_css;
	},

	cmdSave: function ()
	{
		var ed = tinyMCE.get('tinytarget');
		this.autoResize(ed);
		this.setEditStatus(false);
		if (ilCOPage.current_td != "")
		{
			ilFormSend("saveDataTable", ed_para, null, null);
		}
		else if (this.getInsertStatus())
		{
			ilFormSend("insertJS", ed_para, null, null);
		}
		else
		{
			ilFormSend("saveJS", ed_para, null, null);
		}
	},

	cmdSpan: function (t)
	{
		var stype = {Strong: '0', Emph: '1', Important: '2', Comment: '3',
			Quotation: '4', Accent: '5'};

		var ed = tinyMCE.get('tinytarget');

		var st_sel = ed.controlManager.get('styleselect');

		// from tiny_mce_src-> renderMenu
		if (st_sel.settings.onselect('style_' + stype[t]) !== false)
			st_sel.select('style_' + stype[t]); // Must be runned after
		this.autoResize(ed);
	},

	cmdCode: function()
	{
		var ed = tinyMCE.get('tinytarget');

tinymce.activeEditor.formatter.register('mycode', {
   inline : 'code'
 });
		//ed.execCommand('Bold');
		ed.execCommand('mceToggleFormat', false, 'mycode');
		//var st_sel = ed.toggleFormat('Bold');
		this.autoResize(ed);
	},

	cmdIntLink: function(b, e)
	{
		var ed = tinyMCE.get('tinytarget');
		ed.focus();
		//ed.selection.setContent('[iln ' + type + '=&quot;' + node_id + '&quot;]' + ed.selection.getContent() + '[/iln]');
		ed.selection.setContent(b + ed.selection.getContent() + e);

		this.autoResize(ed);

	},

	cmdWikiLink: function()
	{
		var ed = tinyMCE.get('tinytarget');
		ed.focus();
		ed.selection.setContent('[[' + ed.selection.getContent() + ']]');
		this.autoResize(ed);
	},

	cmdTex: function()
	{
		var ed = tinyMCE.get('tinytarget');
		ed.focus();
		ed.selection.setContent('[tex]' + ed.selection.getContent() + '[/tex]');
		this.autoResize(ed);
	},

	cmdFn: function()
	{
		var ed = tinyMCE.get('tinytarget');
		ed.focus();
		ed.selection.setContent('[fn]' + ed.selection.getContent() + '[/fn]');
		this.autoResize(ed);
	},

	cmdExtLink: function()
	{
		var ed = tinyMCE.get('tinytarget');
		ed.focus();
		ed.selection.setContent('[xln url=&quot;http://&quot;]' + ed.selection.getContent() + '[/xln]');
		this.autoResize(ed);
	},

	cmdBList: function()
	{
		var ed = tinyMCE.get('tinytarget');
		ed.focus();
		ed.execCommand('InsertUnorderedList', false);
		this.autoResize(ed);
	},

	cmdNList: function()
	{
		var ed = tinyMCE.get('tinytarget');
		ed.focus();
		ed.execCommand('InsertOrderedList', false);
		this.autoResize(ed);
	},

	cmdListIndent: function()
	{
		var ed = tinyMCE.get('tinytarget');
		ed.focus();
		ed.execCommand('Indent', false);
		this.autoResize(ed);
	},

	cmdListOutdent: function()
	{
		var ed = tinyMCE.get('tinytarget');
		ed.focus();
		ed.execCommand('Outdent', false);
		this.autoResize(ed);
	},

	setEditStatus: function(status)
	{
//console.log("set edit status " + status);
		if (status)
		{
//			YAHOO.util.DragDropMgr.lock();
		}
		else
		{
//			YAHOO.util.DragDropMgr.unlock();
		}
		var elements = YAHOO.util.Dom.getElementsByClassName('il_droparea');

		for (k in elements)
		{
			elements[k].style.visibility = 'hidden';
		}
		var obj = document.getElementById('ilPageEditModeMenu');
		if (obj) obj.style.visibility = 'hidden';
		var obj = document.getElementById('ilPageEditActionBar');
		if (obj) obj.style.visibility = 'hidden';
		var obj = document.getElementById('ilPageEditLegend');
		if (obj) obj.style.visibility = 'hidden';
		elements = YAHOO.util.Dom.getElementsByClassName('ilc_page_cont_PageContainer');
		for (k in elements)
		{
			elements[k].style.backgroundColor = '#F0F0F0';
		}
		elements = YAHOO.util.Dom.getElementsByClassName('ilc_page_Page');
		for (k in elements)
		{
			elements[k].style.backgroundColor = '#F0F0F0';
		}

		this.edit_status = status;
	},

	getEditStatus: function()
	{
		return this.edit_status;
	},

	setInsertStatus: function(status)
	{
		this.insert_status = status;
	},

	getInsertStatus: function()
	{
		return this.insert_status;
	},

	setParagraphClass: function(i)
	{
		// set paragraph class
		var ed = tinyMCE.getInstanceById('tinytarget');
		var r = ed.dom.getRoot();
		var fc = r.childNodes[0];
		if (fc != null)
		{
			fc.className = "ilc_text_block_" + i['hid_val'];
		}
		this.autoResize(ed);
	},


	prepareTinyForEditing: function(insert)
	{
		var ed = tinyMCE.getInstanceById('tinytarget');
		tinyMCE.execCommand('mceAddControl', false, 'tinytarget');
		showToolbar('tinytarget');

//console.log("prepareTiny");
//		if (!insert)
//		{
//console.log("no insert");
			tinyifr = document.getElementById("tinytarget_parent");
			tinyifr.style.position = "absolute";
			this.synchInputRegion();
//		}
		
		this.setEditStatus(true);
		this.setInsertStatus(insert);
		this.focusTiny();
	},

	focusTiny: function(insert)
	{
		var ed = tinyMCE.getInstanceById('tinytarget');
		if (ed)
		{
			var e = tinyMCE.DOM.get(ed.id + '_external');
			var r = ed.dom.getRoot();
			var fc = r.childNodes[0];
			if (fc != null)
			{
				// set selection to start of first div
				var rn = ed.dom.createRng();
				rn.setStart(fc, 0);
				rn.setEnd(fc, 0);
				ed.selection.setRng(rn);
				if (fc.className != null)
				{
					var st = fc.className.substring(15);
					ilAdvancedSelectionList.selectItem('style_selection', st);
				}
			}

			// without the timeout, cursor will disappear, e.g. in firefox when
			// new paragraph is inserted
			setTimeout('tinyMCE.execCommand(\'mceFocus\',false,\'tinytarget\');', 1);
		}
	},

	editTD: function(id)
	{
		editParagraph(id, 'td');
		//var ed = tinyMCE.get('tinytarget');
		//this.focusTiny();
	},

	editNextCell: function()
	{
		// check whether next cell exists
		var cdiv = this.current_td.split("_");
		var next = "cell_" + cdiv[1] + "_" + (parseInt(cdiv[2]) + 1);
		var nobj = document.getElementById("div_" + next);
		if (nobj == null)
		{
			var next = "cell_" + (parseInt(cdiv[1]) + 1) + "_0";
			var nobj = document.getElementById("div_" + next);
		}
		if (nobj != null)
		{
			editParagraph(next, "td");
		}
	},

	editPreviousCell: function()
	{
		// check whether next cell exists
		var prev = "";
		var cdiv = this.current_td.split("_");
		if (parseInt(cdiv[2]) > 0)
		{
			prev = "cell_" + cdiv[1] + "_" + (parseInt(cdiv[2]) - 1);
			var pobj = document.getElementById("div_" + prev);
		}
		else if (parseInt(cdiv[1]) > 0)
		{
			var p = "cell_" + (parseInt(cdiv[1]) - 1) + "_0";
			var o = document.getElementById("div_" + p);
			var i = 0;
			while (o != null)
			{
				pobj = o;
				prev = p;
				p = "cell_" + (parseInt(cdiv[1]) - 1) + "_" + i;
				var o = document.getElementById("div_" + p);
				i++;
			}
		}
		if (prev != "")
		{
			var pobj = document.getElementById("div_" + prev);
			if (pobj != null)
			{
				editParagraph(prev, "td");
			}
		}
	},

	setEditFrameSize: function(width, height)
	{
		var tinyifr = document.getElementById("tinytarget_ifr");
		var tinytd = document.getElementById("tinytarget_tbl");;
		tinyifr.style.width = width + "px";
		tinyifr.style.height = height + "px";
		tinytd.style.width = width + "px";
		tinytd.style.height = height + "px";
		this.ed_width = width;
		this.ed_height = height;
	},

	// copy input of tiny to ghost div in background
	copyInputToGhost: function()
	{
		var ed = tinyMCE.get('tinytarget');
		if (this.edit_ghost)
		{
			var pdiv = document.getElementById(this.edit_ghost);
			if (pdiv)
			{
				pdiv.innerHTML = ed.getContent();
			}
		}
	},

	// synchs the size/position of the tiny to the space the ghost
	// object uses in the background
	synchInputRegion: function()
	{
		var back_el;

		if (this.current_td)
		{
			back_el = document.getElementById(this.edit_ghost);
			back_el = back_el.parentNode;
		}
		else
		{
			back_el = document.getElementById(this.edit_ghost);
		}

		back_el.style.minHeight = ilCOPage.minheight + "px";
		back_el.style.minWidth = ilCOPage.minwidth + "px";

		tinyifr = document.getElementById("tinytarget_parent");

		//tinyifr.style.position = "absolute";
		back_el.style.display = '';
		var pdiv_reg = YAHOO.util.Region.getRegion(back_el);
		YAHOO.util.Dom.setX(tinyifr, pdiv_reg.x - 2);
		YAHOO.util.Dom.setY(tinyifr, pdiv_reg.y - 2);
		this.setEditFrameSize(pdiv_reg.width,
			pdiv_reg.height);
	},

	autoResize: function(ed)
	{
		this.copyInputToGhost();
		this.synchInputRegion();
	},
	
	/**
	 * Init all draggable elements (YUI)
	 */
	initDragElements: function()
	{
		var d;
		
		this.drag_contents = [];
		this.drag_targets = [];
		
		// get all spans
		obj=document.getElementsByTagName('div')
		
		// run through them
		for (var i=0;i<obj.length;i++)
		{
			// make all edit areas draggable
			if(/il_editarea/.test(obj[i].className))
			{
				d = new ilDragContent(obj[i].id, "gr1");
				this.drag_contents.push(d);
	//d.locked = true;
			}
			// make all drop areas dropable
			if(/il_droparea/.test(obj[i].className))
			{
				d = new ilDragTarget(obj[i].id, "gr1");
				this.drag_targets.push(d);
			}
		}
	},
	
	disableDragContents: function()
	{
		var i;
		for (i in this.drag_contents)
		{
			this.drag_contents[i].locked = true;
		}
	},

	enableDragContents: function()
	{
		var i;
		for (i in this.drag_contents)
		{
			this.drag_contents[i].locked = false;
		}
	},

}

var stopHigh = false;
var Mposx = 0;
var Mposy = 0;
var sel_edit_areas = Array();
var edit_area_class = Array();
var edit_area_original_class = Array();
var openedMenu = "";					// menu currently opened
var current_mouse_over_id;
var cmd_called = false;

ilAddOnLoad(function(){var preloader = new Image();
preloader.src = "./templates/default/images/loader.gif";});
//document.onmousemove=followmouse1;
YAHOO.util.Event.addListener(document, 'mousemove', followmouse1);

/**
* Get inner height of window
*/
function ilGetWinInnerHeight()
{
	if (self.innerHeight)
	{
		return self.innerHeight;
	}
	// IE 6 strict Mode
	else if (document.documentElement && document.documentElement.clientHeight)
	{
		return document.documentElement.clientHeight;
	}
	// other IE
	else if (document.body)
	{
		return document.body.clientHeight;
	}
}

function ilGetWinPageYOffset()
{
	if (typeof(window.pageYOffset ) == 'number')
	{
		return window.pageYOffset;
	}
	else if(document.body && (document.body.scrollLeft || document.body.scrollTop ))
	{
		return document.body.scrollTop;
	}
	else if(document.documentElement && (document.documentElement.scrollLeft || document.documentElement.scrollTop))
	{
		return document.documentElement.scrollTop;
	}
	return 0;
}

function getBodyWidth()
{
	if (document.body && document.body.offsetWidth)
	{
		return document.body.offsetWidth;
	}
	else if (document.documentElement && document.documentElement.offsetWidth)
	{
		return document.documentElement.offsetWidth;
	}
	return 0;
}

function ilGetOffsetTop(el)
{
	var y = 0;
	
	if (typeof(el) == "object" && document.getElementById)
	{
		y = el.offsetTop;
		if (el.offsetParent)
		{
			y += ilGetOffsetTop(el.offsetParent);
		}
		return y;
	}
	else 
	{
		return false;
	}
}

function ilGetMouseX(e)
{
	if (e.pageX)
	{
		return e.pageX;
	}
	else if (document.documentElement)
	{
		return e.clientX + document.documentElement.scrollLeft;
	}
	if (document.body)
	{
		Mposx = e.clientX + document.body.scrollLeft;
	}
}

function ilGetMouseY(e)
{
	if (e.pageY)
	{
		return e.pageY;
	}
	else if (document.documentElement)
	{
		return e.clientY + ilGetWinPageYOffset();
	}
	if (document.body)
	{
		Mposx = e.clientY + document.body.scrollTop;
	}
}

/**
* On mouse over: Set style class of element id to class
*/
function doMouseOver (id, mclass)
{
	if (ilCOPage.getEditStatus())
	{
		return;
	}

	if (cmd_called) return;
	if(stopHigh) return;
	stopHigh=true;
	overId = id;
	setTimeout("stopHigh=false",10);
	obj = document.getElementById(id);
	edit_area_class[id] = mclass;
	if (obj.className != "il_editarea_selected")
	{
		edit_area_original_class[id] = obj.className;
	}
	if (sel_edit_areas[id])
	{
		obj.className = "il_editarea_active_selected";
	}
	else
	{
		if (obj.className == "il_editarea_disabled")
		{
			obj.className = "il_editarea_disabled_selected";
		}
		else
		{
			obj.className = mclass;
		}
	}
	
	var typetext = document.getElementById("T" + id);
	if (typetext)
	{
		typetext.style.display = '';
	}
	
	current_mouse_over_id = id;
}

/**
* On mouse out: Set style class of element id to class
*/
function doMouseOut(id, mclass)
{
	if (cmd_called) return;
	if (id!=overId) return;
	stopHigh = false;
	obj = document.getElementById(id);
	if (sel_edit_areas[id])
	{
		obj.className = "il_editarea_selected";
	}
	else
	{
		//obj.className = mclass;
		obj.className = edit_area_original_class[id];
	}
	
	var typetext = document.getElementById("T" + id);
	if (typetext)
	{
		typetext.style.display = 'none';
	}

}

function followmouse1(e) 
{
//    if (!e) var e = window.event;
    
//	Mposx = ilGetMouseX(e);
//	Mposy = ilGetMouseY(e);

	var t = YAHOO.util.Event.getXY(e);
	Mposx = t[0];
	Mposy = t[1];

}

function showMenu(id, x, y)
{
	// no menu when paragraphs are edited
//console.log("show menu" + ilCOPage.getEditStatus());
	if (ilCOPage.getEditStatus())
	{
		return;
	}

	if (cmd_called) return;
	
	var obj = document.getElementById(id);
obj.style.visibility = '';
YAHOO.util.Dom.setXY(obj, [x,y], true);

/*	obj.style.visibility = '';
	obj.style.left = x + 10 + "px";
	obj.style.top = y + "px";
	
	var w = Math.floor(getBodyWidth() / 2);
	
	var wih = ilGetWinInnerHeight();
	var yoff = ilGetWinPageYOffset();
	var top = ilGetOffsetTop(obj);
	
	if (Mposx > w)
	{
		obj.style.left = Mposx - (obj.offsetWidth + 10) + "px";
	}

	if (top + (obj.offsetHeight + 10) > wih + yoff)
	{
		obj.style.top = (wih + yoff - (obj.offsetHeight + 10)) + "px";
	}
*/
}

function hideMenu(id)
{
	if (cmd_called) return;
	obj = document.getElementById(id);
	if (obj)
	{
		obj.style.visibility = 'hidden';
	}
}

var dragDropShow = false;
var mouseIsDown = false;
var mouseDownBlocked = false;
var mouseUpBlocked = false;

var dragId = "";
var overId = "";

function doMouseDown(id) 
{
	if (cmd_called) return;
	//dd.elements.contextmenu.hide();
	if(mouseDownBlocked) return;
	mouseDownBlocked = true;
	setTimeout("mouseDownBlocked = false;",200);
	
	obj = document.getElementById(id);
	
	if (!mouseIsDown) {
//		dragId = id;
	
		oldMposx = Mposx;
		oldMposy = Mposy;
		mouseIsDown = true;
	}
}


var cmd1 = "";
var cmd2 = "";
var cmd3 = "";
var cmd4 = "";

/*function callBeforeAfterAction(setCmd3) 
{
	cmd3 = setCmd3;
	doActionForm(cmd1, cmd2, cmd3, cmd4);
}*/


function doMouseUp(id) 
{
/*	if (dragDropShow)
	{
		if(mouseUpBlocked) return;
		mouseUpBlocked = true;
		setTimeout("mouseUpBlocked = false;",200);
		
		// mousebutton released over new object. call moveafter
		//alert(dragId+" - "+overId);
		DID = overId.substr(7);
		OID = dragId.substr(7);
		if (DID != OID) 
		{ 
			doCloseContextMenuCounter = 20;
			openedMenu = "movebeforeaftermenu";
			dd.elements.movebeforeaftermenu.moveTo(Mposx,Mposy);
			dd.elements.movebeforeaftermenu.show();
			cmd1 = 'cmd[exec_'+OID+']';
			cmd2 = 'command'+OID;
			cmd3 = 'moveAfter';
			cmd4 = DID;
			//doActionForm('cmd[exec_'+OID+']','command'+OID+'', 'moveAfter', DID);
		}
	}
*/
	dragId = "";
	mouseIsDown = false;
	dragDropShow = false;
//	dd.elements.dragdropsymbol.hide();
//	dd.elements.dragdropsymbol.moveTo(-1000,-1000);
	setTimeout("dragDropShow = false",500);
}


                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  
/**
*   on Click show context-menu at mouse-position
*/

var menuBlocked = false;
function nextMenuClick() {
	menuBlocked = false;
}


function extractHierId(id)
{
	var i = id.indexOf(":");
	if (i > 0)
	{
		id = id.substr(0, i);
	}
	
	return id;
}

/**
* Process Single Mouse Click
*/
function doMouseClick(e, id) 
{
	if (cmd_called) return;
	
	if(menuBlocked || mouseUpBlocked) return;
	menuBlocked = true;
	setTimeout("nextMenuClick()",100);

	if (!e) var e = window.event;

	if (id.substr(0, 6) == "TARGET")
	{
		clickcmdid = id.substr(6);
		var nextMenu = "dropareamenu_" + extractHierId(clickcmdid);
	}
	else if (id.substr(0, 4) == "COL_")		// used in table data editor
	{
		clickcmdid = id.substr(4);
		var nextMenu = "col_menu_" + extractHierId(clickcmdid);
	}
	else if (id.substr(0, 4) == "ROW_")		// used in table data editor
	{
		clickcmdid = id.substr(4);
		var nextMenu = "row_menu_" + extractHierId(clickcmdid);
	}
	else
	{
		// these are the "CONTENT" ids now
		clickcmdid = id.substr(7);
//alert(clickcmdid + "*" + extractHierId(clickcmdid));
		var nextMenu = "contextmenu_" + extractHierId(clickcmdid);
	}
	
	Mposx = ilGetMouseX(e);
	Mposy = ilGetMouseY(e);

	if (!dragDropShow) 
	{
		if (openedMenu != "" || openedMenu == nextMenu) 
		{
			hideMenu(openedMenu);
			//dd.elements[openedMenu].hide();
			oldOpenedMenu = openedMenu;
			openedMenu = "";
		}
		else
		{
			oldOpenedMenu = "";
		}
		
		if (openedMenu == "" && nextMenu != oldOpenedMenu)
		{
			openedMenu = nextMenu;
			showMenu(openedMenu, Mposx, Mposy-10);
		}
		doCloseContextMenuCounter = 20;
	}
}

/**
* Process Double Mouse Click
*/
function doMouseDblClick(e, id) 
{
	if (cmd_called) return;
	if (current_mouse_over_id == id)
	{
		obj = document.getElementById(id);
		if (sel_edit_areas[id])
		{
			sel_edit_areas[id] = false;
			obj.className = "il_editarea_active";
		}
		else
		{
			sel_edit_areas[id] = true;
			obj.className = "il_editarea_active_selected";
		}
	}
}

/**
*   on MouseOut of context-menu hide context-menu 
*/
var doCloseContextMenuCounter = -1;
function doCloseContextMenu() 
{
	if (cmd_called) return;
	if (doCloseContextMenuCounter>-1) 
	{
		doCloseContextMenuCounter--;
		if(doCloseContextMenuCounter==0) 
		{
			if(openedMenu!="") 
			{
				//dd.elements[openedMenu].hide();
				hideMenu(openedMenu);
				openedMenu = "";
				oldOpenedMenu = "";
			}
			doCloseContextMenuCounter=-1;
		}
	}
	setTimeout("doCloseContextMenu()",100);
}
setTimeout("doCloseContextMenu()",200);

var clickcmdid = 0;

var tinyinit = false;
var ed_para = null;
function editParagraph(div_id, mode)
{
	ed_para = div_id;
	
	if (mode == 'edit')
	{
		// get paragraph edit div
		var pdiv = document.getElementById("CONTENT" + div_id);
		var pdiv_reg = YAHOO.util.Region.getRegion(pdiv);
	}

	if (mode == 'insert')
	{
		// get placeholder div
		var pdiv = document.getElementById("TARGET" + div_id);
//console.log(pdiv);
		var insert_ghost = new YAHOO.util.Element(document.createElement('div'));
		insert_ghost = YAHOO.util.Dom.insertAfter(insert_ghost, pdiv);
		insert_ghost.id = "insert_ghost";
		insert_ghost.style.paddingTop = "5px";
		insert_ghost.style.paddingBottom = "5px";

		var pdiv_reg = YAHOO.util.Region.getRegion(pdiv);
	}

	// table editing mode (td)
	var moved = false;		// is edit area currently move from one td to another?
	if (mode == 'td')
	{
		// if current_td already set, we must move editor to new td
		if (ilCOPage.current_td != "")
		{
			ilCOPage.copyInputToGhost();

// try using ed.destroy???
//			tinyMCE.execCommand('mceRemoveControl', false, 'tinytarget');
//			ed.destroy();
//tinyMCE.execCommand('mceAddControl', false, 'tinytarget');
//return;
//			var ta = document.getElementById('tinytarget');
//			var par = ta.parentNode;
//			par.removeChild(ta);
			//pdiv.style.display = '';
			var pdiv = document.getElementById('div_' + ilCOPage.current_td);
			pdiv.style.minHeight = '';
			pdiv.style.minWidth = '';
			moved = true;
		}

		// get placeholder div
		var pdiv = document.getElementById('div_' + div_id);
		var pdiv_reg = YAHOO.util.Region.getRegion(pdiv);
		ilCOPage.current_td = div_id;
//		pdiv.style.minHeight = ilCOPage.minheight + "px";
//		pdiv.style.minWidth = ilCOPage.minwidth + "px";

	}


	// set background "ghost" element
	if (mode == 'td')
	{
		ilCOPage.edit_ghost = "div_" + ilCOPage.current_td;
	}
	else if (mode == 'insert')
	{
		ilCOPage.edit_ghost = "insert_ghost";
	}
	else
	{
		ilCOPage.edit_ghost = "CONTENT" + ed_para;
	}

	// disable drag content
	ilCOPage.disableDragContents();
	

//console.log("content_css: " + ilCOPage.content_css);
//	if (!tinyinit) {
// content_css: "Services/COPage/css/content.css, templates/default/delos.css",
// theme_advanced_buttons2 : "table,|,row_props,cell_props,|,row_before,row_after,delete_row,|,col_before,col_after,delete_col,|,split_cells,merge_cells",

	// create new text area for tiny
	if (!moved)
	{
		//var pdiv_width = pdiv_reg.right - pdiv_reg.left;
		var ta = new YAHOO.util.Element(document.createElement('textarea'));
		ta = YAHOO.util.Dom.insertAfter(ta, pdiv);
		ta.id = 'tinytarget';
		ta.className = 'par_textarea';
	}

	// init tiny
	var tinytarget = document.getElementById("tinytarget");
	var show_path = true;
	var resize = true;
	var statusbar = 'bottom';

show_path = false;
resize = false;
statusbar = false;

	if (mode == 'td')
	{
		show_path = false;
		resize = false;
		statusbar = false;
	}
//show_path = false;
resize = false;
//statusbar = false;
	tinytarget.style.display = '';
	if (!moved)
	{
		tinyMCE.init({
			mode : "textareas",
			theme : "advanced",
			editor_selector : "par_textarea",
			language : "en",
			plugins : "safari,save",
			save_onsavecallback : "saveParagraph",
			fix_list_elements : true,
			theme_advanced_blockformats : "code",
			theme_advanced_toolbar_align : "left",
			theme_advanced_buttons1 : "save,b,code,il_strong,styleselect,formatselect,bullist,numlist,outdent,indent",
			theme_advanced_buttons2 : "",
			theme_advanced_buttons3 : "",
			content_css: ilCOPage.content_css,
			theme_advanced_toolbar_location : "external",
			theme_advanced_path : show_path,
			theme_advanced_statusbar_location : statusbar,
			valid_elements : "br,div[class],span[class],code,b,ul,ol,li",
			remove_linebreaks : false,
			convert_newlines_to_brs : false,
			force_p_newlines : false,
			force_br_newlines : true,
			forced_root_block : 'div',
			save_onsavecallback : "saveParagraph",
			theme_advanced_resize_horizontal : false,
			theme_advanced_resizing : resize,
			cleanup_on_startup : true,
			entity_encoding : "raw",
			cleanup: true,

			style_formats : [
				{title : 'Strong', inline : 'span', classes : 'ilc_text_inline_Strong'},
				{title : 'Emph', inline : 'span', classes : 'ilc_text_inline_Emph'},
				{title : 'Important', inline : 'span', classes : 'ilc_text_inline_Important'},
				{title : 'Comment', inline : 'span', classes : 'ilc_text_inline_Comment'},
				{title : 'Quotation', inline : 'span', classes : 'ilc_text_inline_Quotation'},
				{title : 'Accent', inline : 'span', classes : 'ilc_text_inline_Accent'}
			],

			setup : function(ed) {
				ed.onKeyUp.add(function(ed, ev)
				{
//console.log("onKeyPress");
					ilCOPage.autoResize(ed);
				});
				ed.onKeyDown.add(function(ed, ev)
				{
//					console.log("onKeyDown" + ev.keyCode);
//					console.log("shiftKey" + ev.shiftKey);
					if(ev.keyCode == 9 && !ev.shiftKey)
					{
//						console.log("tab");
						YAHOO.util.Event.preventDefault(ev);
						YAHOO.util.Event.stopPropagation(ev);
						ilCOPage.editNextCell();
					}
					if(ev.keyCode == 9 && ev.shiftKey)
					{
//						console.log("backtab");
						YAHOO.util.Event.preventDefault(ev);
						YAHOO.util.Event.stopPropagation(ev);
						ilCOPage.editPreviousCell();
					}
					//console.log("onKeyDown");
				});
				ed.onNodeChange.add(function(ed, cm, n)
				{
//					console.log("onNodeChange");

					// update state of indent/outdent buttons
					var ibut = parent.document.getElementById('ilIndentBut');
					var obut = parent.document.getElementById('ilOutdentBut');
					if (ibut != null && obut != null)
					{
						if (ed.queryCommandState('InsertUnorderedList') ||
							ed.queryCommandState('InsertOrderedList'))
						{
							ibut.style.visibility = '';
							obut.style.visibility = '';
						}
						else
						{
							ibut.style.visibility = 'hidden';
							obut.style.visibility = 'hidden';
						}
					}

					// autoresize
					//ilCOPage.autoResize(ed);

				});

				var width = pdiv_reg.width;
				var height = pdiv_reg.height;
				if (width < ilCOPage.minwidth)
				{
					width = ilCOPage.minwidth;
				}
				if (height < ilCOPage.minheight)
				{
					height = ilCOPage.minheight;
				}

				ed.onActivate.add(function(ed, ev)
				{
//					console.log("onActivate");
				});
				ed.onLoadContent.add(function(ed, ev)
				{
//					console.log("onContent");
				});
				ed.onPostProcess.add(function(ed, ev)
				{
//					console.log("onPostProcess");
					//ilCOPage.prepareTinyForEditing(true);
					//tinyMCE.execCommand('mceFocus',false,'tinytarget');
					//setTimeout('tinyMCE.execCommand(\'mceFocus\',false,\'tinytarget\');', 1);
				});
				ed.onPostRender.add(function(ed, ev)
				{
//					console.log("onPostRender");
				});

				ed.onInit.add(function(ed, evt)
				{
					ilCOPage.setEditFrameSize(width, height);
					if (mode == 'edit')
					{
						pdiv.style.display = "none";
					}

					if (mode == 'edit')
					{
						// get content per ajax
						ed.setProgressState(1); // Show progress
						ilFormSend("editJS", div_id, null, "para");
					}

					if (mode == 'insert')
					{
						//alert("ff");
//		console.log("onInit: setContent");
						ed.setContent("<div class='ilc_text_block_Standard'></div>");
						ilCOPage.prepareTinyForEditing(true);
						//setTimeout('ilCOPage.prepareTinyForEditing(true);', 1);
						ilCOPage.synchInputRegion();
						ilCOPage.focusTiny();
					}

					if (mode == 'td')
					{
						ed.setContent(pdiv.innerHTML);
						ilCOPage.prepareTinyForEditing(false);
						ilCOPage.synchInputRegion();
						ilCOPage.focusTiny();
					}
				});
			}

		});
	}
	else
	{
		//prepareTinyForEditing;
		tinyMCE.execCommand('mceToggleEditor', false, 'tinytarget');
		var ed = tinyMCE.get('tinytarget');
		ed.setContent(pdiv.innerHTML);
//		ilCOPage.prepareTinyForEditing(true);
		ilCOPage.synchInputRegion();
		ilCOPage.focusTiny();
	}

	tinyinit = true;
}


/**
 * Save paragraph
 */
function saveParagraph()
{
//	var ed = tinyMCE.get('tinytarget');
//	ed.setProgressState(1); // Show progress
	ilCOPage.setEditStatus(false);
	ilFormSend("saveJS", ed_para, null, null);
}

function doActionForm(cmd, command, value, target, type)
{
	if (cmd_called) return;
//alert("-" + cmd + "-" + command + "-" + value + "-" + target + "-");
//-cmd[exec]-command-edit--
    doCloseContextMenuCounter = 2;

    if(cmd=="cmd[exec]") 
	{
        cmd = "cmd[exec_"+clickcmdid+"]";
    }
    
    if (command=="command") 
	{
        command += extractHierId(clickcmdid);
    }
//console.trace();
//alert("-" + cmd + "-" + command + "-" + value + "-" + target + "-" + type + "-" + clickcmdid + "-");
//-cmd[exec_1:1d3ae9ffebd59671a8c7e254e22d3b5d]-command1-edit--

	if (value=="edit" && type=="Paragraph")
	{
		editParagraph(clickcmdid, 'edit');
		return false;
	}

	if (value == 'insert_par')
	{
		editParagraph(clickcmdid, 'insert');
		return false;
	}

	if (value=="delete") 
	{
		if(!confirm(confirm_delete)) 
		{
			menuBlocked = true;
			setTimeout("nextMenuClick()",500);
			return;
		}
		menuBlocked = true;
		setTimeout("nextMenuClick()",500);
	}
	
	//alert(target+" - "+command+" - "+value+" - "+cmd);
	
/*
	html = "<form name=cmform id=cmform method=post action='"+actionUrl+"'>";
	html += "<input type=hidden name='target[]' value='"+target+"'>";
	html += "<input type=hidden name='"+command+"' value='"+value+"'>";
	html += "<input type=hidden name='"+cmd+"' value='Ok'>";
	html += "</form>";

	dd.elements.actionForm.write(html);
*/
	obj = document.getElementById("cmform");
	hid_target = document.getElementById("cmform_target");
	hid_target.value = target;
	hid_cmd = document.getElementById("cmform_cmd");
	hid_cmd.name = command;
	hid_cmd.value = value;
	hid_exec = document.getElementById("cmform_exec");
	hid_exec.name = cmd;
	
	cmd_called = true;
	
	if (ccell)
	{
		var loadergif = document.createElement('img');
		loadergif.src = "./templates/default/images/loader.gif";
		loadergif.border = 0;
		loadergif.style.position = 'absolute';
		ccell.bgColor='';
		ccell.appendChild(loadergif);
	}
    obj.submit();
}

var ccell = null;

function M_in(cell) 
{
	if (cmd_called) return;
    cell.style.cursor='pointer';
    cell.bgColor='#C0C0FF';
    doCloseContextMenuCounter=-1;
    ccell = cell;
}
function M_out(cell) 
{
	if (cmd_called) return;
    cell.bgColor='';
    doCloseContextMenuCounter=5;
    ccell = null;
}

var oldMposx = -1;
var oldMposy = -1;    

/*function doKeyDown(e) 
{
    if (!e) var e = window.event;
    kc = e.keyCode;
    kc = kc * 1;

    if(kc == 17) 
	{
		dd.elements.contextmenu.hide();
		oldMposx = Mposx;
		oldMposy = Mposy;
		mouseIsDown = true;
	}
}*/

/*function doKeyUp(e)
{
	if (!e) var e = window.event;
	kc = e.keyCode;
	
	kc = kc*1;
	if(kc==17) 
	{
		mouseIsDown = false;
		dd.elements.dragdropsymbol.hide();
		dd.elements.dragdropsymbol.moveTo(-1000,-1000);
		setTimeout("dragDropShow = false",500);
	}
}*/

// This will be our extended DDProxy object
ilDragContent = function(id, sGroup, config)
{
    this.swapInit(id, sGroup, config);
	this.isTarget = false;
};

// We are extending DDProxy now
YAHOO.extend(ilDragContent, YAHOO.util.DDProxy);

// protype: all instances will get this functions
ilDragContent.prototype.swapInit = function(id, sGroup, config)
{
    if (!id) { return; }
	this.init(id, sGroup, config);	// important!
	this.initFrame();				// important!
};

// overwriting onDragDrop function
// (ending a valid drag drop operation)
ilDragContent.prototype.onDragDrop = function(e, id)
{
	target_id = id.substr(6);
	source_id = this.id.substr(7);
	if (source_id != target_id)
	{
		ilFormSend("moveAfter", source_id, target_id, null);
	}
};


ilDragContent.prototype.endDrag = function(e)
{
};

// overwriting onDragDrop function
ilDragContent.prototype.onDragEnter = function(e, id)
{
	target_id = id.substr(6);
	source_id = this.id.substr(7);
	if (source_id != target_id)
	{
		d_target = document.getElementById(id);
		d_target.className = "il_droparea_active";
	}
};

// overwriting onDragDrop function
ilDragContent.prototype.onDragOut = function(e, id)
{
	d_target = document.getElementById(id);
	d_target.className = "il_droparea";
};

///
///   ilDragTarget
///

// This will be our extended DDProxy object
ilDragTarget = function(id, sGroup, config)
{
    this.dInit(id, sGroup, config);
};

// We are extending DDProxy now
//YAHOO.extend(ilDragTarget, YAHOO.util.DDProxy);
YAHOO.extend(ilDragTarget, YAHOO.util.DDTarget);

// protype: all instances will get this functions
ilDragTarget.prototype.dInit = function(id, sGroup, config)
{
    if (!id) { return; }
	this.init(id, sGroup, config);	// important!
	//this.initFrame();				// important!
};


///
/// ilFormSend
///
function ilFormSend(cmd, source_id, target_id, mode)
{
	// put target id into form
	hid_target = document.getElementById("ajaxform_target");
	hid_target.value = target_id;

	// put command/source id into form
	hid_cmd = document.getElementById("ajaxform_cmd");
	hid_cmd.name = "command" + extractHierId(source_id);
	hid_cmd.value = cmd;

	// put hier_id into form
	hid_exec = document.getElementById("ajaxform_hier_id");
	hid_exec.value = extractHierId(source_id);

	// put command into form
	if (cmd == "insertJS")
	{
		hid_exec = document.getElementById("ajaxform_exec");
		hid_exec.name = "cmd[create_par]";
	}
	else if (cmd != 'saveDataTable')
	{
		hid_exec = document.getElementById("ajaxform_exec");
		hid_exec.name = "cmd[exec_" + source_id + "]";
	}

	if (cmd == 'saveJS' || cmd == 'insertJS')
	{
		// get content of tiny and put it into form
		hid_cont = document.getElementById("ajaxform_content");
		var ed = tinyMCE.get('tinytarget');
		hid_cont.value = ed.getContent();

		// put selected style class into form
		var hid_char = document.getElementById("ajaxform_char");
		hid_char.value = ilAdvancedSelectionList.getHiddenInput('style_selection');

		// get tiny region (befor removing it!)
		var ttbl = document.getElementById("tinytarget_tbl");
		tt_reg = YAHOO.util.Region.getRegion(ttbl);

		// remove tiny
		tinyMCE.execCommand('mceRemoveControl', false, 'tinytarget');
		var tt = document.getElementById("tinytarget");
		tt.style.display = 'none';

		// insert div and loader image
		var ld = new YAHOO.util.Element(document.createElement('div'));
		var lg = new YAHOO.util.Element(document.createElement('img'));
		lg = ld.appendChild(lg);
		ld = YAHOO.util.Dom.insertAfter(ld, tt);
		if (cmd == "insertJS")
		{
			ld.style.width = tt_reg.width + "px";
			ld.style.height = tt_reg.height + "px";
		}
		lg.src = "./templates/default/images/loader.gif";
		lg.border = 0;
		lg.style.position = "absolute";
	}
	else if (cmd == 'saveDataTable')
	{
		// get content of table and put it into form
		tbl = document.getElementById("ed_datatable");
		hid_cont = document.getElementById("ajaxform_content");
		hid_cont.value = tbl.innerHTML;
	}

    form = document.getElementById("ajaxform");
	var str = form.action;

	if (cmd == 'saveDataTable')
	{
		// normal submit for submitting the whole table
		return form.submit();
	}
	else
	{
		// ajax saving
		var r = ilCOPageJSHandler(str, mode);
	}
	return r;
}

function ilEditMultiAction(cmd)
{
	hid_exec = document.getElementById("cmform_exec");
	hid_exec.name = "cmd[" + cmd + "]";
	hid_cmd = document.getElementById("cmform_cmd");
	hid_cmd.name = cmd;
    form = document.getElementById("cmform");
	
	var sel_ids = "";
	var delim = "";
	for (var key in sel_edit_areas)
	{
		if (sel_edit_areas[key])
		{
			sel_ids = sel_ids + delim + key.substr(7);
			delim = ";";
		}
	}

	hid_target = document.getElementById("cmform_target");
	hid_target.value = sel_ids;
	
	form.submit();
	
	return false;
}

//
// js paragraph editing
//

// copied from TinyMCE editor_template_src.js
function showToolbar(ed_id)
{
	var DOM = tinyMCE.DOM;
	var Event = tinyMCE.dom.Event;

	var e = DOM.get(ed_id + '_external');
	DOM.show(e);

//	DOM.hide(lastExtID);

	var f = Event.add(ed_id + '_external_close', 'click', function() {
		DOM.hide(ed_id + '_external');
		Event.remove(ed_id + '_external_close', 'click', f);
	});

	DOM.show(e);

	if (false)
	{
		DOM.setStyle(e, 'top', 0 - DOM.getRect(ed_id + '_tblext').h - 1);
	}
	else
	{
		// make tinymenu a panel
		var obj = document.getElementById('iltinymenu');
		obj.style.display = "";
		// Create a panel Instance, from the 'resizablepanel' DIV standard module markup
		var menu_panel = new YAHOO.widget.Panel("iltinymenu", {
			draggable: true,
			autofillheight: "body", // default value, specified here to highlight its use in the example
			constraintoviewport:true
		});
		menu_panel.render();
		ilCOPage.menu_panel = menu_panel;

		ilCOPage.menu_panel_opened = true;

		DOM.setStyle(e, 'top', -1000);
		var ed_el = document.getElementById(ed_id + '_parent');
		var m_el = document.getElementById('iltinymenu');
//		m_el.style.display = '';
		var ed_reg = YAHOO.util.Region.getRegion(ed_el);
		var m_reg = YAHOO.util.Region.getRegion(m_el);
		var debug = 0;

 //debug = -30;
//		YAHOO.util.Dom.setY(m_el, ed_reg.y - m_reg.height + 1 + debug);
//		YAHOO.util.Dom.setX(m_el, ed_reg.x);
//		menu_panel.moveTo(ed_reg.x,
//			ed_reg.y - m_reg.height + 1 + debug);

		var obj = document.getElementById('iltinymenu_c');
		obj.style.position = 'fixed';

		menu_panel.moveTo(20, 20);

	}

	// Fixes IE rendering bug
	DOM.hide(e);
	DOM.show(e);
	e.style.filter = '';

//	lastExtID = ed.id + '_external';

	e = null;
};

//ilAddOnLoad(function(){ilCOPage.editTD('cell_0_0');});