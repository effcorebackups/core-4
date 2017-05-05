<?php

namespace effectivecore {

  const format_date     = 'Y-m-d';
  const format_time     = 'H:i:s';
  const format_datetime = 'Y-m-d H:i:s';
  const dir_root        = __DIR__.'/';
  const dir_cache       = __DIR__.'/dynamic/cache/';
  const dir_modules     = __DIR__.'/modules/';
  const dir_system      = __DIR__.'/system/';
  const nl              = "\n";
  const br              = "<br/>";

  require_once('system/module_core/classes/gl--factory--events.php');
  require_once('system/module_core/classes/gl--factory--events_module.php');
  events_module_factory::on_init();

# init storage
  $storage = \effectivecore\modules\storage\storage_factory::get_instance('db_main');
# init instance
  $instance = new entity_instance('entities/user/user', ['id' => 1]);
  $storage->select_instance($instance);
  print_R( $instance );

}