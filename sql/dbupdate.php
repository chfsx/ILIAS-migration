<#1>
#intial release of database
<#2>
# adding forum tables
DROP TABLE IF EXISTS frm_posts_tree;
CREATE TABLE frm_posts_tree (
  fpt_pk bigint(20) NOT NULL auto_increment,
  thr_fk bigint(20) NOT NULL default '0',
  pos_fk bigint(20) NOT NULL default '0',
  parent_pos bigint(20) NOT NULL default '0',
  lft int(11) NOT NULL default '0',
  rgt int(11) NOT NULL default '0',
  depth int(11) NOT NULL default '0',
  date datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (fpt_pk)
) TYPE=MyISAM;

DROP TABLE IF EXISTS frm_data;
CREATE TABLE frm_data (
  top_pk bigint(20) NOT NULL auto_increment,
  top_frm_fk bigint(20) NOT NULL default '0',
  top_name varchar(255) NOT NULL default '',
  top_description varchar(255) NOT NULL default '',
  top_num_posts int(11) NOT NULL default '0',
  top_num_threads int(11) NOT NULL default '0',
  top_last_post varchar(50) NOT NULL default '',
  top_mods varchar(100) NOT NULL default '',
  top_last_modified datetime NOT NULL default '0000-00-00 00:00:00',
  visits int(11) NOT NULL default '0',
  PRIMARY KEY  (top_pk)
) TYPE=MyISAM;

DROP TABLE IF EXISTS frm_posts;
CREATE TABLE frm_posts (
  pos_pk bigint(20) NOT NULL auto_increment,
  pos_top_fk bigint(20) NOT NULL default '0',
  pos_thr_fk bigint(20) NOT NULL default '0',
  pos_usr_id bigint(20) NOT NULL default '0',
  pos_message text NOT NULL,
  pos_date datetime NOT NULL default '0000-00-00 00:00:00',
  pos_update datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (pos_pk)
) TYPE=MyISAM;

DROP TABLE IF EXISTS frm_threads;
CREATE TABLE frm_threads (
  thr_pk bigint(20) NOT NULL auto_increment,
  thr_top_fk bigint(20) NOT NULL default '0',
  thr_subject varchar(255) NOT NULL default '',
  thr_usr_id bigint(20) NOT NULL default '0',
  thr_num_posts int(11) NOT NULL default '0',
  thr_last_post varchar(50) NOT NULL default '',
  thr_date datetime NOT NULL default '0000-00-00 00:00:00',
  thr_update datetime NOT NULL default '0000-00-00 00:00:00',
  thr_last_modified datetime NOT NULL default '0000-00-00 00:00:00',
  visits int(11) NOT NULL default '0',
  PRIMARY KEY  (thr_pk)
) TYPE=MyISAM;

<#3>
# set system adminstrator login to root/homer
UPDATE usr_data SET 
login='root', 
passwd='dfa8327f5bfa4c672a04f9b38e348a70' 
WHERE usr_id='6';

<#4>
# change column in `frm_data`
ALTER TABLE `frm_data` CHANGE `top_last_modified` `top_date` DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL;

# new column in `frm_data`
ALTER TABLE `frm_data` ADD `top_update` DATETIME NOT NULL;

# new column in `frm_data`
ALTER TABLE `frm_data` ADD `update_user` INT NOT NULL ;

# new column in `frm_data`
ALTER TABLE `frm_data` ADD `top_usr_id` BIGINT( 20 ) NOT NULL ;

# delete column in `frm_threads`
ALTER TABLE `frm_threads` DROP `thr_last_modified`;

<#5>
# There are some old wrong entries in rbac_templates => delete them
DELETE FROM rbac_templates
WHERE parent='152';

<#6>
# new forum operation in `rbac_operations`
INSERT INTO `rbac_operations` ( `ops_id` , `operation` , `description` ) 
VALUES (
'9', 'edit post', 'edit forum articles'
);

# new operation link in `rbac_ta`
INSERT INTO `rbac_ta` ( `typ_id` , `ops_id` ) 
VALUES (
'14', '9'
);

<#7>
# change data type in `frm_data`
ALTER TABLE `frm_data` CHANGE `top_mods` `top_mods` INT NOT NULL ;

