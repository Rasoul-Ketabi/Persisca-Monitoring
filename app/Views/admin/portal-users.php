<?php
/** Stage 10B Portal Users. Admin only, shared styles with settings forms. */
$base=admin_url('portal-users');
$publicBase=absolute_url('status/');
?>
<?php if ($page==='portal-users'): ?>
<section class="panel portal-users-panel">
  <div class="panel-header portal-users-heading">
    <div><h2>Portal accounts</h2><p>Customers sign in with an email code and manage only their own monitors.</p></div>
    <a class="button primary" href="<?= e(admin_url('portal-users/add')) ?>">+ Add Portal User</a>
  </div>
  <?php if ($portalRows): ?>
  <div class="monitor-table-scroll"><table class="monitor-table"><thead><tr><th>Name</th><th>Email</th><th>Status</th><th>Usage</th><th>Public page</th><th>Last login</th><th>Actions</th></tr></thead><tbody>
  <?php foreach($portalRows as $r): ?>
  <tr><td><strong><?= e($r['name']) ?></strong></td><td><?= e($r['email']) ?></td><td><?= e(ucfirst($r['status'])) ?></td><td><?= (int)$r['monitor_count'] ?> / <?= (int)$r['monitor_limit'] ?><?= (int)$r['monitor_count']>(int)$r['monitor_limit']?' · Over limit':'' ?></td><td><?= (int)$r['public_status_enabled']===1?'Enabled':'Disabled' ?></td><td><?= e(display_date($r['last_login_at'])) ?></td><td><div class="admin-actions"><a href="<?= e(admin_url('portal-users/view').'?id='.$r['id']) ?>">View</a><a href="<?= e(admin_url('portal-users/edit').'?id='.$r['id']) ?>">Edit</a></div></td></tr>
  <?php endforeach; ?></tbody></table></div>
  <?php else: ?><div class="portal-users-empty"><strong>No portal users yet</strong><p>Add a Portal User to assign monitor slots and give them an email-only login.</p></div><?php endif; ?>
</section>
<section class="panel portal-admin-card">
  <div class="portal-admin-heading"><h2>Email delivery settings</h2><p>Configure the sender address used for portal login codes. Your hosting provider must support outbound email.</p></div>
  <form class="portal-admin-form" method="post" action="<?= e($base) ?>">
    <?= \Monitor\Core\Security::csrfField() ?><input type="hidden" name="action" value="mail">
    <div class="portal-admin-fields">
      <label for="portal-mail-email">Sender email<input id="portal-mail-email" name="mail_from_email" type="email" maxlength="190" value="<?= e((string)($settings['mail_from_email']??'')) ?>" placeholder="status@example.com" autocomplete="off"></label>
      <label for="portal-mail-name">Sender name<input id="portal-mail-name" name="mail_from_name" maxlength="100" value="<?= e((string)($settings['mail_from_name']??'')) ?>" placeholder="Service Status" autocomplete="off"></label>
    </div>
    <div class="portal-admin-actions"><button class="button primary" type="submit">Save sender settings</button></div>
  </form>
</section>
<?php elseif ($page==='portal-users/view'): ?>
<?php $personalUrl=$publicBase.rawurlencode((string)$portalTarget['public_slug']).'/'; ?>
<section class="panel portal-admin-card">
  <div class="portal-admin-heading"><h2><?= e($portalTarget['name']) ?></h2><p><?= e($portalTarget['email']) ?></p></div>
  <div class="portal-user-facts">
    <div><span>Account</span><strong><?= e(ucfirst($portalTarget['status'])) ?></strong></div>
    <div><span>Monitor usage</span><strong><?= (int)$portalTarget['monitor_count'] ?> / <?= (int)$portalTarget['monitor_limit'] ?></strong></div>
    <div><span>Last sign in</span><strong><?= e(display_date($portalTarget['last_login_at'])) ?></strong></div>
    <div><span>Public status</span><strong><?= (int)$portalTarget['public_status_enabled']===1?'Enabled':'Disabled' ?></strong></div>
    <div><span>Public slug</span><strong><?= e($portalTarget['public_slug']) ?></strong></div>
    <div><span>Public monitors</span><strong><?= count(array_filter($portalMonitors,static fn($m)=>(int)$m['is_public']===1)) ?></strong></div>
    <div><span>Telegram</span><strong><?= !empty($portalTarget['telegram_chat_id'])?'Connected':'Not Connected' ?></strong></div>
    <div><span>Telegram alerts</span><strong><?= !empty($portalTarget['telegram_chat_id'])&& !empty($portalTarget['telegram_notifications_enabled'])?'Enabled':'Disabled' ?></strong></div>
    <div><span>Telegram connected at</span><strong><?= e(display_date($portalTarget['telegram_connected_at'])) ?></strong></div>
  </div>
  <div class="portal-admin-link"><span>Personal status URL</span><code><?= e($personalUrl) ?></code></div>
  <div class="portal-admin-actions"><a class="button primary" href="<?= e(admin_url('portal-users/edit').'?id='.$portalTarget['id']) ?>">Edit account</a><?php if ((int)$portalTarget['public_status_enabled']===1 && $portalTarget['status']==='active'): ?><a class="button" href="<?= e($personalUrl) ?>" target="_blank" rel="noopener noreferrer">View public page</a><?php endif; ?></div>
