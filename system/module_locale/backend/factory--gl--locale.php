<?php

  #############################################################
  ### Copyright © 2017 Maxim Rysevets. All rights reserved. ###
  #############################################################

namespace effectivecore {
          use \effectivecore\modules\storage\storage_factory as storage;
          abstract class locale_factory {

  protected static $countries;
  protected static $settings;

  static function init() {
    static::$settings = storage::get('settings')->select_group('current')['locales'];
    foreach (storage::get('settings')->select_group('countries') as $c_countries) {
      foreach ($c_countries as $c_country) {
        static::$countries[$c_country->code] = $c_country;
      }
    }
  }

  static function get_countries() {
    if (!static::$countries) static::init();
    return static::$countries;
  }

  static function get_settings() {
    if (!static::$settings) static::init();
    return static::$settings;
  }

  ###############
  ### formats ###
  ###############

  static function format_time($time)                      {return \DateTime::createFromFormat('H:i:s',       $time,     new \DateTimeZone('UTC'))->setTimezone(new \DateTimeZone(static::get_settings()->timezone))->format(static::get_settings()->format_time);}
  static function format_date($date)                      {return \DateTime::createFromFormat('Y-m-d',       $date,     new \DateTimeZone('UTC'))->setTimezone(new \DateTimeZone(static::get_settings()->timezone))->format(static::get_settings()->format_date);}
  static function format_datetime($datetime)              {return \DateTime::createFromFormat('Y-m-d H:i:s', $datetime, new \DateTimeZone('UTC'))->setTimezone(new \DateTimeZone(static::get_settings()->timezone))->format(static::get_settings()->format_datetime);}
  static function format_persent($number, $precision = 2) {return static::format_number(floatval($number), $precision).'%';}
  static function format_msecond($number, $precision = 6) {return static::format_number(floatval($number), $precision);}
  static function format_version($number)                 {return static::format_number(floatval($number), 2);}

  static function format_number($number, $precision = 0, $decimal_point = null, $thousands_separator = null) {
    $current = static::get_settings();
    return number_format($number, $precision,
      is_null($decimal_point)       ? $current->decimal_point       : $decimal_point,
      is_null($thousands_separator) ? $current->thousands_separator : $thousands_separator
    );
  }

}}