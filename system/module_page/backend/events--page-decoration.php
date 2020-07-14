<?php

  ##################################################################
  ### Copyright © 2017—2020 Maxim Rysevets. All rights reserved. ###
  ##################################################################

namespace effcore\modules\page {
          use \effcore\color;
          use \effcore\core;
          use \effcore\layout;
          use \effcore\page;
          use \effcore\tab_item;
          use \effcore\url;
          abstract class events_page_decoration {

  static function on_redirect($event, $page) {
    $type = $page->args_get('type');
    $id   = $page->args_get('id');
    if ($type === null            ) {                                                                                                           url::go($page->args_get('base').'/colors/current'                     );}
    if ($type === 'colors'        ) {                                                                                                           url::go($page->args_get('base').'/colors/current'                     );}
    if ($type === 'colors/presets') {$presets = color::preset_get_all(); core::array_sort_by_text_property($presets); if (empty($presets[$id])) url::go($page->args_get('base').'/colors/presets/'.reset($presets)->id);}
    if ($type === 'layouts'       ) {$layouts = layout::select_all   (); core::array_sort_by_text_property($layouts); if (empty($layouts[$id])) url::go($page->args_get('base').'/layouts/'.       reset($layouts)->id);}
  }

  static function on_tab_build_before($event, $tab) {
    $type = page::get_current()->args_get('type');
  # colors presets
    if ($type === 'colors/presets') {
      $presets = color::preset_get_all();
      core::array_sort_by_text_property($presets);
      foreach ($presets as $c_preset) {
        tab_item::insert(                                              $c_preset->title,
          'decoration_colors_presets_'.                                $c_preset->id,
          'decoration_colors_presets', 'decoration', 'colors/presets/'.$c_preset->id
        );
      }
    }
  # layouts
    if ($type === 'layouts') {
      $layouts = layout::select_all();
      core::array_sort_by_text_property($layouts);
      foreach ($layouts as $c_layout) {
        tab_item::insert(                                $c_layout->title,
          'decoration_layouts_'.                         $c_layout->id,
          'decoration_layouts', 'decoration', 'layouts/'.$c_layout->id
        );
      }
    }
  }

}}