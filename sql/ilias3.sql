# phpMyAdmin MySQL-Dump
# version 2.3.0
# http://phpwizard.net/phpMyAdmin/
# http://www.phpmyadmin.net/ (download page)
#
# Host: localhost
# Erstellungszeit: 22. August 2002 um 12:44
# Server Version: 3.23.44
# PHP-Version: 4.2.1
# Datenbank: `ilias3`
# --------------------------------------------------------

#
# Tabellenstruktur f�r Tabelle `bookmarks`
#

CREATE TABLE bookmarks (
  usr_fk int(11) NOT NULL default '0',
  id int(11) NOT NULL default '0',
  pos int(11) NOT NULL default '0',
  url varchar(255) NOT NULL default '',
  name varchar(255) NOT NULL default '',
  folder varchar(255) NOT NULL default 'top',
  timest timestamp(14) NOT NULL,
  KEY usr_fk (usr_fk),
  KEY id (id),
  KEY pos (pos)
) TYPE=MyISAM;

#
# Daten f�r Tabelle `bookmarks`
#

INSERT INTO bookmarks (usr_fk, id, pos, url, name, folder, timest) VALUES (6, 1, 0, 'www.ilias.uni-koeln.de', 'ILIAS Uni-K�ln', 'top', 20020813174241);
INSERT INTO bookmarks (usr_fk, id, pos, url, name, folder, timest) VALUES (6, 2, 0, 'www.databay.de', 'Databay AG', 'top', 20020813174351);
# --------------------------------------------------------

#
# Tabellenstruktur f�r Tabelle `mail`
#

CREATE TABLE mail (
  id int(11) NOT NULL auto_increment,
  snd int(11) NOT NULL default '0',
  rcp int(11) NOT NULL default '0',
  snd_flag tinyint(1) NOT NULL default '0',
  rcp_flag tinyint(1) NOT NULL default '0',
  rcp_folder varchar(50) NOT NULL default 'inbox',
  subject varchar(255) NOT NULL default '',
  body text NOT NULL,
  as_email tinyint(1) NOT NULL default '0',
  date_send datetime NOT NULL default '0000-00-00 00:00:00',
  timest timestamp(14) NOT NULL,
  UNIQUE KEY id (id)
) TYPE=MyISAM;

#
# Daten f�r Tabelle `mail`
#

# --------------------------------------------------------

#
# Tabellenstruktur f�r Tabelle `object_data`
#

CREATE TABLE object_data (
  obj_id int(11) NOT NULL auto_increment,
  type enum('role','user','le','frm','grp','cat','kurs','file','mail','abo','set','adm','none','usrf','rolf','rolt','objf','type') NOT NULL default 'none',
  title char(70) NOT NULL default '',
  description char(128) default NULL,
  owner int(11) NOT NULL default '0',
  create_date datetime NOT NULL default '0000-00-00 00:00:00',
  last_update datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (obj_id)
) TYPE=MyISAM;

#
# Daten f�r Tabelle `object_data`
#

INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) VALUES (2, 'role', 'Adminstrator', 'Rolle des Systemadministrators (darf alles)', -1, '2002-01-16 15:31:45', '2002-01-16 15:32:49');
INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) VALUES (3, 'role', 'Autor', 'Rolle mit umfassenden Schreibrechten', -1, '2002-01-16 15:32:50', '2002-01-16 15:33:54');
INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) VALUES (4, 'role', 'Lerner', 'Rolle f�r Studierende (wenig Schreibrechte)', -1, '2002-01-16 15:34:00', '2002-01-16 15:34:35');
INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) VALUES (5, 'role', 'Gast', 'Gastzugang mit wenig Leserechten', -1, '2002-01-16 15:34:46', '2002-01-16 15:35:19');
INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) VALUES (6, 'user', 'Meister Ad Min', 'nix', -1, '2002-01-16 16:09:22', '2002-01-16 16:09:22');
INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) VALUES (7, 'usrf', 'User Folder', 'Folder der alle User enth�lt', -1, '2002-06-27 09:24:06', '2002-06-27 09:24:06');
INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) VALUES (8, 'rolf', 'Role Folder', 'Folder der alle System Rollen enth�lt', -1, '2002-06-27 09:24:06', '2002-06-27 09:24:06');
INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) VALUES (1, 'cat', 'root', 'Root Kategorie', -1, '2002-06-24 15:15:03', '2002-06-24 15:15:03');
INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) VALUES (140, 'user', 'Lerner', 'nix', 6, '2002-07-11 10:28:13', '2002-07-11 10:28:13');
INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) VALUES (141, 'user', 'Autor', 'nix', 6, '2002-07-11 10:28:34', '2002-07-11 10:28:34');
INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) VALUES (142, 'user', 'Gast', 'nix', 6, '2002-07-11 10:28:54', '2002-07-11 10:28:54');
INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) VALUES (10, 'objf', 'Object Folder', 'Contains list of known object types', -1, '2002-07-15 12:36:56', '2002-07-15 12:36:56');
INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) VALUES (9, 'adm', 'System Settings', 'Contains systems settings', -1, '2002-07-15 12:37:33', '2002-07-15 12:37:33');
INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) VALUES (11, 'type', 'role', 'Role object', -1, '2002-07-15 15:52:51', '2002-07-15 15:52:51');
INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) VALUES (12, 'type', 'user', 'User object', -1, '2002-07-15 15:53:37', '2002-07-15 15:53:37');
INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) VALUES (13, 'type', 'le', 'Learning object', -1, '2002-07-15 15:54:04', '2002-07-15 15:54:04');
INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) VALUES (14, 'type', 'frm', 'Forum object', -1, '2002-07-15 15:54:22', '2002-07-15 15:54:22');
INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) VALUES (15, 'type', 'grp', 'Group object', -1, '2002-07-15 15:54:37', '2002-07-15 15:54:37');
INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) VALUES (16, 'type', 'cat', 'Category object', -1, '2002-07-15 15:54:54', '2002-07-15 15:54:54');
INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) VALUES (17, 'type', 'crs', 'Course object', -1, '2002-07-15 15:55:08', '2002-07-15 15:55:08');
INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) VALUES (18, 'type', 'file', 'FileSharing object', -1, '2002-07-15 15:55:31', '2002-07-15 15:55:31');
INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) VALUES (19, 'type', 'mail', 'Mailmodule object', -1, '2002-07-15 15:55:49', '2002-07-15 15:55:49');
INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) VALUES (20, 'type', 'abo', 'Subscription/Membership object', -1, '2002-07-15 15:56:11', '2002-07-15 15:56:11');
INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) VALUES (21, 'type', 'adm', 'Administration Panel object', -1, '2002-07-15 15:56:38', '2002-07-15 15:56:38');
INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) VALUES (22, 'type', 'usrf', 'User Folder object', -1, '2002-07-15 15:56:52', '2002-07-15 15:56:52');
INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) VALUES (23, 'type', 'rolf', 'Role Folder object', -1, '2002-07-15 15:57:06', '2002-07-15 15:57:06');
INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) VALUES (24, 'type', 'objf', 'Object-Type Folder object', -1, '2002-07-15 15:57:17', '2002-07-15 15:57:17');
INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) VALUES (25, 'type', 'set', 'Set object', -1, '2002-07-15 15:57:57', '2002-07-15 15:57:57');
INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) VALUES (26, 'type', 'type', 'Object Type Definition object', -1, '2002-07-15 15:58:16', '2002-07-15 15:58:16');
INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) VALUES (27, 'type', 'rolt', 'Role template object', -1, '2002-07-15 15:58:16', '2002-07-15 15:58:16');
INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) VALUES (150, 'grp', 'closed', 'Closed Group', 6, '2002-07-22 16:25:54', '2002-07-22 16:25:54');
INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) VALUES (149, 'grp', 'open', '', 6, '2002-07-22 16:25:37', '2002-07-22 16:25:37');
INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) VALUES (148, 'cat', 'Uni K�ln', '', 6, '2002-07-22 16:25:15', '2002-07-22 16:25:15');
INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) VALUES (151, 'le', 'secret', '', 6, '2002-07-22 16:26:17', '2002-07-22 16:26:17');
INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) VALUES (152, 'rolf', 'Role Folder', 'Automatisch genierter Role Folder', 6, '2002-07-22 16:26:51', '2002-07-22 16:26:51');
# --------------------------------------------------------

#
# Tabellenstruktur f�r Tabelle `object_types`
#

