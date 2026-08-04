document.addEventListener('DOMContentLoaded', function () {

    // --- Inquiry Search Filter ---
    const inquirySearch = document.getElementById('inquirySearch');

    if (inquirySearch) {
        inquirySearch.addEventListener('input', function () {
            const q = this.value.toLowerCase();

            // Checking both the Pending and Resolved panels
            document.querySelectorAll('.inquiry-panel').forEach(panel => {
                const rows = panel.querySelectorAll('tbody tr.inquiry-row');
                if (!rows.length) return;

                let visible = 0;
                rows.forEach(row => {
                    const searchAttr = row.getAttribute('data-search') || '';
                    const text = row.textContent.toLowerCase();
                    const show = searchAttr.includes(q) || text.includes(q);

                    row.style.display = show ? '' : 'none';
                    if (show) visible++;
                });

                // Show nothing on the panel or hide that panel
                panel.style.display = (!q || visible > 0) ? '' : 'none';
            });
        });
    }

});