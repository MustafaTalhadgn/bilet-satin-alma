<?php

require_once __DIR__ . '/../app/core/session.php';

require_once __DIR__ . '/../app/config/config.php';


require_once __DIR__ . '/../app/controllers/CompanyController.php';


$companyAdminController = new CompanyAdminController($pdo);


$companyAdminController->showDashboard();

?>
