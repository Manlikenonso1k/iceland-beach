<?php
$title = "Available Rooms";
include_once "includes/header.php";

// require "core/config/dbquery.php";
$rooms = new Dbquery();

$basic  = $rooms->select("room", "*", "room_category = ? AND is_booked = ?", ['Basic', 'no'], "ss");
$premium = $rooms->select("room", "*", "room_category = ? AND is_booked = ?", ['Premium', 'no'], "ss");
$gold   = $rooms->select("room", "*", "room_category = ? AND is_booked = ?", ['Gold', 'no'], "ss");
$beach  = $rooms->select("room", "*", "room_category = ? AND is_booked = ?", ['Beach Apartment', 'no'], "ss");
?>

<link rel="stylesheet" href="static/styles/rooms.css">

<div class="container m4-5 py-5">

  <!-- Navigation Tabs -->
  <ul class="nav nav-tabs" id="myTab" role="tablist">

    <li class="nav-item" role="presentation">
      <button class="nav-link active"
              id="basic-tab"
              data-bs-toggle="tab"
              data-bs-target="#basic"
              type="button"
              role="tab"
              aria-controls="basic"
              aria-selected="true">
        Silver Rooms
      </button>
    </li>

    <li class="nav-item" role="presentation">
      <button class="nav-link"
              id="premium-tab"
              data-bs-toggle="tab"
              data-bs-target="#premium"
              type="button"
              role="tab"
              aria-controls="premium"
              aria-selected="false">
        Premium Rooms
      </button>
    </li>

    <li class="nav-item" role="presentation">
      <button class="nav-link"
              id="gold-tab"
              data-bs-toggle="tab"
              data-bs-target="#gold"
              type="button"
              role="tab"
              aria-controls="gold"
              aria-selected="false">
        Gold Rooms
      </button>
    </li>

    <li class="nav-item" role="presentation">
      <button class="nav-link"
              id="beach-tab"
              data-bs-toggle="tab"
              data-bs-target="#beach"
              type="button"
              role="tab"
              aria-controls="beach"
              aria-selected="false">
        Beach Apartment
      </button>
    </li>

  </ul>

  <!-- Tab Content -->
  <div class="tab-content mt-3" id="myTabContent">

    <!-- ===================== BASIC ROOMS ===================== -->
    <div class="tab-pane fade show active p-4" id="basic" role="tabpanel">

      <?php if ($basic->num_rows > 0): ?>
        <?php while ($b_rooms = $basic->fetch_assoc()): ?>
          
          <div class="row my-4 p-3 rooms justify-content-between">
            <div class="col-md-5 roomimg">
              <img src="<?= $b_rooms['room_image'] ?>" alt="Basic Room Image" class="img-fluid">
            </div>

            <div class="col-md-7">
              <sub>ROOM NAME:</sub>
              <h2><?= $b_rooms['room_name']; ?></h2><br>

              <sub>ADVANTAGES:</sub>
              <p>- Free Swimming <br> - Complementary</p><br>

              <sub>PRICE:</sub>
              <h2>₦<?= number_format($b_rooms['room_price']); ?> <sub>Per Day</sub></h2>

              <a class="btn btn-dark mt-5 px-5"
                 href="core/processor/reserve.php?room=<?= $b_rooms['room_name']; ?>">
                Book Now
              </a>
            </div>
          </div>

        <?php endwhile; ?>
      <?php else: ?>
        <p class="text-center">NO ROOMS AVAILABLE FOR BASIC ROOMS</p>
      <?php endif; ?>

    </div>

    <!-- ===================== PREMIUM ROOMS ===================== -->
    <div class="tab-pane fade p-4" id="premium" role="tabpanel">

      <?php if ($premium->num_rows > 0): ?>
        <?php while ($p_rooms = $premium->fetch_assoc()): ?>

          <div class="row my-4 p-3 rooms justify-content-between">

            <div class="col-md-5 roomimg">
              <img src="<?= $p_rooms['room_image'] ?>" alt="Premium Room Image" class="img-fluid">
            </div>

            <div class="col-md-7">
              <sub>ROOM NAME:</sub>
              <h2><?= $p_rooms['room_name']; ?></h2><br>

              <sub>ADVANTAGES:</sub>
              <ul>
                <li>Free Swimming</li>
                <li>Complementary</li>
              </ul><br>

              <sub>PRICE:</sub>
              <h2>₦<?= number_format($p_rooms['room_price']); ?> <sub>Per Day</sub></h2>

              <a class="btn btn-dark mt-5 px-5"
                 href="core/processor/reserve.php?room=<?= $p_rooms['room_name']; ?>">
                Book Now
              </a>
            </div>

          </div>

        <?php endwhile; ?>
      <?php else: ?>
        <p class="text-center">NO ROOMS AVAILABLE FOR PREMIUM ROOMS</p>
      <?php endif; ?>

    </div>

    <!-- ===================== GOLD ROOMS ===================== -->
    <div class="tab-pane fade p-4" id="gold" role="tabpanel">

      <?php if ($gold->num_rows > 0): ?>
        <?php while ($g_rooms = $gold->fetch_assoc()): ?>

          <div class="row my-4 p-3 rooms justify-content-between">

            <div class="col-md-5 roomimg">
              <img src="<?= $g_rooms['room_image'] ?>" alt="Gold Room Image" class="img-fluid">
            </div>

            <div class="col-md-7">
              <sub>ROOM NAME:</sub>
              <h2><?= $g_rooms['room_name']; ?></h2><br>

              <sub>ADVANTAGES:</sub>
              <p>
                - Complimentary Breakfast For 1<br>
                - 2 Bottles of water | Ice tea <br>
                -  Free laundry <br>
                - Larger room | Free Swimming
              </p><br>

              <sub>PRICE:</sub>
              <h2>₦<?= number_format($g_rooms['room_price']); ?> <sub>Per Day</sub></h2>

              <a class="btn btn-dark mt-5 px-5"
                 href="core/processor/reserve.php?room=<?= $g_rooms['room_name']; ?>">
                Book Now
              </a>
            </div>

          </div>

        <?php endwhile; ?>
      <?php else: ?>
        <p class="text-center">NO ROOMS AVAILABLE FOR GOLD ROOMS</p>
      <?php endif; ?>

    </div>

    <!-- ===================== BEACH APARTMENT (FIXED!) ===================== -->
    <div class="tab-pane fade p-4" id="beach" role="tabpanel">

      <?php if ($beach->num_rows > 0): ?>
        <?php while ($bch_rooms = $beach->fetch_assoc()): ?>

          <div class="row my-4 p-3 rooms justify-content-between">

            <div class="col-md-5 roomimg">
              <img src="<?= $bch_rooms['room_image'] ?>" alt="Beach Apartment Image" class="img-fluid">
            </div>

            <div class="col-md-7">
              <sub>ROOM NAME:</sub>
              <h2><?= $bch_rooms['room_name']; ?></h2><br>

              <sub>ADVANTAGES:</sub>
              <p>
                - Beach View <br>
                - Free Swimming <br>
                - Complimentary Breakfast For 2
              </p><br>

              <sub>PRICE:</sub>
              <h2>₦<?= number_format($bch_rooms['room_price']); ?> <sub>Per Day</sub></h2>

              <a class="btn btn-dark mt-5 px-5"
                 href="core/processor/reserve.php?room=<?= $bch_rooms['room_name']; ?>">
                Book Now
              </a>
            </div>

          </div>

        <?php endwhile; ?>
      <?php else: ?>
        <p class="text-center">NO ROOMS AVAILABLE FOR BEACH APARTMENTS</p>
      <?php endif; ?>

    </div>

  </div> <!-- END .tab-content -->

</div> <!-- END .container -->

<?php include "includes/footer.php"; ?>
