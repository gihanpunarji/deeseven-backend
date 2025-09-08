<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "CORS/CORS.php";
require "connection/connection.php";
require_once "jwt_middleware.php";

header('Content-Type: application/json');

$response = [
    "status" => false,
    "message" => "Product not found",
    "data" => null
];

// Debug logging
error_log("fetchSingleProduct.php called");

$admin = validateJWT();
if (!$admin) {
    echo json_encode(["status" => false, "message" => "Unauthorized"]);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception("Invalid request method. Only GET allowed.");
    }

    $productId = $_GET['id'] ?? null;
    error_log("Product ID received: " . ($productId ?? 'null'));
    
    if (!$productId) {
        throw new Exception("Product ID is required.");
    }

    // First, let's just get basic product info to test
    $productQuery = "SELECT p.*, c.category_name, sc.sub_category_name 
                     FROM product p
                     LEFT JOIN category c ON p.category_category_id = c.category_id
                     LEFT JOIN sub_category sc ON p.sub_category_sub_category_id = sc.sub_category_id
                     WHERE p.product_id = ?";
    
    $productResult = Database::search($productQuery, [$productId]);
    
    if ($productResult->num_rows === 0) {
        throw new Exception("Product not found with ID: " . $productId);
    }

    $product = $productResult->fetch_assoc();
    error_log("Product found: " . $product['title']);

    // Initialize arrays
    $images = [];
    $fabric = [];
    $fabricCare = [];
    $notes = [];
    $features = [];
    $sizes = [];

    // Fetch product images
    try {
        $imagesQuery = "SELECT image_url FROM product_images WHERE product_product_id = ? ORDER BY product_images_id";
        $imagesResult = Database::search($imagesQuery, [$productId]);
        while ($row = $imagesResult->fetch_assoc()) {
            $images[] = ["image_url" => $row["image_url"]];
        }
        error_log("Images found: " . count($images));
    } catch (Exception $e) {
        error_log("Error fetching images: " . $e->getMessage());
    }

    // Fetch fabric details
    try {
        $fabricQuery = "SELECT about FROM fabric WHERE product_product_id = ? ORDER BY about_id";
        $fabricResult = Database::search($fabricQuery, [$productId]);
        while ($row = $fabricResult->fetch_assoc()) {
            $fabric[] = ["about" => $row["about"]];
        }
        error_log("Fabric details found: " . count($fabric));
    } catch (Exception $e) {
        error_log("Error fetching fabric: " . $e->getMessage());
    }

    // Fetch fabric care
    try {
        $fabricCareQuery = "SELECT fabric_care FROM fabric_care WHERE product_product_id = ? ORDER BY fabric_care_id";
        $fabricCareResult = Database::search($fabricCareQuery, [$productId]);
        while ($row = $fabricCareResult->fetch_assoc()) {
            $fabricCare[] = ["fabric_care" => $row["fabric_care"]];
        }
        error_log("Fabric care found: " . count($fabricCare));
    } catch (Exception $e) {
        error_log("Error fetching fabric care: " . $e->getMessage());
    }

    // Fetch notes
    try {
        $notesQuery = "SELECT note FROM note WHERE product_product_id = ? ORDER BY note_id";
        $notesResult = Database::search($notesQuery, [$productId]);
        while ($row = $notesResult->fetch_assoc()) {
            $notes[] = ["note" => $row["note"]];
        }
        error_log("Notes found: " . count($notes));
    } catch (Exception $e) {
        error_log("Error fetching notes: " . $e->getMessage());
    }

    // Fetch add-on features
    try {
        $featuresQuery = "SELECT features FROM add_on_features WHERE product_product_id = ? ORDER BY features_id";
        $featuresResult = Database::search($featuresQuery, [$productId]);
        while ($row = $featuresResult->fetch_assoc()) {
            $features[] = ["features" => $row["features"]];
        }
        error_log("Features found: " . count($features));
    } catch (Exception $e) {
        error_log("Error fetching features: " . $e->getMessage());
    }

    // Fetch sizes with quantities
    try {
        $sizesQuery = "SELECT s.size_id, s.size_name, ps.quantity 
                       FROM product_size ps
                       INNER JOIN size s ON ps.size_size_id = s.size_id
                       WHERE ps.product_product_id = ?
                       ORDER BY s.size_name";
        $sizesResult = Database::search($sizesQuery, [$productId]);
        while ($row = $sizesResult->fetch_assoc()) {
            $sizes[] = [
                "size_id" => $row["size_id"],
                "size_name" => $row["size_name"],
                "quantity" => $row["quantity"]
            ];
        }
        error_log("Sizes found: " . count($sizes));
    } catch (Exception $e) {
        error_log("Error fetching sizes: " . $e->getMessage());
    }

    // Ensure all arrays have default values if empty
    if (empty($fabric)) {
        $fabric[] = ["about" => ""];
    }
    if (empty($fabricCare)) {
        $fabricCare[] = ["fabric_care" => ""];
    }
    if (empty($features)) {
        $features[] = ["features" => ""];
    }

    // Prepare response data
    $productData = [
        "product_id" => $product["product_id"],
        "title" => $product["title"],
        "description" => $product["description"],
        "price" => $product["price"],
        "category_id" => $product["category_category_id"],
        "category_name" => $product["category_name"] ?? "",
        "sub_category_id" => $product["sub_category_sub_category_id"],
        "sub_category_name" => $product["sub_category_name"] ?? "",
        "date_added" => $product["date_added"],
        "product_status" => $product["product_status"],
        "images" => $images,
        "fabric" => $fabric,
        "fabric_care" => $fabricCare,
        "note" => !empty($notes) ? $notes[0]["note"] : "",
        "notes" => $notes,
        "add_on_features" => $features,
        "sizes" => $sizes
    ];

    error_log("Response prepared successfully");

    $response = [
        "status" => true,
        "message" => "Product fetched successfully",
        "data" => $productData
    ];

} catch (Exception $e) {
    error_log("Exception in fetchSingleProduct: " . $e->getMessage());
    $response = [
        "status" => false,
        "message" => $e->getMessage(),
        "data" => null
    ];
}

echo json_encode($response);
?>