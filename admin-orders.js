// Admin Orders JS (externalized to prevent inline parsing issues)

// Check authentication first (resilient to storage errors)
function initializeAdmin() {
    let isAdmin = false;
    try {
        isAdmin = localStorage.getItem('isAdmin') === 'true';
    } catch (e) {
        console.warn('⚠️ localStorage unavailable, using in-memory flag', e);
        isAdmin = window.__isAdminFlag === true;
    }

    const adminContent = document.getElementById('adminContent');
    const unauthorizedContent = document.getElementById('unauthorizedContent');
    const status = (typeof localStorage !== 'undefined') ? localStorage.getItem('isAdmin') : 'localStorage unavailable';
    if (document.getElementById('debugStatus')) {
        document.getElementById('debugStatus').textContent = status || 'null';
    }
    console.log('🔍 Admin Check:', { isAdmin, status, adminContent: !!adminContent, unauthorizedContent: !!unauthorizedContent });

    if (isAdmin) {
        console.log('✅ Admin detected - showing dashboard');
        if (adminContent) adminContent.style.display = 'block';
        if (unauthorizedContent) unauthorizedContent.style.display = 'none';
        fetchAndRenderOrders();
        renderInventory();
        runPaymentReminders();
    } else {
        console.log('❌ Not admin - showing access denied');
        if (adminContent) adminContent.style.display = 'none';
        if (unauthorizedContent) unauthorizedContent.style.display = 'block';
    }
}

function logout() {
    try {
        localStorage.setItem('isAdmin', 'false');
    } catch {}
    window.location.href = 'shopping-cart.html';
}

function openLoginModal() {
    const modal = document.getElementById('loginModal');
    if (modal) {
        modal.style.display = 'flex';
        modal.style.position = 'fixed';
        modal.style.top = '0';
        modal.style.left = '0';
        modal.style.width = '100%';
        modal.style.height = '100%';
        modal.style.justifyContent = 'center';
        modal.style.alignItems = 'center';
        modal.style.zIndex = '2000';
        console.log('✅ Login modal opened');
    }
}

function closeLoginModal() {
    const modal = document.getElementById('loginModal');
    if (modal) modal.style.display = 'none';
    const err = document.getElementById('loginError');
    if (err) err.style.display = 'none';
    const form = document.getElementById('loginForm');
    if (form) form.reset();
}

function handleAdminLogin(event) {
    event.preventDefault();
    const username = document.getElementById('loginUsername').value;
    const password = document.getElementById('loginPassword').value;
    const errorDiv = document.getElementById('loginError');

    console.log('🔑 Login attempt:', { username });

    // Default credentials
    if (username === 'admin' && password === 'admin123') {
        console.log('✅ Login successful');
        try {
            localStorage.setItem('isAdmin', 'true');
            console.log('✅ localStorage set:', localStorage.getItem('isAdmin'));
        } catch (e) {
            console.warn('⚠️ localStorage unavailable, falling back to memory flag', e);
            window.__isAdminFlag = true;
        }
        closeLoginModal();
        initializeAdmin();
    } else {
        console.log('❌ Invalid credentials');
        if (errorDiv) errorDiv.style.display = 'block';
    }
}

// Close login modal when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    const loginModal = document.getElementById('loginModal');
    if (loginModal) {
        loginModal.addEventListener('click', function(event) {
            if (event.target === this) {
                closeLoginModal();
            }
        });
    }
});

// Load orders from API
let orders = [];
let currentFilter = null; // Track active filter

// Fetch orders from API
async function fetchAndRenderOrders() {
    try {
        console.log('Fetching orders from API...');
        const response = await fetch('api/admin/get-orders.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + (localStorage.getItem('authToken') || '')
            }
        });

        console.log('API response status:', response.status);

        if (response.status === 401) {
            console.log('Unauthorized - opening login modal');
            openLoginModal();
            return;
        }

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        console.log('API response data:', data);
        
        if (data.success) {
            orders = data.orders || [];
            console.log('Orders loaded:', orders.length, 'orders');
            renderOrders();
        } else {
            console.error('Failed to fetch orders:', data.message || data.error);
        }
    } catch (error) {
        console.error('Error fetching orders:', error);
    }
}

