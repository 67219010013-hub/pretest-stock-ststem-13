<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PC Assembly Stock System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>PC Component Stock</h1>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="openModal('addModal')">Add Product</button>
            </div>
        </header>

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
                    <button type="button" class="btn" onclick="closeModal('addModal')" style="background: var(--glass);">Cancel</button>
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
                    <button type="button" class="btn" onclick="closeModal('stockModal')" style="background: var(--glass);">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        async function fetchAPI(action, options = {}) {
            const resp = await fetch(`api.php?action=${action}`, options);
            return resp.json();
        }

        async function loadData() {
            const [products, categories, stats] = await Promise.all([
                fetchAPI('get_products'),
                fetchAPI('get_categories'),
                fetchAPI('get_stats')
            ]);

            // Stats
            document.getElementById('stat-total').textContent = stats.total_products;
            document.getElementById('stat-low').textContent = stats.low_stock;
             document.getElementById('stat-units').textContent = stats.total_stock;

            // Categories
            const catSelect = document.getElementById('cat-select');
            catSelect.innerHTML = categories.map(c => `<option value="${c.id}">${c.name}</option>`).join('');

            // Products
            const list = document.getElementById('product-list');
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
                            <button class="btn" style="background: var(--glass); font-size: 0.75rem;" onclick="openStockModal(${p.id}, '${p.name}')">Adjust Stock</button>
                        </td>
                    </tr>
                `;
            }).join('');
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