<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once("php/configuration.php");
include_once "php/constants.php";
include_once "php/functions.php";

ensureDefaultUser();

if (isset($_POST['userMail']) && isset($_POST['password'])) {
    switch ($_POST['submitType']) {
        case "login":
            loginUser($_POST['userMail'], $_POST['password']);
            break;
        case "register":
            registerUser($_POST['userMail'], $_POST['password']);
            die("registration disabled");
            break;
    }
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Annotation Interface -- Login</title>
    <link href="css/login.css" rel="stylesheet" />
    <script defer src="plugins/fontawesome-all.min.js"></script>
    <?php if (isset($_SESSION['user']) && isset($_SESSION['admin'])) { ?>
    <meta http-equiv="refresh">
    <script language="javascript">
        window.location.href = "index.php"
    </script>
    <?php } ?>
</head>
<body>
<?php if (!isset($_SESSION['loggedIn'])) { ?>
    <section class="loginForm cf">
        <form id="form" name="login" method="post" accept-charset="utf-8">
            <ul>
                <li><label for="userMail">Email</label>
                    <input id="userMail" type="text" name="userMail" placeholder="username" value="<?= isset($_POST['userMail']) ? $_POST['userMail'] : "" ?>" required /></li>
                <li><label for="password">Password</label>
                    <input id="password" type="password" name="password" placeholder="password" required <?php if (isset($_POST['userMail'])) echo 'class="invalid"' ?> /></li>
                <li><input id="submitType" type="hidden" name="submitType"/> </li>
                <li><input type="submit" value="Login"  onclick="(function() {
                        document.getElementById('submitType').value='login';
                })()" /></li>
<?php 

//                <li><input type="submit" value="Create Account" onclick="(function() {
//                        document.getElementById('submitType').value='register';
//                    })()" /></li>
?>
            </ul>
        </form>
    </section>
    <?php 
        if (Configuration::instance()->get("misc", "showinfo") == 1) {
            include("blocks/info.php");
        }
    ?>
<?php } else { ?>
    <p>Logged in successfully. If you are not redirected automatically, follow the <a href='index.php'>link</a>.</p>

<?php } ?>
</body>

