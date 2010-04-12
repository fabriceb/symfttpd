#!/usr/bin/env php
<?php
/**
 * @author Laurent Bachelier <laurent@bachelier.name>
 */

error_reporting(E_ALL|E_STRICT);

require dirname(__FILE__).'/lib/Argument.php';
require dirname(__FILE__).'/lib/Template.php';
require dirname(__FILE__).'/lib/FileTools.php';
require dirname(__FILE__).'/lib/PosixTools.php';
require dirname(__FILE__).'/lib/MultiConfig.php';
require dirname(__FILE__).'/lib/Symfony.php';

$project_path = Symfony::getProjectPath();
$options = MultiConfig::get();

$options['port'] = intval(Argument::get('p', 'port', 4042));
$options['bind'] = Argument::get('A', 'all', false)
                 ? false
                 : Argument::get('b', 'bind', '127.0.0.1');
$options['project_path'] = $project_path;
$options['config_dir'] = $project_path.'/cache/lighttpd';
$options['log_dir'] = $project_path.'/log/lighttpd';

FileTools::mkdirs($options['config_dir']);
FileTools::mkdirs($options['log_dir']);

PosixTools::setCustomPath($options['custom_path']);
try
{
  if (empty($options['lighttpd_cmd']))
  {
    $options['lighttpd_cmd'] = PosixTools::which('lighttpd');
  }

  if (empty($options['php_cgi_cmd']))
  {
    $options['php_cgi_cmd'] = PosixTools::which('php-cgi');
  }

  if (empty($options['php_cmd']))
  {
    $options['php_cmd'] = PosixTools::which('php');
  }
}
catch (ExecutableNotFoundError $e)
{
  /* Weird and ugly, sadly necessary to be sure
   * something will be shown to the user */
  echo "Required executable not found.\n ";
  echo $e->getMessage();
  echo ' not found in the specified paths: ';
  echo implode(', ', PosixTools::getPaths());
  echo "\n";
  exit(1);
}

$config_file = $options['config_dir'].'/lighttpd.conf';
file_put_contents(
  $config_file,
  Template::get($options['config_template'], $options)
);

// Pretty information. Nothing interesting code-wise.
echo 'lighttpd started on ';
echo strlen($options['bind']) ? $options['bind'] : 'all interfaces';
echo ', port '.$options['port'].'.';
echo "\n\nAvailable applications:\n";
$apps = array();
foreach (new DirectoryIterator($project_path.'/web') as $file)
{
  if ($file->isFile() && preg_match('/\.php$/', $file->getFilename()))
  {
    $apps[] = $file->getFilename();
  }
}
$host = in_array($options['bind'], array(false, '0.0.0.0', '::'), true)
      ? 'localhost'
      : $options['bind'];
sort($apps);
foreach ($apps as $app)
{
  echo ' http://'.$host.':'.$options['port'].'/'.$app."\n";
}
echo "\nPress Ctrl+C to stop serving.\n";
flush();

passthru($options['lighttpd_cmd'].' -D -f '.escapeshellarg($config_file));

