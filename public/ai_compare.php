<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$uid  = $user['id'];

$api_key = "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";

$models_available = [
    "meta-llama/llama-3.3-70b-instruct:free" => "LLaMA 3.3 70B (Free)",
    "google/gemini-flash-1.5"               => "Gemini Flash 1.5",
    "google/gemini-pro"                     => "Gemini Pro",
    "mistral/mistral-large"                 => "Mistral Large",
    "openai/gpt-4o-mini"                    => "GPT-4o Mini",
    "openai/gpt-4.1"                        => "GPT-4.1"
];

$error = "";
$compare_results = [];
$history = [];

/* ================================
   XỬ LÝ SO SÁNH
================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $prompt = trim($_POST['prompt']);
    $chosen = $_POST['models'] ?? [];

    if ($prompt == "") {
        $error = "Bạn chưa nhập prompt.";
    } 
    elseif (count($chosen) < 2) {
        $error = "Hãy chọn ít nhất 2 mô hình để so sánh.";
    }
    else {
        foreach ($chosen as $model) {

            $payload = [
                "model" => $model,
                "messages" => [
                    ["role" => "user", "content" => $prompt]
                ]
            ];

            $headers = [
                "Content-Type: application/json",
                "Authorization: Bearer $api_key",
                "HTTP-Referer: http://localhost",
                "X-Title: DACN-AI-Compare"
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => "https://openrouter.ai/api/v1/chat/completions",
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE)
            ]);

            $response = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code !== 200) {
                $compare_results[$model] = "❌ API ERROR ($code)";
            } else {
                $data = json_decode($response, true);
                $compare_results[$model] = $data["choices"][0]["message"]["content"];
            }
        }

        // LƯU VÀO HISTORY
        $stmt = $conn->prepare("
            INSERT INTO ai_model_compare_logs (user_id, prompt, models, results)
            VALUES (?,?,?,?)
        ");
        $json_models  = json_encode($chosen, JSON_UNESCAPED_UNICODE);
        $json_results = json_encode($compare_results, JSON_UNESCAPED_UNICODE);

        $stmt->bind_param("isss", $uid, $prompt, $json_models, $json_results);
        $stmt->execute();
        $stmt->close();
    }
}

/* ================================
   HISTORY
================================= */
$history = $conn->query("
    SELECT * FROM ai_model_compare_logs 
    WHERE user_id=$uid 
    ORDER BY created_at DESC LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>So sánh mô hình AI</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background:#f3f4f6;
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, rgba(0,150,200,0.5), rgba(0,0,70,0.4)),
                  url('https://images.unsplash.com/photo-1503264116251-35a269479413?auto=format&fit=crop&w=1350&q=80');
      background-size: cover;
      background-position: center;
      backdrop-filter: blur(10px);
    
}

.page-box {
    max-width: 1100px;
    margin: 40px auto;
    background:#fff;
    padding:30px;
    border-radius:12px;
    box-shadow:0 4px 12px rgba(0,0,0,0.08);
}

.page-title {
    font-size:2rem;
    font-weight:700;
    margin-bottom:25px;
    color:#1f2937;
}

.back-btn {
    padding:10px 16px;
    display:inline-block;
    background:#e5e7eb;
    border-radius:8px;
    text-decoration:none;
    color:#333;
}
.back-btn:hover { background:#d1d5db; }

.compare-col {
    background:#fafafa;
    border-radius:12px;
    padding:20px;
    border:1px solid #ddd;
    white-space:pre-line;
}
</style>

</head>

<body>

<div class="page-box">

    <button class="btn btn-light mt-4" onclick="history.back();">
    ← Quay lại
</button>


    <h2 class="page-title">⚖️ So sánh mô hình AI</h2>

    <?php if($error): ?>
        <div class="alert alert-danger"><?=$error?></div>
    <?php endif; ?>

    <!-- FORM -->
    <form method="POST" class="mb-4">

        <label class="fw-bold mb-1">Prompt cần so sánh:</label>
        <textarea name="prompt" class="form-control p-3" rows="4"
            placeholder="Ví dụ: Hãy mô tả quy trình tuyển dụng hiện đại..."></textarea>

        <label class="fw-bold mt-3 mb-1">Chọn mô hình:</label>
        <div class="row">
            <?php foreach ($models_available as $mKey => $label): ?>
            <div class="col-md-4">
                <label class="d-flex align-items-center gap-2">
                    <input type="checkbox" name="models[]" value="<?=$mKey?>">
                    <?=$label?>
                </label>
            </div>
            <?php endforeach; ?>
        </div>

        <button class="btn btn-primary w-100 mt-3">🚀 So sánh ngay</button>
    </form>

    <!-- KẾT QUẢ -->
    <?php if (!empty($compare_results)): ?>
    <h4 class="fw-bold mb-3">📊 Kết quả so sánh</h4>

    <div class="row">

        <?php foreach ($compare_results as $model => $result): ?>
        <div class="col-md-4 mb-3">
            <div class="compare-col">
                <h6 class="fw-bold"><?=$models_available[$model]?></h6>
                <div><?=$result?></div>
            </div>
        </div>
        <?php endforeach; ?>

    </div>
    <?php endif; ?>

    <!-- LỊCH SỬ -->
    <h4 class="fw-bold mt-4">🕓 Lịch sử so sánh</h4>

    <?php if ($history->num_rows == 0): ?>
        <p class="text-muted">Chưa có so sánh nào.</p>
    <?php else: ?>
        <?php while($h = $history->fetch_assoc()): ?>
            <div class="border rounded p-3 mb-3">
                <b>Prompt:</b> <?=nl2br(htmlspecialchars($h['prompt']))?><br>
                <span class="text-muted" style="font-size:0.9rem;">
                    <?=date("H:i d/m/Y", strtotime($h['created_at']))?>
                </span>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>

</div>

</body>
</html>
