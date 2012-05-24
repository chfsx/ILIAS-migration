function ilFrmQuoteAjaxHandler(t, ed)
{	
	var ilFrmQuoteCallback =
	{
		success: function(o) {
			if(typeof o.responseText != "undefined")
			{
				var uid = 'frm_quote_' + new Date().getTime();
				tinyMCE.execCommand("mceInsertContent", false, t._ilfrmquote2html(ed, o.responseText) + '<p id="' + uid + '">&nbsp;</p>');

				var rng = tinymce.DOM.createRng();
				var newNode = ed.dom.select('#' + uid)[0];
				rng.setStart(newNode, 0);
				rng.setEnd(newNode, 0);
				ed.selection.setRng(rng);
				ed.focus();
			}
		},
		failure:  function(o) {
			//alert('ilFrmQuoteFailureHandler');
		}
	};

	var request = YAHOO.util.Connect.asyncRequest('GET', '{IL_FRM_QUOTE_CALLBACK_SRC}', ilFrmQuoteCallback);
	
	return false;
}