CREATE TABLE object_types (
  typ_id tinyint(4) unsigned NOT NULL auto_increment,
  type char(4) NOT NULL default '',
  container enum('y','n') NOT NULL default 'n',
  title char(30) NOT NULL default '',
  description char(128) NOT NULL default '',
  PRIMARY KEY  (typ_id)
) TYPE=MyISAM;

#
# Daten f�r Tabelle `object_types`
#

INSERT INTO object_types (typ_id, type, container, title, description) VALUES (11, 'role', 'n', 'Rolle', 'Rollenobjekt');
INSERT INTO object_types (typ_id, type, container, title, description) VALUES (21, 'adm', 'n', 'Administration', 'Contains all system settings');
INSERT INTO object_types (typ_id, type, container, title, description) VALUES (13, 'le', 'y', 'Lerneinheit', 'Objekt erzeugt eine Lerneinheit');
INSERT INTO object_types (typ_id, type, container, title, description) VALUES (14, 'frm', 'y', 'Forum', 'Objekt erzeugt ein Forum');
INSERT INTO object_types (typ_id, type, container, title, description) VALUES (15, 'grp', 'y', 'Arbeitsgruppe', 'Objekt erzeugt eine Arbeitsgruppe');
INSERT INTO object_types (typ_id, type, container, title, description) VALUES (16, 'cat', 'y', 'Kategorie', 'Erzeugt ein Kategorienobjekt');
INSERT INTO object_types (typ_id, type, container, title, description) VALUES (18, 'file', 'y', 'File Sharing', 'Erzeugt ein File Sharing Objekt');
INSERT INTO object_types (typ_id, type, container, title, description) VALUES (20, 'abo', 'n', 'Abonnentengruppe', 'erzeugt einen Abo-Set');
INSERT INTO object_types (typ_id, type, container, title, description) VALUES (17, 'kurs', 'y', 'Kurs', 'erzeugt ein Kurs Objekt');
INSERT INTO object_types (typ_id, type, container, title, description) VALUES (25, 'set', 'n', 'Set', 'Container f�r alles m�gliche');
INSERT INTO object_types (typ_id, type, container, title, description) VALUES (12, 'user', 'n', 'Benutzer', 'Ein normales Personenobjekt');
INSERT INTO object_types (typ_id, type, container, title, description) VALUES (22, 'usrf', 'y', 'User Folder', 'Folder der alle User enth�lt');
INSERT INTO object_types (typ_id, type, container, title, description) VALUES (23, 'rolf', 'y', 'Role Folder', 'Folder der Rollen enth�lt');
INSERT INTO object_types (typ_id, type, container, title, description) VALUES (24, 'objf', 'y', 'Type folder', 'Contains all object type definitions');
INSERT INTO object_types (typ_id, type, container, title, description) VALUES (26, 'type', 'n', 'Object type', 'Defines an object type');
# --------------------------------------------------------

#
# Tabellenstruktur f�r Tabelle `rbac_fa`
#

CREATE TABLE rbac_fa (
  rol_id int(11) NOT NULL default '0',
  parent int(11) NOT NULL default '0',
  assign enum('y','n') default NULL,
  PRIMARY KEY  (rol_id,parent)
) TYPE=MyISAM;

#
# Daten f�r Tabelle `rbac_fa`
#

INSERT INTO rbac_fa (rol_id, parent, assign) VALUES (2, 8, 'y');
INSERT INTO rbac_fa (rol_id, parent, assign) VALUES (3, 8, 'y');
INSERT INTO rbac_fa (rol_id, parent, assign) VALUES (3, 152, 'n');
INSERT INTO rbac_fa (rol_id, parent, assign) VALUES (4, 8, 'y');
INSERT INTO rbac_fa (rol_id, parent, assign) VALUES (4, 152, 'n');
INSERT INTO rbac_fa (rol_id, parent, assign) VALUES (5, 8, 'y');
INSERT INTO rbac_fa (rol_id, parent, assign) VALUES (5, 152, 'n');
# --------------------------------------------------------

#
# Tabellenstruktur f�r Tabelle `rbac_operations`
#

CREATE TABLE rbac_operations (
  ops_id int(11) NOT NULL auto_increment,
  operation char(100) NOT NULL default '',
  description char(255) default NULL,
  PRIMARY KEY  (ops_id)
) TYPE=MyISAM;

#
# Daten f�r Tabelle `rbac_operations`
#