async function runPaymentReminders() {
    try {
        const throttleMinutes = 10;
        const lastRunKey = 'paymentReminderLastRun';
        const lastRun = parseInt(localStorage.getItem(lastRunKey), 10) || 0;
        const now = Date.now();
        if (now - lastRun < throttleMinutes * 60 * 1000) {
            return;
        }

        localStorage.setItem(lastRunKey, String(now));

        const response = await fetch('api/admin/send-payment-reminders.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + (localStorage.getItem('authToken') || '')
            },
            body: JSON.stringify({ source: 'admin-dashboard' })
        });

        if (!response.ok) {
            console.warn('Payment reminder check failed:', response.status);
            return;
        }

        const data = await response.json();
        if (data.success && data.sent > 0) {
            console.log(`Payment reminders sent: ${data.sent}`);
        }
    } catch (error) {
        console.warn('Payment reminder check error:', error);
    }
}

// Book inventory
const books = [
    { id: 1, name: 'Gratitude Journal', price: 24.99 },
    { id: 2, name: 'Fitness Journal', price: 19.99 },
    { id: 3, name: 'Prayer Journal', price: 22.99 }
];

// Save orders to database via API
async function updateOrderStatus(orderId, newStatus) {
    try {
        const response = await fetch('api/admin/update-order-status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + (localStorage.getItem('authToken') || '')
            },
            body: JSON.stringify({
                orderId: orderId,
                status: newStatus
            })
        });

        if (response.status === 401) {
            openLoginModal();
            return false;
        }

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        if (data.success) {
            await fetchAndRenderOrders();
            return true;
        } else {
            console.error('Failed to update order:', data.error);
            return false;
        }
    } catch (error) {
        console.error('Error updating order:', error);
        alert('Failed to update order: ' + error.message);
        return false;
    }
}

// Render orders list
function renderOrders() {
    const container = document.getElementById('ordersList');
    if (!container) return;

    if (orders.length === 0) {
        container.innerHTML = '<div class="empty-state">No orders yet</div>';
        updateStats();
        return;
    }

    // Sort by date (newest first)
    let sorted = [...orders].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
    
    // Apply filters
    if (currentFilter === 'pending_payments') {
        sorted = sorted.filter(order => {
            const paymentMethod = order.payment_method || 'pod';
            const paymentStatus = order.payment_status || 'pending';
            return (paymentMethod === 'bank_transfer' || paymentMethod === 'mobile_money') 
                   && paymentStatus !== 'completed';
        });
    } else if (currentFilter === 'pending_status') {
        sorted = sorted.filter(order => order.status === 'pending');
    }
    
    // Show message if filter returns no results
    if (sorted.length === 0) {
        container.innerHTML = '<div class="empty-state">No orders match this filter</div>';
        updateStats();
        return;
    }
    
    console.log('renderOrders - Total orders:', sorted.length);
    console.log('First order:', sorted[0]);
    console.log('First order items:', sorted[0]?.items);

    container.innerHTML = sorted.map((order) => {
        const dateObj = new Date(order.created_at);
        const formattedDate = dateObj.toLocaleDateString('en-US', { 
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });

        const statusClass = `status-${order.status}`;
        const itemClass = order.status === 'delivered' ? 'delivered' : '';

        const booksHtml = order.items && order.items.length > 0 
            ? order.items.map(item => `${item.product_name} × ${item.quantity}`).join(', ')
            : 'No items';

        // Convert total_amount to number (may be string from database)
        const totalAmount = parseFloat(order.total_amount) || 0;
        
        // Payment status indicator
        const paymentMethod = order.payment_method || 'pod';
        const paymentStatus = order.payment_status || 'pending';
        const needsPaymentVerification = (paymentMethod === 'bank_transfer' || paymentMethod === 'mobile_money') 
                                        && paymentStatus !== 'completed';

        return `
            <div class="order-item ${itemClass}" onclick="openOrderDetails('${order.order_number}', ${order.id})" style="cursor: pointer;">
                <div class="order-info">
                    <div class="order-header">
                        <span>Order #${order.order_number}</span>
                        <span class="order-status ${statusClass}">${order.status.toUpperCase()}</span>
                    </div>
                    <div class="order-details">
                        <strong>${order.customer_name}</strong><br>
                        📧 ${order.customer_email}<br>
                        📅 ${formattedDate}<br>
                        💳 Payment: ${paymentMethod.replace('_', ' ').toUpperCase()} 
                        ${needsPaymentVerification ? '<span style="color: #856404; font-weight: bold;">(⏳ Awaiting Verification)</span>' : 
                          paymentStatus === 'completed' ? '<span style="color: #0f5132;">✓</span>' : ''}
                    </div>
                    <div class="order-books">
                        📦 ${booksHtml}<br>
                        💰 Total: UGX ${totalAmount.toLocaleString('en-US', {maximumFractionDigits: 0})}
                    </div>
                    <div class="order-actions">
                        ${needsPaymentVerification ? `
                            <button class="btn" onclick="event.stopPropagation(); openPaymentVerification(${order.id}, '${order.order_number}', '${order.customer_name.replace(/'/g, "\\'")}', ${totalAmount}, '${paymentMethod}')" 
                                    style="background: #ffc107; color: #333; font-weight: bold;">💳 Verify Payment</button>
                        ` : ''}
                        ${order.status === 'pending' ? `
                            <button class="btn btn-delivered" onclick="event.stopPropagation(); markOrderDelivered(${order.id})">Mark Out For Delivery</button>
                        ` : ''}
                        ${order.status === 'out_for_delivery' ? `
                            <button class="btn btn-delivered" onclick="event.stopPropagation(); markOrderDelivered(${order.id})">Mark Delivered</button>
                        ` : ''}
                        <button class="btn btn-delete" onclick="event.stopPropagation(); deleteOrderById(${order.id})">Delete</button>
                    </div>
                </div>
            </div>
        `;
    }).join('');

    updateStats();
}

