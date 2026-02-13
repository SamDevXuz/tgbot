<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anime Bot Installer - Success</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #e8f5e9; }
        .container { max-width: 600px; margin-top: 50px; }
        .card { border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="container text-center">
        <div class="card p-5">
            <h1 class="text-success mb-4">Muvaffaqiyatli O'rnatildi! 🎉</h1>
            <p class="lead">Sizning Anime Botingiz muvaffaqiyatli o'rnatildi va ishga tushirildi.</p>
            <p class="text-muted">Muallif: <a href="https://t.me/SamDevX">@SamDevX</a></p>

            <div class="alert alert-warning mt-4">
                <strong>Diqqat!</strong> Xavfsizlik maqsadida iltimos <code>install/</code> papkasini serverdan o'chirib tashlang.
            </div>

            <div class="mt-4">
                <a href="https://t.me/SamDevX" target="_blank" class="btn btn-outline-primary me-2">Dasturchi</a>
                <a href="<?php echo $data['bot_link']; ?>" target="_blank" class="btn btn-success">Botga o'tish</a>
            </div>
        </div>
    </div>
</body>
</html>
