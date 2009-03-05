<?php
/** chan_sccp helper Module for FreePBX 2.5
 * Copyright 2008 Niklas Larsson, Infracom AB
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : 'setup';
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] :  '';
if (isset($_REQUEST['delete'])) $action = 'delete';

$chan_sccp_id = isset($_REQUEST['chan_sccp_id']) ? $_REQUEST['chan_sccp_id'] :  false;
$mac = isset($_REQUEST['mac']) ? $_REQUEST['mac'] :  '';
$phone_type = isset($_REQUEST['phone_type']) ? $_REQUEST['phone_type'] :  '';
$ext = isset($_REQUEST['ext']) ? $_REQUEST['ext'] :  '';
$speeds = isset($_REQUEST['speeds']) ? $_REQUEST['speeds'] :  '';

if (isset($_REQUEST['goto0']) && $_REQUEST['goto0']) {
	$dest = $_REQUEST[ $_REQUEST['goto0'].'0' ];
}

switch ($action) {
	case 'add':
		chan_sccp_add($mac, $phone_type, $ext, $speeds);
		needreload();
		redirect_standard();
	break;
	case 'edit':
		chan_sccp_edit($chan_sccp_id, $mac, $phone_type, $ext, $speeds);
		needreload();
		redirect_standard('extdisplay');
	break;
	case 'delete':
		chan_sccp_delete($chan_sccp_id);
		needreload();
		redirect_standard();
	break;
}

?>
</div>

<div class="rnav"><ul>
<?php

echo '<li><a href="config.php?display=chan_sccp&amp;type='.$type.'">'._('Add Phone').'</a></li>';

foreach (chan_sccp_list() as $row) {
	echo '<li><a href="config.php?display=chan_sccp&amp;type='.$type.'&amp;extdisplay='.$row['id'].'" class="">'.$row['mac'] . ' (' .$row['type'] . ') - ' .$row['ext'].'</a></li>';
}

?>
</ul></div>

<div class="content">

<?php

if ($extdisplay) {
	// load
	$row = chan_sccp_get($extdisplay);

	$mac = $row['mac'];
	$ext = $row['ext'];
	$phone_type = $row['type'];
	$speeds = $row['speeds'];

	echo "<h2>"._("Edit: ")."$mac ($phone_type)"."</h2>";
} else {
	echo "<h2>"._("Add Phone")."</h2>";
}

$helptext = _("Connecting a phone (mac) to a FreePBX extension. When you klick on Apply conf... the xml files are created and saved in /tftpboot (make chmod 777 /tftpboot) and the chan_sccp realtime mysql is updated");
echo $helptext;
?>

<form name="edit_chan_sccp" action="<?php  $_SERVER['PHP_SELF'] ?>" method="post" onsubmit="return check_chan_sccp(edit_chan_sccp);">
	<input type="hidden" name="extdisplay" value="<?php echo $extdisplay; ?>">
	<input type="hidden" name="chan_sccp_id" value="<?php echo $extdisplay; ?>">
	<input type="hidden" name="action" value="<?php echo ($extdisplay ? 'edit' : 'add'); ?>">
	<table>
	<tr><td colspan="2"><h5><?php  echo ($extdisplay ? _("Edit Phone") : _("Add Phone")) ?><hr></h5></td></tr>
	<tr>
		<td><a href="#" class="info"><?php echo _("MAC")?>:<span><?php echo _("The MAC address of the phone")?></span></a></td>
		<td><input size="10" type="text" name="mac" value="<?php  echo $mac; ?>"></td>
	</tr>
	<tr>
		<td><a href="#" class="info"><?php echo _("Type")?>:<span><?php echo _("The type of phone: 7905, 7940, 7960...")?></span></a></td>
		<td><input size="6" type="text" name="phone_type" value="<?php  echo $phone_type; ?>"></td>
	</tr>
	<tr>
		<td><a href="#" class="info"><?php echo _("FreePBX extension")?>:<span><?php echo _("Extension")?></span></a></td>
		<td><input size="6" type="text" name="ext" value="<?php echo $ext; ?>" /></td> </tr>
	<tr>
	<tr>
		<td><a href="#" class="info"><?php echo _("Speedials")?>:<span><?php echo _("Enter the extensions numbers or external numbers, seperated by ; To add Your own text use: number,text;")?></span></a></td>
		<td><input size="60" type="text" name="speeds" value="<?php echo $speeds; ?>" /></td> </tr>
	<tr>
		<td colspan="2"><br><input name="Submit" type="submit" value="<?php echo _("Submit Changes")?>">
			<?php if ($extdisplay) { echo '&nbsp;<input name="delete" type="submit" value="'._("Delete").'">'; } ?>
		</td>
	</tr>

</table>
</form>

<script language="javascript">

function check_chan_sccp(theForm) {
	var msgInvalidDescription = "<?php echo _('Invalid description specified'); ?>";

	// set up the Destination stuff
	setDestinations(theForm, '_post_dest');

	// form validation
	defaultEmptyOK = false;
	if (isEmpty(theForm.description.value))
		return warnInvalid(theForm.description, msgInvalidDescription);

	if (!validateDestinations(theForm, 1, true))
		return false;

	return true;
}

</script>
