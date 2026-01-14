<?php 
    require_once "includes/header.php"; 
    require_once "core/config/dbquery.php"; 
    $current_date = date("Y-m-d H:i:s");

    $query = new Dbquery();
    $selectpost = $query->select("festival", "*", "end_date > ?", ['$current_date'], 's');
    if($selectpost->num_rows > 0):
        while($selected = $selectpost->fetch_assoc()):
?>
<style>
    .img-container{
        width: 100%;
        transition: .5s;
        overflow: hidden;
    }
    .img-container img{
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: .5s;
    }
    .img-container:hover img{
        transform: scale(1.1);
        filter: brightness(0.6);
    }
</style>
        <div class="container">
            <div style="overflow: hidden;">
                <h1><?=$selected['title']?></h1>
                <div class="mt-3 mb-5">
                    <small class="text-uppercase text-secondary"><?=html_entity_decode($selected['start_date'])?></small> - 
                    <small class="text-uppercase text-secondary"><?=html_entity_decode($selected['end_date'])?></small>
                </div>
                <div class="img-container"><img class="img-fluid" src="./static/images/post/<?=$selected['image']?>"></div>
                <div class="mt-2 mb-5"><?=html_entity_decode($selected['message'])?></div>
            </div>
        </div>


<?php 
        endwhile;
    else:
        echo "<div class='alert alert-primary mt-5 text-center'> No events available yet </div>";
    endif;
include "includes/footer.php"; 
?>