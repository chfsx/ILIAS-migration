function popup_window(url, x1, x2, y1, y2)
{
	var xpos;

	xpos = screen.availWidth / 100 * x1;
	ypos = screen.availHeight / 100 * y1;
	xwidth = (screen.availWidth / 100 * (x2 - x1)) - 5;
	yheight = (screen.availHeight / 100 * (y2 - y1)) - 30;

	window.open(url,"list","height=" + yheight + ",width=" + xwidth + ",left=" +xpos + ",ScreenX=" + xpos + ",ScreenY=" + ypos + ",top=" + ypos + ",resizable=yes,menubar=no,status=no,directories=no,toolbar=no,scrollbars=yes");

	return false;
}

function CheckAll(){
	if(document.cmd.all)
	{
		var c = document.cmd.all.checked;
	}
	for (var i=0;i<document.cmd.elements.length;i++)
	{
		var e = document.cmd.elements[i];
 	  	if(e.name != 'all') e.checked = c;
   	}
}

function isEmpty(form, a_values, a_checks) 
{	
	feed_back = "";
	
	if (a_values != "")
	{
		if (a_values == "all")
		{
			for(var i=0;i<form.length;i++)
			{				
				if (form.elements[i].type == "text" || form.elements[i].type == "textarea")
				{
					if (form.elements[i].value == "")
						feed_back += "-> " + form.elements[i].id + "\n";
				}
			}
		}
	}
	
	if (feed_back != "") {
		alert("Please insert these data:\n\n" + feed_back);
		return false;
	}
	
	return true;
}

function printPage()
{
	window.print();
	return true;
}
