<?php
$settingsOk  = flash('settings_ok');
$settingsErr = flash('settings_err');
$stitchingTypes = getStitchingTypes();
$panchaTypes    = getPanchaTypes();
?>
<div class="page-header">
  <h2>&#9881; Settings</h2>
</div>

<?php if ($settingsOk): ?>
<div class="alert alert-success"><?= h($settingsOk) ?></div>
<?php endif; ?>
<?php if ($settingsErr): ?>
<div class="alert alert-error"><?= h($settingsErr) ?></div>
<?php endif; ?>

<form method="POST" action="?action=save_settings">
<input type="hidden" name="csrf" value="<?= h(getCsrf()) ?>">

<div class="card">
  <div class="card-head">&#128179; Pricing Defaults</div>
  <div class="card-body">
    <div class="form-grid-2">
      <div class="form-group">
        <label>Default Stitching Price Rs.</label>
        <input type="number" name="default_stitching_price" min="0" step="50"
               value="<?= h(getSetting('default_stitching_price','2300')) ?>"
               placeholder="2300">
        <small style="color:#666; font-size:11px; margin-top:2px;">
          Used for new orders when no stitching type is selected.
        </small>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-head">&#127981; Shop Information</div>
  <div class="card-body">
    <div class="form-grid">
      <div class="form-group">
        <label>Shop Name</label>
        <input type="text" name="shop_name" value="<?= h(getSetting('shop_name','Larkana Fabrics')) ?>">
      </div>
      <div class="form-group">
        <label>Phone</label>
        <input type="text" name="shop_phone" value="<?= h(getSetting('shop_phone','0300-2151261')) ?>">
      </div>
      <div class="form-group">
        <label>Address</label>
        <input type="text" name="shop_address" value="<?= h(getSetting('shop_address','SOAN GARDEN, Shahid Arcade, Islamabad')) ?>">
      </div>
    </div>
  </div>
</div>

<div class="flex-row mb-8">
  <button type="submit" class="btn btn-success">&#10003; Save Settings</button>
</div>
</form>

<!-- STITCHING TYPES -->
<div class="card">
  <div class="card-head">&#9986; Stitching Types &amp; Prices</div>
  <div class="card-body">
    <div class="form-grid-2" style="align-items:start;">

      <!-- Add / Edit Form -->
      <div>
        <form method="POST" action="?action=save_stitching_type" id="st-form">
          <input type="hidden" name="csrf" value="<?= h(getCsrf()) ?>">
          <input type="hidden" name="st_id" id="st_id" value="">
          <div class="form-group mb-8">
            <label>Type Name *</label>
            <input type="text" name="st_name" id="st_name" required placeholder="e.g. Single Stitching">
          </div>
          <div class="form-group mb-8">
            <label>Price Rs. *</label>
            <input type="number" name="st_price" id="st_price" min="0" step="50" required placeholder="2300">
          </div>
          <button type="submit" class="btn btn-success btn-sm">&#10003; Save Type</button>
          <a href="#" onclick="resetSt();return false;" class="btn btn-sm" style="background:#78909c;color:#fff;">Reset</a>
        </form>
      </div>

      <!-- List -->
      <div>
        <?php if (empty($stitchingTypes)): ?>
        <p style="color:#999; font-size:12px;">No stitching types yet.</p>
        <?php else: ?>
        <table>
          <thead><tr><th>Type</th><th>Price</th><th>Actions</th></tr></thead>
          <tbody>
          <?php foreach ($stitchingTypes as $st): ?>
          <tr>
            <td class="bold"><?= h($st['name']) ?></td>
            <td><?= formatMoney($st['price']) ?></td>
            <td style="white-space:nowrap;">
              <a href="#" class="btn btn-info btn-sm"
                 onclick="editSt(<?= h($st['id']) ?>,<?= h(json_encode($st['name'])) ?>,<?= h($st['price']) ?>);return false;">Edit</a>
              <form method="POST" action="?action=delete_stitching_type" style="display:inline;"
                    onsubmit="return confirm('Delete stitching type: <?= h($st['name']) ?>?')">
                <input type="hidden" name="csrf" value="<?= h(getCsrf()) ?>">
                <input type="hidden" name="st_id" value="<?= h($st['id']) ?>">
                <button type="submit" class="btn btn-danger btn-sm">Del</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- PANCHA TYPES -->
<div class="card">
  <div class="card-head">&#127892; Pancha Types &amp; Prices</div>
  <div class="card-body">
    <div class="form-grid-2" style="align-items:start;">

      <!-- Add / Edit Form -->
      <div>
        <form method="POST" action="?action=save_pancha_type" id="pt-form">
          <input type="hidden" name="csrf" value="<?= h(getCsrf()) ?>">
          <input type="hidden" name="pt_id" id="pt_id" value="">
          <div class="form-group mb-8">
            <label>Type Name *</label>
            <input type="text" name="pt_name" id="pt_name" required placeholder="e.g. Pancha Jali">
          </div>
          <div class="form-group mb-8">
            <label>Price Rs. *</label>
            <input type="number" name="pt_price" id="pt_price" min="0" step="50" required placeholder="400">
          </div>
          <button type="submit" class="btn btn-success btn-sm">&#10003; Save Type</button>
          <a href="#" onclick="resetPt();return false;" class="btn btn-sm" style="background:#78909c;color:#fff;">Reset</a>
        </form>
      </div>

      <!-- List -->
      <div>
        <?php if (empty($panchaTypes)): ?>
        <p style="color:#999; font-size:12px;">No pancha types yet.</p>
        <?php else: ?>
        <table>
          <thead><tr><th>Type</th><th>Price</th><th>Actions</th></tr></thead>
          <tbody>
          <?php foreach ($panchaTypes as $pt): ?>
          <tr>
            <td class="bold"><?= h($pt['name']) ?></td>
            <td><?= formatMoney($pt['price']) ?></td>
            <td style="white-space:nowrap;">
              <a href="#" class="btn btn-info btn-sm"
                 onclick="editPt(<?= h($pt['id']) ?>,<?= h(json_encode($pt['name'])) ?>,<?= h($pt['price']) ?>);return false;">Edit</a>
              <form method="POST" action="?action=delete_pancha_type" style="display:inline;"
                    onsubmit="return confirm('Delete pancha type: <?= h($pt['name']) ?>?')">
                <input type="hidden" name="csrf" value="<?= h(getCsrf()) ?>">
                <input type="hidden" name="pt_id" value="<?= h($pt['id']) ?>">
                <button type="submit" class="btn btn-danger btn-sm">Del</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- DATABASE BACKUP -->
<div class="card">
  <div class="card-head">&#128190; Database Backup</div>
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

<script>
function resetSt() {
    document.getElementById('st_id').value = '';
    document.getElementById('st_name').value = '';
    document.getElementById('st_price').value = '';
}
function editSt(id, name, price) {
    document.getElementById('st_id').value = id;
    document.getElementById('st_name').value = name;
    document.getElementById('st_price').value = price;
    document.getElementById('st-form').scrollIntoView({behavior:'smooth'});
}
function resetPt() {
    document.getElementById('pt_id').value = '';
    document.getElementById('pt_name').value = '';
    document.getElementById('pt_price').value = '';
}
function editPt(id, name, price) {
    document.getElementById('pt_id').value = id;
    document.getElementById('pt_name').value = name;
    document.getElementById('pt_price').value = price;
    document.getElementById('pt-form').scrollIntoView({behavior:'smooth'});
}
</script>
