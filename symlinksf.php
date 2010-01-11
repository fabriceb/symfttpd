#!/usr/bin/env php
<?php
/**
 * Create the symbolic link for "/sf"
 *
 * @author Laurent Bachelier <laurent@bachelier.name>
 */

error_reporting(E_ALL|E_STRICT);

/**
 * Guess the symfony data dir path
 * @param string $path Project path
 * @return string|boolean The absolute symfony data dir path or false
 *
 * @author Laurent Bachelier <laurent@bachelier.name>
 */
function get_data_dir($path)
{
  if (file_exists($path.'/config/config.php'))
  {
    require $path.'/config/config.php';
  }
  elseif (file_exists($path.'/config/ProjectConfiguration.class.php'))
  {
    require $path.'/config/ProjectConfiguration.class.php';
  }
  if (isset($sf_symfony_data_dir))
  {
    $sf_data_dir = $sf_symfony_data_dir;
  }
  elseif (class_exists('sfCoreAutoload'))
  {
    $sf_data_dir = sfCoreAutoload::getInstance()->getBaseDir().'../data';
  }
  else
  {
    $sf_data_dir = false;
  }

  return $sf_data_dir;
}

$path = getcwd();
$sf_data_dir = get_data_dir($path);
$sf_data_web_dir = $sf_data_dir ? realpath($sf_data_dir.'/web/sf') : false;
echo "'web/sf' => ".var_export($sf_data_web_dir, true)."\n";
if ($sf_data_web_dir)
{
  if (is_link($path.'/web/sf'))
  {
    unlink($path.'/web/sf');
  }
  symlink($sf_data_web_dir, $path.'/web/sf');
}