<?php

namespace effectivecore {
          use \effectivecore\translate_factory as translations;
          class form_element extends markup {

  public $tag_name;
  public $title;
  public $description;

  function __construct($tag_name = 'div', $attributes = [], $children = [], $weight = 0, $title = '', $description = '') {
    $this->tag_name = $tag_name;
    $this->title = $title;
    $this->description = $description;
    parent::__construct($attributes, $children, $weight);
  }

  function render() {
    return $this->render_self().
           parent::render().
           $this->render_description();
  }

  function render_self() {
    return empty($this->title) ? '' : (new markup('label', [], [
      $this->title,
      $this->attribute_select('required') ? $this->render_required_mark() : ''
    ]))->render();
  }

  function render_required_mark() {
    return (new markup('b', ['class' => ['required' => 'required']], '*'))->render();
  }

  function render_description() {
    $return = [];
    if (!empty($this->description))             $return[] = (new markup('p', [], is_string($this->description) ? translations::get($this->description) : $this->description))->render();
    if (!empty($this->attributes['minlength'])) $return[] = (new markup('p', ['class' => ['minlength' => 'minlength']], translations::get('Field should contain minimum %%_lenght symbols.', ['lenght' => $this->attributes['minlength']])))->render();
    if (!empty($this->attributes['maxlength'])) $return[] = (new markup('p', ['class' => ['maxlength' => 'maxlength']], translations::get('Field should contain maximum %%_lenght symbols.', ['lenght' => $this->attributes['maxlength']])))->render();
    return count($return) ? (new markup('x-description', [], implode($return)))->render() : '';
  }

}}