INSERT INTO rbac_operations (ops_id, operation, description) VALUES (1, 'edit permission', 'edit permissions');
INSERT INTO rbac_operations (ops_id, operation, description) VALUES (2, 'visible', 'view object');
INSERT INTO rbac_operations (ops_id, operation, description) VALUES (3, 'read', 'access object');
INSERT INTO rbac_operations (ops_id, operation, description) VALUES (4, 'write', 'modify object');
INSERT INTO rbac_operations (ops_id, operation, description) VALUES (5, 'create', 'add object');
INSERT INTO rbac_operations (ops_id, operation, description) VALUES (6, 'delete', 'remove object');
INSERT INTO rbac_operations (ops_id, operation, description) VALUES (7, 'join', 'join group');
INSERT INTO rbac_operations (ops_id, operation, description) VALUES (8, 'leave', 'leave group');
# --------------------------------------------------------

#
# Tabellenstruktur f�r Tabelle `rbac_pa`
#

CREATE TABLE rbac_pa (
  rol_id int(11) NOT NULL default '0',
  ops_id text NOT NULL,
  obj_id int(11) NOT NULL default '0',
  set_id int(11) NOT NULL default '0',
  PRIMARY KEY  (rol_id,obj_id,set_id)
) TYPE=MyISAM;

#
# Daten f�r Tabelle `rbac_pa`
#

INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (2, 'a:5:{i:0;s:1:"1";i:1;s:1:"2";i:2;s:1:"3";i:3;s:1:"4";i:4;s:1:"5";}', 1, 0);
INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (3, 'a:2:{i:0;s:1:"2";i:1;s:1:"3";}', 1, 0);
INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (5, 'a:2:{i:0;s:1:"2";i:1;s:1:"3";}', 1, 0);
INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (4, 'a:2:{i:0;s:1:"2";i:1;s:1:"3";}', 1, 0);
INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (2, 'a:4:{i:0;s:1:"1";i:1;s:1:"2";i:2;s:1:"3";i:3;s:1:"4";}', 7, 9);
INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (3, 'a:2:{i:0;s:1:"2";i:1;s:1:"3";}', 7, 9);
INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (5, 'a:1:{i:0;s:1:"2";}', 7, 9);
INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (4, 'a:2:{i:0;s:1:"2";i:1;s:1:"3";}', 7, 9);
INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (2, 'a:5:{i:0;s:1:"1";i:1;s:1:"2";i:2;s:1:"3";i:3;s:1:"4";i:4;s:1:"5";}', 8, 9);
INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (4, 'a:2:{i:0;s:1:"2";i:1;s:1:"3";}', 150, 148);
INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (3, 'a:2:{i:0;s:1:"2";i:1;s:1:"3";}', 8, 9);
INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (4, 'a:2:{i:0;s:1:"2";i:1;s:1:"3";}', 8, 9);
INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (5, 'a:1:{i:0;s:1:"2";}', 8, 9);
INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (2, 'a:4:{i:0;s:1:"1";i:1;s:1:"2";i:2;s:1:"3";i:3;s:1:"4";}', 9, 1);
INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (3, 'a:2:{i:0;s:1:"1";i:1;s:1:"2";}', 9, 1);
INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (4, 'a:1:{i:0;s:1:"1";}', 9, 1);
INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (5, 'N;', 9, 1);
INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (2, 'a:2:{i:0;s:1:"1";i:1;s:1:"2";}', 10, 9);
INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (3, 'a:2:{i:0;s:1:"1";i:1;s:1:"2";}', 10, 9);
INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (4, 'a:1:{i:0;s:1:"1";}', 10, 9);
INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (5, 'N;', 10, 9);
INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (3, 'a:2:{i:0;s:1:"2";i:1;s:1:"3";}', 150, 148);
INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (5, 'a:2:{i:0;s:1:"2";i:1;s:1:"3";}', 149, 148);
INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (4, 'a:2:{i:0;s:1:"2";i:1;s:1:"3";}', 149, 148);
INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (3, 'a:2:{i:0;s:1:"2";i:1;s:1:"3";}', 149, 148);
INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (5, 'a:2:{i:0;s:1:"2";i:1;s:1:"3";}', 148, 1);
INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (2, 'a:5:{i:0;s:1:"1";i:1;s:1:"2";i:2;s:1:"3";i:3;s:1:"4";i:4;s:1:"5";}', 148, 1);
INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (4, 'a:2:{i:0;s:1:"2";i:1;s:1:"3";}', 148, 1);
INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (3, 'a:2:{i:0;s:1:"2";i:1;s:1:"3";}', 148, 1);
INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (2, 'a:5:{i:0;s:1:"1";i:1;s:1:"2";i:2;s:1:"3";i:3;s:1:"4";i:4;s:1:"5";}', 151, 150);
INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (3, 'a:3:{i:0;s:1:"2";i:1;s:1:"3";i:2;s:1:"4";}', 151, 150);
INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (4, 'a:2:{i:0;s:1:"2";i:1;s:1:"3";}', 151, 150);
INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (5, 'a:1:{i:0;s:1:"2";}', 151, 150);
INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (5, 'a:2:{i:0;s:1:"2";i:1;s:1:"3";}', 150, 148);
INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (2, 'a:5:{i:0;s:1:"1";i:1;s:1:"2";i:2;s:1:"3";i:3;s:1:"4";i:4;s:1:"5";}', 152, 150);
INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (3, 'a:2:{i:0;s:1:"2";i:1;s:1:"3";}', 152, 150);
INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (4, 'a:2:{i:0;s:1:"2";i:1;s:1:"3";}', 152, 150);
INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (5, 'a:1:{i:0;s:1:"2";}', 152, 150);
INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (2, 'a:5:{i:0;s:1:"1";i:1;s:1:"2";i:2;s:1:"3";i:3;s:1:"4";i:4;s:1:"5";}', 150, 148);
INSERT INTO rbac_pa (rol_id, ops_id, obj_id, set_id) VALUES (2, 'a:5:{i:0;s:1:"1";i:1;s:1:"2";i:2;s:1:"3";i:3;s:1:"4";i:4;s:1:"5";}', 149, 148);
# --------------------------------------------------------

