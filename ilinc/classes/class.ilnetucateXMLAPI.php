<?php

require_once "class.ilnetucateResponse.php";
require_once "./classes/class.ilXmlWriter.php";

/**
* API to communicate with a the CMSAPI of centra
* (c) Sascha Hofmann, 2004
*  
* @author	Sascha Hofmann <saschahofmann@gmx.de>
* @version	$Id$
* 
* @package	ilias-modules
*/
class ilnetucateXMLAPI extends ilXmlWriter
{
	/**
	* Constructor
	* @access	public
	*/
	function ilnetucateXMLAPI()
	{
		global $ilias;

		parent::ilXmlWriter();

		$this->ilias =& $ilias;

		$this->login = $this->ilias->ini->readVariable("iLinc","login");
		$this->passwd = $this->ilias->ini->readVariable("iLinc","passwd");
		$this->customer_id = $this->ilias->ini->readVariable("iLinc","customer_id");
		$this->server_addr	= $this->ilias->ini->readVariable("iLinc","server_addr");
		$this->server_port	= $this->ilias->ini->readVariable("iLinc","server_port");

	}
	
	function xmlFormatData($a_data)
	{
		return $a_data;
	}
	
	function setServerAddr($a_server_addr)
	{
		$this->server_addr = $a_server_addr;
	}
	
	function getServerAddr()
	{
		return $this->server_addr;
	}
	
	function getServerPort()
	{
		return $this->server_port;
	}

	function getCustomerID()
	{
		return $this->customer_id;
	}

	function setRequest($a_data)
	{
		$this->request = $a_data;
	}
	
	// send request to Centra server
	// returns true if request was successfully sent (a response returned)
	function sendRequest($a_request = '')
	{
		$this->request = $this->xmlDumpMem();

		/*switch ($a_request)
		{
			case "addClass":
				$this->request = ereg_replace('></netucate.Class>','/>',$this->request);
				break;
			
			default:
			
				break;
		}*/


		//var_dump($this->request);exit;
		
		$sock = fsockopen($this->getServerAddr(), $this->getServerPort(), $errno, $errstr, 30);
		if (!$sock) die("$errstr ($errno)\n");
		
		fputs($sock, "POST /campus/XMLAPI/netucateXMLAPI.asp HTTP/1.0\r\n");
		fputs($sock, "Host: ".$this->getServerAddr()."\r\n");
		fputs($sock, "Content-type: text/xml\r\n");
		fputs($sock, "Content-length: " . strlen($this->request) . "\r\n");
		fputs($sock, "Accept: */*\r\n");
		fputs($sock, "\r\n");
		fputs($sock, $this->request."\r\n");
		fputs($sock, "\r\n");
		
		$headers = "";
		
		while ($str = trim(fgets($sock, 4096)))
		{
			$headers .= "$str\n";
		}
		
		$response = "";

		while (!feof($sock))
		{
			$response .= fgets($sock, 4096);
		}
		
		fclose($sock);
		

		// return netucate response object
		$response_obj =  new ilnetucateResponse($response);
		
		/*if ($a_request == "registerUser")
		{
		var_dump($response,$response_obj->data);exit;
		}*/

		return $response_obj;
	}
	
	function addUser(&$a_user_obj)
	{
		$this->xmlHeader();

		$this->xmlStartTag('netucate.API.Request');
		
		$attr = array();
		$attr['user'] = $this->login;
		$attr['password'] = $this->passwd;
		$attr['customerid'] = $this->customer_id;
		$attr['id'] = "";
		$attr['command'] = "Add";
		$attr['object'] = "User";
		$this->xmlStartTag('netucate.Command',$attr);
		$this->xmlEndTag('netucate.Command');

		$attr = array();
		$attr['loginname'] = $a_user_obj->getLogin();
		$attr['fullname'] = $a_user_obj->getFullname();
		$attr['password'] = $a_user_obj->getPasswd();
		$this->xmlStartTag('netucate.User',$attr);
		$this->xmlEndTag('netucate.User');
		
		$this->xmlEndTag('netucate.API.Request');
	}

