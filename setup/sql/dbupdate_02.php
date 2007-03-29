<#865>
<?php
$ilCtrlStructureReader->getStructure();
?>
<#866>
<?php
$ilCtrlStructureReader->getStructure();
?>
<#867>
<?php
$ilCtrlStructureReader->getStructure();
?>
<#868>
<?php
$ilCtrlStructureReader->getStructure();
?>
<#869>
<?php
// add show_questiontext for question blocks
if (!$ilDB->tableColumnExists("survey_questionblock", "show_questiontext"))
{
	$query = "ALTER TABLE `survey_questionblock` ADD `show_questiontext` ENUM( '0', '1' ) DEFAULT '1' AFTER `title`";
	$res = $ilDB->query($query);
}
?>
<#870>
ALTER TABLE `survey_survey_question` CHANGE `heading` `heading` TEXT NULL DEFAULT NULL;

<#871>
CREATE TABLE IF NOT EXISTS `ldap_attribute_mapping` (
  `server_id` int(11) NOT NULL default '0',
  `keyword` varchar(32)   NOT NULL default '',
  `value` varchar(255)   NOT NULL default '',
  `perform_update` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`server_id`,`keyword`),
  KEY `server_id` (`server_id`)
) TYPE=MyISAM;


CREATE TABLE IF NOT EXISTS `ldap_role_group_mapping` (
  `mapping_id` int(11) NOT NULL auto_increment,
  `server_id` int(3) NOT NULL default '0',
  `dn` varchar(255)   NOT NULL default '',
  `member_attribute` varchar(64)   NOT NULL default '',
  `member_isdn` tinyint(1) NOT NULL default '0',
  `role` int(11) NOT NULL default '0',
  PRIMARY KEY  (`mapping_id`)
) TYPE=MyISAM;


CREATE TABLE IF NOT EXISTS `ldap_server_settings` (
  `server_id` int(2) NOT NULL auto_increment,
  `active` int(1) NOT NULL default '0',
  `name` varchar(32)   NOT NULL default '',
  `url` varchar(255)   NOT NULL default '',
  `version` int(1) NOT NULL default '0',
  `base_dn` varchar(255)   NOT NULL default '',
  `referrals` int(1) NOT NULL default '0',
  `tls` int(1) NOT NULL default '0',
  `bind_type` int(1) NOT NULL default '0',
  `bind_user` varchar(255)   NOT NULL default '',
  `bind_pass` varchar(32)   NOT NULL default '',
  `search_base` varchar(255)   NOT NULL default '',
  `user_scope` tinyint(1) NOT NULL default '0',
  `user_attribute` varchar(255)   NOT NULL default '',
  `filter` varchar(255)   NOT NULL default '',
  `group_dn` varchar(255)   NOT NULL default '',
  `group_scope` tinyint(1) NOT NULL default '0',
  `group_filter` varchar(255)   NOT NULL default '',
  `group_member` varchar(255)   NOT NULL default '',
  `group_memberisdn` tinyint(1) NOT NULL default '0',
  `group_name` varchar(255)   NOT NULL default '',
  `group_attribute` varchar(64)   NOT NULL default '',
  `sync_on_login` tinyint(1) NOT NULL default '0',
  `sync_per_cron` tinyint(1) NOT NULL default '0',
  `role_sync_active` tinyint(1) NOT NULL default '0',
  `role_bind_dn` varchar(255)   NOT NULL default '',
  `role_bind_pass` varchar(32)   NOT NULL default '',
  PRIMARY KEY  (`server_id`)
) TYPE=MyISAM;

<#872>
<?php
$ilCtrlStructureReader->getStructure();
?>
<#873>
<?php
$ilCtrlStructureReader->getStructure();
?>
<#874>
<?php
$ilCtrlStructureReader->getStructure();
?>
<#875>
<?php
$ilCtrlStructureReader->getStructure();
?>
<#876>
<?php
$ilCtrlStructureReader->getStructure();
?>
<#877>
<?php
// register new object type 'ps' for privacy security settings
$query = "INSERT INTO object_data (type, title, description, owner, create_date, last_update) ".
		"VALUES ('typ', 'ps', 'Privacy security settings', -1, now(), now())";
$this->db->query($query);

// ADD NODE IN SYSTEM SETTINGS FOLDER
// create object data entry
$query = "INSERT INTO object_data (type, title, description, owner, create_date, last_update) ".
		"VALUES ('ps', '__PrivacySecurity', 'Privacy and Security', -1, now(), now())";
$this->db->query($query);

$query = "SELECT LAST_INSERT_ID() as id";
$res = $this->db->query($query);
$row = $res->fetchRow(DB_FETCHMODE_OBJECT);

// create object reference entry
$query = "INSERT INTO object_reference (obj_id) VALUES('".$row->id."')";
$res = $this->db->query($query);

$query = "SELECT LAST_INSERT_ID() as id";
$res = $this->db->query($query);
$row = $res->fetchRow(DB_FETCHMODE_OBJECT);

// put in tree
$tree = new ilTree(ROOT_FOLDER_ID);
$tree->insertNode($row->id,SYSTEM_FOLDER_ID);

$query = "SELECT obj_id FROM object_data WHERE type = 'typ' ".
	" AND title = 'ps'";
$res = $this->db->query($query);
$row = $res->fetchRow();
$typ_id = $row[0];

// new permission
$query = "INSERT INTO rbac_operations SET operation = 'export_member_data', ".
	"description = 'Export member data', ".
	"class = 'object'";

$res = $ilDB->query($query);
$new_ops_id = $ilDB->getLastInsertId();


