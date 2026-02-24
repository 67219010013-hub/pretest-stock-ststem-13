<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PC Assembly Stock</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="login-body">
    <div class="login-card">
        <div class="login-logo">
            📦
        </div>
        <div class="login-header">
            <h1>Welcome Back</h1>
            <p>Access your PC assembly dashboard</p>
        </div>
        <form id="loginForm">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required placeholder="Enter your username">
            </div>
            <div class="form-group" style="margin-bottom: 2rem;">
                <label>Password</label>
                <div class="password-input-wrapper">
                    <input type="password" name="password" id="password" required placeholder="Enter your password"
                        style="padding-right: 3.5rem;">
                    <button type="button" class="password-toggle" onclick="togglePassword('password', this)">
                        👁️
                    </button>
                </div>
            </div>
            <div id="error-msg" class="error-message"></div>
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1rem;">Sign In
                to System</button>
            <div
                style="margin-top: 2rem; text-align: center; color: var(--text-muted); font-size: 0.9rem; border-top: 1px solid var(--border); padding-top: 1.5rem;">
                Don't have an account? <a href="register.php"
                    style="color: var(--primary); text-decoration: none; font-weight: 700;">Join now</a>
            </div>
        </form>
    </div>

    <script>
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                btn.textContent = '🔒';
            } else {
                input.type = 'password';
                btn.textContent = '👁️';
            }
        }

        document.getElementById('loginForm').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());

            try {
                const res = await fetch('api.php?action=login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await res.json();

                if (result.success) {
                    window.location.href = 'index.php';
                } else {
                    const errorDiv = document.getElementById('error-msg');
                    errorDiv.textContent = result.error || 'Login failed';
                    errorDiv.style.display = 'block';
                }
            } catch (err) {
                console.error(err);
                const errorDiv = document.getElementById('error-msg');
                errorDiv.textContent = 'System Error: Please check console or reset database.';
                errorDiv.style.display = 'block';
            }
        };
    </script>
</body>

</html>