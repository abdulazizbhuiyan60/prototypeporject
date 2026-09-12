<?php
declare(strict_types=1);

session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/*
 * XAMPP database settings.
 */
$dbHost = 'localhost';
$dbName = 'bookhaven';
$dbUser = 'root';
$dbPass = '';

$pdo = null;
$databaseError = '';

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $exception) {
    error_log('Bookhaven database error: ' . $exception->getMessage());

    $databaseError =
        'Cannot connect to the database. Start MySQL in XAMPP.';
}

/*
 * Create the orders table automatically if it does not exist.
 */
if ($pdo instanceof PDO) {
    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS orders (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                order_number VARCHAR(32) NOT NULL,
                user_id INT UNSIGNED NULL,

                customer_name VARCHAR(100) NOT NULL,
                customer_email VARCHAR(255) NOT NULL,
                customer_phone VARCHAR(20) NOT NULL,
                delivery_address VARCHAR(200) NOT NULL,
                city VARCHAR(80) NOT NULL,
                postal_code VARCHAR(12) NOT NULL,

                payment_method VARCHAR(40) NOT NULL,
                checkout_source VARCHAR(20) NOT NULL,
                order_items LONGTEXT NOT NULL,
                item_count INT UNSIGNED NOT NULL,

                subtotal DECIMAL(10,2) NOT NULL,
                delivery_fee DECIMAL(10,2) NOT NULL,
                total_amount DECIMAL(10,2) NOT NULL,

                order_status VARCHAR(30) NOT NULL DEFAULT 'pending',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

                PRIMARY KEY (id),
                UNIQUE KEY unique_order_number (order_number),
                KEY index_customer_email (customer_email),
                KEY index_order_date (created_at)
            ) ENGINE=InnoDB
              DEFAULT CHARACTER SET utf8mb4
              COLLATE utf8mb4_unicode_ci"
        );

        /*
         * Repair an older orders table that does not contain order_items.
         */
        $orderItemsColumn = $pdo->query(
            "SHOW COLUMNS FROM orders LIKE 'order_items'"
        )->fetch();

        if (!$orderItemsColumn) {
            $pdo->exec(
                'ALTER TABLE orders
                 ADD COLUMN order_items LONGTEXT NULL'
            );
        }
    } catch (PDOException $exception) {
        error_log(
            'Bookhaven orders table error: ' .
            $exception->getMessage()
        );

        $databaseError = 'The orders table could not be prepared.';

        $serverName = strtolower(
            (string) ($_SERVER['SERVER_NAME'] ?? '')
        );

        if (
            in_array(
                $serverName,
                ['localhost', '127.0.0.1', '::1'],
                true
            )
        ) {
            $databaseError .=
                ' MySQL error: ' . $exception->getMessage();
        }
    }
}

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
    return htmlspecialchars(
        $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function textLength(string $value): int
{
    return function_exists('mb_strlen')
        ? mb_strlen($value)
        : strlen($value);
}

function money(float $amount): string
{
    return '$' . number_format($amount, 2);
}

function checkoutPageUrl(): string
{
    $scriptName = basename(
        (string) ($_SERVER['SCRIPT_NAME'] ?? 'buy-now.php')
    );

    return preg_match(
        '/^[A-Za-z0-9._()-]+\.php$/',
        $scriptName
    )
        ? $scriptName
        : 'buy-now.php';
}

function redirectToCheckout(): void
{
    header('Location: ' . checkoutPageUrl());
    exit;
}

function buildCheckoutLines(
    ?array $checkout,
    array $products
): array {
    if (
        $checkout === null ||
        !isset($checkout['items']) ||
        !is_array($checkout['items']) ||
        $checkout['items'] === []
    ) {
        return [];
    }

    $lines = [];

    foreach ($checkout['items'] as $item) {
        if (!is_array($item)) {
            return [];
        }

        $productId = isset($item['product_id'])
            ? (string) $item['product_id']
            : '';

        $size = isset($item['size'])
            ? strtoupper((string) $item['size'])
            : '';

        $quantity = isset($item['quantity'])
            ? (int) $item['quantity']
            : 0;

        if (
            !isset($products[$productId]) ||
            !in_array(
                $size,
                $products[$productId]['sizes'],
                true
            ) ||
            $quantity < 1 ||
            $quantity > 99
        ) {
            return [];
        }

        $product = $products[$productId];
        $lineTotal =
            (float) $product['price'] * $quantity;

        $lines[] = [
            'product_id' => $productId,
            'name' => $product['name'],
            'price' => (float) $product['price'],
            'image' => $product['image'],
            'description' => $product['description'],
            'size' => $size,
            'quantity' => $quantity,
            'line_total' => $lineTotal,
        ];
    }

    return $lines;
}

$errors = [];

$checkout = isset($_SESSION['buy_now_checkout'])
    && is_array($_SESSION['buy_now_checkout'])
        ? $_SESSION['buy_now_checkout']
        : null;

$confirmation = isset($_SESSION['order_confirmation'])
    && is_array($_SESSION['order_confirmation'])
        ? $_SESSION['order_confirmation']
        : null;

unset($_SESSION['order_confirmation']);

$old = [
    'full_name' => isset($_SESSION['user_name'])
        ? (string) $_SESSION['user_name']
        : '',
    'email' => isset($_SESSION['user_email'])
        ? (string) $_SESSION['user_email']
        : '',
    'phone' => '',
    'address' => '',
    'city' => '',
    'postal_code' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action'])
        ? (string) $_POST['action']
        : '';

    $submittedToken = isset($_POST['csrf_token'])
        ? (string) $_POST['csrf_token']
        : '';

    if (
        !hash_equals(
            (string) $_SESSION['csrf_token'],
            $submittedToken
        )
    ) {
        $errors['general'] =
            'Your session expired. Please try again.';
    } elseif ($action === 'start_checkout') {
        unset($_SESSION['buy_now_checkout']);
        $checkout = null;

        $productId = isset($_POST['product_id'])
            ? (string) $_POST['product_id']
            : '';

        $size = isset($_POST['size'])
            ? strtoupper(trim((string) $_POST['size']))
            : '';

        $quantity = filter_var(
            $_POST['quantity'] ?? null,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1,
                    'max_range' => 99,
                ],
            ]
        );

        if (!isset($products[$productId])) {
            $errors['general'] =
                'The selected product does not exist.';
        } elseif (
            !in_array(
                $size,
                $products[$productId]['sizes'],
                true
            )
        ) {
            $errors['general'] =
                'Please select a valid product size.';
        } elseif ($quantity === false) {
            $errors['general'] =
                'Please select a valid quantity.';
        } else {
            $_SESSION['buy_now_checkout'] = [
                'source' => 'product',
                'items' => [
                    [
                        'product_id' => $productId,
                        'size' => $size,
                        'quantity' => $quantity,
                    ],
                ],
            ];

            redirectToCheckout();
        }
    } elseif ($action === 'checkout_cart') {
        unset($_SESSION['buy_now_checkout']);
        $checkout = null;

        $sessionCart = isset($_SESSION['cart'])
            && is_array($_SESSION['cart'])
                ? $_SESSION['cart']
                : [];

        if ($sessionCart === []) {
            $errors['general'] =
                'Your cart is empty.';
        } else {
            $items = [];

            foreach ($sessionCart as $cartItem) {
                if (!is_array($cartItem)) {
                    $items = [];
                    break;
                }

                $items[] = [
                    'product_id' => isset($cartItem['id'])
                        ? (string) $cartItem['id']
                        : '',
                    'size' => isset($cartItem['size'])
                        ? (string) $cartItem['size']
                        : '',
                    'quantity' =>
                        isset($cartItem['quantity'])
                            ? (int) $cartItem['quantity']
                            : 0,
                ];
            }

            $cartCheckout = [
                'source' => 'cart',
                'items' => $items,
            ];

            if (
                $items === [] ||
                count(
                    buildCheckoutLines(
                        $cartCheckout,
                        $products
                    )
                ) !== count($items)
            ) {
                $errors['general'] =
                    'One or more cart items are invalid.';
            } else {
                $_SESSION['buy_now_checkout'] =
                    $cartCheckout;

                redirectToCheckout();
            }
        }
    } elseif ($action === 'place_order') {
        foreach ($old as $field => $value) {
            $old[$field] = trim(
                isset($_POST[$field])
                    ? (string) $_POST[$field]
                    : ''
            );
        }

        if ($checkout === null) {
            $errors['general'] =
                'Your checkout session is empty.';
        }

        if ($old['full_name'] === '') {
            $errors['full_name'] =
                'Please enter your full name.';
        } elseif (
            textLength($old['full_name']) < 2 ||
            textLength($old['full_name']) > 100
        ) {
            $errors['full_name'] =
                'Name must be between 2 and 100 characters.';
        }

        if ($old['email'] === '') {
            $errors['email'] =
                'Please enter your email.';
        } elseif (
            !filter_var(
                $old['email'],
                FILTER_VALIDATE_EMAIL
            )
        ) {
            $errors['email'] =
                'Please enter a valid email address.';
        }

        if ($old['phone'] === '') {
            $errors['phone'] =
                'Please enter your phone number.';
        } elseif (
            !preg_match(
                '/^[0-9+()\-\s]{7,20}$/',
                $old['phone']
            )
        ) {
            $errors['phone'] =
                'Please enter a valid phone number.';
        }

        if ($old['address'] === '') {
            $errors['address'] =
                'Please enter your delivery address.';
        } elseif (
            textLength($old['address']) < 10 ||
            textLength($old['address']) > 200
        ) {
            $errors['address'] =
                'Address must be between 10 and 200 characters.';
        }

        if ($old['city'] === '') {
            $errors['city'] =
                'Please enter your city.';
        } elseif (
            textLength($old['city']) < 2 ||
            textLength($old['city']) > 80
        ) {
            $errors['city'] =
                'City must be between 2 and 80 characters.';
        }

        if ($old['postal_code'] === '') {
            $errors['postal_code'] =
                'Please enter your postal code.';
        } elseif (
            !preg_match(
                '/^[A-Za-z0-9\-\s]{3,12}$/',
                $old['postal_code']
            )
        ) {
            $errors['postal_code'] =
                'Please enter a valid postal code.';
        }

        if (
            $databaseError !== '' ||
            !($pdo instanceof PDO)
        ) {
            $errors['general'] =
                $databaseError !== ''
                    ? $databaseError
                    : 'The database is not available.';
        }

        if (
            empty($errors) &&
            $checkout !== null
        ) {
            $orderLines = buildCheckoutLines(
                $checkout,
                $products
            );

            if ($orderLines === []) {
                $errors['general'] =
                    'The product information is invalid.';
            } else {
                $subtotal = 0.0;
                $itemCount = 0;

                foreach ($orderLines as $line) {
                    $subtotal +=
                        (float) $line['line_total'];

                    $itemCount +=
                        (int) $line['quantity'];
                }

                $deliveryFee = 5.00;
                $totalAmount =
                    $subtotal + $deliveryFee;

                $orderNumber =
                    'BH-' .
                    date('Ymd') .
                    '-' .
                    strtoupper(
                        bin2hex(random_bytes(4))
                    );

                $checkoutSource =
                    ($checkout['source'] ?? '') === 'cart'
                        ? 'cart'
                        : 'product';

                $userId = filter_var(
                    $_SESSION['user_id'] ?? null,
                    FILTER_VALIDATE_INT,
                    [
                        'options' => [
                            'min_range' => 1,
                        ],
                    ]
                );

                $userId =
                    $userId === false
                        ? null
                        : $userId;

                $itemsForStorage = [];

                foreach ($orderLines as $line) {
                    $itemsForStorage[] = [
                        'product_id' =>
                            (string) $line['product_id'],
                        'product_name' =>
                            (string) $line['name'],
                        'size' =>
                            (string) $line['size'],
                        'quantity' =>
                            (int) $line['quantity'],
                        'unit_price' =>
                            number_format(
                                (float) $line['price'],
                                2,
                                '.',
                                ''
                            ),
                        'line_total' =>
                            number_format(
                                (float) $line['line_total'],
                                2,
                                '.',
                                ''
                            ),
                    ];
                }

                $orderItemsJson = json_encode(
                    $itemsForStorage,
                    JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES
                );

                if ($orderItemsJson === false) {
                    $errors['general'] =
                        'The order could not be prepared.';
                } else {
                    try {
                        $insertOrder = $pdo->prepare(
                            'INSERT INTO orders (
                                order_number,
                                user_id,
                                customer_name,
                                customer_email,
                                customer_phone,
                                delivery_address,
                                city,
                                postal_code,
                                payment_method,
                                checkout_source,
                                order_items,
                                item_count,
                                subtotal,
                                delivery_fee,
                                total_amount,
                                order_status
                            ) VALUES (
                                :order_number,
                                :user_id,
                                :customer_name,
                                :customer_email,
                                :customer_phone,
                                :delivery_address,
                                :city,
                                :postal_code,
                                :payment_method,
                                :checkout_source,
                                :order_items,
                                :item_count,
                                :subtotal,
                                :delivery_fee,
                                :total_amount,
                                :order_status
                            )'
                        );

                        $insertOrder->execute([
                            'order_number' =>
                                $orderNumber,
                            'user_id' =>
                                $userId,
                            'customer_name' =>
                                $old['full_name'],
                            'customer_email' =>
                                strtolower($old['email']),
                            'customer_phone' =>
                                $old['phone'],
                            'delivery_address' =>
                                $old['address'],
                            'city' =>
                                $old['city'],
                            'postal_code' =>
                                $old['postal_code'],
                            'payment_method' =>
                                'cash_on_delivery',
                            'checkout_source' =>
                                $checkoutSource,
                            'order_items' =>
                                $orderItemsJson,
                            'item_count' =>
                                $itemCount,
                            'subtotal' =>
                                number_format(
                                    $subtotal,
                                    2,
                                    '.',
                                    ''
                                ),
                            'delivery_fee' =>
                                number_format(
                                    $deliveryFee,
                                    2,
                                    '.',
                                    ''
                                ),
                            'total_amount' =>
                                number_format(
                                    $totalAmount,
                                    2,
                                    '.',
                                    ''
                                ),
                            'order_status' =>
                                'pending',
                        ]);

                        $orderId =
                            (int) $pdo->lastInsertId();

                        if ($orderId < 1) {
                            throw new RuntimeException(
                                'Order ID was not returned.'
                            );
                        }

                        $_SESSION['order_confirmation'] = [
                            'order_number' =>
                                $orderNumber,
                            'name' =>
                                $old['full_name'],
                            'email' =>
                                $old['email'],
                            'items' =>
                                $orderLines,
                            'item_count' =>
                                $itemCount,
                            'total' =>
                                $totalAmount,
                            'payment_method' =>
                                'Cash on Delivery',
                        ];

                        if ($checkoutSource === 'cart') {
                            $_SESSION['cart'] = [];
                        }

                        unset(
                            $_SESSION['buy_now_checkout']
                        );

                        $_SESSION['csrf_token'] =
                            bin2hex(random_bytes(32));

                        redirectToCheckout();
                    } catch (Throwable $exception) {
                        error_log(
                            'Bookhaven order error: ' .
                            $exception->getMessage()
                        );

                        $errors['general'] =
                            'Your order could not be saved.';
                    }
                }
            }
        }
    } else {
        $errors['general'] =
            'Invalid checkout request.';
    }
}