</section>
<section class="panel portal-admin-card">
  <div class="portal-admin-heading"><h2>Owned monitors</h2><p>Only public monitors can appear on this user's personal status page.</p></div>
  <?php if (!$portalMonitors): ?><div class="portal-users-empty">No monitors assigned yet.</div><?php else: ?>
  <div class="monitor-table-scroll"><table class="monitor-table"><thead><tr><th>Monitor</th><th>Current state</th><th>Visibility</th><th>Last check</th></tr></thead><tbody><?php foreach($portalMonitors as $m): ?><tr><td><a href="<?= e(admin_url('monitors/view').'?id='.$m['id']) ?>"><?= e($m['name']) ?></a></td><td><?= e(monitor_state($m)) ?></td><td><?= (int)$m['is_public']===1?'Public':'Private' ?></td><td><?= e(display_date($m['last_checked_at'])) ?></td></tr><?php endforeach; ?></tbody></table></div>
  <?php endif; ?>
</section>
<?php else: ?>
<?php
$m=$page==='portal-users/edit'?$portalTarget:['name'=>'','email'=>'','status'=>'active','monitor_limit'=>3,'public_slug'=>'','public_status_enabled'=>0,'status_page_title'=>''];
if (\Monitor\Core\Security::post() && $error) {
    $m=array_merge($m,array_intersect_key($_POST,$m));
    $m['public_status_enabled']=isset($_POST['public_status_enabled'])?1:0;
}
$formUrl=$page==='portal-users/add'?admin_url('portal-users/add'):admin_url('portal-users/edit').'?id='.$portalTarget['id'];
?>
<section class="panel portal-admin-card portal-admin-form-card">
  <div class="portal-admin-heading"><h2><?= $page==='portal-users/add'?'Create Portal User':'Edit Portal User' ?></h2><p>Login codes are emailed to the account address. No password is required.</p></div>
  <form class="portal-admin-form" method="post" action="<?= e($formUrl) ?>">
    <?= \Monitor\Core\Security::csrfField() ?>
    <div class="portal-admin-fields">
      <label for="pu-name">Name<input id="pu-name" name="name" required maxlength="140" value="<?= e((string)$m['name']) ?>"></label>
      <label for="pu-email">Email<input id="pu-email" type="email" name="email" required maxlength="190" value="<?= e((string)$m['email']) ?>"></label>
      <label for="pu-limit">Monitor limit<input id="pu-limit" name="monitor_limit" type="number" min="0" max="100000" required value="<?= (int)$m['monitor_limit'] ?>"><small>0 means the user cannot add monitors.</small></label>
      <label for="pu-status">Account status<select id="pu-status" name="status"><option value="active" <?= $m['status']==='active'?'selected':'' ?>>Active</option><option value="disabled" <?= $m['status']==='disabled'?'selected':'' ?>>Disabled</option></select></label>
    </div>
    <div class="portal-admin-divider"></div>
    <h3>Personal status page</h3><p class="portal-admin-hint">The public page is disabled by default. Slug changes are managed by Super Admins.</p>
    <div class="portal-admin-fields">
      <label for="pu-slug">Public URL slug<input id="pu-slug" name="public_slug" maxlength="120" value="<?= e((string)$m['public_slug']) ?>" placeholder="Generated from name when empty"><small>Use lowercase letters, digits and hyphens.</small></label>
      <label for="pu-title">Page title (optional)<input id="pu-title" name="status_page_title" maxlength="190" value="<?= e((string)($m['status_page_title']??'')) ?>" placeholder="My Services Status"><small>If empty, the user's name is used.</small></label>
    </div>
    <label class="portal-admin-check"><input type="checkbox" name="public_status_enabled" value="1" <?= (int)$m['public_status_enabled']===1?'checked':'' ?>><span>Enable personal public status page</span></label>
    <div class="portal-admin-actions"><button class="button primary" type="submit">Save Portal User</button><a class="portal-admin-cancel" href="<?= e($base) ?>">Cancel</a></div>
  </form>
</section>
<?php endif; ?>
