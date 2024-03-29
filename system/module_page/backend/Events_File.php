<?php

##################################################################
### Copyright © 2017—2024 Maxim Rysevets. All rights reserved. ###
##################################################################

namespace effcore\modules\page;

use const effcore\DIR_ROOT;
use effcore\Core;
use effcore\Dynamic;
use effcore\Event;
use effcore\File;
use effcore\Media;
use effcore\Module;
use effcore\Area_Grid;
use effcore\Request;
use effcore\Response;

abstract class Events_File {

    static function prepath_get($type) {
        $settings = Module::settings_get('page');
        switch ($type) {
            case 'cover_is_not_found'                  : return DIR_ROOT.$settings->prepath__cover_is_not_found;
            case 'file_is_outside_of_dynamic_directory': return DIR_ROOT.$settings->prepath__file_is_outside_of_dynamic_directory;
            case 'poster_is_not_found'                 : return DIR_ROOT.$settings->prepath__poster_is_not_found;
            case 'thumbnail_creation_error'            : return DIR_ROOT.$settings->prepath__thumbnail_creation_error;
            case 'thumbnail_embedding_error'           : return DIR_ROOT.$settings->prepath__thumbnail_embedding_error;
            case 'thumbnail_is_not_allowed'            : return DIR_ROOT.$settings->prepath__thumbnail_is_not_allowed;
        }
    }

    # ─────────────────────────────────────────────────────────────────────
    # not found
    # ─────────────────────────────────────────────────────────────────────

    static function on_load_not_found($event, &$type_info, &$file, $real_path, $phase) {
        switch ($file->path_get()) {
            case DIR_ROOT.'sitemap.xml':
                $settings = Module::settings_get('page');
                $file = new File(Dynamic::DIR_FILES.'sitemap.xml');
                if ($file->is_exists()) {
                    $type = File::types_get()[$settings->apply_tokens_for_sitemap ? 'xmld' : 'xml'];
                    if ($settings->apply_tokens_for_sitemap)
                         Event::start('on_file_load', 'dynamic', ['type_info' => &$type, 'file' => &$file]);
                    else Event::start('on_file_load', 'static' , ['type_info' => &$type, 'file' => &$file]);
                    exit();
                } break;
            case DIR_ROOT.'robots.txt':
                $settings = Module::settings_get('page');
                $file = new File(Dynamic::DIR_FILES.'robots.txt');
                if ($file->is_exists()) {
                    $type = File::types_get()[$settings->apply_tokens_for_robots ? 'txtd' : 'txt'];
                    if ($settings->apply_tokens_for_robots)
                         Event::start('on_file_load', 'dynamic', ['type_info' => &$type, 'file' => &$file]);
                    else Event::start('on_file_load', 'static' , ['type_info' => &$type, 'file' => &$file]);
                    exit();
                } break;
            case DIR_ROOT.'favicon.ico':
                Response::send_header_and_exit('moved_permanently');
        }
    }

    # ─────────────────────────────────────────────────────────────────────
    # audio:
    # ═════════════════════════════════════════════════════════════════════
    # test.audio               → container://test.audio:original
    # test.audio?cover=        → container://test.audio:cover
    # test.audio?cover=unknown → container://test.audio:cover
    # test.audio?cover=small   → container://test.audio:cover-small
    # test.audio?cover=middle  → container://test.audio:cover-middle
    # test.audio?cover=big     → container://test.audio:cover-big
    # ─────────────────────────────────────────────────────────────────────

