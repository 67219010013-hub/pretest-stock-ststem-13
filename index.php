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
                    <button class="header-cart-btn" id="cartBtn" onclick="toggleCart()">
                        🛒 Cart <span class="header-cart-count" id="cart-count">0</span>
                    </button>
                <?php endif; ?>
                <?php if ($_SESSION['role'] === 'customer'): ?>
                    <a href="ai_builder.php" class="btn"
                        style="background: rgba(139, 92, 246, 0.1); border-color: rgba(139, 92, 246, 0.3); color: #a78bfa; text-decoration: none; font-size: 0.75rem;">
                        ✨ AI Builder
                    </a>
                <?php endif; ?>
                <button class="btn" id="logoutBtn">Logout</button>
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
                <div style="position: relative; z-index: 1;">
                    <div
                        style="display: inline-flex; align-items: center; gap: 0.5rem; background: rgba(139, 92, 246, 0.2); border: 1px solid rgba(139, 92, 246, 0.3); padding: 0.5rem 1rem; border-radius: 999px; font-size: 0.75rem; font-weight: 800; color: #a78bfa; text-transform: uppercase; margin-bottom: 1.5rem; backdrop-filter: blur(4px);">
                        ✨ New: AI-Powered PC Architect
                    </div>
                    <h2>Build Your Dream PC</h2>
                    <p style="margin-bottom: 2rem;">Select premium components for your ultimate setup or let our AI
                        architect a build for you.</p>
                    <div style="display: flex; gap: 1rem; justify-content: center;">
                        <a href="ai_builder.php" class="btn btn-primary" style="padding: 1rem 2.5rem; font-size: 1rem;">
                            🚀 Use AI PC Builder
                        </a>
                        <button class="btn" style="padding: 1rem 2.5rem; font-size: 1rem;"
                            onclick="document.getElementById('search-input').focus()">
                            Browse Components
                        </button>
                    </div>
                </div>
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
                    <h2>Your PC Build</h2>
                    <div id="cart-items"
                        style="max-height: 450px; overflow-y: auto; margin-bottom: 1rem; padding-right: 0.5rem;">
                        <p style="color: var(--text-muted); text-align: center; padding: 3rem;">Your build is empty.</p>
                    </div>
                    <div class="cart-total-box">
                        <div>
                            <div class="cart-total-label">Subtotal Estimate</div>
                            <div class="cart-total-value" id="cart-total">$0.00</div>
                        </div>
                    </div>
                    <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                        <button type="button" class="btn btn-primary" style="flex: 2; height: 56px; font-size: 1rem;"
                            onclick="checkout(event)">Finalize Build</button>
                        <button type="button" class="btn" onclick="closeModal('cartModal')"
                            style="flex: 1; height: 56px; background: rgba(255,255,255,0.05);">Cancel</button>
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
                    <div id="add-img-preview"
                        style="width: 100%; height: 150px; background: var(--glass); border-radius: 1rem; margin-bottom: 1rem; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 2px dashed var(--border);">
                        <span style="color: var(--text-muted);">No image selected</span>
                    </div>
                    <input type="file" id="prod-file-input" accept="image/*"
                        onchange="previewImage(this, 'add-img-preview')">
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

    <!-- Edit Product Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h2>Edit Component</h2>
            <form id="editForm">
                <input type="hidden" name="id">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id" id="edit-cat-select" required></select>
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
                    <div id="edit-img-preview"
                        style="width: 100%; height: 150px; background: var(--glass); border-radius: 1rem; margin-bottom: 1rem; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 2px dashed var(--border);">
                        <span style="color: var(--text-muted);">No image</span>
                    </div>
                    <input type="file" id="edit-prod-file-input" accept="image/*"
                        onchange="previewImage(this, 'edit-img-preview')">
                    <input type="hidden" name="image_url">
                </div>
                <div class="form-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label>Price</label>
                        <input type="number" name="price" step="0.01">
                    </div>
                    <div>
                        <label>Min Level</label>
                        <input type="number" name="min_stock_level">
                    </div>
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Update Product</button>
                    <button type="button" class="btn" onclick="closeModal('editModal')"
                        style="background: var(--glass);">Cancel</button>
                </div>
            </form>
        </div>
    </div>
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
        let allProducts = [];
        let allCategories = [];
        let currentFilter = 'all';
        let cart = [];
        try {
            cart = JSON.parse(localStorage.getItem('cart') || '[]');
            if (!Array.isArray(cart)) cart = [];
        } catch (e) {
            cart = [];
        }

        async function fetchAPI(action, options = {}) {
            try {
                const res = await fetch(`api.php?action=${action}`, options);
                if (!res.ok) throw new Error(`API Error: ${res.status}`);
                return await res.json();
            } catch (err) {
                console.error(err);
                return null;
            }
        }

        async function loadData() {
            try {
                const products = await fetchAPI('get_products');
                const categories = await fetchAPI('get_categories');

                if (products) allProducts = products;
                if (categories) {
                    allCategories = categories;
                    renderCategories(categories);
                }

                if (USER_ROLE === 'admin') {
                    const stats = await fetchAPI('get_stats');
                    const logs = await fetchAPI('get_logs');
                    if (stats) renderStats(stats);
                    if (logs) renderLogs(logs);
                }

                filterProducts();
                updateCartUI();
            } catch (e) {
                console.error(e);
            }
        }

        function renderProducts(products) {
            if (USER_ROLE === 'admin') {
                renderAdminProducts(products);
            } else {
                renderCustomerProducts(products);
            }
        }

        function renderAdminProducts(products) {
            const list = document.getElementById('product-list');
            if (!list) return;

            list.innerHTML = products.map(p => {
                const isLow = p.stock_quantity <= p.min_stock_level;
                const status = p.stock_quantity <= 0 ?
                    '<span style="color:var(--danger)">Out of Stock</span>' :
                    (isLow ? '<span style="color:var(--warning)">Low Stock</span>' : '<span style="color:var(--success)">Available</span>');

                return `
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div style="width: 40px; height: 40px; border-radius: 8px; overflow: hidden; background: var(--glass);">
                                    ${p.image_url ? `<img src="${p.image_url}" style="width:100%; height:100%; object-fit:cover;">` : '<span style="display:flex; justify-content:center; align-items:center; height:100%; font-size:1.2rem;">📦</span>'}
                                </div>
                                <div>
                                    <div style="font-weight: 600;">${p.name}</div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);">${p.brand} ${p.model}</div>
                                </div>
                            </div>
                        </td>
                        <td>${p.category_name}</td>
                        <td>
                            <div style="font-weight: 700;">${p.stock_quantity} <span style="font-weight: 400; color: var(--text-muted);">/ ${p.min_stock_level}</span></div>
                        </td>
                        <td>${status}</td>
                        <td>
                            <div style="display: flex; gap: 0.5rem;">
                                <button class="btn btn-icon" onclick="openEditModal(${p.id})" title="Edit">✏️</button>
                                <button class="btn btn-icon" onclick="openStockModal(${p.id}, '${p.name.replace(/'/g, "\\'")}')" title="Adjust Stock">📦</button>
                                <button class="btn btn-icon" onclick="deleteProduct(${p.id})" title="Delete" style="color: var(--danger)">🗑️</button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function renderCustomerProducts(products) {
            const grid = document.getElementById('store-list');
            if (!grid) return;

            if (products.length === 0) {
                grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 4rem; color: var(--text-muted);">No components found matching your search.</div>';
                return;
            }

            grid.innerHTML = products.map(p => {
                const escapedName = p.name.replace(/'/g, "\\'").replace(/"/g, "&quot;");
                return `
                <div class="product-card">
                    <div class="product-image">
                        ${p.image_url ? `<img src="${p.image_url}" alt="${p.name}">` : '<div style="height:100%; display:flex; align-items:center; justify-content:center; background:rgba(255,255,255,0.05); color:var(--text-muted);">No Image</div>'}
                        ${p.stock_quantity <= 0 ? '<div class="stock-badge out">Out of Stock</div>' :
                        p.stock_quantity <= p.min_stock_level ? '<div class="stock-badge low">Low Stock</div>' : ''}
                    </div>
                    <div class="product-info">
                        <div class="product-category">${p.category_name}</div>
                        <h3 class="product-title">${p.name}</h3>
                        <div class="product-meta">${p.brand} | ${p.model}</div>
                        <div style="display: flex; flex-direction: column; gap: 1rem; margin-top: auto;">
                            <div class="product-price">$${parseFloat(p.price).toLocaleString(undefined, { minimumFractionDigits: 2 })}</div>
                            <button class="btn btn-primary" onclick="addToCart(event, ${p.id}, '${escapedName}', ${p.price})" ${p.stock_quantity <= 0 ? 'disabled' : ''} style="width: 100%; height: 50px; font-size: 0.9rem;">
                                🛒 Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            `}).join('');
        }

        function renderCategories(categories) {
            const selects = [document.getElementById('cat-select'), document.getElementById('edit-cat-select')];
            selects.forEach(select => {
                if (select) {
                    select.innerHTML = categories.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
                }
            });

            const scroll = document.getElementById('category-filters');
            if (scroll) {
                const activeClass = currentFilter === 'all' ? 'active' : '';
                scroll.innerHTML = `<button class="filter-tab ${activeClass}" onclick="setFilter(event, 'all')">All Components</button>` +
                    categories.map(c => {
                        const active = currentFilter == c.id ? 'active' : '';
                        return `<button class="filter-tab ${active}" onclick="setFilter(event, ${c.id})">${c.name}</button>`;
                    }).join('');
            }
        }

        function renderStats(stats) {
            const total = document.getElementById('stat-total');
            const low = document.getElementById('stat-low');
            const units = document.getElementById('stat-units');

            if (total) total.innerText = stats.total_products;
            if (low) low.innerText = stats.low_stock;
            if (units) units.innerText = stats.total_stock;
        }

        function renderLogs(logs) {
            const list = document.getElementById('log-list');
            if (!list) return;

            list.innerHTML = logs.map(l => `
                <tr>
                    <td style="font-size: 0.8rem; color: var(--text-muted);">${new Date(l.created_at).toLocaleString()}</td>
                    <td style="font-weight: 500;">${l.product_name || 'Deleted Product'}</td>
                    <td>
                        <span style="color: ${l.type === 'IN' ? 'var(--success)' : 'var(--danger)'}; font-weight: 700;">
                            ${l.type}
                        </span>
                    </td>
                    <td>${l.quantity}</td>
                    <td style="font-size: 0.85rem; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${l.notes}">
                        ${l.notes || '-'}
                    </td>
                </tr>
            `).join('');
        }

        function filterProducts() {
            const search = document.getElementById('search-input')?.value.toLowerCase() || '';
            const filtered = allProducts.filter(p => {
                const matchesSearch = p.name.toLowerCase().includes(search) ||
                    p.brand.toLowerCase().includes(search) ||
                    p.model.toLowerCase().includes(search);
                const matchesCategory = currentFilter === 'all' || p.category_id == currentFilter;
                return matchesSearch && matchesCategory;
            });
            renderProducts(filtered);
        }

        function setFilter(el, cid) {
            currentFilter = cid;
            const tabs = document.querySelectorAll('.filter-tab');
            tabs.forEach(t => t.classList.remove('active'));

            if (el && el.target) el.target.classList.add('active');
            else if (el) el.classList.add('active');

            filterProducts();
        }

        function addToCart(event, id, name, price) {
            console.log('Adding to cart:', id, name, price);
            price = parseFloat(price) || 0;
            const existing = cart.find(i => String(i.id) === String(id));
            if (existing) {
                existing.qty++;
            } else {
                cart.push({ id, name, price, qty: 1 });
            }
            saveAndSyncCart();
            showToast(`Added ${name} to cart`);

            // Animation for cart count
            const countEl = document.getElementById('cart-count');
            if (countEl) {
                countEl.style.transition = 'all 0.3s ease';
                countEl.style.transform = 'scale(1.5)';
                countEl.style.background = 'var(--secondary)';
                setTimeout(() => {
                    countEl.style.transform = 'scale(1)';
                    countEl.style.background = 'var(--primary)';
                }, 300);
            }
        }

        function updateCartUI() {
            const countEl = document.getElementById('cart-count');
            if (countEl) countEl.textContent = cart.reduce((sum, item) => sum + item.qty, 0);

            const cartItems = document.getElementById('cart-items');
            if (!cartItems) return;

            if (cart.length === 0) {
                cartItems.innerHTML = `
                    <div style="text-align:center; padding:4rem 2rem; color:var(--text-muted);">
                        <div style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;">🛒</div>
                        <p>Your build is currently empty</p>
                    </div>`;
                document.getElementById('cart-total').textContent = '$0.00';
                return;
            }

            let total = 0;
            cartItems.innerHTML = cart.map((item, index) => {
                const price = parseFloat(item.price) || 0;
                const itemTotal = price * item.qty;
                total += itemTotal;
                return `
                <div class="cart-item">
                    <div style="flex: 1;">
                        <div style="font-weight: 700; color: white; margin-bottom: 0.5rem; font-size: 1rem;">${item.name}</div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; color: var(--secondary);">
                            <span style="font-weight: 800;">$${price.toFixed(2)}</span>
                            <span style="color: var(--text-muted); font-size: 0.75rem;">each</span>
                        </div>
                    </div>
                    <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.75rem;">
                        <div style="font-weight: 900; color: white; font-size: 1.1rem;">$${itemTotal.toFixed(2)}</div>
                        <div style="display: flex; align-items: center; background: rgba(0,0,0,0.2); border-radius: 8px; padding: 2px;">
                            <button onclick="decrementCart(${index})" style="background:transparent; border:none; color:white; width:24px; cursor:pointer;">-</button>
                            <span style="padding: 0 8px; font-weight: 700; font-size: 0.9rem; min-width: 30px; text-align:center;">${item.qty}</span>
                            <button onclick="incrementCart(${index})" style="background:transparent; border:none; color:white; width:24px; cursor:pointer;">+</button>
                            <button onclick="removeFromCart(${index})" style="margin-left:8px; background:rgba(239, 68, 68, 0.1); border:none; color:var(--danger); width:24px; height:24px; border-radius:4px; cursor:pointer; font-size: 12px;">✕</button>
                        </div>
                    </div>
                </div>
                `;
            }).join('');

            document.getElementById('cart-total').textContent = '$' + total.toLocaleString(undefined, { minimumFractionDigits: 2 });
        }

        function incrementCart(index) {
            cart[index].qty++;
            saveAndSyncCart();
        }

        function decrementCart(index) {
            if (cart[index].qty > 1) {
                cart[index].qty--;
                saveAndSyncCart();
            } else {
                removeFromCart(index);
            }
        }

        function removeFromCart(index) {
            cart.splice(index, 1);
            saveAndSyncCart();
        }

        function saveAndSyncCart() {
            localStorage.setItem('cart', JSON.stringify(cart));
            updateCartUI();
        }

        function toggleCart() {
            const modal = document.getElementById('cartModal');
            if (modal) {
                const isFlex = modal.style.display === 'flex';
                modal.style.display = isFlex ? 'none' : 'flex';
            }
        }

        async function checkout(event) {
            if (cart.length === 0) return;

            const btn = event ? event.target : document.querySelector('button[onclick="checkout()"]');
            const originalText = btn.innerText;
            btn.innerText = "Processing...";
            btn.disabled = true;

            const res = await fetchAPI('checkout', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cart })
            });

            if (res && res.success) {
                alert('Success! Order #' + res.order_id + ' has been placed.');
                cart = [];
                localStorage.setItem('cart', JSON.stringify(cart));
                updateCartUI();
                toggleCart();
                loadData();
            } else {
                alert(res ? res.error : 'Checkout failed');
            }

            btn.innerText = originalText;
            btn.disabled = false;
        }

        async function deleteProduct(id) {
            if (!confirm('Are you sure you want to delete this product?')) return;
            const res = await fetchAPI('delete_product', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            if (res && res.success) loadData();
        }

        function showToast(msg) {
            // Simple temporary notification
            const toast = document.createElement('div');
            toast.style.cssText = `
                position: fixed; bottom: 2rem; right: 2rem;
                background: var(--primary); color: white;
                padding: 1rem 2rem; border-radius: 1rem;
                box-shadow: 0 10px 25px rgba(0,0,0,0.3);
                z-index: 9999; animation: fadeIn 0.3s ease-out;
            `;
            toast.innerText = msg;
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.5s';
                setTimeout(() => toast.remove(), 500);
            }, 3000);
        }

        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    preview.innerHTML = `<img src="${e.target.result}" style="width:100%; height:100%; object-fit:cover;">`;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        window.openEditModal = (id) => {
            const p = allProducts.find(x => x.id == id);
            if (!p) return;
            const f = document.getElementById('editForm');
            f.id.value = p.id;
            f.name.value = p.name;
            f.category_id.value = p.category_id;
            f.brand.value = p.brand;
            f.model.value = p.model;
            f.price.value = p.price;
            f.min_stock_level.value = p.min_stock_level;
            f.image_url.value = p.image_url;

            const preview = document.getElementById('edit-img-preview');
            if (p.image_url) {
                preview.innerHTML = `<img src="${p.image_url}" style="width:100%; height:100%; object-fit:cover;">`;
            } else {
                preview.innerHTML = `<span style="color: var(--text-muted);">No image</span>`;
            }

            openModal('editModal');
        };

        window.openModal = (id) => document.getElementById(id).style.display = 'flex';
        window.closeModal = (id) => document.getElementById(id).style.display = 'none';

        window.openStockModal = (id, name) => {
            document.getElementById('stock-modal-title').innerText = `Adjust Stock: ${name}`;
            document.querySelector('input[name="product_id"]').value = id;
            openModal('stockModal');
        };

        // Event Listeners
        document.getElementById('logoutBtn')?.addEventListener('click', async () => {
            await fetchAPI('logout');
            window.location.href = 'login.php';
        });

        document.getElementById('addForm').onsubmit = async (e) => {
            e.preventDefault();
            const btn = e.target.querySelector('button[type="submit"]');
            const originalText = btn.innerText;
            btn.innerText = "Saving...";
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
                    if (result.success) data.image_url = result.url;
                } catch (err) { console.error('Upload failed', err); }
            }

            const res = await fetchAPI('add_product', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            if (res && res.success) {
                closeModal('addModal');
                e.target.reset();
                document.getElementById('add-img-preview').innerHTML = '<span style="color: var(--text-muted);">No image selected</span>';
                loadData();
            }

            btn.innerText = originalText;
            btn.disabled = false;
        };

        document.getElementById('editForm').onsubmit = async (e) => {
            e.preventDefault();
            const btn = e.target.querySelector('button[type="submit"]');
            const originalText = btn.innerText;
            btn.innerText = "Updating...";
            btn.disabled = true;

            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());

            // Handle Image Upload
            const fileInput = document.getElementById('edit-prod-file-input');
            if (fileInput.files.length > 0) {
                const uploadData = new FormData();
                uploadData.append('file', fileInput.files[0]);
                try {
                    const res = await fetch('api.php?action=upload_image', { method: 'POST', body: uploadData });
                    const result = await res.json();
                    if (result.success) data.image_url = result.url;
                } catch (err) { console.error('Upload failed', err); }
            }

            const res = await fetchAPI('edit_product', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            if (res && res.success) {
                closeModal('editModal');
                loadData();
            }

            btn.innerText = originalText;
            btn.disabled = false;
        };

        document.getElementById('stockForm').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            const res = await fetchAPI('update_stock', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            if (res && res.success) {
                closeModal('stockModal');
                loadData();
            }
        };

        // Initialize
        loadData();
    </script>
</body>

</html>