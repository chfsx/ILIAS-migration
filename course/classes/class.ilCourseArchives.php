<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2001 ILIAS open source, University of Cologne            |
	|                                                                             |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
	+-----------------------------------------------------------------------------+
*/


/**
* class ilobjcourse
*
* @author Stefan Meyer <smeyer@databay.de> 
* @version $Id$
* 
* @extends Object
* @package ilias-core
*/

class ilCourseArchives
{
	var $course_obj;
	var $ilias;
	var $ilErr;
	var $ilDB;
	var $tree;
	var $lng;

	var $archive_type;
	var $archive_date;
	var $archive_size;
	var $archive_name;

	var $course_files_obj;
	var $course_xml_writer;


	function ilCourseArchives(&$course_obj)
	{
		global $ilErr,$ilDB,$lng,$tree,$ilias;

		$this->ilias =& $ilias;
		$this->ilErr =& $ilErr;
		$this->ilDB  =& $ilDB;
		$this->lng   =& $lng;
		$this->tree  =& $tree;

		$this->ARCHIVE_XML = 1;
		$this->ARCHIVE_HTML = 2;
		$this->ARCHIVE_PDF = 3;

		$this->course_obj =& $course_obj;

		$this->__read();
	}

	// SET GET
	function getArchives()
	{
		return $this->archives;
	}
	
	function getArchive($a_id)
	{
		return $this->archives[$a_id];
	}

	function getPublicArchives()
	{
		foreach($this->archives as $id => $archive)
		{
			if($archive['archive_type'] == $this->ARCHIVE_XML)
			{
				continue;
			}
			if($this->course_obj->getArchiveType() != $this->course_obj->ARCHIVE_DOWNLOAD and
				$archive['archive_type'] == $this->ARCHIVE_PDF)
			{
				continue;
			}
			$public_archives[$id] = $archive;
		}
		
		return $public_archives ? $public_archives : array();
	}

	function setType($a_type)
	{
		$this->archive_type = $a_type;
	}
	function getType()
	{
		return $this->archive_type ? $this->archive_type : $this->ARCHIVE_XML;
	}

	function setDate($a_date)
	{
		$this->archive_date = $a_date;
	}
	function getDate()
	{
		return $this->archive_date ? $this->archive_date : time();
	}

	function setSize($a_size)
	{
		$this->archive_size = $a_size;
	}
	function getSize()
	{
		return $this->archive_size;
	}
	function setName($a_name)
	{
		$this->archive_name = $a_name;
	}
	function getName()
	{
		return $this->archive_name;
	}

	function getArchiveFile($a_id)
	{
		$archive = $this->getArchive($a_id);
		$this->initCourseFilesObject();

		return $this->course_files_obj->getArchiveFile($archive['archive_name']);
	}

	function addXML()
	{
		$this->setType($this->ARCHIVE_XML);
		$this->setName(time().'__'.$this->ilias->getSetting('inst_id').'__crs_'.$this->course_obj->getId());
		$this->setDate(time());

		// Step one create folder
		$this->initCourseFilesObject();
		$this->course_files_obj->addDirectory($this->getName());

		// Step two create course xml
		$this->initCourseXMLWriter();

		$this->course_xml_writer->start();
		$this->course_files_obj->writeToFile($this->course_xml_writer->getXML(),$this->getName().'/'.$this->getName().'.xml');

	
		// Step three create child object xml
		// add objects directory
		$this->course_files_obj->addDirectory($this->getName().'/objects');
		
		$this->__addZipFiles($this->course_obj->getRefId());


		// Step four zip
		$this->setSize($this->course_files_obj->zipFile($this->getName(),$this->getName().'.zip'));
		
		
		// Finally add entry in crs_archives table
		$this->add();

		return true;
	}

	function addHTML()
	{
		$this->setType($this->ARCHIVE_HTML);
		$this->setDate(time());
		$this->setName($this->getDate().'__'.$this->ilias->getSetting('inst_id').'__crs_'.$this->course_obj->getId());
		
		// Step one create folder
		$this->initCourseFilesObject();
		$this->course_files_obj->addDirectory($this->getName());

		// Step two, create child html
		$this->course_files_obj->addDirectory($this->getName().'/objects');
		$this->__addHTMLFiles($this->course_obj->getRefId());
		
		// Step three create course html
		$this->__addCourseHTML();

		// Step four zip
		$this->setSize($this->course_files_obj->zipFile($this->getName(),$this->getName().'.zip'));

		// Finally add entry in crs_archives table
		$this->add();
		
		return true;
	}


	function add()
	{
		$query = "INSERT INTO crs_archives ".
			"VALUES ('','".$this->course_obj->getId()."','".$this->getName()."','".$this->getType()."','".
			$this->getDate()."','".$this->getSize()."')";

		$this->ilDB->query($query);
		$this->__read();

		return true;
	}

	function delete($a_id)
	{
		// Delete in file system
		$this->initCourseFilesObject();

		$this->course_files_obj->deleteArchive($this->archives[$a_id]["archive_name"]);

		$query = "DELETE FROM crs_archives ".
			"WHERE course_id = '".$this->course_obj->getId()."' ".
			"AND archive_id = '".$a_id."'";
		
		$this->ilDB->query($query);
		$this->__read();
		
		return true;
	}