    static function on_load_static_audio($event, &$type_info, &$file) {
        if ($type_info->type === 'audio') {
            $path = $file->path_get();
            $path_container    = 'container://'.$path;
            $path_info         = 'container://'.$path.':info';
            $path_original     = 'container://'.$path.':original';
            $path_cover        = 'container://'.$path.':cover';
            $path_cover_small  = 'container://'.$path.':cover-small';
            $path_cover_middle = 'container://'.$path.':cover-middle';
            $path_cover_big    = 'container://'.$path.':cover-big';
            if (file_exists($path_info) &&
                file_exists($path_original)) {
                $info = @unserialize(file_get_contents($path_info));
                $file_types = File::types_get();
                $arg = Request::value_get('cover', 0, '_GET', null);
                if ($arg === null                     ) $target = 'original';
                if ($arg !== null                     ) $target = 'cover';
                if ($arg !== null && $arg === 'small' ) $target = 'cover-small';
                if ($arg !== null && $arg === 'middle') $target = 'cover-middle';
                if ($arg !== null && $arg === 'big'   ) $target = 'cover-big';
                # case for audio (file "original")
                if ($target === 'original') {
                    if (isset(             $info['original']['type'] ) &&
                        isset($file_types[ $info['original']['type'] ])) {
                        if (Media::media_class_get($info['original']['type']) === 'audio') {
                            $type_info = $file_types[$info['original']['type']];
                            $file = new File($path_original);
                            return true;
                        } else Response::send_header_and_exit('unsupported_media_type');
                    }     else Response::send_header_and_exit('unsupported_media_type');
                }
                # case for cover or its thumbnails
                if ($target !== 'original') {
                    if (isset(             $info['cover']['type'] ) &&
                        isset($file_types[ $info['cover']['type'] ])) {
                        if (Media::media_class_get($info['cover']['type']) === 'picture') {
                            $type_info = $file_types[$info['cover']['type']];
                            if ($target === 'cover'       ) $path_target = $path_cover;
                            if ($target === 'cover-small' ) $path_target = $path_cover_small;
                            if ($target === 'cover-middle') $path_target = $path_cover_middle;
                            if ($target === 'cover-big'   ) $path_target = $path_cover_big;
                            if (file_exists($path_target)) {
                                $file = new File($path_target);
                                return true;
                            }
                            # if cover does not exist
                            if (!file_exists($path_cover)) {
                                $file = new File(static::prepath_get('cover_is_not_found').'.'.$info['cover']['type']);
                                return;
                            }
                            # generate thumbnail and insert it into container
                            if (Core::in_array($target, ['cover-small', 'cover-middle', 'cover-big'])) {
                                if ($target === 'cover-small' ) $size = 'small';
                                if ($target === 'cover-middle') $size = 'middle';
                                if ($target === 'cover-big'   ) $size = 'big';
                                if (Media::is_type_for_thumbnail($type_info->type)) {
                                    if (isset($info['cover_thumbnails']) && is_array($info['cover_thumbnails'])) {
                                        if (isset($size) && isset($info['cover_thumbnails'][$size])) {
                                            if (str_starts_with($path, Dynamic::DIRECTORY)) {
                                                $settings = Module::settings_get('page');
                                                if ($size === 'small' ) $width = $settings->thumbnail_width_small;
                                                if ($size === 'middle') $width = $settings->thumbnail_width_middle;
                                                if ($size === 'big'   ) $width = $settings->thumbnail_width_big;
                                                $path_thumbnail_tmp = $path.'.'.$target.'.'.$info['cover']['type'];
                                                $result = Media::thumbnail_create($path_cover, $path_thumbnail_tmp, $width, null, $settings->thumbnail_quality_jpeg);
                                                if ($result && file_exists($path_thumbnail_tmp)) {
                                                    if (Media::container_file_insert($path_container, $path_thumbnail_tmp, $target)) {
                                                        File::delete($path_thumbnail_tmp);
                                                        $file = new File($path_target);
                                                        return true;
                                                    } else $file = new File(static::prepath_get('thumbnail_embedding_error'           ).'.'.$info['cover']['type']);
                                                }     else $file = new File(static::prepath_get('thumbnail_creation_error'            ).'.'.$info['cover']['type']);
                                            }         else $file = new File(static::prepath_get('file_is_outside_of_dynamic_directory').'.'.$info['cover']['type']);
                                        }             else $file = new File(static::prepath_get('thumbnail_is_not_allowed'            ).'.'.$info['cover']['type']);
                                    } else Response::send_header_and_exit('unsupported_media_type');
                                }     else Response::send_header_and_exit('unsupported_media_type');
                            }         else Response::send_header_and_exit('unsupported_media_type');
                        }             else Response::send_header_and_exit('unsupported_media_type');
                    }                 else Response::send_header_and_exit('unsupported_media_type');
                }
            } else Response::send_header_and_exit('unsupported_media_type');
        }
    }

    # ─────────────────────────────────────────────────────────────────────
    # video:
    # ═════════════════════════════════════════════════════════════════════
    # test.video                → container://test.video:original
    # test.video?poster=        → container://test.video:poster
    # test.video?poster=unknown → container://test.video:poster
    # test.video?poster=small   → container://test.video:poster-small
    # test.video?poster=middle  → container://test.video:poster-middle
    # test.video?poster=big     → container://test.video:poster-big
    # ─────────────────────────────────────────────────────────────────────

