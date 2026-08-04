<?php
session_start();
include('includes/dbconnection.php');

$brand_filter = isset($_GET['brand']) ? (array)$_GET['brand'] : [];
$processor_filter = isset($_GET['processor']) ? (array)$_GET['processor'] : [];
$ram_filter = isset($_GET['ram']) ? (array)$_GET['ram'] : [];
$storage_filter = isset($_GET['storage']) ? (array)$_GET['storage'] : [];
$screen_filter = isset($_GET['screen']) ? (array)$_GET['screen'] : [];
$usage_filter = isset($_GET['usage']) ? (array)$_GET['usage'] : [];
$min_price = isset($_GET['min_price']) ? floatval(str_replace(',', '', $_GET['min_price'])) : 0;
$max_price = isset($_GET['max_price']) ? floatval(str_replace(',', '', $_GET['max_price'])) : 1000000;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'featured';


// Get unique brands for filter sidebar
$brands_query = "SELECT DISTINCT brand FROM Product WHERE categoryID = 1 AND status = 'Active' ORDER BY brand";
$brands_result = mysqli_query($conn, $brands_query);
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

    <!-- Laptops Section -->
    <section class="products-listing" data-category="laptops" data-category-label="Laptops">
        <div class="container">
            <div class="listing-layout">
                <!-- Filters Sidebar -->
                <aside class="filters-sidebar">
                    <form method="GET" action="laptops.php" id="filter-form">
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

                        <!-- Processor Filter -->
                        <div class="filter-group">
                            <h4>Processor</h4>
                            <div class="filter-options">
                                <label class="filter-option">
                                    <input type="checkbox" name="processor[]" value="i3" <?php echo (in_array('i3', (array)$processor_filter)) ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    Intel Core i3
                                </label>
                                <label class="filter-option">
                                    <input type="checkbox" name="processor[]" value="i5" <?php echo (in_array('i5', (array)$processor_filter)) ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    Intel Core i5
                                </label>
                                <label class="filter-option">
                                    <input type="checkbox" name="processor[]" value="i7" <?php echo (in_array('i7', (array)$processor_filter)) ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    Intel Core i7
                                </label>
                                <label class="filter-option">
                                    <input type="checkbox" name="processor[]" value="i9" <?php echo (in_array('i9', (array)$processor_filter)) ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    Intel Core i9
                                </label>
                                <label class="filter-option">
                                    <input type="checkbox" name="processor[]" value="ryzen3" <?php echo (in_array('ryzen3', (array)$processor_filter)) ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    AMD Ryzen 3
                                </label>
                                <label class="filter-option">
                                    <input type="checkbox" name="processor[]" value="ryzen5" <?php echo (in_array('ryzen5', (array)$processor_filter)) ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    AMD Ryzen 5
                                </label>
                                <label class="filter-option">
                                    <input type="checkbox" name="processor[]" value="ryzen7" <?php echo (in_array('ryzen7', (array)$processor_filter)) ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    AMD Ryzen 7
                                </label>
                                <label class="filter-option">
                                    <input type="checkbox" name="processor[]" value="ryzen9" <?php echo (in_array('ryzen9', (array)$processor_filter)) ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    AMD Ryzen 9
                                </label>
                                <label class="filter-option">
                                    <input type="checkbox" name="processor[]" value="applem1" <?php echo (in_array('applem1', (array)$processor_filter)) ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    Apple M1
                                </label>
                                <label class="filter-option">
                                    <input type="checkbox" name="processor[]" value="applem2" <?php echo (in_array('applem2', (array)$processor_filter)) ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    Apple M2
                                </label>
                                <label class="filter-option">
                                    <input type="checkbox" name="processor[]" value="applem3" <?php echo (in_array('applem3', (array)$processor_filter)) ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    Apple M3
                                </label>
                            </div>
                        </div>

                        <!-- RAM Filter -->
                        <div class="filter-group">
                            <h4>RAM</h4>
                            <div class="filter-options">
                                <label class="filter-option">
                                    <input type="checkbox" name="ram[]" value="8" <?php echo (in_array('8', (array)$ram_filter)) ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    8GB
                                </label>
                                <label class="filter-option">
                                    <input type="checkbox" name="ram[]" value="16" <?php echo (in_array('16', (array)$ram_filter)) ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    16GB
                                </label>
                                <label class="filter-option">
                                    <input type="checkbox" name="ram[]" value="32" <?php echo (in_array('32', (array)$ram_filter)) ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    32GB
                                </label>
                                <label class="filter-option">
                                    <input type="checkbox" name="ram[]" value="64" <?php echo (in_array('64', (array)$ram_filter)) ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    64GB
                                </label>
                            </div>
                        </div>

                        <!-- Storage Filter -->
                        <div class="filter-group">
                            <h4>Storage</h4>
                            <div class="filter-options">
                                <label class="filter-option">
                                    <input type="checkbox" name="storage[]" value="256" <?php echo (in_array('256', (array)$storage_filter)) ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    256GB SSD
                                </label>
                                <label class="filter-option">
                                    <input type="checkbox" name="storage[]" value="512" <?php echo (in_array('512', (array)$storage_filter)) ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    512GB SSD
                                </label>
                                <label class="filter-option">
                                    <input type="checkbox" name="storage[]" value="1tb" <?php echo (in_array('1tb', (array)$storage_filter)) ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    1TB SSD
                                </label>
                                <label class="filter-option">
                                    <input type="checkbox" name="storage[]" value="2tb" <?php echo (in_array('2tb', (array)$storage_filter)) ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    2TB SSD
                                </label>
                            </div>
                        </div>

                        <!-- Screen Size Filter -->
                        <div class="filter-group">
                            <h4>Screen Size</h4>
                            <div class="filter-options">
                                <label class="filter-option">
                                    <input type="checkbox" name="screen[]" value="13" <?php echo (in_array('13', (array)$screen_filter)) ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    13" - 14"
                                </label>
                                <label class="filter-option">
                                    <input type="checkbox" name="screen[]" value="15" <?php echo (in_array('15', (array)$screen_filter)) ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    15" - 16"
                                </label>
                                <label class="filter-option">
                                    <input type="checkbox" name="screen[]" value="17" <?php echo (in_array('17', (array)$screen_filter)) ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    17" and above
                                </label>
                            </div>
                        </div>

                        <!-- Usage Type Filter -->
                        <div class="filter-group">
                            <h4>Usage Type</h4>
                            <div class="filter-options">
                                <label class="filter-option">
                                    <input type="checkbox" name="usage[]" value="professional" <?php echo (in_array('professional', (array)$usage_filter)) ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    Professional
                                </label>
                                <label class="filter-option">
                                    <input type="checkbox" name="usage[]" value="gaming" <?php echo (in_array('gaming', (array)$usage_filter)) ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    Gaming
                                </label>
                                <label class="filter-option">
                                    <input type="checkbox" name="usage[]" value="convertible" <?php echo (in_array('convertible', (array)$usage_filter)) ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    Convertible
                                </label>
                                <label class="filter-option">
                                    <input type="checkbox" name="usage[]" value="student" <?php echo (in_array('student', (array)$usage_filter)) ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    Student
                                </label>
                                <label class="filter-option">
                                    <input type="checkbox" name="usage[]" value="creative" <?php echo (in_array('creative', (array)$usage_filter)) ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    Creative Work
                                </label>
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
                            <p>Showing <strong id="total-count-display">0</strong> Laptops</p>
                        </div>
                        <div class="sort-options">
                            <label for="sort">Sort by:</label>
                            <select id="sort" name="sort">
                                <option value="featured">Featured</option>
                                <option value="price-low">Price: Low to High</option>
                                <option value="price-high">Price: High to Low</option>
                                <option value="name">Name A-Z</option>
                                <option value="rating">Highest Rated</option>
                                <option value="newest">Newest First</option>
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