	function deleteAll()
	{
		foreach($this->getArchives() as $id => $archive)
		{
			$this->delete($id);
		}
	}
	
	function initCourseFilesObject()
	{
		if(!is_object($this->course_files_obj))
		{
			include_once "./course/classes/class.ilFileDataCourse.php";

			$this->course_files_obj =& new ilFileDataCourse($this->course_obj);
		}
		return true;
	}

	function initCourseXMLWriter()
	{
		if(!is_object($this->course_xml_writer))
		{
			include_once "./course/classes/class.ilCourseXMLWriter.php";

			$this->course_xml_writer =& new ilCourseXMLWriter($this->course_obj);
		}
		return true;
	}

	// PRIVATE
	function __addZipFiles($a_parent_id)
	{
		$this->course_obj->initCourseItemObject();
		$this->course_obj->items_obj->setParentId($a_parent_id);

		foreach($this->course_obj->items_obj->getAllItems() as $item)
		{
			if(!$tmp_obj =& ilObjectFactory::getInstanceByRefId($item['child'],false))
			{
				continue;
			}
			
			if($abs_file_name = $tmp_obj->getXMLZip())
			{
				$new_name = 'il_'.$this->ilias->getSetting('inst_id').'_'.$tmp_obj->getType().'_'.$item['obj_id'].'.zip';
				$this->course_files_obj->copy($abs_file_name,$this->getName().'/objects/'.$new_name);
			}
			$this->__addZipFiles($item['child']);
			unset($tmp_obj);
		}
		return true;
	}

	function __addHTMLFiles($a_parent_id)
	{
		$this->course_obj->initCourseItemObject();
		$this->course_obj->items_obj->setParentId($a_parent_id);
		
		foreach($this->course_obj->items_obj->getAllItems() as $item)
		{
			if(!$tmp_obj =& ilObjectFactory::getInstanceByRefId($item['child'],false))
			{
				continue;
			}
			if($abs_dir_name = $tmp_obj->getHTMLDirectory())
			{
				$new_name = 'il_'.$this->ilias->getSetting('inst_id').'_'.$tmp_obj->getType().'_'.$item['obj_id'];

				$this->course_files_obj->addDirectory($this->getName().'/objects/'.$new_name);
				$this->course_files_obj->rCopy($abs_dir_name,$this->getName().'/objects/'.$new_name);

				// Store filename in hashtable (used for create course html tree)
				$this->html_files["$item[obj_id]"] = "objects/".$new_name."/index.html";
			}
			$this->__addHTMLFiles($item['child']);
			unset($tmp_obj);
		}
		return true;
	}

