<?php

  #############################################################
  ### Copyright © 2017 Maxim Rysevets. All rights reserved. ###
  #############################################################

namespace effectivecore {
          use \effectivecore\translations_factory as translations;
          class markup extends \effectivecore\node {

  public $tag_name = 'div';
  public $template = 'markup_element';

  function __construct($tag_name = null, $attributes = [], $children = [], $weight = 0) {
    if ($tag_name) $this->tag_name = $tag_name;
    parent::__construct($attributes, $children, $weight);
  }

  function child_insert($child, $id = null) {
    if (is_string($child) || is_numeric($child)) return parent::child_insert(new text($child), $id);
    else                                         return parent::child_insert($child, $id);
  }

  function render() {
    return (new template($this->template, [
      'tag_name'   => $this->tag_name,
      'attributes' => factory::data_to_attr($this->attribute_select()),
      'content'    => $this->render_children($this->children)
    ]))->render();
  }

  ###########################################
  ### functionality for inherited classes ###
  ###########################################

  function render_required_mark() {
    return (new markup('b', ['class' => ['required' => 'required']], '*'))->render();
  }

  function render_description() {
    $return = [];
    if (!empty($this->description))             $return[] = new markup('p', [], is_string($this->description) ? translations::get($this->description) : $this->description);
    if (!empty($this->attributes['minlength'])) $return[] = new markup('p', ['class' => ['minlength' => 'minlength']], translations::get('Field must contain a minimum of %%_lenght characters.', ['lenght' => $this->attributes['minlength']]));
    if (!empty($this->attributes['maxlength'])) $return[] = new markup('p', ['class' => ['maxlength' => 'maxlength']], translations::get('Field must contain a maximum of %%_lenght characters.', ['lenght' => $this->attributes['maxlength']]));
    return count($return) ? (new markup('x-description', [], $return))->render() : '';
  }

}}