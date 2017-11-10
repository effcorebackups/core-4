<?php

  #############################################################
  ### Copyright © 2017 Maxim Rysevets. All rights reserved. ###
  #############################################################

namespace effectivecore {
          class captcha extends \effectivecore\node_simple {

  public $length = 8;

  function render() {
    $canvas = new canvas_svg(40, 15, 5);
    $canvas->fill('#000000', .9);
    $canvas->glyph_set(rand(0,   1), rand(1, 5), '00001|00001|00001|00001|00001|10001|01001|00101|00011|00001');
    $canvas->glyph_set(rand(4,   5), rand(1, 5), '11111|10000|01000|00100|00010|00001|00001|00001|00001|11111');
    $canvas->glyph_set(rand(9,  11), rand(1, 5), '01000|00100|00010|00001|11111|01000|00100|00010|00001|11111');
    $canvas->glyph_set(rand(14, 16), rand(1, 5), '00001|00001|00001|00001|01111|10001|01001|00100|00011|00001');
    $canvas->glyph_set(rand(19, 21), rand(1, 5), '01000|00100|00010|00001|11111|10000|10000|10000|10000|11111');
    $canvas->glyph_set(rand(24, 26), rand(1, 5), '11111|10001|10001|10001|11111|10000|01000|00100|00010|00001');
    $canvas->glyph_set(rand(29, 31), rand(1, 5), '10000|10000|10000|10000|10000|01000|00100|00010|00001|11111');
    $canvas->glyph_set(rand(34, 36), rand(1, 5), '11111|10001|10001|10001|11111|10001|10001|10001|10001|11111');
    return $canvas->render();
  }

}}