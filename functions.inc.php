<?php
/**
 * @copyright Niklas Larsson, Infracom AB
 * @license GPL2
 *
 * chan_sccp stuff
 *
 * @todo add speeddails
 * @todo hooks into extensions
 *
 */


function chan_sccp_destinations() {
	// return an associative array with destination and description

	return;
}

function chan_sccp_getdest($exten) {

	return;
}

function chan_sccp_getdestinfo($dest) {

	return;
}

function chan_sccp_get_config($engine) {
	global $db;

	$sql = "SELECT d.id, d.description, m.mac, m.type, m.speeds
					FROM devices d
					LEFT JOIN sccp_mac m ON m.ext = d.id
					WHERE LEFT(d.dial, 4) = 'SCCP'";

	$results = $db->getAll($sql, DB_FETCHMODE_ASSOC);
	if(DB::IsError($results)) {
		die_freepbx($results->getMessage()."<br><br>Error selecting from sccp_mac");
	}

	foreach ($results as $row){
		chan_sccp_create_line($row['id'], $row['description']);
		chan_sccp_create_device($row['mac'], $row['id'], $row['type'], $row['description'], $row['speeds']);
		chan_sccp_create_tftp_SEP($row['mac']);
	}
}

/**  Get a list of all phones
 */
function chan_sccp_list() {
	global $db;
	$sql = "SELECT id, mac, ext, type FROM sccp_mac ORDER BY mac ";
	$results = $db->getAll($sql, DB_FETCHMODE_ASSOC);
	if(DB::IsError($results)) {
		die_freepbx($results->getMessage()."<br><br>Error selecting from sccp_mac");
	}
	return $results;
}

function chan_sccp_get($chan_sccp_id) {
	global $db;
	$sql = "SELECT id, mac, ext, type, speeds FROM sccp_mac WHERE id = " . (int) $chan_sccp_id;
	$row = $db->getRow($sql, DB_FETCHMODE_ASSOC);
	if(DB::IsError($row)) {
		die_freepbx($row->getMessage()."<br><br>Error selecting row from jabber");
	}

	return $row;
}

function chan_sccp_add($mac, $phone_type, $ext, $speeds) {
	global $db;

	$ext = (int) $ext;
	$phone_type = $db->escapeSimple($phone_type);
	$mac = $db->escapeSimple(strtoupper($mac));
	$speeds = $db->escapeSimple(strtoupper($speeds));

	$sql = "INSERT INTO sccp_mac
					(mac, type, ext, speeds)
					VALUES ('$mac', '$phone_type', $ext, '$speeds')";

	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die_freepbx($result->getMessage().$sql);
	}
}

function chan_sccp_delete($chan_sccp_id) {
	global $db;

	$chan_sccp_id = (int) $chan_sccp_id;

	$sql = "DELETE FROM sccp_mac
					WHERE id = $chan_sccp_id";

	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die_freepbx($result->getMessage().$sql);
	}
}

