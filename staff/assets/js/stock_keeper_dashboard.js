// --- 1. Low Stock Toggle ---
function toggleLowStock() {
    const list = document.getElementById('lowStockList');
    const arrow = document.getElementById('lowStockArrow');
    if (list && arrow) {
        if (list.style.display === 'none') {
            list.style.display = 'block';
            arrow.style.transform = 'rotate(180deg)';
        } else {
            list.style.display = 'none';
            arrow.style.transform = 'rotate(0deg)';
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {

    // --- 2. Category Tables Collapse/Expand (unified system) ---
    // Panels are collapsed by default via CSS (max-height: 0)
    // The togglePanel function is defined inline in the PHP page
    // This file only handles the search filter and modal logic now.


    // --- 3. Product Search Filter ---
    const productSearch = document.getElementById('productSearch');
    if (productSearch) {
        productSearch.addEventListener('input', function () {
            const q = this.value.toLowerCase();
            document.querySelectorAll('.cat-panel').forEach(panel => {
                const rows = panel.querySelectorAll('tbody tr');
                if (!rows.length) return;
                let visible = 0;
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    const show = text.includes(q);
                    row.style.display = show ? '' : 'none';
                    if (show) visible++;
                });
                panel.style.display = (!q || visible > 0) ? '' : 'none';
            });
        });
    }

    // --- 4. Dynamic Specifications Loader (Add Form) ---
    const categorySelect = document.getElementById('add_categorySelect');
    const specsContainer = document.getElementById('dynamic_specs_container');
    const brandInput = document.querySelector('input[name="p_brand"]');

    function loadSpecFields() {
        if (!categorySelect || !specsContainer) return;
        const categoryText = categorySelect.options[categorySelect.selectedIndex].text.toLowerCase();
        let html = '';

        if (categoryText.includes('laptop') || categoryText.includes('desktop')) {
            if (brandInput) brandInput.setAttribute('list', 'brands_computers');
            html = `
                <div><label>Processor</label><input type="hidden" name="spec_names[]" value="processor"><input type="text" name="spec_values[]" class="form-control" list="proc_list" placeholder="e.g. Intel Core i7"></div>
                <div><label>RAM</label><input type="hidden" name="spec_names[]" value="ram"><input type="text" name="spec_values[]" class="form-control" list="ram_list" placeholder="e.g. 16GB DDR5"></div>
                <div><label>Storage</label><input type="hidden" name="spec_names[]" value="storage"><input type="text" name="spec_values[]" class="form-control" list="storage_list" placeholder="e.g. 512GB SSD"></div>
                <div><label>Graphics Card</label><input type="hidden" name="spec_names[]" value="grpCard"><input type="text" name="spec_values[]" class="form-control" list="gpu_list" placeholder="e.g. NVIDIA RTX 4060"></div>
                ${categoryText.includes('laptop') ? '<div><label>Screen Size</label><input type="hidden" name="spec_names[]" value="scrSiz"><input type="text" name="spec_values[]" class="form-control" list="screen_list" placeholder="e.g. 15.6 inch"></div>' : ''}
                <div><label>Use Type</label><input type="hidden" name="spec_names[]" value="useType"><input type="text" name="spec_values[]" class="form-control" list="use_list" placeholder="e.g. Gaming / Business"></div>
            `;
        } else if (categoryText.includes('component')) {
            if (brandInput) brandInput.setAttribute('list', 'brands_components');
            html = `<div><label>Component Type</label><input type="hidden" name="spec_names[]" value="useType"><input type="text" name="spec_values[]" class="form-control" list="component_type_list" placeholder="e.g. Motherboard / RAM"></div>`;
        } else if (categoryText.includes('accessori')) {
            if (brandInput) brandInput.setAttribute('list', 'brands_accessories');
            html = `<div><label>Accessory Type</label><input type="hidden" name="spec_names[]" value="useType"><input type="text" name="spec_values[]" class="form-control" list="accessory_type_list" placeholder="e.g. Gaming Mouse"></div>`;
        } else if (categoryText.includes('audio')) {
            if (brandInput) brandInput.setAttribute('list', 'brands_audio');
            html = `<div><label>Audio Type</label><input type="hidden" name="spec_names[]" value="useType"><input type="text" name="spec_values[]" class="form-control" list="audio_type_list" placeholder="e.g. Headphones"></div>`;
        } else if (categoryText.includes('storage')) {
            if (brandInput) brandInput.setAttribute('list', 'brands_storage');
            html = `
                <div><label>Storage Capacity</label><input type="hidden" name="spec_names[]" value="storage"><input type="text" name="spec_values[]" class="form-control" list="storage_list" placeholder="e.g. 1TB / 2TB"></div>
                <div><label>Storage Type</label><input type="hidden" name="spec_names[]" value="useType"><input type="text" name="spec_values[]" class="form-control" list="storage_type_list" placeholder="e.g. Internal NVMe SSD"></div>
            `;
        } else {
            if (brandInput) brandInput.setAttribute('list', 'brands_general');
            html = `<div><label>Product Type</label><input type="hidden" name="spec_names[]" value="useType"><input type="text" name="spec_values[]" class="form-control" list="use_list" placeholder="e.g. Router"></div>`;
        }
        specsContainer.innerHTML = html;
    }

    if (categorySelect) {
        categorySelect.addEventListener('change', loadSpecFields);
        loadSpecFields(); // First, show the fields when the page loads.
    }

    // --- 5. Edit Product Modal Logic ---
    const editBtns = document.querySelectorAll('.btn-edit-prod');
    const modal = document.getElementById('prodModal');
    const closeBtn = document.getElementById('closeProdModal');
    const editCatSelect = document.getElementById('edit_categoryID');

    function loadEditSpecFields(categoryText, specsObj = {}) {
        const container = document.getElementById('edit_dynamic_specs_container');
        const eBrandInput = document.getElementById('edit_brand');
        if (!container) return;

        let html = '';
        const cat = categoryText.toLowerCase();
        const getVal = (key) => specsObj[key] ? specsObj[key] : '';

        if (cat.includes('laptop') || cat.includes('desktop')) {
            if (eBrandInput) eBrandInput.setAttribute('list', 'brands_computers');
            html = `
                <div><label>Processor</label><input type="hidden" name="edit_spec_names[]" value="processor"><input type="text" name="edit_spec_values[]" class="form-control" list="proc_list" value="${getVal('processor')}"></div>
                <div><label>RAM</label><input type="hidden" name="edit_spec_names[]" value="ram"><input type="text" name="edit_spec_values[]" class="form-control" list="ram_list" value="${getVal('ram')}"></div>
                <div><label>Storage</label><input type="hidden" name="edit_spec_names[]" value="storage"><input type="text" name="edit_spec_values[]" class="form-control" list="storage_list" value="${getVal('storage')}"></div>
                <div><label>Graphics Card</label><input type="hidden" name="edit_spec_names[]" value="grpCard"><input type="text" name="edit_spec_values[]" class="form-control" list="gpu_list" value="${getVal('grpCard')}"></div>
                ${cat.includes('laptop') ? `<div><label>Screen Size</label><input type="hidden" name="edit_spec_names[]" value="scrSiz"><input type="text" name="edit_spec_values[]" class="form-control" list="screen_list" value="${getVal('scrSiz')}"></div>` : ''}
                <div><label>Use Type</label><input type="hidden" name="edit_spec_names[]" value="useType"><input type="text" name="edit_spec_values[]" class="form-control" list="use_list" value="${getVal('useType')}"></div>
            `;
        } else if (cat.includes('component')) {
            if (eBrandInput) eBrandInput.setAttribute('list', 'brands_components');
            html = `<div><label>Component Type</label><input type="hidden" name="edit_spec_names[]" value="useType"><input type="text" name="edit_spec_values[]" class="form-control" list="component_type_list" value="${getVal('useType')}"></div>`;
        } else if (cat.includes('accessori')) {
            if (eBrandInput) eBrandInput.setAttribute('list', 'brands_accessories');
            html = `<div><label>Accessory Type</label><input type="hidden" name="edit_spec_names[]" value="useType"><input type="text" name="edit_spec_values[]" class="form-control" list="accessory_type_list" value="${getVal('useType')}"></div>`;
        } else if (cat.includes('audio')) {
            if (eBrandInput) eBrandInput.setAttribute('list', 'brands_audio');
            html = `<div><label>Audio Type</label><input type="hidden" name="edit_spec_names[]" value="useType"><input type="text" name="edit_spec_values[]" class="form-control" list="audio_type_list" value="${getVal('useType')}"></div>`;
        } else if (cat.includes('storage')) {
            if (eBrandInput) eBrandInput.setAttribute('list', 'brands_storage');
            html = `
                <div><label>Storage Capacity</label><input type="hidden" name="edit_spec_names[]" value="storage"><input type="text" name="edit_spec_values[]" class="form-control" list="storage_list" value="${getVal('storage')}"></div>
                <div><label>Storage Type</label><input type="hidden" name="edit_spec_names[]" value="useType"><input type="text" name="edit_spec_values[]" class="form-control" list="storage_type_list" value="${getVal('useType')}"></div>
            `;
        } else {
            if (eBrandInput) eBrandInput.setAttribute('list', 'brands_general');
            html = `<div><label>Product Type</label><input type="hidden" name="edit_spec_names[]" value="useType"><input type="text" name="edit_spec_values[]" class="form-control" list="use_list" value="${getVal('useType')}"></div>`;
        }
        container.innerHTML = html;
    }

    if (editCatSelect) {
        editCatSelect.addEventListener('change', function () {
            loadEditSpecFields(this.options[this.selectedIndex].text, {});
        });
    }

    editBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('edit_product_id').value = this.getAttribute('data-id');
            document.getElementById('edit_name').value = this.getAttribute('data-name');
            document.getElementById('edit_brand').value = this.getAttribute('data-brand');
            document.getElementById('edit_description').value = this.getAttribute('data-desc');
            document.getElementById('edit_price').value = this.getAttribute('data-price');
            document.getElementById('edit_old_price').value = this.getAttribute('data-old');
            document.getElementById('edit_qty').value = this.getAttribute('data-qty');
            document.getElementById('edit_warranty').value = this.getAttribute('data-war');

            const catID = this.getAttribute('data-cat');
            for (let i = 0; i < editCatSelect.options.length; i++) {
                if (editCatSelect.options[i].value == catID) { editCatSelect.selectedIndex = i; break; }
            }

            const statSel = document.getElementById('edit_status');
            const status = this.getAttribute('data-stat');
            for (let i = 0; i < statSel.options.length; i++) {
                if (statSel.options[i].value === status) { statSel.selectedIndex = i; break; }
            }

            let specsObj = {};
            const specsJson = this.getAttribute('data-specs');
            if (specsJson) {
                try { specsObj = JSON.parse(specsJson); } catch (e) { }
            }

            loadEditSpecFields(editCatSelect.options[editCatSelect.selectedIndex].text, specsObj);

            modal.style.display = 'flex';
        });
    });

    if (closeBtn) closeBtn.addEventListener('click', () => modal.style.display = 'none');

    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === this) this.style.display = 'none';
        });
    }
});