// add rbac operations to assessment folder
// 1: edit_permissions, 2: visible, 3: read, 4:write
$query = "INSERT INTO rbac_ta (typ_id, ops_id) VALUES ('".$typ_id."','1')";
$this->db->query($query);
$query = "INSERT INTO rbac_ta (typ_id, ops_id) VALUES ('".$typ_id."','2')";
$this->db->query($query);
$query = "INSERT INTO rbac_ta (typ_id, ops_id) VALUES ('".$typ_id."','3')";
$this->db->query($query);
$query = "INSERT INTO rbac_ta (typ_id, ops_id) VALUES ('".$typ_id."','4')";
$this->db->query($query);

$query = "INSERT INTO rbac_ta (typ_id,ops_id) VALUES ('".$typ_id."','".$new_ops_id."')";
$this->db->query($query);
?>
<#878>
<?php
$ilCtrlStructureReader->getStructure();
?>
<#879>
<?php
// add layout attribute for matrix questions
$attribute_visibility = FALSE;
$query = "SHOW COLUMNS FROM survey_question_matrix";
$res = $ilDB->query($query);
if ($res->numRows())
{
	while ($data = $res->fetchRow(DB_FETCHMODE_ASSOC))
	{
		if (strcmp($data["Field"], "layout") == 0)
		{
			$attribute_visibility = TRUE;
		}
	}
}
if ($attribute_visibility == FALSE)
{
	$query = "ALTER TABLE `survey_question_matrix` ADD `layout` TEXT DEFAULT NULL AFTER `bipolar_adjective2`";
	$res = $ilDB->query($query);
}
?>
<#880>
ALTER TABLE `usr_data` ADD `im_icq` VARCHAR( 40 ) NULL ,
ADD `im_yahoo` VARCHAR( 40 ) NULL ,
ADD `im_msn` VARCHAR( 40 ) NULL ,
ADD `im_aim` VARCHAR( 40 ) NULL ,
ADD `im_skype` VARCHAR( 40 ) NULL ;

INSERT INTO `settings` ( `module` , `keyword` , `value` )
VALUES ('common', 'show_user_activity', '1');
INSERT INTO `settings` ( `module` , `keyword` , `value` )
VALUES ('common', 'user_activity_time', '5');
<#881>
DROP TABLE IF EXISTS il_news_item;
CREATE table `il_news_item`
(
	`id` int not null auto_increment primary key,
	`priority` enum('0','1','2') default 1,
	`title` varchar(200),
	`content` text,
	`context_obj_id` int,
	`context_obj_type` char(10),
	`context_sub_obj_id` int,
	`context_sub_obj_type` char(10),
	`content_type` enum('text','html') default 'text',
	`creation_date` datetime,
	`update_date` datetime,
	`user_id` int,
	`visibility` enum('users','public') default 'users',
	`content_long` text
);
<#882>
CREATE TABLE `il_block_setting` (
  `type` varchar(20) NOT NULL default '',
  `user` int  NOT NULL default '0',
  `block_id` int  NOT NULL default '0',
  `setting` varchar(40) NOT NULL default '',
  `value` varchar(200)   NOT NULL default '',
  PRIMARY KEY  (`type`,`user`,`block_id`,`setting`)
) TYPE=MyISAM;
<#883>
<?php
$ilCtrlStructureReader->getStructure();
?>
<#884>
CREATE TABLE `il_news_subscription` (
  `user_id` int  NOT NULL default '0',
  `ref_id` int  NOT NULL default '0',
  PRIMARY KEY  (`user_id`,`ref_id`)
) TYPE=MyISAM;
<#885>
ALTER TABLE usr_data ADD COLUMN feed_hash char(32);
<#886>
<?php
$ilCtrlStructureReader->getStructure();
?>
<#887>
<?php
$ilCtrlStructureReader->getStructure();
?>
<#888>
DROP TABLE IF EXISTS il_custom_block;
CREATE table `il_custom_block`
(
	`id` int not null auto_increment primary key,
	`context_obj_id` int,
	`context_obj_type` char(10),
	`context_sub_obj_id` int,
	`context_sub_obj_type` char(10),
	`type` varchar(20),
	`title` varchar(200)
);
<#889>
DROP TABLE IF EXISTS il_html_block;
CREATE table `il_html_block`
(
	`id` int not null primary key,
	`content` text
);
<#890>
DROP TABLE IF EXISTS il_external_feed_block;
CREATE table `il_external_feed_block`
(
	`id` int not null auto_increment primary key,
	`feed_url` varchar(250)
);
<#891>
<?php
$ilCtrlStructureReader->getStructure();
?>
<#892>
DROP TABLE IF EXISTS `member_export_user_settings`;
CREATE TABLE `member_export_user_settings` (
  `user_id` int(11) NOT NULL default '0',
  `settings` text NOT NULL,
  PRIMARY KEY  (`user_id`)
) Type=MyISAM;

<#893>
<?php
$ilCtrlStructureReader->getStructure();
?>

<#894>
DROP TABLE IF EXISTS `member_agreement`;
CREATE TABLE `member_agreement` (
  `usr_id` int(11) NOT NULL default '0',
  `obj_id` int(11) NOT NULL default '0',
  `accepted` tinyint(1) NOT NULL default '0',
  `acceptance_time` int(11) NOT NULL default '0',
  PRIMARY KEY  (`usr_id`,`obj_id`)
) Type=MyISAM;

<#895>
ALTER TABLE `usr_data` ADD `delicious` VARCHAR(40);