function chan_sccp_edit($chan_sccp_id, $mac, $phone_type, $ext, $speeds) {
	global $db;

	$chan_sccp_id = (int) $chan_sccp_id;
	$ext = (int) $ext;
	$phone_type = $db->escapeSimple($phone_type);
	$mac = $db->escapeSimple(strtoupper($mac));
	$speeds = $db->escapeSimple(strtoupper($speeds));

	$sql = "UPDATE sccp_mac
					SET mac = '$mac', type = '$phone_type', ext = $ext, speeds = '$speeds'
					WHERE id = $chan_sccp_id";

	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die_freepbx($result->getMessage().$sql);
	}
}
/*
function chan_sccp_configpageinit($pagename) {
	global $currentcomponent;

	// Vi får fixa detta senare
	return true;

	$action = isset($_REQUEST['action'])?$_REQUEST['action']:null;
	$extdisplay = isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:null;
	$extension = isset($_REQUEST['extension'])?$_REQUEST['extension']:null;
	$tech_hardware = isset($_REQUEST['tech_hardware'])?$_REQUEST['tech_hardware']:null;

	// We only want to hook 'users' or 'extensions' pages.
	if ($pagename != 'users' && $pagename != 'extensions')
		return true;
	// On a 'new' user, 'tech_hardware' is set, and there's no extension. Hook into the page.
	if ($tech_hardware != null || $pagename == 'users') {
		chan_sccp_applyhooks();
		$currentcomponent->addprocessfunc('chan_sccp_configprocess', 8);
	} elseif ($action=="add") {
		// We don't need to display anything on an 'add', but we do need to handle returned data.
		$currentcomponent->addprocessfunc('chan_sccp_configprocess', 8);
	} elseif ($extdisplay != '') {
		// We're now viewing an extension, so we need to display _and_ process.
		chan_sccp_applyhooks();
		$currentcomponent->addprocessfunc('chan_sccp_configprocess', 8);
	}
}

function chan_sccp_applyhooks() {
	global $currentcomponent;

	// Add the 'process' function - this gets called when the page is loaded, to hook into
	// displaying stuff on the page.
	$currentcomponent->addguifunc('chan_sccp_configpageload');
}

// This is called before the page is actually displayed, so we can use addguielem().
function chan_sccp_configpageload() {
	global $currentcomponent;

	// Init vars from $_REQUEST[]
	$action = isset($_REQUEST['action'])?$_REQUEST['action']:null;
	$extdisplay = isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:null;

	// Don't display this stuff it it's on a 'This xtn has been deleted' page.
	if ($action != 'del') {
		$chan_sccp_rcpt = chan_sccp_user_get($extdisplay);

		$section = _('SCCP Phone');
		$msgInvalidjabber = _('Please enter a valid jabber Code');
		$currentcomponent->addguielem($section, new gui_textbox('chan_sccp_rcpt', $chan_sccp_rcpt, _('Jabber ID'), _('The jabber code for this user. This will result in messages such as voiclangcode prompts to use the selected jabber if installed.'), "", $msgInvalidjabber, true));
	}
}

function chan_sccp_configprocess() {
	//create vars from the request
	$action = isset($_REQUEST['action'])?$_REQUEST['action']:null;
	$ext = isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:null;
	$extn = isset($_REQUEST['extension'])?$_REQUEST['extension']:null;
	$chan_sccp_rcpt = isset($_REQUEST['chan_sccp_rcpt'])?$_REQUEST['chan_sccp_rcpt']:null;

	if ($ext==='') {
		$extdisplay = $extn;
	} else {
		$extdisplay = $ext;
	}
	if ($action == "add" || $action == "edit") {
		if (!isset($GLOBALS['abort']) || $GLOBALS['abort'] !== true) {
			chan_sccp_user_update($extdisplay, $chan_sccp_rcpt);
		}
	} elseif ($action == "del") {
		chan_sccp_user_del($extdisplay);
	}
}

function chan_sccp_user_get($xtn) {
	global $astman;

	// Retrieve the jabber configuraiton from this user from ASTDB
	$chan_sccp_rcpt = $astman->database_get("AMPUSER",$xtn."/chan_sccp_rcpt");

	return $chan_sccp_rcpt;
}

function chan_sccp_user_update($ext, $chan_sccp_rcpt) {
	global $astman;

	if ($ena === 'disabled') {
		chan_sccp_user_del($ext);
	} else {
		// Update the settings in ASTDB
		$astman->database_put("AMPUSER",$ext."/chan_sccp_rcpt",$chan_sccp_rcpt);
	}
}

function chan_sccp_user_del($ext) {
	global $astman;

	// Clean up the tree when the user is deleted
	$astman->database_deltree("AMPUSER/$ext/chan_sccp_rcpt");
}

function chan_sccp_check_destinations($dest=true) {
	global $active_modules;

	$destlist = array();
	if (is_array($dest) && empty($dest)) {
		return $destlist;
	}
	$sql = "SELECT chan_sccp_id, dest, description FROM jabber ";
	if ($dest !== true) {
		$sql .= "WHERE dest in ('".implode("','",$dest)."')";
	}
	$results = sql($sql,"getAll",DB_FETCHMODE_ASSOC);

	$type = isset($active_modules['jabber']['type'])?$active_modules['jabber']['type']:'setup';

	foreach ($results as $result) {
		$thisdest = $result['dest'];
		$thisid   = $result['chan_sccp_id'];
		$destlist[] = array(
			'dest' => $thisdest,
			'description' => 'jabber Change: '.$result['description'],
			'edit_url' => 'config.php?display=jabber&type='.$type.'&extdisplay='.urlencode($thisid),
		);
	}
	return $destlist;
}
*/
function chan_sccp_create_line($ext, $name){
	global $db;

	$ext = (int) $ext;
	$name = $db->escapeSimple($name);

	$sql = "SELECT COUNT(*)
					FROM sccpline
					WHERE name = '$ext'";

	$res = $db->getone($sql);
	if(DB::IsError($res)) {
		die_freepbx($res->getMessage().$sql);
	}

	if ($res > 0){
		$sql = "UPDATE sccpline
						SET id = $ext, label = $ext, description = '$name', mailbox = $ext, cid_name = '$name', cid_num = '$ext'
						WHERE name = '$ext'";
	}else{
		$sql = "INSERT INTO sccpline
						(id, label, description, mailbox, cid_name, cid_num, name)
						VALUES ($ext, $ext, '$name', $ext, '$name', $ext, $ext)";
	}

	$res = $db->query($sql);
	if(DB::IsError($res)) {
		die_freepbx($res->getMessage().$sql);
	}
}

