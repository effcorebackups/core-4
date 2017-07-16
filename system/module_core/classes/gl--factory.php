<?php

namespace effectivecore {
          use \effectivecore\file_factory as files;
          use \effectivecore\cache_factory as caches;
          use \effectivecore\timer_factory as timers;
          use \effectivecore\console_factory as console;
          abstract class factory {

  static $cache;

  #############################
  ### classes manipulations ###
  #############################

  static function autoload($name) {
    foreach (static::get_classes_map() as $c_class_name => $c_class_info) {
      if ($c_class_name == $name) {
        $c_file = new file($c_class_info->file);
        $c_file->insert();
      }
    }
  }

  static function get_classes_map() {
    $cache = caches::get('classes_map');
    if ($cache) {
      return $cache;
    } else {
      $classes_map = [];
      $files = files::get_all(dir_modules, '%^.*\.php$%') +
               files::get_all(dir_system, '%^.*\.php$%');
      foreach ($files as $c_file) {
        $matches = [];
        preg_match('%namespace (?<namespace>[a-z0-9_\\\\]+).*?'.
                        'class (?<classname>[a-z0-9_]+) (?:'.
                      'extends (?<parent>[a-z0-9_\\\\]+)|)%sS', $c_file->load(), $matches);
        if (!empty($matches['namespace']) &&
            !empty($matches['classname'])) {
          $classes_map[$matches['namespace'].'\\'.$matches['classname']] = (object)[
            'namespace' => $matches['namespace'],
            'classname' => $matches['classname'],
            'parent'    => isset($matches['parent']) ? ltrim($matches['parent'], '\\') : '',
            'file'      => $c_file->get_path_relative()
          ];
        }
      }
      caches::set('classes_map', $classes_map);
      return $classes_map;
    }
  }

  static function class_get_parts($class_name) {
    return explode('\\', $class_name);
  }

  static function class_is_local($class_name) {
    $parts = static::class_get_parts($class_name);
    return $parts[0] === __NAMESPACE__;
  }

  static function class_get_short_name($class_name) {
    $parts = static::class_get_parts($class_name);
    return end($parts);
  }

  ##########################
  ### data manipulations ###
  ##########################

  static function data_to_attr($data, $join_part = ' ', $key_wrapper = '', $value_wrapper = '"') {
    $return = [];
    foreach ((array)$data as $c_name => $c_value) {
      switch (gettype($c_value)) {
        case 'boolean': $return[] = $key_wrapper.$c_name.$key_wrapper; break;
        case 'array'  : $return[] = $key_wrapper.$c_name.$key_wrapper.'='.$value_wrapper.implode(' ', $c_value).$value_wrapper; break;
        case 'object' : $return[] = $key_wrapper.$c_name.$key_wrapper.'='.$value_wrapper.(method_exists($c_value, 'render') ? $c_value->render() : '').$value_wrapper; break;
        default       : $return[] = $key_wrapper.$c_name.$key_wrapper.'='.$value_wrapper.$c_value.$value_wrapper; break;
      }
    }
    return $join_part ? implode($join_part, $return) :
                                            $return;
  }

  static function data_export($data, $prefix = '') {
    $return = '';
    switch (gettype($data)) {
      case 'array':
        if (count($data)) {
          foreach ($data as $c_key => $c_value) {
            $return.= static::data_export($c_value, $prefix.(is_int($c_key) ? '['.$c_key.']' : '[\''.$c_key.'\']'));
          }
        } else {
          $return.= $prefix.' = [];'.nl;
        }
        break;
      case 'object':
        $reflection = new \ReflectionClass(get_class($data));
        $defs = $reflection->getDefaultProperties();
        $return = $prefix.' = new \\'.get_class($data).'();'.nl;
        foreach ($data as $c_prop => $c_value) {
          if (array_key_exists($c_prop, $defs) && $defs[$c_prop] === $c_value) continue;
          $return.= static::data_export($c_value, $prefix.'->'.$c_prop);
        }
        break;
      case 'boolean': $return.= $prefix.' = '.($data ? 'true' : 'false').';'.nl;                    break;
      case 'NULL'   : $return.= $prefix.' = null;'.nl;                                              break;
      case 'string' : $return.= $prefix.' = \''.str_replace('\"', '"', addslashes($data)).'\';'.nl; break;
      default       : $return.= $prefix.' = '.$data.';'.nl;
    }
    return $return;
  }

  ###########################
  ### array manipulations ###
  ###########################

  static function array_rotate($data) {
    $return = [];
    foreach ($data as $c_row) {                  # convert: |1|2| to: |1|3|
      for ($i = 0; $i < count($c_row); $i++) {   #          |3|4|     |2|4|
        $return[$i][] = $c_row[$i];
      }
    }
    return $return;
  }

  static function array_sort_by_weight(&$array) {
  # prepare array for stable sorting when weight = 0
    $c_weight = 0;
    foreach ($array as $c_item) {
      if ($c_item->weight == 0) {
        $c_item->weight = ($c_weight += .0002);
      }
    }
  # sorting
    uasort($array, '\\effectivecore\\factory::_compare_by_weight');
    return $array;
  }

  #######################
  ### npath functions ###
  #######################

  static function npath_get_info($npath) {
    $npath_parts = explode('/', $npath);
    return (object)[
      'id'           => array_pop($npath_parts),
      'parent_npath' => implode('/', $npath_parts),
    ];
  }

  static function &npath_get_pointer($npath, &$p, $reset = false) {
    if (!$reset) {
      if (isset(static::$cache[__FUNCTION__][$npath])) {
         return static::$cache[__FUNCTION__][$npath];
      }
    }
    foreach (explode('/', $npath) as $c_part) {
      switch (gettype($p)) {
        case 'array' : $p = &$p[$c_part];   break;
        case 'object': $p = &$p->{$c_part}; break;
      }
    }
    static::$cache[__FUNCTION__][$npath] = &$p;
    return $p;
  }

  static function npath_get_object($npath, $data, $reset = false) {
    if (!$reset) {
      if (isset(static::$cache[__FUNCTION__][$npath]))
         return static::$cache[__FUNCTION__][$npath];
    }
    $path_parts = explode('/', $npath);
    $p = null;
    foreach ($path_parts as $c_part) {
      if ($p == null) { if (isset($data[$c_part])) {$p = $data[$c_part]; continue;} else {$p = null; break;} } # iteration 1
      if ($p != null) { if (isset(   $p[$c_part])) {$p =    $p[$c_part]; continue;} else {$p = null; break;} } # iteration 2, 3, 4 …
    }
    static::$cache[__FUNCTION__][$npath] = $p;
    return $p;
  }

  ########################
  ### shared functions ###
  ########################

  static function send_header_and_exit($header, $message = '', $p = '') {
    switch ($header) {
      case 'redirect'      : header('Location: '.$p);          break;
      case 'page_refresh'  : header('Refresh: ' .$p);          break;
      case 'access_denided': header('HTTP/1.1 403 Forbidden'); break;
      case 'not_found'     : header('HTTP/1.0 404 Not Found'); break;
    }
    if ($message) {
      print $message;
      print '<style>'.
              'body {padding: 30px; font-family: Arial; font-size: 24px; text-align: center}'.
            '</style>';
    }
    exit();
  }

  static function _compare_by_weight($a, $b) {
    return $a->weight == $b->weight ? 0 : ($a->weight < $b->weight ? -1 : 1);
  }

  static function to_css_class($string) {
    return str_replace(['/', ' '], '-', strtolower($string));
  }

}}