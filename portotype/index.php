<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <title>Bookhaven Catalog</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

    
    <header>
        <div class="logo">
            <span class="logo-icon">◆</span>
            Bookhaven
        </div>

        <nav>
            <a href="index.php" class="active">Home</a>
            <a href="contact.php">Contact Us</a>
            
        </nav>

        <div class="nav-icons">
         <span class="material-icons search-icon">search</span>
          <div class="search-box">
        <input type="text" id="searchInput" placeholder="Search books...">
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

              <!-- <img src="img/library2.jpg" alt="">-->     

                <div class="hero-content">
                    <h1>Discover Your Next Great Read</h1>

                    <p>
                        Explore thousands of titles and find your next
                        favorite book in our collection.
                    </p>

                    <button>Browse Catalog</button>
                </div>

            </div>


            
        </section>


        <!-- Category Menu -->
        <section class="categories">

            <a href="#" class="category-active">All</a>
            <a href="Fiction.html">Fiction</a>
            <a href="#">Non-Fiction</a>
            <a href="#">Children's</a>
            <a href="#">Reference</a>

        </section>


        <!-- Product Grid -->
        <section class="products">

            <!-- Product 1 -->
            <div class="card">

                <div class="product-image img1">
                    <a href="product.php">
                    <img src="img/img1.jpg" class="product-image" alt="">
                    <span class="favorite">♡</span>
                </div>

                <div class="product-info">
                    <h3>The Quiet Harbor</h3>

                    <div class="price-row">
                        <span>Available</span>
                        <span class="old-price">New Arrival</span>
                    </div>
                    </a>

                    <button>Borrow</button>
                </div>

            </div>


            <!-- Product 2 -->
            <div class="card">
                <a href="product.php">

                <div class="product-image img2">
                    <img src="img/img2.jpg" class="product-image" alt="">
                    <span class="favorite">♡</span>
                </div>

                <div class="product-info">
                    <h3>Fields of Thought</h3>

                    <div class="price-row">
                        <span>Checked Out</span>
                        <span class="old-price">Due Sep 12</span>
                    </div>
                    </a>

                    <button>Borrow</button>
                </div>

            </div>


            <!-- Product 3 -->
            <div class="card">
                <a href="product.php">

                <div class="product-image img3">
                    <img src="img/img3.jpg" class="product-image" alt="">
                    <span class="favorite">♡</span>
                </div>

                <div class="product-info">
                    <h3>Atlas of the Old World</h3>

                    <div class="price-row">
                        <span>Reference Only</span>
                        <span class="old-price">In-Library Use</span>
                    </div>
                    </a>

                    <button>Borrow</button>
                </div>

            </div>


            <!-- Product 4 -->
            <div class="card">
                <a href="product.php">

                <div class="product-image img4">
                    <img src="img/img4.jpg" class="product-image" alt="">
                    <span class="favorite">♡</span>
                </div>

                <div class="product-info">
                    <h3>Pip and the Paper Boat</h3>

                    <div class="price-row">
                        <span>Available</span>
                        <span class="old-price">New Arrival</span>
                    </div>
                    </a>

                    <button>Borrow</button>
                </div>

            </div>


            <!-- Product 5 -->
            <div class="card">
                <a href="product.php">

                <div class="product-image img5">
                    <img src="img/img1.jpg" class="product-image" alt="">
                    <span class="favorite">♡</span>
                </div>

                <div class="product-info">
                    <h3>The Last Lighthouse</h3>

                    <div class="price-row">
                        <span>Available</span>
                        <span class="old-price">New Arrival</span>
                    </div>
                    </a>

                    <button>Borrow</button>
                </div>

            </div>


            <!-- Product 6 -->
            <div class="card">
                <a href="product.php"></a>

                <div class="product-image img6">
                    <img src="img/img2.jpg" class="product-image" alt="">
                    <span class="favorite">♡</span>
                </div>

                <div class="product-info">
                    <h3>Silent Orbit</h3>

                    <div class="price-row">
                        <span>Checked Out</span>
                        <span class="old-price">Due Sep 20</span>
                    </div>
                    </a>

                    <button>Borrow</button>
                </div>

            </div>


            <!-- Product 7 -->
            <div class="card">
                <a href="product.php">

                <div class="product-image img7">
                    <img src="img/img3.jpg" class="product-image" alt="">
                    <span class="favorite">♡</span>
                </div>

                <div class="product-info">
                    <h3>Whispers in Ink</h3>

                    <div class="price-row">
                        <span>Available</span>
                        <span class="old-price">New Arrival</span>
                    </div>
                    </a>

                    <button>Borrow</button>
                </div>

            </div>


            <!-- Product 8 -->
            <div class="card">
                <a href="product.php"></a>

                <div class="product-image img8">
                    <img src="img/img4.jpg" class="product-image" alt="">
                    <span class="favorite">♡</span>
                </div>

                <div class="product-info">
                    <h3>The Cartographer's Dream</h3>

                    <div class="price-row">
                        <span>Checked Out</span>
                        <span class="old-price">Due Sep 18</span>
                    </div>
                    </a>

                    <button>Borrow</button>
                </div>

            </div>

        </section>

    </main>
        <footer>

        <div class="footer-top">

            <div class="footer-brand">
                <div class="logo">
                    <span class="logo-icon">◆</span>
                    Bookhaven
                </div>
                <p>Discover new worlds, one page at a time.</p>
            </div>

            <div class="footer-links">
                <h4>Browse</h4>
                <a href="#">Fiction</a>
                <a href="#">Non-Fiction</a>
                <a href="#">Children's</a>
                <a href="#">Reference</a>
            </div>

            <div class="footer-links">
                <h4>Help</h4>
                <a href="contact.php">Contact Us</a>
                <a href="#">Membership</a>
                <a href="#">Renewals</a>
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
            <p>&copy; 2026 Bookhaven. All rights reserved.</p>
        </div>

    </footer>
   <script src="main.js"></script>

</body>
</html>
