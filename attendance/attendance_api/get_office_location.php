<?php


header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Database connection
include "db.php";

try {
    // Fetch office location from geo_settings table
    $query = "SELECT latitude, longitude, radius_meters FROM geo_settings WHERE id = 1 LIMIT 1";
    $result = $con->query($query);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Validate data
        $lat = floatval($row['latitude'] ?? 0);
        $lng = floatval($row['longitude'] ?? 0);
        $radius = floatval($row['radius_meters'] ?? 150);
        
        if ($lat != 0 && $lng != 0) {
            // Success: Return location data
            echo json_encode([
                'status' => 'success',
                'lat' => $lat,
                'lng' => $lng,
                'radius' => $radius
            ]);
        } else {
            // Data exists but invalid
            echo json_encode([
                'status' => 'error',
                'msg' => 'Location data not configured properly'
            ]);
        }
    } else {
        // No data found in database - location not configured
        echo json_encode([
            'status' => 'error',
            'msg' => 'Office location not configured. Please set location in admin panel.'
        ]);
    }
    
} catch (Exception $e) {
    // Error handling
    echo json_encode([
        'status' => 'error',
        'msg' => 'Failed to fetch location: ' . $e->getMessage()
    ]);
}

$con->close();
?>

