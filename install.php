<?php

/*LICENSE INFORMATION*
 * Kure is distributed under the terms of the GNU General Public License
 * (http://www.gnu.org/licenses/gpl.html).
 * Kure Copyright 2007-2008 Ben Carlsson
 * 
 *-->
 * This file is part of Kure.
 * 
 * Kure is free software: you can redistribute it and/or modify it under the terms of the 
 * GNU General Public License as published by the Free Software Foundation, either version
 * 3 of the License, or (at your option) any later version.
 * 
 * Kure is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
 * PURPOSE.  See the GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License along with Kure.  If
 * not, see <http://www.gnu.org/licenses/>.
 *-->
 * 
 * Please see index.php for notes and license information.
 */

$kure_ver = "0.7.1";
@include_once("functions.php"); // supress errors because the file & directory check below will handle it

$head = '<html><head><title>Kure $kure_ver</title><style type="text/css">body { font-family: trebuchet ms; font-size: 14px; } .pagetitle { color: #404040; font-family: trebuchet ms, arial; font-size: 20px; letter-spacing: -1px; }</style></head><body>
  <span class=\"pagetitle\">Install Kure $kure_ver </span><br>
  <p>Detecting tasks...</p>';

$required_paths = array('admin/', 'admin/index.php', 'functions.php', 'index.php');
$success = true;
foreach($required_paths as $path) {
  if(!file_exists($path)) {
    print('<b>ERROR:</b> File or directory <tt>' . $path . '</tt> is missing.<br/>');
    $success = false;
  }
}
if(!$success)
  exit('<br/>Please upload any missing files before continuing.');

if(!isset($_POST['create'])) // so that we can use header() to refresh later
  print($head);

// find out what needs to be done

clearstatcache(); // continue

if(!is__writable('./'))
  $todo_user[] = 'make the current directory (<tt>' . dirname($_SERVER['SCRIPT_NAME']) . '</tt>) writeable';

if(!file_exists('config.php'))
  $todo_kure['config'] = 'generate <tt>config.php</tt>';

if(!file_exists('posts/'))
  $todo_kure['posts'] = 'create a <tt>posts</tt> directory';
elseif(!is__writable("posts"))
  $todo_user[] = 'make the <tt>posts</tt> directory writeable';

if(!file_exists('docs/'))
  $todo_kure['docs'] = 'create a <tt>docs</tt> directory';
elseif(!is__writable('docs'))
  $todo_user[] = 'make the <tt>docs</tt> directory writeable';

if(isset($_POST['create'])) {
  if(isset($todo_kure['config'])) {
    if($_POST['pass1'] != $_POST['pass2'] || $_POST['pass1'] == "")
      exit($head .  'Passwords did not match or were not entered. <a href="?">Try again</a>.');
    $config = array(
      'kure_ver' => $kure_ver,
      'adminpass' => md5($_POST['pass1']),
      'blog_name' => 'kure',
      'blog_sub' => 'beta',
      'num_posts' => 8,
      'docdates' => true,
      'docspagedates' => true,
      'abcdocs' => true,
      'abcposts' => false,
      'showadmin' => true,
      'template' => 'sanitation'
    );
    if(!write_config($config))
      exit($head . '<span class="error">Couldn\'t write to <tt>config.php</tt>!</span>');
  }
  if(isset($todo_kure['posts'])) {
    if(!mkdir('posts/'))
      exit($head '<span class="error">Couldn\'t create directory <tt>posts</tt></span>');
  }
  if(isset($todo_kure['docs'])) {
    if(!mkdir('docs/'))
      exit($head '<span class="error">Couldn\'t create directory <tt>docs</tt></span>');
  }
  header('Location: ?'); // refresh the page to refresh the tasks
}

if(!isset($todo_user) && !isset($todo_kure))
  exit('<b>All done!</b> Be sure to DELETE THIS FILE before moving on to your <a href="admin">admin panel</a>.<br/><br/>Keep in mind that <b>Kure is still in beta</b>. This means that there may (and probably will) be some bugs and possible security holes. In most cases, security holes in Kure will only affect Kure's directory, but this does not rule out the possbile risk of other files on your server. It is a good practice in general, even if you're not using Kure, to backup important files and information on your server regularly.');

print('Okay. ');
if(isset($todo_user))
  print('Let's see what's on the agenda for today:<br/><br/>');

if(isset($todo_user)) {
  print('<b>You</b> need to:<br/><br/>');
  foreach($todo_user as $task)
    print('&bull; $task<br/>');
  print('<br/>');
  if(isset($todo_kure))
    print('So that <b>Kure</b> can:');
  else
    print('Then you\'ll be done.');
} else
  print('Looks like Kure is ready to do the following.');

print('<br/><br/>');

if(isset($todo_kure)) {
  foreach($todo_kure as $task)
    print('&bull; $task<br/>');
}
print('<br/>\n');
if(isset($todo_user)) {
  print('Refresh this page when you\'ve completed your tasks');
  if(isset($todo_kure))
    print(', and Kure will be ready to complete its own');
  print('.');
} else {
  if(isset($todo_kure['config'])) {
    print('Before the config file is generated, however, <b>you must set a password with which you will access the administration interface</b>.<br/>It will be encrypted and stored in <tt>config.php</tt> with all other configuration variables, which is why you need to set it now.<br/>You can change this later.<br/>\n');
    print('<form action="?" method="post"><p><input type="password" name="pass1"> (enter)</p>\n<p><input type="password" name="pass2"> (confirm)</p>\n');
  }
  print('Ready to go?<br/><br/><input type="submit" name="create" value="Let\'s do it"></form>');
}

// from http://php.net/manual/en/function.is-writable.php#73596
function is__writable($path) {

	// will work despite Windows ACLs bug
	// NOTE: use a trailing slash for folders!!!
	// see http://bugs.php.net/bug.php?id=27609
	// see http://bugs.php.net/bug.php?id=30931

  if($path{strlen($path) - 1} == '/') // recursively return a temporary file path
    return is__writable($path . uniqid(mt_rand()) . '.tmp');
  elseif(is_dir($path))
    return is__writable($path . '/' . uniqid(mt_rand()) . '.tmp');

  // check tmp file for read/write capabilities
  $rm = file_exists($path);
  $f = @fopen($path, 'a');

  if($f === false)
    return false;

  fclose($f);

  if(!$rm)
    unlink($path);

  return true;

}

?>