    static function on_load_static_video($event, &$type_info, &$file) {
        if ($type_info->type === 'video') {
            $path = $file->path_get();
            $path_container     = 'container://'.$path;
            $path_info          = 'container://'.$path.':info';
            $path_original      = 'container://'.$path.':original';
            $path_poster        = 'container://'.$path.':poster';
            $path_poster_small  = 'container://'.$path.':poster-small';
            $path_poster_middle = 'container://'.$path.':poster-middle';
            $path_poster_big    = 'container://'.$path.':poster-big';
            if (file_exists($path_info) &&
                file_exists($path_original)) {
                $info = @unserialize(file_get_contents($path_info));
                $file_types = File::types_get();
                $arg = Request::value_get('poster', 0, '_GET', null);
                if ($arg === null                     ) $target = 'original';
                if ($arg !== null                     ) $target = 'poster';
                if ($arg !== null && $arg === 'small' ) $target = 'poster-small';
                if ($arg !== null && $arg === 'middle') $target = 'poster-middle';
                if ($arg !== null && $arg === 'big'   ) $target = 'poster-big';
                # case for video (file "original")
                if ($target === 'original') {
                    if (isset(             $info['original']['type'] ) &&
                        isset($file_types[ $info['original']['type'] ])) {
                        if (Media::media_class_get($info['original']['type']) === 'video') {
                            $type_info = $file_types[$info['original']['type']];
                            $file = new File($path_original);
                            return true;
                        } else Response::send_header_and_exit('unsupported_media_type');
                    }     else Response::send_header_and_exit('unsupported_media_type');
                }
                # case for poster or its thumbnails
                if ($target !== 'original') {
                    if (isset(             $info['poster']['type'] ) &&
                        isset($file_types[ $info['poster']['type'] ])) {
                        if (Media::media_class_get($info['poster']['type']) === 'picture') {
                            $type_info = $file_types[$info['poster']['type']];
                            if ($target === 'poster'       ) $path_target = $path_poster;
                            if ($target === 'poster-small' ) $path_target = $path_poster_small;
                            if ($target === 'poster-middle') $path_target = $path_poster_middle;
                            if ($target === 'poster-big'   ) $path_target = $path_poster_big;
                            if (file_exists($path_target)) {
                                $file = new File($path_target);
                                return true;
                            }
                            # if poster does not exist
                            if (!file_exists($path_poster)) {
                                $file = new File(static::prepath_get('poster_is_not_found').'.'.$info['poster']['type']);
                                return;
                            }
                            # generate thumbnail and insert it into container
                            if (Core::in_array($target, ['poster-small', 'poster-middle', 'poster-big'])) {
                                if ($target === 'poster-small' ) $size = 'small';
                                if ($target === 'poster-middle') $size = 'middle';
                                if ($target === 'poster-big'   ) $size = 'big';
                                if (Media::is_type_for_thumbnail($type_info->type)) {
                                    if (isset($info['poster_thumbnails']) && is_array($info['poster_thumbnails'])) {
                                        if (isset($size) && isset($info['poster_thumbnails'][$size])) {
                                            if (str_starts_with($path, Dynamic::DIRECTORY)) {
                                                $settings = Module::settings_get('page');
                                                if ($size === 'small' ) $width = $settings->thumbnail_width_small;
                                                if ($size === 'middle') $width = $settings->thumbnail_width_middle;
                                                if ($size === 'big'   ) $width = $settings->thumbnail_width_big;
                                                $path_thumbnail_tmp = $path.'.'.$target.'.'.$info['poster']['type'];
                                                $result = Media::thumbnail_create($path_poster, $path_thumbnail_tmp, $width, null, $settings->thumbnail_quality_jpeg);
                                                if ($result && file_exists($path_thumbnail_tmp)) {
                                                    if (Media::container_file_insert($path_container, $path_thumbnail_tmp, $target)) {
                                                        File::delete($path_thumbnail_tmp);
                                                        $file = new File($path_target);
                                                        return true;
                                                    } else $file = new File(static::prepath_get('thumbnail_embedding_error'           ).'.'.$info['poster']['type']);
                                                }     else $file = new File(static::prepath_get('thumbnail_creation_error'            ).'.'.$info['poster']['type']);
                                            }         else $file = new File(static::prepath_get('file_is_outside_of_dynamic_directory').'.'.$info['poster']['type']);
                                        }             else $file = new File(static::prepath_get('thumbnail_is_not_allowed'            ).'.'.$info['poster']['type']);
                                    } else Response::send_header_and_exit('unsupported_media_type');
                                }     else Response::send_header_and_exit('unsupported_media_type');
                            }         else Response::send_header_and_exit('unsupported_media_type');
                        }             else Response::send_header_and_exit('unsupported_media_type');
                    }                 else Response::send_header_and_exit('unsupported_media_type');
                }
            } else Response::send_header_and_exit('unsupported_media_type');
        }
    }

    # ─────────────────────────────────────────────────────────────────────
    # picture:
    # ═════════════════════════════════════════════════════════════════════
    # test.picture               → container://test.picture:original
    # test.picture?thumb=        → container://test.picture:original
    # test.picture?thumb=unknown → container://test.picture:original
    # test.picture?thumb=small   → container://test.picture:thumbnail-small
    # test.picture?thumb=middle  → container://test.picture:thumbnail-middle
    # test.picture?thumb=big     → container://test.picture:thumbnail-big
    # ─────────────────────────────────────────────────────────────────────