<#896>
CREATE TABLE `crs_defined_field_definitions` (
`field_id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
`obj_id` INT( 11 ) NOT NULL ,
`field_name` VARCHAR( 255 ) NOT NULL ,
`field_type` TINYINT( 1 ) NOT NULL ,
`field_values` TEXT NOT NULL ,
`field_required` TINYINT( 1 ) NOT NULL ,
PRIMARY KEY ( `field_id` )
) TYPE = MYISAM ;

<#897>
CREATE TABLE `crs_user_data` (
`usr_id` INT( 11 ) NOT NULL ,
`field_id` INT( 11 ) NOT NULL ,
`value` TEXT NOT NULL ,
PRIMARY KEY ( `usr_id` , `field_id` )
) TYPE = MYISAM ;

<#898>
<?php
$ilCtrlStructureReader->getStructure();
?>
<#899>
<?php
$ilCtrlStructureReader->getStructure();
?>
<#900> 
CREATE TABLE `reg_access_limitation` (
  `role_id` int(11) unsigned NOT NULL,
  `limit_absolute` int(11) unsigned default NULL,
  `limit_relative_d` int(11) unsigned default NULL,
  `limit_relative_m` int(11) unsigned default NULL,
  `limit_relative_y` int(11) unsigned default NULL,
  `limit_mode` enum('absolute','relative','unlimited') NOT NULL,
  PRIMARY KEY  (`role_id`)
) ENGINE=MyISAM; 
<#901>
<?php
$ilCtrlStructureReader->getStructure();
?>
<#902>
<?php
$ilCtrlStructureReader->getStructure();
?>
<#903>
<?php
$ilCtrlStructureReader->getStructure();
?>
<#904>
<?php
$ilCtrlStructureReader->getStructure();
?>
<#905>
ALTER TABLE `chat_invitations` ADD `guest_informed` tinyint(1) NOT NULL DEFAULT '0';

<#906>
ALTER TABLE `file_data` ADD `file_size` INT( 11 ) DEFAULT '0' NOT NULL AFTER `file_type` ;

<#907>
DROP TABLE IF EXISTS tmp_migration;
CREATE TABLE `tmp_migration` (
  `obj_id` int(11) NOT NULL default '0',
  `passed` tinyint(4) NOT NULL default '0');

<#908>
<?php
$wd = getcwd();
chdir('..');

global $ilLog;

include_once('Services/Migration/DBUpdate_904/classes/class.ilUpdateUtils.php');
include_once('Services/Migration/DBUpdate_904/classes/class.ilFSStorageFile.php');
include_once('Services/Migration/DBUpdate_904/classes/class.ilObjFileAccess.php');

// Fetch obj_ids of files
$query = "SELECT obj_id FROM object_data WHERE type = 'file' ORDER BY obj_id";
$res = $ilDB->query($query);
$file_ids = array();
while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
{
	$file_ids[] = $row->obj_id;
}

foreach($file_ids as $file_id)
{
	// Check if done
	$query = "SELECT * FROM tmp_migration WHERE obj_id = ".$file_id." AND passed = 1";
	$res = $ilDB->query($query);
	if($res->numRows())
	{
		continue;
	}
	
	if(!@file_exists(ilUpdateUtils::getDataDir().'/files/file_'.$file_id) or !@is_dir(ilUpdateUtils::getDataDir().'/files/file_'.$file_id))
	{
		$ilLog->write('DB Migration 905: Failed: No data found for file_'.$file_id);
		continue;
	}	

	// Rename
	$fss = new ilFSStorageFile($file_id);
	$fss->create();


	if($fss->rename(ilUpdateUtils::getDataDir().'/files/file_'.$file_id,$fss->getAbsolutePath()))
	{
		$ilLog->write('DB Migration 905: Success renaming file_'.$file_id);
	}
	else
	{
		$ilLog->write('DB Migration 905: Failed renaming '.ilUpdateUtils::getDataDir().'/files/file_'.$file_id.' -> '.$fss->getAbsolutePath());
		continue;
	}
	
	// Save success
	$query = "REPLACE INTO tmp_migration SET obj_id = '".$file_id."',passed = '1'";
	$ilDB->query($query);
	
	// Update file size
	$size = ilObjFileAccess::_lookupFileSize($file_id);
	$query = "UPDATE file_data SET file_size = '".$size."' ".
		"WHERE file_id = ".$file_id;
	$ilDB->query($query);
	$ilLog->write('DB Migration 905: File size is '.$size.' Bytes');
}

chdir($wd);
?>
<#909>
DROP TABLE IF EXISTS tmp_migration;

<#910>
DROP TABLE IF EXISTS ldap_role_group_mapping;
CREATE TABLE `ldap_role_group_mapping` (
  `mapping_id` int(11) NOT NULL auto_increment,
  `server_id` int(3) NOT NULL default '0',
  `url` varchar(255) NOT NULL default '',
  `dn` varchar(255) NOT NULL default '',
  `member_attribute` varchar(64) NOT NULL default '',
  `member_isdn` tinyint(1) NOT NULL default '0',
  `role` int(11) NOT NULL default '0',
  PRIMARY KEY  (`mapping_id`)
) Type=MyISAM;

<#911>
<?php
$ilCtrlStructureReader->getStructure();
?>

<#912>
ALTER TABLE `user_defined_field_definition` ADD `export` TINYINT( 1 ) NOT NULL ,
ADD `course_export` TINYINT( 1 ) NOT NULL ;

<#913>
<?php
$ilCtrlStructureReader->getStructure();
?>
<#914>
ALTER TABLE `il_news_item` MODIFY `priority` int default 1;

<#915>
ALTER TABLE `il_news_item` ADD COLUMN `content_is_lang_var` tinyint default 0;

<#916>
CREATE TABLE `copy_wizard_options` (
`copy_id` INT( 11 ) NOT NULL ,
`source_id` INT( 11 ) NOT NULL ,
`options` TEXT NOT NULL ,
PRIMARY KEY ( `copy_id` , `source_id` )
) TYPE = MYISAM ;
<#917>
ALTER TABLE  `tst_test_result` ADD INDEX (  `active_fi` );
<#918>
ALTER TABLE  `qpl_answer_cloze` ADD  `lowerlimit` DOUBLE NULL DEFAULT  '0' AFTER  `cloze_type` , ADD  `upperlimit` DOUBLE NULL DEFAULT  '0' AFTER  `lowerlimit` ;
<#919>
<?php
// register new object type 'newss' for news settings
$query = "INSERT INTO object_data (type, title, description, owner, create_date, last_update) ".
		"VALUES ('typ', 'nwss', 'News settings', -1, now(), now())";
$this->db->query($query);

// ADD NODE IN SYSTEM SETTINGS FOLDER
// create object data entry
$query = "INSERT INTO object_data (type, title, description, owner, create_date, last_update) ".
		"VALUES ('nwss', '__NewsSettings', 'News Settings', -1, now(), now())";
$this->db->query($query);

$query = "SELECT LAST_INSERT_ID() as id";
$res = $this->db->query($query);
$row = $res->fetchRow(DB_FETCHMODE_OBJECT);

// create object reference entry
$query = "INSERT INTO object_reference (obj_id) VALUES('".$row->id."')";
$res = $this->db->query($query);

$query = "SELECT LAST_INSERT_ID() as id";
$res = $this->db->query($query);
$row = $res->fetchRow(DB_FETCHMODE_OBJECT);

// put in tree
$tree = new ilTree(ROOT_FOLDER_ID);
$tree->insertNode($row->id,SYSTEM_FOLDER_ID);

$query = "SELECT obj_id FROM object_data WHERE type = 'typ' ".
	" AND title = 'nwss'";
$res = $this->db->query($query);
$row = $res->fetchRow();
$typ_id = $row[0];

// add rbac operations to news settings
// 1: edit_permissions, 2: visible, 3: read, 4:write
$query = "INSERT INTO rbac_ta (typ_id, ops_id) VALUES ('".$typ_id."','1')";
$this->db->query($query);
$query = "INSERT INTO rbac_ta (typ_id, ops_id) VALUES ('".$typ_id."','2')";
$this->db->query($query);
$query = "INSERT INTO rbac_ta (typ_id, ops_id) VALUES ('".$typ_id."','3')";
$this->db->query($query);
$query = "INSERT INTO rbac_ta (typ_id, ops_id) VALUES ('".$typ_id."','4')";
$this->db->query($query);

?>
<#920>
<?php
$ilCtrlStructureReader->getStructure();
?>
<#921>
CREATE TABLE  `tst_test_defaults` (
 `test_defaults_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
 `user_fi` INT NOT NULL ,
 `name` VARCHAR( 255 ) NOT NULL ,
 `defaults` TEXT NOT NULL ,
 `marks` TEXT NOT NULL ,
 `lastchange` TIMESTAMP NOT NULL ,
INDEX (  `user_fi` )
);
<#922>
DELETE FROM il_custom_block;
<#923>
<?php
$ilCtrlStructureReader->getStructure();
?>
<#924>
DELETE FROM il_html_block;
DELETE FROM il_external_feed_block;

