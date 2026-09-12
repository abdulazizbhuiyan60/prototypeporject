<?php
declare(strict_types=1);

session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/*
 * The browser sends only the product ID, selected size, and quantity.
 * Trusted product details and prices are kept on the server.
 */
$products = [
    'classic-tshirt' => [
        'name' => 'Classic T-Shirt',
        'price' => 24.99,
        'image' => 'img/img1.jpg',
        'description' => 'Cotton fabric 190+ GSM',
        'sizes' => ['M', 'L', 'XL'],
    ],
];

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function money(float $amount): string
{
    return '$' . number_format($amount, 2);
}

function redirectToCart(): void
{
    header('Location: cart.php');
    exit;
}

function setCartMessage(string $text, string $type = 'success'): void
{
    $_SESSION['cart_message'] = [
        'text' => $text,
        'type' => $type,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = isset($_POST['csrf_token'])
        ? (string) $_POST['csrf_token']
        : '';

    if (!hash_equals((string) $_SESSION['csrf_token'], $submittedToken)) {
        setCartMessage('Your session expired. Please try again.', 'error');
        redirectToCart();
    }

    $action = isset($_POST['action']) ? (string) $_POST['action'] : '';

    if ($action === 'add') {
        $productId = isset($_POST['product_id']) ? (string) $_POST['product_id'] : '';
        $size = isset($_POST['size']) ? strtoupper(trim((string) $_POST['size'])) : '';
        $quantity = filter_var(
            $_POST['quantity'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 99]]
        );

        if (!isset($products[$productId])) {
            setCartMessage('The selected product does not exist.', 'error');
            redirectToCart();
        }

        $product = $products[$productId];

        if (!in_array($size, $product['sizes'], true)) {
            setCartMessage('Please select a valid size.', 'error');
            redirectToCart();
        }

        if ($quantity === false) {
            setCartMessage('Please select a valid quantity.', 'error');
            redirectToCart();
        }

        $cartKey = $productId . ':' . $size;

        if (isset($_SESSION['cart'][$cartKey])) {
            $currentQuantity = (int) $_SESSION['cart'][$cartKey]['quantity'];
            $_SESSION['cart'][$cartKey]['quantity'] = min(99, $currentQuantity + $quantity);
        } else {
            $_SESSION['cart'][$cartKey] = [
                'id' => $productId,
                'name' => $product['name'],
                'price' => $product['price'],
                'image' => $product['image'],
                'description' => $product['description'],
                'size' => $size,
                'quantity' => $quantity,
            ];
        }

        setCartMessage($product['name'] . ' was added to your cart.');
        redirectToCart();
    }

    $cartKey = isset($_POST['cart_key']) ? (string) $_POST['cart_key'] : '';

    if (!isset($_SESSION['cart'][$cartKey])) {
        setCartMessage('That cart item could not be found.', 'error');
        redirectToCart();
    }

    if ($action === 'increase') {
        $_SESSION['cart'][$cartKey]['quantity'] = min(
            99,
            (int) $_SESSION['cart'][$cartKey]['quantity'] + 1
        );
    } elseif ($action === 'decrease') {
        $_SESSION['cart'][$cartKey]['quantity'] = max(
            1,
            (int) $_SESSION['cart'][$cartKey]['quantity'] - 1
        );
    } elseif ($action === 'remove') {
        unset($_SESSION['cart'][$cartKey]);
        setCartMessage('The item was removed from your cart.');
    } else {
        setCartMessage('Invalid cart action.', 'error');
    }

    redirectToCart();
}

$cartItems = $_SESSION['cart'];
$subtotal = 0.0;
$cartCount = 0;

foreach ($cartItems as $item) {
    $quantity = max(1, (int) ($item['quantity'] ?? 1));
    $subtotal += (float) ($item['price'] ?? 0) * $quantity;
    $cartCount += $quantity;
}

$serviceFee = $cartCount > 0 ? 5.00 : 0.00;
$total = $subtotal + $serviceFee;
$cartMessage = isset($_SESSION['cart_message']) && is_array($_SESSION['cart_message'])
    ? $_SESSION['cart_message']
    : null;