# new forum operation in `rbac_operations`
INSERT INTO `rbac_operations` ( `ops_id` , `operation` , `description` ) 
VALUES (
'10', 'delete post', 'delete forum articles'
);

# new operation link in `rbac_ta`
INSERT INTO `rbac_ta` ( `typ_id` , `ops_id` ) 
VALUES (
'14', '10'
);

<#8>
# new column in `frm_posts`
ALTER TABLE `frm_posts` ADD `update_user` INT NOT NULL ;

<#9>
#  delete operation create of root folder and add operation delete
UPDATE rbac_ta SET ops_id='6' WHERE typ_id='33' AND ops_id='5';

<#10>
#  set missing primary key an auto increment flag for some lo_tables
ALTER TABLE `lo_attribute_name_leaf` DROP PRIMARY KEY , ADD PRIMARY KEY ( `leaf_id` ); 
ALTER TABLE `lo_attribute_name_leaf` CHANGE `leaf_id` `leaf_id` INT( 11 ) DEFAULT '0' NOT NULL AUTO_INCREMENT;

ALTER TABLE `lo_attribute` DROP PRIMARY KEY ,ADD PRIMARY KEY ( `attribute_id` );
ALTER TABLE `lo_attribute` CHANGE `attribute_id` `attribute_id` INT( 11 ) DEFAULT '0' NOT NULL AUTO_INCREMENT;

ALTER TABLE `lo_attribute_namespace_leaf` DROP PRIMARY KEY ,ADD PRIMARY KEY ( `leaf_id` );
ALTER TABLE `lo_attribute_namespace_leaf` CHANGE `leaf_id` `leaf_id` INT( 11 ) DEFAULT '0' NOT NULL AUTO_INCREMENT;

<#11>
# new object-types: note folder object, note object
INSERT INTO object_data (type,title,description,owner,create_date,last_update) VALUES ('typ', 'notf', 'Note Folder Object', -1, '2002-12-21 00:04:00', '2002-12-21 00:04:00');
INSERT INTO object_data (type,title,description,owner,create_date,last_update) VALUES ('typ', 'note', 'Note Object', -1, '2002-12-21 00:04:00', '2002-12-21 00:04:00');

# new table note_data
DROP TABLE IF EXISTS note_data;
CREATE TABLE note_data (
  note_id int(11) NOT NULL default '0',
  lo_id int(11) NOT NULL default '0',
  text text,
  create_date datetime NOT NULL default '0000-00-00 00:00:00',
  last_update datetime NOT NULL default '0000-00-00 00:00:00',
  important enum('y','n') NOT NULL default 'n',
  good enum('y','n') NOT NULL default 'n',
  question enum('y','n') NOT NULL default 'n',
  bad enum('y','n') NOT NULL default 'n',
  PRIMARY KEY  (note_id)
) TYPE=MyISAM;

<#12>
# many changes in LO-repository

# remove old tables
 DROP TABLE IF EXISTS `lo_attribute` ,
`lo_attribute_name_leaf` ,
`lo_attribute_namespace_leaf` ,
`lo_attribute_value_leaf` ,
`lo_cdata_leaf` ,
`lo_comment_leaf` ,
`lo_element_name_leaf` ,
`lo_element_namespace_leaf` ,
`lo_entity_reference_leaf` ,
`lo_pi_data_leaf` ,
`lo_pi_target_leaf` ,
`lo_text_leaf` ,
`lo_tree` ;

# add new tables
DROP TABLE IF EXISTS lo_attribute_idx;
CREATE TABLE lo_attribute_idx (
  node_id int(10) unsigned NOT NULL default '0',
  attribute_id smallint(5) unsigned NOT NULL default '0',
  value_id smallint(5) unsigned NOT NULL default '0',
  KEY node_id (node_id)
) TYPE=MyISAM;

DROP TABLE IF EXISTS lo_attribute_name;
CREATE TABLE lo_attribute_name (
  attribute_id smallint(5) unsigned NOT NULL auto_increment,
  attribute char(32) NOT NULL default '',
  PRIMARY KEY  (attribute_id),
  UNIQUE KEY attribute (attribute)
) TYPE=MyISAM;

DROP TABLE IF EXISTS lo_attribute_namespace;
CREATE TABLE lo_attribute_namespace (
  attribute_id smallint(5) unsigned NOT NULL auto_increment,
  node_id int(10) unsigned NOT NULL default '0',
  namespace char(64) NOT NULL default '',
  PRIMARY KEY  (attribute_id)
) TYPE=MyISAM;

