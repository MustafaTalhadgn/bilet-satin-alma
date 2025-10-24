<?php



require_once __DIR__ . '/../app/core/session.php';


require_once __DIR__ . '/../app/config/config.php';


require_once __DIR__ . '/../app/controllers/HomeController.php';


$homeController = new HomeController($pdo);


$homeController->showHomePage();

?>
