<#1>
#intial release of database
<#2>
#
# Tabellenstruktur f�r Tabelle `frm_posts_tree`
#
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

#
# Tabellenstruktur f�r Tabelle `frm_data`
#

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

#
# Tabellenstruktur f�r Tabelle `frm_posts`
#

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

#
# Tabellenstruktur f�r Tabelle `frm_threads`
#

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

<<<<<<< dbupdate.php
<#9>
# new table note_data
DROP TABLE IF EXISTS note_data;
CREATE TABLE note_data (
  note_id int(11) NOT NULL default '0',
  lo_id int(11) NOT NULL default '0',
  lo_title varchar(255) default '',
  text text,
  create_date datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (note_id)
) TYPE=MyISAM;

<#10>
# new object-types: note folder object, note object
INSERT INTO object_data (type,title,description,owner,create_date,last_update) VALUES ('typ', 'notf', 'Note Folder Object', -1, '2002-12-21 00:04:00', '2002-12-21 00:04:00');
INSERT INTO object_data (type,title,description,owner,create_date,last_update) VALUES ('typ', 'note', 'Note Object', -1, '2002-12-21 00:04:00', '2002-12-21 00:04:00');
=======
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
ALTER TABLE `lo_attribute_namespace_leaf` CHANGE `leaf_id` `leaf_id` INT( 11 ) DEFAULT '0' NOT NULL AUTO_INCREMENT;>>>>>>> 1.35
