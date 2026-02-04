<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - PC Assembly Stock</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="login-body">
    <div class="login-card">
        <div class="login-header">
            <h1>Create Account</h1>
            <p>Join us to build your dream PC.</p>
        </div>
        <form id="registerForm">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required placeholder="Choose a username">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="Min 6 characters">
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required placeholder="Repeat password">
            </div>
            <div id="error-msg" class="error-message"></div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Register</button>
            <div style="margin-top: 1.5rem; text-align: center; color: var(--text-muted); font-size: 0.875rem;">
                Already have an account? <a href="login.php" style="color: var(--primary); text-decoration: none;">Sign
                    In</a>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('registerForm').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());

            if (data.password !== data.confirm_password) {
                const errorDiv = document.getElementById('error-msg');
                errorDiv.textContent = 'Passwords do not match';
                errorDiv.style.display = 'block';
                return;
            }

            try {
                const res = await fetch('api.php?action=register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await res.json();

                if (result.success) {
                    alert('Registration successful! Please login.');
                    window.location.href = 'login.php';
                } else {
                    const errorDiv = document.getElementById('error-msg');
                    errorDiv.textContent = result.error || 'Registration failed';
                    errorDiv.style.display = 'block';
                }
            } catch (err) {
                console.error(err);
            }
        };
    </script>
</body>

</html>