<?php

  ##################################################################
  ### Copyright © 2017—2018 Maxim Rysevets. All rights reserved. ###
  ##################################################################

  if (version_compare(phpversion(), '5.6.0', '<')) {
    print 'Current version of PHP is '.phpversion().'. Required version 5.6.0+';
  } else {
    $www_root = DIRECTORY_SEPARATOR == '\\' ? str_replace('\\', '/', __DIR__) : __DIR__;
    define('effcore\\dir_root',    $www_root.'/');
    define('effcore\\dir_cache',   $www_root.'/dynamic/cache/');
    define('effcore\\dir_dynamic', $www_root.'/dynamic/');
    define('effcore\\dir_modules', $www_root.'/modules/');
    define('effcore\\dir_system',  $www_root.'/system/');
    require_once('system/boot.php');
  }
