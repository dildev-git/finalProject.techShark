<?php
session_start();
include('includes/dbconnection.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tech Shark Computer Shop</title>
    <link rel="icon" type="image/png" href="assets/logo.png"/>
    <link rel="stylesheet" href="includes/css/customer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css">
</head>
<body>
    <!-- Header -->
    <?php include 'includes/customer_header.php'; ?>
    
    <!-- Hero Banner -->
<section class="hero-banner">
    <div class="swiper banner-slider">
        <div class="swiper-wrapper">
            <div class="swiper-slide slide-1" style="background-image: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)), url('https://images.unsplash.com/photo-1593642632823-8f785ba67e45?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');">
                <div class="container">
                    <div class="slide-content">
                        <h1>Tech Shark</h1>
                        <p>Trusted by thousands of customer base</p>
                        <a href="laptops.php" class="btn">Shop Now</a>
                    </div>
                </div>
            </div>
            <div class="swiper-slide slide-2" style="background-image: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)), url('https://images.unsplash.com/photo-1517336714731-489689fd1ca8?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');">
                <div class="container">
                    <div class="slide-content">
                        <h1>New Arrivals</h1>
                        <p>Discover the latest tech gadgets and components</p>
                        <a href="accessories.php" class="btn">Explore</a>
                    </div>
                </div>
            </div>
            <div class="swiper-slide slide-3" style="background-image: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)), url('https://images.unsplash.com/photo-1552831388-6a0b3575b32a?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');">
                <div class="container">
                    <div class="slide-content">
                        <h1>Customer Support</h1>
                        <p>Reach out to us to request information or submit a detailed inquiry.</p>
                        <a href="contact.php" class="btn">Contact Us</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="swiper-pagination"></div>
    </div>
</section>
    
    <!-- Categories -->
    <section class="categories-section">
        <div class="container">
            <h2 class="section-title">Shop by Category</h2>
            <div class="categories-grid">
                <a href="laptops.php" class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-laptop"></i>
                    </div>
                    <h3>Laptops</h3>
                    <p>From ultrabooks to workstations</p>
                </a>
                <a href="desktops.php" class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-desktop"></i>
                    </div>
                    <h3>Desktops</h3>
                    <p>Powerful computers for all needs</p>
                </a>
                <a href="components.php" class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-microchip"></i>
                    </div>
                    <h3>Components</h3>
                    <p>CPUs, GPUs, RAM and more</p>
                </a>
                <a href="accessories.php" class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-keyboard"></i>
                    </div>
                    <h3>Accessories</h3>
                    <p>Keyboards, mouses, monitors</p>
                </a>
                <a href="audio.php" class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-headphones"></i>
                    </div>
                    <h3>Audio</h3>
                    <p>Headphones, speakers, mics</p>
                </a>
                <a href="storage.php" class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-database"></i>
                    </div>
                    <h3>Storage</h3>
                    <p>SSDs, HDDs, external drives</p>
                </a>
            </div>
        </div>
    </section>
    
    <!-- Featured Products -->
    <section class="products-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Featured Products</h2>
            </div>
            
            <div class="products-grid">
                <?php
                $featured_query = "SELECT * FROM Product WHERE status = 'Active' ORDER BY addedDate DESC LIMIT 4";
                $featured_result = mysqli_query($conn, $featured_query);
                
                while($product = mysqli_fetch_array($featured_result)) {
                    $badge_class = "";
                    $badge_text = "";
                    
                    // Determine badge type
                    if($product['oldPrice'] > 0 && $product['oldPrice'] > $product['price']) {
                        $discount = (($product['oldPrice'] - $product['price']) / $product['oldPrice']) * 100;
                        $badge_class = "product-badge";
                        $badge_text = "-" . round($discount) . "%";
                    } elseif(strtotime($product['addedDate']) > strtotime('-30 days')) {
                        $badge_class = "product-badge new";
                        $badge_text = "New";
                    }
                ?>
                
                <div class="product-card">
                <?php if(!empty($badge_text)): ?>
                    <div class="<?php echo $badge_class; ?>"><?php echo $badge_text; ?></div>
                    <?php endif; ?>
                    
                    <div class="product-image">
                        <img src="assets/products/<?php echo $product['productImage'] ?: 'default.jpg'; ?>" alt="<?php echo $product['productName']; ?>">
                        <div class="product-actions">
                            <button class="action-btn quick-view-btn" data-product="<?php echo $product['productID']; ?>"><i class="fas fa-eye"></i></button>
                            <button class="action-btn cart-btn" data-product="<?php echo $product['productID']; ?>"><i class="fas fa-shopping-cart"></i></button>
                        </div>
                    </div>
                    <div class="product-info">
                        <h3 class="product-title"><?php echo $product['productName']; ?></h3>
                        <div class="product-rating">
                            <?php
                            $rating = $product['rating'];
                            $full_stars = floor($rating);
                            $half_star = ($rating - $full_stars) >= 0.5;
                            $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
                            
                            // Full stars
                            for($i = 0; $i < $full_stars; $i++) {
                                echo '<i class="fas fa-star"></i>';
                            }
                            
                            // Half star
                            if($half_star) {
                                echo '<i class="fas fa-star-half-alt"></i>';
                            }
                            
                            // Empty stars
                            for($i = 0; $i < $empty_stars; $i++) {
                                echo '<i class="far fa-star"></i>';
                            }
                            ?>
                            <span>(<?php echo rand(10, 50); ?>)</span>
                        </div>
                        <div class="product-price">
                            <span class="current-price">LKR <?php echo number_format($product['price'], 2); ?></span>
                            <?php if($product['oldPrice'] > 0 && $product['oldPrice'] > $product['price']): ?>
                            <span class="old-price">LKR <?php echo number_format($product['oldPrice'], 2); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
    </section>

    <!-- Track Repair Status -->
    <section class="special-offer" style="background-image: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('https://images.unsplash.com/photo-1518770660439-4636190af475?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');">
        <div class="container">
            <div class="offer-content">
                <h2>Track Repair Status</h2>
                <p>Check the repair status of your device from anywhere</p>
                <a href="repairs.php" class="btn btn-outline">Track Repair</a>
            </div>
        </div>
    </section>
    
    <!-- Popular Products -->
    <section class="products-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Popular Products</h2>
                <a href="#" class="view-all">View All</a>
            </div>
            
            <div class="products-grid">
                <?php
                $popular_query = "SELECT p.*, COUNT(f.feedbackID) as review_count 
                                FROM Product p 
                                LEFT JOIN Feedback f ON p.productID = f.productID 
                                WHERE p.status = 'Active' 
                                GROUP BY p.productID 
                                ORDER BY p.rating DESC, review_count DESC 
                                LIMIT 4";
                $popular_result = mysqli_query($conn, $popular_query);
                
                while($product = mysqli_fetch_array($popular_result)) {
                    $badge_class = "";
                    $badge_text = "";
                    
                    // Determine badge type for popular products
                    if($product['rating'] >= 4.5) {
                        $badge_class = "product-badge";
                        $badge_text = "Popular";
                    } elseif($product['oldPrice'] > 0 && $product['oldPrice'] > $product['price']) {
                        $discount = (($product['oldPrice'] - $product['price']) / $product['oldPrice']) * 100;
                        $badge_class = "product-badge";
                        $badge_text = "-" . round($discount) . "%";
                    }
                ?>
                
                <div class="product-card">
                <?php if(!empty($badge_text)): ?>
                    <div class="<?php echo $badge_class; ?>"><?php echo $badge_text; ?></div>
                    <?php endif; ?>
                    
                    <div class="product-image">
                        <img src="assets/products/<?php echo $product['productImage'] ?: 'default.jpg'; ?>" alt="<?php echo $product['productName']; ?>">
                        <div class="product-actions">
                            <button class="action-btn quick-view-btn" data-product="<?php echo $product['productID']; ?>"><i class="fas fa-eye"></i></button>
                            <button class="action-btn cart-btn" data-product="<?php echo $product['productID']; ?>"><i class="fas fa-shopping-cart"></i></button>
                        </div>
                    </div>
                    <div class="product-info">
                        <h3 class="product-title"><?php echo $product['productName']; ?></h3>
                        <div class="product-rating">
                            <?php
                            $rating = $product['rating'];
                            $full_stars = floor($rating);
                            $half_star = ($rating - $full_stars) >= 0.5;
                            $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
                            
                            // Full stars
                            for($i = 0; $i < $full_stars; $i++) {
                                echo '<i class="fas fa-star"></i>';
                            }
                            
                            // Half star
                            if($half_star) {
                                echo '<i class="fas fa-star-half-alt"></i>';
                            }
                            
                            // Empty stars
                            for($i = 0; $i < $empty_stars; $i++) {
                                echo '<i class="far fa-star"></i>';
                            }
                            ?>
                            <span>(<?php echo rand(15, 80); ?>)</span>
                        </div>
                        <div class="product-price">
                            <span class="current-price">LKR <?php echo number_format($product['price'], 2); ?></span>
                            <?php if($product['oldPrice'] > 0 && $product['oldPrice'] > $product['price']): ?>
                            <span class="old-price">LKR <?php echo number_format($product['oldPrice'], 2); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
        <?php include 'includes/customer_footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
    <script src="includes/js/index.js"></script>

</body>
</html>