	function registerUser($a_ilinc_user_id,$a_ilinc_course_id,$a_instructor = "False")
	{
		$this->xmlHeader();

		$this->xmlStartTag('netucate.API.Request');
		
		$attr = array();
		$attr['user'] = $this->login;
		$attr['password'] = $this->passwd;
		$attr['customerid'] = $this->customer_id;
		$attr['id'] = "";
		$attr['command'] = "Register";
		$attr['object'] = "User";
		$this->xmlStartTag('netucate.Command',$attr);
		$this->xmlEndTag('netucate.Command');

		$attr = array();
		$attr['courseid'] = $a_ilinc_course_id;
		$this->xmlStartTag('netucate.Course',$attr);

		$this->xmlStartTag('netucate.User.List');

		$attr = array();
		$attr['userid'] = $a_ilinc_user_id;
		$attr['instructorflag'] =$a_instructor;
		$this->xmlStartTag('netucate.User',$attr);
		$this->xmlEndTag('netucate.User');
		
		$this->xmlEndTag('netucate.User.List');
		
		$this->xmlEndTag('netucate.Course');
		
		$this->xmlEndTag('netucate.API.Request');
	}

	function unregisterUser(&$a_user_obj)
	{
		$this->xmlHeader();

		$this->xmlStartTag('netucate.API.Request');
		
		$attr = array();
		$attr['user'] = $this->login;
		$attr['password'] = $this->passwd;
		$attr['customerid'] = $this->customer_id;
		$attr['id'] = "";
		$attr['command'] = "UnRegister";
		$attr['object'] = "User";
		$this->xmlStartTag('netucate.Command',$attr);
		$this->xmlEndTag('netucate.Command');

		$attr = array();
		$attr['courseid'] = "2191";
		$this->xmlStartTag('netucate.Course',$attr);

		$this->xmlStartTag('netucate.User.List');

		$attr = array();
		$attr['userid'] = "2191";
		$attr['instructorflag'] = "True";
		$this->xmlStartTag('netucate.User',$attr);
		$this->xmlEndTag('netucate.User');
		
		$attr = array();
		$attr['userid'] = "2192";
		$attr['instructorflag'] = "False";
		$this->xmlStartTag('netucate.User',$attr);
		$this->xmlEndTag('netucate.User');
		
		$this->xmlEndTag('netucate.User.List');
		
		$this->xmlEndTag('netucate.Course');
		
		$this->xmlEndTag('netucate.API.Request');
	}
	
	function findUser(&$a_user_obj)
	{
		$this->xmlHeader();

		$this->xmlStartTag('netucate.API.Request');
		
		$attr = array();
		$attr['user'] = $this->login;
		$attr['password'] = $this->passwd;
		$attr['customerid'] = $this->customer_id;
		$attr['id'] = "";
		$attr['command'] = "Find";
		$attr['object'] = "User";
		$this->xmlStartTag('netucate.Command',$attr);
		$this->xmlEndTag('netucate.Command');

		$attr = array();
		$attr['userid'] = "2191";
		$attr['loginname'] = "ffuss";
		$attr['fullname'] = "Fred Fuss";
		$attr['lotnumber'] = "1";
		$this->xmlStartTag('netucate.User',$attr);
		$this->xmlEndTag('netucate.User');
		
		$this->xmlEndTag('netucate.API.Request');
	}
	
	function removeUser(&$a_user_obj)
	{
		$this->xmlHeader();

		$this->xmlStartTag('netucate.API.Request');
		
		$attr = array();
		$attr['user'] = $this->login;
		$attr['password'] = $this->passwd;
		$attr['customerid'] = $this->customer_id;
		$attr['id'] = "";
		$attr['command'] = "Remove";
		$attr['object'] = "User";
		$this->xmlStartTag('netucate.Command',$attr);
		$this->xmlEndTag('netucate.Command');

		$this->xmlStartTag('netucate.User.List');

		$attr = array();
		$attr['userid'] = "2191";
		$attr['instructorflag'] = "True";
		$this->xmlStartTag('netucate.User',$attr);
		$this->xmlEndTag('netucate.User');
		
		$attr = array();
		$attr['userid'] = "2192";
		$attr['loginname'] = "ffuss";
		$this->xmlStartTag('netucate.User',$attr); // userid or loginname per User are required.
		$this->xmlEndTag('netucate.User');
		
		$this->xmlEndTag('netucate.User.List');
		
		$this->xmlEndTag('netucate.API.Request');
	}
	