// Mark order as out for delivery
async function markOrderShipped(orderId) {
    if (confirm('Mark order as out for delivery?')) {
        const success = await updateOrderStatus(orderId, 'out_for_delivery');
        if (!success) {
            alert('Failed to update order status');
        }
    }
}

// Mark order as delivered
async function markOrderDelivered(orderId) {
    if (confirm('Mark order as delivered?')) {
        const success = await updateOrderStatus(orderId, 'delivered');
        if (!success) {
            alert('Failed to update order status');
        }
    }
}

// Delete order
async function deleteOrderById(orderId) {
    if (confirm('Delete this order? This cannot be undone.')) {
        try {
            const response = await fetch('/api/delete-order.php', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + (localStorage.getItem('authToken') || '')
                },
                body: JSON.stringify({ id: orderId })
            });

            if (response.status === 401) {
                openLoginModal();
                return;
            }

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            if (data.success) {
                await fetchAndRenderOrders();
            } else {
                alert('Error: ' + (data.error || 'Failed to delete order'));
            }
        } catch (error) {
            console.error('Error deleting order:', error);
            alert('Failed to delete order: ' + error.message);
        }
    }
}

// Calculate inventory sold
function getInventoryStats() {
    const stats = {};
    books.forEach(book => {
        stats[book.id] = {
            name: book.name,
            price: book.price,
            sold: 0
        };
    });

    orders.forEach(order => {
        order.items.forEach(item => {
            if (stats[item.id]) {
                stats[item.id].sold += item.quantity;
            }
        });
    });

    return stats;
}

// Render inventory
function renderInventory() {
    const container = document.getElementById('inventorySection');
    if (!container) return;
    
    // Load products from database
    loadProducts();
}

async function loadProducts() {
    try {
        const response = await fetch('api/get-products.php');
        const data = await response.json();
        
        if (data.success) {
            displayProducts(data.products);
        } else {
            document.getElementById('inventorySection').innerHTML = 
                '<div style="color:red; padding: 20px;">Failed to load products</div>';
        }
    } catch (error) {
        console.error('Error loading products:', error);
        document.getElementById('inventorySection').innerHTML = 
            '<div style="color:red; padding: 20px;">Error loading products</div>';
    }
}

