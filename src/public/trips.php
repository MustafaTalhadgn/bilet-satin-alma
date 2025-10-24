<?php


require_once __DIR__ . '/../app/core/session.php';


require_once __DIR__ . '/../app/config/config.php';


require_once __DIR__ . '/../app/controllers/TripController.php';


$tripController = new TripController($pdo);


$tripController->showTripResults();

?>
