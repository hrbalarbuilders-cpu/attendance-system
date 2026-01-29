<?php
/**
 * get_geo_fences.php
 * Returns all active geo-fence locations for the mobile app.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

include "db.php";

try {
    // Fetch all active geo-fence locations including polygon
    $query = "SELECT id, location_group, location_name, latitude, longitude, radius_meters, geofence_polygon 
              FROM geo_settings 
              WHERE is_active = 1 
              ORDER BY location_group ASC, location_name ASC";

    $result = $con->query($query);

    if ($result) {
        $fences = [];

        while ($row = $result->fetch_assoc()) {
            $fences[] = [
                'id' => (int) $row['id'],
                'group' => $row['location_group'] ?? '',
                'name' => $row['location_name'] ?? '',
                'lat' => floatval($row['latitude']),
                'lng' => floatval($row['longitude']),
                'radius' => floatval($row['radius_meters'] ?? 100),
                'polygon' => $row['geofence_polygon'] ?? null
            ];
        }

        echo json_encode([
            'status' => 'success',
            'data' => $fences,
            'count' => count($fences)
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'msg' => 'Failed to fetch geo-fences'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'msg' => 'Error: ' . $e->getMessage()
    ]);
}

$con->close();
?>