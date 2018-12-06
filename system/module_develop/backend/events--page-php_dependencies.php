<?php

  ##################################################################
  ### Copyright © 2017—2019 Maxim Rysevets. All rights reserved. ###
  ##################################################################

namespace effcore\modules\develop {
          use const \effcore\dir_root;
          use \effcore\block;
          use \effcore\file;
          use \effcore\markup;
          use \effcore\module;
          use \effcore\table_body_row_cell;
          use \effcore\table_body_row;
          use \effcore\table;
          use \effcore\text_multiline;
          use \effcore\text_simple;
          abstract class events_page_php_dependencies {

  static function on_show_block_php_dependencies_list($page) {
  # collect information about native php functions
    $used_funcs = [];
    foreach (get_loaded_extensions() as $c_extension) {
      foreach (get_extension_funcs($c_extension) ?: [] as $c_function) {
        $used_funcs[$c_function] = $c_extension;
      }
    }
  # collect information about paths of modules in the system
    $paths = [];
    foreach (module::all_get() as $c_module) {
      $paths[$c_module->id] = $c_module->path;
    }
    arsort($paths);
  # scan each php file on used functions
    $statistic = [];
    $php_files = file::select_recursive(dir_root, '%^.*\\.php$%');
    foreach ($php_files as $c_file) {
      $c_matches = [];
      preg_match_all('%(?<![a-z0-9_])(?<name>[a-z0-9_]+)\\(%isS', $c_file->load(), $c_matches, PREG_OFFSET_CAPTURE);
      if ($c_matches) {
        foreach ($c_matches['name'] as $c_match) {
          if (isset($used_funcs[$c_match[0]])) {
            $c_extension = $used_funcs[$c_match[0]];
            $c_function = $c_match[0];
            $c_position = $c_match[1];
            $statistic[$c_extension][$c_function][] = (object)[
              'file'     => $c_file->path_relative_get(),
              'position' => $c_position
            ];
          }
        }
      }
    }
    ksort($statistic);
  # prepare report
    $thead = [['Ext.', 'Function', 'File', 'Pos.']];
    $tbody = [];
    foreach ($statistic as $c_extension => $c_functions) {
      foreach ($c_functions as $c_function => $c_positions) {
        foreach ($c_positions as $c_position_info) {
          $tbody[] = new table_body_row([], [
            new table_body_row_cell(['class' => ['extension' => 'extension']], new text_simple($c_extension)),
            new table_body_row_cell(['class' => ['function'  => 'function' ]], new text_simple($c_function)),
            new table_body_row_cell(['class' => ['file'      => 'file'     ]], new text_simple($c_position_info->file)),
            new table_body_row_cell(['class' => ['position'  => 'position' ]], new text_simple($c_position_info->position))
          ]);
        }
      }
    }
    return new block('', ['class' => ['php-dependencies' => 'php-dependencies']], [
      new markup('p', [], new text_multiline(['The report was generated in real time.', 'The system can search for the used functions only for enabled PHP modules!'])),
      new table(['class' => ['compact' => 'compact']], $tbody, $thead)
    ]);
  }

}}