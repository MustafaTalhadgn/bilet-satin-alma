<?php

class AdminController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }


    public function showDashboard() {

        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            
            header("Location: /login.php");
            exit();
        }


        $data = [
            'pageTitle' => 'Admin Paneli Anasayfa'

        ];


        $this->loadView('/dashboard', $data); 
    }
    


    protected function loadView($viewName, $data = []) {
        extract($data);

        require __DIR__ . '/../../views/pages/admin/' . $viewName . '.php';
    }


}
?>
