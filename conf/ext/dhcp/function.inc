<?php
/*
functions
*/
function dhcp_server_init() {
exec ("mkdir -p /usr/local/www/ext/dhcp");
exec ("cp {$config['dhcplight']['homefolder']}conf/ext/dhcp_server/* /usr/local/www/ext/dhcp/");
exec ("ln -s /usr/local/www/ext/dhcp/extensions_dhcpd_server.php /usr/local/www/extensions_dhcpd_server.php");
}

function cidr2mask($netmask) {
 $netmask_result="";
for($i=1; $i <= $netmask; $i++) {
  $netmask_result .= "1";
}
for($i=$netmask+1; $i <= 32; $i++) {
    $netmask_result .= "0";
}
$netmask_ip_binary_array = str_split( $netmask_result, 8 );
$netmask_ip_decimal_array = array();
foreach( $netmask_ip_binary_array as $k => $v ){
    $netmask_ip_decimal_array[$k] = bindec( $v ); // "100" => 4
}
$subnet = join( ".", $netmask_ip_decimal_array );
return $subnet;
}
function get_subnet_lan()
{
	$if = $config['interfaces']['lan']['ipaddr']."/".$config['interfaces']['lan']['subnet'];
$data = array();
$output = array();
exec("/usr/local/bin/sipcalc --cidr-addr {$if}", $data);
preg_match("/.+Network address\s+- (\d+)\.(\d+)\.(\d+)\.(\d+)/", implode($data), $output[1]);
preg_match("/Network mask \(bits\)	- (\d+).+/", implode($data), $output[2]);
return $output;
}
function mask2cidr($mask) {
 
$mask = split( "[.]", $mask );
 
$bits = 0;
 
foreach ($mask as $octect) {
	$bin = decbin($octect);
	$bin = str_replace ( "0" , "" , $bin);
	$bits = $bits + strlen($bin);
}
	return $bits;
}

//this function read dhcpd.conf file into array $result
function read_dhcpconf($file) {
	If (is_file ($file) ) {
		$configdhcp = file($file);
		$countconfig = count($configdhcp);
		$labels = array_keys( preg_grep ('/^#.+/i', $configdhcp));
		for ( $i=$labels[0]; $i<$labels[1]; ) {
					label1:
			++$i;
			$item = $configdhcp [$i];
			$parts = explode(' ', $item);
			if (rtrim($parts[0],";\n") === "authoritative") {  $result['globals']['authoritative'] = "yes"; goto label1; } 
			if ($parts[0] === "default-lease-time") {  $result['globals']['default-lease-time'] = rtrim($parts[1],";\n"); goto label1; } 
			if ($parts[0] === "max-lease-time") {  $result['globals']['max-lease-time'] = rtrim($parts[1],";\n"); goto label1; } 
			if ($parts[0] === "allow") {  $result['globals']['allow'] = rtrim($parts[1],";\n"); goto label1; }
			}
		$subnetstrings = array_keys( preg_grep ('/^subnet.+{/i', $configdhcp));
		for ($i=0; $i<(count($subnetstrings) ) ; ) {
			$j = $subnetstrings[$i];
					loop1:
			if ($j == $labels[2]) goto exit2;
			$item = ($configdhcp [$j]);
			$parts = explode(' ', $item);
			if ($parts[0] === "subnet") { $result['subnets'][$i]['subnet'] = $parts[1]; $result['subnets'][$i]['netmask'] = $parts[3]; ++$j; goto loop1; }
			if ($parts[0] === "range") { $result['subnets'][$i]['startadr'] = $parts[1]; $result['subnets'][$i]['endadr'] = rtrim($parts[2],";\n"); ++$j; goto loop1; }
			if ($parts[0] === "option") { $line = rtrim($parts[2]." ".$parts[3]." ".$parts[4], " "); $line1 = rtrim($line,";\n"); $result['subnets'][$i]['option'][$parts[1]] = trim($line1,'"'); ++$j; goto loop1;}
			++$i;}
					exit2:
		$hoststrings = array_keys( preg_grep ('/^host.+{/i', $configdhcp));
		for ($i=0; $i<(count($hoststrings) ) ; ) {
			$j = $hoststrings[$i];
			$item = ($configdhcp [$j]);
			$parts = explode(' ', $item);
			$result['hosts'][$i]['host'] = $parts[1];
					loop2:
			if ($j == $countconfig) goto exit4;
			++$j;
			if ($configdhcp[$j] === "}") {goto exit3;} else {
			$item = ($configdhcp [$j]);
			$parts = explode(' ', $item);
			if ($parts[0] === "hardware") { $result['hosts'][$i]['mac'] = rtrim($parts[2],";\n"); goto loop2; }
			if ($parts[0] === "fixed-address") { $result['hosts'][$i]['fixed-address'] = rtrim($parts[1],";\n"); goto loop2;}
			if ($parts[0] === "next-server") { $result['hosts'][$i]['next-server'] = rtrim($parts[1],";\n"); goto loop2;}
			if (($parts[0] === "allow") && (rtrim($parts[1],";\n") === "booting")) { $result['hosts'][$i]['allowboot'] = rtrim($parts[1],";\n"); goto loop2;}
			if ($parts[0] === "filename") { $line = rtrim($parts[1],";\n"); $result['hosts'][$i]['filename'] = trim($line,'"'); goto loop2;}
			if (($parts[0] === "option") && ($parts[1] === "root-path") ) { $line = rtrim($parts[2],";\n"); $result['hosts'][$i]['root-path'] = trim($line,'"'); goto loop2;}
			}
					exit3:
	++$i;}
exit4:

}
return $result;
}

