<?php

  ##################################################################
  ### Copyright © 2017—2020 Maxim Rysevets. All rights reserved. ###
  ##################################################################

namespace effcore {
          class widget_area_parts extends widget_fields {

  public $attributes = ['data-type' => 'fields-info-area_parts'];
  public $name_complex = 'widget_area_parts';
  public $item_title = 'Part';
  public $id_area;

  function __construct($id_area, $attributes = [], $weight = 0) {
    $this->id_area = $id_area;
    parent::__construct($attributes, $weight);
  }

  function widget_manage_get($item, $c_row_id) {
    $widget = parent::widget_manage_get($item, $c_row_id);
  # data markup
    $preset = part_preset::select($item->id);
    $data_markup = new markup('x-info',  [], [
        'title' => new markup('x-title', [], $preset ? [$preset->managing_group, ': ', $preset->managing_title] : 'LOST PART'),
        'id'    => new markup('x-id',    [], new text_simple($item->id) ) ]);
  # group the previous elements in widget 'manage'
    $widget->child_insert($data_markup, 'data');
    return $widget;
  }

  function widget_insert_get() {
    $widget = new markup('x-widget', [
      'data-type' => 'insert']);
  # field for selection of the type of new item
    $presets = part_preset::select_all($this->id_area);
    core::array_sort_by_text_property($presets, 'managing_group');
    $options = ['not_selected' => '- no -'];
    foreach ($presets as $c_preset) {
      $c_group_id = core::sanitize_id($c_preset->managing_group);
      if (!isset($options[$c_group_id])) {
                 $options[$c_group_id] = new \stdClass;
                 $options[$c_group_id]->title = $c_preset->managing_group;}
      $options[$c_group_id]->values[$c_preset->id] = translation::get($c_preset->managing_title).' ('.$c_preset->id.')';
    }
    foreach ($options as $c_group) {
      if ($c_group instanceof \stdClass) {
        core::array_sort_text($c_group->values);
      }
    }
    $select = new field_select('Insert part');
    $select->values = $options;
    $select->build();
    $select->name_set($this->name_prefix.'__insert');
    $select->required_set(false);
    $this->_fields['insert'] = $select;
  # button for insertion of the new item
    $button = new button(null, ['data-style' => 'narrow-insert', 'title' => new text('insert')]);
    $button->break_on_validate = true;
    $button->build();
    $button->value_set($this->name_prefix.'__insert');
    $button->_type = 'insert';
    $this->_buttons['insert'] = $button;
  # group the previous elements in widget 'insert'
    $widget->child_insert($select, 'select');
    $widget->child_insert($button, 'button');
    return $widget;
  }

  # ─────────────────────────────────────────────────────────────────────

  function on_button_click_insert($form, $npath, $button) {
    $new_value = $this->_fields['insert']->value_get();
    if ($new_value) {
      $min_weight = 0;
      $items = $this->items_get();
      foreach ($items as $c_row_id => $c_item)
        $min_weight = min($min_weight, $c_item->weight);
      $new_item = new part_preset_link($new_value);
      $new_item->weight = count($items) ? $min_weight - 5 : 0;
      $items[] = $new_item;
      $this->items_set($items);
      $this->_fields['insert']->value_set('');
      message::insert(new text_multiline([
        'Item of type "%%_type" was inserted.',
        'Do not forget to save the changes with "%%_button" button!'], [
        'type'   => translation::get($this->item_title),
        'button' => translation::get('update')]));
      return true;
    } else {
      $this->_fields['insert']->error_set(
        'Field "%%_title" must be selected!', ['title' => translation::get($this->_fields['insert']->title)]
      );
    }
  }

}}