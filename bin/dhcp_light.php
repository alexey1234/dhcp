#!/usr/local/bin/php-cgi -f
<?php
/*
dhcp_lite.php
*/
  require("guiconfig.inc");

  $install = "/usr/bin/install";
  $cp = "/bin/cp";

  // webgui
  $extname = "dhcp";
  $srcdir = $config['dhcplight']['homefolder']."conf/ext/dhcp";
  $dstdir = "/usr/local/www";

  if (!is_dir($dstdir."/ext")) {
    mkdir($dstdir."/ext");
    chmod($dstdir."/ext", 0755);
  }
  if (!is_dir($dstdir."/ext/".$extname)) {
    mkdir($dstdir."/ext/".$extname);
    chmod($dstdir."/ext/".$extname, 0755);
  }

  $cmd = $install." -c -o root -g wheel -m 644 ".$srcdir."/menu.inc ".$dstdir."/ext/".$extname."/";
  exec($cmd);
  $cmd = $install." -c -o root -g wheel -m 644 ".$srcdir."/function.inc ".$dstdir."/ext/".$extname."/";
  exec($cmd);
  $cmd = $install." -c -o root -g wheel -m 644 ".$srcdir."/extensions_dhcpd_host.php ".$dstdir;
  exec($cmd);
  $cmd = $install." -c -o root -g wheel -m 644 ".$srcdir."/extensions_dhcpd_server.php ".$dstdir;
  exec($cmd);
  $cmd = $install." -c -o root -g wheel -m 644 ".$srcdir."/extensions_dhcpd_subnet.php ".$dstdir;
  exec($cmd);

  exit(0);
?>