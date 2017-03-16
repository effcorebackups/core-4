<?php

namespace effectivecore {
          abstract class core {

  static function init() {
    require_once('class_abstract--cache.php');
    require_once('class_abstract--factory.php');
    require_once('class--file.php');
    spl_autoload_register('\effectivecore\factory::autoload');
  # classes initialization
    settings::init();
    token::init();
    url::init();
  # redirect from '/any_path/' to '/any_path'
    if (url::$current->path != '/' && substr(url::$current->path, -1) == '/') {
      $right_url = clone url::$current;
      $right_url->path = rtrim($right_url->path, '/');
      url::go($right_url->full());
    }
  # single entry point
    $file_types = [];
    foreach (settings::$data['file_types'] as $c_types) {
      foreach ($c_types as $c_type_name => $c_type_info) {
        $file_types[$c_type_name] = $c_type_info;
      }
    }
    $ext = url::$current->extension();
    if ($ext) {
      if (!empty($file_types[$ext]->protected)) {
      # file existence is not checking - show access denied messge if url has any protected extension
        factory::send_header_and_exit('access_denided',
          'Any file with this extension is protected by settings in file_types!'
        );
      }
      $path = dir_root.url::$current->path;
      if (is_file($path) && is_readable($path)) {
        $data = (new file($path))->load();
        if (isset($file_types[$ext]->mime)) header('Content-type: '.$file_types[$ext]->mime, true);
        if (isset($file_types[$ext]->use_tokens)) $data = token::replace($data);
        print $data;
        exit();
      }
    }
  # init modules
    ob_start();
    $call_stack = factory::array_collect_by_property(settings::$data['module'], 'on_init', 'id');
    foreach (factory::array_sort_by_weight($call_stack) as $module_id => $c_event) {
      console::set_log('Module ID = '.$module_id, $c_event->handler, 'Init calls');
      call_user_func($c_event->handler);
    }
  }

}}