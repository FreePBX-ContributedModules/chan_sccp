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
		die_freepbx($row->getMessage()."<br><br>Error selecting row from sccp_mac");
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
	
	$sql = "SELECT mac, ext
					FROM sccp_mac
					WHERE id = $chan_sccp_id";
					
	$row = $db->getRow($sql, DB_FETCHMODE_ASSOC);
	if(DB::IsError($row)) {
		die_freepbx($row->getMessage()."<br><br>Error selecting row from sccp_mac");
	}
	
	$sql = "DELETE FROM sccpline
					WHERE id = " . (int) $row['ext'];
	
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die_freepbx($result->getMessage().$sql);
	}

	$sql = "DELETE FROM sccpdevice
					WHERE name = 'SEP{$row['mac']}'";
	
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die_freepbx($result->getMessage().$sql);
	}

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
	$speeds = $db->escapeSimple($speeds);

	$sql = "UPDATE sccp_mac
					SET mac = '$mac', type = '$phone_type', ext = $ext, speeds = '$speeds'
					WHERE id = $chan_sccp_id";

	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die_freepbx($result->getMessage().$sql);
	}
}
/*
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
		$speed_arr = explode(',', $speed);
		$speed = $speed_arr[0];
		$desc = $speed_arr[1];

		$speed = $db->escapeSimple($speed);

		if (!$desc){
			$sql = "SELECT d.`description`
							FROM devices d
							WHERE d.`id` = '$speed'";

			$res = $db->getone($sql);
			if(DB::IsError($res)) {
				die_freepbx($res->getMessage().$sql);
			}

			$desc = $res;
		}

		if ($desc != ''){
			$desc = str_replace(array(',', ';'), '', $desc);
			$str .= "$speed,$desc";
			if ($type != '7905' && $type != '7912')
				$str .= ",$speed@from-internal";
		}else{
			$str .= "$speed,$speed";
		}


		$str .= ';';
		$desc = '';
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
