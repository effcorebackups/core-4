<?php

  ##################################################################
  ### Copyright © 2017—2018 Maxim Rysevets. All rights reserved. ###
  ##################################################################

namespace effcore {
          class field_time extends field_text {

  const input_min_time = '00:00:00';
  const input_max_time = '23:59:59';

  public $title = 'Time';
  public $attributes = ['x-type' => 'time'];
  public $element_attributes_default = [
    'type'     => 'time',
    'name'     => 'time',
    'required' => 'required',
    'min'      => self::input_min_time,
    'max'      => self::input_max_time,
    'step'     => 60
  ];

  function build() {
    $this->attribute_insert('value', core::time_get(), 'element_attributes_default');
    parent::build();
  }

  ###########################
  ### static declarations ###
  ###########################

  static function value_min_get($element) {$min = $element->attribute_select('min') !== null ? $element->attribute_select('min') : self::input_min_time; return strlen($min) == 5 ? $min.':00' : $min;}
  static function value_max_get($element) {$max = $element->attribute_select('max') !== null ? $element->attribute_select('max') : self::input_max_time; return strlen($max) == 5 ? $max.':00' : $max;}

  static function validate($field, $form) {
    $element = $field->child_select('element');
    $name = $field->element_name_get();
    $type = $field->element_type_get();
    if ($name && $type) {
      if (static::is_disabled($field, $element)) return true;
      if (static::is_readonly($field, $element)) return true;
      $cur_index = static::cur_index_get($name);
      $new_value = static::request_value_get($name, $cur_index, $form->source_get());
      $new_value = strlen($new_value) == 5 ? $new_value.':00' : $new_value;
      $result = static::validate_required ($field, $form, $element, $new_value) &&
                static::validate_minlength($field, $form, $element, $new_value) &&
                static::validate_maxlength($field, $form, $element, $new_value) &&
                static::validate_value    ($field, $form, $element, $new_value) &&
                static::validate_min      ($field, $form, $element, $new_value) &&
                static::validate_max      ($field, $form, $element, $new_value) &&
                static::validate_pattern  ($field, $form, $element, $new_value);
      $field->value_set($new_value);
      return $result;
    }
  }

  static function validate_value($field, $form, $element, &$new_value) {
    if (!core::validate_time($new_value)) {
      $field->error_add(
        translation::get('Field "%%_title" contains an incorrect time!', ['title' => translation::get($field->title)])
      );
    } else {
      return true;
    }
  }

}}