	function addClass($a_icla_arr,$a_icrs_id)
	{
		$this->xmlHeader();

		$this->xmlStartTag('netucate.API.Request');
		
		$attr = array();
		$attr['user'] = $this->login;
		$attr['password'] = $this->passwd;
		$attr['customerid'] = $this->customer_id;
		$attr['id'] = "";
		$attr['command'] = "Add";
		$attr['object'] = "Class";
		$this->xmlStartTag('netucate.Command',$attr);
		$this->xmlEndTag('netucate.Command');

		$attr = array();
		$attr['courseid'] = $a_icrs_id;
		$attr['name'] = $a_icla_arr['title'];
		$this->xmlStartTag('netucate.Class',$attr);
		$this->xmlEndTag('netucate.Class');
		
		$this->xmlEndTag('netucate.API.Request');
	}

	function editClass(&$a_user_obj)
	{
		$this->xmlHeader();

		$this->xmlStartTag('netucate.API.Request');
		
		$attr = array();
		$attr['user'] = $this->login;
		$attr['password'] = $this->passwd;
		$attr['customerid'] = $this->customer_id;
		$attr['id'] = "";
		$attr['command'] = "Edit";
		$attr['object'] = "Class";
		$this->xmlStartTag('netucate.Command',$attr);
		$this->xmlEndTag('netucate.Command');

		$attr = array();
		$attr['classid'] = "2191";
		$attr['name'] = "New Name";
		$attr['instructoruserid'] = "";
		$attr['bandwidth'] = "";
		$attr['appsharebandwidth'] = "";
		$attr['description'] = "";
		$attr['alwaysopen'] = "";
		$attr['password'] = "";
		$attr['message'] = "";
		$attr['floorpolicy'] = "";
		$attr['conferencetypeid'] = "";
		$attr['videobandwidth'] = "";
		$attr['videoframerate'] = "";
		$attr['enablepush'] = "";
		$attr['issecure'] = "";
		$attr['akclassvalue1'] = "";
		$attr['akclassvalue2'] = "";
		$this->xmlStartTag('netucate.Class',$attr);
		$this->xmlEndTag('netucate.Class');
		
		$this->xmlEndTag('netucate.API.Request');
	}
	
	function joinClass(&$a_user_obj,$a_ilinc_class_id)
	{
		$this->xmlHeader();

		$this->xmlStartTag('netucate.API.Request');
		
		$attr = array();
		$attr['user'] = $a_user_obj->getLogin();
		$attr['password'] = $a_user_obj->getPasswd();
		$attr['customerid'] = $this->customer_id;
		$attr['id'] = "";
		$attr['task'] = "JoinClass";
		$attr['classid'] = $a_ilinc_class_id;
		$this->xmlStartTag('netucate.Task',$attr);
		$this->xmlEndTag('netucate.Task');

		$this->xmlEndTag('netucate.API.Request');
	}

	function removeClass($a_icla_id)
	{
		$this->xmlHeader();

		$this->xmlStartTag('netucate.API.Request');
		
		$attr = array();
		$attr['user'] = $this->login;
		$attr['password'] = $this->passwd;
		$attr['customerid'] = $this->customer_id;
		$attr['id'] = "";
		$attr['command'] = "Remove";
		$attr['object'] = "Class";
		$this->xmlStartTag('netucate.Command',$attr);
		$this->xmlEndTag('netucate.Command');

		$this->xmlStartTag('netucate.Class.List');

		$attr = array();
		$attr['classid'] = $a_icla_id;
		$this->xmlStartTag('netucate.Class',$attr);
		$this->xmlEndTag('netucate.Class');
		
		$this->xmlEndTag('netucate.Class.List');
		
		$this->xmlEndTag('netucate.API.Request');
	}
	
