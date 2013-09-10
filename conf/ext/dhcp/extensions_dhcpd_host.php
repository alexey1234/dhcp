<?php
/* 
extensions_dhcpd_host.php
*/
require("auth.inc");
require("guiconfig.inc");
include_once ("ext/dhcp/function.inc");
if ( ! is_array ($dhcpd_conf['hosts'])) { $dhcpd_conf['hosts'] = array();}
$dhcpd_conf = read_dhcpconf($config['dhcplight']['homefolder']."conf/dhcpd.conf");
if ($_GET) { 
	if (isset($_GET['act']) && ($_GET['act'] === "edit")) { 
		$pconfig['number'] = $_GET['number'];
		$pconfig['host'] = $dhcpd_conf['hosts'][$_GET['number']]['host'];
		$pconfig['mac'] = $dhcpd_conf['hosts'][$_GET['number']]['mac'];
		$pconfig['fixed-address'] = $dhcpd_conf['hosts'][$_GET['number']]['fixed-address'];
		$pconfig['allowdupl'] = $dhcpd_conf['hosts'][$_GET['number']]['allowdupl'];
		$pconfig['allowboot'] = $dhcpd_conf['hosts'][$_GET['number']]['allowboot'];
		$pconfig['next-server'] = $dhcpd_conf['hosts'][$_GET['number']]['next-server'];
		$pconfig['filename'] = $dhcpd_conf['hosts'][$_GET['number']]['filename'];
		$pconfig['root-path'] = $dhcpd_conf['hosts'][$_GET['number']]['root-path'];
		goto out;}
	elseif (isset($_GET['act']) && ($_GET['act'] === "new")) {
		$pconfig['number'] = count ($dhcpd_conf['hosts']);
		$pconfig['host'] = "";
		$pconfig['mac'] = "";
		$pconfig['fixed-address'] = "";
		$pconfig['allowdupl'] = "";
		$pconfig['allowboot'] = "";
		$pconfig['next-server'] = "";
		$pconfig['filename'] = "";
		$pconfig['root-path'] = "";
		goto out;}
	elseif (isset($_GET['act']) && ($_GET['act'] === "del")) {
		$number = $_GET['number'];
		unset ($dhcpd_conf['hosts'][$number]);
		$dhcpd_conf['hosts'] = array_values ($dhcpd_conf['hosts']);
		write_dhcpconf($dhcpd_conf, $config['dhcplight']['homefolder']."conf/dhcpd.conf");
		header("Location: extensions_dhcpd_server.php");
		}
	else {}	
	}
	


if ($_POST) { 
	$pconfig = $_POST;
	unset($input_errors);
	if (isset($_POST['Submit']) && ($_POST['Submit']=== "Cancel" )) { 	header("Location: extensions_dhcpd_server.php"); }
	if (isset($_POST['Submit']) && ($_POST['Submit'] === "Save")) { 
	// input validation
	
	$reqdfields = explode(" ", "host mac fixed-address");
	$reqdfieldsn = array(gettext("Hostname"), gettext("MAC"), gettext("fixed-address"));
	$reqdfieldst = explode(" ", "hostname macaddr ipaddr");
	if (isset($_POST['allowboot']) && $_POST['allowboot']) {
		$reqdfields = explode(" ", "next-server filename root-path");
		$reqdfieldsn = array(gettext("TFTP server"), gettext("Filename"), gettext("Root path"));
		$reqdfieldst = explode(" ", "ipaddr string string");
	}

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);
	do_input_validation_type($_POST, $reqdfields, $reqdfieldsn, $reqdfieldst, $input_errors);

	if (empty($input_errors)) {

// define  host number
$number = $_POST['number'];
$dhcpd_conf['hosts'][$number]['host'] = $_POST['host'];
$dhcpd_conf['hosts'][$number]['mac'] = $_POST['mac'];
$dhcpd_conf['hosts'][$number]['fixed-address'] = $_POST['fixed-address'];
if (isset($_POST['allowdupl'])){ $dhcpd_conf['hosts'][$number]['allowdupl'] = "allow duplicates";} else { unset($dhcpd_conf['hosts'][$number]['allowdupl']);}
if (isset($_POST['allowboot'])){ 
	$dhcpd_conf['hosts'][$number]['allowboot'] = "allow boot";
	if ( ! empty ($_POST['next-server'])) { $dhcpd_conf['hosts'][$number]['next-server'] = $_POST['next-server']; } else {}
	if ( ! empty ($_POST['filename'])) { $dhcpd_conf['hosts'][$number]['filename'] = $_POST['filename']; } else {}	
	if ( ! empty ($_POST['root-path'])) { $dhcpd_conf['hosts'][$number]['root-path'] = $_POST['root-path']; } else {}	
	} 
	else {
	unset ( $dhcpd_conf['hosts'][$number]['next-server']);
	unset ( $dhcpd_conf['hosts'][$number]['filename']);
	unset ( $dhcpd_conf['hosts'][$number]['allowboot']);
	unset ( $dhcpd_conf['hosts'][$number]['root-path']);
	
	}
write_dhcpconf($dhcpd_conf, $config['dhcplight']['homefolder']."conf/dhcpd.conf");
mwexec ("touch /var/run/dhcpd.reload");
}
else goto out;
}