<#925>
<?php
// register new object type 'feed'
$query = "INSERT INTO object_data (type, title, description, owner, create_date, last_update) ".
		"VALUES ('typ', 'feed', 'External Feed', -1, now(), now())";
$this->db->query($query);

$query = "SELECT obj_id FROM object_data WHERE type = 'typ' ".
	" AND title = 'feed'";
$res = $this->db->query($query);
$row = $res->fetchRow();
$typ_id = $row[0];

// add rbac operations for feed object
// 1: edit_permissions, 2: visible, 3: read, 4:write
$query = "INSERT INTO rbac_ta (typ_id, ops_id) VALUES ('".$typ_id."','1')";
$this->db->query($query);
$query = "INSERT INTO rbac_ta (typ_id, ops_id) VALUES ('".$typ_id."','2')";
$this->db->query($query);
$query = "INSERT INTO rbac_ta (typ_id, ops_id) VALUES ('".$typ_id."','3')";
$this->db->query($query);
$query = "INSERT INTO rbac_ta (typ_id, ops_id) VALUES ('".$typ_id."','4')";
$this->db->query($query);

?>

<#926>
DROP TABLE IF EXISTS tmp_migration;
CREATE TABLE `tmp_migration` (
  `obj_id` int(11) NOT NULL default '0',
  `passed` tinyint(4) NOT NULL default '0');

<#927>
<?php
$wd = getcwd();
chdir('..');

global $ilLog;

include_once('Services/Migration/DBUpdate_904/classes/class.ilUpdateUtils.php');
include_once('Services/Migration/DBUpdate_904/classes/class.ilFSStorageEvent.php');

// Fetch event_ids of files
$query = "SELECT DISTINCT(event_id) as event_ids FROM event_file";
$res = $ilDB->query($query);
$event_ids = array();
while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
{
	$event_ids[] = $row->event_ids;
}

