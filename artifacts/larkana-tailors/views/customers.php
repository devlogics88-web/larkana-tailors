<?php
$search    = trim($_GET['q'] ?? '');
$customers = getCustomersWithBalance($search);
$csrfToken = getCsrf();
?>
<div class="page-header">
  <h2>&#128101; Customers</h2>
  <button type="button" class="btn btn-success btn-sm" onclick="toggleAddForm()">&#43; Add Customer</button>
</div>

<?php if ($msg = flash('customer_ok')): ?>
<div class="alert alert-success"><?= h($msg) ?></div>
<?php endif; ?>
<?php if ($err = flash('customer_err')): ?>
<div class="alert alert-error"><?= h($err) ?></div>
<?php endif; ?>

<!-- ADD CUSTOMER FORM -->
<div class="card" id="add-customer-card" style="display:none;">
  <div class="card-head" style="background:#2e7d32; color:#fff;">&#43; Add New Customer</div>
  <div class="card-body">
    <div id="add-cust-msg" class="alert alert-error" style="display:none;"></div>
    <div class="form-grid">
      <div class="form-group">
        <label>Name *</label>
        <input type="text" id="ac_name" placeholder="Full Name">
      </div>
      <div class="form-group">
        <label>Phone</label>
        <input type="text" id="ac_phone" placeholder="0300-0000000">
      </div>
      <div class="form-group">
        <label>Address</label>
        <input type="text" id="ac_address" placeholder="City / Area">
      </div>
    </div>
    <div style="margin-top:8px; display:flex; gap:8px;">
      <button type="button" class="btn btn-success btn-sm" onclick="saveNewCustomer()">&#10003; Save Customer</button>
      <button type="button" class="btn btn-sm" style="background:#546e7a;color:#fff;" onclick="toggleAddForm()">Cancel</button>
    </div>
  </div>
</div>

<!-- EDIT CUSTOMER MODAL -->
<div id="edit-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.6); z-index:1000; align-items:center; justify-content:center;">
  <div style="background:#1e2d3b; border:1px solid #2f4a5f; border-radius:4px; padding:20px; min-width:340px; max-width:480px; width:90%;">
    <div style="font-weight:bold; color:#ffd54f; font-size:15px; margin-bottom:12px;">&#9999; Edit Customer</div>
    <div id="edit-cust-msg" class="alert alert-error" style="display:none;"></div>
    <input type="hidden" id="ec_id">
    <div class="form-group" style="margin-bottom:8px;">
      <label>Name *</label>
      <input type="text" id="ec_name" placeholder="Full Name">
    </div>
    <div class="form-group" style="margin-bottom:8px;">
      <label>Phone</label>
      <input type="text" id="ec_phone" placeholder="0300-0000000">
    </div>
    <div class="form-group" style="margin-bottom:12px;">
      <label>Address</label>
      <input type="text" id="ec_address" placeholder="City / Area">
    </div>
    <div style="display:flex; gap:8px;">
      <button type="button" class="btn btn-success btn-sm" onclick="submitEditCustomer()">&#10003; Save Changes</button>
      <button type="button" class="btn btn-sm" style="background:#546e7a;color:#fff;" onclick="closeEditModal()">Cancel</button>
    </div>
  </div>
</div>

<!-- SEARCH + TABLE -->
<div class="card">
  <div class="card-body">
    <form method="GET" action="" class="search-box">
      <input type="hidden" name="page" value="customers">
      <input type="text" name="q" value="<?= h($search) ?>" placeholder="Search by name or phone..." autofocus>
      <button type="submit" class="btn btn-primary btn-sm">&#128269; Search</button>
      <?php if ($search): ?>
      <a href="?page=customers" class="btn btn-sm" style="background:#546e7a;color:#fff;">Clear</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<?php if ($search && empty($customers)): ?>
