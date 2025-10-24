<?php

require_once __DIR__ . '/../app/core/session.php';


require_once __DIR__ . '/../app/config/config.php';


require_once __DIR__ . '/../app/controllers/TicketController.php';


$ticketController = new TicketController($pdo);


$ticketController->showMyTickets();

?>