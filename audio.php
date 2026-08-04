<?php
session_start();
include('includes/dbconnection.php');

// Get filter parameters
$category_filter = isset($_GET['category']) ? $_GET['category'] : [];
$brand_filter = isset($_GET['brand']) ? $_GET['brand'] : [];
$com_type_filter = isset($_GET['Usetype']) ? $_GET['Usetype'] : [];
$min_price = isset($_GET['min_price']) ? floatval(str_replace(',', '', $_GET['min_price'])) : 0;
$max_price = isset($_GET['max_price']) ? floatval(str_replace(',', '', $_GET['max_price'])) : 1000000;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'featured';

// Get unique brands for filter
$brands_query = "SELECT DISTINCT brand FROM Product WHERE categoryID = 5 AND status = 'Active' ORDER BY brand";
$brands_result = mysqli_query($conn, $brands_query);

// Get unique audio type for filter
$com_type_query = "SELECT DISTINCT ps.attributeValue AS Usetype 
                   FROM Product_Specification ps 
                   JOIN Product p ON ps.productID = p.productID 
                   WHERE p.categoryID = 5 AND p.status = 'Active' AND ps.attributeName = 'useType' 
                   ORDER BY Usetype";
$com_type_result = mysqli_query($conn, $com_type_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tech Shark Computer Shop</title>
    <link rel="icon" type="image/png" href="assets/logo.png"/>
    <link rel="stylesheet" href="includes/css/customer.css">
    <link rel="stylesheet" href="includes/css/laptops.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <?php include 'includes/customer_header.php'; ?>

    <!-- Audio Section -->
    <section class="products-listing" data-category="audio" data-category-label="Audio Devices">
        <div class="container">
            <div class="listing-layout">
                <!-- Filters Sidebar -->
                <aside class="filters-sidebar">
                    <form method="GET" action="components.php" id="filter-form">
                        <div class="filter-group">
                            <h3>Filters</h3>
                            <button type="button" class="clear-filters">Clear All</button>
                        </div>

                        <!-- Price Filter -->
                        <div class="filter-group">
                            <h4>Price Range (LKR)</h4>
                            
                            <!-- Input Boxes -->
                            <div class="price-inputs">
                                <input type="text" name="min_price" id="min_price_input" placeholder="Min" class="price-input" value="<?php echo number_format($min_price); ?>">
                                <span>-</span>
                                <input type="text" name="max_price" id="max_price_input" placeholder="Max" class="price-input" value="<?php echo number_format($max_price); ?>">
                            </div>
                            
                            <!-- Sliders -->
                            <div class="price-slider">
                                <input type="range" id="range_min" min="0" max="1000000" value="<?php echo $min_price; ?>" class="range-min">
                                <input type="range" id="range_max" min="0" max="1000000" value="<?php echo $max_price; ?>" class="range-max">
                            </div>
                        </div>

                        <!-- Components Type Filter -->
                        <div class="filter-group">
                            <h4>Type</h4>
                            <div class="filter-options">
                                <?php while($com_type = mysqli_fetch_array($com_type_result)): ?>
                                    <?php if(!empty($com_type['Usetype'])): ?>
                                    <label class="filter-option">
                                        <input type="checkbox" name="Usetype[]" value="<?php echo $com_type['Usetype']; ?>" 
                                            <?php echo (in_array($com_type['Usetype'], (array)$com_type_filter)) ? 'checked' : ''; ?>>
                                        <span class="checkmark"></span>
                                        <?php echo $com_type['Usetype']; ?>
                                    </label>
                                    <?php endif; ?>
                                <?php endwhile; ?>
                            </div>
                        </div>



                        <!-- Brand Filter -->
                        <div class="filter-group">
                            <h4>Brand</h4>
                            <div class="filter-options">
                                <?php while($brand = mysqli_fetch_array($brands_result)): ?>
                                    <?php if(!empty($brand['brand'])): ?>
                                    <label class="filter-option">
                                        <input type="checkbox" name="brand[]" value="<?php echo $brand['brand']; ?>" 
                                            <?php echo (in_array($brand['brand'], (array)$brand_filter)) ? 'checked' : ''; ?>>
                                        <span class="checkmark"></span>
                                        <?php echo $brand['brand']; ?>
                                    </label>
                                    <?php endif; ?>
                                <?php endwhile; ?>
                            </div>
                        </div>


                        <input type="hidden" name="sort" id="sort-hidden" value="<?php echo $sort; ?>">
                        <button type="submit" style="display: none;" id="filter-submit">Apply Filters</button>
                    </form>
                </aside>

                <!-- Products Grid -->
                <main class="products-main">
                    <div class="products-header">
                        <div class="results-info">
                            <p>Showing <strong id="total-count-display">0</strong> Audio Devices</p>
                        </div>
                        <div class="sort-options">
                            <label for="sort">Sort by:</label>
                            <select id="sort" name="sort">
                                <option value="featured" <?php echo ($sort == 'featured') ? 'selected' : ''; ?>>Featured</option>
                                <option value="price-low" <?php echo ($sort == 'price-low') ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price-high" <?php echo ($sort == 'price-high') ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="name" <?php echo ($sort == 'name') ? 'selected' : ''; ?>>Name A-Z</option>
                                <option value="rating" <?php echo ($sort == 'rating') ? 'selected' : ''; ?>>Highest Rated</option>
                                <option value="newest" <?php echo ($sort == 'newest') ? 'selected' : ''; ?>>Newest First</option>
                            </select>
                        </div>
                    </div>

                    <div class="products-grid" id="products-container">
                        <div style="text-align:center; width:100%; padding:50px;">
                            <i class="fas fa-spinner fa-spin fa-2x"></i> Loading products...
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </section>

    <!-- Footer -->
        <?php include 'includes/customer_footer.php'; ?>

    <script src="includes/js/products.js"></script>
</body>
</html>