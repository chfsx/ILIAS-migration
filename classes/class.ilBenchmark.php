<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* performance measurement class
*
* Author: Alex Killing <Alex.Killing@gmx.de>
*
* @version	$Id$
*/
class ilBenchmark
{
	var $bench = array();
	
	/**
	* constructor
	*/
	function ilBenchmark()
	{
	}


	/**
	*
	*/
	function microtimeDiff($t1, $t2)
	{
		$t1 = explode(" ",$t1);
		$t2 = explode(" ",$t2);
		$diff = $t2[0] - $t1[0] + $t2[1] - $t1[1];

		return $diff;
	}



	/**
	* delete all measurement data
	*/
	function clearData()
	{
		global $ilDB;

		$q = "DELETE FROM benchmark";
		$ilDB->manipulate($q);
	}


	/**
	* start measurement
	*
	* @param	string		$type		measurement type
	*
	* @return	int			measurement id
	*/
	function start($a_module, $a_bench)
	{
return;
		if ($this->isEnabled())
		{
			$this->bench[$a_module.":".$a_bench][] = microtime();
		}
	}


	/**
	* stop measurement
	*
	* @param	int			$mid		measurement id
	*/
	function stop($a_module, $a_bench)
	{
return;
		if ($this->isEnabled())
		{
			$this->bench[$a_module.":".$a_bench][count($this->bench[$a_module.":".$a_bench]) - 1]
				= $this->microtimeDiff($this->bench[$a_module.":".$a_bench][count($this->bench[$a_module.":".$a_bench]) - 1], microtime());
		}
	}


	/**
	* save all measurements
	*/
	function save()
	{
		global $ilDB;
return;
		if ($this->isEnabled() &&
			($this->getMaximumRecords() > $this->getCurrentRecordNumber()))
		{
			foreach($this->bench as $key => $bench)
			{
				$bench_arr = explode(":", $key);
				$bench_module = $bench_arr[0];
				$benchmark = $bench_arr[1];
				foreach($bench as $time)
				{
					$q = "INSERT INTO benchmark (cdate, duration, module, benchmark) VALUES ".
						"(".
						$ilDB->now().", ".
						$ilDB->quote($time, "float").", ".
						$ilDB->quote($bench_module, "text").", ".
						$ilDB->quote($benchmark, "text").")";
					$ilDB->manipulate($q);
				}
			}
			$this->bench = array();
		}
	}


	/*
	SELECT module, benchmark, COUNT(*) AS cnt, AVG(duration) AS avg_dur FROM benchmark
	GROUP BY module, benchmark ORDER BY module, benchmark
	*/

	/**
	* get performance evaluation data
	*/
	function getEvaluation($a_module)
	{
		global $ilDB;

		$q = "SELECT COUNT(*) AS cnt, AVG(duration) AS avg_dur, benchmark,".
			" MIN(duration) AS min_dur, MAX(duration) AS max_dur".
			" FROM benchmark".
			" WHERE module = ".$ilDB->quote($a_module, "text")." ".
			" GROUP BY benchmark".
			" ORDER BY benchmark";
		$bench_set = $ilDB->query($q);
		$eva = array();
		while($bench_rec = $ilDB->fetchAssoc($bench_set))
		{
			$eva[] = array("benchmark" => $bench_rec["benchmark"],
				"cnt" => $bench_rec["cnt"], "duration" => $bench_rec["avg_dur"],
				"min" => $bench_rec["min_dur"], "max" => $bench_rec["max_dur"]);
		}
		return $eva;
	}


	/**
	* get current number of benchmark records
	*/
	function getCurrentRecordNumber()
	{
		global $ilDB;

		$q = "SELECT COUNT(*) AS cnt FROM benchmark";
		$cnt_set = $ilDB->query($q);
		$cnt_rec = $ilDB->fetchAssoc($cnt_set);

		return $cnt_rec["cnt"];
	}


	/**
	* get maximum number of benchmark records
	*/
	function getMaximumRecords()
	{
		global $ilias;

		return $ilias->getSetting("bench_max_records");
	}


	/**
	* set maximum number of benchmark records
	*/
	function setMaximumRecords($a_max)
	{
		global $ilias;

		$ilias->setSetting("bench_max_records", (int) $a_max);
	}


	/**
	* check wether benchmarking is enabled or not
	*/
	function isEnabled()
	{
		global $ilSetting;

		if (!is_object($ilSetting))
		{
			return true;
		}

		return (boolean) $ilSetting->get("enable_bench");
	}


	/**
	* enable benchmarking
	*/
	function enable($a_enable)
	{
		global $ilias;

		if ($a_enable)
		{
			$ilias->setSetting("enable_bench", 1);
		}
		else
		{
			$ilias->setSetting("enable_bench", 0);
		}
	}


	/**
	* get all current measured modules
	*/
	function getMeasuredModules()
	{
		global $ilDB;

		$q = "SELECT DISTINCT module FROM benchmark";
		$mod_set = $ilDB->query($q);

		$modules = array();
		while ($mod_rec = $ilDB->fetchAssoc($mod_set))
		{
			$modules[$mod_rec["module"]] = $mod_rec["module"];
		}

		return $modules;
	}

	// BEGIN WebDAV: Get measured time.
	/**
	* Get measurement.
	*
	* @return	Measurement in milliseconds.
	*/
	function getMeasuredTime($a_module, $a_bench)
	{
		if (isset($this->bench[$a_module.":".$a_bench]))
		{
			return $this->bench[$a_module.":".$a_bench][count($this->bench[$a_module.":".$a_bench]) - 1];
		}
		return false;
	}
	// END WebDAV: Get measured time.

}

?>