foreach($event_ids as $event_id)
{
	// Check if done
	$query = "SELECT * FROM tmp_migration WHERE obj_id = ".$event_id." AND passed = 1";
	$res = $ilDB->query($query);
	if($res->numRows())
	{
		continue;
	}
	
	if(!@file_exists(ilUpdateUtils::getDataDir().'/events/event_'.$event_id))
	{
		$ilLog->write('DB Migration 905: Failed: No data found for event id '.$event_id);
		continue;
	}	

	// Rename
	$fss = new ilFSStorageEvent($event_id);
	$fss->create();


	if($fss->rename(ilUpdateUtils::getDataDir().'/events/event_'.$event_id,$fss->getAbsolutePath()))
	{
		$ilLog->write('DB Migration 905: Success renaming event_'.$event_id);
	}
	else
	{
		$ilLog->write('DB Migration 905: Failed renaming '.ilUpdateUtils::getDataDir().'/events/event_'.$event_id.' -> '.$fss->getAbsolutePath());
		continue;
	}
	
	// Save success
	$query = "REPLACE INTO tmp_migration SET obj_id = '".$event_id."',passed = '1'";
	$ilDB->query($query);
}

chdir($wd);
?>
<#928>
DROP TABLE IF EXISTS tmp_migration;

<#929>
DROP TABLE IF EXISTS tmp_migration;
CREATE TABLE `tmp_migration` (
  `obj_id` int(11) NOT NULL default '0',
  `passed` tinyint(4) NOT NULL default '0');

<#930>
<?php
$wd = getcwd();
chdir('..');

global $ilLog;

include_once('Services/Migration/DBUpdate_904/classes/class.ilUpdateUtils.php');
include_once('Services/Migration/DBUpdate_904/classes/class.ilFSStorageCourse.php');

// Fetch archive ids
$query = "SELECT DISTINCT(archive_id) as archive_ids,archive_name,course_id FROM crs_archives";
$res = $ilDB->query($query);
$archive_ids = array();
while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
{
	$archive_ids[$row->archive_ids]['id'] = $row->archive_ids;
	$archive_ids[$row->archive_ids]['name'] = $row->archive_name;
	$archive_ids[$row->archive_ids]['course_id'] = $row->course_id;
	
}

foreach($archive_ids as $archive_id => $data)
{
	// Check if done
	$query = "SELECT * FROM tmp_migration WHERE obj_id = ".$archive_id." AND passed = 1";
	$res = $ilDB->query($query);
	if($res->numRows())
	{
		continue;
	}
	
	if(!@file_exists(ilUpdateUtils::getDataDir().'/course/'.$data['name']))
	{
		$ilLog->write('DB Migration 930: Failed: No data found for archive id '.$data['name']);
		continue;
	}	

	// Rename
	$fss = new ilFSStorageCourse($data['course_id']);
	$fss->create();
	$fss->initArchiveDirectory();


	if($fss->rename(ilUpdateUtils::getDataDir().'/course/'.$data['name'],$fss->getArchiveDirectory().'/'.$data['name']))
	{
		$ilLog->write('DB Migration 905: Success renaming archive '.$data['name']);
	}
	if($fss->rename(ilUpdateUtils::getDataDir().'/course/'.$data['name'].'.zip',$fss->getArchiveDirectory().'/'.$data['name'].'.zip'))
	{
		$ilLog->write('DB Migration 905: Success renaming archive '.$data['name'].'.zip');
	}
	else
	{
		$ilLog->write('DB Migration 905: Failed renaming '.ilUpdateUtils::getDataDir().'/course/'.$data['name'] .'-> '.
			 $fss->getArchiveDirectory().'/'.$data['name']);
		continue;
	}
	
	// Save success
	$query = "REPLACE INTO tmp_migration SET obj_id = '".$archive_id."',passed = '1'";
	$ilDB->query($query);
}

chdir($wd);
?>
<#931>
DROP TABLE IF EXISTS tmp_migration;

<#932>
DROP TABLE IF EXISTS il_media_cast_item;
CREATE TABLE `il_media_cast_item` (
  `id` int(11) NOT NULL auto_increment,
  `mcst_id` int NOT NULL default '0',
  `mob_id` int NOT NULL default '0',
  `creation_date` datetime NOT NULL,
  `update_date` datetime NOT NULL,
  `update_user` int NOT NULL default '0',
  `length` varchar(8) NOT NULL,
  PRIMARY KEY  (`id`)
) Type=MyISAM;

<#933>
ALTER table usr_data ADD column latitude varchar(30) NOT NULL DEFAULT '';
ALTER table usr_data ADD column longitude varchar(30) NOT NULL DEFAULT '';
ALTER table usr_data ADD column loc_zoom int NOT NULL DEFAULT 0;

<#934>
ALTER TABLE `il_media_cast_item` ADD COLUMN `title` varchar(200);
ALTER TABLE `il_media_cast_item` ADD COLUMN `description` text;

<#935>
ALTER TABLE `il_media_cast_item` ADD COLUMN `visibility` enum('users','public') default 'users';

<#936>
<?php
$ilCtrlStructureReader->getStructure();
?>
<#937>
<?php
// register new object type 'mcst' for media casts
$query = "INSERT INTO object_data (type, title, description, owner, create_date, last_update) ".
		"VALUES ('typ', 'mcst', 'Media Cast', -1, now(), now())";
$this->db->query($query);

$query = "SELECT obj_id FROM object_data WHERE type = 'typ' ".
	" AND title = 'mcst'";
$res = $this->db->query($query);
$row = $res->fetchRow();
$typ_id = $row[0];

