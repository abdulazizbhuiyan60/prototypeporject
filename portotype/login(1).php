<?php
declare(strict_types=1);

session_start();

/*
 * XAMPP defaults. If your MySQL username or password is different,
 * change these four values.
 */
$dbHost = 'localhost';
$dbName = 'bookhaven';
$dbUser = 'root';
$dbPass = '';

$loginErrors = [];
$signupErrors = [];
$activeForm = 'login';
$loginEmail = '';
$signupName = '';
$signupEmail = '';
$databaseError = '';

$successMessage = isset($_SESSION['auth_success'])
    ? (string) $_SESSION['auth_success']
    : '';
$loginEmail = isset($_SESSION['login_email'])
    ? (string) $_SESSION['login_email']
    : '';
unset($_SESSION['auth_success'], $_SESSION['login_email']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function textLength(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
}

/*
 * Connect to the existing Bookhaven database.
 * Import database.sql once in phpMyAdmin before using this page.
 */
$pdo = null;

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
    $databaseError = 'Cannot connect to the database. Start MySQL in XAMPP and import database.sql.';
    $pdo = null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? (string) $_POST['action'] : '';
    $activeForm = $action === 'signup' ? 'signup' : 'login';

    $submittedToken = isset($_POST['csrf_token'])
        ? (string) $_POST['csrf_token']
        : '';

    if (!hash_equals((string) $_SESSION['csrf_token'], $submittedToken)) {
        $message = 'Your form session expired. Refresh the page and try again.';

        if ($activeForm === 'signup') {
            $signupErrors['general'] = $message;
        } else {
            $loginErrors['general'] = $message;
        }
    } elseif ($action === 'signup') {
        $signupName = trim(isset($_POST['signup_name']) ? (string) $_POST['signup_name'] : '');
        $signupName = preg_replace('/\s+/u', ' ', $signupName) ?? $signupName;
        $signupEmail = strtolower(trim(isset($_POST['signup_email']) ? (string) $_POST['signup_email'] : ''));
        $password = isset($_POST['signup_password']) ? (string) $_POST['signup_password'] : '';
        $confirmPassword = isset($_POST['confirm_password']) ? (string) $_POST['confirm_password'] : '';
        $acceptedTerms = isset($_POST['terms']);

        if ($signupName === '') {
            $signupErrors['name'] = 'Please enter your full name.';
        } elseif (textLength($signupName) < 3 || textLength($signupName) > 100) {
            $signupErrors['name'] = 'Name must be between 3 and 100 characters.';
        }

        if ($signupEmail === '') {
            $signupErrors['email'] = 'Please enter your email.';
        } elseif (!filter_var($signupEmail, FILTER_VALIDATE_EMAIL)) {
            $signupErrors['email'] = 'Please enter a valid email address.';
        } elseif (strlen($signupEmail) > 255) {
            $signupErrors['email'] = 'Email must not be longer than 255 characters.';
        }

        if ($password === '') {
            $signupErrors['password'] = 'Please create a password.';
        } elseif (strlen($password) < 8) {
            $signupErrors['password'] = 'Password must be at least 8 characters.';
        } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
            $signupErrors['password'] = 'Password must contain at least one letter and one number.';
        }

        if ($confirmPassword === '') {
            $signupErrors['confirm_password'] = 'Please confirm your password.';
        } elseif ($password !== $confirmPassword) {
            $signupErrors['confirm_password'] = 'Passwords do not match.';
        }

        if (!$acceptedTerms) {
            $signupErrors['terms'] = 'Please accept the Terms & Conditions.';
        }

        if ($databaseError !== '') {
            $signupErrors['general'] = $databaseError;
        }

        if (empty($signupErrors) && $pdo instanceof PDO) {
            try {
                $checkUser = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
                $checkUser->execute(['email' => $signupEmail]);

                if ($checkUser->fetch()) {
                    $signupErrors['email'] = 'An account with this email already exists.';
                } else {
                    $createUser = $pdo->prepare(
                        'INSERT INTO users (full_name, email, password_hash)
                         VALUES (:full_name, :email, :password_hash)'
                    );
                    $createUser->execute([
                        'full_name' => $signupName,
                        'email' => $signupEmail,
                        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    ]);

                    $_SESSION['auth_success'] = 'Account created successfully. You can log in now.';
                    $_SESSION['login_email'] = $signupEmail;
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                    $selfPath = strtok((string) $_SERVER['REQUEST_URI'], '?');
                    header('Location: ' . ($selfPath !== false ? $selfPath : 'login.php'));
                    exit;
                }
            } catch (PDOException $exception) {
                error_log('Bookhaven registration error: ' . $exception->getMessage());

                if ((string) $exception->getCode() === '23000') {
                    $signupErrors['email'] = 'An account with this email already exists.';
                } else {
                    $signupErrors['general'] = 'The account could not be created. Please try again.';
                }
            }
        }
    } elseif ($action === 'login') {
        $loginEmail = strtolower(trim(isset($_POST['login_email']) ? (string) $_POST['login_email'] : ''));
        $password = isset($_POST['login_password']) ? (string) $_POST['login_password'] : '';

        if ($loginEmail === '') {
            $loginErrors['email'] = 'Please enter your email.';
        } elseif (!filter_var($loginEmail, FILTER_VALIDATE_EMAIL)) {
            $loginErrors['email'] = 'Please enter a valid email address.';
        }

        if ($password === '') {
            $loginErrors['password'] = 'Please enter your password.';
        }

        if ($databaseError !== '') {
            $loginErrors['general'] = $databaseError;
        }

        if (empty($loginErrors) && $pdo instanceof PDO) {
            try {
                $findUser = $pdo->prepare(
                    'SELECT id, full_name, email, password_hash
                     FROM users
                     WHERE email = :email
                     LIMIT 1'
                );
                $findUser->execute(['email' => $loginEmail]);
                $user = $findUser->fetch();

                if (!$user || !password_verify($password, (string) $user['password_hash'])) {
                    $loginErrors['general'] = 'Incorrect email or password.';
                } else {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int) $user['id'];
                    $_SESSION['user_name'] = (string) $user['full_name'];
                    $_SESSION['user_email'] = (string) $user['email'];
                    $_SESSION['logged_in'] = true;
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                    header('Location: index.html');
                    exit;
                }
            } catch (PDOException $exception) {
                error_log('Bookhaven login error: ' . $exception->getMessage());
                $loginErrors['general'] = 'Login could not be completed. Please try again.';
            }
        }
    } else {
        $loginErrors['general'] = 'Invalid form request.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <title>Bookhaven - Login</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <main class="auth-container">
        <div class="auth-box">
            <div class="auth-left">
                <div class="brand-symbol">◆</div>
                <h1>Welcome to Bookhaven</h1>
                <p>Explore our catalog and borrow your favorite books all in one place.</p>
                <div class="fashion-text">
                    <span>READ</span>
                    <span>BORROW</span>
                    <span>RETURN</span>
                </div>
            </div>

            <div class="auth-right">
                <div class="form-container <?php echo $activeForm === 'signup' ? 'hidden' : ''; ?>" id="loginForm">
                    <h2>Welcome Back!</h2>
                    <p class="form-subtitle">Login to your Bookhaven account</p>

                    <?php if ($successMessage !== ''): ?>
                        <div class="form-message success" role="status">
                            <?php echo escape($successMessage); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($loginErrors['general'])): ?>
                        <div class="form-message error" role="alert">
                            <?php echo escape($loginErrors['general']); ?>
                        </div>
                    <?php endif; ?>

                    <form id="login" method="post" action="" novalidate>
                        <input type="hidden" name="action" value="login">
                        <input type="hidden" name="csrf_token" value="<?php echo escape((string) $_SESSION['csrf_token']); ?>">

                        <div class="input-group <?php echo isset($loginErrors['email']) ? 'has-error' : ''; ?>">
                            <label for="loginEmail">Email</label>
                            <div class="input-box">
                                <span class="material-icons" aria-hidden="true">email</span>
                                <input
                                    type="email"
                                    id="loginEmail"
                                    name="login_email"
                                    value="<?php echo escape($loginEmail); ?>"
                                    placeholder="Enter your email"
                                    autocomplete="email"
                                    maxlength="255"
                                    aria-describedby="loginEmailError"
                                    <?php echo isset($loginErrors['email']) ? 'aria-invalid="true"' : ''; ?>
                                    required
                                >
                            </div>
                            <small id="loginEmailError"><?php echo escape($loginErrors['email'] ?? ''); ?></small>
                        </div>

                        <div class="input-group <?php echo isset($loginErrors['password']) ? 'has-error' : ''; ?>">
                            <label for="loginPassword">Password</label>
                            <div class="input-box">
                                <span class="material-icons" aria-hidden="true">lock</span>
                                <input
                                    type="password"
                                    id="loginPassword"
                                    name="login_password"
                                    placeholder="Enter your password"
                                    autocomplete="current-password"
                                    aria-describedby="loginPasswordError"
                                    <?php echo isset($loginErrors['password']) ? 'aria-invalid="true"' : ''; ?>
                                    required
                                >
                                <button type="button" class="material-icons eye" id="loginEye" aria-label="Show password">visibility</button>
                            </div>
                            <small id="loginPasswordError"><?php echo escape($loginErrors['password'] ?? ''); ?></small>
                        </div>

                        <button type="submit" class="auth-button">Login</button>
                    </form>

                    <p class="switch-text">
                        Don't have an account?
                        <a href="#signup" id="showSignup">Sign Up</a>
                    </p>
                </div>

                <div class="form-container <?php echo $activeForm === 'signup' ? '' : 'hidden'; ?>" id="signupForm">
                    <h2>Create Account</h2>
                    <p class="form-subtitle">Join Bookhaven today</p>

                    <?php if (isset($signupErrors['general'])): ?>
                        <div class="form-message error" role="alert">
                            <?php echo escape($signupErrors['general']); ?>
                        </div>
                    <?php endif; ?>

                    <form id="signup" method="post" action="" novalidate>
                        <input type="hidden" name="action" value="signup">
                        <input type="hidden" name="csrf_token" value="<?php echo escape((string) $_SESSION['csrf_token']); ?>">

                        <div class="input-group <?php echo isset($signupErrors['name']) ? 'has-error' : ''; ?>">
                            <label for="signupName">Full Name</label>
                            <div class="input-box">
                                <span class="material-icons" aria-hidden="true">person</span>
                                <input
                                    type="text"
                                    id="signupName"
                                    name="signup_name"
                                    value="<?php echo escape($signupName); ?>"
                                    placeholder="Enter your full name"
                                    autocomplete="name"
                                    minlength="3"
                                    maxlength="100"
                                    aria-describedby="nameError"
                                    <?php echo isset($signupErrors['name']) ? 'aria-invalid="true"' : ''; ?>
                                    required
                                >
                            </div>
                            <small id="nameError"><?php echo escape($signupErrors['name'] ?? ''); ?></small>
                        </div>

                        <div class="input-group <?php echo isset($signupErrors['email']) ? 'has-error' : ''; ?>">
                            <label for="signupEmail">Email</label>
                            <div class="input-box">
                                <span class="material-icons" aria-hidden="true">email</span>
                                <input
                                    type="email"
                                    id="signupEmail"
                                    name="signup_email"
                                    value="<?php echo escape($signupEmail); ?>"
                                    placeholder="Enter your email"
                                    autocomplete="email"
                                    maxlength="255"
                                    aria-describedby="signupEmailError"
                                    <?php echo isset($signupErrors['email']) ? 'aria-invalid="true"' : ''; ?>
                                    required
                                >
                            </div>
                            <small id="signupEmailError"><?php echo escape($signupErrors['email'] ?? ''); ?></small>
                        </div>

                        <div class="input-group <?php echo isset($signupErrors['password']) ? 'has-error' : ''; ?>">
                            <label for="signupPassword">Password</label>
                            <div class="input-box">
                                <span class="material-icons" aria-hidden="true">lock</span>
                                <input
                                    type="password"
                                    id="signupPassword"
                                    name="signup_password"
                                    placeholder="Create a password"
                                    autocomplete="new-password"
                                    minlength="8"
                                    aria-describedby="signupPasswordError"
                                    <?php echo isset($signupErrors['password']) ? 'aria-invalid="true"' : ''; ?>
                                    required
                                >
                                <button type="button" class="material-icons eye" id="signupEye" aria-label="Show password">visibility</button>
                            </div>
                            <small id="signupPasswordError"><?php echo escape($signupErrors['password'] ?? ''); ?></small>
                        </div>

                        <div class="input-group <?php echo isset($signupErrors['confirm_password']) ? 'has-error' : ''; ?>">
                            <label for="confirmPassword">Confirm Password</label>
                            <div class="input-box">
                                <span class="material-icons" aria-hidden="true">lock</span>
                                <input
                                    type="password"
                                    id="confirmPassword"
                                    name="confirm_password"
                                    placeholder="Confirm your password"
                                    autocomplete="new-password"
                                    minlength="8"
                                    aria-describedby="confirmPasswordError"
                                    <?php echo isset($signupErrors['confirm_password']) ? 'aria-invalid="true"' : ''; ?>
                                    required
                                >
                                <button type="button" class="material-icons eye" id="confirmEye" aria-label="Show password">visibility</button>
                            </div>
                            <small id="confirmPasswordError"><?php echo escape($signupErrors['confirm_password'] ?? ''); ?></small>
                        </div>

                        <label class="terms">
                            <input type="checkbox" id="terms" name="terms" value="1" <?php echo isset($_POST['terms']) ? 'checked' : ''; ?>>
                            I agree to the Terms &amp; Conditions
                        </label>
                        <small id="termsError"><?php echo escape($signupErrors['terms'] ?? ''); ?></small>

                        <button type="submit" class="auth-button">Sign Up</button>
                    </form>

                    <p class="switch-text">
                        Already have an account?
                        <a href="#login" id="showLogin">Login</a>
                    </p>
                </div>
            </div>
        </div>
    </main>

    <script src="login.js"></script>
</body>
</html>