$checkoutLines = buildCheckoutLines(
    $checkout,
    $products
);

if (
    $checkout !== null &&
    $checkoutLines === []
) {
    unset($_SESSION['buy_now_checkout']);
    $checkout = null;
}

$subtotal = 0.0;
$itemCount = 0;

foreach ($checkoutLines as $line) {
    $subtotal +=
        (float) $line['line_total'];

    $itemCount +=
        (int) $line['quantity'];
}

$deliveryFee =
    $checkoutLines !== []
        ? 5.00
        : 0.0;

$total = $subtotal + $deliveryFee;

$editOrderLink =
    ($checkout['source'] ?? '') === 'cart'
        ? 'cart.php'
        : 'product.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>StyleCart - Buy Now</title>

    <link
        rel="stylesheet"
        href="buy-now.css"
    >

    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/icon?family=Material+Icons"
    >
</head>

<body>
    <header class="checkout-header">
        <a href="index.php" class="brand">
            <span class="brand-symbol">◆</span>
            <span>StyleCart</span>
        </a>

        <div class="secure-checkout">
            <span
                class="material-icons"
                aria-hidden="true"
            >
                lock
            </span>

            Secure checkout
        </div>

        <a href="cart.php" class="cart-link">
            <span
                class="material-icons"
                aria-hidden="true"
            >
                shopping_cart
            </span>

            Cart
        </a>
    </header>

    <main class="checkout-container">
        <?php if ($confirmation !== null): ?>
            <section class="status-card success-card">
                <div class="status-icon">
                    <span
                        class="material-icons"
                        aria-hidden="true"
                    >
                        check_circle
                    </span>
                </div>

                <p class="eyebrow">
                    Order confirmed
                </p>

                <h1>
                    Thank you,
                    <?php
                    echo escape(
                        (string) $confirmation['name']
                    );
                    ?>!
                </h1>

                <p>
                    Your order number is

                    <strong>
                        <?php
                        echo escape(
                            (string)
                            $confirmation['order_number']
                        );
                        ?>
                    </strong>.
                </p>

                <div class="confirmation-items">
                    <?php
                    foreach (
                        ($confirmation['items'] ?? [])
                        as $confirmedItem
                    ):
                    ?>
                        <div class="confirmation-item">
                            <span>
                                <?php
                                echo escape(
                                    (string)
                                    $confirmedItem['name']
                                );
                                ?>

                                — Size

                                <?php
                                echo escape(
                                    (string)
                                    $confirmedItem['size']
                                );
                                ?>

                                ×

                                <?php
                                echo (int)
                                    $confirmedItem['quantity'];
                                ?>
                            </span>

                            <strong>
                                <?php
                                echo money(
                                    (float)
                                    $confirmedItem['line_total']
                                );
                                ?>
                            </strong>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="confirmation-details">
                    <div>
                        <span>Total items</span>

                        <strong>
                            <?php
                            echo (int)
                                ($confirmation['item_count'] ?? 0);
                            ?>
                        </strong>
                    </div>

                    <div>
                        <span>Payment</span>

                        <strong>
                            <?php
                            echo escape(
                                (string)
                                $confirmation['payment_method']
                            );
                            ?>
                        </strong>
                    </div>

                    <div>
                        <span>Total</span>

                        <strong>
                            <?php
                            echo money(
                                (float)
                                $confirmation['total']
                            );
                            ?>
                        </strong>
                    </div>
                </div>

                <a
                    href="product.php"
                    class="primary-link"
                >
                    Continue shopping
                </a>

                <a
                    href="index.php"
                    class="secondary-link"
                >
                    Return home
                </a>
            </section>

        <?php elseif ($checkout === null): ?>
            <section class="status-card">
                <div class="status-icon">
                    <span
                        class="material-icons"
                        aria-hidden="true"
                    >
                        shopping_bag
                    </span>
                </div>

                <h1>No product selected</h1>

                <?php
                if (isset($errors['general'])):
                ?>
                    <div
                        class="form-message error"
                        role="alert"
                    >
                        <?php
                        echo escape(
                            $errors['general']
                        );
                        ?>
                    </div>
                <?php else: ?>
                    <p>
                        Return to the product page and
                        click Buy now.
                    </p>
                <?php endif; ?>

                <a
                    href="product.php"
                    class="primary-link"
                >
                    Go to product
                </a>
            </section>

        <?php else: ?>
            <div class="checkout-heading">
                <p class="eyebrow">
                    Fast checkout
                </p>

                <h1>Complete your order</h1>

                <p>
                    Enter your delivery details and
                    review the order before confirming.
                </p>
            </div>

            <div class="checkout-grid">
                <section class="details-card">
                    <form
                        id="checkoutForm"
                        method="post"
                        action="<?php
                        echo escape(checkoutPageUrl());
                        ?>"
                        novalidate
                    >
                        <input
                            type="hidden"
                            name="action"
                            value="place_order"
                        >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?php
                            echo escape(
                                (string)
                                $_SESSION['csrf_token']
                            );
                            ?>"
                        >

                        <?php
                        if (isset($errors['general'])):
                        ?>
                            <div
                                class="form-message error"
                                role="alert"
                            >
                                <?php
                                echo escape(
                                    $errors['general']
                                );
                                ?>
                            </div>
                        <?php endif; ?>

                        <fieldset>
                            <legend>
                                Contact information
                            </legend>

                            <div class="field-grid two-columns">
                                <div
                                    class="field-group <?php
                                    echo isset(
                                        $errors['full_name']
                                    )
                                        ? 'has-error'
                                        : '';
                                    ?>"
                                >
                                    <label for="fullName">
                                        Full name
                                    </label>

                                    <input
                                        type="text"
                                        id="fullName"
                                        name="full_name"
                                        value="<?php
                                        echo escape(
                                            $old['full_name']
                                        );
                                        ?>"
                                        autocomplete="name"
                                        maxlength="100"
                                        aria-describedby="fullNameError"
                                        required
                                    >

                                    <small id="fullNameError">
                                        <?php
                                        echo escape(
                                            $errors['full_name']
                                            ?? ''
                                        );
                                        ?>
                                    </small>
                                </div>

                                <div
                                    class="field-group <?php
                                    echo isset(
                                        $errors['email']
                                    )
                                        ? 'has-error'
                                        : '';
                                    ?>"
                                >
                                    <label for="email">
                                        Email
                                    </label>

                                    <input
                                        type="email"
                                        id="email"
                                        name="email"
                                        value="<?php
                                        echo escape(
                                            $old['email']
                                        );
                                        ?>"
                                        autocomplete="email"
                                        maxlength="255"
                                        aria-describedby="emailError"
                                        required
                                    >

                                    <small id="emailError">
                                        <?php
                                        echo escape(
                                            $errors['email']
                                            ?? ''
                                        );
                                        ?>
                                    </small>
                                </div>
                            </div>

                            <div
                                class="field-group <?php
                                echo isset($errors['phone'])
                                    ? 'has-error'
                                    : '';
                                ?>"
                            >
                                <label for="phone">
                                    Phone number
                                </label>

                                <input
                                    type="tel"
                                    id="phone"
                                    name="phone"
                                    value="<?php
                                    echo escape(
                                        $old['phone']
                                    );
                                    ?>"
                                    autocomplete="tel"
                                    maxlength="20"
                                    placeholder="+880 1XXXXXXXXX"
                                    aria-describedby="phoneError"
                                    required
                                >

                                <small id="phoneError">
                                    <?php
                                    echo escape(
                                        $errors['phone']
                                        ?? ''
                                    );
                                    ?>
                                </small>
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend>
                                Delivery address
                            </legend>

                            <div
                                class="field-group <?php
                                echo isset($errors['address'])
                                    ? 'has-error'
                                    : '';
                                ?>"
                            >
                                <label for="address">
                                    Street address
                                </label>

                                <textarea
                                    id="address"
                                    name="address"
                                    rows="3"
                                    maxlength="200"
                                    autocomplete="street-address"
                                    aria-describedby="addressError"
                                    required
                                ><?php
                                echo escape(
                                    $old['address']
                                );
                                ?></textarea>

                                <small id="addressError">
                                    <?php
                                    echo escape(
                                        $errors['address']
                                        ?? ''
                                    );
                                    ?>
                                </small>
                            </div>

                            <div class="field-grid two-columns">
                                <div
                                    class="field-group <?php
                                    echo isset($errors['city'])
                                        ? 'has-error'
                                        : '';
                                    ?>"
                                >
                                    <label for="city">
                                        City
                                    </label>

                                    <input
                                        type="text"
                                        id="city"
                                        name="city"
                                        value="<?php
                                        echo escape(
                                            $old['city']
                                        );
                                        ?>"
                                        autocomplete="address-level2"
                                        maxlength="80"
                                        aria-describedby="cityError"
                                        required
                                    >

                                    <small id="cityError">
                                        <?php
                                        echo escape(
                                            $errors['city']
                                            ?? ''
                                        );
                                        ?>
                                    </small>
                                </div>

                                <div
                                    class="field-group <?php
                                    echo isset(
                                        $errors['postal_code']
                                    )
                                        ? 'has-error'
                                        : '';
                                    ?>"
                                >
                                    <label for="postalCode">
                                        Postal code
                                    </label>

                                    <input
                                        type="text"
                                        id="postalCode"
                                        name="postal_code"
                                        value="<?php
                                        echo escape(
                                            $old['postal_code']
                                        );
                                        ?>"
                                        autocomplete="postal-code"
                                        maxlength="12"
                                        aria-describedby="postalCodeError"
                                        required
                                    >

                                    <small id="postalCodeError">
                                        <?php
                                        echo escape(
                                            $errors['postal_code']
                                            ?? ''
                                        );
                                        ?>
                                    </small>
                                </div>
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend>
                                Payment method
                            </legend>

                            <label class="payment-option">
                                <input
                                    type="radio"
                                    name="payment_method"
                                    value="cash_on_delivery"
                                    checked
                                >

                                <span
                                    class="material-icons"
                                    aria-hidden="true"
                                >
                                    local_shipping
                                </span>

                                <span>
                                    <strong>
                                        Cash on Delivery
                                    </strong>

                                    <small>
                                        Pay when your order arrives.
                                    </small>
                                </span>
                            </label>
                        </fieldset>

                        <button
                            type="submit"
                            class="place-order-button"
                        >
                            Place order —
                            <?php echo money($total); ?>
                        </button>
                    </form>
                </section>

                <aside class="summary-card">
                    <h2>Order summary</h2>

                    <div class="summary-products">
                        <?php
                        foreach ($checkoutLines as $line):
                        ?>
                            <div class="summary-product">
                                <img
                                    src="<?php
                                    echo escape(
                                        (string) $line['image']
                                    );
                                    ?>"
                                    alt="<?php
                                    echo escape(
                                        (string) $line['name']
                                    );
                                    ?>"
                                >

                                <div>
                                    <h3>
                                        <?php
                                        echo escape(
                                            (string) $line['name']
                                        );
                                        ?>
                                    </h3>

                                    <p>
                                        Size:
                                        <?php
                                        echo escape(
                                            (string) $line['size']
                                        );
                                        ?>
                                    </p>

                                    <p>
                                        Quantity:
                                        <?php
                                        echo (int)
                                            $line['quantity'];
                                        ?>
                                    </p>
                                </div>

                                <strong>
                                    <?php
                                    echo money(
                                        (float)
                                        $line['line_total']
                                    );
                                    ?>
                                </strong>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="summary-lines">
                        <div>
                            <span>Subtotal</span>

                            <span>
                                <?php
                                echo money($subtotal);
                                ?>
                            </span>
                        </div>

                        <div>
                            <span>Delivery</span>

                            <span>
                                <?php
                                echo money($deliveryFee);
                                ?>
                            </span>
                        </div>

                        <div class="summary-total">
                            <span>Total</span>

                            <span>
                                <?php echo money($total); ?>
                            </span>
                        </div>
                    </div>

                    <div class="summary-note">
                        <span
                            class="material-icons"
                            aria-hidden="true"
                        >
                            verified_user
                        </span>

                        <p>
                            Your order details are protected
                            by server-side validation.
                        </p>
                    </div>

                    <a
                        href="<?php
                        echo escape($editOrderLink);
                        ?>"
                        class="edit-order-link"
                    >
                        <?php
                        echo
                            ($checkout['source'] ?? '')
                            === 'cart'
                                ? '← Return to cart'
                                : '← Edit product selection';
                        ?>
                    </a>
                </aside>
            </div>
        <?php endif; ?>
    </main>

    <script src="buy-now.js"></script>
</body>
</html>