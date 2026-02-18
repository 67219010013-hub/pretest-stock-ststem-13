<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - PC Stock</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>

<body>
    <div class="container">
        <header>
            <h1>My Profile</h1>
            <div class="header-actions">
                <a href="index.php" class="btn">Back to Store</a>
                <button class="btn" onclick="logout()">Logout</button>
            </div>
        </header>

        <div class="content-card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h2>Personal Information</h2>
            </div>
            <form id="profileForm" style="display: grid; grid-template-columns: 200px 1fr; gap: 2rem;">
                <div style="text-align: center;">
                    <div
                        style="width: 150px; height: 150px; background: #1e293b; border-radius: 50%; overflow: hidden; margin: 0 auto 1rem; border: 2px solid var(--primary);">
                        <img id="profile-img-preview" src="" alt="Profile"
                            style="width: 100%; height: 100%; object-fit: cover; display: none;">
                        <span id="profile-placeholder" style="font-size: 3rem; line-height: 150px;">👤</span>
                    </div>
                    <label class="btn btn-primary" style="cursor: pointer; display: inline-block;">
                        Change Photo
                        <input type="file" id="file-input" accept="image/*" style="display: none;"
                            onchange="previewImage(this)">
                    </label>
                    <input type="hidden" name="profile_image" id="profile_image_url">
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" id="username" disabled style="opacity: 0.7;">
                    </div>
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" id="full_name">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="tel" name="phone" id="phone">
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label>Address</label>
                        <input type="text" name="address" id="address">
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="content-card">
            <div class="card-header">
                <h2>Order History</h2>
                <div style="display: flex; gap: 0.5rem;">
                    <input type="text" id="filter-id" placeholder="Order ID..."
                        style="padding: 0.25rem; border-radius: 4px; border: 1px solid #334155; background: #0f172a; color: white;">
                    <input type="date" id="filter-date"
                        style="padding: 0.25rem; border-radius: 4px; border: 1px solid #334155; background: #0f172a; color: white;">
                    <button class="btn" onclick="filterOrders()">Filter</button>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Items</th>
                    </tr>
                </thead>
                <tbody id="order-list">
                    <!-- Orders Loaded Here -->
                </tbody>
            </table>
        </div>
    </div>

    <script>
        let allOrders = [];

        async function loadProfile() {
            try {
                const res = await fetch('api.php?action=get_profile');
                const data = await res.json();

                if (data.user) {
                    const u = data.user;
                    document.getElementById('username').value = u.username;
                    document.getElementById('full_name').value = u.full_name || '';
                    document.getElementById('phone').value = u.phone || '';
                    document.getElementById('address').value = u.address || '';

                    if (u.profile_image) {
                        document.getElementById('profile-img-preview').src = u.profile_image;
                        document.getElementById('profile-img-preview').style.display = 'block';
                        document.getElementById('profile-placeholder').style.display = 'none';
                        document.getElementById('profile_image_url').value = u.profile_image;
                    }
                }

                if (data.orders) {
                    allOrders = data.orders;
                    renderOrders(allOrders);
                }
            } catch (e) {
                console.error(e);
            }
        }

        function renderOrders(orders) {
            const list = document.getElementById('order-list');
            if (orders.length === 0) {
                list.innerHTML = '<tr><td colspan="5" style="text-align: center; color: var(--text-muted);">No orders found.</td></tr>';
                return;
            }

            list.innerHTML = orders.map(o => {
                const itemsHtml = o.items.map(i => `
                    <div style="display: flex; gap: 0.5rem; align-items: center; font-size: 0.875rem;">
                        <span style="color: var(--text-muted);">${i.quantity}x</span>
                        <span>${i.product_name}</span>
                    </div>
                `).join('');

                return `
                    <tr>
                        <td>#${o.id}</td>
                        <td>${new Date(o.created_at).toLocaleDateString()} ${new Date(o.created_at).toLocaleTimeString()}</td>
                        <td style="font-weight: bold;">$${parseFloat(o.total_price).toFixed(2)}</td>
                        <td><span class="badge ${o.status === 'completed' ? 'badge-success' : 'badge-warning'}">${o.status}</span></td>
                        <td>${itemsHtml}</td>
                    </tr>
                `;
            }).join('');
        }

        function filterOrders() {
            const idFilter = document.getElementById('filter-id').value.trim().toLowerCase();
            const dateFilter = document.getElementById('filter-date').value;

            const filtered = allOrders.filter(o => {
                const matchId = idFilter ? o.id.toString().includes(idFilter) : true;
                const matchDate = dateFilter ? o.created_at.startsWith(dateFilter) : true;
                return matchId && matchDate;
            });

            renderOrders(filtered);
        }

        async function previewImage(input) {
            if (input.files && input.files[0]) {
                const formData = new FormData();
                formData.append('file', input.files[0]);

                try {
                    const res = await fetch('api.php?action=upload_image', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await res.json();

                    if (result.success) {
                        document.getElementById('profile-img-preview').src = result.url;
                        document.getElementById('profile-img-preview').style.display = 'block';
                        document.getElementById('profile-placeholder').style.display = 'none';
                        document.getElementById('profile_image_url').value = result.url;
                    } else {
                        alert('Upload failed: ' + result.error);
                    }
                } catch (e) {
                    console.error(e);
                }
            }
        }

        document.getElementById('profileForm').onsubmit = async (e) => {
            e.preventDefault();
            const btn = e.target.querySelector('button[type="submit"]');
            btn.innerText = "Saving...";

            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());

            try {
                const res = await fetch('api.php?action=update_profile', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                if (result.success) {
                    alert('Profile updated!');
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (e) {
                console.error(e);
                alert('Network error');
            } finally {
                btn.innerText = "Save Changes";
            }
        };

        async function logout() {
            await fetch('api.php?action=logout');
            window.location.href = 'login.php';
        }

        loadProfile();
    </script>

    <style>
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        @media (max-width: 600px) {
            #profileForm {
                grid-template-columns: 1fr !important;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>

</html>