<?php

  ##################################################################
  ### Copyright © 2017—2019 Maxim Rysevets. All rights reserved. ###
  ##################################################################

namespace effcore\modules\user {
          use \effcore\core;
          use \effcore\file;
          use \effcore\instance;
          use \effcore\message;
          use \effcore\page;
          use \effcore\translation;
          use \effcore\url;
          abstract class events_form_user_edit {

  static function on_init($form, $items) {
    $nick = page::current_get()->args_get('nick');
    $user = (new instance('user', ['nick' => $nick]))->select();
    $items['#email']->value_set($user->email);
    $items['#nick']->value_set($user->nick);
    $items['#timezone']->value_set($user->timezone);
    $items['#avatar']->pool_values_init_old_from_storage(
      $user->avatar_path ? [$user->avatar_path] : []
    );
  }

  static function on_validate($form, $items) {
    switch ($form->clicked_button->value_get()) {
      case 'save':
        if (!$form->has_error()) {
          $nick = page::current_get()->args_get('nick');
        # check security
          $test_password = (new instance('user', [
            'nick' => $nick
          ]))->select();
          if ($test_password->password_hash !== core::hash_password_get($items['#password']->value_get())) {
            $items['#password']->error_set(
              'Field "%%_title" contains incorrect value!', ['title' => translation::get($items['#password']->title)]
            );
            return;
          }
        # test email
          $test_email = (new instance('user', [
            'email' => strtolower($items['#email']->value_get())
          ]))->select();
          if ($test_email &&
              $test_email->nick != $nick) {
            $items['#email']->error_set(
              'User with this EMail was already registered!'
            );
            return;
          }
        # test nick
          $test_nick = (new instance('user', [
            'nick' => $items['#nick']->value_get()
          ]))->select();
          if ($test_nick &&
              $test_nick->nick != $nick) {
            $items['#nick']->error_set(
              'User with this Nick was already registered!'
            );
            return;
          }
        # test new password
          if ($items['#password_new']->value_get() ==
              $items['#password'    ]->value_get()) {
            $items['#password_new']->error_set(
              'New password must be different from the current password!'
            );
            return;
          }
        }
        break;
    }
  }

  static function on_submit($form, $items) {
    $nick = page::current_get()->args_get('nick');
    switch ($form->clicked_button->value_get()) {
      case 'save':
        $user = (new instance('user', ['nick' => $nick]))->select();
        $user->email = strtolower($items['#email']->value_get());
        $user->nick  = $items['#nick']->value_get();
        $user->timezone = $items['#timezone']->value_get();
        if ($items['#password_new']->value_get()) {
          $user->password_hash = core::hash_password_get($items['#password_new']->value_get());
        }
        $avatar_info = $items['#avatar']->pool_files_save();
        if (isset($avatar_info[0]->path) &&
                  $avatar_info[0]->path) {
           $c_file = new file($avatar_info[0]->path);
           $user->avatar_path = $c_file->path_relative_get(); } else {
           $user->avatar_path = null;
        }
        if ($user->update()) {
          message::insert(
            translation::get('User %%_nick was updated.', ['nick' => $user->nick])
          );
          url::go(url::back_url_get() ?: '/user/'.$user->nick);
        } else {
          message::insert(
            translation::get('User %%_nick was not updated.', ['nick' => $user->nick]), 'warning'
          );
        }
        break;
      case 'cancel':
        url::go(url::back_url_get() ?: '/user/'.$user->nick);
        break;
    }
  }

}}