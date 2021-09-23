<?php

  ##################################################################
  ### Copyright © 2017—2021 Maxim Rysevets. All rights reserved. ###
  ##################################################################

namespace effcore\modules\user {
          use \effcore\core;
          use \effcore\message;
          use \effcore\module;
          use \effcore\session;
          use \effcore\text_multiline;
          use \effcore\text;
          use \effcore\url;
          use \effcore\user;
          abstract class events_form_registration {

  static function on_init($event, $form, $items) {
    $settings = module::settings_get('user');
    $items['#session_params:is_long_session']->description = new text_multiline([
      'Short session: %%_min day%%_plural{min|s} | long session: %%_max day%%_plural{max|s}'], [
      'min' => $settings->session_duration_min,
      'max' => $settings->session_duration_max], '', true, true);
    $items['#email'   ]->value_set('');
    $items['#nickname']->value_set('');
  }

  static function on_submit($event, $form, $items) {
    switch ($form->clicked_button->value_get()) {
      case 'register':
        $user = user::insert([
          'email'         => $items['#email'   ]->value_get(),
          'nickname'      => $items['#nickname']->value_get(),
          'timezone'      => $items['#timezone']->value_get(),
          'password_hash' => $items['#password']->value_get()
        ]);
        if ($user) {
          session::insert($user->id,
            core::array_kmap($items['*session_params']->values_get())
          );
          message::insert(
            new text('Welcome, %%_nickname!', ['nickname' => $user->nickname])
          );
          url::go(url::back_url_get() ?: '/user/'.$user->nickname);
        } else {
          message::insert(
            'User was not registered!', 'error'
          );
        }
        break;
    }
  }

}}