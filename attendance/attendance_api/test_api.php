<?php
// Clean test endpoint - no BOM, no includes
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

echo json_encode([
    'status' => 'success',
    'message' => 'API is working perfectly!',
    'timestamp' => date('Y-m-d H:i:s'),
    'server' => 'InfinityFree'
]);
// No closing PHP tag
