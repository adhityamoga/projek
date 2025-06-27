// js/app.js
// File JavaScript utama untuk aplikasi Warung MadangBae.
// Mengelola semua fungsionalitas frontend seperti menampilkan menu,
// keranjang belanja, checkout, autentikasi pengguna, dan pembaruan UI.

// Variabel global untuk menyimpan semua data produk setelah diambil pertama kali
let allProductsData = [];
let searchTimeout; // Untuk debounce pencarian

document.addEventListener('DOMContentLoaded', () => {
    // --- Variabel Global & Konfigurasi ---
    const deliveryFee = 8000; // Biaya pengiriman tetap (Rp)

    // --- Fungsi Utilitas Umum ---

    /**
     * Menampilkan pesan respons (sukses/error) di UI.
     * Digunakan terutama pada halaman autentikasi (daftar, login) dan profil.
     * @param {string} message - Pesan yang akan ditampilkan.
     * @param {'success'|'error'|'info'} type - Tipe pesan untuk styling.
     */
    function showResponseMessage(message, type) {
        const responseMessageDiv = document.getElementById('responseMessage');
        if (responseMessageDiv) {
            responseMessageDiv.textContent = message;
            responseMessageDiv.className = `response-message ${type}`; // Atur kelas untuk styling
            responseMessageDiv.style.display = 'block'; // Tampilkan elemen
            // Sembunyikan pesan setelah beberapa detik, kecuali di halaman login/daftar agar pesan terlihat
            if (window.location.pathname.includes('login.html') || window.location.pathname.includes('daftar.html') || window.location.pathname.includes('profil.html')) {
                // Jangan sembunyikan otomatis di halaman ini
            } else {
                setTimeout(() => {
                    responseMessageDiv.style.display = 'none';
                    responseMessageDiv.textContent = '';
                }, 5000); // Sembunyikan setelah 5 detik
            }
        }
    }

    /**
     * Mengambil parameter dari URL query string.
     * @param {string} name - Nama parameter yang ingin diambil.
     * @returns {string|null} Nilai parameter atau null jika tidak ditemukan.
     */
    function getUrlParameter(name) {
        name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
        var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
        var results = regex.exec(location.search);
        return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
    }

    /**
     * Memformat angka menjadi format mata uang Rupiah.
     * @param {number} amount - Jumlah uang.
     * @returns {string} String format Rupiah.
     */
    function formatRupiah(amount) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(amount);
    }

    /**
     * Memperbarui kuantitas di ikon keranjang pada navbar.
     */
    function updateCartIconQuantity() {
        const cart = JSON.parse(localStorage.getItem('cart') || '[]');
        const cartQuantityElement = document.getElementById('cartQuantity');
        if (cartQuantityElement) {
            const totalQuantity = cart.reduce((sum, item) => sum + item.quantity, 0);
            cartQuantityElement.textContent = totalQuantity;
            cartQuantityElement.style.display = totalQuantity > 0 ? 'inline-block' : 'none';
        }
    }

    /**
     * Memperbarui UI berdasarkan status autentikasi pengguna.
     * Mengatur tampilan tombol Login/Daftar atau Profil Saya.
     */
    async function updateAuthUI() {
        const userActionsDiv = document.querySelector('.user-actions');
        if (!userActionsDiv) return;

        try {
            // Cek status login dari server
            const response = await fetch('api/get_session.php');
            const data = await response.json();

            userActionsDiv.innerHTML = ''; // Kosongkan dulu

            if (data.isLoggedIn) {
                // Tampilkan nama pengguna dan tombol profil
                const userNameSpan = document.createElement('span');
                userNameSpan.textContent = `Halo, ${data.user.username}!`;
                userNameSpan.style.marginRight = '10px';
                userNameSpan.style.fontWeight = 'bold';
                userNameSpan.style.color = '#fff';
                userActionsDiv.appendChild(userNameSpan);

                const profileLink = document.createElement('a');
                profileLink.href = 'profil.html';
                profileLink.className = 'btn-signup';
                profileLink.textContent = 'Profil Saya';
                userActionsDiv.appendChild(profileLink);

                const logoutBtn = document.createElement('button');
                logoutBtn.textContent = 'Logout';
                logoutBtn.className = 'btn-logout';
                logoutBtn.style.marginLeft = '10px';
                logoutBtn.style.padding = '8px 15px';
                logoutBtn.style.border = 'none';
                logoutBtn.style.borderRadius = '5px';
                logoutBtn.style.backgroundColor = '#dc3545';
                logoutBtn.style.color = 'white';
                logoutBtn.style.cursor = 'pointer';
                logoutBtn.addEventListener('click', async () => {
                    try {
                        await fetch('api/logout.php');
                        showResponseMessage('Anda telah berhasil logout.', 'success');
                        setTimeout(() => {
                            window.location.href = 'login.html';
                        }, 1500);
                    } catch (error) {
                        console.error('Logout error:', error);
                        showResponseMessage('Terjadi kesalahan saat logout.', 'error');
                    }
                });
                userActionsDiv.appendChild(logoutBtn);

            } else {
                // Tampilkan tombol Login dan Daftar
                const loginLink = document.createElement('a');
                loginLink.href = 'login.html';
                loginLink.className = 'btn-login';
                loginLink.textContent = 'Login';
                loginLink.style.marginRight = '10px';
                loginLink.style.color = '#fff';
                loginLink.style.textDecoration = 'none';
                userActionsDiv.appendChild(loginLink);

                const signupLink = document.createElement('a');
                signupLink.href = 'daftar.html';
                signupLink.className = 'btn-signup';
                signupLink.textContent = 'Daftar';
                userActionsDiv.appendChild(signupLink);
            }
        } catch (error) {
            console.error('Error updating auth UI:', error);
            // Fallback ke tampilan tidak login
            userActionsDiv.innerHTML = `
                <a href="login.html" class="btn-login" style="margin-right: 10px; color: #fff; text-decoration: none;">Login</a>
                <a href="daftar.html" class="btn-signup">Daftar</a>
            `;
        }
    }

    // --- Fungsi Khusus Halaman (Pesanan, Menu, Keranjang, dll.) ---

    // ===========================================
    // Halaman Pesanan (pesanan.php)
    // ===========================================
    if (window.location.pathname.includes('pesanan.php')) {
        const currentOrdersContainer = document.getElementById('current-orders');
        const pastOrdersContainer = document.getElementById('past-orders');
        const tabLinks = document.querySelectorAll('.tabs .tab-link');
        let allOrders = []; // Variabel untuk menyimpan semua pesanan yang diambil dari API

        /**
         * Mengambil data pesanan dari API.
         */
        async function loadUserOrders() {
            showResponseMessage('Memuat pesanan Anda...', 'info');
            try {
                const response = await fetch('api/get_orders.php');
                const data = await response.json();

                if (data.status === 'error') {
                    showResponseMessage(data.message, 'error');
                    currentOrdersContainer.innerHTML = '<p class="empty-orders-message">Tidak ada pesanan yang sedang berjalan saat ini.</p>';
                    pastOrdersContainer.innerHTML = '<p class="empty-orders-message">Belum ada riwayat pesanan.</p>';
                    return;
                }

                allOrders = data; // Simpan semua pesanan

                // Filter dan tampilkan pesanan
                filterAndDisplayOrders();
                showResponseMessage('Pesanan berhasil dimuat.', 'success');
                setTimeout(() => {
                    const responseMsg = document.getElementById('responseMessage');
                    if (responseMsg) responseMsg.style.display = 'none';
                }, 1500);
            } catch (error) {
                console.error('Error loading orders:', error);
                showResponseMessage('Gagal memuat pesanan. Silakan coba lagi.', 'error');
                currentOrdersContainer.innerHTML = '<p class="empty-orders-message">Gagal memuat pesanan.</p>';
                pastOrdersContainer.innerHTML = '<p class="empty-orders-message">Gagal memuat riwayat pesanan.</p>';
            }
        }

        /**
         * Memfilter pesanan berdasarkan status dan menampilkannya.
         */
        function filterAndDisplayOrders() {
            currentOrdersContainer.innerHTML = '';
            pastOrdersContainer.innerHTML = '';

            const currentOrders = allOrders.filter(order =>
                ['pending', 'preparing', 'on_delivery'].includes(order.status)
            );
            const pastOrders = allOrders.filter(order =>
                ['delivered', 'cancelled'].includes(order.status)
            );

            displayOrders(currentOrders, currentOrdersContainer, 'Tidak ada pesanan yang sedang berjalan saat ini.');
            displayOrders(pastOrders, pastOrdersContainer, 'Belum ada riwayat pesanan.');

            // Setelah data dimuat dan dirender, aktifkan kembali tab yang terakhir aktif
            const activeTabId = document.querySelector('.tab-link.active')?.dataset.tab || 'current-orders';
            activateTab(activeTabId);
        }

        /**
         * Merender daftar pesanan ke dalam kontainer yang ditentukan.
         * @param {Array} orders - Array objek pesanan.
         * @param {HTMLElement} container - Elemen DOM tempat pesanan akan dirender.
         * @param {string} emptyMessage - Pesan yang ditampilkan jika tidak ada pesanan.
         */
        function displayOrders(orders, container, emptyMessage) {
            if (orders.length === 0) {
                container.innerHTML = `<p class="empty-orders-message">${emptyMessage}</p>`;
                return;
            }

            orders.forEach(order => {
                const orderCard = document.createElement('div');
                orderCard.className = 'order-card';
                orderCard.dataset.orderId = order.id;

                let itemsHtml = '';
                if (order.items && Array.isArray(order.items)) {
                    order.items.forEach(item => {
                        const imageUrl = item.image_url || 'https://placehold.co/60x60/EAEAEA/555555?text=No+Img';
                        itemsHtml += `
                            <div class="order-card-item">
                                <img src="${imageUrl}" alt="${item.product_name}">
                                <div class="item-info">
                                    <p class="item-name">${item.product_name}</p>
                                    <p class="item-qty-price">${item.quantity}x ${formatRupiah(item.price_at_order)}</p>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    itemsHtml = '<p>Detail item tidak tersedia.</p>';
                }

                let actionButtonsHtml = '';
                // Tombol "Batalkan" hanya untuk status 'pending' atau 'preparing'
                if (order.status === 'pending' || order.status === 'preparing') {
                    actionButtonsHtml += `<button class="btn-action btn-cancel" data-order-id="${order.id}">Batalkan</button>`;
                }
                // Tombol "Selesai" hanya untuk status 'on_delivery'
                if (order.status === 'on_delivery') {
                    actionButtonsHtml += `<button class="btn-action btn-complete" data-order-id="${order.id}">Selesai</button>`;
                }
                // Tombol "Pesan Lagi" untuk semua pesanan yang sudah selesai atau dibatalkan
                if (order.status === 'delivered' || order.status === 'cancelled') {
                    actionButtonsHtml += `<button class="btn-action btn-reorder" data-order-id="${order.id}">Pesan Lagi</button>`;
                }

                orderCard.innerHTML = `
                    <div class="order-card-header">
                        <h3>Order #${order.id}</h3>
                        <span class="status ${order.status.replace('_', '-')}">
                            ${order.status.replace('_', ' ')}
                        </span>
                    </div>
                    <p class="order-date">Tanggal: ${new Date(order.order_date).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })}</p>
                    <div class="order-items-list-summary">
                        ${itemsHtml}
                    </div>
                    <div class="order-card-footer">
                        <span class="total-price">Total: ${formatRupiah(order.total_amount)}</span>
                        <div class="action-buttons">
                            ${actionButtonsHtml}
                        </div>
                    </div>
                `;
                container.appendChild(orderCard);
            });
        }

        /**
         * Mengubah status pesanan melalui API.
         * @param {number} orderId - ID pesanan yang akan diupdate.
         * @param {string} action - Aksi yang ingin dilakukan ('cancel' atau 'complete').
         */
        async function updateOrderStatus(orderId, action) {
            if (!confirm(`Anda yakin ingin ${action === 'cancel' ? 'membatalkan' : 'menyelesaikan'} pesanan #${orderId}?`)) {
                return;
            }

            showResponseMessage('Memproses permintaan...', 'info');
            try {
                const response = await fetch('api/update_order_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ order_id: orderId, action: action })
                });
                const result = await response.json();

                if (result.success) {
                    showResponseMessage(result.message, 'success');
                    loadUserOrders(); // Muat ulang pesanan setelah update berhasil
                } else {
                    showResponseMessage(result.message, 'error');
                }
            } catch (error) {
                console.error('Error updating order status:', error);
                showResponseMessage('Terjadi kesalahan saat memperbarui status pesanan.', 'error');
            }
        }

        /**
         * Mengarahkan pengguna ke halaman checkout dengan item pesanan sebelumnya.
         * @param {number} orderId - ID pesanan yang akan di-reorder.
         */
        async function reorder(orderId) {
            const orderToReorder = allOrders.find(order => order.id === orderId);

            if (!orderToReorder || !orderToReorder.items || orderToReorder.items.length === 0) {
                showResponseMessage('Detail pesanan untuk "Pesan Lagi" tidak ditemukan.', 'error');
                return;
            }

            if (confirm(`Anda yakin ingin memesan ulang pesanan #${orderId}? Item dari pesanan ini akan ditambahkan ke keranjang Anda.`)) {
                let cart = JSON.parse(localStorage.getItem('cart') || '[]');

                orderToReorder.items.forEach(item => {
                    const existingItemIndex = cart.findIndex(cartItem => cartItem.id === item.product_id);
                    if (existingItemIndex > -1) {
                        cart[existingItemIndex].quantity += item.quantity;
                    } else {
                        cart.push({
                            id: item.product_id,
                            name: item.product_name,
                            price: item.price_at_order,
                            image: item.image_url,
                            quantity: item.quantity
                        });
                    }
                });

                localStorage.setItem('cart', JSON.stringify(cart));
                updateCartIconQuantity();

                showResponseMessage('Item telah ditambahkan ke keranjang!', 'success');
                setTimeout(() => {
                    window.location.href = 'keranjang.html';
                }, 1500);
            }
        }

        // Event Listeners untuk Tab
        tabLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const targetTab = this.dataset.tab;
                activateTab(targetTab);
            });
        });

        // Event listener delegasi untuk tombol aksi
        document.querySelector('.order-sections').addEventListener('click', function(event) {
            const target = event.target;
            if (target.classList.contains('btn-cancel')) {
                const orderId = parseInt(target.dataset.orderId);
                updateOrderStatus(orderId, 'cancel');
            } else if (target.classList.contains('btn-complete')) {
                const orderId = parseInt(target.dataset.orderId);
                updateOrderStatus(orderId, 'complete');
            } else if (target.classList.contains('btn-reorder')) {
                const orderId = parseInt(target.dataset.orderId);
                reorder(orderId);
            }
        });

        // Fungsi untuk mengaktifkan tab tertentu
        function activateTab(tabId) {
            document.querySelectorAll('.order-list').forEach(section => {
                section.classList.remove('active-tab');
            });
            document.querySelectorAll('.tab-link').forEach(link => {
                link.classList.remove('active');
            });

            const targetSection = document.getElementById(tabId);
            const targetLink = document.querySelector(`.tab-link[data-tab="${tabId}"]`);

            if (targetSection) targetSection.classList.add('active-tab');
            if (targetLink) targetLink.classList.add('active');
        }

        // Muat pesanan saat halaman dimuat
        loadUserOrders();
        updateCartIconQuantity();
        updateAuthUI();
    }

    // ===========================================
    // Halaman Menu (menu.html)
    // ===========================================
    if (window.location.pathname.includes('menu.html')) {
        const productGrid = document.getElementById('menuGrid');
        const searchInput = document.getElementById('menuSearchInput');

        async function loadProducts() {
            try {
                const response = await fetch('api/get_products.php');
                const products = await response.json();
                
                if (products.status === 'error') {
                    productGrid.innerHTML = '<p class="empty-orders-message">Gagal memuat menu: ' + products.message + '</p>';
                    return;
                }
                
                allProductsData = products;
                displayProducts(products);
            } catch (error) {
                console.error('Error loading products:', error);
                productGrid.innerHTML = '<p class="empty-orders-message">Gagal memuat menu. Silakan coba lagi.</p>';
            }
        }

        function displayProducts(products) {
            productGrid.innerHTML = '';
            if (products.length === 0) {
                productGrid.innerHTML = '<p class="empty-orders-message">Tidak ada menu yang ditemukan.</p>';
                return;
            }
            products.forEach(product => {
                const productCard = document.createElement('div');
                productCard.className = 'food-card';
                productCard.innerHTML = `
                    <img src="${product.image_url || 'https://via.placeholder.com/150'}" alt="${product.name}">
                    <h4>${product.name}</h4>
                    <p class="product-description">${product.description ? product.description.substring(0, 70) + '...' : 'Tidak ada deskripsi'}</p>
                    <p class="price">${formatRupiah(product.price)}</p>
                    <button class="btn-order" data-id="${product.id}" data-name="${product.name}" data-price="${product.price}" data-image="${product.image_url}">
                        Tambah ke Keranjang
                    </button>
                `;
                productGrid.appendChild(productCard);
            });

            // Add to cart event listeners
            document.querySelectorAll('.btn-order').forEach(button => {
                button.addEventListener('click', addToCart);
            });
        }

        function filterProducts() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const searchTerm = searchInput.value.toLowerCase();
                const filteredProducts = allProductsData.filter(product =>
                    product.name.toLowerCase().includes(searchTerm) ||
                    (product.description && product.description.toLowerCase().includes(searchTerm))
                );
                displayProducts(filteredProducts);
            }, 300);
        }

        function addToCart(event) {
            const button = event.target;
            const id = parseInt(button.dataset.id);
            const name = button.dataset.name;
            const price = parseFloat(button.dataset.price);
            const image = button.dataset.image;

            let cart = JSON.parse(localStorage.getItem('cart') || '[]');
            const existingItem = cart.find(item => item.id === id);

            if (existingItem) {
                existingItem.quantity++;
            } else {
                cart.push({ id, name, price, image, quantity: 1 });
            }

            localStorage.setItem('cart', JSON.stringify(cart));
            updateCartIconQuantity();
            showResponseMessage(`${name} ditambahkan ke keranjang!`, 'success');
        }

        if (searchInput) {
            searchInput.addEventListener('input', filterProducts);
        }
        loadProducts();
        updateCartIconQuantity();
        updateAuthUI();
    }

    // ===========================================
    // Halaman Keranjang (keranjang.html)
    // ===========================================
    if (window.location.pathname.includes('keranjang.html')) {
        const cartItemsContainer = document.getElementById('cartItemsContainer');
        const subtotalElement = document.getElementById('subtotalPrice');
        const deliveryFeeElement = document.getElementById('deliveryFee');
        const finalTotalElement = document.getElementById('finalTotalPrice');
        const totalItemsElement = document.getElementById('totalItems');
        const checkoutBtn = document.querySelector('.btn-checkout');

        function renderCart() {
            let cart = JSON.parse(localStorage.getItem('cart') || '[]');
            cartItemsContainer.innerHTML = '';
            let subtotal = 0;
            let totalItems = 0;

            if (cart.length === 0) {
                cartItemsContainer.innerHTML = '<p class="empty-orders-message">Keranjang belanja Anda kosong.</p>';
                if (subtotalElement) subtotalElement.textContent = formatRupiah(0);
                if (deliveryFeeElement) deliveryFeeElement.textContent = formatRupiah(0);
                if (finalTotalElement) finalTotalElement.textContent = formatRupiah(0);
                if (totalItemsElement) totalItemsElement.textContent = '0';
                if (checkoutBtn) checkoutBtn.style.pointerEvents = 'none';
                return;
            }

            cart.forEach(item => {
                const itemTotal = item.price * item.quantity;
                subtotal += itemTotal;
                totalItems += item.quantity;

                const cartItemDiv = document.createElement('div');
                cartItemDiv.className = 'cart-item';
                cartItemDiv.innerHTML = `
                    <img src="${item.image || 'https://via.placeholder.com/80'}" alt="${item.name}">
                    <div class="item-details">
                        <h3>${item.name}</h3>
                        <p class="price">${formatRupiah(item.price)}</p>
                    </div>
                    <div class="quantity-selector">
                        <button class="qty-btn decrement" data-id="${item.id}">-</button>
                        <span>${item.quantity}</span>
                        <button class="qty-btn increment" data-id="${item.id}">+</button>
                    </div>
                    <span class="remove-item" data-id="${item.id}">
                        <i class="fa-solid fa-trash"></i>
                    </span>
                `;
                cartItemsContainer.appendChild(cartItemDiv);
            });

            const grandTotal = subtotal + deliveryFee;

            if (subtotalElement) subtotalElement.textContent = formatRupiah(subtotal);
            if (deliveryFeeElement) deliveryFeeElement.textContent = formatRupiah(deliveryFee);
            if (finalTotalElement) finalTotalElement.textContent = formatRupiah(grandTotal);
            if (totalItemsElement) totalItemsElement.textContent = totalItems.toString();
            if (checkoutBtn) checkoutBtn.style.pointerEvents = 'auto';

            attachCartEventListeners();
            updateCartIconQuantity();
        }

        function attachCartEventListeners() {
            document.querySelectorAll('.qty-btn').forEach(button => {
                button.addEventListener('click', handleQuantityChange);
            });
            document.querySelectorAll('.remove-item').forEach(button => {
                button.addEventListener('click', removeItemFromCart);
            });
        }

        function handleQuantityChange(event) {
            const button = event.target;
            const id = parseInt(button.dataset.id);
            let cart = JSON.parse(localStorage.getItem('cart') || '[]');
            const itemIndex = cart.findIndex(item => item.id === id);

            if (itemIndex > -1) {
                if (button.classList.contains('decrement')) {
                    cart[itemIndex].quantity--;
                    if (cart[itemIndex].quantity <= 0) {
                        cart.splice(itemIndex, 1);
                    }
                } else if (button.classList.contains('increment')) {
                    cart[itemIndex].quantity++;
                }
                localStorage.setItem('cart', JSON.stringify(cart));
                renderCart();
            }
        }

        function removeItemFromCart(event) {
            const button = event.target.closest('.remove-item');
            const id = parseInt(button.dataset.id);
            let cart = JSON.parse(localStorage.getItem('cart') || '[]');
            cart = cart.filter(item => item.id !== id);
            localStorage.setItem('cart', JSON.stringify(cart));
            renderCart();
            showResponseMessage('Item berhasil dihapus dari keranjang.', 'success');
        }

        if (checkoutBtn) {
            checkoutBtn.addEventListener('click', async () => {
                const cart = JSON.parse(localStorage.getItem('cart') || '[]');
                if (cart.length === 0) {
                    showResponseMessage('Keranjang Anda kosong. Tambahkan item terlebih dahulu.', 'error');
                    return;
                }

                // Cek status login dari server
                try {
                    const response = await fetch('api/get_session.php');
                    const data = await response.json();
                    
                    if (!data.isLoggedIn) {
                        if (confirm('Anda harus login untuk melanjutkan checkout. Apakah Anda ingin login sekarang?')) {
                            window.location.href = 'login.html';
                        }
                        return;
                    }

                    // Jika sudah login, lanjutkan ke halaman checkout
                    window.location.href = 'checkout.html';
                } catch (error) {
                    console.error('Error checking login status:', error);
                    showResponseMessage('Terjadi kesalahan. Silakan coba lagi.', 'error');
                }
            });
        }

        renderCart();
        updateCartIconQuantity();
        updateAuthUI();
    }

    // ===========================================
    // Halaman Checkout (checkout.html)
    // ===========================================
    if (window.location.pathname.includes('checkout.html')) {
        const summaryItemsContainer = document.getElementById('summaryItemsContainer');
        const checkoutDeliveryFee = document.getElementById('checkoutDeliveryFee');
        const checkoutTotalPrice = document.getElementById('checkoutTotalPrice');
        const placeOrderBtn = document.querySelector('.btn-place-order');
        const checkoutForm = document.getElementById('checkoutForm');

        let cart = JSON.parse(localStorage.getItem('cart') || '[]');
        let subtotal = 0;

        function renderCheckoutSummary() {
            if (summaryItemsContainer) {
                summaryItemsContainer.innerHTML = '';
            }
            subtotal = 0;

            if (cart.length === 0) {
                if (summaryItemsContainer) {
                    summaryItemsContainer.innerHTML = '<p>Keranjang Anda kosong. Silakan kembali ke menu.</p>';
                }
                if (placeOrderBtn) placeOrderBtn.disabled = true;
                return;
            }

            cart.forEach(item => {
                const itemTotal = item.price * item.quantity;
                subtotal += itemTotal;

                if (summaryItemsContainer) {
                    const itemDiv = document.createElement('div');
                    itemDiv.className = 'summary-item';
                    itemDiv.innerHTML = `
                        <span>${item.name} x ${item.quantity}</span>
                        <span>${formatRupiah(itemTotal)}</span>
                    `;
                    summaryItemsContainer.appendChild(itemDiv);
                }
            });

            const grandTotal = subtotal + deliveryFee;

            if (checkoutDeliveryFee) checkoutDeliveryFee.textContent = formatRupiah(deliveryFee);
            if (checkoutTotalPrice) checkoutTotalPrice.textContent = formatRupiah(grandTotal);
            if (placeOrderBtn) placeOrderBtn.disabled = false;
        }

        if (placeOrderBtn) {
            placeOrderBtn.addEventListener('click', async () => {
                const namaLengkap = document.getElementById('nama_lengkap')?.value.trim();
                const telepon = document.getElementById('telepon')?.value.trim();
                const alamatLengkap = document.getElementById('alamat_lengkap')?.value.trim();

                if (cart.length === 0) {
                    showResponseMessage('Keranjang Anda kosong.', 'error');
                    return;
                }
                if (!namaLengkap || !telepon || !alamatLengkap) {
                    showResponseMessage('Semua field harus diisi.', 'error');
                    return;
                }

                const deliveryAddress = `${namaLengkap}\n${telepon}\n${alamatLengkap}`;

                showResponseMessage('Menempatkan pesanan...', 'info');

                try {
                    const response = await fetch('api/place_order.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            delivery_address: deliveryAddress,
                            notes: '',
                            items: cart,
                            total_amount: subtotal + deliveryFee
                        })
                    });

                    const result = await response.json();

                    if (result.status === 'success') {
                        showResponseMessage(result.message, 'success');
                        localStorage.removeItem('cart');
                        updateCartIconQuantity();
                        setTimeout(() => {
                            window.location.href = 'pesanan.php';
                        }, 2000);
                    } else {
                        showResponseMessage(result.message, 'error');
                    }
                } catch (error) {
                    console.error('Error placing order:', error);
                    showResponseMessage('Terjadi kesalahan saat menempatkan pesanan. Silakan coba lagi.', 'error');
                }
            });
        }

        renderCheckoutSummary();
        updateCartIconQuantity();
        updateAuthUI();
    }

    // ===========================================
    // Halaman Auth (login.html, daftar.html)
    // ===========================================
    if (window.location.pathname.includes('login.html')) {
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const usernameOrEmail = loginForm.username_or_email.value;
                const password = loginForm.password.value;

                showResponseMessage('Mencoba login...', 'info');

                try {
                    const formData = new FormData();
                    formData.append('username_or_email', usernameOrEmail);
                    formData.append('password', password);

                    const response = await fetch('api/login.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();

                    if (result.status === 'success') {
                        showResponseMessage(result.message, 'success');
                        updateAuthUI();
                        setTimeout(() => {
                            window.location.href = 'index.html';
                        }, 1500);
                    } else {
                        showResponseMessage(result.message, 'error');
                    }
                } catch (error) {
                    console.error('Login error:', error);
                    showResponseMessage('Terjadi kesalahan saat login.', 'error');
                }
            });
        }
        updateAuthUI();
    }

    if (window.location.pathname.includes('daftar.html')) {
        const registerForm = document.getElementById('registerForm');
        if (registerForm) {
            registerForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const username = registerForm.username.value;
                const email = registerForm.email.value;
                const password = registerForm.password.value;
                const fullName = registerForm.full_name.value;

                showResponseMessage('Mencoba mendaftar...', 'info');

                try {
                    const formData = new FormData();
                    formData.append('username', username);
                    formData.append('email', email);
                    formData.append('password', password);
                    formData.append('full_name', fullName);

                    const response = await fetch('api/register.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();

                    if (result.status === 'success') {
                        showResponseMessage(result.message + ' Anda dapat login sekarang.', 'success');
                        setTimeout(() => {
                            window.location.href = 'login.html';
                        }, 2000);
                    } else {
                        showResponseMessage(result.message, 'error');
                    }
                } catch (error) {
                    console.error('Register error:', error);
                    showResponseMessage('Terjadi kesalahan saat mendaftar.', 'error');
                }
            });
        }
        updateAuthUI();
    }

    // ===========================================
    // Halaman Profil (profil.html)
    // ===========================================
    if (window.location.pathname.includes('profil.html')) {
        const profileName = document.getElementById('profileName');
        const profileUsername = document.getElementById('profileUsername');
        const logoutProfileButton = document.getElementById('logoutProfileButton');

        async function loadUserProfile() {
            try {
                const response = await fetch('api/get_session.php');
                const data = await response.json();

                if (data.isLoggedIn) {
                    if (profileName) profileName.textContent = data.user.full_name || data.user.username;
                    if (profileUsername) profileUsername.textContent = `@${data.user.username}`;
                } else {
                    showResponseMessage('Anda harus login untuk melihat profil.', 'error');
                    setTimeout(() => { window.location.href = 'login.html'; }, 1500);
                }
            } catch (error) {
                console.error('Error loading profile:', error);
                showResponseMessage('Gagal memuat data profil.', 'error');
            }
        }

        if (logoutProfileButton) {
            logoutProfileButton.addEventListener('click', async () => {
                try {
                    await fetch('api/logout.php');
                    showResponseMessage('Anda telah berhasil logout.', 'success');
                    setTimeout(() => {
                        window.location.href = 'login.html';
                    }, 1500);
                } catch (error) {
                    console.error('Logout error:', error);
                    showResponseMessage('Terjadi kesalahan saat logout.', 'error');
                }
            });
        }

        loadUserProfile();
        updateAuthUI();
    }

    // ===========================================
    // Halaman Utama (index.html)
    // ===========================================
    if (window.location.pathname.includes('index.html') || window.location.pathname === '/' || window.location.pathname.endsWith('/madangbae/')) {
        const favoriteMenuGrid = document.getElementById('favoriteMenuGrid');
        const homeSearchInput = document.getElementById('homeSearchInput');
        const promoModal = document.getElementById('promoModal');
        const closeButton = document.querySelector('.close-button');
        const btnPromo = document.querySelector('.btn-promo');
        const modalCtaButton = document.querySelector('.modal-cta-button');

        async function loadFavoriteProducts() {
            try {
                const response = await fetch('api/get_products.php');
                const products = await response.json();
                
                if (products.status === 'error') {
                    favoriteMenuGrid.innerHTML = '<p class="empty-orders-message">Gagal memuat menu favorit.</p>';
                    return;
                }
                
                // Ambil 4 produk acak sebagai "favorit"
                const shuffled = [...products].sort(() => 0.5 - Math.random());
                const favoriteProducts = shuffled.slice(0, 4); 
                
                displayFavoriteProducts(favoriteProducts);
            } catch (error) {
                console.error('Error loading favorite products:', error);
                favoriteMenuGrid.innerHTML = '<p class="empty-orders-message">Gagal memuat menu favorit.</p>';
            }
        }

        function displayFavoriteProducts(products) {
            favoriteMenuGrid.innerHTML = '';
            if (products.length === 0) {
                favoriteMenuGrid.innerHTML = '<p class="empty-orders-message">Tidak ada menu favorit yang ditemukan.</p>';
                return;
            }
            products.forEach(product => {
                const productCard = document.createElement('div');
                productCard.className = 'food-card';
                productCard.innerHTML = `
                    <img src="${product.image_url || 'https://via.placeholder.com/150'}" alt="${product.name}">
                    <h4>${product.name}</h4>
                    <p class="product-description">${product.description ? product.description.substring(0, 70) + '...' : 'Tidak ada deskripsi'}</p>
                    <p class="price">${formatRupiah(product.price)}</p>
                    <button class="btn-order" data-id="${product.id}" data-name="${product.name}" data-price="${product.price}" data-image="${product.image_url}">
                        Tambah ke Keranjang
                    </button>
                `;
                favoriteMenuGrid.appendChild(productCard);
            });

            // Add to cart event listeners for homepage products
            document.querySelectorAll('#favoriteMenuGrid .btn-order').forEach(button => {
                button.addEventListener('click', addToCartHome);
            });
        }

        function addToCartHome(event) {
            const button = event.target;
            const id = parseInt(button.dataset.id);
            const name = button.dataset.name;
            const price = parseFloat(button.dataset.price);
            const image = button.dataset.image;

            let cart = JSON.parse(localStorage.getItem('cart') || '[]');
            const existingItem = cart.find(item => item.id === id);

            if (existingItem) {
                existingItem.quantity++;
            } else {
                cart.push({ id, name, price, image, quantity: 1 });
            }

            localStorage.setItem('cart', JSON.stringify(cart));
            updateCartIconQuantity();
            showResponseMessage(`${name} ditambahkan ke keranjang!`, 'success');
        }

        // Modal functionality
        if (btnPromo && promoModal) {
            btnPromo.addEventListener('click', function(e) {
                e.preventDefault();
                promoModal.style.display = 'flex';
            });
        }

        if (closeButton && promoModal) {
            closeButton.addEventListener('click', function() {
                promoModal.style.display = 'none';
            });
        }

        if (modalCtaButton) {
            modalCtaButton.addEventListener('click', function() {
                window.location.href = 'menu.html';
            });
        }

        if (promoModal) {
            window.addEventListener('click', function(event) {
                if (event.target === promoModal) {
                    promoModal.style.display = 'none';
                }
            });
        }

        // Search functionality
        if (homeSearchInput) {
            homeSearchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    const query = this.value.trim();
                    if (query) {
                        window.location.href = `menu.html?search=${encodeURIComponent(query)}`;
                    }
                }
            });
        }

        loadFavoriteProducts();
        updateCartIconQuantity();
        updateAuthUI();
    }

    // Inisialisasi umum untuk semua halaman
    updateCartIconQuantity();
    updateAuthUI();
});