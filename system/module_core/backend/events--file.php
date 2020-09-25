<?php

  ##################################################################
  ### Copyright © 2017—2021 Maxim Rysevets. All rights reserved. ###
  ##################################################################

namespace effcore\modules\core {
          use const \effcore\dir_system;
          use const \effcore\nl;
          use \effcore\console;
          use \effcore\core;
          use \effcore\file;
          use \effcore\locale;
          use \effcore\media;
          use \effcore\module;
          use \effcore\timer;
          use \effcore\token;
          use \effcore\url;
          abstract class events_file {

  static function on_load_dynamic($event, $type_info, &$file) {
    $data = token::apply($file->load());
    $etag = core::hash_get_etag($data);

  # send header '304 Not Modified' if the data has no changes
    if (isset($_SERVER['HTTP_IF_NONE_MATCH']) &&
              $_SERVER['HTTP_IF_NONE_MATCH'] === $etag) {
      header('HTTP/1.1 304 Not Modified');
      console::log_store();
      exit();
    }

  # send result data
    $result = $data;
    timer::tap('total');
    if (module::is_enabled('test')) {
      header('X-Time-total: '.locale::format_msecond(
        timer::period_get('total', 0, 1)
      ));
    }
    if ($file->type === 'cssd' ||
        $file->type === 'jsd') {
      if (console::visible_mode_get() === console::visible_for_everyone) {
        $result.= nl.'/*'.nl.console::text_get().nl.'*/'.nl;
      }
    }
    header('Content-Length: '.strlen($result));
    header('Cache-Control: private, no-cache');
    header('Accept-Ranges: none');
    header('Etag: '.$etag);
    if (!empty($type_info->headers)) {
      foreach ($type_info->headers as $c_key => $c_value) {
        header($c_key.': '.$c_value);
      }
    }
    print $result;
    console::log_store();
    exit();
  }

  # range support:
  # ┌────────────────────────────────────────┬───┐
  # │ header                                 │   │
  # ╞════════════════════════════════════════╪═══╡
  # │ Range: bytes=int-                      │ + │
  # │ Range: bytes=int-int                   │ + │
  # │ Range: bytes=int-int, int-int          │ - │
  # │ Range: bytes=int-int, int-int, int-int │ - │
  # │ Range: bytes=-<-length>                │ - │
  # └────────────────────────────────────────┴───┘

  # http ranges limits:
  # ─────────────────────────────────────────────────────────────────────
  #
  #    ┌┬┬┬┬┬┬┬┬┐
  #    ┝┷┷┷┷┷┷┷┷┿━━━━━━━━━━━━━━━━━━━━━┥
  #   0│min     │max                  │length
  #
  #
  #               ┌┬┬┬┬┬┬┬┬┐
  #    ┝━━━━━━━━━━┿┷┷┷┷┷┷┷┷┿━━━━━━━━━━┥
  #   0│       min│        │max       │length
  #
  #
  #                         ┌┬┬┬┬┬┬┬┬┐
  #    ┝━━━━━━━━━━━━━━━━━━━━┿┷┷┷┷┷┷┷┷┿┥
  #   0│                 min│     max││length
  #
  #
  # .....................................................................
  #
  #    0 ≤ min ≤ max < length
  #
  # ─────────────────────────────────────────────────────────────────────

  static function on_load_static($event, $type_info, &$file) {
    $last_modified = gmdate('D, d M Y H:i:s', filemtime($file->path_get())).' GMT';

  # send header '304 Not Modified' if the data has not changed
    if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) &&
              $_SERVER['HTTP_IF_MODIFIED_SINCE'] === $last_modified) {
      header('HTTP/1.1 304 Not Modified');
      console::log_store();
      exit();
    }

  # ranges
    $length = filesize($file->path_get());
    $ranges = core::server_get_http_range();
    if ($ranges->has_range) {
      $min = $ranges->min;
      $max = $ranges->max;
      if ($min === null) {header('HTTP/1.1 416 Requested Range Not Satisfiable'); exit();}
      if ($max === null || $max >= $length) $max = $length - 1;
      if (!(0 <= $min &&
                 $min <= $max &&
                         $max < $length)) {header('HTTP/1.1 416 Requested Range Not Satisfiable'); exit();}
      header('HTTP/1.1 206 Partial Content');
      header('Content-Range: bytes '.$min.'-'.$max.'/'.$length);
    } else {
      $min = 0;
      $max = $length - 1;
    }

  # send headers
    header('Content-Length: '.($max - $min + 1));
    header('Accept-Ranges: bytes');
    header('Cache-Control: private, no-cache');
    header('Last-Modified: '.$last_modified);
    if (!empty($type_info->headers)) {
      foreach ($type_info->headers as $c_key => $c_value) {
        header($c_key.': '.$c_value);
      }
    }

  # send result data
    if ($resource = fopen($file->path_get(), 'rb')) {
      $c_print_length = $min;
      if (fseek($resource, $min) == 0) {
        while (!feof($resource)) {
          $c_data = fread($resource, 1024);
          for ($i = 0; $i < strlen($c_data); $i++, $c_print_length++) {
            if ($c_print_length > $max) break 2;
            print $c_data[$i];
          }
        }
      }
      fclose($resource);
    }
    console::log_store();
    exit();
  }

  # ─────────────────────────────────────────────────────────────────────

  const jpeg_quality = 90;

  static function on_load_static_pictures($event, $type_info, &$file) {
    if ($type_info->type === 'png' ||
        $type_info->type === 'gif' ||
        $type_info->type === 'jpg' ||
        $type_info->type === 'jpeg') {
      $thumb_url_arg = url::get_current()->query_arg_select('thumb');
      if ($thumb_url_arg !== null) {
        if (substr($file->name_get(), -6) !== '.thumb') {
          $file_thumb = new file($file->path_get());
          $file_thumb->name_set($file->name_get().'.thumb');
          if ($file_thumb->is_exist()) {
            $file = $file_thumb;
            return;
          } else {
            if (extension_loaded('exif') && extension_loaded('gd')) {
              $result = media::picture_thumbnail_create(
                $file      ->path_get(),
                $file_thumb->path_get(), 100, null, static::jpeg_quality);
              if ($result) {
                $file = $file_thumb;
                return;
              } else {$file = new file(dir_system.'module_core/frontend/pictures/media-error-extensions-not-loaded.'   .$file->type_get()); return;}
            }   else {$file = new file(dir_system.'module_core/frontend/pictures/media-error-thumbnail-creation-error.'.$file->type_get()); return;}
          }
        } else {
        # can not create thumbnail from thumbnail
          core::send_header_and_exit('file_not_found');
        }
      }
    }
  }

}}