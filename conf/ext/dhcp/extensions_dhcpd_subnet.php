<?php
/* 
extensions_dhcpd_subnet.php
*/
require("auth.inc");
require("guiconfig.inc");
include_once ("ext/dhcp/function.inc");
$dhcpd_conf = read_dhcpconf($config['dhcplight']['homefolder']."conf/dhcpd.conf");
if ($_GET) { 
	if (isset($_GET['act']) && ($_GET['act'] === "edit")) { 
		$pconfig['number'] = $_GET['number'];
		$pconfig['subnet'] = $dhcpd_conf['subnets'][$_GET['number']]['subnet'];
		$pconfig['netmask'] = mask2cidr($dhcpd_conf['subnets'][$_GET['number']]['netmask']);
		$pconfig['startadr'] = $dhcpd_conf['subnets'][$_GET['number']]['startadr'];
		$pconfig['endadr'] = $dhcpd_conf['subnets'][$_GET['number']]['endadr'];
		$pconfig['routers'] = $dhcpd_conf['subnets'][$_GET['number']]['option']['routers'];
		$pconfig['domain-name-servers'] = $dhcpd_conf['subnets'][$_GET['number']]['option']['domain-name-servers'];
		$pconfig['broadcast-address'] = $dhcpd_conf['subnets'][$_GET['number']]['option']['broadcast-address'];
		$pconfig['ntp-servers'] = $dhcpd_conf['subnets'][$_GET['number']]['option']['ntp-servers'];
		/* $pconfig['netbios-name-servers'] = $dhcpd_conf['subnets'][$_GET['number']]['option']['netbios-name-servers'];*/
		$r_option = $dhcpd_conf['subnets'][$_GET['number']]['option'];
		unset ($r_option['domain-name-servers']);
		unset ($r_option['broadcast-address']);
		unset ($r_option['routers']);
		unset ($r_option['ntp-servers']);
		$pconfig['auxparam'] ="";
		$rk_config = array_keys($r_option);
		$rv_config = array_values($r_option);
		for ($i=0; $i < count ($r_option);) { $pconfig['auxparam'] = $pconfig['auxparam'].$rk_config[$i]." ".$rv_config[$i]."\n"; ++$i;}
		//$pconfig['auxparam'] = implode("\n", $rn_option);
		goto out;}
	elseif (isset($_GET['act']) && ($_GET['act'] === "new")) {
		$pconfig['number'] = count ($dhcpd_conf['subnets']);
		$if = $config['interfaces']['lan']['ipaddr']."/".$config['interfaces']['lan']['subnet'];
$data = array();
exec("/usr/local/bin/sipcalc --cidr-addr {$if}", $data);
preg_match("/.+Network address\s+- (\d+)\.(\d+)\.(\d+)\.(\d+)/", implode($data), $out[1]);
preg_match("/Network mask \(bits\)	- (\d+).+/", implode($data), $out[2]);
preg_match("/.+Broadcast address\s+- (\d+)\.(\d+)\.(\d+)\.(\d+)/", implode($data), $out[3]);
		
		$lansubnetnas = get_subnet_lan();
		$pconfig['subnet'] = $out[1][1].".".$out[1][2].".".$out[1][3].".".$out[1][4];
		
		$pconfig['netmask'] = $out[2][1];
		$pconfig['startadr'] = "";
		$pconfig['endadr'] = "";
		$pconfig['routers'] = "";
		$pconfig['domain-name-servers'] = "";
		$pconfig['broadcast-address'] = $out[3][1].".".$out[3][2].".".$out[3][3].".".$out[3][4];;
		$pconfig['ntp-servers'] = $config['system']['ntp']['timeservers'];
		/*$pconfig['netbios-name-servers'] = "";*/
		$pconfig['auxparam'] = "";
		goto out;}
	elseif (isset($_GET['act']) && ($_GET['act'] === "del")) {
		$number = $_GET['number'];
		unset ($dhcpd_conf['subnets'][$number]);
		$dhcpd_conf['subnets'] = array_values ($dhcpd_conf['subnets']);
		write_dhcpconf($dhcpd_conf, $config['dhcplight']['homefolder']."conf/dhcpd.conf");
		header("Location: extensions_dhcpd_server.php");
		}
	else {}	
	}




