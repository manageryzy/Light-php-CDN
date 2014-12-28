<?php
///////////////////////////////////////////////////////////////
//                                                           //
//      LIGHTphpCDN                                          //
//                                                           //
//                 ------ An light php CDN                   //
//                                                           //
//                      Licence : BSD                        //
//                      copyright manageryzy@gmail.com       //
//                                                           //
///////////////////////////////////////////////////////////////



//---------------------------------
// setting area

$ACCEPT_HOST = 'localhost';                 //only the HTTP request with this doname would be accepted

$IS_ROOT_SERVER = true;                     //all the file in the CDN will be same as the file in the Root Server
$SOURCE_SERVER_PATH = 'http://127.0.0.1/';  //the path of the source server 
$SOURCE_SERVER_HOST = 'localhost';          //the acceptable host for the server,this field also be the key of AES

$CHILD_SERVER_LIST = array();


//---------------------------------
//gloabal variable

$dir_list = array();
$file_list = array();

$local_file_list = array();
$local_dir_list = array();


//---------------------------------
//     php settings

ignore_user_abort(true);                    //these two functions will keep the script working until finish , so lock is needed
set_time_limit(0);

//----------------------------------
// class and function define area

//a class for cache
class HashCache
{
    var $hash_sha;
    var $hash_md5;
    var $motify_time;
    var $file_path;
    
    function __construct($ifUpdate,$FILE_PATH,$HASH_MD5,$HASH_SHA,$MOTIFY_TIME)
    {
        if($ifUpdate)
        {
            if(file_exists($FILE_PATH))
            {
                $time = filemtime($FILE_PATH);
                if($time==false)
                {
                    //error in getting file time
                    $this->file_path = $FILE_PATH;
                    $this->hash_md5 = '0';
                    $this->hash_sha = '0';
                    $this->motify_time = 0;
                }
                else if($time == $MOTIFY_TIME)
                {
                    //not changed
                    $this->file_path = $FILE_PATH;
                    $this->hash_md5 = $HASH_MD5;
                    $this->hash_sha = $HASH_SHA;
                    $this->motify_time = $MOTIFY_TIME;
                }
                else
                {
                    //changed
                    $this->file_path = $FILE_PATH;
                    $this->hash_md5 = md5_file($FILE_PATH);
                    $this->hash_sha = sha1_file($FILE_PATH);
                    $this->motify_time = $time;
                }
            }
            else
            {
                //file is not exist
                $this->file_path = $FILE_PATH;
                $this->hash_md5 = '0';
                $this->hash_sha = '0';
                $this->motify_time = 0;
            }
        }
        else
        {
            //do not update the cache
            $this->file_path = $FILE_PATH;
            $this->hash_md5 = $HASH_MD5;
            $this->hash_sha = $HASH_SHA;
            $this->motify_time = $MOTIFY_TIME;
        }
    }
}


//an aes class. 
//copy from http://www.oschina.net/code/snippet_248412_15378 
class aes {
 
    // CRYPTO_CIPHER_BLOCK_SIZE 32
     
    private $_secret_key = 'default_secret_key';
     
    public function setKey($key) {
        $this->_secret_key = $key;
    }
     
    public function encode($data) {
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_256,'',MCRYPT_MODE_CBC,'');
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td),MCRYPT_RAND);
        mcrypt_generic_init($td,$this->_secret_key,$iv);
        $encrypted = mcrypt_generic($td,$data);
        mcrypt_generic_deinit($td);
         
        return $iv . $encrypted;
    }
     
    public function decode($data) {
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_256,'',MCRYPT_MODE_CBC,'');
        $iv = mb_substr($data,0,32,'latin1');
        mcrypt_generic_init($td,$this->_secret_key,$iv);
        $data = mb_substr($data,32,mb_strlen($data,'latin1'),'latin1');
        $data = mdecrypt_generic($td,$data);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
         
        return trim($data);
    }
}

//error dealing
function OnError($info)
{
    unlink('./.lock');
    die($info);
}