function displayProducts(products) {
    const container = document.getElementById('inventorySection');
    if (!products || products.length === 0) {
        container.innerHTML = '<div style="padding: 20px;">No products found</div>';
        return;
    }

    let html = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <div style="font-weight: bold; color: #2c3e50;">Manage Product Stock:</div>
            <button onclick="showAddProductModal()" style="padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">
                + Add Product
            </button>
        </div>
    `;
    
    products.forEach(product => {
        const inStock = product.in_stock == 1;
        html += `
            <div class="inventory-item" style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: white; margin-bottom: 10px; border-radius: 5px;">
                <div style="flex: 1;">
                    <div style="font-weight: bold; color: #2c3e50; margin-bottom: 5px;">${product.name}</div>
                    <div style="font-size: 14px; color: #666;">UGX ${parseInt(product.price).toLocaleString()}</div>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="padding: 5px 12px; border-radius: 15px; font-size: 12px; font-weight: bold; ${inStock ? 'background: #d4edda; color: #155724;' : 'background: #f8d7da; color: #721c24;'}">
                        ${inStock ? '✓ In Stock' : '✗ Out of Stock'}
                    </span>
                    <button onclick="toggleProductStock(${product.id}, ${inStock})" 
                            style="padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer; font-size: 13px; font-weight: bold; color: white; ${inStock ? 'background: #dc3545;' : 'background: #28a745;'}">
                        ${inStock ? 'Mark Out' : 'Mark In'}
                    </button>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

async function toggleProductStock(productId, currentStatus) {
    const newStatus = currentStatus ? 0 : 1;
    
    if (!confirm(`Mark this product as ${newStatus ? 'IN STOCK' : 'OUT OF STOCK'}?`)) {
        return;
    }

    try {
        const response = await fetch('api/admin/update-product-stock.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                productId: productId, 
                inStock: newStatus 
            })
        });

        const result = await response.json();
        
        if (result.success) {
            // Reload products to update display immediately
            await loadProducts();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error updating stock:', error);
        alert('Failed to update. Please try again.');
    }
}

function showAddProductModal() {
    const modal = document.createElement('div');
    modal.id = 'addProductModal';
    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; z-index: 10000;';
    
    modal.innerHTML = `
        <div style="background: white; padding: 30px; border-radius: 10px; max-width: 500px; width: 90%;">
            <h2 style="margin-top: 0; color: #2c3e50;">Add New Product</h2>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #555;">Product Name *</label>
                <input type="text" id="productName" placeholder="e.g., Daily Planner" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #555;">Price (UGX) *</label>
                <input type="number" id="productPrice" placeholder="e.g., 85000" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #555;">Description</label>
                <textarea id="productDescription" placeholder="Brief product description..." rows="3" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;"></textarea>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #555;">Category *</label>
                <input type="text" id="productCategory" placeholder="e.g., Journals" value="Journals" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #555;">Image URL</label>
                <input type="text" id="productImageUrl" placeholder="e.g., image/product.jpg" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #555;">Emoji</label>
                <input type="text" id="productEmoji" placeholder="e.g., 📓" value="📔" maxlength="10" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;">
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button onclick="submitNewProduct()" style="flex: 1; padding: 12px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">
                    Add Product
                </button>
                <button onclick="closeAddProductModal()" style="flex: 1; padding: 12px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">
                    Cancel
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    document.getElementById('productName').focus();
}

function closeAddProductModal() {
    const modal = document.getElementById('addProductModal');
    if (modal) {
        modal.remove();
    }
}

async function submitNewProduct() {
    const name = document.getElementById('productName').value.trim();
    const price = parseInt(document.getElementById('productPrice').value);
    const description = document.getElementById('productDescription').value.trim();
    const category = document.getElementById('productCategory').value.trim();
    const imageUrl = document.getElementById('productImageUrl').value.trim();
    const emoji = document.getElementById('productEmoji').value.trim();
    
    // Validation
    if (!name) {
        alert('Please enter a product name');
        return;
    }
    
    if (!price || price <= 0) {
        alert('Please enter a valid price');
        return;
    }
    
    if (!category) {
        alert('Please enter a category');
        return;
    }
    
    try {
        const response = await fetch('api/admin/add-product.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                name: name,
                price: price,
                description: description,
                category: category,
                imageUrl: imageUrl,
                emoji: emoji || '📔'
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Product added successfully!');
            closeAddProductModal();
            loadProducts(); // Reload product list
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error adding product:', error);
        alert('Failed to add product. Please try again.');
    }
}

// Update statistics
function updateStats() {
    const total = orders.length;
    const pending = orders.filter(o => o.status === 'pending').length;
    const delivered = orders.filter(o => o.status === 'out_for_delivery' || o.status === 'delivered').length;
    const revenue = orders.reduce((sum, o) => sum + parseFloat(o.total_amount), 0);

    const totalOrdersEl = document.getElementById('totalOrders');
    const pendingOrdersEl = document.getElementById('pendingOrders');
    const shippedOrdersEl = document.getElementById('shippedOrders');
    const totalRevenueEl = document.getElementById('totalRevenue');
    if (totalOrdersEl) totalOrdersEl.textContent = total;
    if (pendingOrdersEl) pendingOrdersEl.textContent = pending;
    if (shippedOrdersEl) shippedOrdersEl.textContent = shipped;
    if (totalRevenueEl) totalRevenueEl.textContent = 'UGX ' + revenue.toLocaleString('en-US', {minimumFractionDigits: 0});
}

// Export orders to CSV
function exportOrders() {
    let csv = 'Order #,Date,Customer Name,Email,Status,Items,Total\n';
    
    orders.forEach((order) => {
        const items = order.items.map(i => `${i.product_name} (×${i.quantity})`).join('; ');
        csv += `${order.order_number},${order.created_at},"${order.customer_name}","${order.customer_email}",${order.status},"${items}",${parseFloat(order.total_amount).toFixed(2)}\n`;
    });

    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `orders-${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
}

// Export inventory to CSV
function exportInventory() {
    const stats = getInventoryStats();
    let csv = 'Book Name,Price,Units Sold,Total Revenue\n';
    
    books.forEach(book => {
        const stat = stats[book.id];
        const revenue = (stat.sold * book.price).toFixed(2);
        csv += `"${book.name}",${book.price},${stat.sold},${revenue}\n`;
    });

    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `inventory-${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
}

// View pending orders
function viewPendingOrders() {
    const pending = orders.filter(o => o.status === 'pending');
    
    if (pending.length === 0) {
        alert('No pending orders');
        return;
    }

    let list = 'PENDING ORDERS:\n\n';
    pending.forEach((order) => {
        const items = order.items.map(i => `${i.product_name} (×${i.quantity})`).join('\n  ');
        list += `Order #${order.order_number}\n`;
        list += `Customer: ${order.customer_name}\n`;
        list += `Items:\n  ${items}\n`;
        list += `Total: UGX ${parseFloat(order.total_amount).toLocaleString('en-US', {maximumFractionDigits: 0})}\n\n`;
    });

    alert(list);
}