header("Location: extensions_dhcpd_server.php");}
out:
$pgtitle = array(gettext("Extensions"),gettext("DHCP_server|host"));
include("fbegin.inc");?>
<script type="text/javascript">
<!--
$(document).ready(function () {
<?php If ($pconfig['allowboot'] === "booting"):?>
	showElementById('next-server_tr','show');
	showElementById('filename_tr','show');
	showElementById('root-path_tr','show');
<?php else:?>
	showElementById('next-server_tr','hide');
	showElementById('filename_tr','hide');
	showElementById('root-path_tr','hide');
<?php endif ?>
});

function boot_change() {
	switch (document.iform.allowboot.checked) {
		case false:
			showElementById('next-server_tr','hide');
			showElementById('filename_tr','hide');
			showElementById('root-path_tr','hide');
			break;
		case true:
			showElementById('next-server_tr','show');
			showElementById('filename_tr','show');
			showElementById('root-path_tr','show');
			break;
	}
}

// -->
</script>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
	<form action="extensions_dhcpd_host.php" method="post" name="iform" id="iform">
		<td class="tabcont">
		<?php if (!empty($input_errors)) print_input_errors($input_errors); ?>
			<table width="100%" border="0" cellpadding="5" cellspacing="0">
				<tr>
					
					<?php html_titleline(gettext("Dynamic Host Configuration Protocol - host"));
					html_inputbox("host", gettext("Hostname"), $pconfig['host'], gettext("Define host"), false, 20,false);
					html_inputbox("mac", gettext("MAC"), $pconfig['mac'], gettext("Define mac for host"), false, 30,false);
					html_inputbox("fixed-address", gettext("IP adress"), $pconfig['fixed-address'], gettext("Define pseudo static IP"), false, 30,false);
					html_checkbox("allowdupl", gettext("Allow duplicates"), !empty($pconfig['allowdupl']) ? true : false, gettext("Use it when one computer has more than one operating system installed on it"), "", false);
					html_checkbox("allowboot", gettext("Allow booting"), !empty($pconfig['allowboot']) ? true : false, gettext("If this DHCP server is the official DHCP server for the local network, the authoritative directive should be checked."), "", false, "boot_change()");?>
					<?php html_inputbox("next-server", gettext("TFTP server"), $pconfig['next-server'], gettext("Choise TFTP server"), false, 25, false);?>
					<?php html_inputbox("filename", gettext("filename"), $pconfig['filename'], gettext("Use the specified file for boot."), false, 25, false);?>
					<?php html_inputbox("root-path", gettext("root path"), $pconfig['root-path'], gettext("Define iSCSI root path"), false, 50, false);?>
					
				</tr>	
				
				<tr>
				</tr>
				<tr><td>
					<div id="submit">
					<input name="Submit" type="submit" class="formbtn" value="<?=gettext("Save");?>" />
					<input name="number" type="hidden" value="<?=$pconfig['number'];?>" />
					<input name="Submit" type="submit" class="formbtn" value="<?=gettext("Cancel");?>" />
					
					</div>
				    </td>
				</tr>
			
			</table>
		</td>
		<?php include("formend.inc");?>
		</form>
	</tr>

	
</table>

<script type="text/javascript">
<!--
boot_change();
//-->
</script>
<?php include("fend.inc"); ?>