// add rbac operations for feed object
// 1: edit_permissions, 2: visible, 3: read, 4:write
$query = "INSERT INTO rbac_ta (typ_id, ops_id) VALUES ('".$typ_id."','1')";
$this->db->query($query);
$query = "INSERT INTO rbac_ta (typ_id, ops_id) VALUES ('".$typ_id."','2')";
$this->db->query($query);
$query = "INSERT INTO rbac_ta (typ_id, ops_id) VALUES ('".$typ_id."','3')";
$this->db->query($query);
$query = "INSERT INTO rbac_ta (typ_id, ops_id) VALUES ('".$typ_id."','4')";
$this->db->query($query);

?>

<#938>
DROP TABLE IF EXISTS il_media_cast_data;
CREATE TABLE `il_media_cast_data` (
  `id` int(11) NOT NULL auto_increment,
  `offline` TINYINT DEFAULT 0,
  `public_files` TINYINT DEFAULT 0,
  PRIMARY KEY  (`id`)
) Type=MyISAM;

<#939>
DROP TABLE IF EXISTS il_media_cast_item;
ALTER TABLE `il_news_item` ADD COLUMN `mob_id` int;
ALTER TABLE `il_news_item` ADD COLUMN `playtime` varchar(8);

<#940>
ALTER TABLE `il_news_item` MODIFY `content_type` enum('text','html','audio') default 'text';

<#941>
ALTER table grp_data ADD column latitude varchar(30) NOT NULL DEFAULT '';
ALTER table grp_data ADD column longitude varchar(30) NOT NULL DEFAULT '';
ALTER table grp_data ADD column location_zoom int NOT NULL DEFAULT 0;
ALTER table grp_data ADD column enable_group_map TINYINT NOT NULL DEFAULT 0;

<#942>
ALTER table crs_settings ADD column latitude varchar(30) NOT NULL DEFAULT '';
ALTER table crs_settings ADD column longitude varchar(30) NOT NULL DEFAULT '';
ALTER table crs_settings ADD column location_zoom int NOT NULL DEFAULT 0;
ALTER table crs_settings ADD column enable_course_map TINYINT NOT NULL DEFAULT 0;

<#943>
<?php
$query = "SELECT * FROM ldap_server_settings ";
$res = $ilDB->query($query);
if(!$res->numRows())
{
	// Only update if no setting is available
	# Fetch old settings from settings_table
	$query = "SELECT * FROM settings WHERE keyword LIKE('ldap_%')";
	$res = $ilDB->query($query);
	while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
	{
		$ldap_old[$row->keyword] = $row->value;
	}
	
	if($ldap_old['ldap_server'])
	{
		$ldap_new['name'] = 'Default Server';
		$ldap_new['url'] = ('ldap://'.$ldap_old['ldap_server']);
		if($ldap_old['ldap_port'])
		{
			$ldap_new['url'] .= (':'.$ldap_old['ldap_port']);
		}
		$ldap_new['active'] = $ldap_old['ldap_active'] ? 1 : 0;
		$ldap_new['tls'] = (int) $ldap_old['ldap_tls'];
		$ldap_new['version'] = $ldap_old['ldap_version'] ? $ldap_old['version'] : 3;
		$ldap_new['basedn'] = $ldap_old['ldap_basedn'];
		$ldap_new['referrals'] = (int) $ldap_old['ldap_referrals'];
		$ldap_new['bind_type'] = $ldap_old['ldap_bind_pw'] ? 1 : 0;
		$ldap_new['bind_user'] = $ldap_old['ldap_bind_dn'];
		$ldap_new['bind_pass'] = $ldap_old['ldap_bind_pw'];
		$ldap_new['search_base'] = $ldap_old['ldap_search_base'];
		$ldap_new['user_scope'] = 0;
		$ldap_new['user_attribute'] = $ldap_old['ldap_login_key'];
		$ldap_new['filter'] = ('(objectclass='.$ldap_old['ldap_objectclass'].')');
		
		$query = "INSERT INTO  ldap_server_settings SET ".
			"active = '".$ldap_new['active']."', ".
			"name = '".$ldap_new['name']."', ".
			"url = ".$this->db->quote($ldap_new['url']).", ".
			"version = ".$this->db->quote($ldap_new['version']).", ".
			"base_dn = '".$ldap_new['basedn']."', ".
			"referrals = '".$ldap_new['referrals']."', ".
			"tls = '".$ldap_new['tls']."', ".
			"bind_type = '".$ldap_new['bind_type']."', ".
			"bind_user = '".$ldap_new['bind_user']."', ".
			"bind_pass = '".$ldap_new['bind_pass']."', ".
			"search_base = '".$ldap_new['search_base']."', ".
			"user_scope = '".$ldap_new['user_scope']."', ".
			"user_attribute = '".$ldap_new['user_attribute']."', ".
			"filter = '".$ldap_new['filter']."' ";
			"group_dn = '', ".
			"group_scope = '', ".
			"group_filter = '', ".
			"group_member = '', ".
			"group_memberisdn = '', ".
			"group_name = '', ".
			"group_attribute = '', ".
			"sync_on_login = '0', ".
			"sync_per_cron = '0', ".
			"role_sync_active = '0', ".
			"role_bind_dn = '', ".
			"role_bind_pass = '', ";
			
		$res = $ilDB->query($query);
	}
}
?>
<#944>
<?php
$wd = getcwd();
chdir('..');

global $ilLog;

include_once('Services/Migration/DBUpdate_904/classes/class.ilUpdateUtils.php');
include_once('Services/Migration/DBUpdate_904/classes/class.ilFSStorageCourse.php');

$ilLog->write('DB Migration 944: Starting migration of course info files');