unset($_SESSION['cart_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StyleCart - Shopping Cart</title>
    <link rel="stylesheet" href="cart.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
</head>
<body>
    <header>
        <div class="logo">
            <span class="logo-icon">◆</span>
            StyleCart
        </div>

        <nav>
            <a href="index.php">Home</a>
            <a href="contactus.php">Contact Us</a>
        </nav>

        <div class="nav-icons">
            <span class="material-icons search-icon">search</span>
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search products...">
                <span class="material-icons close-search">close</span>
            </div>
            <span class="material-icons">favorite_border</span>
            <a href="cart.php" class="cart-icon" aria-label="Shopping cart">
                <span class="material-icons">shopping_cart</span>
                <span id="cart-count"><?php echo $cartCount; ?></span>
            </a>
            <span class="material-icons profile">person</span>
        </div>
    </header>

    <main class="cart-container">
        <h1>Shopping Cart</h1>

        <?php if ($cartMessage !== null): ?>
            <div class="cart-message <?php echo escape((string) ($cartMessage['type'] ?? 'success')); ?>" role="status">
                <?php echo escape((string) ($cartMessage['text'] ?? '')); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($cartItems)): ?>
            <div class="cart-content">
                <section class="cart-items">
                    <?php foreach ($cartItems as $cartKey => $item): ?>
                        <?php
                        $quantity = max(1, (int) ($item['quantity'] ?? 1));
                        $lineTotal = (float) ($item['price'] ?? 0) * $quantity;
                        ?>
                        <div class="cart-item">
                            <img
                                src="<?php echo escape((string) $item['image']); ?>"
                                alt="<?php echo escape((string) $item['name']); ?>"
                            >

                            <div class="item-info">
                                <h2><?php echo escape((string) $item['name']); ?></h2>
                                <p>Size: <?php echo escape((string) $item['size']); ?></p>
                                <p><?php echo escape((string) $item['description']); ?></p>
                            </div>

                            <form class="quantity" method="post" action="cart.php">
                                <input type="hidden" name="csrf_token" value="<?php echo escape((string) $_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="cart_key" value="<?php echo escape((string) $cartKey); ?>">
                                <button type="submit" name="action" value="decrease" aria-label="Decrease quantity">−</button>
                                <span class="quantity-value"><?php echo $quantity; ?></span>
                                <button type="submit" name="action" value="increase" aria-label="Increase quantity">+</button>
                            </form>

                            <div class="item-price">
                                <span class="price"><?php echo money($lineTotal); ?></span>
                            </div>

                            <form class="remove-form" method="post" action="cart.php">
                                <input type="hidden" name="csrf_token" value="<?php echo escape((string) $_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="cart_key" value="<?php echo escape((string) $cartKey); ?>">
                                <button type="submit" name="action" value="remove" class="remove" aria-label="Remove item">
                                    <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </section>

                <section class="cart-summary">
                    <h2>Order Summary</h2>

                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span id="subtotal"><?php echo money($subtotal); ?></span>
                    </div>

                    <div class="summary-row">
                        <span>Service Fee</span>
                        <span id="service-fee"><?php echo money($serviceFee); ?></span>
                    </div>

                    <hr>

                    <div class="summary-total">
                        <span>Total</span>
                        <span id="total"><?php echo money($total); ?></span>
                    </div>

                    <form method="post" action="buy-now.php" class="checkout-form">
                        <input type="hidden" name="csrf_token" value="<?php echo escape((string) $_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="action" value="checkout_cart">
                        <button type="submit" class="checkout-btn">Checkout</button>
                    </form>

                    <a href="product.php" class="continue-shopping">
                        ← Continue Shopping
                    </a>
                </section>
            </div>
        <?php else: ?>
            <div class="empty-cart is-visible" id="empty-cart">
                <i class="fa-solid fa-cart-shopping"></i>
                <h2>Your Cart Is Empty</h2>
                <p>Add a product to see it here.</p>
                <button type="button" onclick="continueShopping()">Continue Shopping</button>
            </div>
        <?php endif; ?>
    </main>

    <script src="cart.js"></script>
</body>
</html>