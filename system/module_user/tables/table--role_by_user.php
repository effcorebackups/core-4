<?php

namespace effectivecore\modules\user {
          use \effectivecore\core;
          abstract class table_role_by_user extends \effectivecore\modules\data\db_table {

  static $table_name = 'role_by_user';
  static $fields = [
    'role_id' => ['type' => 'varchar(255)', 'not null', 'primary key' => true],
    'user_id' => ['type' => 'int(11)', 'unsigned', 'not null', 'primary key' => true],
  ];

  static function install() {
    parent::install();
    static::insert(['user_id' => 1, 'role_id' => 'admins']);
  }

}}