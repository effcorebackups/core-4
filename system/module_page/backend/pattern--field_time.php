<?php

  ##################################################################
  ### Copyright © 2017—2018 Maxim Rysevets. All rights reserved. ###
  ##################################################################

namespace effcore {
          class field_time extends field {

  public $title = 'Time';
  public $attributes = ['x-type' => 'time'];
  public $element_attributes_default = [
    'type'     => 'time',
    'name'     => 'time',
    'required' => 'required',
    'min'      => form::input_min_time,
    'max'      => form::input_max_time,
    'step'     => 60
  ];

  function build() {
    $this->attribute_insert('value', factory::time_get(), 'element_attributes_default');
    parent::build();
  }

}}