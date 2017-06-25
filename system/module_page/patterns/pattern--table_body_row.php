<?php

namespace effectivecore {
          class table_body_row extends node {

  public $template = 'table_body_row';

  function child_insert($child, $id = null) {
    if ($child instanceof table_body_row_cell)   return parent::child_insert($child, $id);
    if ($child instanceof markup)                return parent::child_insert(new table_body_row_cell([], $child), $id);
    if (is_string($child) || is_numeric($child)) return parent::child_insert(new table_body_row_cell([], $child), $id);
  }

  function render() {
    if (count($this->children)) {
      return (new template($this->template, [
        'attributes' => factory::data_to_attr($this->attributes, ' '),
        'children'   => $this->render_children($this->children),
      ]))->render();
    }
  }

}}