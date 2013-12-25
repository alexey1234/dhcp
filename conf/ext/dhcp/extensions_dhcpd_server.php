<?php
/* 
extensions_dhcpd_server.php
Version=0.05
*/
require("auth.inc");
require("guiconfig.inc");
include_once ("ext/dhcp/function.inc");
$dhcpd_conf = read_dhcpconf($config['dhcplight']['homefolder']."conf/dhcpd.conf");
$pconfig = $dhcpd_conf['globals']; //  hmmm need conver 
$pconfig['enable'] = $config['dhcplight']['enable'];
$tempfilecontent = "<?php require(\"auth.inc\"); require(\"guiconfig.inc\"); if (is_file (\"/var/run/dhcpd.pid\")) { \$pid=file_get_contents(\"/var/run/dhcpd.pid\"); print \"DHCP run with pid=\".\$pid; } else print \"DHCP stoped\";?>";
file_put_contents ("test_dhcp.php", $tempfilecontent );
if ($_POST) { 
	if (isset($_POST['Submit']) && ($_POST['Submit'] === "Save")) { 
		unset($input_errors);
		$pconfig = $_POST;
		if (isset($_POST['enable'])) { $config['dhcplight']['enable'] = "yes"; exec ("/bin/sh {$config['dhcplight']['homefolder']}bin/dhcp_light start");} else {unset($config['dhcplight']['enable']); exec ("/bin/sh {$config['dhcplight']['homefolder']}bin/dhcp_light stop");}
		write_config();
		
		if (isset($_POST['authoritative'])) { $dhcpd_conf['globals']['authoritative'] = "yes";} else {unset($dhcpd_conf['globals']['authoritative']);}
		if (isset($_POST['allow'])){  $dhcpd_conf['globals']['allow'] = "bootp";} else {unset($dhcpd_conf['globals']['allow']);}
		$dhcpd_conf['globals']['default-lease-time'] = $_POST['default-lease-time'];
		$dhcpd_conf['globals']['max-lease-time'] = $_POST['max-lease-time'];
		write_dhcpconf($dhcpd_conf, $config['dhcplight']['homefolder']."conf/dhcpd.conf");
		mwexec ("/bin/sh {$config['dhcplight']['homefolder']}bin/dhcp_light.sh restart");
		}
	if (isset($_POST['apply']) && ($_POST['apply'] === "Apply changes")) { 
		$savemsg = "";
		$warnmess ="";
		$pidrestart = exec ("/bin/sh {$config['dhcplight']['homefolder']}bin/dhcp_light restart");
		exec ("rm -f /var/run/dhcpd.reload");
		if (is_numeric($pidrestart)) {	$savemsg = gettext("The changes have been applied successfully.");} else {$warnmess = gettext("Something wrong, please refer dhcpd.conf or check DHCP server checkbox"); }
		}
	if ($config['dhcplight']['enable'] == "yes")  {
				if (FALSE == is_file ("/var/run/dhcpd.pid")) { exec ("/bin/sh {$config['dhcplight']['homefolder']}bin/dhcp_light start");}
					else { exec ("/bin/sh {$config['dhcplight']['homefolder']}bin/dhcp_light restart");}
				}
			else { exec ("/bin/sh {$config['dhcplight']['homefolder']}bin/dhcp_light stop");}
	}

$pgtitle = array(gettext("Extensions"),gettext("DHCP_server|light"));
include("fbegin.inc");?>
<script language="JavaScript">
var auto_refresh = setInterval(
		function()
		{
		$('#dhcpserv').load('test_dhcp.php');
		}, 2000);
