/* Larkana Tailors - App JS */

// Customer search AJAX
function searchCustomer() {
    const q = document.getElementById('customer_search_q')?.value.trim();
    if (!q || q.length < 2) return;
    fetch('?action=search_customer&q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(data => {
            const list = document.getElementById('customer_results');
            if (!list) return;
            list.innerHTML = '';
            if (!data.length) {
                list.innerHTML = '<div class="search-result-item no-result">No customer found. <a href="#" onclick="setNewCustomer()">Add as New</a></div>';
                return;
            }
            data.forEach(c => {
                const div = document.createElement('div');
                div.className = 'search-result-item';
                div.innerHTML = `<strong>${escHtml(c.name)}</strong> &mdash; ${escHtml(c.phone || '-')} <span class="small">${escHtml(c.address || '')}</span>`;
                div.onclick = () => selectCustomer(c);
                list.appendChild(div);
            });
        });
}

function escHtml(s) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(s || ''));
    return d.innerHTML;
}

function selectCustomer(c) {
    document.getElementById('customer_id').value = c.id;
    document.getElementById('customer_name_display').textContent = c.name + ' (' + (c.phone || '') + ')' + (c.address ? ' — ' + c.address : '');
    document.getElementById('customer_panel').style.display = 'block';
    document.getElementById('customer_results').innerHTML = '';
    document.getElementById('customer_search_q').value = '';
    document.getElementById('new_customer_section').style.display = 'none';
}

function setNewCustomer() {
    document.getElementById('customer_id').value = '';
    document.getElementById('customer_panel').style.display = 'none';
    document.getElementById('new_customer_section').style.display = 'block';
    document.getElementById('customer_results').innerHTML = '';
}

function clearCustomer() {
    document.getElementById('customer_id').value = '';
    document.getElementById('customer_panel').style.display = 'none';
    document.getElementById('new_customer_section').style.display = 'none';
}

// Cloth source toggle
function toggleClothSource(val) {
    const shopFields = document.getElementById('shop_cloth_fields');
    if (!shopFields) return;
    shopFields.style.display = val === 'shop' ? 'block' : 'none';
}

// Calculate remaining
function calcRemaining() {
    const total   = parseFloat(document.getElementById('total_price')?.value) || 0;
    const advance = parseFloat(document.getElementById('advance_paid')?.value) || 0;
    const rem = document.getElementById('remaining');
    if (rem) rem.value = (total - advance).toFixed(0);
}

// Print invoice
function printInvoice() {
    window.print();
}

// Confirm delete
function confirmDelete(msg) {
    return confirm(msg || 'Are you sure you want to delete this record?');
}

// Auto-dismiss alerts
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(a => {
            a.style.transition = 'opacity .5s';
            a.style.opacity = '0';
            setTimeout(() => a.remove(), 600);
        });
    }, 4000);

    // Attach calc listeners
    ['total_price', 'advance_paid'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', calcRemaining);
    });

    // Init cloth source
    const clothSrc = document.querySelector('input[name="cloth_source"]:checked');
    if (clothSrc) toggleClothSource(clothSrc.value);
});

// Stock item: read from data-* attribute to avoid inline JSON injection risk.
function editStockFromData(el) {
    editStock(JSON.parse(el.dataset.stock));
}

// Stock item: fill meters when editing
function editStock(data) {
    document.getElementById('stock_id').value      = data.id;
    document.getElementById('brand_name').value    = data.brand_name;
    document.getElementById('cloth_type').value    = data.cloth_type || '';
    document.getElementById('total_meters').value  = data.total_meters;
    document.getElementById('avail_meters').value  = data.available_meters;
    document.getElementById('cost_meter').value    = data.cost_per_meter;
    document.getElementById('sell_meter').value    = data.sell_per_meter || '';
    document.getElementById('stock_notes').value   = data.notes || '';
    document.getElementById('stock_form_title').textContent = 'Edit Stock Item';
    document.getElementById('stock-form').scrollIntoView({ behavior: 'smooth' });
}

function resetStock() {
    document.getElementById('stock_id').value      = '';
    document.getElementById('brand_name').value    = '';
    document.getElementById('cloth_type').value    = '';
    document.getElementById('total_meters').value  = '';
    document.getElementById('avail_meters').value  = '';
    document.getElementById('cost_meter').value    = '';
    document.getElementById('sell_meter').value    = '';
    document.getElementById('stock_notes').value   = '';
    document.getElementById('stock_form_title').textContent = 'Add New Stock Item';
}