DROP TABLE IF EXISTS lo_attribute_value;
CREATE TABLE lo_attribute_value (
  value_id smallint(5) unsigned NOT NULL auto_increment,
  value char(32) NOT NULL default '0',
  PRIMARY KEY  (value_id)
) TYPE=MyISAM;

DROP TABLE IF EXISTS lo_cdata;
CREATE TABLE lo_cdata (
  node_id int(10) unsigned NOT NULL auto_increment,
  cdata text NOT NULL,
  PRIMARY KEY  (node_id)
) TYPE=MyISAM;

DROP TABLE IF EXISTS lo_comment;
CREATE TABLE lo_comment (
  node_id int(10) unsigned NOT NULL auto_increment,
  comment text NOT NULL,
  PRIMARY KEY  (node_id)
) TYPE=MyISAM;

DROP TABLE IF EXISTS lo_element_idx;
CREATE TABLE lo_element_idx (
  node_id int(10) unsigned NOT NULL default '0',
  element_id smallint(5) unsigned NOT NULL default '0',
  PRIMARY KEY  (node_id)
) TYPE=MyISAM;

DROP TABLE IF EXISTS lo_element_name;
CREATE TABLE lo_element_name (
  element_id smallint(5) unsigned NOT NULL auto_increment,
  element char(32) NOT NULL default '',
  PRIMARY KEY  (element_id),
  UNIQUE KEY element (element)
) TYPE=MyISAM;

DROP TABLE IF EXISTS lo_element_namespace;
CREATE TABLE lo_element_namespace (
  element_id smallint(5) unsigned NOT NULL auto_increment,
  node_id int(10) unsigned NOT NULL default '0',
  namespace char(64) NOT NULL default '',
  PRIMARY KEY  (element_id)
) TYPE=MyISAM;

DROP TABLE IF EXISTS lo_entity_reference;
CREATE TABLE lo_entity_reference (
  element_id smallint(5) unsigned NOT NULL auto_increment,
  node_id int(10) unsigned NOT NULL default '0',
  entity_reference char(128) NOT NULL default '',
  PRIMARY KEY  (element_id)
) TYPE=MyISAM;

DROP TABLE IF EXISTS lo_pi_data;
CREATE TABLE lo_pi_data (
  leaf_id int(10) unsigned NOT NULL auto_increment,
  node_id int(10) unsigned NOT NULL default '0',
  leaf_text text NOT NULL,
  PRIMARY KEY  (leaf_id)
) TYPE=MyISAM;

DROP TABLE IF EXISTS lo_pi_target;
CREATE TABLE lo_pi_target (
  leaf_id int(10) unsigned NOT NULL auto_increment,
  node_id int(10) unsigned NOT NULL default '0',
  leaf_text text NOT NULL,
  PRIMARY KEY  (leaf_id)
) TYPE=MyISAM;

DROP TABLE IF EXISTS lo_text;
CREATE TABLE lo_text (
  node_id int(10) unsigned NOT NULL default '0',
  textnode text NOT NULL,
  PRIMARY KEY  (node_id),
  FULLTEXT KEY textnode (textnode)
) TYPE=MyISAM;

DROP TABLE IF EXISTS lo_tree;
CREATE TABLE lo_tree (
  node_id int(10) unsigned NOT NULL auto_increment,
  lo_id mediumint(8) unsigned NOT NULL default '0',
  parent_node_id int(10) unsigned NOT NULL default '0',
  lft smallint(5) unsigned NOT NULL default '0',
  rgt smallint(5) unsigned NOT NULL default '0',
  node_type_id tinyint(3) unsigned NOT NULL default '0',
  depth smallint(5) unsigned NOT NULL default '0',
  prev_sibling_node_id int(10) unsigned NOT NULL default '0',
  next_sibling_node_id int(10) unsigned NOT NULL default '0',
  first_child_node_id int(10) unsigned NOT NULL default '0',
  struct tinyint(3) unsigned NOT NULL default '0',
  PRIMARY KEY  (node_id),
  KEY lo_id (lo_id)
) TYPE=MyISAM;

# remove old LO entries
<?php
$query = "SELECT obj_id FROM object_data WHERE type = 'lo'";
$result = $this->db->query($query);

