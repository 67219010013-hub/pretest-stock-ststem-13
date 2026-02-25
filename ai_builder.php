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
    <title>AI PC Builder | PC Component Stock</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        .ai-container {
            max-width: 1000px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 2rem;
            animation: fadeIn 0.8s ease-out;
        }

        .ai-chat-card {
            background: var(--card-bg);
            backdrop-filter: blur(24px);
            border: 1px solid var(--border);
            border-radius: 2rem;
            padding: 3rem;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }

        .ai-chat-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at center, rgba(139, 92, 246, 0.05) 0%, transparent 50%);
            animation: rotate 20s linear infinite;
            z-index: -1;
        }

        @keyframes rotate {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .ai-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .ai-badge {
            display: inline-block;
            padding: 0.5rem 1.5rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 1rem;
            box-shadow: 0 0 20px rgba(139, 92, 246, 0.4);
        }

        .ai-input-group {
            display: grid;
            grid-template-columns: 2fr 1fr auto;
            gap: 1.5rem;
            background: rgba(15, 23, 42, 0.6);
            padding: 1rem;
            border-radius: 1.5rem;
            border: 1px solid var(--border);
        }

        .ai-input {
            background: transparent;
            border: none;
            padding: 1rem;
            color: white;
            font-size: 1.1rem;
            width: 100%;
        }

        .ai-input:focus {
            outline: none;
        }

        .ai-select {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border);
            border-radius: 1rem;
            color: white;
            padding: 0 1rem;
        }

        .recommendation-result {
            margin-top: 3rem;
            display: none;
        }

        .ai-message {
            background: rgba(255, 255, 255, 0.03);
            border-left: 4px solid var(--primary);
            padding: 2rem;
            border-radius: 0 1.5rem 1.5rem 0;
            margin-bottom: 2.5rem;
            font-size: 1.1rem;
            line-height: 1.8;
            color: #d1d5db;
        }

        .parts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .part-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border);
            border-radius: 1.5rem;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            transition: all 0.3s ease;
        }

        .part-card:hover {
            background: rgba(255, 255, 255, 0.05);
            transform: translateY(-5px);
            border-color: var(--primary);
        }

        .part-icon {
            width: 60px;
            height: 60px;
            background: rgba(139, 92, 246, 0.1);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .part-info h4 {
            margin-bottom: 0.25rem;
            font-size: 1.1rem;
        }

        .part-info p {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .part-price {
            margin-top: 0.5rem;
            font-weight: 700;
            color: var(--secondary);
        }

        .total-summary {
            margin-top: 3rem;
            padding: 2rem;
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(6, 182, 212, 0.1) 100%);
            border-radius: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid var(--border);
        }

        .loading-spinner {
            display: none;
            text-align: center;
            padding: 3rem;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid var(--glass);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1.5rem;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 768px) {
            .ai-input-group {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <header>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <a href="index.php" style="text-decoration: none; font-size: 1.5rem;">←</a>
                <h1>AI System Architect</h1>
            </div>
            <div class="header-actions">
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
                    <span>
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </span>
                </a>
            </div>
        </header>

        <div class="ai-container">
            <div class="ai-chat-card">
                <div class="ai-header">
                    <span class="ai-badge">Advanced Intelligence</span>
                    <h2 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 1rem;">How can I help you build
                        today?</h2>
                    <p style="color: var(--text-muted); font-size: 1.1rem;">Enter your budget and purpose, and I'll
                        architect the perfect machine for you.</p>
                </div>

                <form id="ai-form">
                    <div class="ai-input-group">
                        <input type="number" id="budget" class="ai-input" placeholder="Enter your budget (e.g. 2500)"
                            required min="500">
                        <select id="usage" class="ai-select">
                            <option value="gaming">Ultimate Gaming</option>
                            <option value="workstation">Creative Workstation</option>
                            <option value="office">Efficient Office</option>
                        </select>
                        <button type="submit" class="btn btn-primary" style="padding: 0 2rem;">Architect Build</button>
                    </div>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 1rem; text-align: center;">I will
                        analyze available inventory to find the best performance-per-dollar components.</p>
                </form>

                <div id="loading" class="loading-spinner">
                    <div class="spinner"></div>
                    <p>Analyzing component compatibility and performance benchmarks...</p>
                </div>

                <div id="result" class="recommendation-result">
                    <div class="ai-message" id="ai-explanation">
                        <!-- AI explanation here -->
                    </div>

                    <div class="parts-grid" id="parts-list">
                        <!-- Parts cards here -->
                    </div>

                    <div class="total-summary">
                        <div>
                            <div
                                style="font-size: 0.9rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em;">
                                Total Estimate</div>
                            <div style="font-size: 2rem; font-weight: 900;" id="total-price">$0.00</div>
                        </div>
                        <button class="btn btn-primary" id="add-all-btn" style="padding: 1.25rem 2.5rem;">Add All to
                            Cart</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentBuild = [];

        document.getElementById('ai-form').onsubmit = async (e) => {
            e.preventDefault();
            const budget = document.getElementById('budget').value;
            const usage = document.getElementById('usage').value;

            // UI States
            document.getElementById('result').style.display = 'none';
            document.getElementById('loading').style.display = 'block';

            try {
                const res = await fetch('api.php?action=get_ai_recommendation', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ budget, usage })
                });
                const data = await res.json();

                if (data.success) {
                    currentBuild = data.recommendation;
                    renderBuild(data);
                } else {
                    alert(data.error || 'Failed to generate recommendation');
                }
            } catch (err) {
                console.error(err);
                alert('An error occurred');
            } finally {
                document.getElementById('loading').style.display = 'none';
            }
        };

        function renderBuild(data) {
            const result = document.getElementById('result');
            const explanation = document.getElementById('ai-explanation');
            const list = document.getElementById('parts-list');
            const total = document.getElementById('total-price');

            explanation.innerHTML = `<p>${data.explanation}</p>`;

            const icons = {
                'CPU': '💻',
                'GPU': '🎮',
                'RAM': '⚡',
                'Motherboard': '🔌',
                'Storage': '💾',
                'PSU': '🔋',
                'Case': '📦',
                'Cooling': '❄️'
            };

            list.innerHTML = data.recommendation.map((p, idx) => `
                <div class="part-card">
                    <div class="part-icon">${icons[p.category_name] || '📦'}</div>
                    <div class="part-info" style="flex: 1;">
                        <div style="font-size: 0.7rem; color: var(--secondary); font-weight: 800; text-transform: uppercase;">${p.category_name}</div>
                        <h4>${p.name}</h4>
                        <p>${p.brand} ${p.model}</p>
                        <div class="part-price">$${parseFloat(p.price).toFixed(2)}</div>
                    </div>
                    <button class="btn btn-icon" onclick="addToCart(event, ${p.id}, '${p.name.replace(/'/g, "\\'")}', ${p.price})" style="background: rgba(139, 92, 246, 0.1); border-color: rgba(139, 92, 246, 0.2); width: 36px; height: 36px; border-radius: 10px;">
                        🛒
                    </button>
                </div>
            `).join('');

            total.innerText = '$' + parseFloat(data.total_price).toLocaleString(undefined, { minimumFractionDigits: 2 });
            result.style.display = 'block';
            result.scrollIntoView({ behavior: 'smooth' });
        }

        function addToCart(event, id, name, price) {
            price = parseFloat(price) || 0;
            let cart = [];
            try {
                const stored = localStorage.getItem('cart');
                cart = stored ? JSON.parse(stored) : [];
                if (!Array.isArray(cart)) cart = [];
            } catch (e) { cart = []; }
            const existing = cart.find(i => i.id === id);
            if (existing) {
                existing.qty++;
            } else {
                cart.push({ id, name, price: parseFloat(price), qty: 1 });
            }
            localStorage.setItem('cart', JSON.stringify(cart));

            // Show a simple toast or alert
            const btn = event.currentTarget;
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '✅';
            btn.style.borderColor = 'var(--success)';
            setTimeout(() => {
                btn.innerHTML = originalHTML;
                btn.style.borderColor = 'rgba(139, 92, 246, 0.2)';
            }, 2000);
        }

        document.getElementById('add-all-btn').onclick = () => {
            if (currentBuild.length === 0) return;

            // Get existing cart from localStorage or just use it from index.php
            // Since this is a separate page, we should use localStorage to sync
            let cart = JSON.parse(localStorage.getItem('cart') || '[]');

            currentBuild.forEach(p => {
                const existing = cart.find(i => i.id === p.id);
                if (existing) {
                    existing.qty++;
                } else {
                    cart.push({ id: p.id, name: p.name, price: parseFloat(p.price), qty: 1 });
                }
            });

            localStorage.setItem('cart', JSON.stringify(cart));

            // Show success and redirect or stay
            alert('All components have been added to your build! Redirecting to storefront to finalize.');
            window.location.href = 'index.php';
        };
    </script>
</body>

</html>