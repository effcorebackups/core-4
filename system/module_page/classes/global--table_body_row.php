<?php

namespace effectivecore {
          class table_body_row extends node {

  public $template = 'table_body_row';

  function add_child($child, $id = null) {
    parent::add_child(
      is_string($child) ? new table_body_row_cell([], $child) : $child, $id
    );
  }

  function render() {
    if (count($this->children)) {
      return (new template($this->template, [
        'attributes' => factory::data_to_attr($this->attributes, ' '),
        'data'       => $this->render_children($this->children),
      ]))->render();
    }
  }

}}