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
    <title>PC Assembly Stock System</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>

<body>
    <div class="container">
        <header>
            <h1>PC Component Stock</h1>
            <div class="header-actions" style="display: flex; gap: 1rem; align-items: center;">
                <a href="profile.php"
                    style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 0.5rem;">
                    <div style="width: 32px; height: 32px; background: #334155; border-radius: 50%; overflow: hidden;">
                        <?php if (!empty($_SESSION['profile_image'])): ?>
                            <img src="<?php echo htmlspecialchars($_SESSION['profile_image']); ?>"
                                style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <span
                                style="display: flex; justify-content: center; align-items: center; height: 100%;">👤</span>
                        <?php endif; ?>
                    </div>
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </a>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <button class="btn btn-primary" onclick="openModal('addModal')">Add Product</button>
                <?php else: ?>
                    <button class="header-cart-btn" onclick="openModal('cartModal')">
                        🛒 Cart <span class="header-cart-count" id="cart-count">0</span>
                    </button>
                <?php endif; ?>
                <button class="btn" onclick="logout()">Logout</button>
            </div>
        </header>

        <?php if ($_SESSION['role'] === 'admin'): ?>
            <!-- ADMIN DASHBOARD -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Components</div>
                    <div class="stat-value" id="stat-total">0</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Low Stock Items</div>
                    <div class="stat-value" style="color: var(--warning)" id="stat-low">0</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Units in Stock</div>
                    <div class="stat-value" id="stat-units">0</div>
                </div>
            </div>

            <div class="content-card" style="margin-bottom: 2rem;">
                <div class="card-header">
                    <h2>Recent Activities</h2>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Product</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody id="log-list">
                        <!-- Logs will be loaded here -->
                    </tbody>
                </table>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h2>Inventory List</h2>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Product Info</th>
                            <th>Category</th>
                            <th>Stock Level</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="product-list">
                        <!-- Products will be loaded here -->
                    </tbody>
                </table>
            </div>

        <?php else: ?>
            <!-- CUSTOMER STOREFRONT -->
            <div class="hero-banner">
                <h2>Build Your Dream PC</h2>
                <p>Select premium components for your ultimate setup.</p>
            </div>

            <!-- Store Controls -->
            <div class="store-controls">
                <div class="search-bar-container">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="search-input" class="search-input"
                        placeholder="Search for components (e.g. 'RTX', 'Intel')..." oninput="filterProducts()">
                </div>
                <div class="filter-tabs" id="category-filters">
                    <button class="filter-tab active" onclick="setFilter('all')">All</button>
                    <!-- Categories injected here -->
                </div>
            </div>

            <div class="store-grid" id="store-list">
                <!-- Products loaded here -->
            </div>

            <!-- Cart Modal -->
            <div id="cartModal" class="modal">
                <div class="modal-content">
                    <h2>Your Build</h2>
                    <div id="cart-items" style="max-height: 400px; overflow-y: auto; margin-bottom: 1rem;">
                        <!-- Cart items loaded here -->
                        <p style="color: var(--text-muted); text-align: center;">Your cart is empty.</p>
                    </div>
                    <div class="cart-total">
                        <span>Total</span>
                        <span id="cart-total">$0.00</span>
                    </div>
                    <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                        <button type="button" class="btn btn-primary" style="flex: 1;"
                            onclick="checkout()">Checkout</button>
                        <button type="button" class="btn" onclick="closeModal('cartModal')"
                            style="background: var(--glass);">Close</button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <!-- Add Product Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <h2>Add New Component</h2>
            <form id="addForm">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id" id="cat-select" required></select>
                </div>
                <div class="form-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label>Brand</label>
                        <input type="text" name="brand">
                    </div>
                    <div>
                        <label>Model</label>
                        <input type="text" name="model">
                    </div>
                </div>
                <div class="form-group">
                    <label>Product Image</label>
                    <input type="file" id="prod-file-input" accept="image/*">
                    <input type="hidden" name="image_url">
                </div>
                <div class="form-group" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                    <div>
                        <label>Price</label>
                        <input type="number" name="price" step="0.01" value="0.00">
                    </div>
                    <div>
                        <label>Initial Stock</label>
                        <input type="number" name="stock_quantity" value="0">
                    </div>
                    <div>
                        <label>Min Level</label>
                        <input type="number" name="min_stock_level" value="5">
                    </div>
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Save Product</button>
                    <button type="button" class="btn" onclick="closeModal('addModal')"
                        style="background: var(--glass);">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Update Stock Modal -->
    <div id="stockModal" class="modal">
        <div class="modal-content">
            <h2 id="stock-modal-title">Adjust Stock</h2>
            <p id="stock-modal-subtitle" style="color: var(--text-muted); margin-bottom: 1.5rem;"></p>
            <form id="stockForm">
                <input type="hidden" name="product_id">
                <div class="form-group">
                    <label>Type</label>
                    <select name="type">
                        <option value="IN">Stock In (+)</option>
                        <option value="OUT">Stock Out (-)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Quantity</label>
                    <input type="number" name="quantity" min="1" value="1" required>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <input type="text" name="notes" placeholder="Reason for adjustment">
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Confirm</button>
                    <button type="button" class="btn" onclick="closeModal('stockModal')"
                        style="background: var(--glass);">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const USER_ROLE = '<?php echo $_SESSION['role']; ?>';
        let cart = [];

        // ... existing cart functions ...

        document.getElementById('addForm').onsubmit = async (e) => {
            e.preventDefault();
            const btn = e.target.querySelector('button[type="submit"]');
            const originalText = btn.innerText;
            btn.innerText = "Uploading...";
            btn.disabled = true;

            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());

            // Handle Image Upload
            const fileInput = document.getElementById('prod-file-input');
            if (fileInput.files.length > 0) {
                const uploadData = new FormData();
                uploadData.append('file', fileInput.files[0]);
                try {
                    const res = await fetch('api.php?action=upload_image', { method: 'POST', body: uploadData });
                    const result = await res.json();
                    if (result.success) {
                        data.image_url = result.url;
                    }
                } catch (err) {
                    console.error('Upload failed', err);
                }
            }

            btn.innerText = "Saving...";

            await fetchAPI('add_product', {
                method: 'POST',
                body: JSON.stringify(data)
            });

            btn.innerText = originalText;
            btn.disabled = false;
            closeModal('addModal');
            loadData();
        };

        document.getElementById('stockForm').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            await fetchAPI('update_stock', {
                method: 'POST',
                body: JSON.stringify(data)
            });
            closeModal('stockModal');
            loadData();
        };

        loadData();
    </script>
</body>

</html>