<?php
declare(strict_types=1);

session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <title>StyleCart Dashboard</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

    
    <header>
        <div class="logo">
            <span class="logo-icon">◆</span>
            StyleCart
        </div>

        <nav>
            <a href="index.php" class="active">Home</a>
            <a href="contactus.php">Contact Us</a>
            
        </nav>

        <div class="nav-icons">
          <span class="material-icons search-icon">search</span>
          <div class="search-box">
        <input type="text" id="searchInput" placeholder="Search products...">
        <span class="material-icons close-search">close</span>
    </div>
        <span class="material-icons">favorite_border</span>
    <span class="material-icons"><a href="cart.php">shopping_cart</a></span>
    <span class="material-icons profile">person</span>
            
        </div>
    </header>


    <main>

        
        <section class="hero">

            <div class="hero-text">

                <div class="dots"></div>

                <div class="hero-content">
                    <h1>End of Season Sale</h1>

                    <p>
                        Discover amazing deals and refresh your wardrobe
                        with our latest collection.
                    </p>

                    <button>Shop Now</button>
                </div>

            </div>


            
        </section>


        <section class="product-detail">

    <div class="breadcrumb">
        <a href="index.php">Home</a> <span>/</span> <span class="current">Classic T-Shirt</span>
    </div>

    <div class="product-detail-grid">

        <!-- Left: Gallery -->
        <div class="product-gallery">

            <div class="main-image">
                <img src="img/img1.jpg" alt="Product" id="mainImage">
            </div>

            <div class="thumbnail-row">
                <img src="img/img1.jpg" class="thumb active" onclick="changeImage(this)">
                <img src="img/img2.jpg" class="thumb" onclick="changeImage(this)">
                <img src="img/img3.jpg" class="thumb" onclick="changeImage(this)">
                <img src="img/img4.jpg" class="thumb" onclick="changeImage(this)">
            </div>

        </div>

        <!-- Right: Info -->
        <div class="product-detail-info">

            <h1>Classic T-Shirt</h1>

            <p class="detail-price">$24.99</p>

            <p class="size-guide">Size guide <span>📏</span></p>

            <div class="fabric-info">
                <p><strong>M</strong> : Length - 27 &nbsp; Width - 42</p>
                <p><strong>L</strong> : Length - 28 &nbsp; Width - 44</p>
                <p><strong>XL</strong> : Length - 29 &nbsp; Width - 46</p>
                <p class="fabric-note">Cotton fabric 190+ GSM</p>
            </div>

            <div class="size-selector">
                <span class="size-label">Size: <strong id="selectedSize">M</strong></span>

                <div class="size-options">
                    <button type="button" class="size-btn active" onclick="selectSize(this,'M')">M</button>
                    <button type="button" class="size-btn" onclick="selectSize(this,'L')">L</button>
                    <button type="button" class="size-btn" onclick="selectSize(this,'XL')">XL</button>
                </div>
            </div>

            <div class="helper-row">
                <span>&#9432; Have questions?</span>
                <span>&#8599; Share</span>
            </div>

            <div class="divider"></div>

            <form class="cart-row" method="post" action="cart.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="classic-tshirt">
                <input type="hidden" name="size" id="cartSize" value="M">
                <input type="hidden" name="quantity" id="cartQuantity" value="1">

                <div class="qty-control">
                    <button type="button" onclick="changeQty(-1)">-</button>
                    <span id="qtyValue">1</span>
                    <button type="button" onclick="changeQty(1)">+</button>
                </div>

                <button type="submit" class="add-cart-btn">Add to cart</button>

            </form>

            <form class="buy-now-form" method="post" action="buy-now.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="start_checkout">
                <input type="hidden" name="product_id" value="classic-tshirt">
                <input type="hidden" name="size" id="buyNowSize" value="M">
                <input type="hidden" name="quantity" id="buyNowQuantity" value="1">
                <button type="submit" class="buy-now-btn">Buy now</button>
            </form>

            <div class="accordion">
                <div class="accordion-item">
                    <button class="accordion-header">
                        Shipping and exchange policies <span>⌄</span>
                    </button>
                    <div class="accordion-body">
                        <p>Orders are shipped within 2-3 business days. Exchanges accepted within 7 days of delivery for unused items with tags attached.</p>
                    </div>
                </div>

                <div class="accordion-item">
                    <button class="accordion-header">
                        Return policies <span>⌄</span>
                    </button>
                    <div class="accordion-body">
                        <p>Returns accepted within 7 days for a full refund, provided the item is unworn and in original packaging.</p>
                    </div>
                </div>
            </div>

        </div>

    </div>

</section>

    </main>
        <footer>

        <div class="footer-top">

            <div class="footer-brand">
                <div class="logo">
                    <span class="logo-icon">◆</span>
                    StyleCart
                </div>
                <p>Refresh your wardrobe with styles made for everyday life.</p>
            </div>

            <div class="footer-links">
                <h4>Shop</h4>
                <a href="#">Men</a>
                <a href="#">Women</a>
                <a href="#">Accessories</a>
                <a href="#">Shoes</a>
            </div>

            <div class="footer-links">
                <h4>Help</h4>
                <a href="#">Contact Us</a>
                <a href="#">Shipping</a>
                <a href="#">Returns</a>
                <a href="#">FAQ</a>
            </div>

            <div class="footer-social">
                <h4>Follow Us</h4>
                <div class="social-icons">
                                     
        <span class="material-icons"><i class="fa-brands fa-facebook"></i></span>
       <span class="material-icons"><i class="fa-brands fa-instagram"></i></span>
    
                </div>
            </div>

        </div>

        <div class="footer-bottom">
            <p>&copy; 2026 StyleCart. All rights reserved.</p>
        </div>

    </footer>
   <script src="cart.js"></script>

</body>
</html>