	function findCourseClasses($a_icrs_id)
	{
		$this->xmlHeader();

		$this->xmlStartTag('netucate.API.Request');
		
		$attr = array();
		$attr['user'] = $this->login;
		$attr['password'] = $this->passwd;
		$attr['customerid'] = $this->customer_id;
		$attr['id'] = "";
		$attr['command'] = "Find";
		$attr['object'] = "CourseClasses";
		$this->xmlStartTag('netucate.Command',$attr);
		$this->xmlEndTag('netucate.Command');

		$attr = array();
		$attr['courseid'] = $a_icrs_id;
		$this->xmlStartTag('netucate.Course',$attr);
		$this->xmlEndTag('netucate.Course');
		
		$this->xmlEndTag('netucate.API.Request');
	}
	
	function addCourse(&$a_icrs_arr)
	{
		$this->xmlHeader();

		$this->xmlStartTag('netucate.API.Request');
		
		$attr = array();
		$attr['user'] = $this->login;
		$attr['password'] = $this->passwd;
		$attr['customerid'] = $this->customer_id;
		$attr['id'] = "";
		$attr['command'] = "Add";
		$attr['object'] = "Course";
		$this->xmlStartTag('netucate.Command',$attr);
		$this->xmlEndTag('netucate.Command');

		$attr = array();
		$attr['name'] = $a_icrs_arr['title'];
		$attr['homepage'] = $a_icrs_arr['homepage']; // (optional; if present and not empty, the value will be changed)
		$attr['download'] = $a_icrs_arr['download']; // (optional; if present and not empty, the value will be changed)
		$attr['description'] = $a_icrs_arr['desc']; // (optional; if present and not empty, the value will be changed)
		$this->xmlStartTag('netucate.Course',$attr);
		$this->xmlEndTag('netucate.Course');
		
		$this->xmlEndTag('netucate.API.Request');
	}

	function editCourse(&$a_user_obj)
	{
		$this->xmlHeader();

		$this->xmlStartTag('netucate.API.Request');
		
		$attr = array();
		$attr['user'] = $this->login;
		$attr['password'] = $this->passwd;
		$attr['customerid'] = $this->customer_id;
		$attr['id'] = "";
		$attr['command'] = "Edit";
		$attr['object'] = "Course";
		$this->xmlStartTag('netucate.Command',$attr);
		$this->xmlEndTag('netucate.Command');

		// Modifies any or all of the fields in a Course record. An empty parameter in an existing attribute (except the name) will cause the corresponding field to be cleared.
		$attr = array();
		$attr['courseid'] = "2191"; // (required; existing courseID)
		$attr['name'] = "New Name"; // (optional; if present and not empty, the value will be changed)
		$attr['homepage'] = ""; // (optional; if present and not empty, the value will be changed)
		$attr['download'] = ""; // (optional; if present and not empty, the value will be changed)
		$attr['description'] = ""; // (optional; if present and not empty, the value will be changed)
		$this->xmlStartTag('netucate.Course',$attr);
		$this->xmlEndTag('netucate.Course');
		
		$this->xmlEndTag('netucate.API.Request');
	}
	
	function removeCourse($a_icrs_id)
	{
		$this->xmlHeader();

		$this->xmlStartTag('netucate.API.Request');
		
		$attr = array();
		$attr['user'] = $this->login;
		$attr['password'] = $this->passwd;
		$attr['customerid'] = $this->customer_id;
		$attr['id'] = "";
		$attr['command'] = "Remove";
		$attr['object'] = "Course";
		$this->xmlStartTag('netucate.Command',$attr);
		$this->xmlEndTag('netucate.Command');

		$this->xmlStartTag('netucate.Course.List');

		$attr = array();
		$attr['courseid'] = $a_icrs_id;
		$this->xmlStartTag('netucate.Class',$attr);
		$this->xmlEndTag('netucate.Class');
		
		/*
		$attr = array();
		$attr['courseid'] = "2191";
		$this->xmlStartTag('netucate.Course',$attr);
		$this->xmlEndTag('netucate.Course');
		*/

		$this->xmlEndTag('netucate.Course.List');
		
		$this->xmlEndTag('netucate.API.Request');
	}
}
?>