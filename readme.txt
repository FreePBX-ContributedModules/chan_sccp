A helper module for chan_sccp-b

Install chan_sccp-b from svn:

cd /usr/src
svn co https://chan-sccp-b.svn.sourceforge.net/svnroot/chan-sccp-b/branches/v2 chan_sccp2-b
cd chan_sccp2-b
make 
make install

add this line to /etc/asterisk/modules.conf

noload => chan_skinny.so

modify /etc/asterisk/sccp.conf to your needs (remove the devices and lines that are in the conf)

Add to /etc/asterisk/extconfig.conf 

sccpdevice => mysql,asterisk
sccpline => mysql,asterisk

And /etc/asterisk/res_mysql.conf

[general]
dbhost = localhost
dbname = asterisk 
dbpass = amp109
dbuser = asteriskuser
dbsock = /var/lib/mysql/mysql.sock

Install tftp

yum install tftp-server
chmod 777 /tftpboot

Set disbaled = no in /etc/xinetd.d/tftp

chkconfig xinetd on
service xinetd start

Module installation:
1. untar this module in admin/modules
2. Goto freepbx admin -> Module Admin
3. Find the module and select Install -> Procced
4. In the Setup menu -> SCCP Phone 
5. Add the phone with its MAC address, type and the freepbx extension
6. Apply configuration and you are done!
7. Enter the ip of your server into the cisco phone (tftpaddress) and start making calls.  