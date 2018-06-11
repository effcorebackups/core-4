<?php

  ##################################################################
  ### Copyright © 2017—2018 Maxim Rysevets. All rights reserved. ###
  ##################################################################

namespace effcore {
          class entity
          implements has_external_cache, has_post_parsing {

  public $name;
  public $storage_id;
  public $catalog_id;
  public $ws_created;
  public $ws_updated;
  public $title;
  public $fields = [];
  public $constraints = [];
  public $indexes = [];

  function __post_parsing() {
  # add field "created" and index for it
    if ($this->ws_created) {
      $this->fields->created = new \stdClass;
      $this->fields->created->type = 'datetime';
      $this->fields->created->not_null = true;
      $this->indexes['idx_created'] = new \stdClass;
      $this->indexes['idx_created']->type = 'index';
      $this->indexes['idx_created']->fields = ['created' => 'created'];
    }
  # add field "updated" and index for it
    if ($this->ws_updated) {
      $this->fields->updated = new \stdClass;
      $this->fields->updated->type = 'datetime';
      $this->fields->updated->not_null = true;
      $this->indexes['idx_updated'] = new \stdClass;
      $this->indexes['idx_updated']->type = 'index';
      $this->indexes['idx_updated']->fields = ['updated' => 'updated'];
    }
  }

  function get_name()             {return $this->name;}
  function get_storage_id()       {return $this->storage_id;}
  function get_catalog_id()       {return $this->catalog_id;}
  function get_field_info($name)  {return $this->fields->{$name};}
  function get_fields_info()      {return $this->fields;}
  function get_indexes_info()     {return $this->indexes;}
  function get_constraints_info() {return $this->constraints;}
  function get_fields() {
    return core::array_kmap(
      array_keys((array)$this->fields)
    );
  }

  function get_auto_name() {
    foreach ($this->fields as $name => $info) {
      if ($info->type == 'autoincrement') {
        return $name;
      }
    }
  }

  function get_keys($primary = true, $unique = true) {
    $keys = [];
    foreach ($this->constraints as $c_cstr) {
      if (($c_cstr->type == 'primary key' && $primary) ||
          ($c_cstr->type == 'unique'      && $unique)) {
        $keys += $c_cstr->fields;
      }
    }
    return core::array_kmap($keys);
  }

  function install() {
    $storage = storage::get($this->get_storage_id());
    return $storage->install_entity($this);
  }

  function uninstall() {
    $storage = storage::get($this->get_storage_id());
    return $storage->uninstall_entity($this);
  }

  function instances_select($conditions = [], $order = [], $count = 0, $offset = 0) {
    $storage = storage::get($this->get_storage_id());
    return $storage->instances_select($this, $conditions, $order, $count, $offset);
  }

  function instances_insert() {} # todo: make functionality
  function instances_delete() {} # todo: make functionality

  ###########################
  ### static declarations ###
  ###########################

  static protected $cache;
  static protected $cache_orig;

  static function not_external_properties_get() {
    return [
      'name'       => 'name',
      'storage_id' => 'storage_id',
      'catalog_id' => 'catalog_id',
      'title'      => 'title'
    ];
  }

  static function init($name = '') {
    static::$cache_orig = storage::get('files')->select('entities');
    foreach (static::$cache_orig as $c_module_id => $c_entities) {
      foreach ($c_entities as $c_row_id => $c_entity) {
        if ($name == '' || (
            $name && $name == $c_entity->name)) {
          if ($c_entity instanceof external_cache)
              $c_entity = $c_entity->external_cache_load();
          if (isset(static::$cache[$c_entity->name])) console::add_log_about_duplicate('entity', $c_entity->name);
          static::$cache[$c_entity->name] = $c_entity;
          static::$cache[$c_entity->name]->module_id = $c_module_id;
        }
      }
    }
  }

  static function get($name) {
    if (!isset(static::$cache[$name])) static::init($name);
    return     static::$cache[$name];
  }

  static function all_get() {
    if   (!static::$cache) static::init();
    return static::$cache;
  }

  static function all_by_module_get($module) {
    if   (!static::$cache_orig) static::init();
    return static::$cache_orig[$module];
  }

}}