<?php

namespace effectivecore\modules\data {
          use \effectivecore\settings;
          use \effectivecore\html;
          use \effectivecore\factory;
          use \effectivecore\console;
          use \effectivecore\modules\page\page;
          abstract class events extends \effectivecore\events {

  static function on_init() {
    $is_init = db::init(
      settings::$data['db']['module_data']->prod->driver,
      settings::$data['db']['module_data']->prod->host,
      settings::$data['db']['module_data']->prod->database_name,
      settings::$data['db']['module_data']->prod->username,
      settings::$data['db']['module_data']->prod->password,
      settings::$data['db']['module_data']->prod->table_prefix
    );
    if (!$is_init) {
      factory::send_header_and_exit('access_denided',
        'Database is unavailable!'
      );
    }
  }

}}