if(@is_dir($dir = ilUpdateUtils::getDataDir().'/course'))
{
	$dp = @opendir($dir);
	while(($filedir = readdir($dp)) !== false)
	{
		if($filedir == '.' or $filedir == '..')
		{
			continue;
		}
		if(preg_match('/^course_file([0-9]+)$/',$filedir,$matches))
		{
			$ilLog->write('DB Migration 944: Found file: '.$filedir.' with course_id: '.$matches[1]);
			
			$fss_course = new ilFSStorageCourse($matches[1]);
			$fss_course->initInfoDirectory();
			
			if(@is_dir($info_dir = ilUpdateUtils::getDataDir().'/course/'.$filedir))
			{
				$dp2 = @opendir($info_dir);
				while(($file = readdir($dp2)) !== false)
				{
					if($file == '.' or $file == '..')
					{
						continue;
					}
					$fss_course->rename($from = ilUpdateUtils::getDataDir().'/course/'.$filedir.'/'.$file,
						$to = $fss_course->getInfoDirectory().'/'.$file);

					$ilLog->write('DB Migration 944: Renamed: '.$from.' to: '.$to);
				}
				
			}
		}
		
	}
}
chdir($wd);
?>
<#945>
<?php
// new permission copy
$query = "INSERT INTO rbac_operations SET operation = 'copy', ".
	"description = 'Copy Object', ".
	"class = 'general', ".
	"op_order = '115'";

$res = $ilDB->query($query);
?>
<#946>
<?php

// copy permission id
$query = "SELECT * FROM rbac_operations WHERE operation = 'copy'";
$res = $ilDB->query($query);
$row = $res->fetchRow(DB_FETCHMODE_OBJECT);
$ops_id = $row->ops_id;

$all_types = array('cat','chat','crs','dbk','exc','file','fold','frm','glo','grp','htlm','icrs','lm','mcst','sahs','svy','tst','webr');
foreach($all_types as $type)
{
	$query = "SELECT obj_id FROM object_data WHERE type = 'typ' AND title = '".$type."'";
	$res = $ilDB->query($query);
	$row = $res->fetchRow(DB_FETCHMODE_OBJECT);
	
	$query = "INSERT INTO rbac_ta SET typ_id = '".$row->obj_id."', ops_id = '".$ops_id."'";
	$ilDB->query($query);
}
?>
<#947>
DROP TABLE IF EXISTS tmp_migration;
CREATE TABLE `tmp_migration` (
  `obj_id` int(11) NOT NULL default '0',
  `parent` int(11) NOT NULL default '0',
  `type` varchar(4),
  `passed` tinyint(4) NOT NULL default '0');
  
<#948>
<?php
// Adjust rbac templates 

// copy permission id
$query = "SELECT * FROM rbac_operations WHERE operation = 'copy'";
$res = $ilDB->query($query);
$row = $res->fetchRow(DB_FETCHMODE_OBJECT);
$ops_id = $row->ops_id;

$all_types = array('cat','chat','crs','dbk','exc','file','fold','frm','glo','grp','htlm','icrs','lm','mcst','sahs','svy','tst','webr');
$query = "SELECT * FROM rbac_templates ".
	"WHERE type IN ('".implode("','",$all_types)."') ".
	"AND ops_id = 4 ".
	"ORDER BY rol_id,parent";
$res = $ilDB->query($query);
while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
{
	// CHECK done
	$query = "SELECT * FROM tmp_migration ".
		"WHERE obj_id = '".$row->rol_id."' ".
		"AND parent = '".$row->parent."' ".
		"AND type = '".$row->type."'";
	$res_done = $ilDB->query($query);
	if($res_done->numRows())
	{
		continue;
	}
	// INSERT new permission
	$query = "INSERT INTO rbac_templates SET ".
		"rol_id = '".$row->rol_id."', ".
		"type = '".$row->type."', ".
		"ops_id = '".$ops_id."', ".
		"parent = '".$row->parent."'";
	$ilDB->query($query);
	
	// Set Passed
	$query = "INSERT INTO tmp_migration SET ".
		"obj_id = '".$row->rol_id."', ".
		"parent = '".$row->parent."', ".
		"type = '".$row->type."', ".
		"passed = '1'";
	$ilDB->query($query);
}
?>
<#949>
DROP TABLE IF EXISTS tmp_migration;
CREATE TABLE `tmp_migration` (
  `rol_id` int(11) NOT NULL default '0',
  `ref_id` int(11) NOT NULL default '0',
  `passed` tinyint(4) NOT NULL default '0');

<#950>
<?php
// Adjust rbac_pa

// copy permission id
$query = "SELECT * FROM rbac_operations WHERE operation = 'copy'";
$res = $ilDB->query($query);
$row = $res->fetchRow(DB_FETCHMODE_OBJECT);
$ops_id = (int) $row->ops_id;

$all_types = array('cat','chat','crs','dbk','exc','file','fold','frm','glo','grp','htlm','icrs','lm','mcst','sahs','svy','tst','webr');

// Get all objects
$query = "SELECT rol_id,ops_id,pa.ref_id AS ref_id FROM rbac_pa AS pa ".
	"JOIN object_reference AS obr ON pa.ref_id = obr.ref_id ".
	"JOIN object_data AS obd ON obr.obj_id = obd.obj_id ".
	"WHERE obd.type IN ('".implode("','",$all_types)."') ".
	"ORDER BY rol_id,pa.ref_id ";
