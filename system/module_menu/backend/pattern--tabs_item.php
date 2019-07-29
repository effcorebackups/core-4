<?php

  ##################################################################
  ### Copyright © 2017—2019 Maxim Rysevets. All rights reserved. ###
  ##################################################################

namespace effcore {
          class tabs_item extends node {

  public $template = 'tabs_item';
  public $template_children = 'tabs_item_children';
  public $element_attributes = ['role' => 'tab'];
  public $id;
  public $id_parent;
  public $id_tab;
  public $title = '';
  public $action_name;
  public $action_name_default;
  public $is_hidden = false;
  public $access;

  function __construct($title = '', $id = null, $id_parent = null, $id_tab = null, $action_name = null, $action_name_default = null, $attributes = [], $element_attributes = [], $is_hidden = false, $weight = 0) {
    if ($id                 ) $this->id                  = $id;
    if ($id_parent          ) $this->id_parent           = $id_parent;
    if ($id_tab             ) $this->id_tab              = $id_tab;
    if ($title              ) $this->title               = $title;
    if ($action_name        ) $this->action_name         = $action_name;
    if ($action_name_default) $this->action_name_default = $action_name_default;
    if ($element_attributes ) $this->element_attributes  = $element_attributes;
    if ($is_hidden          ) $this->is_hidden           = $is_hidden;
    parent::__construct($attributes, [], $weight);
  }

  function build() {
    if (!$this->is_builded) {
      $this->attribute_insert('data-id', $this->id);
      foreach (static::select_all() as $c_item) {
        if ($c_item->id_parent == $this->id) {
          $this->child_insert($c_item, $c_item->id);
          $c_item->build();}}
      $this->is_builded = true;
    }
  }

  function render() {
    if (empty($this->is_hidden)) {
      if (access::check($this->access)) {
        $rendered_children = $this->children_select_count() ? (template::make_new($this->template_children, [
          'children' => $this->render_children($this->children_select())
        ]))->render() : '';
        return (template::make_new($this->template, [
          'attributes' => $this->render_attributes(),
          'self'       => $this->render_self(),
          'children'   => $rendered_children
        ]))->render();
      }
    }
  }

  function render_self() {
    $href         = rtrim(page::get_current()->args_get('base').'/'.($this->action_name         ?: $this->action_name), '/');
    $href_default = rtrim(page::get_current()->args_get('base').'/'.($this->action_name_default ?: $this->action_name), '/');
    if ($href && url::is_active      ($href, 'path')) {$this->attribute_insert('aria-selected',       'true', 'element_attributes');}
    if ($href && url::is_active_trail($href))         {$this->attribute_insert('data-selected-trail', 'true', 'element_attributes');}
    if ($href_default) $this->attribute_insert('href', $href_default, 'element_attributes');
    $this->attribute_insert('title', new text('Click to open the tab: %%_title', ['title' => translation::get($this->title)]), 'element_attributes');
    return (new markup('a', $this->attributes_select('element_attributes'),
      new text($this->title, [], true, true)
    ))->render();
  }

  ###########################
  ### static declarations ###
  ###########################

  static protected $cache;

  static function cache_cleaning() {
    static::$cache = null;
  }

  static function init() {
    if (static::$cache == null) {
      foreach (storage::get('files')->select('tabs_items') as $c_module_id => $c_tabs_items) {
        foreach ($c_tabs_items as $c_row_id => $c_item) {
          if (isset(static::$cache[$c_item->id])) console::log_insert_about_duplicate('tabs_item', $c_item->id, $c_module_id);
          static::$cache[$c_item->id] = $c_item;
          static::$cache[$c_item->id]->module_id = $c_module_id;
        }
      }
    }
  }

  static function select_all($id_tab = null, $id_parent = null, $is_skip_hidden = true) {
    static::init();
    $result = static::$cache ?? [];
    if ($id_tab)
      foreach ($result as $c_id => $c_item)
        if ($c_item->id_tab != $id_tab)
          unset($result[$c_id]);
    if ($id_parent)
      foreach ($result as $c_id => $c_item)
        if ($c_item->id_parent != $id_parent)
          unset($result[$c_id]);
    if ($is_skip_hidden)
      foreach ($result as $c_id => $c_item)
        if ($c_item->is_hidden == true)
          unset($result[$c_id]);
    return $result;
  }

  static function select($id) {
    static::init();
    return static::$cache[$id] ?? null;
  }

  static function insert($title, $id, $id_parent, $id_tab, $action_name, $action_name_default = null, $attributes = [], $element_attributes = [], $is_hidden = false, $weight = 0, $module_id = null) {
    static::init();
    $new_item = new static($title, $id, $id_parent, $id_tab, $action_name, $action_name_default, $attributes, $element_attributes, $is_hidden, $weight);
           static::$cache[$id] = $new_item;
           static::$cache[$id]->module_id = $module_id;
    return static::$cache[$id];
  }

  static function delete($id) {
    static::init();
    unset(static::$cache[$id]);
  }

}}