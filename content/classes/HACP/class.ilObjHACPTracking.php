<?

require_once("content/classes/AICC/class.ilObjAICCTracking.php");
require_once("content/classes/HACP/class.ilHACPResponse.php");


class ilObjHACPTracking extends ilObjAICCTracking {

	/**
	* Constructor
	* @access	public
	*/
	function ilObjHACPTracking($ref_id, $obj_id)
	{
		global $ilias, $HTTP_POST_VARS;
		global $ilDB, $ilUser;
		
/*
		if (is_object($ilUser))
		{
			$user_id = $ilUser->getId();
		
		}
		
		$fp=fopen("./content/hacp.log", "a+");
		fputs($fp, "test 17\n");	
		fputs($fp, "ilDB=$ilDB\n");	
		fputs($fp, "user_id=$user_id\n");	
		fputs($fp, "user_class=".get_class($ilUser)."\n");	
		fclose($fp);	
*/		
		
		//just to make sure to extract only this parameter 
		$mainKeys=array("command", "version", "session_id", "aicc_data");
		$postVars=array_change_key_case($HTTP_POST_VARS, CASE_LOWER);
		foreach($mainKeys as $key) {
			$$key=$postVars[$key];
		}
		
		//only allowed commands
		$allowedCommands=array("getparam", "putparam", "exitau");
		if (!in_array($command, $allowedCommands)) {
			exit;
		}
			
		$fp=fopen("./content/hacp.log", "a+");
		fputs($fp, "$command ref_id=$ref_id, obj_id=$obj_id\n");	
		fclose($fp);			
		
		$this->$command($ref_id, $obj_id, $version, $aicc_data);
		
	}
	
	function getparam($ref_id, $obj_id, $version, $aicc_data) {
		//if (empty($aicc_data)) {
		//	$this->startau($ref_id, $obj_id, $version, $aicc_data);
		//	return;
		//}
		
		$response=new ilHACPResponse($ref_id, $obj_id);
		$response->sendParam();	
		
/*		
		$fp=fopen("./content/hacp.log", "a+");
		fputs($fp, "getparam ref_id=$ref_id, obj_id=$obj_id, aicc_data=$aicc_data\n");	
		fclose($fp);	
*/
	}
	
	function putparam($ref_id, $obj_id, $version, $aicc_data) {
		//aiccdata is a non standard ini format
		//$data=parse_ini_file($tmpFilename, TRUE);
		$data=$this->parseAICCData($aicc_data);

		//choose either insert or update to be able to inherit superclass
		global $ilDB, $ilUser;
		$this->update=array();
		$this->insert=array();
		if (is_object($ilUser)) {
			$user_id = $ilUser->getId();
			foreach ($data as $key=>$value) {
				$stmt = "SELECT * FROM scorm_tracking WHERE user_id = ".$ilDB->quote($user_id).
					" AND sco_id = ".$ilDB->quote($obj_id)." AND lvalue = ".$ilDB->quote($key);
				$set = $ilDB->query($stmt);
				if ($rec = $set->fetchRow(DB_FETCHMODE_ASSOC))
					$this->update[] = array("left" => $key, "right" => $value);
				else
					$this->insert[] = array("left" => $key, "right" => $value);
			}
		}
		
		//store
		$this->store($ref_id, $obj_id, 0);
		
		$response=new ilHACPResponse($ref_id, $obj_id);
		$response->sendOk();
	}

	function exitau($ref_id, $obj_id, $version, $aicc_data) {
		$response=new ilHACPResponse($ref_id, $obj_id);
		$response->sendOk();
	}
	
	function startau($ref_id, $obj_id, $version, $aicc_data) {
		$response=new ilHACPResponse($ref_id, $obj_id);
		$response->sendParam();	
	}

	function parseAICCData($string) {
		$data=array();
		if (!empty($string)) {
			$lines=explode("\n", $string);
			for($i=0;$i<count($lines);$i++) {
				$line=trim($lines[$i]);
				if (empty($line) || substr($line,0,1)==";" || substr($line,0,1)=="#"){
					continue;
				}
				if (substr($line,0,1)=="[") {
					$block=substr($line,1,-1);
					continue;
				}
				if (empty($block))
					continue;
				
				if (substr_count($line, "=")==0)
					$data[strtolower("cmi.".$block)]=$line;
				else if (substr_count($line, "=")==1) {
					$line=explode("=", $line);
					$data[strtolower("cmi.".$block.".".$line[0])]=$line[1];
				}
			}
		}
		return $data;
	}
	
}

?>