<?php
$settingsOk = flash('settings_ok');
?>
<div class="page-header">
  <h2>&#9881; Settings (سیٹنگز)</h2>
</div>

<?php if ($settingsOk): ?>
<div class="alert alert-success"><?= h($settingsOk) ?></div>
<?php endif; ?>

<form method="POST" action="?action=save_settings">
<input type="hidden" name="csrf" value="<?= h(getCsrf()) ?>">

<div class="card">
  <div class="card-head">&#128179; Pricing Defaults (قیمتیں)</div>
  <div class="card-body">
    <div class="form-grid-2">
      <div class="form-group">
        <label>Default Stitching Price (سلائی کی قیمت) Rs.</label>
        <input type="number" name="default_stitching_price" min="0" step="50"
               value="<?= h(getSetting('default_stitching_price','2000')) ?>"
               placeholder="2000">
        <small style="color:#666; font-size:11px; margin-top:2px;">
          This default is used for new orders. You can override per order on the order form.
        </small>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-head">&#127978; Shop Information (دکان کی معلومات)</div>
  <div class="card-body">
    <div class="form-grid">
      <div class="form-group">
        <label>Shop Name (دکان کا نام)</label>
        <input type="text" name="shop_name" value="<?= h(getSetting('shop_name','Larkana Tailors & Cloth House')) ?>">
      </div>
      <div class="form-group">
        <label>Phone (فون نمبر)</label>
        <input type="text" name="shop_phone" value="<?= h(getSetting('shop_phone','0300-2151261')) ?>">
      </div>
      <div class="form-group">
        <label>Address (پتہ)</label>
        <input type="text" name="shop_address" value="<?= h(getSetting('shop_address','SOAN GARDEN, Shahid Arcade, Islamabad')) ?>">
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-head">&#128190; Database Backup (ڈیٹا بیک اپ)</div>
  <div class="card-body">
    <p style="font-size:12px; margin-bottom:8px;">
      All data is stored in <strong>data/larkana.db</strong> (SQLite file).<br>
      To back up, simply copy that file to a USB drive or cloud storage.
    </p>
    <a href="?action=backup_db" class="btn btn-primary btn-sm" onclick="return confirm('This will download a copy of the database. Continue?')">
      &#128190; Download Database Backup
    </a>
  </div>
</div>

<div class="flex-row mb-8">
  <button type="submit" class="btn btn-success">&#10003; Save Settings</button>
</div>
</form>
