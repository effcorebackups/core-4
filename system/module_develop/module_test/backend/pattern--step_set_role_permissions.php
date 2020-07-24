<?php

  ##################################################################
  ### Copyright © 2017—2020 Maxim Rysevets. All rights reserved. ###
  ##################################################################

namespace effcore {
          class step_set_role_permissions {

  public $id_role;
  public $permissions = [];
  public $is_reset = false;

  function run(&$test, $dpath, &$c_results) {
    if (true           ) $c_results['reports'][$dpath][] = new text('changing permissions for role = "%%_role"', ['role' => $this->id_role]);
    if ($this->is_reset) $c_results['reports'][$dpath][] = new text('there will be try to delete all permissions');
    if (true           ) $c_results['reports'][$dpath][] = new text('there will be try to insert permissions: %%_permissions', ['permissions' => implode(', ', $this->permissions) ?: 'n/a']);
    $old_permissions =   role::relation_permission_select    ($this->id_role);
    if ($this->is_reset) role::relation_permission_delete_all($this->id_role);
                         role::relation_permission_insert    ($this->id_role, $this->permissions, 'test');
    $new_permissions =   role::relation_permission_select    ($this->id_role);
    $c_results['reports'][$dpath][] = new text('permissions before insertion: %%_permissions', ['permissions' => implode(', ', $old_permissions) ?: 'n/a']);
    $c_results['reports'][$dpath][] = new text('permissions after insertion: %%_permissions',  ['permissions' => implode(', ', $new_permissions) ?: 'n/a']);
  }

}}