while ($data = $result->fetchRow(DB_FETCHMODE_ASSOC))
{
	$obj_id = $data["obj_id"];
	
	$query = "DELETE FROM tree WHERE child = '".$obj_id."'";
	$this->db->query($query);
}

$query = "DELETE FROM object_data WHERE type = 'lo'";
$this->db->query($query); 
?>
# remove legacy forum objects
DELETE FROM tree WHERE child = '172';
DELETE FROM tree WHERE child = '174';
DELETE FROM tree WHERE child = '178';
DELETE FROM object_data WHERE obj_id = '172';
DELETE FROM object_data WHERE obj_id = '174';
DELETE FROM object_data WHERE obj_id = '178';
DELETE FROM rbac_pa WHERE obj_id = '172';
DELETE FROM rbac_pa WHERE obj_id = '174';
DELETE FROM rbac_pa WHERE obj_id = '178';

# db performance tuning: setting several indexes and shortened some data columns
ALTER TABLE `object_data` ADD INDEX (`type`);
ALTER TABLE `rbac_fa` DROP PRIMARY KEY;
ALTER TABLE `rbac_fa` DROP PRIMARY KEY, ADD PRIMARY KEY (`rol_id`);
ALTER TABLE `rbac_operations` CHANGE `operation` `operation` CHAR(32) NOT NULL;
ALTER TABLE `rbac_operations` ADD UNIQUE (`operation`);
ALTER TABLE `rbac_templates` ADD INDEX (`rol_id`);
ALTER TABLE `rbac_templates` ADD INDEX (`type`);
ALTER TABLE `rbac_templates` ADD INDEX (`ops_id`);
ALTER TABLE `rbac_templates` ADD INDEX (`parent`);
ALTER TABLE `rbac_ua` DROP PRIMARY KEY;
ALTER TABLE `rbac_ua` ADD INDEX (`usr_id`);
ALTER TABLE `rbac_ua` ADD INDEX (`rol_id`);
ALTER TABLE `settings` DROP PRIMARY KEY;
ALTER TABLE `settings` DROP PRIMARY KEY ,
ADD PRIMARY KEY (`keyword`);
ALTER TABLE `tree` CHANGE `tree` `tree` SMALLINT(5) UNSIGNED DEFAULT '0' NOT NULL;
ALTER TABLE `tree` CHANGE `child` `child` INT(10) UNSIGNED DEFAULT '0' NOT NULL;
ALTER TABLE `tree` CHANGE `parent` `parent` INT(10) UNSIGNED DEFAULT NULL;
ALTER TABLE `tree` CHANGE `lft` `lft` INT(10) UNSIGNED DEFAULT '0' NOT NULL;
ALTER TABLE `tree` CHANGE `rgt` `rgt` INT(10) UNSIGNED DEFAULT '0' NOT NULL;
ALTER TABLE `tree` CHANGE `depth` `depth` SMALLINT(5) UNSIGNED DEFAULT '0' NOT NULL;
ALTER TABLE `usr_data` CHANGE `usr_id` `usr_id` INT(10) UNSIGNED DEFAULT '0' NOT NULL;
ALTER TABLE `usr_pref` CHANGE `usr_id` `usr_id` INT( 10 ) UNSIGNED DEFAULT '0' NOT NULL;
ALTER TABLE `tree` ADD INDEX (`child`);
ALTER TABLE `tree` ADD INDEX (`parent`);

<#13>
#due to problems changed index back for rbac_fa
ALTER TABLE `rbac_fa` DROP PRIMARY KEY, ADD PRIMARY KEY (rol_id,parent);

<#14>
#fixed bug of update #12
#tree tree shouldn't be unsigned, otherwise trash bin doesn't work
ALTER TABLE `tree` CHANGE `tree` `tree` INT(10) DEFAULT '0' NOT NULL;

<#15>
#drop unnecessary column
<?php
$query = "DESCRIBE note_data";
$result = $this->db->query($query);

while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC))
{
	$data[] = $row["Field"];
}

