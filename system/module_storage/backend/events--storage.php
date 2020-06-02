<?php

  ##################################################################
  ### Copyright © 2017—2020 Maxim Rysevets. All rights reserved. ###
  ##################################################################

namespace effcore\modules\storage {
          use const \effcore\dir_root;
          use \effcore\field_file;
          use \effcore\file_uploaded;
          use \effcore\widget_files;
          abstract class events_storage {

  static function on_instance_delete_before($event, $instance) {
    $entity = $instance->entity_get();
    foreach ($entity->fields as $c_name => $c_field) {
      if (!empty($c_field->managing_control_class)) {
        $c_reflection = new \ReflectionClass($c_field->managing_control_class);
        $c_reflection_instance = $c_reflection->newInstanceWithoutConstructor();
        if ($c_reflection_instance instanceof field_file) {
          if (!empty($instance->{$c_name})) {
            @unlink(dir_root.$instance->{$c_name});
          }
        }
        if ($c_reflection_instance instanceof widget_files) {
          if (!empty($instance->{$c_name})) {
            foreach ($instance->{$c_name} as $c_item) {
              if ($c_item->object instanceof file_uploaded) {
                @unlink($c_item->object->get_current_path());
              }
            }
          }
        }
      }
    }
  }

}}