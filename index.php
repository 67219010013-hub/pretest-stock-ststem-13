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
                <span style="color: var(--text-muted);">Welcome,
                    <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                    (<?php echo ucfirst($_SESSION['role']); ?>)</span>
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
                    <label>Image URL</label>
                    <input type="url" name="image_url" placeholder="https://example.com/image.jpg">
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

        function addToCart(product) {
            cart.push(product);
            updateCartUI();

            // Visual feedback
            const btn = event.target;
            const originalText = btn.innerText;
            btn.innerText = "Added!";
            btn.style.background = "var(--success)";
            setTimeout(() => {
                btn.innerText = originalText;
                btn.style.background = "";
            }, 1000);
        }

        function removeFromCart(index) {
            cart.splice(index, 1);
            updateCartUI();
        }

        function updateCartUI() {
            // Update badge
            document.getElementById('cart-count').innerText = cart.length;

            // Update List
            const list = document.getElementById('cart-items');
            if (cart.length === 0) {
                list.innerHTML = '<p style="color: var(--text-muted); text-align: center; padding: 2rem;">Your cart is empty.</p>';
                document.getElementById('cart-total').innerText = '$0.00';
                return;
            }

            let total = 0;
            list.innerHTML = cart.map((item, index) => {
                total += parseFloat(item.price);
                return `
                    <div class="cart-item">
                        <div style="width: 50px; height: 50px; background: #1e293b; border-radius: 0.25rem; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                             ${item.image_url ? `<img src="${item.image_url}" style="width: 100%; height: 100%; object-fit: cover;">` : '🖥️'}
                        </div>
                        <div class="cart-item-info">
                            <div style="font-weight: 600;">${item.name}</div>
                            <div style="font-size: 0.875rem; color: var(--text-muted);">$${parseFloat(item.price).toFixed(2)}</div>
                        </div>
                        <button onclick="removeFromCart(${index})" style="background: none; border: none; color: var(--danger); cursor: pointer; font-size: 1.25rem;">&times;</button>
                    </div>
                `;
            }).join('');

            document.getElementById('cart-total').innerText = '$' + total.toFixed(2);
        }

        async function checkout() {
            if (cart.length === 0) return;

            const btn = document.querySelector('#cartModal .btn-primary');
            const originalText = btn.innerText;
            btn.innerText = "Processing...";
            btn.disabled = true;

            try {
                const resp = await fetch('api.php?action=checkout', {
                    method: 'POST',
                    body: JSON.stringify({ cart: cart })
                });
                const result = await resp.json();

                if (result.success) {
                    alert('🎉 Order Placed Successfully! Order ID: #' + result.order_id);
                    cart = [];
                    updateCartUI();
                    closeModal('cartModal');
                    loadData(); // Refresh stock levels
                } else {
                    alert('Error: ' + (result.error || 'Checkout failed'));
                }
            } catch (e) {
                alert('Network error');
            } finally {
                btn.innerText = originalText;
                btn.disabled = false;
            }
        }

        async function fetchAPI(action, options = {}) {
            const resp = await fetch(`api.php?action=${action}`, options);
            return resp.json();
        }

        function renderStorefront(products) {
            const list = document.getElementById('store-list');
            if (!list) return;
            list.innerHTML = products.map(p => {
                const isOutOfStock = p.stock_quantity <= 0;
                const isLow = p.stock_quantity < 5;
                const stockLabel = isOutOfStock ? 'Sold Out' : (isLow ? `Only ${p.stock_quantity} Left` : `${p.stock_quantity} in Stock`);
                const badgeClass = isOutOfStock ? 'out' : (isLow ? 'low' : '');

                return `
                    <div class="product-card">
                        <div class="product-image">
                            <div class="stock-badge ${badgeClass}">
                                ${isOutOfStock ? '🔴' : (isLow ? '⚠️' : '🟢')} ${stockLabel}
                            </div>
                            ${p.image_url ?
                        `<img src="${p.image_url}" alt="${p.name}" style="width: 100%; height: 100%; object-fit: cover;">` :
                        `<!-- Placeholder for product image -->
                                <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--text-muted);">
                                    <span style="font-size: 3rem; margin-bottom: 0.5rem;">🖥️</span>
                                    <span style="font-size: 0.875rem; letter-spacing: 0.05em; text-transform: uppercase;">${p.category_name}</span>
                                </div>`
                    }
                        </div>
                        <div class="product-info">
                            <div class="product-category">${p.category_name}</div>
                            <h3 class="product-title">${p.name}</h3>
                            <div class="product-meta">${p.brand} ${p.model}</div>
                            <div class="product-price">$${parseFloat(p.price).toFixed(2)}</div>
                            <button class="btn btn-primary" style="width: 100%; margin-top: 1rem;" 
                                ${isOutOfStock ? 'disabled' : ''} 
                                onclick='addToCart(${JSON.stringify(p).replace(/'/g, "&#39;")})'>
                                ${isOutOfStock ? 'Out of Stock' : 'Add to Build'}
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
        }

        async function loadData() {
            if (USER_ROLE === 'admin') {
                const [products, categories, stats, logs] = await Promise.all([
                    fetchAPI('get_products'),
                    fetchAPI('get_categories'),
                    fetchAPI('get_stats'),
                    fetchAPI('get_logs')
                ]);

                // Stats logic (same as before)
                document.getElementById('stat-total').textContent = stats.total_products;
                document.getElementById('stat-low').textContent = stats.low_stock;
                document.getElementById('stat-units').textContent = stats.total_stock;

                // Inventory Table
                renderInventory(products);

                // Logs Table
                renderLogs(logs);

                // Categories for modal
                const catSelect = document.getElementById('cat-select');
                // Only verify if element exists (admin check implicitly covered but safe to check)
                if (catSelect) {
                    catSelect.innerHTML = categories.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
                }

            } else {
                // Customer View
                const [products, categories] = await Promise.all([
                    fetchAPI('get_products'),
                    fetchAPI('get_categories')
                ]);

                // Render Filters
                const filterContainer = document.getElementById('category-filters');
                if (filterContainer) {
                    const extraFilters = categories.map(c =>
                        `<button class="filter-tab" onclick="setFilter(${c.id}, this)">${c.name}</button>`
                    ).join('');
                    filterContainer.innerHTML += extraFilters;
                }

                // Global for filtering
                window.allProducts = products;
                window.activeCategory = 'all';

                filterProducts();
            }
        }

        function setFilter(categoryId, btn = null) {
            window.activeCategory = categoryId;

            // Update UI
            document.querySelectorAll('.filter-tab').forEach(b => b.classList.remove('active'));
            if (btn) {
                btn.classList.add('active');
            } else {
                document.querySelector('.filter-tab').classList.add('active'); // Default 'All'
            }

            filterProducts();
        }

        function filterProducts() {
            const searchText = document.getElementById('search-input').value.toLowerCase();
            const filtered = window.allProducts.filter(p => {
                const matchesSearch = p.name.toLowerCase().includes(searchText) ||
                    p.brand.toLowerCase().includes(searchText) ||
                    p.model.toLowerCase().includes(searchText);
                const matchesCategory = window.activeCategory === 'all' || p.category_id == window.activeCategory;

                return matchesSearch && matchesCategory;
            });

            renderStorefront(filtered);
        }

        function renderInventory(products) {
            const list = document.getElementById('product-list');
            if (!list) return;

            list.innerHTML = products.map(p => {
                const isLow = p.stock_quantity <= p.min_stock_level;
                const badgeClass = isLow ? 'badge-danger' : 'badge-success';
                const status = isLow ? 'Low Stock' : 'Optimal';

                return `
                    <tr>
                        <td>
                            <div style="font-weight: 600;">${p.name}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted)">${p.brand} ${p.model}</div>
                        </td>
                        <td>${p.category_name}</td>
                        <td>
                            <div style="font-size: 1.125rem; font-weight: 700;">${p.stock_quantity}</div>
                            <div style="font-size: 0.625rem; color: var(--text-muted)">Min: ${p.min_stock_level}</div>
                        </td>
                        <td><span class="badge ${badgeClass}">${status}</span></td>
                        <td>
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <button class="btn" style="background: rgba(16, 185, 129, 0.2); color: #6ee7b7; font-size: 0.75rem; padding: 0.25rem 0.5rem;" onclick="openStockModal(${p.id}, '${p.name}')">Stock</button>
                                <button class="btn" style="background: rgba(59, 130, 246, 0.2); color: #93c5fd; font-size: 0.75rem; padding: 0.25rem 0.5rem;" onclick='openEditModal(${JSON.stringify(p).replace(/'/g, "&#39;")})'>Edit</button>
                                <button class="btn" style="background: rgba(239, 68, 68, 0.2); color: #fca5a5; font-size: 0.75rem; padding: 0.25rem 0.5rem;" onclick="deleteProduct(${p.id})">Del</button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function renderLogs(logs) {
            const logList = document.getElementById('log-list');
            if (!logList) return;

            logList.innerHTML = logs.map(l => {
                const isOut = l.type === 'OUT';
                const badgeClass = isOut ? 'badge-warning' : 'badge-success';
                const typeLabel = isOut ? 'Stock Out' : 'Stock In';

                return `
                    <tr>
                        <td style="color: var(--text-muted); font-size: 0.875rem;">${new Date(l.created_at).toLocaleString()}</td>
                        <td style="font-weight: 500;">${l.product_name}</td>
                        <td><span class="badge ${badgeClass}">${typeLabel}</span></td>
                        <td style="font-weight: 700;">${l.quantity}</td>
                        <td style="color: var(--text-muted); font-style: italic;">${l.notes || '-'}</td>
                    </tr>
                `;
            }).join('');
        }

        async function logout() {
            await fetchAPI('logout');
            window.location.href = 'login.php';
        }

        function openModal(id) {
            document.getElementById(id).style.display = 'flex';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        function openStockModal(id, name) {
            const modal = document.getElementById('stockModal');
            modal.querySelector('input[name="product_id"]').value = id;
            document.getElementById('stock-modal-title').textContent = 'Adjust Stock';
            document.getElementById('stock-modal-subtitle').textContent = name;
            openModal('stockModal');
        }

        document.getElementById('addForm').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            await fetchAPI('add_product', {
                method: 'POST',
                body: JSON.stringify(data)
            });
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