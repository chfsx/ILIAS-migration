/* XHTML Xtras Plugin
 * Andrew Tetlaw 2006/02/21
 * http://tetlaw.id.au/view/blog/xhtml-xtras-plugin-for-tinymce/
 * Thanks to Scott 'monkeybrain' Eade http://sourceforge.net/users/monkeybrain/ for handleNodeChage patch
 */
tinyMCE.importPluginLanguagePack('xhtmlxtras', 'en,de');

var TinyMCE_XHTMLXtrasPlugin = {

	getInfo : function() {
		return {
			longname : 'XHTML Xtras Plugin',
			author : 'Andrew Tetlaw',
			authorurl : 'http://tetlaw.id.au',
			infourl : 'http://tetlaw.id.au/view/blog/xhtml-xtras-plugin-for-tinymce/',
			version : "1.1"
		};
	},

	initInstance : function(inst) {
		tinyMCE.importCSS(inst.getDoc(), tinyMCE.baseURL + "/plugins/xhtmlxtras/css/xhtmlxtras.css");
	},

	getControlHTML : function(cn) {
		switch (cn) {
			case "cite":
				return tinyMCE.getButtonHTML(cn, 'lang_xhtmlxtras_cite_desc', '{$pluginurl}/images/cite.gif', 'mceCite', true);
			case "acronym":
				return tinyMCE.getButtonHTML(cn, 'lang_xhtmlxtras_acronym_desc', '{$pluginurl}/images/acronym.gif', 'mceAcronym', true);
			case "abbr":
				return tinyMCE.getButtonHTML(cn, 'lang_xhtmlxtras_abbr_desc', '{$pluginurl}/images/abbr.gif', 'mceAbbr', true);
			case "del":
				return tinyMCE.getButtonHTML(cn, 'lang_xhtmlxtras_del_desc', '{$pluginurl}/images/del.gif', 'mceDel', true);
			case "ins":
				return tinyMCE.getButtonHTML(cn, 'lang_xhtmlxtras_ins_desc', '{$pluginurl}/images/ins.gif', 'mceIns', true);
		}

		return "";
	},

	execCommand : function(editor_id, element, command, user_interface, value) {
		switch (command) {
		case "mceCite":
			var template = new Array();
			template['file'] = '../../plugins/xhtmlxtras/cite.htm';
			template['width'] = 350;
			template['height'] = 400;
			tinyMCE.openWindow(template, {editor_id : editor_id});
			return true;

		case "mceAcronym":
			var template = new Array();
			template['file'] = '../../plugins/xhtmlxtras/acronym.htm';
			template['width'] = 350;
			template['height'] = 400;
			tinyMCE.openWindow(template, {editor_id : editor_id});
			return true;

		case "mceAbbr":
			var template = new Array();
			template['file'] = '../../plugins/xhtmlxtras/abbr.htm';
			template['width'] = 350;
			template['height'] = 400;
			tinyMCE.openWindow(template, {editor_id : editor_id});
			return true;

		case "mceIns":
			var template = new Array();
			template['file'] = '../../plugins/xhtmlxtras/ins.htm';
			template['width'] = 350;
			template['height'] = 400;
			tinyMCE.openWindow(template, {editor_id : editor_id});
			return true;

		case "mceDel":
			var template = new Array();
			template['file'] = '../../plugins/xhtmlxtras/del.htm';
			template['width'] = 350;
			template['height'] = 400;
			tinyMCE.openWindow(template, {editor_id : editor_id});
			return true;
		}

		return false;
	},

	handleNodeChange : function(editor_id, node, undo_index,undo_levels, visual_aid, any_selection) {
		if (node == null) return;
		if (!any_selection) {
			// Disable the buttons
			tinyMCE.switchClass(editor_id + '_cite', 'mceButtonDisabled');
			tinyMCE.switchClass(editor_id + '_acronym', 'mceButtonDisabled');
			tinyMCE.switchClass(editor_id + '_abbr', 'mceButtonDisabled');
			tinyMCE.switchClass(editor_id + '_del', 'mceButtonDisabled');
			tinyMCE.switchClass(editor_id + '_ins', 'mceButtonDisabled');
			
		} else {
			// A selection means the buttons should be active.
			tinyMCE.switchClass(editor_id + '_cite', 'mceButtonNormal');
			tinyMCE.switchClass(editor_id + '_acronym', 'mceButtonNormal');
			tinyMCE.switchClass(editor_id + '_abbr', 'mceButtonNormal');
			tinyMCE.switchClass(editor_id + '_del', 'mceButtonNormal');
			tinyMCE.switchClass(editor_id + '_ins', 'mceButtonNormal');
		}
		switch (node.nodeName) {
			case "CITE":
				tinyMCE.switchClass(editor_id + '_cite', 'mceButtonSelected');
				return true;
			case "ACRONYM":
				tinyMCE.switchClass(editor_id + '_acronym', 'mceButtonSelected');
				return true;
			case "ABBR":
				tinyMCE.switchClass(editor_id + '_abbr', 'mceButtonSelected');
				return true;
			case "DEL":
				tinyMCE.switchClass(editor_id + '_del', 'mceButtonSelected');
				return true;
			case "INS":
				tinyMCE.switchClass(editor_id + '_ins', 'mceButtonSelected');
				return true;
		}
		return true;
	}
};

tinyMCE.addPlugin("xhtmlxtras", TinyMCE_XHTMLXtrasPlugin);
