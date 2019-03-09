<?php

  ##################################################################
  ### Copyright © 2017—2019 Maxim Rysevets. All rights reserved. ###
  ##################################################################

namespace effcore\modules\storage {
          use \effcore\manage_instances;
          use \effcore\markup;
          use \effcore\message;
          use \effcore\page;
          use \effcore\text;
          use \effcore\url;
          abstract class events_form_instance_delete {

  static function on_init($form, $items) {
    manage_instances::instance_delete_by_entity_name_and_instance_id(page::current_get(), true); # emulation for access checking
    $entity_name = page::current_get()->args_get('entity_name');
    $instance_id = page::current_get()->args_get('instance_id');
    $question = new markup('p', [], new text('Do you want to delete instance of entity "%%_entity_name" with id = "%%_instance_id"?', ['entity_name' => $entity_name, 'instance_id' => $instance_id]));
    $items['info']->child_insert($question);
  }

  static function on_submit($form, $items) {
    $base        = page::current_get()->args_get('base');
    $entity_name = page::current_get()->args_get('entity_name');
    $instance_id = page::current_get()->args_get('instance_id');
    switch ($form->clicked_button->value_get()) {
      case 'delete':
        if (manage_instances::instance_delete_by_entity_name_and_instance_id(page::current_get(), false))
             message::insert(new text('Instance of entity "%%_entity_name" with id = "%%_instance_id" was deleted.',     ['entity_name' => $entity_name, 'instance_id' => $instance_id]));
        else message::insert(new text('Instance of entity "%%_entity_name" with id = "%%_instance_id" was not deleted!', ['entity_name' => $entity_name, 'instance_id' => $instance_id]), 'error');
        url::go(url::back_url_get() ?: $base.'/select/'.$entity_name);
        break;
      case 'cancel':
        url::go(url::back_url_get() ?: $base.'/select/'.$entity_name);
        break;
    }
  }

}}