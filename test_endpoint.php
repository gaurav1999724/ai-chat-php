<?php
// Test the actual API endpoint
$url = 'http://localhost/ChatGpt/api/chatgpt.php';
$data = json_encode([
    'message' => 'test message',
    'session_id' => 'test123'
]);

$options = [
    'http' => [
        'header' => "Content-type: application/json\r\n",
        'method' => 'POST',
        'content' => $data
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo "Response:\n";
echo $result . "\n";
?> 