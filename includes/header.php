<?php
   session_start();
   require_once "core/config/dbquery.php";
   $users = new Dbquery();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Meta tags -->
    <meta name="description" content="Discover the beauty and history of Iceland's most popular beach and resort">
    <meta name="keywords" content="Iceland, beach, resort, villas, luxury, relaxation, family">

    <!-- Open Graph / Social -->
    <meta property="og:title" content="ICELAND BEACH AND RESORT">
    <meta property="og:description" content="Nestled on the pristine shores, Iceland Private Beach Resort offers an exclusive haven for discerning individuals seeking tranquility and refinement. Our resort embodies the essence of elegance, blending breathtaking natural beauty with exquisite amenities.">
    <meta property="og:image" content="/static/images/img (13).png">
    <meta property="og:url" content="https://icelandbeach.com">
    <meta property="og:site_name" content="ICELAND BEACH AND RESORT">

    <!-- Other meta -->
    <meta name="author" content="Nwabali chinonso">
    <meta name="copyright" content="Iceland Beach 2025">
    <meta name="robots" content="index, follow">
    <meta name="googlebot" content="index, follow">

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="/static/images/img (1).png">
    <link rel="icon" type="image/png" sizes="16x16" href="/static/images/img (1).png">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- Main CSS -->
    <link rel="stylesheet" href="static/styles/style.css">
    <link rel="stylesheet" href="static/CAOS.file/CAOS.file.v-1.5/style.css">

    <!-- Page title -->
    <?php if(isset($title)): ?>
        <title>Iceland Beach | <?php echo $title ?></title>
    <?php else: ?>
        <title>Iceland Beach And Resort</title>
    <?php endif; ?>
</head>
<body>
<!-- Page loader -->
<div class="loading">
   <div class="loader"></div>
</div>

<script>
   // Page loader
   const loading = document.querySelector('.loading');
   window.addEventListener('load', () => {
      loading.classList.add('active');
   });
</script>

<!-- Social icons -->
<div class="about">
   <a class="bg_links social twitter" href="#" target="_blank">
      <span class="icon fa-brands fa-twitter-square"></span>
   </a>
   <a class="bg_links social instagram" href="#" target="_blank">
      <span class="icon fa-brands fa-instagram"></span>
   </a>
   <a class="bg_links social facebook" href="#" target="_blank">
      <span class="icon fa-brands fa-facebook"></span>
   </a>
   <a class="bg_links logo fa fa-user"></a>
</div>

<!-- Navigation -->
<nav>
<?php 
   if(isset($_SESSION['email'])):
      $email = $_SESSION['email'];
      $selectuser = $users->select("membership", "*", "email =? AND member_status =?", [$email, 'paid'], "ss");
      if($selectuser->num_rows > 0): 
?>
      <div class="logo d-flex align-items-center">
          <img src="/static/images/img (1).png" alt="Iceland Logo" width="50"> 
          <p class="ms-3 lead">| MEMBER</p>
      </div>
<?php else: ?>
      <div class="logo">
          <img src="/static/images/img (1).png" alt="Iceland Logo" width="50">
      </div>
<?php endif; else: ?>
      <div class="logo">
          <img src="/static/images/img (1).png" alt="Iceland Logo" width="50">
      </div>
<?php endif; ?>
   <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasExample" aria-controls="offcanvasExample">
      <i class="fa fa-bars"></i>
   </button>
</nav>

<!-- Offcanvas -->
<div class="offcanvas offcanvas-start" id="offcanvasExample">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title"><img src="/static/images/img (1).png" alt="Iceland Logo" width="50"></h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
      <li><a href="index" class="text-decoration-none"><span class="fa fa-home navicon"></span> Home</a></li>
      <li><a href="about" class="text-decoration-none"><span class="fa fa-user navicon"></span> About</a></li>
      <li><a href="event" class="text-decoration-none"><i class="fa fa-calendar-plus navicon"></i> Event</a></li>
      <li><a href="menu" class="text-decoration-none"><span class="fa fa-utensils navicon"></span> Menu</a></li>
      <li class="droplist"><a class="text-decoration-none" href="services"><span class="fa fa-database navicon"></span> Our Spaces</a></li>
      <li><a href="rooms" class="text-decoration-none"><span class="fa fa-bed navicon"></span> Rooms</a></li>
      <li><a href="membership" class="text-decoration-none"><span class="fa fa-crown navicon"></span> Membership</a></li>
      <li><a href="gallery" class="text-decoration-none"><span class="fa fa-image navicon"></span> Gallery</a></li>
      <li><a href="contact" class="text-decoration-none"><span class="fa fa-phone navicon"></span> Contact Us</a></li>
<?php 
   if(isset($_SESSION['email'])):
      $email = $_SESSION['email'];
      $selectuser = $users->select("membership", "*", "email = ? AND member_status = ?", [$email, 'paid'], "ss");
      if($selectuser->num_rows > 0): 
          $newu = $selectuser->fetch_assoc();
          $username = $newu['full_name'];
?>
      <li><a href="profile?name=<?=$username?>" class="text-decoration-none"><span class="fa fa-users navicon"></span> <?=$_SESSION['email']?></a></li> 
      <li><a href="logout" class="text-decoration-none"><span class="fa fa-arrow-left navicon"></span> Logout</a></li> 
<?php endif; else: ?>
      <li><a href="login" class="text-decoration-none"><span class="fa fa-users navicon"></span> Login</a></li>
<?php endif; ?>
  </div>
</div>
