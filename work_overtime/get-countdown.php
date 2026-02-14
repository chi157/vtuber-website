<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$filepath = __DIR__ . '/countdown-data.json';

if (!file_exists($filepath)) {
    // 如果檔案不存在，返回預設 3 小時倒數
    echo json_encode([
        'success' => true,
        'data' => [
            'mode' => 'timestamp',
            'targetTimestamp' => (time() + 180 * 60) * 1000,
            'title' => '加班台倒數計時',
            'message' => '距離下班還有',
            'endMessage' => '🎉 下班囉！',
            'showDays' => true,
            'showHours' => true,
            'showMinutes' => true,
            'showSeconds' => true
        ],
        'isDefault' => true
    ]);
    exit();
}

$data = file_get_contents($filepath);
$config = json_decode($data, true);

if (!$config) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to read data']);
    exit();
}

echo json_encode([
    'success' => true,
    'data' => $config,
    'isDefault' => false,
    'serverTime' => time() * 1000
]);
