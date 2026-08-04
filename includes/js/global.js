/**
 * =============================================================================
 * global.js — Shared Customer-Facing JavaScript
 * =============================================================================
 * Loaded on every customer-facing page via includes/customer_footer.php.
 *
 * Contains three global behaviours:
 *   1. Live Search       — Real-time product search dropdown in the header.
 *   2. Cart Count Updater— Keeps the cart badge count accurate via polling.
 *   3. AI Chat Assistant — Floating chatbot powered by api/gemini_assistant.php.
 *
 * Pages that have their own product-filter JS (products.js, lap_desk.js) will
 * still benefit from this file because it handles the shared header widgets.
 * =============================================================================
 */

document.addEventListener('DOMContentLoaded', function () {

    // =========================================================================
    // 1. LIVE SEARCH
    // =========================================================================
    // Wires the header search input (#searchInput) to the search API and
    // renders a dropdown of matching products as the user types.
    // The search is scoped to the current category page when possible.
    // =========================================================================
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');

    if (searchInput && searchResults) {
        let searchTimeout = null;

        searchInput.addEventListener('input', function (e) {
            clearTimeout(searchTimeout);
            const query = e.target.value.trim();

            if (query.length < 2) {
                searchResults.style.display = 'none';
                return;
            }

            searchTimeout = setTimeout(async function () {
                try {
                    // Detect which category page the user is on and scope results
                    const path = window.location.pathname;
                    let categoryParam = '';
                    if (path.includes('laptops.php')) categoryParam = '&category=1';
                    else if (path.includes('desktops.php')) categoryParam = '&category=2';
                    else if (path.includes('components.php')) categoryParam = '&category=3';
                    else if (path.includes('accessories.php')) categoryParam = '&category=4';
                    else if (path.includes('audio.php')) categoryParam = '&category=5';
                    else if (path.includes('storage.php')) categoryParam = '&category=6';

                    const res = await fetch('api/search_products.php?q=' + encodeURIComponent(query) + categoryParam);
                    const items = await res.json();

                    searchResults.innerHTML = '';

                    if (items.length > 0) {
                        items.forEach(function (item) {
                            // Build a result-link element
                            const link = document.createElement('a');
                            link.href = 'product_details.php?id=' + item.productID;
                            link.style = 'display:flex;align-items:center;padding:10px;text-decoration:none;color:#1f2937;border-bottom:1px solid #f3f4f6;';

                            // Product thumbnail
                            const img = document.createElement('img');
                            img.src = 'assets/products/' + (item.productImage || 'default.jpg');
                            img.style = 'width:40px;height:40px;object-fit:cover;border-radius:4px;margin-right:10px;';

                            // Text block
                            const textDiv = document.createElement('div');
                            textDiv.style = 'flex:1;';

                            const title = document.createElement('div');
                            title.style = 'font-weight:600;font-size:14px;';
                            title.textContent = item.productName;

                            const price = document.createElement('div');
                            price.style = 'color:#2563eb;font-size:13px;font-weight:700;';
                            price.textContent = 'LKR ' + parseFloat(item.price).toLocaleString('en-US', { minimumFractionDigits: 2 });

                            textDiv.appendChild(title);
                            textDiv.appendChild(price);
                            link.appendChild(img);
                            link.appendChild(textDiv);

                            // Row hover highlight
                            link.addEventListener('mouseover', function () { link.style.backgroundColor = '#f9fafb'; });
                            link.addEventListener('mouseout', function () { link.style.backgroundColor = 'transparent'; });

                            searchResults.appendChild(link);
                        });
                        searchResults.style.display = 'block';
                    } else {
                        searchResults.innerHTML = '<div style="padding:15px;color:#6b7280;text-align:center;">No products found in this category.</div>';
                        searchResults.style.display = 'block';
                    }
                } catch (err) {
                    console.error('Search error:', err);
                }
            }, 300); // 300 ms debounce
        });

        // Close the dropdown when the user clicks anywhere outside it
        document.addEventListener('click', function (e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });
    }


    // =========================================================================
    // 2. CART COUNT UPDATER
    // =========================================================================
    // Polls the cart count API every 5 seconds and updates every badge element
    // that carries either the class `.cart-count` or the id `cartCountBadge`.
    // Uses ajax_cart.php (for logged-in customers) and falls back to
    // api/get_cart_count.php for the header badge numeric display.
    // =========================================================================

    /**
     * Refreshes all cart-count display elements on the page.
     * Targets both the navbar badge (cartCountBadge) and any
     * inline span.cart-count elements.
     */
    async function refreshCartBadge() {
        try {
            // Fetch from the lightweight count endpoint
            const res = await fetch('api/get_cart_count.php');
            const data = await res.json();
            const count = data.count || 0;

            // Update the header badge that shows a numeric bubble
            const badge = document.getElementById('cartCountBadge');
            if (badge) {
                badge.textContent = count;
                badge.style.display = count > 0 ? 'flex' : 'none';
            }

            // Update any other cart-count spans (e.g. in nav links)
            document.querySelectorAll('.cart-count').forEach(function (el) {
                el.textContent = count;
            });
        } catch (err) {
            // Silently ignore — non-critical UI element
        }
    }

    // Run once on load, then every 5 seconds
    refreshCartBadge();
    setInterval(refreshCartBadge, 5000);


    // =========================================================================
    // 3. AI CHAT ASSISTANT
    // =========================================================================
    // Powers the floating chatbot widget whose HTML is rendered by
    // customer_footer.php. Sends user messages to api/gemini_assistant.php
    // and displays the AI reply with basic markdown formatting.
    // =========================================================================
    const aiToggleBtn = document.getElementById('aiToggleBtn');
    const aiChatWindow = document.getElementById('aiChatWindow');
    const aiCloseBtn = document.getElementById('aiCloseBtn');
    const aiSendBtn = document.getElementById('aiSendBtn');
    const aiChatInput = document.getElementById('aiChatInput');
    const aiChatBody = document.getElementById('aiChatBody');

    // Only initialise if the AI widget exists on this page
    if (!aiToggleBtn) return;

    // Toggle the chat window open / closed
    aiToggleBtn.addEventListener('click', function () {
        aiChatWindow.style.display = aiChatWindow.style.display === 'none' ? 'flex' : 'none';
    });
    aiCloseBtn.addEventListener('click', function () {
        aiChatWindow.style.display = 'none';
    });

    /**
     * appendMessage — Adds a chat bubble to the conversation.
     * Supports simple markdown: **bold**, newlines, and bullet points.
     * Returns the unique ID assigned to the bubble (used to remove loading state).
     */
    function appendMessage(text, className) {
        const msgDiv = document.createElement('div');
        msgDiv.className = 'chat-message ' + className;

        let formatted = text
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\n/g, '<br>')
            .replace(/\* /g, '<br>&bull; ');
        formatted = formatted.replace(/^(<br>)+/, '');
        msgDiv.innerHTML = formatted;

        // Unique ID so we can remove the loading indicator element later
        const id = 'msg-' + Date.now() + '-' + Math.floor(Math.random() * 10000);
        msgDiv.id = id;

        aiChatBody.appendChild(msgDiv);
        aiChatBody.scrollTop = aiChatBody.scrollHeight;
        return id;
    }

    /**
     * sendMessage — Reads the chat input, posts it to the Gemini API,
     * then replaces the loading bubble with the actual reply.
     */
    async function sendMessage() {
        const message = aiChatInput.value.trim();
        if (!message) return;

        appendMessage(message, 'user-message');
        aiChatInput.value = '';

        // Show a "..." loading bubble while the API responds
        const loadingId = appendMessage('...', 'ai-message loading-msg');

        try {
            const response = await fetch('api/gemini_assistant.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: message })
            });
            const data = await response.json();

            document.getElementById(loadingId).remove();

            if (data.reply) {
                appendMessage(data.reply, 'ai-message');
            } else {
                appendMessage('Sorry, I encountered an error.', 'ai-message error');
            }
        } catch (error) {
            document.getElementById(loadingId).remove();
            appendMessage('Network error. Could not reach the assistant.', 'ai-message error');
        }
    }

    // Send on button click or Enter key
    aiSendBtn.addEventListener('click', sendMessage);
    aiChatInput.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') sendMessage();
    });

    // Newsletter form
    const newsletterForm = document.querySelector('.newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const email = this.querySelector('input').value;

            if (email && email.includes('@')) {
                alert(`Thank you for subscribing with ${email}! You'll receive our newsletter soon.`);
                this.querySelector('input').value = '';
            } else {
                alert('Please enter a valid email address.');
            }
        });
    }

    // =========================================================================
    // PRODUCT ACTIONS (Add to Cart & Quick View)
    // =========================================================================

    // 1. Event Listener
    document.addEventListener('click', function (e) {
        // If Click the Add to Cart button
        if (e.target.closest('.cart-btn')) {
            const button = e.target.closest('.cart-btn');
            const productId = button.getAttribute('data-product');

            // Find the product name from the HTML
            const productCard = button.closest('.product-card');
            let productName = 'Item'; // Default name if not found
            if (productCard) {
                const titleElement = productCard.querySelector('.product-title');
                if (titleElement) {
                    productName = titleElement.textContent.trim();
                }
            }

            // Send the ID and name to the function
            addToCart(productId, productName);
        }

        // If Click the Quick View button
        if (e.target.closest('.quick-view-btn')) {
            const button = e.target.closest('.quick-view-btn');
            const productId = button.getAttribute('data-product');
            window.location.href = 'product_details.php?id=' + productId;
        }
    });

    // 2. Add to Cart Function
    // Added a new parameter called itemName here
    function addToCart(productId, itemName = 'Product') {
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('quantity', 1);

        fetch('ajax_cart.php?action=add', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.message === 'not_logged_in') {
                    showNotification('Please log in to add items to cart', 'warning');
                    setTimeout(() => window.location.href = 'login.php', 1500);
                } else if (data.success) {
                    // This is where the success message with the name is displayed
                    showNotification(`${itemName} added to cart!`, 'success');

                    // Update all the numbers in the header
                    document.querySelectorAll('.cart-count').forEach(el => el.textContent = data.cart_count);
                } else {
                    showNotification(data.message || 'Error adding to cart', 'warning');
                }
            })
            .catch(() => showNotification('Network error. Please try again.', 'warning'));
    }

    // 3. Notification Function (No changes)
    function showNotification(message, type) {
        const n = document.createElement('div');
        n.textContent = message;
        n.style.cssText = `
        position:fixed; top:70px; right:20px; 
        background:${type === 'success' ? '#10b981' : '#f59e0b'}; 
        color:white; padding:15px 25px; border-radius:8px; 
        z-index:10000; box-shadow:0 10px 15px rgba(0,0,0,0.1);
        font-family: sans-serif; font-weight: 500;
    `;
        document.body.appendChild(n);
        setTimeout(() => n.remove(), 3000);
    }
});
