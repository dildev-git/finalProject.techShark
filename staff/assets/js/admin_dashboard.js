document.addEventListener('DOMContentLoaded', function () {

    // --- 1. Customer Search Filter ---
    const customerSearch = document.getElementById('customerSearch');
    if (customerSearch) {
        customerSearch.addEventListener('keyup', function () {
            filterSingleTable('customerSearch', 'customerTableBody');
        });
    }

    // --- 2. Staff Search Filter ---
    const staffSearch = document.getElementById('staffSearch');
    if (staffSearch) {
        staffSearch.addEventListener('keyup', function () {
            filterSingleTable('staffSearch', 'staffTableBody');
        });
    }

    // Generic Table Filter Function
    function filterSingleTable(inputId, tbodyId) {
        const q = document.getElementById(inputId).value.toLowerCase();
        const rows = document.getElementById(tbodyId).querySelectorAll('tr');
        rows.forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    }

    // --- 3. Edit Staff Modal Logic ---
    const editButtons = document.querySelectorAll('.btn-edit-staff');
    const modal = document.getElementById('editModal');
    const closeBtn = document.getElementById('closeModalBtn');

    editButtons.forEach(button => {
        button.addEventListener('click', function () {
            document.getElementById('edit_staff_id').value = this.getAttribute('data-id');
            document.getElementById('edit_fullName').value = this.getAttribute('data-name');
            document.getElementById('edit_NIC').value = this.getAttribute('data-nic');
            document.getElementById('edit_email').value = this.getAttribute('data-email');
            document.getElementById('edit_userName').value = this.getAttribute('data-uname');
            document.getElementById('edit_contactNo').value = this.getAttribute('data-phone');
            document.getElementById('edit_address').value = this.getAttribute('data-addr');
            document.getElementById('edit_city').value = this.getAttribute('data-city');
            document.getElementById('edit_dob').value = this.getAttribute('data-dob');

            const gender = this.getAttribute('data-gender');
            const role = this.getAttribute('data-role');

            const genSel = document.getElementById('edit_gender');
            for (let i = 0; i < genSel.options.length; i++) {
                if (genSel.options[i].value === gender) { genSel.selectedIndex = i; break; }
            }

            const roleSel = document.getElementById('edit_staff_type');
            for (let i = 0; i < roleSel.options.length; i++) {
                if (roleSel.options[i].value === role) { roleSel.selectedIndex = i; break; }
            }

            modal.style.display = 'flex';
        });
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', () => modal.style.display = 'none');
    }

    // Click outside to close
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === this) this.style.display = 'none';
        });
    }
});