if (in_array("last_update",$data))
{
	$query = "ALTER TABLE note_data DROP COLUMN last_update";
	$this->db->query($query);
}
?>
<#16>
#modified usr_data
ALTER TABLE usr_data CHANGE surname lastname CHAR( 30 ) NOT NULL;
ALTER TABLE usr_data ADD institution VARCHAR( 80 ) AFTER email ,
ADD street VARCHAR( 40 ) AFTER institution ,
ADD city VARCHAR( 40 ) AFTER street ,
ADD zipcode VARCHAR( 10 ) AFTER city ,
ADD country VARCHAR( 40 ) AFTER zipcode ,
ADD phone VARCHAR( 40 ) AFTER country ;

<#17>
#introducing reference id table
DROP TABLE IF EXISTS object_reference;
CREATE TABLE object_reference (
ref_id INT NOT NULL AUTO_INCREMENT,
obj_id INT NOT NULL ,
PRIMARY KEY (ref_id)
) TYPE=MyISAM;

<#18>
#migrate existing objects
<?php
$tree_objects = array("frm","grp","cat","root","adm","lngf","objf","usrf","rolf","lm","le","lo","crs");

$query = "SELECT type,obj_id FROM object_data ORDER by obj_id";
$result = $this->db->query($query);

while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC))
{
	$data[$row["obj_id"]] = $row["type"];
}

foreach ($data as $_id => $_type)
{
	if (in_array($_type,$tree_objects))
	{
		$query = "INSERT INTO object_reference (ref_id,obj_id) VALUES ('".$_id."','".$_id."')";
		$this->db->query($query);
	}
}
?>
<#19>
#enlarge several user fields to 32 letters
ALTER TABLE usr_data CHANGE login login VARCHAR( 32 ) NOT NULL;
ALTER TABLE usr_data CHANGE firstname firstname VARCHAR( 32 ) NOT NULL;
ALTER TABLE usr_data CHANGE lastname lastname VARCHAR( 32 ) NOT NULL;
ALTER TABLE usr_data CHANGE title title VARCHAR( 32 ) NOT NULL;

<#20>
# init mail functions
DROP TABLE IF EXISTS mail;
CREATE TABLE mail (
	mail_id int(11) NOT NULL auto_increment,
	user_id int(11) NOT NULL default '0',
	folder_id int(11) NOT NULL default '0',
	sender_id int(11) default NULL,
	attachments varchar(255) default NULL,
	send_time datetime NOT NULL default '0000-00-00 00:00:00',
	timest timestamp(14) NOT NULL,
	rcp_to varchar(255) default NULL,
	rcp_cc varchar(255) default NULL,
	rcp_bcc varchar(255) default NULL,
	m_status varchar(16) default NULL,
	m_type varchar(16) default NULL,
	m_email tinyint(1) default NULL,
	m_subject varchar(255) default NULL,
	m_message text,
	PRIMARY KEY (mail_id)
	) TYPE=MyISAM;

DROP TABLE IF EXISTS mail_attachment;
CREATE TABLE mail_attachment (
	mail_id int(11) NOT NULL default '0',
	path text NOT NULL,
	PRIMARY KEY  (mail_id)
	) TYPE=MyISAM;
	
DROP TABLE IF EXISTS mail_obj_data;
CREATE TABLE mail_obj_data (
	obj_id int(11) NOT NULL auto_increment,
	user_id int(11) NOT NULL default '0',
	title char(70) NOT NULL default '',
	type char(16) NOT NULL default '',
	PRIMARY KEY  (obj_id,user_id)
) TYPE=MyISAM;
			
DROP TABLE IF EXISTS mail_options;
CREATE TABLE mail_options (
				user_id int(11) NOT NULL default '0',
  linebreak tinyint(4) NOT NULL default '0',
  signature text NOT NULL,
  KEY user_id (user_id,linebreak)
) TYPE=MyISAM;

DROP TABLE IF EXISTS mail_saved;
CREATE TABLE mail_saved (
  user_id int(11) NOT NULL default '0',
  attachments varchar(255) default NULL,
  rcp_to varchar(255) default NULL,
  rcp_cc varchar(255) default NULL,
  rcp_bcc varchar(255) default NULL,
  m_type varchar(16) default NULL,
  m_email tinyint(1) default NULL,
  m_subject varchar(255) default NULL,
  m_message text
	) TYPE=MyISAM;

