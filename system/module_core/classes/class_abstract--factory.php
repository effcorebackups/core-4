<?php

namespace effectivecore {
          abstract class factory {

  static function autoload($name) {
    $classes_map = static::get_classes_map();
    foreach ($classes_map as $c_class_name => $c_class_info) {
      if ($c_class_name == $name) {
        (new file($c_class_info->file))->insert();
      }
    }
  }

  static function send_header_and_exit($header, $message = '', $p = '') {
    switch ($header) {
      case 'redirect'      : header('Location: '.$p); break;
      case 'page_refresh'  : header('Refresh: '.$p); break;
      case 'access_denided': header('HTTP/1.1 403 Forbidden'); break;
      case 'not_found'     : header('HTTP/1.0 404 Not Found'); break;
    }
    if ($message) {
      print $message;
      print '<style>body{padding:30px;font-family:Arial;font-size:24px;text-align:center}</style>';
    }
    exit();
  }

  static function get_classes_map() {
    $cache = cache::get('classes_map');
    if ($cache) {
      return $cache;
    } else {
      $classes_map = [];
      $files = file::get_all(dir_modules, '%^.*\.php$%') +
               file::get_all(dir_system, '%^.*\.php$%');
      foreach ($files as $c_file) {
        $matches = [];
        preg_match('%namespace (?<namespace>[a-z0-9_\\\\]+) .*? '.
                        'class (?<classname>[a-z0-9_]+) (?:'.
                      'extends (?<parent>[a-z0-9_\\\\]+)|)%s', $c_file->load(), $matches);
        if (!empty($matches['namespace']) &&
            !empty($matches['classname'])) {
          $classes_map[$matches['namespace'].'\\'.$matches['classname']] = (object)[
            'namespace' => $matches['namespace'],
            'classname' => $matches['classname'],
            'parents'   => isset($matches['parent']) ? [ltrim($matches['parent'], '\\') => ltrim($matches['parent'], '\\')] : [],
            'file'      => $c_file->path_relative_full
          ];
        }
      }
      cache::set('classes_map', $classes_map);
      return $classes_map;
    }
  }



  static function class_invoke_up($method_name, $class_name = null) {
    $class_name = $class_name ?: get_called_class();
    $call_stack = [$class_name];
  # collect stack
    foreach (static::get_classes_map() as $c_class_name => $c_class_info) {
      if (isset($c_class_info->parents[$class_name])) {
        $c_reflection = new \ReflectionMethod($c_class_name, $method_name);
        if ($c_reflection->getDeclaringClass()->name == $c_class_name) {
          $call_stack[] = $c_class_name;
        }
      }
    }
  # call stack
    $return = [];
    foreach ($call_stack as $c_class_name) {
      $return[$c_class_name] = call_user_func($c_class_name.'::'.$method_name);
    }
    return $return;
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



  static function data_to_attr($data) {
    $buf = [];
    foreach ($data as $c_name => $c_value) {
      switch (gettype($c_value)) {
        case 'boolean': $buf[] = $c_name; break;
        case 'array'  : $buf[] = $c_name.'="'.implode(' ', $c_value).'"'; break;
        default       : $buf[] = $c_name.'="'.$c_value.'"'; break;
      }
    }
    return $buf;
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
        $return = $prefix.' = new \\'.get_class($data).'();'.nl;
        foreach ($data as $c_key => $c_value) {
          $return.= static::data_export($c_value, $prefix.'->'.$c_key);
        }
        break;
      case 'boolean': $return.= $prefix.' = '.($data ? 'true' : 'false').';'.nl; break;
      case 'string' : $return.= $prefix.' = \''.addslashes($data).'\';'.nl;      break;
      case 'NULL'   : $return.= $prefix.' = null;'.nl;                           break;
      default       : $return.= $prefix.' = '.$data.';'.nl;
    }
    return $return;
  }



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
    uasort($array, '\\effectivecore\\factory::_compare_by_weight');
    return $array;
  }



  static function collect_by_property($data, $prop_name, $prop_for_id = null) {
    $return = [];
    foreach ($data as $c_item) {
      if (isset($c_item->{$prop_name})) {
        $return[$prop_for_id ? $c_item->{$prop_for_id} : count($return)] = $c_item->{$prop_name};
      }
    }
    return $return;
  }

  static function collect_content($data) {
    $return = [];
    foreach ($data as $c_key => $c_item) {
      $return[$c_key] = $c_item;
      if (isset($c_item->content)) {
        $return += static::collect_content($c_item->content);
      }
    }
    return $return;
  }



  static function _compare_by_weight($a, $b) {
    return $a->weight == $b->weight ? 0 : ($a->weight < $b->weight ? -1 : 1);
  }

}}