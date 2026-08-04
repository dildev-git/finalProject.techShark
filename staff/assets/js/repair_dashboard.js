document.addEventListener('DOMContentLoaded', function () {

    // --- 1. Repair Search Filter ---
    const repairSearch = document.getElementById('repairSearch');
    if (repairSearch) {
        repairSearch.addEventListener('input', function () {
            const q = this.value.toLowerCase();
            document.querySelectorAll('.repair-panel').forEach(panel => {
                const rows = panel.querySelectorAll('tbody tr.repair-row');
                if (!rows.length) return;

                let visible = 0;
                rows.forEach(row => {
                    const searchAttr = row.getAttribute('data-search') || '';
                    const text = row.textContent.toLowerCase();
                    const show = searchAttr.includes(q) || text.includes(q);

                    row.style.display = show ? '' : 'none';
                    if (show) visible++;
                });

                panel.style.display = (!q || visible > 0) ? '' : 'none';
            });
        });
    }

    // --- 2. Edit Repair Modal Logic ---
    const editBtns = document.querySelectorAll('.btn-edit-repair');
    const modal = document.getElementById('repairModal');
    const closeBtn = document.getElementById('closeRepairModal');

    editBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('edit_repair_id').value = this.getAttribute('data-id');
            document.getElementById('edit_r_device').value = this.getAttribute('data-device');
            document.getElementById('edit_r_issue').value = this.getAttribute('data-issue');
            document.getElementById('edit_r_cost').value = this.getAttribute('data-cost');

            const cid = this.getAttribute('data-cid');
            const cSel = document.getElementById('edit_r_customer');
            for (let i = 0; i < cSel.options.length; i++) {
                if (cSel.options[i].value == cid) { cSel.selectedIndex = i; break; }
            }
            // Update the customer detail tooltip for the edit modal
            cSel.dispatchEvent(new Event('change'));

            const status = this.getAttribute('data-status');
            const sSel = document.getElementById('edit_r_status');
            for (let i = 0; i < sSel.options.length; i++) {
                if (sSel.options[i].value === status) { sSel.selectedIndex = i; break; }
            }

            modal.style.display = 'flex';
        });
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', () => modal.style.display = 'none');
    }

    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === this) this.style.display = 'none';
        });
    }
});