//this function write dhcpd.conf file from $configarray
function write_dhcpconf($configarray, $file) {
$handle=fopen($file, "w");
fwrite ($handle,"#global\n");
fwrite ($handle,"log-facility local7;\n");
if ($configarray['globals']['authoritative'] === "yes") { fwrite ($handle,"authoritative;\n");} else {}
fwrite ($handle,"default-lease-time ".$configarray['globals']['default-lease-time'].";\n");
fwrite ($handle,"max-lease-time ".$configarray['globals']['max-lease-time'].";\n");
if ($configarray['globals']['allow'] === "bootp") { fwrite ($handle,"allow bootp;\n");} else {}
fwrite ($handle,"#subnet\n");
for ($i=0; $i<count($configarray['subnets']);) {
fwrite ($handle, "subnet ".$configarray['subnets'][$i]['subnet']." netmask ".$configarray['subnets'][$i]['netmask']." {\n");
fwrite ($handle, "range ".$configarray['subnets'][$i]['startadr']." ".$configarray['subnets'][$i]['endadr'].";\n");
$temparray = $configarray['subnets'][$i]['option'];
foreach ( $temparray as $key => $value ) { fwrite ($handle, "option ".$key." ".$value.";\n"); }
fwrite ($handle,"}\n");
++$i; }
fwrite ($handle,"#hosts\n");
for ($i=0; $i<count($configarray['hosts']);) {
fwrite ($handle, "host ".$configarray['hosts'][$i]['host']." {\n");
fwrite ($handle, "hardware ethernet ".$configarray['hosts'][$i]['mac'].";\n");
fwrite ($handle, "fixed-address ".$configarray['hosts'][$i]['fixed-address'].";\n");
if (!empty($configarray['hosts'][$i]['allowboot']))  {fwrite ($handle, "allow booting;\n");} else {}
if (!empty($configarray['hosts'][$i]['next-server']))  {fwrite ($handle, "next-server ".$configarray['hosts'][$i]['next-server'].";\n");} else {}
if (!empty($configarray['hosts'][$i]['filename']))  {fwrite ($handle, "filename \"".$configarray['hosts'][$i]['filename']."\";\n");} else {}
if (!empty($configarray['hosts'][$i]['root-path']))  {fwrite ($handle, "option root-path \"".$configarray['hosts'][$i]['root-path']."\";\n");} else {}
fwrite ($handle,"}\n");
++$i; }
fclose($handle);
}

?>