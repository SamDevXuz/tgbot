<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anime Bot Installer - Step 3</title>
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
            <h3 class="text-center mb-4">Ma'lumotlar Bazasi va Token</h3>
            <p class="text-center text-muted">Muallif: <a href="https://t.me/SamDevX">@SamDevX</a></p>

            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="index.php" method="POST">
                <input type="hidden" name="step" value="3">
                <div class="mb-3">
                    <label for="db_host" class="form-label">Database Host</label>
                    <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" required>
                </div>
                <div class="mb-3">
                    <label for="db_user" class="form-label">Database Username</label>
                    <input type="text" class="form-control" id="db_user" name="db_user" placeholder="root" required>
                </div>
                <div class="mb-3">
                    <label for="db_pass" class="form-label">Database Password</label>
                    <input type="password" class="form-control" id="db_pass" name="db_pass" placeholder="Database paroli">
                </div>
                <div class="mb-3">
                    <label for="db_name" class="form-label">Database Name</label>
                    <input type="text" class="form-control" id="db_name" name="db_name" placeholder="anime_bot" required>
                </div>
                <div class="mb-3">
                    <label for="bot_token" class="form-label">Bot API Token</label>
                    <input type="text" class="form-control" id="bot_token" name="bot_token" placeholder="123456789:ABCDefGHIjklMNOpqrsTUVwxyZ" required>
                    <div class="form-text">BotFather dan olingan token.</div>
                </div>
                <div class="mb-3">
                    <label for="webhook_url" class="form-label">Webhook URL</label>
                    <input type="url" class="form-control" id="webhook_url" name="webhook_url" value="<?php echo "https://" . $_SERVER['HTTP_HOST'] . str_replace("/install", "", dirname($_SERVER['REQUEST_URI'])); ?>" placeholder="https://yoursite.com" required>
                    <div class="form-text">Bot joylashgan sayt manzili (avtomatik aniqlandi).</div>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">O'rnatishni Yakunlash</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
