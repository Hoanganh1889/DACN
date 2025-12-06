<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user'])) { 
    header("Location: login.php"); 
    exit;
}

$user = $_SESSION['user'];
$uid = $user['id'];

$ai_result = "";
$error = "";

/* ============================================================
   1) XỬ LÝ GỬI YÊU CẦU AI
============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $prompt = trim($_POST['prompt']);

    if ($prompt === "") {
        $error = "Vui lòng nhập nội dung để phân tích.";
    } else {

        // GỌI OPENROUTER
        $api_key = "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";

        $url = "https://openrouter.ai/api/v1/chat/completions";

        $payload = [
            "model" => "meta-llama/llama-3.3-70b-instruct:free",
            "messages" => [
                ["role" => "user", "content" => $prompt]
            ]
        ];

        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer $api_key",
            "HTTP-Referer: http://localhost",
            "X-Title: DACN-Web-AI"
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE)
        ]);

        $response = curl_exec($ch);
        $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200) {
            $error = "API ERROR ($code): $response";
        } else {
            $data = json_decode($response, true);
            $ai_result = $data["choices"][0]["message"]["content"] ?? "Không có phản hồi.";
        }

        // LƯU LOG
        if ($ai_result) {
            $stmt = $conn->prepare("
                INSERT INTO ai_insights_logs (user_id, prompt, ai_result, model)
                VALUES (?,?,?,?)
            ");
            $model_used = "llama-3.3-70b-free";
            $stmt->bind_param("isss", $uid, $prompt, $ai_result, $model_used);
            $stmt->execute();
            $stmt->close();
        }
    }
}

/* ============================================================
   2) LẤY LỊCH SỬ GỢI Ý
============================================================ */
$logs = $conn->query("
    SELECT * FROM ai_insights_logs 
    WHERE user_id = $uid
    ORDER BY created_at DESC
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Gợi ý thông minh (AI Insights)</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background: #f3f4f6;
    font-family: 'Inter', sans-serif;
}

.page-container {
    max-width: 900px;
    margin: 40px auto;
    background: #ffffff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 25px;
    color: #1f2937;
}

.back-btn {
    display: inline-block;
    margin-bottom: 20px;
    padding: 10px 16px;
    background: #e5e7eb;
    color: #333;
    border-radius: 8px;
    text-decoration: none;
}
.back-btn:hover { background: #d1d5db; }

.prompt-box, .result-box {
    padding: 20px;
    border-radius: 12px;
    border: 1px solid #ddd;
    background: #fafafa;
}

.result-box { display: <?= $ai_result ? "block" : "none" ?>; }

.history-box {
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
}
</style>
</head>

<body>

<div class="page-container">

    <button class="btn btn-light mt-4" onclick="history.back();">
    ← Quay lại
</button>


    <h2 class="page-title">
        🤖 Gợi ý thông minh (AI Insights)
    </h2>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?=$error?></div>
    <?php endif; ?>

    <!-- FORM GỬI YÊU CẦU -->
    <form method="POST" class="prompt-box mb-4">
        <label class="fw-bold">Nhập nội dung muốn AI phân tích:</label>
        <textarea name="prompt" class="form-control p-3" rows="5"
                  placeholder="Ví dụ: Hãy phân tích hiệu suất làm việc tuần này và đưa ra gợi ý cải thiện..."></textarea>

        <button class="btn btn-primary mt-3 w-100">
            🚀 Phân tích bằng AI
        </button>
    </form>

    <!-- KẾT QUẢ AI -->
    <div class="result-box mb-5">
        <h5 class="fw-bold mb-3">📌 Kết quả phân tích:</h5>
        <div style="white-space: pre-line;"><?= $ai_result ?></div>
    </div>

    <!-- LỊCH SỬ PHÂN TÍCH -->
    <div class="history-box">
        <h5 class="fw-bold mb-3">🕘 Lịch sử gợi ý gần đây</h5>

        <?php if ($logs->num_rows == 0): ?>
            <p class="text-muted">Chưa có yêu cầu nào.</p>
        <?php else: ?>
            <?php while ($row = $logs->fetch_assoc()): ?>
                <div class="border rounded p-3 mb-3">
                    <div><b>📌 Prompt:</b> <?= nl2br(htmlspecialchars($row['prompt'])) ?></div>
                    <div class="mt-2 text-muted" style="font-size: 0.9rem;">
                        🕒 <?= date("H:i d/m/Y", strtotime($row['created_at'])) ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
