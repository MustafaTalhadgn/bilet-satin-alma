<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="./assets/css/header.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="./assets/css/index.css">
  </head>
<body>
      <!-- header -->
   <header class="header ">
<nav class="navbar navbar-expand-md bg-info-subtle">
  <div class="container">
    <a class="navbar-brand header-image" href="/">
      <img src="./assets/images/logo.png" alt="">
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
      aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <div class="container-fluid">
        <div class="row align-items-center w-100">

          <!-- Sol Menü -->
          <ul class="navbar-nav mb-2 mb-lg-0 col-sm-4 d-flex justify-content-start">
            <li class="nav-item me-4">
              <a class="nav-link active" aria-current="page" href="/">Anasayfa</a>
            </li>
            <li class="nav-item me-4">
              <a class="nav-link" href="#">Otobüs Bileti</a>
            </li>
            <li class="nav-item dropdown me-4">
              <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                aria-expanded="false">
                İletişim
              </a>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#">Bize ulaşın</a></li>
                <li><a class="dropdown-item" href="#">Konum</a></li>
                <li><hr class="dropdown-divider"></li>
              </ul>
            </li>
          </ul>

          <!-- Arama Formu -->
          <form class="d-flex col-sm-4 justify-content-center" role="search">
            <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search" />
            <button class="btn btn-secondary" type="submit">Search</button>
          </form>

          <!-- Sağ Menü -->
          <ul class="navbar-nav mb-2 mb-lg-0 col-sm-4 d-flex justify-content-end">
            <li class="nav-item me-4">
              <a class="nav-link active" aria-current="page" href="/login.php">Giriş Yap</a>
            </li>
            <li class="nav-item me-4">
              <a class="nav-link active" aria-current="page" href="/register.php">Kayıt Ol</a>
            </li>
          </ul>

        </div>
      </div>
    </div>
  </div>
</nav>
    
   </header>

   <section class="search-trips backgroun-image">
    <div class="cover">
      <div class="container ">
        


      </div>
</div>
   </section>
   <section class="popular-trips">
      <div class="container ">
      </div>
   </section>






   <!-- footer -->
    <footer class="bg-info-subtle  fixed-bottom">
        <div class="container ">
        <div class="contact col">
            <ul class="footer-contact ">
            <p><strong>E-posta:</strong> destek@ticketbuy.com</p>
            
        </ul>
        <div class="footer-message text-center col ">    
        <p>© 2025 TicketBuy. Tüm hakları saklıdır.</p>
        </div>
        </div>
    </footer>
</body>
</html>