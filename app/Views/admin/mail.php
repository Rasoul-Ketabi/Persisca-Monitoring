<?php
/** Mail delivery configuration, only accessible to Super Admins. */
$transport=(string)($settings['mail_transport']??'php_mail');
$lastTest=(string)($settings['mail_last_test_status']??'never');
$testRecipient=(string)($user['email']??'');
?>
<div class="settings-layout">
  <aside class="settings-menu">
    <div class="settings-label">SETTINGS</div>
    <a class="settings-menu-link" href="<?= e(admin_url('settings/general')) ?>">General <span>→</span></a>
    <a class="settings-menu-link" href="<?= e(admin_url('settings/branding')) ?>">Branding <span>→</span></a>
    <a class="settings-menu-link active" href="<?= e(admin_url('settings/mail')) ?>" aria-current="page">Mail / SMTP <span>→</span></a>
    <p>Changes apply to the next email. Save settings before sending a test.</p>
  </aside>
  <div class="mail-settings-stack">
    <section class="panel settings-panel">
      <div class="panel-header"><div><h2>Transactional email</h2><p>Configure SMTP for portal login codes or keep PHP mail transport.</p></div><span class="pill <?= $transport==='smtp'?'pill-success':'pill-neutral' ?>"><?= e($transport==='smtp'?'SMTP':'PHP mail()') ?></span></div>
      <form method="post" action="<?= e(admin_url('settings/mail')) ?>" class="settings-form">
        <?= \Monitor\Core\Security::csrfField() ?><input type="hidden" name="action" value="save">
        <div class="form-section"><h3>Mail transport</h3><p>SMTP is recommended for external inboxes. It is not activated until you select SMTP and save.</p>
          <label for="mail-transport">Transport<select id="mail-transport" name="mail_transport"><option value="php_mail" <?= $transport==='php_mail'?'selected':'' ?>>PHP mail() (current hosting relay)</option><option value="smtp" <?= $transport==='smtp'?'selected':'' ?>>SMTP</option></select></label>
        </div>
        <div class="form-section"><h3>SMTP server</h3><p>Enter details supplied by your mail provider. STARTTLS on port 587 or SSL/TLS on port 465 is recommended.</p>
          <div class="field-grid">
            <label for="smtp-host">SMTP host<input id="smtp-host" name="smtp_host" maxlength="253" value="<?= e((string)($settings['smtp_host']??'')) ?>" placeholder="smtp.example.com" autocomplete="off"></label>
            <label for="smtp-port">SMTP port<input id="smtp-port" name="smtp_port" type="number" min="1" max="65535" required value="<?= e((string)($settings['smtp_port']??'587')) ?>"></label>
            <label for="smtp-encryption">Encryption<select id="smtp-encryption" name="smtp_encryption"><option value="tls" <?= ($settings['smtp_encryption']??'tls')==='tls'?'selected':'' ?>>STARTTLS</option><option value="ssl" <?= ($settings['smtp_encryption']??'')==='ssl'?'selected':'' ?>>SSL/TLS (implicit)</option><option value="none" <?= ($settings['smtp_encryption']??'')==='none'?'selected':'' ?>>None (unauthenticated relay only)</option></select></label>
            <label for="smtp-timeout">Connection timeout (seconds)<input id="smtp-timeout" type="number" name="smtp_timeout" min="3" max="60" required value="<?= e((string)($settings['smtp_timeout']??'15')) ?>"></label>
          </div>
          <label class="mail-checkbox" for="smtp-auth"><input id="smtp-auth" name="smtp_auth_enabled" type="checkbox" value="1" <?= ($settings['smtp_auth_enabled']??'1')==='1'?'checked':'' ?>><span>Use SMTP authentication (AUTH PLAIN or LOGIN)</span></label>
          <div class="field-grid">
            <label for="smtp-username">SMTP username<input id="smtp-username" name="smtp_username" autocomplete="off" maxlength="254" value="<?= e((string)($settings['smtp_username']??'')) ?>"></label>
            <label for="smtp-password">SMTP password<input id="smtp-password" name="smtp_password" type="password" autocomplete="new-password" maxlength="1024" placeholder="<?= ($settings['smtp_password']??'')!==''?'Saved. Leave blank to keep.':'Enter password' ?>"><span class="field-help"><?= ($settings['smtp_password']??'')!==''?'Password configured. Leave blank to keep the existing password.':'No SMTP password saved.' ?></span></label>
          </div>
          <label class="mail-checkbox" for="smtp-password-clear"><input type="checkbox" name="smtp_password_clear" id="smtp-password-clear" value="1"><span>Remove saved SMTP password</span></label>
          <label for="smtp-clear-confirm">Confirm removal<select name="confirm_password_clear" id="smtp-clear-confirm"><option value="no">No, keep password</option><option value="yes">Yes, remove saved password</option></select><span class="field-help">Selecting Yes only takes effect when the removal checkbox is checked.</span></label>
        </div>
        <div class="form-section"><h3>Sender identity</h3><p>These addresses are used for portal login emails and delivery tests.</p>
          <div class="field-grid">
            <label for="mail-from-email">From Email<input id="mail-from-email" type="email" name="mail_from_email" maxlength="190" value="<?= e((string)($settings['mail_from_email']??'')) ?>" placeholder="status@example.com"></label>
            <label for="mail-from-name">From Name<input id="mail-from-name" name="mail_from_name" maxlength="120" value="<?= e((string)($settings['mail_from_name']??'')) ?>" placeholder="<?= e((string)($settings['app_name']??'Monitor')) ?>"></label>
          </div>
          <label for="mail-reply-to">Reply-To Email (optional)<input id="mail-reply-to" type="email" name="mail_reply_to" maxlength="190" value="<?= e((string)($settings['mail_reply_to']??'')) ?>" placeholder="support@example.com"></label>
        </div>
        <div class="save-bar"><span>SMTP credentials remain stored on this server. Saved passwords are never displayed here.</span><button type="submit" class="button primary">Save mail settings</button></div>
      </form>
    </section>
    <section class="panel settings-panel">
      <div class="panel-header"><div><h2>Send Test Email</h2><p>Tests saved settings. Use SMTP test before changing your active transport.</p></div><span class="pill <?= $lastTest==='success'?'pill-success':($lastTest==='failed'?'pill-danger':'pill-neutral') ?>"><?= e($lastTest==='success'?'Successful':($lastTest==='failed'?'Failed':'Never tested')) ?></span></div>
      <form class="settings-form" method="post" action="<?= e(admin_url('settings/mail')) ?>">
        <?= \Monitor\Core\Security::csrfField() ?><input type="hidden" name="action" value="test">
        <div class="form-section">
          <label for="mail-test-recipient">Test Recipient<input type="email" id="mail-test-recipient" name="test_recipient" required maxlength="190" value="<?= e($testRecipient) ?>"></label>
          <label for="mail-test-transport">Transport to test<select id="mail-test-transport" name="test_transport"><option value="smtp">SMTP (even if PHP mail is active)</option><option value="active">Currently active transport</option></select></label>
          <p class="field-help">Last test: <?= e(display_date(($settings['mail_last_test_at']??'')?:null)) ?>. A successful submission does not guarantee delivery to the inbox.</p>
          <?php if ($lastTest==='failed' && ($settings['mail_last_test_error']??'')!==''): ?><p class="mail-last-error" role="status"><?= e((string)$settings['mail_last_test_error']) ?></p><?php endif; ?>
        </div>
        <div class="save-bar"><span>Test sends one message and never creates an OTP or incident.</span><button type="submit" class="button primary">Send Test Email</button></div>
      </form>
    </section>
  </div>
</div>