DROP TABLE IF EXISTS mail_tree;
CREATE TABLE mail_tree (
  tree int(11) NOT NULL default '0',
  child int(11) unsigned NOT NULL default '0',
  parent int(11) unsigned default NULL,
  lft int(11) unsigned NOT NULL default '0',
  rgt int(11) unsigned NOT NULL default '0',
  depth smallint(5) unsigned NOT NULL default '0',
  KEY child (child),
  KEY parent (parent)
) TYPE=MyISAM;

<#21>
<?php
$query = "SELECT usr_id FROM usr_data";
$result = $this->db->query($query);
$counter = 1;
while($row = $result->fetchRow(DB_FETCHMODE_OBJECT))
{
	$parent = $counter + 1;
	$query = "INSERT INTO mail_tree ".
		"SET tree = '".$row->usr_id."',".
		"child = '".++$counter."',".
		"parent = '0',".
		"lft = '1',".
		"rgt = '12',".
		"depth = '1'";
	$res2 = $this->db->query($query);
	$query = "INSERT INTO mail_obj_data ".
		"SET obj_id = '".$counter."',".
		"user_id = '".$row->usr_id."',".
		"title = 'a_root',".
		"type = 'root'";
	$res3 = $this->db->query($query);
	$query = "INSERT INTO mail_tree ".
		"SET tree = '".$row->usr_id."',".
		"child = '".++$counter."',".
		"parent = '".$parent."',".
		"lft = '2',".
		"rgt = '3',".
		"depth = '2'";
	$res2 = $this->db->query($query);
	$query = "INSERT INTO mail_obj_data ".
		"SET obj_id = '".$counter."',".
		"user_id = '".$row->usr_id."',".
		"title = 'b_inbox',".
		"type = 'inbox'";
	$res3 = $this->db->query($query);
	$query = "INSERT INTO mail_tree ".
		"SET tree = '".$row->usr_id."',".
		"child = '".++$counter."',".
		"parent = '".$parent."',".
		"lft = '4',".
		"rgt = '5',".
		"depth = '2'";
	$res2 = $this->db->query($query);
	$query = "INSERT INTO mail_obj_data ".
		"SET obj_id = '".$counter."',".
		"user_id = '".$row->usr_id."',".
		"title = 'c_trash',".
		"type = 'trash'";
	$res3 = $this->db->query($query);
	$query = "INSERT INTO mail_tree ".
		"SET tree = '".$row->usr_id."',".
		"child = '".++$counter."',".
		"parent = '".$parent."',".
		"lft = '6',".
		"rgt = '7',".
		"depth = '2'";
	$res2 = $this->db->query($query);
	$query = "INSERT INTO mail_obj_data ".
		"SET obj_id = '".$counter."',".
		"user_id = '".$row->usr_id."',".
		"title = 'd_drafts',".
		"type = 'drafts'";
	$res3 = $this->db->query($query);
	$query = "INSERT INTO mail_tree ".
		"SET tree = '".$row->usr_id."',".
		"child = '".++$counter."',".
		"parent = '".$parent."',".
		"lft = '8',".
		"rgt = '9',".
		"depth = '2'";
	$res2 = $this->db->query($query);
	$query = "INSERT INTO mail_obj_data ".
		"SET obj_id = '".$counter."',".
		"user_id = '".$row->usr_id."',".
		"title = 'e_sent',".
		"type = 'sent'";
	$res3 = $this->db->query($query);
	$query = "INSERT INTO mail_tree ".
		"SET tree = '".$row->usr_id."',".
		"child = '".++$counter."',".
		"parent = '".$parent."',".
		"lft = '10',".
		"rgt = '11',".
		"depth = '2'";
	$res2 = $this->db->query($query);
	$query = "INSERT INTO mail_obj_data ".
		"SET obj_id = '".$counter."',".
		"user_id = '".$row->usr_id."',".
		"title = 'z_local',".
		"type = 'local'";
	$res3 = $this->db->query($query);
	$query = "INSERT INTO mail_options ".
		"SET user_id = '".$row->usr_id."',".
		"linebreak = '60'";
	$res4 = $this->db->query($query);
}
?>
<#22>
CREATE TABLE addressbook (
  addr_id int(11) NOT NULL auto_increment,
  user_id int(11) NOT NULL default '0',
  login varchar(40) default NULL,
  firstname varchar(40) default NULL,
  lastname varchar(40) default NULL,
  email varchar(40) default NULL,
  PRIMARY KEY  (addr_id)
) TYPE=MyISAM;