<div class="alert alert-info">No customers found for "<?= h($search) ?>".</div>
<?php elseif (!empty($customers)): ?>
<div class="card">
  <div class="card-head">&#128101; <?= $search ? 'Search Results' : 'All Customers' ?> (<?= count($customers) ?>)</div>
  <div class="card-body" style="padding:0;">
    <table>
      <thead>
        <tr>
          <th>CID</th>
          <th>Name</th>
          <th>Phone</th>
          <th>Address</th>
          <th>Orders</th>
          <th>Outstanding</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="customers-tbody">
        <?php foreach ($customers as $c):
          $outstanding = (float)($c['total_outstanding'] ?? 0);
          $hasArrears  = (int)($c['has_arrears'] ?? ($outstanding > 0 ? 1 : 0));
        ?>
        <tr id="cust-tr-<?= $c['id'] ?>">
          <td style="font-family:monospace; font-size:11px; font-weight:bold; color:#4fc3f7;">CID-<?= str_pad($c['id'], 5, '0', STR_PAD_LEFT) ?></td>
          <td class="bold"><?= h($c['name']) ?></td>
          <td><?= h($c['phone'] ?? '-') ?></td>
          <td><?= h($c['address'] ?? '-') ?></td>
          <td class="text-center"><?= (int)($c['order_count'] ?? 0) ?></td>
          <td>
            <?php if ($hasArrears): ?>
            <span class="badge-arrears">Rs.<?= number_format($outstanding, 0) ?></span>
            <?php else: ?>
            <span class="badge-paid">&#10003; Paid</span>
            <?php endif; ?>
          </td>
          <td style="white-space:nowrap;">
            <a href="?page=customer_orders&customer_id=<?= h($c['id']) ?>" class="btn btn-info btn-sm">Orders</a>
            <a href="?page=order_new&prefill_customer=<?= h($c['id']) ?>" class="btn btn-success btn-sm">+ Order</a>
            <button type="button" class="btn btn-sm" style="background:#0277bd;color:#fff;"
                    onclick="openEditModal(<?= (int)$c['id'] ?>, <?= json_encode($c['name']) ?>, <?= json_encode($c['phone'] ?? '') ?>, <?= json_encode($c['address'] ?? '') ?>)">Edit</button>
            <?php if (isAdmin()): ?>
            <button type="button" class="btn btn-danger btn-sm"
                    onclick="deleteCustomer(<?= (int)$c['id'] ?>, <?= json_encode($c['name']) ?>)">Del</button>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php elseif (!$search): ?>
<div class="alert alert-info">No customers yet. Click <strong>+ Add Customer</strong> above to add your first customer.</div>
<?php endif; ?>

<?php if (isAdmin()): ?>
<!-- DANGER ZONE -->
<div class="card" style="border:2px solid #c62828; margin-top:16px;">
  <div class="card-head" style="background:#c62828; color:#fff;">&#9888; Danger Zone</div>
  <div class="card-body">
    <p style="color:#c62828; font-weight:bold; margin-bottom:8px;">WARNING: Permanently deletes ALL customers, orders and measurements. Cannot be undone.</p>
    <button type="button" class="btn btn-danger" onclick="showDeleteAllCustomers()">&#128465; Delete All Customer Records</button>
    <div id="delete-customers-confirm" style="display:none; margin-top:12px; background:#fff3e0; padding:10px; border:1px solid #e65100;">
      <p style="margin:0 0 8px; color:#bf360c; font-weight:bold;">Type <strong>OK</strong> to confirm:</p>
      <form method="POST" action="?action=delete_all_customers" onsubmit="return validateDeleteAll(this)">
        <input type="hidden" name="csrf" value="<?= h(getCsrf()) ?>">
        <input type="text" name="confirm_word" id="confirm_word_customers" autocomplete="off"
               style="width:100px; margin-right:8px; font-size:14px; font-weight:bold; letter-spacing:2px;" placeholder="OK">
        <button type="submit" class="btn btn-danger">Confirm Delete All</button>
        <button type="button" class="btn" style="background:#546e7a;color:#fff;"
                onclick="document.getElementById('delete-customers-confirm').style.display='none'">Cancel</button>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
var csrfToken = '<?= h($csrfToken) ?>';

