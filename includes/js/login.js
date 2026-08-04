document.addEventListener('DOMContentLoaded', function () {
    const loginForm = document.getElementById('loginForm');

    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const rememberMe = document.querySelector('input[name="remember"]').checked;

            // Simple validation
            if (!username || !password) {
                alert('Please enter both username and password');
                return;
            }

            // Simulate login process
            console.log('Login attempt with:', { username, password, rememberMe });

            // Here you would typically make an AJAX call to your backend
            // For demo purposes, we'll simulate a successful login
            setTimeout(() => {
                // Redirect based on user type (in a real app, this would come from the server)
                if (username.toLowerCase().includes('admin')) {
                    window.location.href = 'admin-dashboard.html';
                } else {
                    window.location.href = 'customer-home.html';
                }
            }, 1000);
        });
    }

    // Need help button functionality
    const needHelpBtn = document.querySelector('.need-help-btn');
    if (needHelpBtn) {
        needHelpBtn.addEventListener('click', function (e) {
            e.preventDefault();
            window.location.href = 'contact.php';
        });
    }
});