    static function on_load_static_picture($event, &$type_info, &$file) {
        if ($type_info->type === 'picture') {
            $path = $file->path_get();
            $path_container        = 'container://'.$path;
            $path_info             = 'container://'.$path.':info';
            $path_original         = 'container://'.$path.':original';
            $path_thumbnail_small  = 'container://'.$path.':thumbnail-small';
            $path_thumbnail_middle = 'container://'.$path.':thumbnail-middle';
            $path_thumbnail_big    = 'container://'.$path.':thumbnail-big';
            if (file_exists($path_info) &&
                file_exists($path_original)) {
                $info = @unserialize(file_get_contents($path_info));
                $file_types = File::types_get();
                if (isset(             $info['original']['type'] ) &&
                    isset($file_types[ $info['original']['type'] ])) {
                    $type_info = $file_types[$info['original']['type']];
                    if (Media::media_class_get($type_info->type) === 'picture') {
                        switch (Request::value_get('thumb', 0, '_GET', null)) {
                            case 'small' : $size = 'small';  break;
                            case 'middle': $size = 'middle'; break;
                            case 'big'   : $size = 'big';    break;
                            default      : $size = 'original';
                        }
                        # case for picture (file "original")
                        if ($size === 'original') {
                            $file = new File($path_original);
                            return true;
                        }
                        # case for thumbnails
                        if ($size === 'small' ) $path_thumbnail = $path_thumbnail_small;
                        if ($size === 'middle') $path_thumbnail = $path_thumbnail_middle;
                        if ($size === 'big'   ) $path_thumbnail = $path_thumbnail_big;
                        if (file_exists($path_thumbnail)) {
                            $file = new File($path_thumbnail);
                            return true;
                        }
                        # generate thumbnail and insert it into container
                        if (Media::is_type_for_thumbnail($type_info->type)) {
                            if (isset($info['thumbnails']) && is_array($info['thumbnails'])) {
                                if (isset($info['thumbnails'][$size])) {
                                    if (str_starts_with($path, Dynamic::DIRECTORY)) {
                                        $settings = Module::settings_get('page');
                                        if ($size === 'small' ) $width = $settings->thumbnail_width_small;
                                        if ($size === 'middle') $width = $settings->thumbnail_width_middle;
                                        if ($size === 'big'   ) $width = $settings->thumbnail_width_big;
                                        $path_thumbnail_tmp = $path.'.thumbnail-'.$size.'.'.$type_info->type;
                                        $result = Media::thumbnail_create($path_original, $path_thumbnail_tmp, $width, null, $settings->thumbnail_quality_jpeg);
                                        if ($result && file_exists($path_thumbnail_tmp)) {
                                            if (Media::container_file_insert($path_container, $path_thumbnail_tmp, 'thumbnail-'.$size)) {
                                                File::delete($path_thumbnail_tmp);
                                                $file = new File($path_thumbnail);
                                                return true;
                                            } else $file = new File(static::prepath_get('thumbnail_embedding_error'           ).'.'.$type_info->type);
                                        }     else $file = new File(static::prepath_get('thumbnail_creation_error'            ).'.'.$type_info->type);
                                    }         else $file = new File(static::prepath_get('file_is_outside_of_dynamic_directory').'.'.$type_info->type);
                                }             else $file = new File(static::prepath_get('thumbnail_is_not_allowed'            ).'.'.$type_info->type);
                            } else Response::send_header_and_exit('unsupported_media_type');
                        }     else Response::send_header_and_exit('unsupported_media_type');
                    }         else Response::send_header_and_exit('unsupported_media_type');
                }             else Response::send_header_and_exit('unsupported_media_type');
            }                 else Response::send_header_and_exit('unsupported_media_type');
        }
    }

    # ─────────────────────────────────────────────────────────────────────
    # virtual files
    # ─────────────────────────────────────────────────────────────────────

    static function on_load_virtual_not_found($event, &$type_info, &$file) {
        Response::send_header_and_exit('file_not_found');
    }

    static function on_load_virtual_style__grid_custom($event, &$type_info, &$file) {
        if ($type_info->type === 'cssv') {
            if ($file->path_get_relative() === 'system/module_page/frontend/layout-grid-custom.cssv') {
                header('content-type: text/css');
                header('x-content-type-options: nosniff');
                header('cache-control: private, no-cache, no-store, must-revalidate');
                header('expires: 0');
                $col_count = (int)Request::value_get('col_count', 0, '_GET', Area_Grid::COL_COUNT_MAX);
                $row_count = (int)Request::value_get('row_count', 0, '_GET', Area_Grid::ROW_COUNT_MAX);
                Area_Grid::validate_col_count($col_count);
                Area_Grid::validate_row_count($row_count);
                print Area_Grid::generate_styles(
                    $col_count,
                    $row_count);
                exit();
            }
        }
    }

}
