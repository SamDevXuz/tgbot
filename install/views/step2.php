<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anime Bot Installer - Step 2</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .container { max-width: 600px; margin-top: 50px; }
        .card { border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="container">
        <div class="card p-4">
            <h3 class="text-center mb-4">Admin Ma'lumotlari</h3>
            <p class="text-center text-muted">Muallif: <a href="https://t.me/SamDevX">@SamDevX</a></p>

            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="index.php" method="POST">
                <input type="hidden" name="step" value="2">
                <div class="mb-3">
                    <label for="admin_name" class="form-label">Ismingiz</label>
                    <input type="text" class="form-control" id="admin_name" name="admin_name" placeholder="Ismingizni kiriting" required>
                </div>
                <div class="mb-3">
                    <label for="admin_id" class="form-label">Telegram ID raqamingiz</label>
                    <input type="number" class="form-control" id="admin_id" name="admin_id" placeholder="Masalan: 7775806579" required>
                    <div class="form-text">Telegram ID raqamingizni @userinfobot orqali olishingiz mumkin.</div>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">Keyingi Qadam</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
