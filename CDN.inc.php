<?php
$ACCEPT_HOST        = 'localhost';          //only the HTTP request with this doname would be accepted

$IS_ROOT_SERVER     = true;                 //all the file in the CDN will be same as the file in the Root Server
$SERVER_KEY         = 'WzTZkJnBhS0nbcMk';   //The key for this server
$SOURCE_SERVER_PATH = 'http://127.0.0.1/';  //the path of the source server 
$SOURCE_SERVER_HOST = 'localhost';          //the acceptable host for the server,this field also be the key of AES
$SOURCE_SERVER_KEY  = '';                   //The Key for the source server
$Enable_Log         = true;
?>