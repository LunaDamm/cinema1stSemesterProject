<?php
declare(strict_types=1);

session_start();
unset($_SESSION['user_id'], $_SESSION['user_username'], $_SESSION['user_email']);
header('Location: /');
exit;
