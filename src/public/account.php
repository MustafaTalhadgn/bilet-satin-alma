<?php

require_once __DIR__ . '/../app/core/session.php';

require_once __DIR__ . '/../app/config/config.php';

require_once __DIR__ . '/../app/controllers/UserController.php';

$userController = new UserController($pdo);

$userController->showAccountPage();

?>