$res = $ilDB->query($query);
while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
{
	// CHECK done
	$query = "SELECT * FROM tmp_migration ".
		"WHERE rol_id = '".$row->rol_id."' ".
		"AND ref_id = '".$row->ref_id."' ".
		"AND passed = '1'";
	$res_done = $ilDB->query($query);
	if($res_done->numRows())
	{
		continue;
	}
	$ops_ids = unserialize(stripslashes($row->ops_id));
	// write granted ?
	if(!in_array(4,$ops_ids))
	{
		continue;
	}
	// Grant permission
	$ops_ids[] = $ops_id;
	$query = "UPDATE rbac_pa SET ".
		"ops_id = '".addslashes(serialize($ops_ids))."' ".
		"WHERE rol_id = '".$row->rol_id."' ".
		"AND ref_id = '".$row->ref_id."'";
	$ilDB->query($query);
	
	// Set Passed
	$query = "INSERT INTO tmp_migration SET ".
		"rol_id = '".$row->rol_id."', ".
		"ref_id = '".$row->ref_id."', ".
		"passed = '1'";
	$ilDB->query($query);
}
?>
<#951>
DROP TABLE IF EXISTS tmp_migration;
<#952>
<?php
// Mail enhancements part II
// Create b-tree index for fast access to object titles. This is needed for
// efficient generation and resolution of role mailbox addresses.
$query = "CREATE INDEX title_index USING BTREE ON object_data (title ASC);";
$this->db->query($query);
?>
<#953>
<?php
$ilCtrlStructureReader->getStructure();
?>
<#954>
<?php
$query = "UPDATE usr_data SET ext_account = login WHERE auth_mode = 'radius'";
$ilDB->query($query);
?>
<#955>
<?php
// add create operation for 
$query = "INSERT INTO rbac_operations ".
	"SET operation = 'create_feed', description = 'create external feed'";
$ilDB->query($query);

// get new ops_id
$query = "SELECT LAST_INSERT_ID()";
$res = $ilDB->query($query);
$row = $res->fetchRow();
$ops_id = $row[0];

// add create feed for crs,cat,fold and grp
// get category type id
$query = "SELECT obj_id FROM object_data WHERE type='typ' and title='cat'";
$res = $ilDB->query($query);
$row = $res->fetchRow();
$typ_id = $row[0];

$query = "INSERT INTO rbac_ta (typ_id, ops_id) VALUES ('".$typ_id."','".$ops_id."')";
$ilDB->query($query);

$query = "SELECT obj_id FROM object_data WHERE type='typ' and title='crs'";
$res = $ilDB->query($query);
$row = $res->fetchRow();
$typ_id = $row[0];

$query = "INSERT INTO rbac_ta (typ_id, ops_id) VALUES ('".$typ_id."','".$ops_id."')";
$ilDB->query($query);

$query = "SELECT obj_id FROM object_data WHERE type='typ' and title='grp'";
$res = $ilDB->query($query);
$row = $res->fetchRow();
$typ_id = $row[0];

$query = "INSERT INTO rbac_ta (typ_id, ops_id) VALUES ('".$typ_id."','".$ops_id."')";
$ilDB->query($query);

?>
<#956>
UPDATE rbac_operations SET class='create' WHERE operation='create_feed';

<#957>
<?php
// add create operation for 
$query = "INSERT INTO rbac_operations ".
	"SET operation = 'create_mcst', class='create', description = 'create media cast'";
$ilDB->query($query);

// get new ops_id
$query = "SELECT LAST_INSERT_ID()";
$res = $ilDB->query($query);
$row = $res->fetchRow();
$ops_id = $row[0];

// add create feed for crs,cat,fold and grp
// get category type id
$query = "SELECT obj_id FROM object_data WHERE type='typ' and title='cat'";
$res = $ilDB->query($query);
$row = $res->fetchRow();
$typ_id = $row[0];

$query = "INSERT INTO rbac_ta (typ_id, ops_id) VALUES ('".$typ_id."','".$ops_id."')";
$ilDB->query($query);

$query = "SELECT obj_id FROM object_data WHERE type='typ' and title='crs'";
$res = $ilDB->query($query);
$row = $res->fetchRow();
$typ_id = $row[0];

$query = "INSERT INTO rbac_ta (typ_id, ops_id) VALUES ('".$typ_id."','".$ops_id."')";
$ilDB->query($query);

$query = "SELECT obj_id FROM object_data WHERE type='typ' and title='grp'";
$res = $ilDB->query($query);
$row = $res->fetchRow();
$typ_id = $row[0];

$query = "INSERT INTO rbac_ta (typ_id, ops_id) VALUES ('".$typ_id."','".$ops_id."')";
$ilDB->query($query);

$query = "SELECT obj_id FROM object_data WHERE type='typ' and title='fold'";
$res = $ilDB->query($query);
$row = $res->fetchRow();
$typ_id = $row[0];

$query = "INSERT INTO rbac_ta (typ_id, ops_id) VALUES ('".$typ_id."','".$ops_id."')";
$ilDB->query($query);

?>
<#958>
ALTER TABLE `il_media_cast_data` CHANGE `offline` `online` TINYINT DEFAULT 0;

<#959>
ALTER TABLE `frm_posts` ADD `pos_usr_alias` VARCHAR( 255 ) NOT NULL AFTER `pos_usr_id` ;
ALTER TABLE `frm_threads` ADD `thr_usr_alias` VARCHAR( 255 ) NOT NULL AFTER `thr_usr_id` ;

<#960>
<?php
$ilCtrlStructureReader->getStructure();
?>

<#961>
ALTER TABLE  `qpl_question_cloze` ADD  `fixed_textlen` INT NULL ;

<#962>
DROP TABLE IF EXISTS il_news_read;
CREATE TABLE `il_news_read` (
  `user_id` int(11) NOT NULL default '0',
  `news_id` int(11) NOT NULL default '0',
  INDEX (`user_id`));

<#963>
ALTER TABLE `il_news_read` ADD INDEX (`news_id`);

