<?php

  ##################################################################
  ### Copyright © 2017—2018 Maxim Rysevets. All rights reserved. ###
  ##################################################################

namespace effectivecore\modules\develop {
          use \effectivecore\message as message;
          use \effectivecore\translation as translation;
          abstract class events_form extends \effectivecore\events_form {

  ##################
  ### form: demo ###
  ##################

  static function on_init_demo($form, $fields) {
    $fields['form_elements/select_macro']->option_insert('Option 2.5 (inserted + disabled from init)', 'option_2_5', ['disabled' => 'disabled'], 'group_2_1');
    $fields['form_elements/select_macro']->option_insert('Option 2.6 (inserted from init)', 'option_2_6', [], 'group_2_1');
    $fields['form_elements/select_macro']->optgroup_insert('group_2_2', 'Group 2.2 (inserted from init)');
    $fields['form_elements/select_macro']->option_insert('Option 2.7 (inserted from init)', 'option_2_7', [], 'group_2_2');
    $fields['form_elements/select_macro']->option_insert('Option 2.8 (inserted from init)', 'option_2_8', [], 'group_2_2');
    $fields['form_elements/select_macro']->option_insert('Option 2.9 (inserted from init)', 'option_2_9', [], 'group_2_2');
  }

  static function on_validate_demo($form, $fields, &$values) {
    static::on_validate($form, $fields, $values);
  }

  static function on_submit_demo($form, $fields, &$values) {
    static::on_submit_files($form, $fields, $values);
    message::insert(translation::get('Call %%_name', ['name' => '\\'.__METHOD__]));
    if (count($values['file'])) {
      foreach ($values['file'] as $hash => $c_file_info) {
        message::insert(translation::get('File %%_name was uploaded.', ['name' => $c_file_info->name]));
      }
    }
  }

}}