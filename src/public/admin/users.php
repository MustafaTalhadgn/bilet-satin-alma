<?php

require_once __DIR__ . '/../../app/core/session.php';


require_once __DIR__ . '/../../app/config/config.php';


require_once __DIR__ . '/../../app/controllers/admin/AdminUserController.php';


if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /login.php"); 
    exit();
}


$adminUserController = new AdminUserController($pdo);


$adminUserController->showUsersPage();

?>
