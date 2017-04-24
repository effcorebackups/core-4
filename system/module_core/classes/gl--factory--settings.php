<?php

namespace effectivecore {
          use \effectivecore\files_factory as files;
          use \effectivecore\parser_factory as parser;
          abstract class settings_factory {

  static $data;

  static function init() {
    $file = new file(dir_cache.'settings.php');
    if ($file->is_exist()) $file->insert();
    else static::_update();
  }

  static protected function _update() {
    $parse = [];
    $files = files::get_all(dir_modules, '%^.*\.data$%') +
             files::get_all(dir_system, '%^.*\.data$%');
    $modules = [];
    foreach ($files as $c_file) {
      if ($c_file->get_file_full() == 'module.data') {
        $modules[$c_file->get_dir_parent()] = $c_file->get_dirs_relative();
      }
    }
    foreach ($files as $c_file) {
      $c_scope = 'global';
      foreach ($modules as $c_module_id => $c_module_path) {
        if (strpos($c_file->get_dirs_relative(), $c_module_path) === 0) {
          $c_scope = $c_module_id;
          break;
        }
      }
      foreach (parser::parse_settings($c_file->load()) as $c_type => $c_data) {
        if (is_object($c_data)) {
          if ($c_type == 'module') $c_data->path = $modules[$c_scope];
          $parse[$c_type][$c_scope] = $c_data;
        }
        if (is_array($c_data)) {
          if (!isset($parse[$c_type][$c_scope])) $parse[$c_type][$c_scope] = [];
          $parse[$c_type][$c_scope] += $c_data;
        }
      }
    }
    $file = new file(dir_cache.'settings.php');
    $file->set_data(
      "<?php \n\nnamespace effectivecore { # settings_factory::\$data[type][scope]...\n\n".
         factory::data_export($parse, '  settings_factory::$data').
      "\n}");
    $file->save();
    factory::send_header_and_exit('page_refresh',
      'Make cache directory writable if you see this message!', 0
    );
  }

}}