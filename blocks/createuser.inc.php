<?php
// TODO make this form pretty
// TODO move the logic from login.php in here
// TODO secure it by only allowing sessions with the admin flag to register new users
?>
 <form action="login.php" id="form" name="login" method="post" accept-charset="utf-8">
            <ul>
                <li><label for="userMail">Email</label>
                    <input id="userMail" type="text" name="userMail" placeholder="username" value="<?= isset($_POST['userMail']) ? $_POST['userMail'] : "" ?>" required /></li>
                <li><label for="password">Password</label>
                    <input id="password" type="password" name="password" placeholder="password" required <?php if (isset($_POST['userMail'])) echo 'class="invalid"' ?> /></li>
                <li><input id="submitType" type="hidden" name="submitType"/> </li>
                <li><input type="submit" value="Create"  onclick="(function() {
                        document.getElementById('submitType').value='register';
                })()" /></li>
                    </form>
