<?php
// test_gemini.php
// Dùng để test API key Gemini, không liên quan DB

$apiKey = 'YOUR_GEMINI_API_KEY_HERE'; // dán cùng key như ở project_ai.php

$model = 'gemini-1.5-flash';
$url = "https://generativelanguage.googleapis.com/v1beta/models/$model:generateContent?key=" . urlencode($apiKey);

$payload = [
    "contents" => [
        [
            "parts" => [
                ["text" => "Hãy trả lời một câu ngắn: API Gemini của tôi đang hoạt động!"]
            ]
        ]
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));

$response = curl_exec($ch);

if ($response === false) {
    echo "Lỗi CURL: " . curl_error($ch);
    exit;
}

curl_close($ch);

$data = json_decode($response, true);

echo "<h3>Kết quả thô từ Gemini:</h3>";
echo "<pre>";
print_r($data);
echo "</pre>";

if (isset($data['error'])) {
    echo "<h4 style='color:red'>Gemini báo lỗi:</h4>";
    echo htmlspecialchars($data['error']['message'] ?? 'Không rõ lỗi');
} elseif (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
    echo "<h4>Text AI trả về:</h4>";
    echo nl2br(htmlspecialchars($data['candidates'][0]['content']['parts'][0]['text']));
} else {
    echo "<h4 style='color:red'>Không tìm thấy text trong phản hồi.</h4>";
}
