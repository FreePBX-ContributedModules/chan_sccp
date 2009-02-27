<?php

global $db;

echo "dropping table chan_mac..";
sql("DROP TABLE IF EXISTS `chan_mac`");
echo "done<br>\n";

echo "dropping table chanline..";
sql("DROP TABLE IF EXISTS `chanline`");
echo "done<br>\n";

echo "dropping table chandevice..";
sql("DROP TABLE IF EXISTS `chandevice`");
echo "done<br>\n";
?>
