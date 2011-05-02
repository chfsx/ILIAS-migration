
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

ilCOPagePres =
{
	/**
	 * Basic init function
	 */
	init: function ()
	{
		this.initToc();
		this.initInteractiveImages();
	},
	
	//
	// Toc (as used in Wikis)
	//
	
	/**
	 * Init the table of content
	 */
	initToc: function ()
	{
		// init toc
		var cookiePos = document.cookie.indexOf("pg_hidetoc=");
		if (cookiePos > -1 && document.cookie.charAt(cookiePos + 11) == 1)
		{
			this.toggleToc();
		}
	},

	/**
	 * Toggle the table of content
	 */
	toggleToc: function()
	{
		var toc = document.getElementById('ilPageTocContent');

		if (!toc)
		{
			return;
		}
		var toc_on = document.getElementById('ilPageTocOn');
		var toc_off = document.getElementById('ilPageTocOff');
		if (toc && toc.style.display == 'none')
		{
			toc.style.display = 'block';
			toc_on.style.display = 'none';
			toc_off.style.display = '';
			document.cookie = "pg_hidetoc=0";
		}
		else
		{
			toc_on.style.display = '';
			toc_off.style.display = 'none';
			toc.style.display = 'none';
			document.cookie = "pg_hidetoc=1";
		}
	},
	
	//
	// Interactive Images
	//

	iim_trigger: {},
	iim_area: {},
	iim_popup: {},

	/**
	 * Init interactive images
	 */
	initInteractiveImages: function ()
	{
		// preload overlay images (necessary?)
		
		// add onmouseover event to all map areas
		$("map.iim > area").mouseover(this.overIIMArea);
		$("map.iim > area").mouseout(this.outIIMArea);
		$("map.iim > area").click(this.clickBaseArea);
	},
	
	/**
	 * Mouse over base image map area -> show the overlay image
	 * and (on first time) init the image map of the overlay image
	 */
	overIIMArea: function (e)
	{
//console.log("enter");
		var k, j, tr, coords, ovx, ovy;
		var t = ilCOPagePres.iim_area[e.target.id].title;
		var iim_id = ilCOPagePres.iim_area[e.target.id].iim_id;

		for (k in ilCOPagePres.iim_trigger)
		{
			tr = ilCOPagePres.iim_trigger[k];
			if (tr.title == t && tr.iim_id == iim_id)
			{
				var base = $("img#base_img_" + tr.iim_id);
//console.log("get base" + tr['tr_id']);
				var pos = base.position();
				var ov = $("img#iim_ov_" + tr['tr_id']);
				var cnt = 1;
				var base_map_name = base.attr('usemap').substr(1);
				
				// display the overlay at the correct position
				ov.css('position', 'absolute');
				ovx = parseInt(tr['ovx']);
				ovy = parseInt(tr['ovy']);
				ov.css('left', pos.left + ovx);
				ov.css('top', pos.top + ovy);
				ov.css('display', '');

				// on first time we need to initialize the
				// image map of the overlay image
				if (tr.map_initialized == null)
				{
					tr.map_initialized = true;
//console.log(tr);
					cnt = 1;
					$("map[name='" + base_map_name + "'] > area").each(
						function (i,el) {
							// if title is the same, add area to overlay map
							if (ilCOPagePres.iim_area[el.id]['title'] == t)
							{
								coords = $(el).attr("coords");
								// fix coords
								switch($(el).attr("shape").toLowerCase())
								{
									case "rect":
										var c = coords.split(",");
										coords = "" + (parseInt(c[0]) - ovx) + "," +
											(parseInt(c[1]) - ovy) + "," +
											(parseInt(c[2]) - ovx) + "," +
											(parseInt(c[3]) - ovy);
										break;
										
									case "poly":
										var c = coords.split(",");
										coords = "";
										var sep = "";
										for (j in c)
										{
											if (j % 2 == 0)
											{
												coords = sep + c[j] - ovx;
											}
											else
											{
												coords = sep + c[j] - ovy;
											}
											sep = ",";
										}
										break;
										
									case "circle":
										var c = coords.split(",");
										coords = "" + (parseInt(c[0]) - ovx) + "," +
											(parseInt(c[1]) - ovy) + "," +
											(parseInt(c[2]));
										break;
								}
								
								// set shape and coords
								$("area#iim_ov_area_" + tr['tr_id']).attr("coords", coords);
								$("area#iim_ov_area_" + tr['tr_id']).attr("shape", $(el).attr("shape"));
								
								// add mouse event listeners
								var k2 = k;
								var i2 = "iim_ov_" + tr['tr_id'];
								var tr2 = tr['tr_id'];
  								$("area#iim_ov_area_" + tr['tr_id']).mouseover(
  									function() {ilCOPagePres.overOvArea(k2, true, i2)});
  								$("area#iim_ov_area_" + tr['tr_id']).mouseout(
  									function() {ilCOPagePres.overOvArea(k2, false, i2)});
  								$("area#iim_ov_area_" + tr['tr_id']).click(
  									function(e) {ilCOPagePres.clickOvArea(e, tr2)});
							}
							cnt++;
						});
				}
			}
		}
	},
	
	/**
	 * Leave a base image map area: hide corresponding images
	 */
	outIIMArea: function (e)
	{
//console.log("out");
		var k, tr;
		var t = ilCOPagePres.iim_area[e.target.id].title;
		var iim_id = ilCOPagePres.iim_area[e.target.id].iim_id;
		for (k in ilCOPagePres.iim_trigger)
		{
			tr = ilCOPagePres.iim_trigger[k];
			if (tr.title == t && tr.iim_id == iim_id &&
				(ilCOPagePres.iim_trigger[k]['over_ov_area'] == null ||
					!ilCOPagePres.iim_trigger[k]['over_ov_area']
				))
			{
				$("img#iim_ov_" + tr['tr_id']).css('display', 'none');
			}
		}
	},
	
	/**
	 * Triggered by mouseover/out on imagemap of overlay image
	 */
	overOvArea: function (k, value, ov_id)
	{
//console.log("overOvArea " + k + ":" + ov_id);
		ilCOPagePres.iim_trigger[k]['over_ov_area'] = value;
		if (value)
		{
			$("img#" + ov_id).css('display', '');
		}
		else
		{
			$("img#" + ov_id).css('display', 'none');
		}
	},
	
	/**
	 * A base image map area is clicked
	 */
	clickBaseArea: function (e)
	{
		var k;
		var t = ilCOPagePres.iim_area[e.target.id].title;
		var iim_id = ilCOPagePres.iim_area[e.target.id].iim_id;

		// iterate through the triggers and search the correct one
		for (k in ilCOPagePres.iim_trigger)
		{
			tr = ilCOPagePres.iim_trigger[k];
			if (tr.title == t && tr.iim_id == iim_id)
			{
				ilCOPagePres.handleAreaClick(e, tr['tr_id']);
			}
		}
	},
	
	/**
	 * Handle area click (triggered by base or overlay image map area)
	 */
	handleAreaClick: function (e, tr_id)
	{
		var tr = ilCOPagePres.iim_trigger[tr_id];
		
		// on first time we need to initialize content overlay
		if (tr.popup_initialized == null)
		{
			tr.popup_initialized = true;
			
			// @todo: initialize the overlay
			/*
			ilOverlay.add("iim_popup_" + tr.tr_id,
				{"yuicfg":{"visible":false,"fixedcenter":false,
					"context":["iim_ov_area_" + tr.tr_id,"tl","bl",["beforeShow","windowResize"]]},
				"trigger":"iim_ov_area_" + tr.tr_id,
				"trigger_event":"click",
				"anchor_id":"iim_ov_area_" + tr.tr_id,
				"auto_hide":false,
				"close_el":"iim_ov_area_" + tr.tr_id});
			*/
			ilOverlay.add("iim_popup_" + tr_id,
				{"yuicfg":{"visible":false,"fixedcenter":true},
				"auto_hide":false});
		}
		
		// @todo: show the overlay
		ilOverlay.show(e, "iim_popup_" + tr_id, null, true, null, null);

		e.preventDefault();
	},
	
	/**
	 * A overlay image map area is clicked
	 */
	clickOvArea: function (e, tr_id)
	{
		ilCOPagePres.handleAreaClick(e, tr_id);
	},

	addIIMTrigger: function(tr)
	{
		this.iim_trigger[tr.tr_id] = tr;
	},
	
	addIIMArea: function(a)
	{
		this.iim_area[a.area_id] = a;
	},
	
	addIIMPopup: function(p)
	{
		this.iim_popup[p.pop_id] = p;
	}
}
ilAddOnLoad(function() {ilCOPagePres.init();});