If ($_POST) {
$pconfig = $_POST;
	unset($input_errors);
	if (isset($_POST['Submit']) && ($_POST['Submit']=== "Cancel" )) { 	header("Location: extensions_dhcpd_server.php"); }
	if (isset($_POST['Submit']) && ($_POST['Submit'] === "Save")) { 
	// Input validation
	$reqdfields = explode(" ", "subnet netmask startadr endadr routers broadcast-address ntp-servers");
	$reqdfieldsn = array(gettext("Subnet"), gettext("Netmask"), gettext("DHCP range - start"), gettext("DHCP range - end"), gettext("Gateway"), gettext("Broadcast"), gettext("NTP server"));
	$reqdfieldst = explode(" ", "ipaddr subnet ipaddr ipaddr ipaddr ipaddr string");
	
	do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);
	do_input_validation_type($_POST, $reqdfields, $reqdfieldsn, $reqdfieldst, $input_errors);
	
	if (empty($input_errors)) {
	$number = $_POST['number'];
	$dhcpd_conf['subnets'][$number]['subnet'] = $_POST['subnet'];
	$dhcpd_conf['subnets'][$number]['netmask'] = cidr2mask($_POST['netmask']);
	$dhcpd_conf['subnets'][$number]['startadr'] = $_POST['startadr'];
	$dhcpd_conf['subnets'][$number]['endadr'] = $_POST['endadr'];
	unset($dhcpd_conf['subnets'][$number]['option']);
	$dhcpd_conf['subnets'][$number]['option']['routers'] = $_POST['routers'];
	$dhcpd_conf['subnets'][$number]['option']['domain-name-servers'] = $_POST['domain-name-servers'];
	$dhcpd_conf['subnets'][$number]['option']['ntp-servers'] = $_POST['ntp-servers'];
	if (empty ($_POST['broadcast-address'])) { $dhcpd_conf['subnets'][$number]['option']['broadcast-address'] = gen_subnet_max ($_POST['subnet'], $_POST['netmask']); }
			else { $dhcpd_conf['subnets'][$number]['option']['broadcast-address'] =	$_POST['broadcast-address'];}
	/* $dhcpd_conf['subnets'][$number]['option']['netbios-name-servers'] = $_POST['netbios-name-servers'];*/
	if ( ! empty ($_POST['auxparam']))	{
	$w_option = explode("\r\n",$_POST['auxparam']); 
	for ($i=0;  $i < count($w_option);) { 
					$item = $w_option[$i];
					$parts = explode(' ', $item); $result_opt = $parts[1]." ".$parts[2]." ".$parts[3];
					$result = rtrim($result_opt," ");
					if ( ! empty($result) ) { $dhcpd_conf['subnets'][$number]['option'][$parts[0]] = $result;} else {}
					++$i;}
	} else {}
	}
	else goto out;
	write_dhcpconf($dhcpd_conf, $config['dhcplight']['homefolder']."conf/dhcpd.conf");
	mwexec ("touch /var/run/dhcpd.reload");	
}

header("Location: extensions_dhcpd_server.php");
}
out:
$pgtitle = array(gettext("Extensions"),gettext("DHCP_server|subnet"));
include("fbegin.inc");
?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
	<form action="extensions_dhcpd_subnet.php" method="post" name="iform" id="iform">
		<td class="tabcont">
		<?php if (!empty($input_errors)) print_input_errors($input_errors); ?>
			<table width="100%" border="0" cellpadding="5" cellspacing="0">
				<tr>
					<?php html_titleline(gettext("Dynamic Host Configuration Protocol - subnet"));?>
					<?php html_ipv4addrbox("subnet", "netmask", gettext("DHCP subnet"), $pconfig['subnet'], $pconfig['netmask'], gettext("Choice subnet"), true, false) ?>
					<?php html_inputbox("startadr", gettext("DHCP range - start"), $pconfig['startadr'], gettext("Choice start adress for DHCP hosts"), true, 16,false);?>
					<?php html_inputbox("endadr", gettext("DHCP range - end"), $pconfig['endadr'], gettext("Choice end adress for DHCP hosts"), true, 16,false);?>
					<?php html_inputbox("routers", gettext("Gateway"), $pconfig['routers'], gettext("Choise router IP"), true, 16,false);?>
					<?php html_inputbox("domain-name-servers", gettext("Name servers"), $pconfig['domain-name-servers'], gettext("Choise Name servers"), true, 50,false);?>
					<?php html_inputbox("ntp-servers", gettext("NTP server"), $pconfig['ntp-servers'], gettext("Use the specified NTP server for network."), false, 30, false);?>
					<?php html_inputbox("broadcast-address", gettext("Brodcast"), $pconfig['broadcast-address'], gettext("Choice brodcast"), false, 16, false);?>
					<?php html_textarea("auxparam", gettext("Aux parameters"), $pconfig['auxparam'] , sprintf(gettext(" This <b>options</b> can be added to config. ")), false, 65, 8, false, false);?>
		
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
<?php include("fend.inc"); ?>