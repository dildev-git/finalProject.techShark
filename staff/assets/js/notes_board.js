document.addEventListener('DOMContentLoaded', function () {
    const notesBadge = document.getElementById('notesBadge');

    // Check if you are currently in notes board.php
    const isNotesPage = window.location.pathname.includes('notes_board.php');

    function updateNotesBadge() {
        // If on the Notes page, no need to show the badge.
        if (isNotesPage) {
            if (notesBadge) notesBadge.style.display = 'none';
            return;
        }

        // Retrieving new notes from the API every 5 seconds
        fetch('../api/get_unread_notes.php')
            .then(res => res.json())
            .then(data => {
                const count = data.count || 0;
                if (count > 0 && notesBadge) {
                    notesBadge.textContent = count > 99 ? '99+' : count;
                    if (notesBadge.style.display === 'none' || notesBadge.style.display === '') {
                        notesBadge.style.display = 'inline-block';
                        notesBadge.style.transform = 'scale(0)';
                        setTimeout(() => {
                            notesBadge.style.transition = 'transform 0.3s ease';
                            notesBadge.style.transform = 'scale(1)';
                        }, 10);
                    } else {
                        notesBadge.textContent = count > 99 ? '99+' : count;
                    }
                } else if (notesBadge) {
                    notesBadge.style.display = 'none';
                }
            })
            .catch(() => {
                // If a network error occurs, do nothing
            });
    }

    if (notesBadge) {
        updateNotesBadge();
        setInterval(updateNotesBadge, 5000);
    }
});