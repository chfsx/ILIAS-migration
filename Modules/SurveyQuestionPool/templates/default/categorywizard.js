var ilCategoryWizardInput = {
	
	init: function() {			
		this.initEvents($('tr.catwzd').parent());
	},
	
	initEvents: function(rootel) {			
		$(rootel).find('button.categorywizard_add').click(function(e) {
			ilCategoryWizardInput.addRow(e);
		});	
		$(rootel).find('button.categorywizard_remove').click(function(e) {
			ilCategoryWizardInput.removeRow(e);
		});	
		$(rootel).find('button.categorywizard_up').click(function(e) {
			ilCategoryWizardInput.moveRowUp(e);
		});	
		$(rootel).find('button.categorywizard_down').click(function(e) {
			ilCategoryWizardInput.moveRowDown(e);
		});			
	},
	
	addRow: function(e) {				
		// clone row
		var source = $(e.target).parents('tr');				
		var target = $(source).clone();		
		
		// add events
		this.initEvents(target);
		
		// empty inputs
		this.cleanRow(target);
		
		$(source).after(target);	
					
		this.reindexRows($(e.target).parents('tbody'));		
	},
	
	removeRow: function(e) {		
		var source = $(e.target).parents('tr');			
		var tbody = $(e.target).parents('tbody');
		
		// do not remove last row
		if($(tbody).find('tr').size() > 1) {
			$(source).remove();
		}
		// reset last remaining row
		else {
			this.cleanRow(source);
		}
			
		this.reindexRows(tbody);		
	},
	
	moveRowUp: function(e) {		
		var source = $(e.target).parents('tr');					
		var prev = $(source).prev();
		if(prev[0])
		{
			$(prev).before(source);
		}		
	},
	
	moveRowDown: function(e) {		
		var source = $(e.target).parents('tr');		
		var next = $(source).next();
		if(next[0])
		{
			$(next).after(source);
		}
	},
	
	cleanRow: function(row) {
		$(row).find('input:text').attr('value', '');
		$(row).find('input:checkbox').prop('checked', false);
	},
	
	reindexRows: function(tbody) {		
		var postvar = $(tbody).parents('div').attr('id');
		var rowindex = 0;
		var maxscale = 0;
		
		// process all rows
		$(tbody).find('tr').each(function() {
			
			// answer
			$(this).find('input:text[id*="[answer]"]').each(function() {				
				$(this).attr('id', postvar + '[answer][' + rowindex + ']');
				$(this).attr('name', postvar + '[answer][' + rowindex + ']');								
			});
			
			// scale
			$(this).find('input:text[id*="[scale]"]').each(function() {				
				$(this).attr('id', postvar + '[scale][' + rowindex + ']');
				$(this).attr('name', postvar + '[scale][' + rowindex + ']');								
				
				// find current max scale
				var value = $(this).attr('value');
				if (!isNaN(value) && parseInt(value) > maxscale) {
					maxscale = parseInt(value);
				}
			});
			
			// other
			$(this).find('input:checkbox').each(function() {				
				$(this).attr('id', postvar + '[other][' + rowindex + ']');
				$(this).attr('name', postvar + '[other][' + rowindex + ']');												
			});
								
			rowindex++;
		});			
		
		// redo scale values
		$(tbody).find('input:text[id*="[scale]"]').each(function() {	
			var value = $(this).attr('value');		
			if(isNaN(value) || value === '') {
				maxscale++;				
				$(this).attr('value', maxscale);
			}		
		});			
		
		// fix neutral
		var neutral = $('#' + postvar + '_neutral_scale').attr('value');
		if (neutral !== null)
		{
			if (parseInt(neutral) <= maxscale) {
				$('#' + postvar + '_neutral_scale').attr('value', maxscale+1);
			}
		}
	}
};

$(document).ready(function() {
	ilCategoryWizardInput.init();
});