// Print packing list
function printPackingList() {
    const pending = orders.filter(o => o.status === 'pending' || o.status === 'out_for_delivery');
    
    if (pending.length === 0) {
        alert('No orders to pack');
        return;
    }

    let html = '<html><head><title>Packing List</title><style>';
    html += 'body { font-family: Arial; padding: 20px; }';
    html += '.order { page-break-inside: avoid; border: 1px solid #ccc; padding: 20px; margin-bottom: 20px; }';
    html += '.order-title { font-weight: bold; font-size: 18px; margin-bottom: 10px; }';
    html += '.order-detail { margin: 5px 0; }';
    html += '.items { margin-top: 10px; padding-left: 20px; }';
    html += '</style></head><body>';
    html += '<h1>📦 PACKING LIST</h1>';
    html += `<p>Date: ${new Date().toLocaleDateString()}</p>`;

    pending.forEach((order) => {
        html += '<div class="order">';
        html += `<div class="order-title">Order #${order.order_number}</div>`;
        html += `<div class="order-detail"><strong>${order.customer_name}</strong></div>`;
        html += `<div class="order-detail">${order.customer_email}</div>`;
        html += '<div class="items">';
        order.items.forEach(item => {
            html += `<div>☐ ${item.product_name} × ${item.quantity}</div>`;
        });
        html += '</div>';
        html += `<div class="order-detail" style="margin-top: 10px;">Total: UGX ${parseFloat(order.total_amount).toLocaleString('en-US', {maximumFractionDigits: 0})}</div>`;
        html += '</div>';
    });

    html += '</body></html>';

    const printWindow = window.open('', '', 'width=800,height=600');
    printWindow.document.write(html);
    printWindow.document.close();
    printWindow.print();
}

// Close order details modal
function closeOrderDetails() {
    const modal = document.getElementById('orderDetailsModal');
    if (modal) modal.classList.remove('active');
}

