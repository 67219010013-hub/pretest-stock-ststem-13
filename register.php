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
    <div class="login-card" style="max-width: 500px; margin: 2rem auto;">
        <div class="login-logo">
            ✨
        </div>
        <div class="login-header">
            <h1>Create Account</h1>
            <p>Join our community of PC enthusiasts</p>
        </div>
        <form id="registerForm">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required placeholder="User123">
                </div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" required placeholder="John Doe">
                </div>
            </div>

            <div class="form-group">
                <label>Profile Image</label>
                <div
                    style="display: flex; gap: 1rem; align-items: center; background: rgba(15, 23, 42, 0.4); padding: 0.75rem; border-radius: 1rem; border: 1px dashed var(--border);">
                    <input type="file" id="file-input" accept="image/*"
                        style="border: none; background: transparent; padding: 0; font-size: 0.8rem;">
                    <input type="hidden" name="profile_image">
                </div>
            </div>

            <div class="form-group">
                <label>Address</label>
                <input type="text" name="address" placeholder="123 Alpha St, Digital City">
            </div>

            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" name="phone" placeholder="+66 81 234 5678">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Password</label>
                    <div class="password-input-wrapper">
                        <input type="password" name="password" id="password" required placeholder="••••••••"
                            style="padding-right: 3rem;">
                        <button type="button" class="password-toggle" onclick="togglePassword('password', this)"
                            style="right: 0.5rem; font-size: 1rem;">👁️</button>
                    </div>
                </div>
                <div class="form-group">
                    <label>Confirm</label>
                    <div class="password-input-wrapper">
                        <input type="password" name="confirm_password" id="confirm_password" required
                            placeholder="••••••••" style="padding-right: 3rem;">
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', this)"
                            style="right: 0.5rem; font-size: 1rem;">👁️</button>
                    </div>
                </div>
            </div>

            <div id="error-msg" class="error-message"></div>
            <button type="submit" class="btn btn-primary"
                style="width: 100%; padding: 1rem; font-size: 1rem; margin-top: 1rem;">Create My Account</button>
            <div
                style="margin-top: 2rem; text-align: center; color: var(--text-muted); font-size: 0.9rem; border-top: 1px solid var(--border); padding-top: 1.5rem;">
                Already have an account? <a href="login.php"
                    style="color: var(--primary); text-decoration: none; font-weight: 700;">Sign In</a>
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

        document.getElementById('registerForm').onsubmit = async (e) => {
            e.preventDefault();
            const btn = e.target.querySelector('button');
            const originalText = btn.innerText;
            btn.innerText = "Processing...";
            btn.disabled = true;

            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());

            if (data.password !== data.confirm_password) {
                showError('Passwords do not match');
                resetBtn(btn, originalText);
                return;
            }

            try {
                // 1. Upload Image if exists
                const fileInput = document.getElementById('file-input');
                if (fileInput.files.length > 0) {
                    const uploadData = new FormData();
                    uploadData.append('file', fileInput.files[0]);

                    // We need auth to upload, but registration is public?
                    // Correction: api.php upload_image requires Auth.
                    // This is a Catch-22. New users can't upload images if it requires Auth.
                    // I should modify api.php to allow upload without auth OR (safer) just allow it for this specific flow.
                    // Or, simply, I will allow upload_image to be public for now or assume I need to handle it differently.
                    // Let's modify api.php to allow public upload or check if session exists.
                    // Actually, for simplicity, let's remove requireAuth() from upload_image or check if it's for registration.
                    // But waiting, let's try to upload. If it fails (401), we skip image.

                    const res = await fetch('api.php?action=upload_image', {
                        method: 'POST',
                        body: uploadData
                    });
                    const result = await res.json();
                    if (result.success) {
                        data.profile_image = result.url;
                    } else {
                        console.warn('Image upload failed, skipping:', result.error);
                        // If it failed because of Auth, well... 
                        // I will fix api.php in next step to allow public upload for now or assume logged in. 
                        // Actually, I can't be logged in if I'm registering.
                        // I will fix api.php in a moment.
                    }
                }

                // 2. Register
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
                    showError(result.error || 'Registration failed');
                }
            } catch (err) {
                console.error(err);
                showError('Network error');
            } finally {
                resetBtn(btn, originalText);
            }
        };

        function showError(msg) {
            const errorDiv = document.getElementById('error-msg');
            errorDiv.textContent = msg;
            errorDiv.style.display = 'block';
        }

        function resetBtn(btn, text) {
            btn.innerText = text;
            btn.disabled = false;
        }
    </script>
</body>

</html>