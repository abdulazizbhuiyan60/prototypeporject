<?php
declare(strict_types=1);

session_start();

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function textLength(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
}

if (empty($_SESSION['contact_csrf_token'])) {
    $_SESSION['contact_csrf_token'] = bin2hex(random_bytes(32));
}

$subjectOptions = [
    'book_availability' => 'Book availability',
    'borrowing' => 'Borrowing and renewals',
    'account' => 'Account help',
    'order' => 'Order help',
    'other' => 'Other',
];

$values = [
    'full_name' => isset($_SESSION['user_name']) ? (string) $_SESSION['user_name'] : '',
    'email' => isset($_SESSION['user_email']) ? (string) $_SESSION['user_email'] : '',
    'phone' => '',
    'subject' => '',
    'message' => '',
];

$errors = [];
$databaseError = '';
$pdo = null;

$successMessage = isset($_SESSION['contact_success'])
    ? (string) $_SESSION['contact_success']
    : '';
unset($_SESSION['contact_success']);

try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=bookhaven;charset=utf8mb4',
        'root',
        '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS contact_messages (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT UNSIGNED NULL,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(20) NULL,
            subject VARCHAR(50) NOT NULL,
            message TEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'new',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY index_contact_email (email),
            KEY index_contact_status (status),
            KEY index_contact_date (created_at)
        ) ENGINE=InnoDB
          DEFAULT CHARACTER SET utf8mb4
          COLLATE utf8mb4_unicode_ci"
    );
} catch (PDOException $exception) {
    error_log('Bookhaven contact database error: ' . $exception->getMessage());
    $databaseError = 'The contact form is temporarily unavailable. Start MySQL in XAMPP and try again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $field => $currentValue) {
        $values[$field] = trim(isset($_POST[$field]) ? (string) $_POST[$field] : '');
    }

    $values['full_name'] = preg_replace('/\s+/u', ' ', $values['full_name']) ?? $values['full_name'];
    $values['email'] = strtolower($values['email']);

    $submittedToken = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';

    if (!hash_equals((string) $_SESSION['contact_csrf_token'], $submittedToken)) {
        $errors['general'] = 'Your session expired. Refresh the page and try again.';
    }

    if ($values['full_name'] === '') {
        $errors['full_name'] = 'Please enter your full name.';
    } elseif (textLength($values['full_name']) < 2 || textLength($values['full_name']) > 100) {
        $errors['full_name'] = 'Name must be between 2 and 100 characters.';
    }

    if ($values['email'] === '') {
        $errors['email'] = 'Please enter your email address.';
    } elseif (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    } elseif (strlen($values['email']) > 255) {
        $errors['email'] = 'Email must not be longer than 255 characters.';
    }

    if (
        $values['phone'] !== '' &&
        !preg_match('/^[0-9+()\-\s]{7,20}$/', $values['phone'])
    ) {
        $errors['phone'] = 'Please enter a valid phone number.';
    }

    if (!array_key_exists($values['subject'], $subjectOptions)) {
        $errors['subject'] = 'Please select a subject.';
    }

    if ($values['message'] === '') {
        $errors['message'] = 'Please enter your message.';
    } elseif (textLength($values['message']) < 10) {
        $errors['message'] = 'Message must contain at least 10 characters.';
    } elseif (textLength($values['message']) > 2000) {
        $errors['message'] = 'Message must not be longer than 2000 characters.';
    }

    if ($databaseError !== '' || !($pdo instanceof PDO)) {
        $errors['general'] = $databaseError !== ''
            ? $databaseError
            : 'The contact form is temporarily unavailable.';
    }

    if ($errors === [] && $pdo instanceof PDO) {
        try {
            $userId = filter_var(
                $_SESSION['user_id'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );
            $userId = $userId === false ? null : $userId;

            $insertMessage = $pdo->prepare(
                'INSERT INTO contact_messages (
                    user_id,
                    full_name,
                    email,
                    phone,
                    subject,
                    message
                ) VALUES (
                    :user_id,
                    :full_name,
                    :email,
                    :phone,
                    :subject,
                    :message
                )'
            );

            $insertMessage->execute([
                'user_id' => $userId,
                'full_name' => $values['full_name'],
                'email' => $values['email'],
                'phone' => $values['phone'] !== '' ? $values['phone'] : null,
                'subject' => $values['subject'],
                'message' => $values['message'],
            ]);

            $_SESSION['contact_success'] = 'Thank you! Your message has been received. We will get back to you soon.';
            $_SESSION['contact_csrf_token'] = bin2hex(random_bytes(32));

            header('Location: contact.php');
            exit;
        } catch (PDOException $exception) {
            error_log('Bookhaven contact form error: ' . $exception->getMessage());
            $errors['general'] = 'Your message could not be saved. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | Bookhaven</title>
    <meta name="description" content="Contact Bookhaven for help with books, borrowing, accounts, and orders.">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="contact.css">
</head>
<body class="contact-page">
    <header>
        <div class="logo">
            <span class="logo-icon">◆</span>
            Bookhaven
        </div>

        <nav aria-label="Main navigation">
            <a href="index.php">Home</a>
            <a href="contact.php" class="active" aria-current="page">Contact Us</a>
        </nav>

        <div class="nav-icons">
            <span class="material-icons search-icon" role="button" tabindex="0" aria-label="Open book search">search</span>

            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search books..." aria-label="Search books">
                <span class="material-icons close-search" role="button" tabindex="0" aria-label="Close search">close</span>
            </div>

            <span class="material-icons" aria-hidden="true">favorite_border</span>

            <a class="nav-icon-link" href="cart.php" aria-label="Shopping cart">
                <span class="material-icons">shopping_cart</span>
            </a>

            <a class="nav-icon-link profile-link" href="login.php" aria-label="Your account">
                <span class="material-icons profile">person</span>
            </a>
        </div>
    </header>

    <main class="contact-main">
        <section class="contact-hero">
            <div class="contact-hero-content">
                <p class="eyebrow">Bookhaven support</p>
                <h1>How can we help?</h1>
                <p>Ask us about a book, your membership, borrowing, or an order. Our team is ready to help.</p>
            </div>
        </section>

        <section class="contact-section">
            <div class="contact-grid">
                <section class="form-card" aria-labelledby="contactFormTitle">
                    <div class="section-heading">
                        <p class="eyebrow">Send a message</p>
                        <h2 id="contactFormTitle">How can we help?</h2>
                    </div>

                    <?php if ($successMessage !== ''): ?>
                        <div class="form-message success" role="status">
                            <span class="material-icons" aria-hidden="true">check_circle</span>
                            <span><?php echo escape($successMessage); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($errors['general'])): ?>
                        <div class="form-message error" role="alert">
                            <span class="material-icons" aria-hidden="true">error</span>
                            <span><?php echo escape($errors['general']); ?></span>
                        </div>
                    <?php endif; ?>

                    <form id="contactForm" method="post" action="contact.php" novalidate>
                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?php echo escape((string) $_SESSION['contact_csrf_token']); ?>"
                        >

                        <div class="field-row">
                            <div class="field-group <?php echo isset($errors['full_name']) ? 'has-error' : ''; ?>">
                                <label for="fullName">Full name</label>
                                <input
                                    type="text"
                                    id="fullName"
                                    name="full_name"
                                    value="<?php echo escape($values['full_name']); ?>"
                                    maxlength="100"
                                    autocomplete="name"
                                    aria-describedby="fullNameError"
                                    required
                                >
                                <small id="fullNameError"><?php echo escape($errors['full_name'] ?? ''); ?></small>
                            </div>

                            <div class="field-group <?php echo isset($errors['email']) ? 'has-error' : ''; ?>">
                                <label for="email">Email address</label>
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    value="<?php echo escape($values['email']); ?>"
                                    maxlength="255"
                                    autocomplete="email"
                                    aria-describedby="emailError"
                                    required
                                >
                                <small id="emailError"><?php echo escape($errors['email'] ?? ''); ?></small>
                            </div>
                        </div>

                        <div class="field-row">
                            <div class="field-group <?php echo isset($errors['phone']) ? 'has-error' : ''; ?>">
                                <label for="phone">Phone number <span>(optional)</span></label>
                                <input
                                    type="tel"
                                    id="phone"
                                    name="phone"
                                    value="<?php echo escape($values['phone']); ?>"
                                    maxlength="20"
                                    autocomplete="tel"
                                    placeholder="+880 1XXXXXXXXX"
                                    aria-describedby="phoneError"
                                >
                                <small id="phoneError"><?php echo escape($errors['phone'] ?? ''); ?></small>
                            </div>

                            <div class="field-group <?php echo isset($errors['subject']) ? 'has-error' : ''; ?>">
                                <label for="subject">Subject</label>
                                <select id="subject" name="subject" aria-describedby="subjectError" required>
                                    <option value="">Select a subject</option>
                                    <?php foreach ($subjectOptions as $subjectValue => $subjectLabel): ?>
                                        <option
                                            value="<?php echo escape($subjectValue); ?>"
                                            <?php echo $values['subject'] === $subjectValue ? 'selected' : ''; ?>
                                        ><?php echo escape($subjectLabel); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small id="subjectError"><?php echo escape($errors['subject'] ?? ''); ?></small>
                            </div>
                        </div>

                        <div class="field-group <?php echo isset($errors['message']) ? 'has-error' : ''; ?>">
                            <div class="label-row">
                                <label for="message">Message</label>
                                <span id="messageCount">0 / 2000</span>
                            </div>
                            <textarea
                                id="message"
                                name="message"
                                rows="7"
                                maxlength="2000"
                                placeholder="Tell us how we can help..."
                                aria-describedby="messageError messageCount"
                                required
                            ><?php echo escape($values['message']); ?></textarea>
                            <small id="messageError"><?php echo escape($errors['message'] ?? ''); ?></small>
                        </div>

                        <button type="submit" class="submit-button">
                            <span>Send message</span>
                            <span class="material-icons" aria-hidden="true">arrow_forward</span>
                        </button>
                    </form>
                </section>

                <aside class="contact-card" aria-label="Contact details">
                    <p class="eyebrow">Contact details</p>
                    <h2>Talk to our team</h2>
                    <p class="contact-intro">Choose the easiest way to reach us. We normally respond within one business day.</p>

                    <div class="contact-list">
                        <a class="contact-item" href="mailto:hello@bookhaven.com">
                            <span class="contact-icon material-icons" aria-hidden="true">mail</span>
                            <span>
                                <small>Email</small>
                                <strong>hello@bookhaven.com</strong>
                            </span>
                        </a>

                        <a class="contact-item" href="tel:+8801700000000">
                            <span class="contact-icon material-icons" aria-hidden="true">call</span>
                            <span>
                                <small>Phone</small>
                                <strong>+880 1700-000000</strong>
                            </span>
                        </a>

                        <div class="contact-item">
                            <span class="contact-icon material-icons" aria-hidden="true">location_on</span>
                            <span>
                                <small>Visit us</small>
                                <strong>Dhaka, Bangladesh</strong>
                            </span>
                        </div>

                        <div class="contact-item">
                            <span class="contact-icon material-icons" aria-hidden="true">schedule</span>
                            <span>
                                <small>Opening hours</small>
                                <strong>Sat–Thu, 9:00 AM–6:00 PM</strong>
                            </span>
                        </div>
                    </div>

                    <div class="response-note">
                        <span class="material-icons" aria-hidden="true">verified_user</span>
                        <p>Your message is protected with server-side validation and saved securely.</p>
                    </div>
                </aside>
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
                    <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; 2026 Bookhaven. All rights reserved.</p>
        </div>
    </footer>

    <script src="contact.js"></script>
</body>
</html>