function toggleAddForm() {
    var card = document.getElementById('add-customer-card');
    card.style.display = card.style.display === 'none' ? 'block' : 'none';
    if (card.style.display === 'block') document.getElementById('ac_name').focus();
}

function saveNewCustomer() {
    var name    = document.getElementById('ac_name').value.trim();
    var phone   = document.getElementById('ac_phone').value.trim();
    var address = document.getElementById('ac_address').value.trim();
    var msgEl   = document.getElementById('add-cust-msg');
    if (!name) { msgEl.textContent = 'Name is required.'; msgEl.style.display = 'block'; return; }
    msgEl.style.display = 'none';

    var fd = new FormData();
    fd.append('csrf', csrfToken);
    fd.append('name', name);
    fd.append('phone', phone);
    fd.append('address', address);

    fetch('?action=save_customer_ajax', { method:'POST', body:fd })
        .then(function(r){ return r.json(); })
        .then(function(data) {
            if (!data.success) { msgEl.textContent = data.error || 'Error saving.'; msgEl.style.display='block'; return; }
            window.location.reload();
        })
        .catch(function(){ msgEl.textContent = 'Network error.'; msgEl.style.display='block'; });
}

function openEditModal(id, name, phone, address) {
    document.getElementById('ec_id').value      = id;
    document.getElementById('ec_name').value    = name;
    document.getElementById('ec_phone').value   = phone || '';
    document.getElementById('ec_address').value = address || '';
    document.getElementById('edit-cust-msg').style.display = 'none';
    var ov = document.getElementById('edit-overlay');
    ov.style.display = 'flex';
    document.getElementById('ec_name').focus();
}

function closeEditModal() {
    document.getElementById('edit-overlay').style.display = 'none';
}

function submitEditCustomer() {
    var id      = document.getElementById('ec_id').value;
    var name    = document.getElementById('ec_name').value.trim();
    var phone   = document.getElementById('ec_phone').value.trim();
    var address = document.getElementById('ec_address').value.trim();
    var msgEl   = document.getElementById('edit-cust-msg');
    if (!name) { msgEl.textContent = 'Name is required.'; msgEl.style.display='block'; return; }
    msgEl.style.display = 'none';

    var fd = new FormData();
    fd.append('csrf', csrfToken);
    fd.append('customer_id', id);
    fd.append('name', name);
    fd.append('phone', phone);
    fd.append('address', address);

    fetch('?action=save_customer_ajax', { method:'POST', body:fd })
        .then(function(r){ return r.json(); })
        .then(function(data) {
            if (!data.success) { msgEl.textContent = data.error || 'Error saving.'; msgEl.style.display='block'; return; }
            closeEditModal();
            window.location.reload();
        })
        .catch(function(){ msgEl.textContent = 'Network error.'; msgEl.style.display='block'; });
}

function deleteCustomer(id, name) {
    if (!confirm('Delete customer "' + name + '" and ALL their orders? This cannot be undone.')) return;
    var fd = new FormData();
    fd.append('csrf', csrfToken);
    fd.append('customer_id', id);
    fetch('?action=delete_customer_ajax', { method:'POST', body:fd })
        .then(function(r){ return r.json(); })
        .then(function(data) {
            if (data.success) {
                var row = document.getElementById('cust-tr-' + id);
                if (row) row.remove();
            } else {
                alert(data.error || 'Error deleting customer.');
            }
        })
        .catch(function(){ alert('Network error.'); });
}

function showDeleteAllCustomers() {
    document.getElementById('delete-customers-confirm').style.display = 'block';
    document.getElementById('confirm_word_customers').focus();
}

function validateDeleteAll(form) {
    var wordField = form.querySelector('[name="confirm_word"]');
    if (!wordField || wordField.value.trim() !== 'OK') {
        alert('You must type exactly OK to confirm.');
        return false;
    }
    return confirm('FINAL WARNING: All customer data will be permanently deleted. Proceed?');
}

document.getElementById('edit-overlay').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});
</script>
