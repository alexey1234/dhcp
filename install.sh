#!/bin/sh
workdir=`pwd`
# Make sure the user can't kill us by pressing Ctrl-C
trap : 2
trap : 3
trap : 4
# Make sure the user can't access rootshell by pressing Ctrl-Z
trap : 18
while : ; do
		# display menu
		echo
		echo "Console setup"
		echo "-------------"
		echo "1) Install DHCP-light"
		echo "2) Uninstall DHCP-light"
		echo "8) Exit"
		echo
		read -p "Enter a number: " opmode

		# see what the user has chosen
		case ${opmode} in
			1)
				$workdir/bin/install_dhcp
			 	;;
			2)
				$workdir/bin/uninstall_dhcp
			 	;;
			8)
				exit
			
		esac
	done