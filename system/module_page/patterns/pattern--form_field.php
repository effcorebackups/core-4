<?php

namespace effectivecore {
          use \effectivecore\translate_factory as translations;
          class form_field extends form_container {

  public $template = 'form_field';
  public $tag_name = 'x-field';
  public $title;
  public $description;

  function render() {
    return parent::render();
  }

}}