//lock the server when do the atom opetate
function lock_server()
{
    $fp = fopen('./.lock','w');
    if($fp == false)
    {
        OnError('error in lock file');
    }
    fclose($fp);
}

//trace the local file
function tree($directory) 
{ 
    global $local_file_list,$local_dir_list;
	$mydir = dir($directory); 

	while($file = $mydir->read())
	{ 
		if((is_dir("$directory/$file")) AND ($file!=".") AND ($file!="..")) 
		{
            $local_dir_list[]="$directory/$file";
			tree("$directory/$file"); 
		} 
		else if(($file!=".") AND ($file!=".."))
        {
            $local_file_list[]="$directory/$file";
        }
	} 

	$mydir->close(); 
} 

//load hash data form .cache file
function load_hash_cache($ifUpdate)
{
    global $file_list,$dir_list;
    $fp = fopen('./.cache','r');
    
    if($fp == false)
        return;
    
    while($line = fscanf($fp,'%s %s %d %s %s\n')) 
    {
        list($type,$path,$time,$md5,$sha) = $line;

        if($type == 'dir')
        {
            $File = new HashCache(false,$path,'0','0',0);
            if($ifUpdate)
            { 
                if(file_exists($path))
                {
                    $file_list[$path] = $File;
                }
            }
            else
            {
                $dir_list[$path] = $File;
            }
        }
        else if($type == 'file')
        {
            $File = new HashCache($ifUpdate,$path,$md5,$sha,$time);
            if($ifUpdate)
            {
                if($File->hash_md5!='0')
                {
                    $file_list[$path] = $File;
                }
            }
            else
            {
                $file_list[$path] = $File;
            }
        }
        else
        {
            OnError('<h1>Error:Cache File is in the Wrong Format!</h1><h2>Do it damaged ? Please refresh the cache.</h2>');
        }
    }
    
    fclose($fp);
}

//save the hash cache data to the .cache file
function save_hash_cache()
{
    global $file_list,$dir_list;
    $fp = fopen('./.cache','w');
    
    if($fp == false)
    {
        OnError('<h1>Error in saving cache file!</h1>I do not know why ,but it was really happened!');
    }
    
    foreach($file_list as $key=>$value)
    {
        fprintf($fp,"%s %s %d %s %s\n",'file',$value->file_path,$value->motify_time,$value->hash_md5,$value->hash_sha);
    }
    
    foreach($dir_list as $key=>$value)
    {
        fprintf($fp,"%s %s %d %s %s\n",'dir',$value->file_path,0,'0','0');
    }
    
    fclose($fp);
}

//add the new file to the cache list
function trace_new_file()
{
    global $file_list,$dir_list;
    global $local_file_list,$local_dir_list;
    
    foreach($local_dir_list as $key=>$value)
    {
        if(!isset($dir_list[$value]))
        {
            $File = new HashCache(false,$value,'0','0',0);
            $dir_list[$value] = $File;
        }
    }
    
    foreach($local_file_list as $key=>$value)
    {
        if(!isset($file_list[$value]))
        {
            $File = new HashCache(true,$value,'0','0',0);
            $file_list[$value] = $File;
        }
    }
}

//------------------------------------------
//   code start here

//check whether the host field is acceptable
if($_SERVER['HTTP_HOST'] != $ACCEPT_HOST)
{
    //Do not use OnError here
    die('<h1>The Host is NOT Acceptable!</h1><h2>Are you a hacker?</h2>');
}

//die if the server is busy
if(file_exists('./.lock')!=false)
{
    //Do not use OnError Here.
    die('<h1>Sorry ! the CDN is busy now!</h1><br>If you think it is an error,please delete the .lock file');
}

lock_server();//lock the server in case error happen


tree("./data"); 
load_hash_cache(true);
trace_new_file();
save_hash_cache();

//print_r($local_dir_list);
//print_r($local_file_list);

//print_r($dir_list);
//print_r($file_list);

unlink('./.lock');//delete the lock file
?>