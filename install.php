<?php

global $db;

$autoincrement = (($amp_conf["AMPDBENGINE"] == "sqlite") || ($amp_conf["AMPDBENGINE"] == "sqlite3")) ? "AUTOINCREMENT":"AUTO_INCREMENT";

$sql = "CREATE TABLE IF NOT EXISTS `sccp_mac` (
  `id` int(10) unsigned NOT NULL $autoincrement,
  `mac` varchar(20) NOT NULL,
  `ext` int(10) unsigned NOT NULL,
  `type` varchar(10) NOT NULL,
  `speeds` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
)";

$check = $db->query($sql);
if(DB::IsError($check)) {
	die_freepbx("Can not create sccp_mac table\n");
}

$sql = "CREATE TABLE IF NOT EXISTS `sccpdevice` (
  `type` varchar(45) default NULL,
  `autologin` varchar(45) default NULL,
  `description` varchar(45) default NULL,
  `tzoffset` varchar(45) default NULL,
  `transfer` varchar(45) default 'on',
  `speeddial` varchar(75) default NULL,
  `cfwdall` varchar(45) default 'on',
  `cfwdbusy` varchar(45) default 'on',
  `dtmfmode` varchar(45) default 'inbound',
  `imageversion` varchar(45) default NULL,
  `deny` varchar(45) default NULL,
  `permit` varchar(45) default '0.0.0.0',
  `dnd` varchar(45) default 'on',
  `setvar` varchar(100) default NULL,
  `serviceURL` varchar(254) default NULL,
  `name` varchar(15) NOT NULL,
  PRIMARY KEY  (`name`)
)";

$check = $db->query($sql);
if(DB::IsError($check)) {
	die_freepbx("Can not create sccpdevice table\n");
}

$sql = "CREATE TABLE IF NOT EXISTS `sccpline` (
  `id` varchar(45) default NULL,
  `pin` varchar(45) default '1234',
  `label` varchar(45) default NULL,
  `description` varchar(45) default NULL,
  `context` varchar(45) default 'from-internal',
  `incominglimit` varchar(45) default '2',
  `transfer` varchar(45) default 'on',
  `mailbox` varchar(45) default NULL,
  `vmnum` varchar(45) default '*97',
  `cid_name` varchar(45) default NULL,
  `cid_num` varchar(45) default NULL,
  `trnsfvm` varchar(45) default '1',
  `secondary_dialtone_digits` varchar(45) default NULL,
  `secondary_dialtone_tone` varchar(45) default NULL,
  `musicclass` varchar(45) default 'default',
  `language` varchar(45) default 'se',
  `accountcode` varchar(45) default NULL,
  `rtptos` varchar(45) default '184',
  `echocancel` varchar(45) default 'on',
  `silencesuppression` varchar(45) default 'on',
  `callgroup` varchar(45) default NULL,
  `pickupgroup` varchar(45) default NULL,
  `amaflags` varchar(45) default NULL,
  `setvar` varchar(50) default NULL,
  `name` varchar(45) NOT NULL,
  PRIMARY KEY  (`name`)
)";

$check = $db->query($sql);
if(DB::IsError($check)) {
	die_freepbx("Can not create sccpline table\n");
}

?>
