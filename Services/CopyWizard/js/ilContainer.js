/**
 * Disables radio buttons if a parent container option is 'omit'
 *
 * @param   string   the form name
 * @param   string   the checkbox name (or the first characters of the name, if unique)
 * @param   boolean  whether to check or to uncheck the element
 * @return  boolean  always true
 */
function ilDisableChilds(the_form)
{
	var disable = false;
	var stored_depth = 0;

	for(var i=0;i<document.forms[the_form].elements.length;i++)
	{
		var e = document.forms[the_form].elements[i];
		var id_info = e.id.split("_");


		var depth = id_info[0];
		var type = id_info[1];
		var ref_id = id_info[2];
		var action = id_info[3];

		if(disable == true && depth > stored_depth)
		{
			e.disabled = true;
		}
		else
		{
			e.disabled = false;
		}
		if(action != 'omit')
		{
			continue;
		}
		if(disable == true && depth <= stored_depth)
		{
			disable = false;
		}
		if(disable == false && action == 'omit' && e.checked == true)
		{
			stored_depth = depth;
			disable = true;
		}	
	}
  return true;
}
