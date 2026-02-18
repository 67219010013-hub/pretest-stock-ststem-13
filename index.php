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

        async function fetchAPI(action, options = {}) {
            try {
                const res = await fetch(`api.php?action=${action}`, options);
                if (!res.ok) throw new Error(`API Error: ${res.status}`);
                return await res.json();
            } catch (err) {
                console.error(err);
                alert('Operation failed. See console.');
                return null;
            }
        }

        async function loadData() {
            try {
                // Determine what to load based on role
                if (USER_ROLE === 'admin' || USER_ROLE === 'customer') {
                    const products = await fetchAPI('get_products');
                    const categories = await fetchAPI('get_categories');
                    if (products) renderProducts(products, categories);
                    if (categories) renderCategories(categories);
                }

                if (USER_ROLE === 'admin') {
                    const stats = await fetchAPI('get_stats');
                    if (stats) renderStats(stats);
                }

                updateCartUI();
            } catch (e) { console.error(e); }
        }

        function renderProducts(products, categories) {
            const grid = document.getElementById('product-grid');
            if (!grid) return;

            // Simple category map
            const catMap = {};
            if (categories) categories.forEach(c => catMap[c.id] = c.name);

            grid.innerHTML = products.map(p => `
                <div class="product-card">
                    <div class="product-image">
                        ${p.image_url ? `<img src="${p.image_url}" alt="${p.name}">` : '<div style="height:100%; display:flex; align-items:center; justify-content:center; background:#f1f5f9; color:#64748b;">No Image</div>'}
                        ${p.stock_quantity <= 0 ? '<div class="stock-badge out">Out of Stock</div>' :
                    p.stock_quantity <= p.min_stock_level ? '<div class="stock-badge low">Low Stock</div>' : ''}
                    </div>
                    <div class="product-info">
                        <div class="product-category">${catMap[p.category_id] || 'Component'}</div>
                        <h3 class="product-title">${p.name}</h3>
                        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: auto;">
                            <div class="product-price">$${parseFloat(p.price).toFixed(2)}</div>
                            ${USER_ROLE === 'admin' ?
                    `<button class="btn-icon" onclick="openStockModal(${p.id}, '${p.name}')" title="Adjust Stock">📦</button>` :
                    `<button class="btn-icon" onclick="addToCart(${p.id}, '${p.name}', ${p.price})" ${p.stock_quantity <= 0 ? 'disabled' : ''}>🛒</button>`
                }
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function renderCategories(categories) {
            const select = document.getElementById('cat-select');
            if (select) {
                select.innerHTML = categories.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
            }
        }

        function renderStats(stats) {
            // Implementation for dashboard stats if elements exist
        }

        function addToCart(id, name, price) {
            const existing = cart.find(i => i.id === id);
            if (existing) {
                // limit to 1 per click? or check stock?
                // For MVP, just add.
            } else {
                cart.push({ id, name, price, qty: 1 });
            }
            updateCartUI();
        }

        function updateCartUI() {
            const cartBtn = document.querySelector('.cart-btn span');
            if (cartBtn) cartBtn.textContent = `Cart (${cart.length})`;

            const cartItems = document.getElementById('cart-items');
            if (!cartItems) return;

            if (cart.length === 0) {
                cartItems.innerHTML = '<div style="text-align:center; padding:2rem; color:var(--text-muted);">Cart is empty</div>';
                document.getElementById('cart-total').textContent = '$0.00';
                return;
            }

            let total = 0;
            cartItems.innerHTML = cart.map((item, index) => {
                total += item.price;
                return `
                <div class="cart-item">
                    <div>
                        <div style="font-weight: 500;">${item.name}</div>
                        <div style="font-size: 0.875rem; color: var(--text-muted);">$${item.price.toFixed(2)}</div>
                    </div>
                    <button class="btn-icon" onclick="removeFromCart(${index})" style="color: #ef4444;">×</button>
                </div>
                `;
            }).join('');

            document.getElementById('cart-total').textContent = '$' + total.toFixed(2);
        }

        function removeFromCart(index) {
            cart.splice(index, 1);
            updateCartUI();
        }

        function toggleCart() {
            const modal = document.getElementById('cartModal');
            if (modal) modal.style.display = modal.style.display === 'flex' ? 'none' : 'flex';
        }

        // Expose to window for onclick
        window.addToCart = addToCart;
        window.removeFromCart = removeFromCart;
        window.toggleCart = toggleCart;
        window.openStockModal = (id, name) => {
            document.getElementById('stock-modal-title').innerText = `Adjust Stock: ${name}`;
            document.querySelector('input[name="product_id"]').value = id;
            openModal('stockModal');
        };
        window.closeModal = (id) => document.getElementById(id).style.display = 'none';
        window.openModal = (id) => document.getElementById(id).style.display = 'flex';

        // Logout
        document.getElementById('logoutBtn')?.addEventListener('click', async (e) => {
            e.preventDefault();
            await fetchAPI('logout');
            window.location.href = 'login.php';
        });

        // Checkout
        document.getElementById('checkoutBtn')?.addEventListener('click', async () => {
            if (cart.length === 0) return;
            const res = await fetchAPI('checkout', {
                method: 'POST',
                body: JSON.stringify({ cart })
            });
            if (res && res.success) {
                alert('Order placed successfully! Order #' + res.order_id);
                cart = [];
                updateCartUI();
                toggleCart();
                loadData();
            } else {
                alert(res ? res.error : 'Checkout failed');
            }
        });


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