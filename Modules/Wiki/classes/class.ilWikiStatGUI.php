<?php

/* Copyright (c) 1998-2014 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once "Modules/Wiki/classes/class.ilWikiStat.php";

/**
 * Wiki statistics GUI class
 *
 * @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
 * @version $Id$
 * @ingroup ModulesWiki
 */
class ilWikiStatGUI
{
	protected $wiki_id; // [integer]
	
	public function __construct($a_wiki_id)
	{
		$this->wiki_id = (int)$a_wiki_id;
	}
	
	public function executeCommand()
	{  		
		global $ilCtrl;
		
		$next_class = $ilCtrl->getNextClass($this);
		$cmd = $ilCtrl->getCmd("view");

  		switch($next_class)
		{
			default:
				$this->$cmd();
				break;
		}
	}
	
	protected function viewToolbar($a_is_initial = false)
	{
		global $ilToolbar, $lng, $ilCtrl;
		
		$current_figure = (int)$_POST["fig"];
		$current_time_frame = (string)$_POST["tfr"];
		$current_scope = (int)$_POST["scp"];
		
		include_once "Services/Form/classes/class.ilPropertyFormGUI.php";
		$view = new ilSelectInputGUI($lng->txt("wiki_stat_figure"), "fig");
		$view->setOptions(ilWikiStat::getFigureOptions());
		if($current_figure)
		{
			$view->setValue($current_figure);
		}
		else if($a_is_initial)
		{
			$current_figure = ilWikiStat::KEY_FIGURE_WIKI_NUM_PAGES; // default
		}
		$ilToolbar->addInputItem($view, true);
						
		$options = array();
		include_once "Services/Calendar/classes/class.ilCalendarUtil.php";
		$lng->loadLanguageModule("dateplaner");
		foreach(ilWikiStat::getAvailableMonths($this->wiki_id) as $month)
		{
			$parts = explode("-", $month);
			$options[$month] = ilCalendarUtil::_numericMonthToString((int)$parts[1]).
				" ".$parts[0];
		}
		krsort($options);
		
		$tframe = new ilSelectInputGUI($lng->txt("month"), "tfr");
		$tframe->setOptions($options);			
		if($current_time_frame)
		{		
			$tframe->setValue($current_time_frame);
		}
		else if($a_is_initial)
		{
			$current_time_frame = array_shift(array_keys($options)); // default
		}
		$ilToolbar->addInputItem($tframe, true);
		
		$scope = new ilSelectInputGUI($lng->txt("wiki_stat_scope"), "scp");
		$scope->setOptions(array(
			1 => "1 ".$lng->txt("month"),
			2 => "2 ".$lng->txt("months"),
			3 => "3 ".$lng->txt("months"),
			4 => "4 ".$lng->txt("months"),
			5 => "5 ".$lng->txt("months"),
			6 => "6 ".$lng->txt("months")
		));			
		if($current_scope)
		{		
			$scope->setValue($current_scope);
		}
		else if($a_is_initial)
		{
			$current_scope = 1; // default
		}
		$ilToolbar->addInputItem($scope, true);
		
		$ilToolbar->setFormAction($ilCtrl->getFormAction($this, "view"));
		$ilToolbar->addFormButton($lng->txt("show"), "view");
		
		if($current_figure && $current_time_frame && $current_scope)
		{
			return array(
				"figure" => $current_figure,
				"month" => $current_time_frame,
				"scope" => $current_scope
			);
		}
	}
	
	protected function initial()
	{
		$this->view(true);
	}
	
