LIGHTphpCDN
=======
<p>This project is an light weight mirror manage system wrote in php.</p>
## Why open this project?
<p>Many Server on the Internet is fast,but they can only use php as the server script. You can't 
use the existing system to manage them if you want to use them as your file server. You have to
use FTP to send file between them.So I just make this little tool to send data between servers.</p>
## Quick start.
Coming soon.
## Manual 
### Requirement
A php environment is required , at least PHP 4.5. Some PHP modules is needed ,such as mcrypt.
### Setting up
<p>Upload the file 'CDN.php' and 'CDN.inc.php' to your server.Set the parameter in the CDN.inc.php.</p>
<p>Here are all the parameters.</p>
<ul>
<li>ACCEPT_HOST - Only the HTTP Repuest with this host name would be accepted</li>
<li>IS_ROOT_SERVER - Set to be true if this server is the root server.</li>
<li>SERVER_KEY - The key for AES in this server.</li>
<li>SOURCE_SERVER_PATH - The path for father server.It will be ignored at the root server.</li>
<li>SOURCE_SERVER_HOST - The Host for father server.This server will use this host name to visit father server.</li> 
<li>SOURCE_SERVER_KEY - The AES key for father server.</li>
<li>Enable_Log - Set to be true if you want to log events.</li>
</ul>
<p>After editing your inc file, you should create a directory called 'data'.Here will be the place where
putting your files.</p>
<p>If all the tasks is done, you should be able to visit the page and use the task parameter to do an 
task.You should do the task 'add' first.</p>
### Tasks
<p>Here are the tasks.</p>
<ul>
<li>list - List the files in this server , this task should only called by the child server.</li>
<li>get - Get an file, This task should only called by the child server.</li>
<li>add - Refresh the cache file.Just like the command in git.</li>
<li>clone - clone the father server.This command will be called automatically by the root server.</li>
<li>push - Call the clone task in all the child server.</li>
<li>help - Useless help info XDD.</li>
</ul>
## Licence
Apache License 2.0
Just use.Have fun!
## Author
<li>manageryzy@gmail.com</li>
