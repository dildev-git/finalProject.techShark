<?php
session_start();
include('includes/dbconnection.php');

// Check if it's an AJAX request
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    
    // Get filter parameters
    $brand_filter      = isset($_GET['brand'])       ? (array)$_GET['brand']       : [];
    $com_type_filter   = isset($_GET['Usetype'])     ? (array)$_GET['Usetype']     : [];
    $storage_filter    = isset($_GET['storage'])     ? (array)$_GET['storage']     : [];
    $screen_filter     = isset($_GET['screen'])      ? (array)$_GET['screen']      : [];
    $processor_filter  = isset($_GET['processor'])   ? (array)$_GET['processor']   : [];
    $ram_filter        = isset($_GET['ram'])          ? (array)$_GET['ram']         : [];
    $gpu_filter        = isset($_GET['gpu'])          ? (array)$_GET['gpu']         : [];
    $usage_filter      = isset($_GET['usage'])        ? (array)$_GET['usage']       : [];
    $price_range       = isset($_GET['price_range'])  ? $_GET['price_range']        : 'all';
    $min_price = isset($_GET['min_price']) ? floatval(str_replace(',', '', $_GET['min_price'])) : 0;
    $max_price = isset($_GET['max_price']) ? floatval(str_replace(',', '', $_GET['max_price'])) : 1000000;
    $sort              = isset($_GET['sort'])          ? $_GET['sort']               : 'featured';
    
    // Determine category from referrer or param
    if (isset($_GET['category'])) {
        switch($_GET['category']) {
            case 'laptops': $categoryID = 1; break;
            case 'desktops': $categoryID = 2; break; 
            case 'components': $categoryID = 3; break;
            case 'accessories': $categoryID = 4; break;
            case 'audio': $categoryID = 5; break;
            case 'storage': $categoryID = 6; break;
            default: $categoryID = 1;
        }
    } else {
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        if (strpos($referrer, 'desktops.php') !== false) {
            $categoryID = 2;
        } elseif (strpos($referrer, 'components.php') !== false) {
            $categoryID = 3;
        } elseif (strpos($referrer, 'accessories.php') !== false) {
            $categoryID = 4;
        } elseif (strpos($referrer, 'audio.php') !== false) {
            $categoryID = 5;
        } elseif (strpos($referrer, 'storage.php') !== false) {
            $categoryID = 6;
        } else {
            $categoryID = 1; // Default: Laptops
        }
    }

    // ==============================================================
    // 3NF JOIN: pivot Product_Specification into flat spec columns
    // Filters on spec columns must go in HAVING, not WHERE
    // ==============================================================
    $query = "SELECT p.*,
        MAX(CASE WHEN ps.attributeName = 'processor' THEN ps.attributeValue END) AS processor,
        MAX(CASE WHEN ps.attributeName = 'ram'       THEN ps.attributeValue END) AS ram,
        MAX(CASE WHEN ps.attributeName = 'storage'   THEN ps.attributeValue END) AS storage,
        MAX(CASE WHEN ps.attributeName = 'scrSiz'    THEN ps.attributeValue END) AS scrSiz,
        MAX(CASE WHEN ps.attributeName = 'grpCard'   THEN ps.attributeValue END) AS grpCard,
        MAX(CASE WHEN ps.attributeName = 'useType'   THEN ps.attributeValue END) AS useType
    FROM Product p
    LEFT JOIN Product_Specification ps ON p.productID = ps.productID
    WHERE p.categoryID = $categoryID AND p.status = 'Active'";

    // Price filter
    $query .= " AND p.price BETWEEN $min_price AND $max_price";

    // Brand filter (safe in WHERE — is a Product column)
    if (!empty($brand_filter)) {
        $brand_conditions = [];
        foreach ($brand_filter as $brand) {
            $brand_conditions[] = "p.brand = '" . mysqli_real_escape_string($conn, $brand) . "'";
        }
        $query .= " AND (" . implode(' OR ', $brand_conditions) . ")";
    }

    // GROUP BY — required for MAX() pivot aggregation
    $query .= " GROUP BY p.productID";

    // ==============================================================
    // Spec-based filters -> HAVING (post-aggregation)
    // ==============================================================    // Filter conditions array for HAVING
    $having_conditions = [];

    // Component/Accessory/Audio/Storage useType filter (e.g. CPU, Mouse, Headphones, Internal SSD)
    if (!empty($com_type_filter)) {
        $ct_conds = [];
        foreach ($com_type_filter as $ct) {
            $ct_conds[] = "MAX(CASE WHEN ps.attributeName='useType' THEN ps.attributeValue END) = '" . mysqli_real_escape_string($conn, $ct) . "'";
        }
        $having_conditions[] = "(" . implode(' OR ', $ct_conds) . ")";
    }

    // Storage capacity filter
    if (!empty($storage_filter)) {
        $storage_conds = [];
        foreach ($storage_filter as $cap) {
            $storage_conds[] = "MAX(CASE WHEN ps.attributeName='storage' THEN ps.attributeValue END) LIKE '%" . mysqli_real_escape_string($conn, $cap) . "%'";
        }
        $having_conditions[] = "(" . implode(' OR ', $storage_conds) . ")";
    }

    // Screen Size filter
    if (!empty($screen_filter)) {
        $screen_conds = [];
        foreach ($screen_filter as $screen) {
            switch ($screen) {
                case '13': 
                    $screen_conds[] = "(MAX(CASE WHEN ps.attributeName='scrSiz' THEN ps.attributeValue END) LIKE '%13%' OR MAX(CASE WHEN ps.attributeName='scrSiz' THEN ps.attributeValue END) LIKE '%14%')"; 
                    break;
                case '15': 
                    $screen_conds[] = "(MAX(CASE WHEN ps.attributeName='scrSiz' THEN ps.attributeValue END) LIKE '%15%' OR MAX(CASE WHEN ps.attributeName='scrSiz' THEN ps.attributeValue END) LIKE '%16%')"; 
                    break;
                case '17': 
                    $screen_conds[] = "(MAX(CASE WHEN ps.attributeName='scrSiz' THEN ps.attributeValue END) LIKE '%17%' OR MAX(CASE WHEN ps.attributeName='scrSiz' THEN ps.attributeValue END) LIKE '%18%')"; 
                    break;
            }
        }
        if (!empty($screen_conds)) {
            $having_conditions[] = "(" . implode(' OR ', $screen_conds) . ")";
        }
    }

    // Processor filter (laptops & desktops)
    if (($categoryID == 1 || $categoryID == 2) && !empty($processor_filter)) {
        $proc_conds = [];
        foreach ($processor_filter as $proc) {
            switch ($proc) {
                case 'i3':     $proc_conds[] = "MAX(CASE WHEN ps.attributeName='processor' THEN ps.attributeValue END) LIKE '%i3%'"; break;
                case 'i5':     $proc_conds[] = "MAX(CASE WHEN ps.attributeName='processor' THEN ps.attributeValue END) LIKE '%i5%'"; break;
                case 'i7':     $proc_conds[] = "MAX(CASE WHEN ps.attributeName='processor' THEN ps.attributeValue END) LIKE '%i7%'"; break;
                case 'i9':     $proc_conds[] = "MAX(CASE WHEN ps.attributeName='processor' THEN ps.attributeValue END) LIKE '%i9%'"; break;
                case 'ryzen3': $proc_conds[] = "MAX(CASE WHEN ps.attributeName='processor' THEN ps.attributeValue END) LIKE '%Ryzen 3%'"; break;
                case 'ryzen5': $proc_conds[] = "MAX(CASE WHEN ps.attributeName='processor' THEN ps.attributeValue END) LIKE '%Ryzen 5%'"; break;
                case 'ryzen7': $proc_conds[] = "MAX(CASE WHEN ps.attributeName='processor' THEN ps.attributeValue END) LIKE '%Ryzen 7%'"; break;
                case 'ryzen9': $proc_conds[] = "MAX(CASE WHEN ps.attributeName='processor' THEN ps.attributeValue END) LIKE '%Ryzen 9%'"; break;
                case 'applem1': $proc_conds[] = "MAX(CASE WHEN ps.attributeName='processor' THEN ps.attributeValue END) LIKE '%Apple M1%'"; break;
                case 'applem2': $proc_conds[] = "MAX(CASE WHEN ps.attributeName='processor' THEN ps.attributeValue END) LIKE '%Apple M2%'"; break;
                case 'applem3': $proc_conds[] = "MAX(CASE WHEN ps.attributeName='processor' THEN ps.attributeValue END) LIKE '%Apple M3%'"; break;
            }
        }
        if (!empty($proc_conds)) {
            $having_conditions[] = "(" . implode(' OR ', $proc_conds) . ")";
        }
    }

    // RAM filter
    if (!empty($ram_filter)) {
        $ram_conds = [];
        foreach ($ram_filter as $ram) {
            $ram_conds[] = "MAX(CASE WHEN ps.attributeName='ram' THEN ps.attributeValue END) LIKE '%" . mysqli_real_escape_string($conn, $ram) . "%'";
        }
        $having_conditions[] = "(" . implode(' OR ', $ram_conds) . ")";
    }

    // GPU filter (desktops)
    if ($categoryID == 2 && !empty($gpu_filter)) {
        $gpu_conds = [];
        foreach ($gpu_filter as $gpu) {
            switch ($gpu) {
                case 'integrated': $gpu_conds[] = "MAX(CASE WHEN ps.attributeName='grpCard' THEN ps.attributeValue END) LIKE '%Intel%'"; break;
                case 'gtx1650':    $gpu_conds[] = "MAX(CASE WHEN ps.attributeName='grpCard' THEN ps.attributeValue END) LIKE '%1650%'"; break;
                case 'rtx3050':    $gpu_conds[] = "MAX(CASE WHEN ps.attributeName='grpCard' THEN ps.attributeValue END) LIKE '%3050%'"; break;
                case 'rtx4060':    $gpu_conds[] = "MAX(CASE WHEN ps.attributeName='grpCard' THEN ps.attributeValue END) LIKE '%4060%'"; break;
                case 'rtx4070':    $gpu_conds[] = "MAX(CASE WHEN ps.attributeName='grpCard' THEN ps.attributeValue END) LIKE '%4070%'"; break;
                case 'rtx4080':    $gpu_conds[] = "MAX(CASE WHEN ps.attributeName='grpCard' THEN ps.attributeValue END) LIKE '%4080%'"; break;
                case 'applem1':    $gpu_conds[] = "MAX(CASE WHEN ps.attributeName='grpCard' THEN ps.attributeValue END) LIKE '%Apple M1%'"; break;
                case 'applem2':    $gpu_conds[] = "MAX(CASE WHEN ps.attributeName='grpCard' THEN ps.attributeValue END) LIKE '%Apple M2%'"; break;
                case 'applem3':    $gpu_conds[] = "MAX(CASE WHEN ps.attributeName='grpCard' THEN ps.attributeValue END) LIKE '%Apple M3%'"; break;
            }
        }
        if (!empty($gpu_conds)) {
            $having_conditions[] = "(" . implode(' OR ', $gpu_conds) . ")";
        }
    }

    // Usage type filter
    if (!empty($usage_filter)) {
        $usage_conds = [];
        foreach ($usage_filter as $usage) {
            $usage_conds[] = "MAX(CASE WHEN ps.attributeName='useType' THEN ps.attributeValue END) LIKE '%" . mysqli_real_escape_string($conn, $usage) . "%'";
        }
        $having_conditions[] = "(" . implode(' OR ', $usage_conds) . ")";
    }

    if (!empty($having_conditions)) {
        $query .= " HAVING " . implode(' AND ', $having_conditions);
    }

    // Sorting
    switch ($sort) {
        case 'price-low':   $query .= " ORDER BY p.price ASC"; break;
        case 'price-high':  $query .= " ORDER BY p.price DESC"; break;
        case 'name':        $query .= " ORDER BY p.productName ASC"; break;
        case 'rating':      $query .= " ORDER BY p.rating DESC"; break;
        case 'newest':      $query .= " ORDER BY p.addedDate DESC"; break;
        default:            $query .= " ORDER BY p.rating DESC, p.addedDate DESC"; break;
    }
    
    $result = mysqli_query($conn, $query);
    $total_products = mysqli_num_rows($result);
    
    // ====================================================================
    // Generate HTML
    // ====================================================================
    $html = '';
    if(mysqli_num_rows($result) > 0) {
        while($product = mysqli_fetch_assoc($result)) {
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
            } elseif($product['rating'] >= 4.5) {
                $badge_class = "product-badge";
                $badge_text = "Popular";
            }
            
            $currency = 'LKR';
            
            $html .= '
            <div class="product-card">
                '.(!empty($badge_text) ? '<div class="'.$badge_class.'">'.$badge_text.'</div>' : '').'
                <div class="product-image">
                    <img src="assets/products/'.($product['productImage'] ?: 'default.jpg').'" alt="'.htmlspecialchars($product['productName']).'">
                    <div class="product-actions">
                        <button class="action-btn quick-view-btn" data-product="'.$product['productID'].'"><i class="fas fa-eye"></i></button>
                        <button class="action-btn cart-btn" data-product="'.$product['productID'].'"><i class="fas fa-shopping-cart"></i></button>
                    </div>
                </div>
                <div class="product-info">
                    <h3 class="product-title">'.htmlspecialchars($product['productName']).'</h3>'; // close the HTML string first here.
            
            $html .= '<div class="product-specs">';
            
            // Showing special things related to Storage
            if($categoryID == 6) {
                if(!empty($product['brand'])) {
                    $html .= '<span style="margin-right: 5px;">'.htmlspecialchars($product['brand']).'</span>';
                }
                if(!empty($product['storage'])) {
                    $html .= '<span style="margin-right: 5px;">'.htmlspecialchars($product['storage']).'</span>'; 
                }
                if(!empty($product['description'])) {
                    $html .= '<span style="margin-right: 5px;">'.htmlspecialchars($product['description']).'</span>';
                }
            } 
            // Showing special things related Components, Accessories, Audio
            elseif(in_array($categoryID, [3, 4, 5])) {
                if(!empty($product['brand'])) {
                    $html .= '<span style="margin-right: 5px;">'.htmlspecialchars($product['brand']).'</span>';
                }
                if(!empty($product['description'])) {
                    $html .= '<span style="margin-right: 5px;">'.htmlspecialchars($product['description']).'</span>';
                }
            }
            // Showing special things related Laptops, Desktops
            else {
                if(!empty($product['processor'])) {
                    $html .= '<span style="margin-right: 5px;">'.htmlspecialchars($product['processor']).'</span>';
                }
                if(!empty($product['ram'])) {
                    $html .= '<span style="margin-right: 5px;">'.htmlspecialchars($product['ram']).' RAM</span>';
                }
                if(!empty($product['storage'])) {
                    $html .= '<span style="margin-right: 5px;">'.htmlspecialchars($product['storage']).' Storage</span>';
                }
                if(!empty($product['scrSiz'])) {
                    $html .= '<span style="margin-right: 5px;">'.htmlspecialchars($product['scrSiz']).'" Display</span>';
                }
                if(!empty($product['grpCard'])) {
                    $html .= '<span style="margin-right: 5px;">'.htmlspecialchars($product['grpCard']).'</span>';
                }
            }
            
            // Start again the HTML string here.
            $html .= '</div>
                    <div class="product-rating">
                        <div class="stars">';
            
            $rating = $product['rating'];
            $full_stars = floor($rating);
            $half_star = ($rating - $full_stars) >= 0.5;
            $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
            
            for($i = 0; $i < $full_stars; $i++) {
                $html .= '<i class="fas fa-star"></i>';
            }
            if($half_star) {
                $html .= '<i class="fas fa-star-half-alt"></i>';
            }
            for($i = 0; $i < $empty_stars; $i++) {
                $html .= '<i class="far fa-star"></i>';
            }
            
            $html .= '</div>
                        <span class="rating-count">('.rand(10, 50).')</span>
                    </div>
                    <div class="product-price">
                        <span class="current-price">'.$currency.' '.number_format($product['price'], 2).'</span>';
            
            if($product['oldPrice'] > 0 && $product['oldPrice'] > $product['price']) {
                $html .= '<span class="old-price">'.$currency.' '.number_format($product['oldPrice'], 2).'</span>';
            }
            
            $html .= '</div>
                    </div>
            </div>';
        }
    } else {
        $html = '
        <div class="no-products">
            <h3>No products found matching your criteria</h3>
            <p>Try adjusting your filters or search terms.</p>
        </div>';
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'html' => $html,
        'total' => $total_products
    ]);
    
} else {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Direct access not allowed']);
}
?>