// Open order details modal (use loaded orders data)
function openOrderDetails(orderNumber, orderId) {
    console.log('openOrderDetails called with orderNumber:', orderNumber, 'orderId:', orderId);
    const modal = document.getElementById('orderDetailsModal');
    const body = document.getElementById('orderDetailsBody');
    if (!modal || !body) return;

    const order = orders.find(o => (orderId && o.id === orderId) || (orderNumber && o.order_number === orderNumber));
    if (!order) {
        body.innerHTML = '<div class="details-section">Order not found.</div>';
        modal.classList.add('active');
        return;
    }

    const items = order.items || [];

    // Parse delivery info from notes field
    let deliveryInfo = {};
    try {
        if (order.notes) {
            deliveryInfo = typeof order.notes === 'string' ? JSON.parse(order.notes) : order.notes;
        }
    } catch (e) {
        deliveryInfo = { rawNotes: order.notes };
    }

    const zone = deliveryInfo.zone || '';
    const street = deliveryInfo.street || '';
    const building = deliveryInfo.building || '';
    const area = deliveryInfo.area || '';
    const landmark = deliveryInfo.landmark || '';
    const directions = deliveryInfo.directions || '';
    const notes = deliveryInfo.notes || '';
    const phone = deliveryInfo.phone || '';

    // Build comprehensive map link with all address components
    let mapParts = [];
    if (street) mapParts.push(street);
    if (building) mapParts.push(building);
    if (area) mapParts.push(area);
    if (landmark) mapParts.push(landmark);
    mapParts.push('Kampala, Uganda');
    const mapAddress = mapParts.join(', ');
    const mapsLink = mapParts.length > 1 ? `https://www.google.com/maps/search/${encodeURIComponent(mapAddress)}` : '';

    console.log('Order items:', items);
    console.log('Items length:', items.length);
    
    const itemsHtml = items && items.length > 0
        ? items.map(item => `
            <div class="details-row">📦 ${item.product_name || 'Unknown'} × ${item.quantity || 1} — UGX ${parseFloat(item.total_price || (item.unit_price * item.quantity) || 0).toLocaleString('en-US', {maximumFractionDigits: 0})}</div>
          `).join('')
        : '<div class="details-row">No items found</div>';

    body.innerHTML = `
        <div class="details-section">
            <h3>📋 Order Information</h3>
            <div class="details-row"><span class="details-label">Order Number:</span> ${order.order_number || ''}</div>
            <div class="details-row"><span class="details-label">Status:</span> ${order.status || ''}</div>
            <div class="details-row"><span class="details-label">Total:</span> UGX ${parseFloat(order.total_amount || 0).toLocaleString('en-US', {maximumFractionDigits: 0})}</div>
            <div class="details-row"><span class="details-label">Payment:</span> ${order.payment_method || 'Pay on Delivery'}</div>
        </div>
        
        <div class="details-section">
            <h3>👤 Customer Details</h3>
            <div class="details-row"><span class="details-label">Name:</span> ${order.customer_name || ''}</div>
            <div class="details-row"><span class="details-label">Email:</span> ${order.customer_email || ''}</div>
            <div class="details-row"><span class="details-label">Phone:</span> ${phone || 'N/A'}</div>
        </div>
        
        <div class="details-section">
            <h3>🚚 Delivery Information</h3>
            <div class="details-row"><span class="details-label">Delivery Fee:</span> UGX ${parseFloat(order.delivery_cost || 0).toLocaleString('en-US', {maximumFractionDigits: 0})}</div>
            ${zone ? `<div class="details-row"><span class="details-label">Zone:</span> ${zone}</div>` : ''}
            ${street ? `<div class="details-row"><span class="details-label">Street/Road:</span> ${street}</div>` : ''}
            ${building ? `<div class="details-row"><span class="details-label">Building/House:</span> ${building}</div>` : ''}
            ${area ? `<div class="details-row"><span class="details-label">Area:</span> ${area}</div>` : ''}
            ${landmark ? `<div class="details-row"><span class="details-label">Landmark:</span> ${landmark}</div>` : ''}
            ${directions ? `<div class="details-row"><span class="details-label">Directions:</span> ${directions}</div>` : ''}
            ${notes ? `<div class="details-row"><span class="details-label">Delivery Notes:</span> ${notes}</div>` : ''}
            ${order.shipping_address ? `<div class="details-row"><span class="details-label">Full Address:</span> ${order.shipping_address}</div>` : ''}
            ${mapsLink ? `<div class="details-row"><a class="details-link" href="${mapsLink}" target="_blank" rel="noopener">📍 Open in Google Maps</a></div>` : ''}
        </div>
        
        <div class="details-section">
            <h3>📦 Order Items</h3>
            ${itemsHtml}
        </div>
    `;

    modal.classList.add('active');
}