function make_speeds($speeds, $type = '7960'){
	global $db;
	$str = '';

	$speeds_arr = explode(';', $speeds);
	foreach ($speeds_arr as $speed){
		$speed = $db->escapeSimple($speed);

		$sql = "SELECT d.`description`
						FROM devices d
						WHERE d.`id` = '$speed'";

		$res = $db->getone($sql);
		if(DB::IsError($res)) {
			die_freepbx($res->getMessage().$sql);
		}

		if ($res != ''){
			$res = str_replace(array(',', ';'), '', $res);
			$str .= "$speed,$res";
			if ($type != '7905' && $type != '7912')
				$str .= ",$speed@from-internal";
		}else{
			$str .= "$speed,$speed";
		}


		$str .= ';';
	}
	return $str;
}

function chan_sccp_create_device($mac, $ext, $type, $name, $speeds){
	global $db;

	$sep = 'SEP' . $db->escapeSimple(strtoupper($mac));

	$ext = (int) $ext;
	$type = $db->escapeSimple($type);
	$name = $db->escapeSimple($name);
	$speeds = make_speeds($speeds, $type);

	$sql = "SELECT COUNT(*)
					FROM sccpdevice
					WHERE name = '$sep'";

	$res = $db->getone($sql);
	if(DB::IsError($res)) {
		die_freepbx($res->getMessage().$sql);
	}

	if ($res > 0){
		$sql = "UPDATE sccpdevice
						SET type = '$type', autologin = $ext, description = '$name', speeddial = '$speeds'
						WHERE name = '$sep'";
	}else{
		$sql = "INSERT INTO sccpdevice
						(type, autologin, description, speeddial, name)
						VALUES ('$type', $ext, '$name', '$speeds', '$sep')";
	}

	$res = $db->query($sql);
	if(DB::IsError($res)) {
		die_freepbx($res->getMessage().$sql);
	}

	return $sep;
}

function chan_sccp_create_tftp_SEP($mac, $cm_ip = ''){
	if ($cm_ip == ''){
		$cm_ip = exec('hostname -i');
	}

	$template = file_get_contents($_SERVER['PWD'] . '/modules/chan_sccp/SEPXML.txt');

	$template = str_replace('{$cm_ip}', $cm_ip, $template);

	$filename = '/tftpboot/SEP' . $mac . '.cnf.xml';
	file_put_contents($filename, $template);

	return true;
}
?>
