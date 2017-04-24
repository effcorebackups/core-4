<?php

namespace effectivecore\modules\storage {
          use \effectivecore\factory;
          use \effectivecore\settings_factory as settings;
          use \effectivecore\modules\storage\db_factory as db;
          use \effectivecore\modules\storage\storage_factory as storage;
          abstract class events_module extends \effectivecore\events_module_factory {

  static function on_init() {
    storage::init();
  # old code
    $is_init = db::init(
      settings::$data['db']['storage']->prod->driver,
      settings::$data['db']['storage']->prod->host,
      settings::$data['db']['storage']->prod->database_name,
      settings::$data['db']['storage']->prod->username,
      settings::$data['db']['storage']->prod->password,
      settings::$data['db']['storage']->prod->table_prefix
    );
    if (!$is_init) {
      factory::send_header_and_exit('access_denided',
        'Database is unavailable!'
      );
    }
  }

}}