	protected function view($a_is_initial = false)
	{	
		global $tpl, $lng;
		
		$params = $this->viewToolbar($a_is_initial);
		if(is_array($params))
		{						
			// data
			
			$tfr = explode("-", (string)$params["month"]);
			$day_from = date("Y-m-d", mktime(0, 0, 1, $tfr[1]-($params["scope"]-1), 1, $tfr[0]));
			$day_to = date("Y-m-d", mktime(0, 0, 1, $tfr[1]+1, 0, $tfr[0]));
			unset($tfr);			
			
			$chart_data = $this->getChartData($params["figure"], $params["scope"], $day_from, $day_to);
			$list_data = $this->getListData();
			
			
			// render 
			
			$vtpl = new ilTemplate("tpl.wiki_stat_list.html", true, true, "Modules/Wiki");
			
			$vtpl->setVariable("CHART", $this->renderGraph($params["figure"], $chart_data));
						
			$vtpl->setCurrentBlock("row_bl");
			$counter = 0;
			foreach($list_data as $figure => $values)
			{
				$day = (int)substr($day, 8);
				$vtpl->setVariable("CSS_ROW", ($counter++%2) ? "tblrow1" : "tblrow2");
				$vtpl->setVariable("FIGURE", $figure);
				$vtpl->setVariable("YESTERDAY_VALUE", $values["yesterday"]);
				$vtpl->setVariable("TODAY_VALUE", $values["today"]);
				$vtpl->parseCurrentBlock();
			}
									
			$vtpl->setVariable("BLOCK_TITLE", $lng->txt("statistics"));
			$vtpl->setVariable("FIGURE_HEAD", $lng->txt("wiki_stat_figure"));
			$vtpl->setVariable("YESTERDAY_HEAD", $lng->txt("yesterday"));					
			$vtpl->setVariable("TODAY_HEAD", $lng->txt("today"));					
			
			$tpl->setContent($vtpl->get());						
		}
	}
	
	protected function getChartData($a_figure, $a_scope, $a_from, $a_to)
	{		
		$data = array();
		
		$raw = ilWikiStat::getFigureData($this->wiki_id, $a_figure, $a_from, $a_to);
				
		$parts = explode("-", $a_from);
		for($loop = 0; $loop <= ($a_scope*31); $loop++)
		{				
			$current_day = date("Y-m-d", mktime(0, 0, 1, $parts[1], $parts[2]+$loop, $parts[0]));
			if($current_day <= $a_to)
			{					
				$data[$current_day] = (int)$raw[$current_day];		
			}
		}
			
		return $data;
	}
	
	protected function getListData()
	{		
		$data = array();
		
		$today = date("Y-m-d");
		$yesterday = date("Y-m-d", strtotime("yesterday"));		
				
		foreach(ilWikiStat::getFigureOptions() as $figure => $title)
		{
			$tmp = (array)ilWikiStat::getFigureData($this->wiki_id, $figure, $yesterday, $today);			
			$data[$title] = array(
				"yesterday" => (int)$tmp[$yesterday], 
				"today" => (int)$tmp[$today]
			);			
		}
		
		return $data;
	}
	
	protected function renderGraph($a_figure, array $a_data)
	{
		$scope = ceil(sizeof($a_data)/31);		
		
		include_once "Services/Chart/classes/class.ilChart.php";
		$chart = new ilChart("wikistat", 600, 400);
		$chart->setColors(array("#C0E0FF"));

		$legend = new ilChartLegend();
		$chart->setLegend($legend);
		
		if($a_figure == ilWikiStat::KEY_FIGURE_WIKI_NUM_PAGES)
		{		
			$series = new ilChartData("lines");
			$series->setLineSteps(true);
			$series->setFill(true, "#E0F0FF");
		}
		else
		{
			$series = new ilChartData("bars");			
			$series->setBarOptions(round(10/($scope*2))/10);					
		}
		$series->setLabel(ilWikiStat::getFigureTitle($a_figure));
				
		$labels = array();		
		$x = 0;
		foreach($a_data as $date => $value)
		{						
			$series->addPoint($x, $value);		
			
			$day = (int)substr($date, 8, 2);
					
			// match scale to scope
			if($scope == 1)
			{
				// daily
				$labels[$x] = substr($date, 8, 2);				
			}
			elseif($scope == 2)
			{
				// weekly
				if(!($x%7))
				{
					$labels[$x] = substr($date, 8, 2).".".substr($date, 5, 2).".";
				}
			}
			else
			{
				// 1st/15th
				if($day == 1 || $day == 15 || $x == sizeof($a_data)-1)
				{
					$labels[$x] = substr($date, 8, 2).".".substr($date, 5, 2).".";
				}
			}
						
			$x++;
		}

		$chart->addData($series);
		$chart->setTicks($labels, null, true);
		$chart->setYAxisToInteger(true);
			
		return $chart->getHTML();
	}
}

?>