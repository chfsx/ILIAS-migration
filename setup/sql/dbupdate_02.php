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
$attribute_visibility = FALSE;
$query = "SHOW COLUMNS FROM survey_questionblock";
$res = $ilDB->query($query);
if ($res->numRows())
{
	while ($data = $res->fetchRow(DB_FETCHMODE_ASSOC))
	{
		if (strcmp($data["Field"], "show_questiontext") == 0)
		{
			$attribute_visibility = TRUE;
		}
	}
}
if ($attribute_visibility == FALSE)
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

