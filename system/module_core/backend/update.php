<?php

  ##################################################################
  ### Copyright © 2017—2021 Maxim Rysevets. All rights reserved. ###
  ##################################################################

namespace effcore {
          abstract class update {

  static function select_all($module_id, $from_number = 0) {
    $updates = [];
    foreach (storage::get('files')->select('modules_update_data', false, false) ?? [] as $c_module_id => $c_updates)
      if ($c_module_id === $module_id)
        foreach ($c_updates as $c_row_id => $c_update)
          if ($c_update->number >= $from_number)
            $updates[$c_row_id] = $c_update;
    return $updates;
  }

  static function select_last_number($module_id) {
    $settings = module::settings_get($module_id);
    return $settings->update_data_last_number ?? 0;
  }

  static function is_required() {
    foreach (module::get_all() as $c_module) {
      $c_updates            = static::select_all        ($c_module->id);
      $c_update_last_number = static::select_last_number($c_module->id);
      foreach ($c_updates as $c_update) {
        if ($c_update->number > $c_update_last_number) return true;
      }
    }
  }

}}