	function __addCourseHTML()
	{
		global $tpl;

		$tmp_tpl =& new ilTemplate("tpl.crs_export.html",true,true,true);

		$this->course_files_obj->copy($tpl->tplPath.'/default.css',$this->getName().'/default.css');

		$tmp_tpl->setVariable('TITLE','Course export');
		$tmp_tpl->setVariable("CRS_STRUCTURE",$this->lng->txt('crs_structure'));


		$tmp_tpl->setVariable("DETAILS_TITLE",$this->lng->txt("crs_details"));
		#$tmp_tpl->setVariable("TYPE_IMG",ilUtil::getImagePath('icon_crs_b.gif'));
		#$tmp_tpl->setVariable("ALT_IMG",$this->lng->txt("crs_details"));
		
		// SET TXT VARIABLES
		$tmp_tpl->setVariable("TXT_SYLLABUS",$this->lng->txt("syllabus"));
		$tmp_tpl->setVariable("TXT_CONTACT",$this->lng->txt("contact"));
		$tmp_tpl->setVariable("TXT_CONTACT_NAME",$this->lng->txt("contact_name"));
		$tmp_tpl->setVariable("TXT_CONTACT_RESPONSIBILITY",$this->lng->txt("contact_responsibility"));
		$tmp_tpl->setVariable("TXT_CONTACT_EMAIL",$this->lng->txt("contact_email"));
		$tmp_tpl->setVariable("TXT_CONTACT_PHONE",$this->lng->txt("contact_phone"));
		$tmp_tpl->setVariable("TXT_CONTACT_CONSULTATION",$this->lng->txt("contact_consultation"));
		$tmp_tpl->setVariable("TXT_DATES",$this->lng->txt("dates"));
		$tmp_tpl->setVariable("TXT_ACTIVATION",$this->lng->txt("activation"));
		$tmp_tpl->setVariable("TXT_SUBSCRIPTION",$this->lng->txt("subscription"));
		$tmp_tpl->setVariable("TXT_ARCHIVE",$this->lng->txt("archive"));

		// FILL 
		$tmp_tpl->setVariable("SYLLABUS",nl2br($this->course_obj->getSyllabus() ? 
												 $this->course_obj->getSyllabus() : 
												 $this->lng->txt("not_available")));

		$tmp_tpl->setVariable("CONTACT_NAME",$this->course_obj->getContactName() ? 
								$this->course_obj->getContactName() : 
								$this->lng->txt("not_available"));
		$tmp_tpl->setVariable("CONTACT_RESPONSIBILITY",$this->course_obj->getContactResponsibility() ? 
								$this->course_obj->getContactResponsibility() : 
								$this->lng->txt("not_available"));
		$tmp_tpl->setVariable("CONTACT_PHONE",$this->course_obj->getContactPhone() ? 
								$this->course_obj->getContactPhone() : 
								$this->lng->txt("not_available"));
		$tmp_tpl->setVariable("CONTACT_CONSULTATION",nl2br($this->course_obj->getContactConsultation() ? 
								$this->course_obj->getContactConsultation() : 
								$this->lng->txt("not_available")));
		if($this->course_obj->getContactEmail())
		{
			$tmp_tpl->setCurrentBlock("email_link");
			#$tmp_tpl->setVariable("EMAIL_LINK","mail_new.php?type=new&mail_data[rcp_to]=".$this->course_obj->getContactEmail());
			$tmp_tpl->setVariable("CONTACT_EMAIL",$this->course_obj->getContactEmail());
			$tmp_tpl->parseCurrentBlock();
		}
		else
		{
			$tmp_tpl->setCurrentBlock("no_mail");
			$tmp_tpl->setVariable("NO_CONTACT_EMAIL",$this->course_obj->getContactEmail());
			$tmp_tpl->parseCurrentBlock();
		}
		if($this->course_obj->getActivationUnlimitedStatus())
		{
			$tmp_tpl->setVariable("ACTIVATION",$this->lng->txt('unlimited'));
		}
		else
		{
			$str = $this->lng->txt("crs_from")." ".strftime("%Y-%m-%d %R",$this->course_obj->getActivationStart())." ".
				$this->lng->txt("crs_to")." ".strftime("%Y-%m-%d %R",$this->course_obj->getActivationEnd());
			$tmp_tpl->setVariable("ACTIVATION",$str);
		}
		if($this->course_obj->getSubscriptionUnlimitedStatus())
		{
			$tmp_tpl->setVariable("SUBSCRIPTION",$this->lng->txt('unlimited'));
		}
		else
		{
			$str = $this->lng->txt("crs_from")." ".strftime("%Y-%m-%d %R",$this->course_obj->getSubscriptionStart())." ".
				$this->lng->txt("crs_to")." ".strftime("%Y-%m-%d %R",$this->course_obj->getSubscriptionEnd());
			$tmp_tpl->setVariable("SUBSCRIPTION",$str);
		}
		if($this->course_obj->getArchiveType() == $this->course_obj->ARCHIVE_DISABLED)
		{
			$tmp_tpl->setVariable("ARCHIVE",$this->lng->txt('archive_disabled'));
		}
		else
		{
			$str = $this->lng->txt("crs_from")." ".strftime("%Y-%m-%d %R",$this->course_obj->getArchiveStart())." ".
				$this->lng->txt("crs_to")." ".strftime("%Y-%m-%d %R",$this->course_obj->getArchiveEnd());
			$tmp_tpl->setVariable("ARCHIVE",$str);
		}

		$this->structure = '';
		$this->__buildStructure($tmp_tpl,$this->course_obj->getRefId());
		$tmp_tpl->setVariable("STRUCTURE",$this->structure);

		$this->course_files_obj->writeToFile($tmp_tpl->get(),$this->getName().'/index.html');

		return true;
	}

	function __buildStructure(&$tmp_tpl,$a_parent_id)
	{
		$this->course_obj->initCourseItemObject();
		$this->course_obj->items_obj->setParentId($a_parent_id);
		
		$items = $this->course_obj->items_obj->getAllItems();

		foreach($items as $key => $item)
		{
			if(!$tmp_obj =& ilObjectFactory::getInstanceByRefId($item['child'],false))
			{
				continue;
			}


			if($key == 0)
			{
				$this->structure .= "<ul>";
			}

			$this->structure .= "<li>";

			if(isset($this->html_files["$item[obj_id]"]))
			{
				$link = "<a href=\"./".$this->html_files["$item[obj_id]"]."\">".$item["title"]."</a>";
			}
			else
			{
				$link = $item['title'];
			}
			$this->structure .= $link;
			$this->structure .= "</li>";

			$this->__buildStructure($tmp_tpl,$item['child']);

			if($key == (count($items) - 1))
			{
				$this->structure .= "</ul>";
			}
		

			unset($tmp_obj);
		}
		return true;
	}


	function __read()
	{
		$this->archives = array();

		$query = "SELECT * FROM crs_archives ".
			"WHERE course_id = '".$this->course_obj->getId()."' ".
			"ORDER BY archive_date";

		$res = $this->ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$this->archives[$row->archive_id]["archive_type"]	= $row->archive_type;
			$this->archives[$row->archive_id]["archive_date"]	= $row->archive_date;
			$this->archives[$row->archive_id]["archive_size"]	= $row->archive_size;
			$this->archives[$row->archive_id]["archive_name"]	= $row->archive_name;
		}
		return true;
	}
}
?>