// Auto-enable admin view for local preview and initialize
try {
    localStorage.setItem('isAdmin', 'true');
} catch (e) {
    window.__isAdminFlag = true;
}

// Ensure year in footer
const yearEl = document.getElementById('year');
if (yearEl) yearEl.textContent = new Date().getFullYear();

// Initialize on DOMContentLoaded to ensure all elements are loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        initializeAdmin();
    });
} else {
    initializeAdmin();
}

// Real-time updates via polling every 30 seconds
setInterval(async () => {
    await fetchAndRenderOrders();
}, 30000);

// ===== PAYMENT VERIFICATION FUNCTIONS =====

function openPaymentVerification(orderId, orderNumber, customerName, totalAmount, paymentMethod) {
    const modal = document.getElementById('paymentVerificationModal');
    
    // Populate order information
    document.getElementById('verifyOrderId').value = orderId;
    document.getElementById('verifyOrderNumber').textContent = orderNumber;
    document.getElementById('verifyCustomerName').textContent = customerName;
    document.getElementById('verifyAmount').textContent = `UGX ${parseFloat(totalAmount).toLocaleString()}`;
    document.getElementById('verifyPaymentMethod').textContent = paymentMethod.replace('_', ' ').toUpperCase();
    
    // Pre-fill amount
    document.getElementById('verifyReceivedAmount').value = totalAmount;
    
    // Clear other fields
    document.getElementById('verifyReference').value = '';
    document.getElementById('verifyNotes').value = '';
    
    // Clear error
    document.getElementById('verificationError').style.display = 'none';
    document.getElementById('verificationError').textContent = '';
    
    // Show modal
    modal.classList.add('active');
}

function closePaymentVerification() {
    const modal = document.getElementById('paymentVerificationModal');
    modal.classList.remove('active');
}

async function submitPaymentVerification(event) {
    event.preventDefault();
    
    const orderId = document.getElementById('verifyOrderId').value;
    const reference = document.getElementById('verifyReference').value.trim();
    const amount = parseFloat(document.getElementById('verifyReceivedAmount').value);
    const notes = document.getElementById('verifyNotes').value.trim();
    
    const errorDiv = document.getElementById('verificationError');
    
    // Validate amount
    if (!amount || amount <= 0) {
        errorDiv.textContent = 'Please enter a valid amount received';
        errorDiv.style.display = 'block';
        return;
    }
    
    try {
        const response = await fetch('api/admin/verify-payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                orderId: parseInt(orderId),
                reference: reference,
                amount: amount,
                notes: notes
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            closePaymentVerification();
            alert(`✓ Payment Verified!\n\nOrder ${result.data.orderNumber} has been marked as paid.\nA receipt email has been sent to the customer.`);
            // Refresh orders list
            await fetchAndRenderOrders();
        } else {
            errorDiv.textContent = result.message || 'Failed to verify payment';
            errorDiv.style.display = 'block';
        }
    } catch (error) {
        console.error('Payment verification error:', error);
        errorDiv.textContent = 'An error occurred while verifying payment. Please try again.';
        errorDiv.style.display = 'block';
    }
}

// ===== ORDER FILTERING FUNCTIONS =====

function filterPendingPayments() {
    currentFilter = 'pending_payments';
    const indicator = document.getElementById('filterIndicator');
    const filterText = document.getElementById('filterText');
    
    indicator.style.display = 'block';
    filterText.textContent = 'Showing orders awaiting payment verification';
    
    renderOrders();
}

function viewAllOrders() {
    clearFilter();
}

function clearFilter() {
    currentFilter = null;
    const indicator = document.getElementById('filterIndicator');
    indicator.style.display = 'none';
    
    renderOrders();
}

function viewPendingOrders() {
    currentFilter = 'pending_status';
    const indicator = document.getElementById('filterIndicator');
    const filterText = document.getElementById('filterText');
    
    indicator.style.display = 'block';
    filterText.textContent = 'Showing pending orders';
    
    renderOrders();
}
