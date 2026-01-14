<?php
$title = "Our spaces";
include_once "includes/header.php";
include_once "core/config/dbquery.php";

$query = new Dbquery();
// Fetching services, ideally ordered by category for grouping
$space = $query->select("services", "*", "", [], "ORDER BY service_category ASC");


/**
 * Checks if the file path ends with a common video extension.
 * @param string $path The service_image path.
 * @return bool True if it looks like a video, false otherwise.
 */
function isVideoFile($path) {
    $videoExtensions = ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv']; 
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    return in_array(strtolower($ext), $videoExtensions);
}

?>
<style>
    .bd-placeholder-img {
        font-size: 1.125rem;
        text-anchor: middle;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
    }

    /* Container for media: sets a fixed height and ensures media covers it */
    .img-div {
        overflow: hidden;
        height: 200px; /* Default height for mobile/carousel, overwritten for desktop */
    }

    .img-div img,
    .img-div video {
        width: 100%;
        height: 100%;
        object-fit: cover; /* Ensures media covers the container without distortion */
        display: block;
    }
    
    /* Ensure the card content below the media stays consistent */
    .card {
        height: 100%;
    }

    #quantity {
        width: 150px;
        border: 1px solid gray !important;
    }

    /* --- MOBILE CAROUSEL STYLES (Applies up to 767.98px) --- */
    @media (max-width: 767.98px) {
        .mobile-carousel-container {
            overflow-x: auto; /* Enable horizontal scrolling */
            -webkit-overflow-scrolling: touch; /* Smooth scrolling on iOS */
            white-space: nowrap; /* Prevent items from wrapping */
            padding-bottom: 10px; /* Space for the scroll bar */
            /* Remove margins from the row */
            margin-left: 0;
            margin-right: 0;
        }
        
        .mobile-carousel-item {
            display: inline-block; 
            width: 85%; /* Each card takes 85% of the viewport width */
            max-width: 350px;
            margin-right: 15px; /* Space between cards */
        }
        
        /* Ensure the mobile view starts with padding */
        .mobile-carousel-wrapper {
             padding-left: 15px;
             padding-right: 15px;
        }

        /* Hide the standard grid view on mobile */
        .desktop-grid-row {
            display: none !important;
        }
        
        /* Reset row padding for the category header */
        .container > .row.g-3.p-3 {
            padding: 0 !important;
        }
    }
    /* --- END MOBILE CAROUSEL STYLES --- */

    @media (min-width: 768px) {
        .bd-placeholder-img-lg {
            font-size: 3.5rem;
        }
        /* Hide the mobile carousel on desktop */
        .mobile-carousel-wrapper {
            display: none;
        }
        /* Adjust image height for desktop grid */
        .desktop-grid-row .img-div {
            height: 225px; 
        }
    }
</style>

<section class="text-center container">
    <div class="row py-lg-5">
        <div class="col-lg-6 col-md-8 mx-auto">
            <h1 class="text-uppercase">Our spaces</h1>
            <p class="lead text-muted">
                Our mission is to provide exceptional service and quality services to our clients. We strive to create lasting memories through our services.
            </p>
            <p>
                <a href="contact" class="btn btn-primary my-2">Make Enquiry</a>
                <a class="btn btn-secondary my-2">Back Home</a>
            </p>
        </div>
    </div>
</section>
<div class="album py-5 bg-light"></div>
<div class="container">
    <?php
    if ($space->num_rows > 0):
        $servicesByCategory = [];

        // 1. Re-organize services into an associative array grouped by category
        while ($row = $space->fetch_assoc()) {
            $servicesByCategory[$row['service_category']][] = $row;
        }

        // 2. Loop through the categories and display the header and the two views (mobile carousel and desktop grid)
        foreach ($servicesByCategory as $category => $services):
    ?>

        <h1 class='text-uppercase mt-4'><?= htmlspecialchars($category) ?></h1>
        
        <div class="mobile-carousel-wrapper">
            <div class="mobile-carousel-container row flex-nowrap">
                <?php foreach ($services as $row): ?>
                    <?php $is_video = isVideoFile($row['service_image']); ?> 
                    <div class="mobile-carousel-item">
                        <div class="card shadow-sm">
                            <div class="img-div">
                                <?php if ($is_video): ?>
                                    <video src="<?= $row['service_image'] ?>" autoplay muted loop playsinline></video>
                                <?php else: ?>
                                    <img src="<?= $row['service_image'] ?>" alt="<?= htmlspecialchars($row['service_name']) ?>" class="img-fluid">
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <p class="card-text"><?= htmlspecialchars($row['service_name']) ?></p>
                                <h3 class="card-text my-3"><?= "₦" . number_format($row['service_price']) ?></h3>
                                <div>
                                    <a href="core/processor/reserve.php?service=<?= urlencode($row['service_name']) ?>" class="btn btn-outline-secondary w-100 text-center mt-3">Make Reservation</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="row g-3 p-3 d-none d-md-flex desktop-grid-row">
            <?php foreach ($services as $row): ?>
                <?php $is_video = isVideoFile($row['service_image']); ?>
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="img-div">
                            <?php if ($is_video): ?>
                                <video src="<?= $row['service_image'] ?>" autoplay muted loop playsinline></video>
                            <?php else: ?>
                                <img src="<?= $row['service_image'] ?>" alt="<?= htmlspecialchars($row['service_name']) ?>" class="img-fluid">
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <p class="card-text"><?= htmlspecialchars($row['service_name']) ?></p>
                            <h3 class="card-text my-3"><?= "₦" . number_format($row['service_price']) ?></h3>
                            <div>
                                <a href="core/processor/reserve.php?service=<?= urlencode($row['service_name']) ?>" class="btn btn-outline-secondary w-100 text-center mt-3">Make Reservation</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        endforeach; // End foreach category loop
    endif; // End if $space->num_rows > 0 check
    ?>
</div>
</div>
</main>


<?php include_once "includes/footer.php"; ?>