</script>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
	<form action="extensions_dhcpd_server.php" method="post" name="iform" id="iform">
		<td class="tabcont">
		<?php if (!empty($input_errors)) print_input_errors($input_errors); ?>
			<?php if (is_file("/var/run/dhcpd.reload"))  print_config_change_box() ?>
			<?php if ($savemsg) {print_info_box($savemsg); $savemsg = ""; } ?>
			<?php if ($warnmess) {print_warning_box($warnmess); $warnmess = ""; } ?>
			<table width="100%" border="0" cellpadding="5" cellspacing="0">
			
				<?php html_titleline_checkbox("enable", gettext("Dynamic Host Configuration Protocol"), !empty($pconfig['enable']) ? true : false, gettext("Enable"), "enable_change(false)");?>	
				
				<?php html_separator();?>
				<tr>
					<td width="22%" valign="top" class="vncell"><?=gettext("Dhcp-server status");?></td>
					
					
					<td width="78%" class="vtable"><div id="dhcpserv" style="display: block;"></td>
					
					
				</tr>
				<?php html_separator();?>
				<tr>
				
					<td width="22%" valign="top" class="vncell"><?=gettext("Subnet");?></td>
					<td width="78%" class="vtable">
					
						<table width="100%" border="0" cellpadding="0" cellspacing="0">
								<tr>
									<td width="5%" class="listhdrlr"><?=gettext(" ");?></td>
									<td width="10%" class="listhdrr"><?=gettext("Subnet");?></td>
									<td width="10%" class="listhdrr"><?=gettext("Netmask");?></td>
									<td width="30%" class="listhdrr"><?=gettext("Range");?></td>
									<td width="10%" class="listhdrr"><?=gettext("gateway");?></td>
									<td width="15%" class="listhdrr"><?=gettext("name server");?></td>
									<td width="15%" class="listhdrr"><?=gettext("time server");?></td>
																	
									<td width="5%" class="list"></td>
								</tr>
									<?php for ($i=0; $i< count($dhcpd_conf['subnets']);) { $subnets_d = $dhcpd_conf['subnets'][$i];?>
								<tr>
									<td class="listr"><?=htmlspecialchars($i);?>&nbsp;</td>
									<td class="listr"><?=htmlspecialchars ($subnets_d['subnet']);?>&nbsp;</td>
									<td class="listr"><?=htmlspecialchars($subnets_d['netmask']);?>&nbsp;</td>
									<td class="listr"><?=htmlspecialchars($subnets_d['startadr']." - ".$subnets_d['endadr']);?>&nbsp;</td>
									<td class="listr"><?=htmlspecialchars($subnets_d['option']['routers']);?>&nbsp;</td>
									<td class="listr"><?=htmlspecialchars($subnets_d['option']['domain-name-servers']);?>&nbsp;</td>
									<td class="listr"><?=htmlspecialchars($subnets_d['option']['ntp-servers']);?>&nbsp;</td>
									<td valign="middle" nowrap="nowrap" class="list">
										<a href="extensions_dhcpd_subnet.php?act=edit&amp;number=<?=$i;?>"><img src="e.gif" title="<?=gettext("Edit host");?>" border="0" alt="<?=gettext("Edit host");?>" /></a>
										&nbsp;
										<a href="extensions_dhcpd_subnet.php?act=del&amp;number=<?=$i;?>" onclick="return confirm('<?=gettext("Do you really want to delete this entry?");?>')"><img src="x.gif" title="<?=gettext("Delete host");?>" border="0" alt="<?=gettext("Delete host");?>" /></a>
									</td>
								</tr>									
									<?php ++$i; } ?>
								<tr>
																		
								</tr>
								<tr>
									<td class="list" colspan="7"></td>
									<td class="list">
										<a href="extensions_dhcpd_subnet.php?act=new"><img src="plus.gif" title="<?=gettext("Add subnet");?>" border="0" alt="<?=gettext("Add subnet");?>" /></a>
									</td>
								</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td width="22%" valign="top" class="vncell"><?=gettext("Hosts");?></td>
					<td width="78%" class="vtable">
						<table width="100%" border="0" cellpadding="0" cellspacing="0">
								<tr>
									<td width="5%" class="listhdrlr">&nbsp;</td>
									<td width="15%" class="listhdrr"><?=gettext("Hostname");?></td>
									<td width="25%" class="listhdrr"><?=gettext("MAC");?></td>
									<td width="25%" class="listhdrr"><?=gettext("IP adress");?></td>
									<td width="15%" class="listhdrr"><?=gettext("Booting");?></td>
									<td width="15%" class="list"></td>
								</tr>
								<?php for ($i=0; $i< count($dhcpd_conf['hosts']);) {
								$hosts_d = $dhcpd_conf['hosts'][$i];?>								
								<tr>
									<td class="listr"><?=htmlspecialchars($i);?>&nbsp;</td>
									<td class="listr"><?=htmlspecialchars ( $hosts_d['host']);?>&nbsp;</td>
									<td class="listr"><?=htmlspecialchars($hosts_d['mac']);?>&nbsp;</td>
									<td class="listr"><?=htmlspecialchars($hosts_d['fixed-address']);?>&nbsp;</td>
									<td class="listr"><?php If(!empty ($hosts_d['allowboot'])):?>
											<a title="<?=gettext("Enabled");?>"><img src="status_enabled.png" border="0" alt="" /></a>
											<?php else:?>
											<a title="<?=gettext("Disabled");?>"><img src="status_disabled.png" border="0" alt="" /></a>
										<?php endif;?></center></td>
									<td valign="middle" nowrap="nowrap" class="list">
										<a href="extensions_dhcpd_host.php?act=edit&amp;number=<?=$i;?>"><img src="e.gif" title="<?=gettext("Edit host");?>" border="0" alt="<?=gettext("Edit host");?>" /></a>
										<a href="extensions_dhcpd_host.php?act=del&amp;number=<?=$i;?>" onclick="return confirm('<?=gettext("Do you really want to delete this entry?");?>')"><img src="x.gif" title="<?=gettext("Delete host");?>" border="0" alt="<?=gettext("Delete host");?>" /></a>
									</td>	
									<?php ++$i; } ?>
								</tr>
								<tr>
									<td class="list" colspan="5"></td>
									<td class="list">
										<a href="extensions_dhcpd_host.php?act=new"><img src="plus.gif" title="<?=gettext("Add host");?>" border="0" alt="<?=gettext("Add host");?>" /></a>
									</td>
								</tr>
						</table>
					</td>
				</tr>
				<tr>
					<?php html_checkbox("authoritative", gettext("Autoritative"), !empty($pconfig['authoritative']) ? true : false, gettext("If this DHCP server is the official DHCP server for the local network, the authoritative directive should be checked."), "", false);?>
					<?php html_checkbox("allow", gettext("Allow bootp"), !empty($pconfig['allow']) ? true : false, gettext("The bootp flag is used to tell dhcpd whether or not to respond to bootp queries."), "", false);?>

					
					<?php html_inputbox("default-lease-time", gettext("Default lease time"), $pconfig['default-lease-time'], gettext("Sets default lease time in seconds"), false, 10,false);?>
					<?php html_inputbox("max-lease-time", gettext("Maximum lease time"), $pconfig['max-lease-time'], gettext("Sets maximum lease time in seconds"), false, 10,false);?>
				</tr>
				<tr><td>
					<div id="submit">
					<input name="Submit" type="submit" class="formbtn" value="<?=gettext("Save");?>" />
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
