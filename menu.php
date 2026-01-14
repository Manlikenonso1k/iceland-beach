<?php 
require_once "includes/header.php"; 

// --- UPDATED: Define the ABSOLUTE URL for the PDF ---
// The Google Viewer needs the *full* public URL.
// Replace 'https://yourdomain.com' with your actual website's base URL.
$pdf_url = 'https://' . $_SERVER['HTTP_HOST'] . '/static/menu.pdf';
$google_viewer_src = 'https://docs.google.com/gview?url=' . urlencode($pdf_url) . '&embedded=true';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iceland Beach Menu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            background: url('static/Iceland-beach-resort.jpeg') no-repeat center center/cover;
            animation: pan 30s infinite alternate ease-in-out;
            background-attachment: fixed;
        }

        @keyframes pan {
            0% { background-position: left center; }
            100% { background-position: right center; }
        }

        /* Overlay to make text readable */
        body::before {
            content: "";
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            /* Slightly darker overlay for better contrast */
            background: rgba(255, 255, 255, 0.75); 
            z-index: 0;
        }

        /* Container */
        .menu-container {
            position: relative;
            z-index: 1;
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px;
            text-align: center;
        }

        /* Title animation (same as yours) */
        .menu-title {
            text-align: center;
            font-size: 2.2rem;
            font-weight: 700;
            margin: 30px 0;
            color: #0073e6;
            position: relative;
            display: inline-block;
            background: linear-gradient(90deg, #0073e6, #00b4d8, #90e0ef, #0073e6);
            background-size: 300% 300%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: shimmer 4s infinite linear;
        }

        @keyframes shimmer {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* PDF Viewer */
        .pdf-viewer {
            /* Adds a card-like container around the iframe */
            background: white; 
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
            padding: 10px;
        }
        .menu-frame {
            width: 100%;
            /* Reduced for better mobile height and scrollability */
            height: 75vh; 
            border: none;
            border-radius: 6px;
        }

        /* Buttons */
        .pdf-actions {
            margin-top: 20px;
        }
        .pdf-actions a {
            margin: 5px;
            padding: 10px 20px;
            border-radius: 30px;
            transition: all 0.3s ease;
        }
        .pdf-actions a:hover {
            transform: scale(1.05);
        }

        /* Mobile adjustments */
        @media (max-width: 768px) {
            .menu-title {
                font-size: 1.8rem;
                margin-top: 20px;
            }
            .menu-frame {
                /* Even smaller height on small screens */
                height: 60vh; 
            }
            .pdf-actions a {
                /* Stack buttons vertically on small screens */
                display: block; 
                width: 100%;
                margin: 10px 0;
            }
        }
    </style>
</head>
<body>

    <div class="menu-container">
        <h1 class="menu-title">Iceland Beach Menu</h1>

        <div class="pdf-viewer">
            <iframe 
                src="<?php echo $google_viewer_src; ?>" 
                class="menu-frame" 
                title="Iceland Beach Menu PDF Viewer"
                frameborder="0"
                allowfullscreen>
            </iframe>
        </div>

        <div class="pdf-actions">
            <a href="static/menu.pdf" class="btn btn-primary" download>
                <i class="fas fa-download"></i> Download Menu
            </a>
            <a href="static/menu.pdf" target="_blank" class="btn btn-outline-primary">
                <i class="fas fa-external-link-alt"></i> Open in New Tab
            </a>
        </div>
    </div>

    </body>
</html>
<?php require_once "includes/footer.php"; ?>