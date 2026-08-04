    
    <div class="ai-assistant-container">
        <button class="ai-btn" id="aiToggleBtn">
            <i class="fas fa-robot"></i>
            <span>Tech Assistant</span>
        </button>

        <div class="ai-chat-window" id="aiChatWindow" style="display: none;">
            <div class="ai-chat-header">
                <h3><i class="fas fa-robot"></i> Tech Shark AI</h3>
                <button id="aiCloseBtn"><i class="fas fa-times"></i></button>
            </div>
            <div class="ai-chat-body" id="aiChatBody">
                <div class="chat-message ai-message">
                    Hello! I'm the Tech Shark Assistant. How can I help you find the perfect product today?
                </div>
            </div>
            <div class="ai-chat-footer">
                <input type="text" id="aiChatInput" placeholder="Ask me anything...">
                <button id="aiSendBtn"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="customer-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h3>Tech Shark</h3>
                    <p>All in one place. Quality products with expert support.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                
                <div class="footer-col">
                    <h3>Shop</h3>
                    <ul>
                        <li><a href="laptops.php">Laptops</a></li>
                        <li><a href="desktops.php">Desktops</a></li>
                        <li><a href="components.php">Components</a></li>
                        <li><a href="accessories.php">Accessories</a></li>
                        <li><a href="audio.php">Audio</a></li>
                        <li><a href="storage.php">Storage</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h3>Support</h3>
                    <ul>
                        <li><a href="contact.php">Contact Us</a></li>
                        <li><a href="faq.php">FAQs</a></li>
                        <li><a href="repairs.php">Repairs</a></li>
                        <li><a href="contact.php"> Inquiry</a></li>
                        <li><a href="profile.php?tab=orders">Shipping</a></li>
                        <li><a href="profile.php?tab=orders">Track Order</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h3>Company</h3>
                    <ul>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="blog.php">Blog</a></li>
                        <li><a href="careers.php">Careers</a></li>
                        <li><a href="privacy.php">Privacy Policy</a></li>
                        <li><a href="terms.php">Terms of Service</a></li>
                    </ul>
                </div>
                
                <div class="footer-col newsletter">
                    <h3>Newsletter</h3>
                    <p>Subscribe to get updates on new products and special offers.</p>
                    <form class="newsletter-form">
                        <input type="email" placeholder="Your email address">
                        <button type="submit">Subscribe</button>
                    </form>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2025 Tech Shark. All rights reserved.</p>
                <div class="payment-methods">
                    <i class="fab fa-cc-visa"></i>
                    <i class="fab fa-cc-mastercard"></i>
                </div>
            </div>
        </div>
    </footer>

    <!-- =====================================================================
         Global JavaScript — loaded on every customer page.
         Handles: Live Search, Cart Count Updater, AI Chat Assistant.
         ===================================================================== -->
    <script src="includes/js/global.js"></script>
    