#
# Tabellenstruktur f�r Tabelle `rbac_ta`
#

CREATE TABLE rbac_ta (
  typ_id smallint(6) NOT NULL default '0',
  ops_id smallint(6) NOT NULL default '0',
  PRIMARY KEY  (typ_id,ops_id)
) TYPE=MyISAM;

#
# Daten f�r Tabelle `rbac_ta`
#

INSERT INTO rbac_ta (typ_id, ops_id) VALUES (11, 2);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (11, 3);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (11, 4);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (11, 5);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (11, 6);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (12, 2);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (12, 3);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (12, 4);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (12, 5);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (12, 6);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (13, 1);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (13, 2);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (13, 3);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (13, 4);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (13, 5);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (13, 6);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (14, 1);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (14, 2);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (14, 3);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (14, 4);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (14, 5);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (14, 6);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (15, 1);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (15, 2);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (15, 3);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (15, 4);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (15, 5);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (15, 6);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (16, 1);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (16, 2);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (16, 3);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (16, 4);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (16, 5);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (16, 6);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (17, 1);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (17, 2);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (17, 3);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (17, 4);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (18, 1);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (18, 2);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (18, 3);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (18, 4);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (19, 1);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (19, 2);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (19, 3);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (20, 1);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (20, 2);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (20, 3);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (21, 1);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (21, 2);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (21, 3);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (21, 4);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (21, 5);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (22, 1);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (22, 2);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (22, 3);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (22, 4);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (23, 1);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (23, 2);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (23, 3);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (23, 4);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (23, 5);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (23, 6);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (24, 1);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (24, 2);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (24, 3);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (25, 1);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (25, 2);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (25, 3);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (26, 1);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (26, 2);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (26, 3);
INSERT INTO rbac_ta (typ_id, ops_id) VALUES (26, 4);
# --------------------------------------------------------

#
# Tabellenstruktur f�r Tabelle `rbac_templates`
#

CREATE TABLE rbac_templates (
  rol_id int(11) NOT NULL default '0',
  type char(5) NOT NULL default '',
  ops_id int(11) NOT NULL default '0',
  parent int(11) NOT NULL default '0'
) TYPE=MyISAM;

#
# Daten f�r Tabelle `rbac_templates`
#

INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (5, 'usrf', 2, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (4, 'usrf', 3, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (4, 'usrf', 2, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (3, 'usrf', 3, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'role', 5, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'role', 4, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'role', 3, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'role', 2, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'usrf', 4, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'usrf', 3, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'usrf', 2, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'usrf', 1, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'type', 4, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'type', 3, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'type', 2, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'type', 1, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'rolf', 5, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'rolf', 4, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (3, 'usrf', 2, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (3, 'rolf', 3, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (3, 'rolf', 2, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (3, 'le', 4, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (3, 'le', 3, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (3, 'le', 2, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (3, 'grp', 3, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (3, 'grp', 2, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (3, 'frm', 4, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (3, 'frm', 3, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (3, 'frm', 2, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (3, 'cat', 3, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (3, 'cat', 2, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (4, 'rolf', 3, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (4, 'rolf', 2, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (4, 'le', 3, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (4, 'le', 2, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (4, 'grp', 3, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (4, 'grp', 2, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (4, 'frm', 3, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (4, 'frm', 2, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (4, 'cat', 3, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (4, 'cat', 2, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (5, 'rolf', 2, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (5, 'le', 2, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (5, 'grp', 3, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (5, 'grp', 2, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (5, 'frm', 2, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (5, 'cat', 3, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (5, 'cat', 2, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (3, 'rolf', 2, 152);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (3, 'usrf', 2, 152);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (3, 'usrf', 1, 152);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (3, 'le', 2, 152);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (3, 'le', 1, 152);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (3, 'grp', 2, 152);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (3, 'grp', 1, 152);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (3, 'frm', 2, 152);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (3, 'frm', 1, 152);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (3, 'cat', 2, 152);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (3, 'cat', 1, 152);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (4, 'usrf', 2, 152);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (4, 'usrf', 1, 152);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (4, 'le', 1, 152);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (4, 'grp', 2, 152);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (4, 'grp', 1, 152);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (4, 'frm', 1, 152);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (4, 'cat', 2, 152);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (4, 'cat', 1, 152);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (5, 'usrf', 1, 152);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (5, 'le', 1, 152);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (5, 'grp', 1, 152);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (5, 'frm', 1, 152);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (5, 'cat', 1, 152);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'rolf', 3, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'rolf', 2, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'rolf', 1, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'objf', 2, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'objf', 1, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'le', 5, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'le', 4, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'le', 3, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'le', 2, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'le', 1, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'grp', 5, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'grp', 4, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'grp', 3, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'grp', 2, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'grp', 1, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'frm', 5, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'frm', 4, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'frm', 3, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'frm', 2, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'frm', 1, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'cat', 5, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'cat', 4, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'cat', 3, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'cat', 2, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'cat', 1, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'adm', 4, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'adm', 3, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'adm', 2, 8);
INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES (2, 'adm', 1, 8);
# --------------------------------------------------------

#
# Tabellenstruktur f�r Tabelle `rbac_ua`
#

CREATE TABLE rbac_ua (
  usr_id int(11) NOT NULL default '0',
  rol_id int(11) NOT NULL default '0'
) TYPE=MyISAM;

#
# Daten f�r Tabelle `rbac_ua`
#

INSERT INTO rbac_ua (usr_id, rol_id) VALUES (140, 4);
INSERT INTO rbac_ua (usr_id, rol_id) VALUES (141, 3);
INSERT INTO rbac_ua (usr_id, rol_id) VALUES (140, 5);
INSERT INTO rbac_ua (usr_id, rol_id) VALUES (141, 5);
INSERT INTO rbac_ua (usr_id, rol_id) VALUES (142, 5);
INSERT INTO rbac_ua (usr_id, rol_id) VALUES (6, 4);
INSERT INTO rbac_ua (usr_id, rol_id) VALUES (141, 4);
INSERT INTO rbac_ua (usr_id, rol_id) VALUES (6, 5);
INSERT INTO rbac_ua (usr_id, rol_id) VALUES (6, 2);
INSERT INTO rbac_ua (usr_id, rol_id) VALUES (6, 3);
# --------------------------------------------------------

#
# Tabellenstruktur f�r Tabelle `settings`
#

CREATE TABLE settings (
  keyword varchar(255) NOT NULL default '',
  value_str varchar(255) NOT NULL default '',
  value_int bigint(20) NOT NULL default '0',
  UNIQUE KEY keyword (keyword)
) TYPE=MyISAM;

#
# Daten f�r Tabelle `settings`
#

INSERT INTO settings (keyword, value_str, value_int) VALUES ('db_version', '', 1);
# --------------------------------------------------------

#
# Tabellenstruktur f�r Tabelle `tree`
#

CREATE TABLE tree (
  tree smallint(6) NOT NULL default '0',
  child int(11) NOT NULL default '0',
  parent int(11) default NULL,
  lft int(11) NOT NULL default '0',
  rgt int(11) NOT NULL default '0',
  depth int(11) NOT NULL default '0'
) TYPE=MyISAM;

#
# Daten f�r Tabelle `tree`
#

INSERT INTO tree (tree, child, parent, lft, rgt, depth) VALUES (1, 1, 0, 1, 20, 1);
INSERT INTO tree (tree, child, parent, lft, rgt, depth) VALUES (1, 7, 9, 13, 14, 3);
INSERT INTO tree (tree, child, parent, lft, rgt, depth) VALUES (1, 8, 9, 15, 16, 3);
INSERT INTO tree (tree, child, parent, lft, rgt, depth) VALUES (1, 9, 1, 12, 19, 2);
INSERT INTO tree (tree, child, parent, lft, rgt, depth) VALUES (1, 10, 9, 17, 18, 3);
INSERT INTO tree (tree, child, parent, lft, rgt, depth) VALUES (1, 150, 148, 3, 8, 3);
INSERT INTO tree (tree, child, parent, lft, rgt, depth) VALUES (1, 149, 148, 9, 10, 3);
INSERT INTO tree (tree, child, parent, lft, rgt, depth) VALUES (1, 148, 1, 2, 11, 2);
INSERT INTO tree (tree, child, parent, lft, rgt, depth) VALUES (1, 151, 150, 6, 7, 4);
INSERT INTO tree (tree, child, parent, lft, rgt, depth) VALUES (1, 152, 150, 4, 5, 4);
# --------------------------------------------------------

#
# Tabellenstruktur f�r Tabelle `user_data`
#

CREATE TABLE user_data (
  usr_id int(11) NOT NULL default '0',
  login char(11) NOT NULL default '',
  passwd char(32) NOT NULL default '',
  firstname char(20) NOT NULL default '',
  surname char(30) NOT NULL default '',
  title char(20) default NULL,
  gender enum('m','f') NOT NULL default 'm',
  email char(40) NOT NULL default 'here your email',
  last_login datetime NOT NULL default '0000-00-00 00:00:00',
  last_update datetime NOT NULL default '0000-00-00 00:00:00',
  create_date datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (usr_id),
  KEY login (login,passwd)
) TYPE=MyISAM;

#
# Daten f�r Tabelle `user_data`
#

INSERT INTO user_data (usr_id, login, passwd, firstname, surname, title, gender, email, last_login, last_update, create_date) VALUES (6, 'admin', '21232f297a57a5a743894a0e4a801fc3', 'ad', 'min2', 'fd', 'm', 'a@b', '2002-05-15 14:56:41', '2002-05-22 13:08:18', '0000-00-00 00:00:00');
INSERT INTO user_data (usr_id, login, passwd, firstname, surname, title, gender, email, last_login, last_update, create_date) VALUES (140, 'lerner', '3c1c7de8baffc419327b6439bba34217', 'Lerner', '', '', 'm', '', '0000-00-00 00:00:00', '2002-07-11 10:28:13', '2002-07-11 10:28:13');
INSERT INTO user_data (usr_id, login, passwd, firstname, surname, title, gender, email, last_login, last_update, create_date) VALUES (141, 'autor', '7a25cefdc710b155828e91df70fe7478', 'Autor', '', '', 'm', '', '0000-00-00 00:00:00', '2002-07-11 10:28:34', '2002-07-11 10:28:34');
INSERT INTO user_data (usr_id, login, passwd, firstname, surname, title, gender, email, last_login, last_update, create_date) VALUES (142, 'gast', 'd4061b1486fe2da19dd578e8d970f7eb', 'Gast', '', '', 'm', '', '0000-00-00 00:00:00', '2002-07-11 10:28:54', '2002-07-11 10:28:54');
# --------------------------------------------------------

#
# Tabellenstruktur f�r Tabelle `user_session`
#

CREATE TABLE user_session (
  sesskey varchar(32) NOT NULL default '',
  expiry int(11) NOT NULL default '0',
  value text NOT NULL,
  PRIMARY KEY  (sesskey)
) TYPE=MyISAM;

#
# Daten f�r Tabelle `user_session`
#


