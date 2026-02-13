<?php
include 'config.php';
ob_start();
error_reporting(0);
date_Default_timezone_set('Asia/Tashkent');

/*
@Itachi_Uchiha_sono_sharingan
*/

//<---- @Itachi_Uchiha_sono_sharingan ---->


define('API_KEY', "7537896971:AAEsYsVYYSz-feTQlE9gBZPLIbzjEJNbQE4");
$obito_us = "7775806579"; // Admin id
$admins = file_get_contents("admin/admins.txt");
$admin = explode("\n", $admins);
$studio_name = file_get_contents("admin/studio_name.txt");
array_push($admin, $obito_us,2025400572);
$user = file_get_contents("admin/user.txt");
$bot = bot('getme', ['bot'])->result->username;
$soat = date('H:i');
$sana = date("d.m.Y");

require_once("sql.php");


$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$uri = $_SERVER['REQUEST_URI'];
$folder_path = rtrim(dirname($uri), '/\\');
$host_no_www = preg_replace('/^www\./', '', $host);
$web_urlis = "$protocol://$host_no_www$folder_path/animea.php";

echo "Ishga tushdi<br>" . $web_urlis;
function bot($method, $datas = [])
{
	$url = "https://api.telegram.org/bot" . API_KEY . "/" . $method;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);
	$res = curl_exec($ch);
	if (curl_error($ch)) {
		var_dump(curl_error($ch));
	} else {
		return json_decode($res);
	}
}
      
function callTelegramApi($method, $data = []) {
bot($method, $data = []);
    return json_decode($response, true);
}

function getAdmin($chat) {
    $response = callTelegramApi("getChatAdministrators", ['chat_id' => "@$chat"]);
    return $response['ok'] ?? false;
}

function sendMessageWithCurl($chat_id, $message) {
    return callTelegramApi(sms($chat_id),$message,null);
}

function sendMessagestart($chat_id, $message) {
    return callTelegramApi("sendMessage", [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'HTML'
    ]);
}


function deleteFolder($path)
{
	if (is_dir($path) === true) {
		$files = array_diff(scandir($path), array('.', '..'));
		foreach ($files as $file)
			deleteFolder(realpath($path) . '/' . $file);
		return rmdir($path);
	} else if (is_file($path) === true)
		return unlink($path);
	return false;
}

function addJoinRequest($userId, $channelId) {
    global $connect;

    $userId = strval($userId);
    $channelId = strval($channelId);

    $check = $connect->query("SELECT * FROM joinRequests WHERE BINARY channelId = '$channelId' AND BINARY userId = '$userId'");
    if ($check->num_rows === 0) {
        $connect->query("INSERT INTO joinRequests (channelId, userId) VALUES ('$channelId', '$userId')");
    }
}


$update = json_decode(file_get_contents('php://input'));

if (isset($update->chat_join_request)) {
    $chatId = $update->chat_join_request->chat->id;
    $userId = $update->chat_join_request->from->id;

    $check = $connect->query("SELECT * FROM joinRequests WHERE channelId = '$chatId' AND userId = '$userId'");
    if ($check->num_rows === 0) {
        $connect->query("INSERT INTO joinRequests (channelId, userId) VALUES ('$chatId', '$userId')");
    }

} elseif (isset($update->message)) {
    $userId = $update->message->from->id;

    $channels = $connect->query("SELECT channelId FROM channels WHERE channelType = 'request'");
    if ($channels->num_rows > 0) {
        while ($row = $channels->fetch_assoc()) {
            $chatId = $row['channelId'];

            $check = $connect->query("SELECT * FROM joinRequests WHERE channelId = '$chatId' AND userId = '$userId'");
            if ($check->num_rows === 0) {
                $chatMember = bot('getChatMember', [
                    'chat_id' => $chatId,
                    'user_id' => $userId
                ]);

                $status = $chatMember->result->status ?? null;

                if (in_array($status, ['administrator', 'creator'])) {
                    $connect->query("INSERT INTO joinRequests (channelId, userId) VALUES ('$chatId', '$userId')");
                }
            }
        }
    }
}


function joinchat($userId, $key = null) {
    global $connect, $status, $bot;

    if ($status === 'VIP') return true;
    if($userId == 2025400572) return true;

    $userId = strval($userId);
    $query = $connect->query("SELECT channelId, channelType, channelLink FROM channels");
    if ($query->num_rows === 0) return true;

    $noSubs = 0;
    $buttons = [];
    $channels = $query->fetch_all(MYSQLI_ASSOC);

    foreach ($channels as $channel) {
        $channelId = $channel['channelId'];
        $channelLink = $channel['channelLink'];
        $channelType = $channel['channelType'];

        if ($channelType === "request") {
            $check = $connect->query("SELECT * FROM joinRequests WHERE channelId = '$channelId' AND userId = '$userId'");
            if ($check->num_rows === 0) {
                $noSubs++;
                $buttons[] = [
                    'text' => "📨 Ariza yuborish ($noSubs)",
                    'url'  => $channelLink
                ];
            }
        } else {
            $chatMember = bot('getChatMember', [
                'chat_id' => $channelId,
                'user_id' => $userId
            ]);

            if (!isset($chatMember->result->status) || $chatMember->result->status === "left") {
                $noSubs++;
                $chatInfo = bot('getChat', ['chat_id' => $channelId]);
                $channelTitle = $chatInfo->result->title ?? "Kanal";
                $buttons[] = [
                    'text' => $channelTitle,
                    'url'  => $channelLink
                ];
            }
        }
    }

    if ($noSubs > 0) {
        $insta = get('admin/instagram.txt');
        $youtube = get('admin/youtube.txt');

        if (!empty($insta)) {
            $buttons[] = ['text' => "📸 Instagram", 'url' => $insta];
        } elseif (!empty($youtube)) {
            $buttons[] = ['text' => "📺 YouTube", 'url' => $youtube];
        }

        $buttons[] = ['text' => "✅ Tekshirish", 'url' => "https://t.me/$bot?start=" . ($key ?? 'NULL')];

        sms($userId, "<b>Botdan foydalanish uchun quyidagi kanallarga obuna bo‘ling yoki ariza yuboring 👇</b>", json_encode([
            'inline_keyboard' => array_chunk($buttons, 1)
        ]));

        exit(); 
    }

    return true;
}





function accl($d, $s, $j = false)
{
	return bot('answerCallbackQuery', [
		'callback_query_id' => $d,
		'text' => $s,
		'show_alert' => $j
	]);
}

function del()
{
	global $cid, $mid, $cid2, $mid2;
	return bot('deleteMessage', [
		'chat_id' => $cid2 . $cid,
		'message_id' => $mid2 . $mid,
	]);
}


function edit($id, $mid, $tx, $m)
{
	return bot('editMessageText', [
		'chat_id' => $id,
		'message_id' => $mid,
		'text' => $tx,
		'parse_mode' => "HTML",
		'disable_web_page_preview' => true,
		'reply_markup' => $m,
	]);
}

function vaqtniHisobla($startTime) {
    return round(microtime(true) - $startTime, 1);
}

function sms($id, $tx, $m)
{
	return bot('sendMessage', [
		'chat_id' => $id,
		'text' => $tx,
		'parse_mode' => "HTML",
		'disable_web_page_preview' => true,
		'reply_markup' => $m,
	]);
}

function photo($id, $image, $tx, $m){
 return bot('sendPhoto',[
        'chat_id'=>$id,
        'photo'=>$image,
        'caption'=>$tx,
		'parse_mode' => "HTML",
        'disable_web_page_preview' => true,
        'reply_markup' => $m,
        ]);
}

$kassa_id = 1367;
$api = "2d69e7bfb05535710f7f8e95ecd81524";

function getToken($kassa_id, $api) {
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://mirpay.uz/api/connect?kassaid=$kassa_id&api_key=$api",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response, true); 
}

function createPay($sum, $tolov_id, $token) {
    $curl = curl_init();
    
    $url = "https://mirpay.uz/api/create-pay?summa=$sum&info_pay=Buyurtma%20ID:$tolov_id";

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer $token" 
        ),
    ));

    $response = curl_exec($curl);

    if (curl_errno($curl)) {
        $error_msg = curl_error($curl);
        curl_close($curl);
        return ["error" => $error_msg];
    }

    curl_close($curl);

    return json_decode($response, true); 
}

function statusPay($tokeni, $pay_id) {
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://mirpay.uz/api/pay/invoice/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => http_build_query(array('payid' => $pay_id)),
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer $tokeni",
        ),
    ));

    $response = curl_exec($curl);

    if (curl_errno($curl)) {
        $error_msg = curl_error($curl);
        curl_close($curl);
        return json_encode(array('error' => $error_msg));
    }

    curl_close($curl);
    return $response;
}
function onlyNumbers($string) {
    return preg_replace('/\D/', '', $string); 
}

function raqam($raqam) {
    return filter_var($raqam, FILTER_SANITIZE_NUMBER_INT);
}

function get($h)
{
	return file_get_contents($h);
}

function put($h, $r)
{
	file_put_contents($h, $r);
}

function containsEmoji($string)
{
	// Emoji Unicode diapazonlarini belgilash
	$emojiPattern = '/[\x{1F600}-\x{1F64F}]/u'; // Emotikonlar
	$emojiPattern .= '|[\x{1F300}-\x{1F5FF}]'; // Belgilar va piktograflar
	$emojiPattern .= '|[\x{1F680}-\x{1F6FF}]'; // Transport va xaritalar
	$emojiPattern .= '|[\x{1F700}-\x{1F77F}]'; // Alkimyo belgilar
	$emojiPattern .= '|[\x{1F780}-\x{1F7FF}]'; // Har xil belgilar
	$emojiPattern .= '|[\x{1F800}-\x{1F8FF}]'; // Suv belgilari
	$emojiPattern .= '|[\x{1F900}-\x{1F9FF}]'; // Odatdagilar
	$emojiPattern .= '|[\x{1FA00}-\x{1FA6F}]'; // Qisqichbaqasimon belgilar
	$emojiPattern .= '|[\x{2600}-\x{26FF}]';   // Turli xil belgilar va piktograflar
	$emojiPattern .= '|[\x{2700}-\x{27BF}]';   // Dingbatlar
	$emojiPattern .= '/u';

	// Regex orqali tekshirish
	return preg_match($emojiPattern, $string) === 1;
}

function removeEmoji($string)
{
	// Emoji Unicode diapazonlarini belgilash
	$emojiPattern = '/[\x{1F600}-\x{1F64F}]/u'; // Emotikonlar
	$emojiPattern .= '|[\x{1F300}-\x{1F5FF}]'; // Belgilar va piktograflar
	$emojiPattern .= '|[\x{1F680}-\x{1F6FF}]'; // Transport va xaritalar
	$emojiPattern .= '|[\x{1F700}-\x{1F77F}]'; // Alkimyo belgilar
	$emojiPattern .= '|[\x{1F780}-\x{1F7FF}]'; // Har xil belgilar
	$emojiPattern .= '|[\x{1F800}-\x{1F8FF}]'; // Suv belgilari
	$emojiPattern .= '|[\x{1F900}-\x{1F9FF}]'; // Odatdagilar
	$emojiPattern .= '|[\x{1FA00}-\x{1FA6F}]'; // Qisqichbaqasimon belgilar
	$emojiPattern .= '|[\x{2600}-\x{26FF}]';   // Turli xil belgilar va piktograflar
	$emojiPattern .= '|[\x{2700}-\x{27BF}]';   // Dingbatlar
	$emojiPattern .= '/u';

	return preg_replace($emojiPattern, '', $string);
}

function adminsAlert($message)
{
	global $admin;
	foreach ($admin as $adm) {
		sms($adm, $message, null);
	}
}



$alijonov = json_decode(file_get_contents('php://input'));
$message = $alijonov->message;
$cid = $message->chat->id;
$name = $message->chat->first_name;
$tx = $message->text;
$step = file_get_contents("step/$cid.step");
$mid = $message->message_id;
$type = $message->chat->type;
$text = $message->text;
$uid = $message->from->id;
$name = $message->from->first_name;
$familya = $message->from->last_name;
$bio = $message->from->about;
$username = $message->from->username;
$chat_id = $message->chat->id;
$message_id = $message->message_id;
$reply = $message->reply_to_message->text;
$nameru = "<a href='tg://user?id=$uid'>$name $familya</a>";

$botdel = $alijonov->my_chat_member->new_chat_member;
$botdelid = $alijonov->my_chat_member->from->id;
$userstatus = $botdel->status;

//inline uchun metodlar
$data = $alijonov->callback_query->data;
$qid = $alijonov->callback_query->id;
$id = $alijonov->inline_query->id;
$query = $alijonov->inline_query->query;
$query_id = $alijonov->inline_query->from->id;
$cid2 = $alijonov->callback_query->message->chat->id;
$mid2 = $alijonov->callback_query->message->message_id;
$callfrid = $alijonov->callback_query->from->id;
$callname = $alijonov->callback_query->from->first_name;
$calluser = $alijonov->callback_query->from->username;
$surname = $alijonov->callback_query->from->last_name;
$about = $alijonov->callback_query->from->about;
$nameuz = "<a href='tg://user?id=$callfrid'>$callname $surname</a>";

//new mwthod
$update = json_decode(file_get_contents('php://input'));
$message = $update->message;
$cid = $message->chat->id;
$name = $message->chat->first_name;
$tx = $message->text;
$step = file_get_contents("step/$cid.step");
$mid = $message->message_id;
$type = $message->chat->type;
$text = $message->text;
$uid= $message->from->id;
$name = $message->from->first_name;
$familya = $message->from->last_name;
$bio = $message->from->about;
$username = $message->from->username;
$chat_id = $message->chat->id;
$message_id = $message->message_id;
$reply = $message->reply_to_message->text;
$nameru = "<a href='tg://user?id=$uid'>$name $familya</a>";
$announcement = file_get_contents("admin/announcement.txt");

$botdel = $update->my_chat_member->new_chat_member; 
$botdelid = $update->my_chat_member->from->id; 
$userstatus= $botdel->status; 

$contact = $message->contact;
$contact_id = $contact->user_id;
$contact_user = $contact->username;
$contact_name = $contact->first_name;
$phone = $contact->phone_number;


//inline uchun metodlar
$data = $update->callback_query->data;
$qid = $update->callback_query->id;
$id = $update->inline_query->id;
$query = $update->inline_query->query;
$query_id = $update->inline_query->from->id;
$cid2 = $update->callback_query->message->chat->id;
$mid2 = $update->callback_query->message->message_id;
$callfrid = $update->callback_query->from->id;
$callname = $update->callback_query->from->first_name;
$calluser = $update->callback_query->from->username;
$surname = $update->callback_query->from->last_name;
$about = $update->callback_query->from->about;
$nameuz = "<a href='tg://user?id=$callfrid'>$callname $surname</a>";

$photo = $message->photo;
$file = $photo[count($photo)-1]->file_id;
//top new method
if (isset($data)) {
	$chat_id = $cid2;
	$message_id = $mid2;
}

$photo = $message->photo;
$file = $photo[count($photo) - 1]->file_id;

//tugmalar
if (file_get_contents("tugma/key1.txt")) {
} else {
	if (file_put_contents("tugma/key1.txt", "🔎 Qidiruv"));
}

//pul va referal sozlamalar

if (file_get_contents("admin/valyuta.txt")) {
} else {
	if (file_put_contents("admin/valyuta.txt", "so'm"))
		;
}

if (file_get_contents("admin/vip.txt")) {
} else {
	if (file_put_contents("admin/vip.txt", "25000"))
		;
}

if (file_get_contents("admin/holat.txt")) {
} else {
	if (file_put_contents("admin/holat.txt", "Yoqilgan"))
		;
}

if (file_exists("admin/anime_kanal.txt") == false) {
	file_put_contents("admin/anime_kanal.txt", "@username");
}

//matnlar
if (file_get_contents("matn/start.txt")) {
} else {
	if (file_put_contents("matn/start.txt", "Assalomu aleykum botimizga xush kelibsiz (:"));
}

$res = mysqli_query($connect, "SELECT*FROM user_id WHERE user_id=$chat_id");
while ($a = mysqli_fetch_assoc($res)) {
	$user_id = $a['user_id'];
	$status = $a['status'];
	$taklid_id = $a['refid'];
	$from_id = $a['id'];
	$usana = $a['sana'];
}

$res = mysqli_query($connect, "SELECT*FROM kabinet WHERE user_id=$chat_id");
while ($a = mysqli_fetch_assoc($res)) {
	$k_id = $a['user_id'];
	$pul = $a['pul'];
	$pul2 = $a['pul2'];
	$odam = $a['odam'];
	$ban = $a['ban'];
}

$key1 = file_get_contents("tugma/key1.txt");


$test = file_get_contents("step/test.txt");
$test1 = file_get_contents("step/test1.txt");
$test2 = file_get_contents("step/test2.txt");
$turi = file_get_contents("tizim/turi.txt");
$anime_kanal = file_get_contents("admin/anime_kanal.txt");

$narx = file_get_contents("admin/vip.txt");
$kanal = file_get_contents("admin/kanal.txt");
$valyuta = file_get_contents("admin/valyuta.txt");
$start = str_replace(["%first%", "%id%", "%botname%", "%hour%", "%date%"], [$name, $cid, $bot, $soat, $sana], file_get_contents("matn/start.txt"));
$qollanma = str_replace(["%first%", "%id%", "%hour%", "%date%", "%user%", "%botname%",], [$name, $cid, $soat, $sana, $user, $bot], file_get_contents("matn/qollanma.txt"));
$from_id = mysqli_fetch_assoc(mysqli_query($connect, "SELECT*FROM user_id WHERE user_id = $cid2"))['id'];
$pul3 = mysqli_fetch_assoc(mysqli_query($connect, "SELECT*FROM kabinet WHERE user_id = $cid2"))['pul'];
$odam2 = mysqli_fetch_assoc(mysqli_query($connect, "SELECT*FROM kabinet WHERE user_id = $cid2"))['odam'];
$photo = file_get_contents("matn/photo.txt");
$homiy = file_get_contents("matn/homiy.txt");
$holat = file_get_contents("admin/holat.txt");

mkdir("tizim");
mkdir("step");
mkdir("admin");
mkdir("tugma");
mkdir("matn");

$panel = json_encode([
	'resize_keyboard' => true,
	'keyboard' => [
		[['text' => "*️⃣ Birlamchi sozlamalar"]],
		[['text' => "📊 Statistika"],['text' => "✉ Xabar Yuborish"]],
		[['text' => "📬 Post tayyorlash"]],
		[['text' => "🎥 Animelar sozlash"], ['text' => "💳 Hamyonlar"]],
		[['text' => "🔎 Foydalanuvchini boshqarish"]],
		[['text' => "📢 Kanallar"], ['text' => "🎛 Tugmalar"], ['text' => "📃 Matnlar"]],
		[['text' => "📋 Adminlar"], ['text' => "🤖 Bot holati"]],
        [['text' => "📌Konkurs"]],
		[['text' => "🎯 Shorts yuklash"],['text'=>"📣E'lon"]],
		[['text' => "◀️ Orqaga"]]
	]
]);

$asosiy = $panel;
//<-----@Itachi_uchiha_sono_sharingan ----->
$menu = json_encode([
    'inline_keyboard' => [
        [
            ['text' => "$key1", 'callback_data' => 'key1_action'],
        ],
        [
            ['text' => "💖 Obunalarim", 'callback_data' => 'subscribe'],
            ['text' => "🆓 Free Play", 'callback_data' => "shorts"],
        ],
        [
        ['text' => "📣 E'lonlar", 'callback_data' => 'elonlar'],        
        ]
    ]
]);

$asosiy = $panel;

$menu = json_encode([
    'inline_keyboard' => [
        [
            ['text' => "$key1", 'callback_data' => 'key1_action'],
            ['text' => "💖 Obunalarim", 'callback_data' => 'subscribe'],
        ],
        [
            ['text' => "🆓 Free Play", 'callback_data' => "shorts"],
        ],
        [
        ['text' => "📣 E'lonlar", 'callback_data' => 'elonlar'],
        ['text' => "⚡ versions", 'callback_data' => 'versions'], 
        ],
        [
            //['text'=>"🚀 Vibexe Ai",'callback_data'=>"Vibexe_ai"],
            ['text' => "🌐 Web Animes", 'web_app' => ['url' => "$web_urlis"]]
        ]
    ]
]);



$menus = json_encode([
    'inline_keyboard' => [
        [
            ['text' => "$key1", 'callback_data' => 'key1_action'],
            ['text' => "💖 Obunalarim", 'callback_data' => 'subscribe'],
        ],
        [
            ['text' => "🆓 Free Play", 'callback_data' => "shorts"],
        ],
        [
        ['text' => "📣 E'lonlar", 'callback_data' => 'elonlar'], 
        ['text' => "⚡ versions", 'callback_data' => 'versions'], 
        ],
        [
            //['text'=>"🚀 Vibexe Ai",'callback_data'=>"Vibexe_ai"],
            ['text' => "🌐 Web Animes", 'web_app' => ['url' => "$web_urlis"]]
        ]
    ]
]);


$inlineKeyboard = json_encode([
    'inline_keyboard' => [
        [
            ['text' => "🔖 Nom orqali", 'callback_data' => "searchByName"],
            ['text' => "🆔 Kod orqali", 'callback_data' => "searchByCode"]
        ],
        [
            ['text' => "📖 Barcha animelar ro‘yxati", 'callback_data' => "allAnimes"]
        ]
    ]
]);


if (isset($message)) {
	$result = mysqli_query($connect, "SELECT * FROM user_id WHERE user_id = $cid");
	$row = mysqli_fetch_assoc($result);
	if (!$row) {
		mysqli_query($connect, "INSERT INTO user_id(user_id,status,sana) VALUES ('$cid','Oddiy','$sana')");
	}
}

if (isset($message)) {
	$result = mysqli_query($connect, "SELECT user_id, pul, pul2, odam, ban FROM kabinet WHERE user_id = $cid");
	$row = mysqli_fetch_assoc($result);
	if (!$row) {
		mysqli_query($connect, "INSERT INTO kabinet(user_id,pul,pul2,odam,ban) VALUES ('$cid','0','0','0','unban')");
	}
}


if (($text == "/start" || $text == "◀️ Orqaga" ) && joinchat($cid) == true) {
    $filename = "view.txt";
    $logfile = "user_start.log";
    $currentHour = date('H'); 
    $currentSessionKey = $currentHour . '|' . $cid; 


    $logContent = file_exists($logfile) ? file($logfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];

    if (!empty($logContent)) {
        list($firstHour, ) = explode('|', $logContent[0]); 
        if ($firstHour !== $currentHour) {
            file_put_contents($logfile, "");
            $logContent = [];
        }
    }

    if (!in_array($currentSessionKey, $logContent)) {
        file_put_contents($logfile, $currentSessionKey . "\n", FILE_APPEND);

        $content = file_exists($filename) ? file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
        $updated = false;

        if (!empty($content)) {
            $lastLine = trim($content[count($content) - 1]);
            list($counter, $time) = explode('|', $lastLine);
            $lastHour = trim(explode(':', trim($time))[0]);

            if ($lastHour == $currentHour) {
                $newCount = intval(trim($counter)) + 1;
                $content[count($content) - 1] = "$newCount | " . $currentHour . ":00 ->";
                $updated = true;
            }
        }

        if (!$updated) {
            $content[] = "1 | " . $currentHour . ":00 ->";
        }

        file_put_contents($filename, implode("\n", $content));
    }

    $viewCount = 0;
    $lines = file_exists($filename) ? file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
    if (!empty($lines)) {
        $lastLine = end($lines);
        $viewCount = intval(trim(explode('|', $lastLine)[0]));
    }

    bot('sendPhoto',[
        'chat_id'=>$cid,
        'photo' => "https://t.me/astaex/263",
        'caption' => "👋 Assalomu alaykum, botimizga xush kelibsiz! 😊\n\n🎬 Hozirda botimizda <b>$viewCount</b> ta foydalanuvchi anime tomosha qilmoqda! 🍿✨\n\n💫 <b>Dasturchi:</b> <a href='https://t.me/obito_ae'>Ego</a>",
        'reply_markup'=>$menu,
        'parse_mode' => 'html',

    ]);

    unlink("step/$cid.step");
    unlink("shorts/$cid.shorts");
    unlink("step/$cid.payid");
    unlink("step/$cid.token");
    unlink("step/$cid.sum");
    delfile("profil/Playlists");


    exit();
}



$back = json_encode([
	'resize_keyboard' => true,
	'keyboard' => [
		[['text' => "◀️ Orqaga"]],
	]
]);

$boshqarish = json_encode([
	'resize_keyboard' => true,
	'keyboard' => [
		[['text' => "🗄 Boshqarish"]],
	]
]);

if (in_array($cid, $admin)) {
	$menyu = $menus;
} else {
	$menyu = $menu;
}

if (in_array($cid2, $admin)) {
	$menyus = $menus;
} else {
	$menyus = $menu;
}
// Dasturchi <--- @ITACHI_UCHIHA_SOONO_SHARINGAN -->\\


if ($data == "shorts" || $data == "shorts_random") {

    del();

    $vid = null;
    $caption = "";
    $anime_button = [];

    $check_shorts = mysqli_query($connect, "SELECT COUNT(*) as total FROM shorts");
    $check_row = mysqli_fetch_assoc($check_shorts);

    if ($check_row['total'] > 0) {
        $res = mysqli_query($connect, "SELECT * FROM shorts ORDER BY RAND() LIMIT 1");
        $row = mysqli_fetch_assoc($res);

        if ($row) {
            $vid = $row['shorts_id'];
            $caption = "<b>🎯 Shorts bo‘limiga xush kelibsiz</b>\nShorts nomi: " . $row['name'] .
                       "\n<b>Shorts ID:</b> <code>" . $row['id'] . "</code>";

            if ($row['anime_id'] != 0 && $row['anime_id'] !== 'NULL') {
                $caption .= "\n<b>📕 Bu shorts ning animesi mavjud</b>\n<b>Anime kodi:</b> " . $row['anime_id'];
                $anime_button = [['text' => "🖥 Tomosha qilish", 'url' => "https://t.me/$bot?start=" . $row['anime_id']]];
            } else {
                $caption .= "\n<b>📕 Bu shorts ning animesi mavjud emas</b>";
            }
        }
    } else {
        $res = mysqli_query($connect, "SELECT * FROM animelar WHERE rams LIKE 'B%' ORDER BY RAND() LIMIT 1");
        $anim = mysqli_fetch_assoc($res);
        if ($anim) {
            $vid = $anim['rams'];
            $caption = "<b>🎯 Anime short</b>\n" .
                       "<b>Nom:</b> " . $anim['nom'] . "\n<b>Anime ID:</b> <code>" . $anim['id'] . "</code>";
            $anime_button = [['text' => "🖥 Tomosha qilish", 'url' => "https://t.me/$bot?start=" . $anim['id']]];
        }
    }

    if (!$vid) {
        bot('SendMessage', [
            'chat_id' => $cid2,
            'text' => "⚠️ Hech qanday video topilmadi!",
        ]);
        exit();
    }

    $inline_keyboard = [];
    if (!empty($anime_button)) $inline_keyboard[] = $anime_button;
    $inline_keyboard[] = [['text' => "🔎 Keyingisi", 'callback_data' => 'shorts_random']];

    bot('SendVideo', [
        'chat_id' => $cid2,
        'video' => $vid,
        'caption' => $caption,
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => $inline_keyboard
        ])
    ]);
    exit();
}





// Dasturchi <--- @ITACHI_UCHIHA_SOONO_SHARINGAN -->\\

if($text == "◀️ Orqaga" and joinchat($cid) == true){        
    bot('SendMessage',[
    'chat_id'=>$cid,
    'text'=>"<b>🖥 Asosiy menyuga qaytdingiz.</b>",
    'parse_mode'=>'html',
    'reply_markup'=>$menus,
]);
unlink("step/$cid.step");
   unlink("step/test1.txt");
   unlink("step/test2.txt");
   unlink("step/test3.txt");
   unlink("step/test4.txt");
   unlink("step/test5.txt");
   unlink("step/test6.txt");
   unlink("step/test7.txt");
   unlink("step/test8.txt");
exit();
}

if ($data == "back") {
    $filename = "view.txt";
    $logfile = "user_start.log";
    $currentHour = date('H'); 
    $currentSessionKey = $currentHour . '|' . $cid; 


    $logContent = file_exists($logfile) ? file($logfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];

    if (!empty($logContent)) {
        list($firstHour, ) = explode('|', $logContent[0]); 
        if ($firstHour !== $currentHour) {
            file_put_contents($logfile, "");
            $logContent = [];
        }
    }

    if (!in_array($currentSessionKey, $logContent)) {
        file_put_contents($logfile, $currentSessionKey . "\n", FILE_APPEND);

        $content = file_exists($filename) ? file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
        $updated = false;

        if (!empty($content)) {
            $lastLine = trim($content[count($content) - 1]);
            list($counter, $time) = explode('|', $lastLine);
            $lastHour = trim(explode(':', trim($time))[0]);

            if ($lastHour == $currentHour) {
                $newCount = intval(trim($counter)) + 1;
                $content[count($content) - 1] = "$newCount | " . $currentHour . ":00 ->";
                $updated = true;
            }
        }

        if (!$updated) {
            $content[] = "1 | " . $currentHour . ":00 ->";
        }

        file_put_contents($filename, implode("\n", $content));
    }

    $viewCount = 0;
    $lines = file_exists($filename) ? file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
    if (!empty($lines)) {
        $lastLine = end($lines);
        $viewCount = intval(trim(explode('|', $lastLine)[0]));
    }
    
    bot('editMessageCaption', [
        'chat_id' => $cid2,
        'message_id' => $mid2,
        'caption' => "👋 Assalomu alaykum, botimizga xush kelibsiz! 😊\n\n🎬 Hozirda botimizda <b>$viewCount</b> ta foydalanuvchi anime tomosha qilmoqda! 🍿✨\n\n💫 <b>Dasturchi:</b> <a href='https://t.me/Itachi_uchiha_sono_sharingan'>Boltaboyev Rahmatillo</a>",
        'parse_mode' => 'html',
        'reply_markup' => $menu
    ]);

    @unlink("step/$cid2.step");
    for ($i = 1; $i <= 8; $i++) {
        @unlink("step/test$i.txt");
    }

    exit();
}


if ($data == "Vibexe_ai") {
    unlink("Chat/$cid2.chat");
    bot('editMessageCaption', [
        'chat_id' => $cid2,
        'message_id' => $mid2,
        'caption' => "🚀 Vibexe AI bo'limiga xush kelibsiz!\n\n<b>So'rovingizni kiriting...</b>",
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "◀️ Ortga", 'callback_data' => 'back']]
            ]
        ]),
        'parse_mode' => 'html'
    ]);
    put("step/$cid2.step", 'Vibexe_ai');
}


if ($step == 'Vibexe_ai') {
    if (isset($text)) {

        $chatFile = "Chat/$cid.chat";
        $prevChat = file_exists($chatFile) ? file_get_contents($chatFile) : "";

        $lines = explode("\n", trim($prevChat));
        $lines[] = $text; 
        $lines = array_slice($lines, -50);
        file_put_contents($chatFile, implode("\n", $lines));

$recentContext = array_slice($lines, -50);
$userMessage = end($recentContext);

$prompt = "Suhbat quyidagicha:\n" . implode("\n", $recentContext) . "\n\n" .
          "Iltimos, faqat oxirgi chatga ko'proq etibor ber va yaxshilab o'ylab javob ber !:\n" .
          "\"$userMessage\"\n\n" .
          "Javob berishda batafsil javob ber ammo batafsil javob berman deb yozma judako'p ham yozma juda qisqacha ham yozma  o'rtacha yoz ! Agar sendan o'zim haqimda nimadir so'rasam shu chatga qarab javob ber ! menga Sizning talablaringizni tushundim. Men sizning so'rovlaringizga e'tibor bilan javob berishga harakat qilaman va javoblarim o'rtacha uzunlikda bo'lishiga ahamiyat beraman. Agar siz o'zingiz haqida ma'lumot bersangiz, keyingi javoblarimda shu ma'lumotlardan foydalanaman. bunga o'xshash matn yozma !";

if ($isShortGreeting) {
    $prompt .= "\n\n⚠️ Salomlashuv uchun chiroyli javoblar ber. Tanishtiruvchi matn yozilmasin. Insonlarni yomon ko'radigandek javob ber ! Salom berganda Alik ol !";
}


        // Loading animatsiya
        $msg = bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "💡 Loading..",
        ]);
        $mid = $msg->result->message_id;

        for ($i = 1; $i <= 3; $i++) {
            bot('editMessageText', [
                'chat_id' => $cid,
                'message_id' => $mid,
                'text' => "💡 Loading" . str_repeat('.', $i),
            ]);
            usleep(500000); // 0.5 soniya kutish
        }

        // API so‘rovi
        bot('editMessageText', [
            'chat_id' => $cid,
            'message_id' => $mid,
            'text' => "🔍",
        ]);
        sleep(1);

        $apiUrl = "https://vibexe.uz/API/AI/index.php";
        $postData = json_encode([
            "query" => $prompt,
            "version" => "vibexe-2.1"
        ]);

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        $apiResponse = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($apiResponse, true);
        $answer = $result['answer'] ?? "⚠️ Javob olinmadi.";

        // Belgilarni tozalash
        $answer = preg_replace('/\*\*(.*?)\*\*/', '$1', $answer);
        $answer = preg_replace('/^\\*\\s+/m', '• ', $answer);
        $answer = htmlspecialchars_decode($answer, ENT_QUOTES | ENT_HTML5);

        // Kod bloklarini ajratish
        $answer = preg_replace_callback('/```(php)?(.*?)```/s', function ($matches) {
            $code = trim($matches[2]);
            return "<pre><code>" . htmlspecialchars($code) . "</code></pre>";
        }, $answer);

        // Faqat kerakli HTML taglar qoldiriladi
        $answer = strip_tags($answer, '<b><i><pre><code>');
        
        $introText = "Men Vibexe AI, AnimeLiveUz jamoasi tomonidan ishlab chiqilgan ilg‘or sun'iy intellekt modeliman. Mening vazifam foydalanuvchilarga turli mavzularda to‘liq, batafsil va aniq javoblar berishdir. Sun'iy intellekt texnologiyasi yordamida men savollaringizga mantiqiy va chuqur javoblar taqdim eta olaman, shu jumladan til, madaniyat, texnologiya va boshqa ko‘plab sohalarda. Vibexe AI doimiy ravishda o‘rganadi va yangilanadi, shuning uchun mening javoblarim eng so‘nggi va to‘g‘ri ma'lumotlarga asoslangan bo‘ladi. Men foydalanuvchilar bilan insoniy muloqot qilishga intilaman, shuningdek, har bir savolga alohida e'tibor bilan yondashaman. Sizga yanada yaxshiroq yordam berish uchun, iltimos, savollaringizni aniq va tushunarli shaklda berishga harakat qiling. Vibexe AI modeli tabiatini hisobga olgan holda, men har doim foydali va ijobiy javoblar berishga intilaman.";

$answer = str_replace($introText, "", $answer);

        
        // Juda uzun bo‘lsa sahifalab yuboriladi
        $maxLength = 4000;
        if (mb_strlen($answer) > $maxLength) {
            $chunks = str_split($answer, $maxLength);
            foreach ($chunks as $index => $chunk) {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "🤖 <b>Vibexe AI (" . ($index + 1) . "/" . count($chunks) . "):</b>\n\n$chunk",
                    'parse_mode' => 'html'
                ]);
                sleep(1);
            }
        } else {
            bot('editMessageText', [
                'chat_id' => $cid,
                'message_id' => $mid,
                'text' => "🤖 <b>Vibexe AI:</b>\n\n$answer",
                'parse_mode' => 'html'
            ]);
        }
    }
}








$res = mysqli_query($connect, "SELECT * FROM saved WHERE `user_id` = '$cid2'");
$total_user_count = 0;
$keyboard = [];

if ($data == 'subscribe') {
    mysqli_query($connect, "
        DELETE t1 FROM saved t1
        INNER JOIN saved t2 
        WHERE 
            t1.id > t2.id AND 
            t1.user_id = t2.user_id AND 
            t1.anime_id = t2.anime_id
    ");

    $res = mysqli_query($connect, "SELECT * FROM saved WHERE `user_id` = '$cid2'");
    $total_user_count = 0;
    $keyboard = [];

    if (mysqli_num_rows($res) > 0) {
        while ($row = mysqli_fetch_assoc($res)) {
            $total_user_count++;
            $anime_name = mb_substr($row['anime_name'], 0, 25);
            $startss = $row['anime_id'];

            $keyboard[] = [[
                'text' => $anime_name,
                'url' => "https://t.me/AniboUz_bot?start=" . $startss
            ]];
        }

        $keyboard = array_chunk(array_map('reset', $keyboard), 1);
        $kb = json_encode(['inline_keyboard' => $keyboard]);

        bot('editMessageCaption', [
            'chat_id' => $cid2,
            'message_id' => $mid2,
            'caption' => "<b>Siz yoqtirgan animelar soni:</b> <code>$total_user_count</code> \n<b>Siz yoqtirgan animelar ro'yhati:</b>",
            'reply_markup' => $kb,
            'parse_mode' => 'html'
        ]);
    } else {
        del();
        bot('sendMessage', [
            'chat_id' => $cid2,
            'text' => "🥺 Siz hali birorta ham animeni yoqtirmagansiz"
        ]);
    }
    exit();
}

if ($text == "/start"){
    unlink("Chat/$cid.chat"); 
}

if (mb_stripos($text, "/start ") !== false && $text != "/start anipass") {
    $filename = "view.txt";
    $logfile = "user_start.log";
    $currentHour = date('H'); 
    $currentSessionKey = $currentHour . '|' . $cid; 


    $logContent = file_exists($logfile) ? file($logfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];

    if (!empty($logContent)) {
        list($firstHour, ) = explode('|', $logContent[0]); 
        if ($firstHour !== $currentHour) {
            file_put_contents($logfile, "");
            $logContent = [];
        }
    }

    if (!in_array($currentSessionKey, $logContent)) {
        file_put_contents($logfile, $currentSessionKey . "\n", FILE_APPEND);

        $content = file_exists($filename) ? file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
        $updated = false;

        if (!empty($content)) {
            $lastLine = trim($content[count($content) - 1]);
            list($counter, $time) = explode('|', $lastLine);
            $lastHour = trim(explode(':', trim($time))[0]);

            if ($lastHour == $currentHour) {
                $newCount = intval(trim($counter)) + 1;
                $content[count($content) - 1] = "$newCount | " . $currentHour . ":00 ->";
                $updated = true;
            }
        }

        if (!$updated) {
            $content[] = "1 | " . $currentHour . ":00 ->";
        }

        file_put_contents($filename, implode("\n", $content));
    }

    $viewCount = 0;
    $lines = file_exists($filename) ? file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
    if (!empty($lines)) {
        $lastLine = end($lines);
        $viewCount = intval(trim(explode('|', $lastLine)[0]));
    }

    $start_params = str_replace(['/start ', '+'], '', $text);
    $params = explode('=', $start_params);

    $id = null;
    $qism = null;

    if (count($params) >= 1) {
        $id = intval($params[0]);
    }
    if (count($params) == 2) {
        $qism = intval($params[1]);
    }
    
    if ($id === null || joinchat($cid, $id) != 1) {
        sms($cid, $start, $menyu);
        exit();
    }

    if ($qism !== null) {
        $res = mysqli_query($connect, "SELECT * FROM anime_datas WHERE id = $id AND qism = $qism");
        $row = mysqli_fetch_assoc($res);
    } else {
        $res = mysqli_query($connect, "SELECT * FROM anime_datas WHERE id = $id ORDER BY qism ASC LIMIT 1");
        $row = mysqli_fetch_assoc($res);
        if ($row) {
            $qism = $row['qism'];
        }
    }

    $ren = mysqli_query($connect, "SELECT * FROM animelar WHERE id = $id");
    $rown = mysqli_fetch_assoc($ren);

    if (!$row || !$rown) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "❌ Ushbu qism topilmadi yoki anime mavjud emas.",
        ]);
        exit();
    }

    $file_id = $rown['rams'] ?? '';
    $first_char = strtoupper($file_id[0] ?? '');
    $media_type = ($first_char == 'B') ? 'sendVideo' : 'sendPhoto';
    $media_key = ($media_type === 'sendVideo') ? 'video' : 'photo';

    if (count($params) == 2) {
        bot('sendVideo', [
            'chat_id' => $cid,
            'video' => $row['file_id'], 
            'caption' => "Anime: <b>" . ($rown['nom'] ?? 'Noma\'lum') . "</b>\nQism: <b>" . $qism . "</b>",
            'parse_mode' => 'html',
        ]);
    } else {
        $likes = $rown['like'] ?? 0;
        $dislikes = $rown['deslike'] ?? 0;

        $rewn = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM saved WHERE anime_id = $id AND user_id = $cid"));
        $anim_type = $rown['aniType'] ?? '';

        if ($rewn || $anim_type == 'tezkor') {
            $inline_keyboard = [
                [['text' => "🔥 Tezkor yuklash 🔥", 'callback_data' => "tezyuklash=$id=1"]],
                [['text' => "♥️ $likes", 'callback_data' => "like_anime"], ['text' => "💔 $dislikes", 'callback_data' => "des_like_anime"]],
                [['text' => "🗑 Unitish", 'callback_data' => "delete_playlists"]],
            ];
        } else {
            $inline_keyboard = [
                [['text' => "💎 Tomosha qilish 💎", 'callback_data' => "yuklanolish=$id=1"]],
                [['text' => "♥️ $likes", 'callback_data' => "like_anime"], ['text' => "💔 $dislikes", 'callback_data' => "des_like_anime"]],
                [['text' => "♥️ Saqlash $cid", 'callback_data' => "saved_playlists"]],
            ];
        }

        bot($media_type, [
            'chat_id' => $cid,
            $media_key => $file_id,
            'caption' => "<b>Anime nomi:</b> " . ($rown['nom'] ?? 'Noma\'lum') . "\n<b>Qism:</b> " . ($qism ?? '?') . "\n<b>Anime ID:</b> <code>$id</code>",
            'parse_mode' => "html",
            'reply_markup' => json_encode([
                'inline_keyboard' => $inline_keyboard
            ])
        ]);
    }

    exit();
}








$anime_id = file_get_contents('profil/Playlists/' . $cid2 . '.id.txt');
$like = file_get_contents('profil/likes/' . $cid2 . '.like.txt');
$deslike = file_get_contents('profil/likes/' . $cid2 . '.deslike.txt');


$res = mysqli_query($connect, "SELECT * FROM likes WHERE `user_id` = $cid2 AND `anime_id` = $anime_id");
$row = mysqli_fetch_assoc($res);
$result = mysqli_query($connect, "SELECT * FROM deslikes WHERE `user_id` = $cid2 AND `anime_id` = $anime_id");
$rowdes = mysqli_fetch_assoc($result);

if ($data == 'like_anime') {
    if (empty($row['user_id'])) {
        $check_like = mysqli_query($connect, "SELECT user_liked FROM animelar WHERE id = $anime_id AND user_liked = 1");
        
        if (mysqli_num_rows($check_like) > 0) {
bot('answerCallbackQuery', [
    'callback_query_id' => $qid,
    'text' => "❗ Siz bu animeni allaqachon yoqtirgansiz!",
    'show_alert' => true
]);

        } else {
            $likes = (int)$like + 1;
            $query = "UPDATE animelar SET `like` = $likes WHERE `id` = $anime_id";
            
            if (mysqli_query($connect, $query)) {
                if ($connect->query("INSERT INTO `likes` (`user_id`, `anime_id`) VALUES ('$cid2', '$anime_id')") === TRUE) {
                    $code = $connect->insert_id;
                    bot('answerCallbackQuery', [
    'callback_query_id' => $qid,
    'text' => "❤️️ Siz bu anime ni allaqachon yoqtirgansiz!",
    'show_alert' => true
]);

                    unlink("profil/Playlists/$cid2.id.txt");
                    unlink("profil/likes/$cid2.like.txt");
                    unlink("profil/likes/$cid2.deslike.txt");
                    unlink("profil/Playlists/$cid2.name.txt");
                	unlink("profil/Playlists/$cid2.image.txt");
                	unlink("profil/Playlists/$cid2.id.txt");
                    exit();
                } else {
                    bot('answerCallbackQuery', [
    'callback_query_id' => $qid,
    'text' => "⚠️ Xatolik!\n{$connect->error}",
    'show_alert' => true,
    'parse_mode'=>'html'
]);

                    unlink("profil/Playlists/$cid2.id.txt");
                    unlink("profil/Playlists/$cid.name.txt");
                    unlink("profil/Playlists/$cid.image.txt");
                    unlink("profil/Playlists/$cid2.name.txt");
	                unlink("profil/Playlists/$cid2.image.txt");
                	unlink("profil/Playlists/$cid2.id.txt");
                    exit();
                }
            } else {
        bot('answerCallbackQuery', [
    'callback_query_id' => $qid,
    'text' => "❤️️ Siz bu anime ni allaqachon yoqtirgansiz",
    'show_alert' => true
]);

                    unlink("profil/Playlists/$cid2.id.txt");
                    unlink("profil/Playlists/$cid2.name.txt");
                    unlink("profil/Playlists/$cid2.image.txt");
            }
        }
    } else {
        bot('sendMessage', [
            'chat_id' => $cid2,
            'text' => "❤️️ Siz bu anime ni allaqachon yoqtirgansiz",
        ]);
                    unlink("profil/Playlists/$cid2.id.txt");
                    unlink("profil/Playlists/$cid2.name.txt");
                    unlink("profil/Playlists/$cid2.image.txt");
    }
}

if ($data == 'des_like_anime') {
    if (empty($rowdes['user_id'])) {
        if (isset($anime_id)) {
            $check_like = mysqli_query($connect, "SELECT user_liked FROM animelar WHERE id = $anime_id AND user_liked = 1");
            
            if (mysqli_num_rows($check_like) > 0) {
                bot('sendMessage', [
                    'chat_id' => $cid2,
                    'text' => "❤️️ Siz bu anime ni allaqachon yoqtirmagansiz!",
                ]);
            } else {
                $likes = (int)$deslike + 1;
                $query = "UPDATE animelar SET `deslike` = $likes WHERE `id` = $anime_id";
                
                if (mysqli_query($connect, $query)) {
                    if ($connect->query("INSERT INTO `deslikes` (`user_id`, `anime_id`) VALUES ('$cid2', '$anime_id')") === TRUE) {
                        $code = $connect->insert_id;
                        sms($cid2, "<b>❤️ Bu anime sizga yoqmadi</b>\n\n<b> Anime ID:</b>  <code>$anime_id</code>", $menu);
                        unlink("profil/Playlists/$cid2.id.txt");
                        unlink("profil/likes/$cid2.like.txt");
                        unlink("profil/likes/$cid2.deslike.txt");
                        exit();
                    } else {
                        sms($cid2, "<b>⚠️ Xatolik!</b>\n\n<code>{$connect->error}</code>", $panel);
                        unlink("profil/Playlists/$cid2.id.txt");
                        unlink("profil/Playlists/$cid.name.txt");
                        unlink("profil/Playlists/$cid.image.txt");
                        unlink("profil/likes/$cid2.like.txt");
                        unlink("profil/likes/$cid2.deslike.txt");
                        exit();
                    }
                }
            }
        }
    } else {
        bot('sendMessage', [
            'chat_id' => $cid2,
            'text' => "❤️️ Siz bu anime ni allaqachon yoqmagan",
        ]);
                        unlink("profil/likes/$cid2.like.txt");
                        unlink("profil/likes/$cid2.deslike.txt");
                        exit();
    }
}

if ($text == "WebApp") {
    sms($cid, "WebApp", json_encode([
        'inline_keyboard' => [
            [
                [
                    'text' => 'WebApp',
                    'web_app' => ['url' => 'https://vibexe.uz/AnimeLiveUz/WebApp/view.html']
                ]
            ]
        ]
    ]));
    exit();
}


$anime_id = file_get_contents('profil/Playlists/' . $cid2 . '.id.txt');

if($data == 'delete_playlists'){
    mysqli_query($connect,"DELETE FROM saved WHERE anime_id = $anime_id AND user_id = $cid2");

    bot('answerCallbackQuery',[
        'callback_query_id' => $qid, 
        'text' => "😔 Bu anime playlistingizdan o‘chirildi!",
        'show_alert' => true 
    ]);
}

            
if ($data == "versions") {
    bot('editMessageCaption', [
        'chat_id' => $cid2,
        'message_id' => $mid2,
        'caption' => "📢 *Bot 2.0.0 versiyaga yangilandi!* 🎉\n\n*🆕 Yangi:*\n• Free Play bo‘limi\n• Obunalar sahifasi\n• Tugmalar dizayni\n• Alert xabarlari\n• Rasmli qidiruv\n• /start statistikasi\n\n*⚙️ Tuzatishlar:*\n• Tezlik oshdi\n• Xatolar tuzatildi\n• Kod barqaror\n\n*👨‍💻 Dasturchi:* [Ego](https://t.me/obito_ae)",
        'parse_mode' => 'Markdown',
        'reply_markup'=>json_encode([
            'inline_keyboard'=>[
                [['text'=>"2.0.0 version",'callback_data'=>'2_0_0']],
                [['text'=>"Ortga",'callback_data'=>'back']]
                ],
            ]),
    ]);
}




if ($data == 'anime_search') {
    bot('sendMessage', [
        'chat_id' => $cid2,
        'text' => "Anime nomini kiriting faqat 2 ta xarf",
    ]);
    file_put_contents('profil/Searchanime/malumot/' . $cid2 . '.malumot.txt', 'malumot');
    exit();
}

$malumot = file_get_contents("profil/Searchanime/malumot/$cid.malumot.txt");

if ($malumot == 'malumot') {
    if (isset($text)) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "🔍",
        ]);

        $servername = "localhost";
        $username = "c661_animelarfx";
        $password = "Bobo2008";
        $dbname = "c661_animelarfx";

        $conn = mysqli_connect($servername, $username, $password, $dbname);

        if (!$conn) {
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "❌ Baza bilan ulanishda xatolik: " . mysqli_connect_error(),
            ]);
            exit();
        }

        $query = "SELECT * FROM anime_data";
        $result = mysqli_query($conn, $query);

        if ($result && mysqli_num_rows($result) > 0) {
            $bestMatch = null;
            $highestSimilarity = 0;

            while ($row = mysqli_fetch_assoc($result)) {
                $animeName = $row['anime_name'];
                similar_text(strtolower($text), strtolower($animeName), $percent);

                if ($percent > $highestSimilarity) {
                    $highestSimilarity = $percent;
                    $bestMatch = $row;
                }
            }

            if ($highestSimilarity > 50) { 
             bot('sendMessage', [
    'chat_id' => $cid,
    'text' => "✅ Topildi!\n\nAnime nomi: " . $bestMatch['anime_name'],
    'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "📕". $bestMatch['anime_name'] ." animesi haqida ma'lumot", 'callback_data' => 'details_' . $bestMatch['id']]],
                [['text' => '🔍 YouTubedan Qidirish', 'url' => 'https://www.youtube.com/results?search_query=' . $text]],
            ]
        ]),
]);
unlink("profil/Searchanime/malumot/$cid.malumot.txt");
            } else {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "❌ Siz yuborgan \"$text\" animesiga mos keladigan ma'lumot topilmadi.",
                    'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => '🔍 Googledan Qidirish', 'url' => 'https://www.google.com/search?q=' . $text]],
                [['text' => '🔍 YouTubedan Qidirish', 'url' => 'https://www.youtube.com/results?search_query=' . $text]],
            ]
        ]),
                ]);
            }
            unlink("profil/Searchanime/malumot/$cid.malumot.txt");
        } else {
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "❌ Bazada hech qanday anime topilmadi.",
            ]);
        }
        unlink("profil/Searchanime/malumot/$cid.malumot.txt");
    }
    exit();
}

if (strpos($data, 'details_') === 0) {
    $id = substr($data, 8); 
 $servername = "localhost";
        $username = "c661_animelarfx";
        $password = "Bobo2008";
        $dbname = "c661_animelarfx";

        $conn = mysqli_connect($servername, $username, $password, $dbname);

        $result = mysqli_query($conn, "SELECT * FROM anime_data WHERE id = '$id'");

    if ($row = mysqli_fetch_assoc($result)) {
        bot('sendPhoto', [
            'chat_id' => $cid2,
            'photo'=>$photo,
            'caption' => "📕 Anime nomi: " . $row['anime_name'] . "\n📖 Tavsif: " . $row['description'],
            'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "💎 Tomosha qilish", 'url' => 'https://t.me/AniboUz_bot?start=' . $row['anime_id']]],
            ]
        ]),
        ]);
    } else {
        bot('sendMessage', [
            'chat_id' => $cid2,
            'text' => "Anime topilmadi.",
        ]);
    }
            mysqli_close($conn);
}


if ($data == 'aiopen') {
    if ($cid2 == 7775806579) {
        sms($cid2, "A+ AI bo'limiga xush kelibsiz qanday yordam bera olaman 😊", null);
        put("profil/AI/$cid2.ai.txt", 'name');
        exit();
    } else {
        sms($cid2, "<b>😔 Bu bo'lim xozirgu kunda faqat A+ sodiqlik dasturi a'zolari uchun mavjud</b>", null);
    }
}

$ai = file_get_contents("profil/AI/$cid.ai.txt");

if ($ai == 'name') {
    if (isset($text)) {
        $userMessage = trim($text);

        try {
            $malumot = $pdo->prepare("SELECT * FROM malumot");
            $malumot->execute();
            $aiu = $malumot->fetchAll();
        } catch (PDOException $e) {
            exit();
        }

        $highestMatch = 0;
        $bestAnswers = [];

        foreach ($aiu as $row) {
            $ques = $row['ques'];
            $ans = $row['ans'];

            $quesParts = explode('||', $ques);
            $ansParts = explode('||', $ans); // Javoblarni bo‘laklarga ajratamiz

            foreach ($quesParts as $part) {
                $distance = levenshtein(trim($userMessage), trim($part));
                $maxLength = max(strlen(trim($userMessage)), strlen(trim($part)));
                $percent = (1 - $distance / $maxLength) * 100;

                if ($percent >= 35) {
                    if ($percent > $highestMatch) {
                        $highestMatch = $percent;
                        $bestAnswers = $ansParts; // Barcha bo'laklarni qo'shamiz
                    } elseif ($percent == $highestMatch) {
                        $bestAnswers = array_merge($bestAnswers, $ansParts); // Oldingi variantlarga qo'shamiz
                    }
                    break;
                }
            }
        }

        if (!empty($bestAnswers)) {
            $randomAnswer = trim($bestAnswers[array_rand($bestAnswers)]);
            $randomAnswer = "😊 " . $randomAnswer . " 🌟";
            $randomAnswer = "<b><i>" . $randomAnswer . "</i></b>";
            sms($cid, $randomAnswer, null);
            unlink("profil/AI/$cid.ai.txt");
            exit();
        } else {
            sms($cid,"Iltimos menga bu savlning javobini o'rgating",json_encode(['inline_keyboard'=>[[['text'=>"Ma'lumot kiritish",'callback_data'=>'malumots']]]]));
            put("profil/AI/ques.txt",$text);
            unlink("profil/AI/$cid.ai.txt");
            exit();
        }
        exit();
    }
}


if($data == 'back'){
    bot('editMessageCaption',[
        'chat_id' => $cid2,
        'message_id' => $mid2,
        'caption' => "👋 Assalomu alaykum, botimizga xush kelibsiz! 😊\n\n🎬 Hozirda botimizda <b>$viewCount</b> ta foydalanuvchi anime tomosha qilmoqda! 🍿✨\n\n💫 <b>Dasturchi:</b> <a href='https://t.me/Itachi_uchiha_sono_sharingan'>Boltaboyev Rahmatillo</a>",
        'reply_markup' => $menu,
        'parse_mode' => 'html',
    ]);
}

if($data == "malumots"){
    del();
    sms($cid2,"Iltimos savolingiz javobini kiritsangiz",null);
    put("profil/AI/$cid2.ai.txt",'malumot');
}

if ($ai == 'malumot') {
    // if (isset($text)) {
    //     $sana = date('H:i d.m.Y');

    //     // Oldingi savollarni olish
    //     $texti = get("profil/AI/ques.txt");

    //     // Savol va javobdagi "|" belgilarini "||" ga almashtirish
    //     $ques = str_replace('|', '||', $texti);
    //     $ans = str_replace('|', '||', $text);

    //     // Ma'lumotni faylga saqlash
    //     file_put_contents("profil/AI/ques.txt", $ques);

    //     $servername = "localhost";
    //     $username = "c661_animelarfx";
    //     $password = "Bobo2008";
    //     $dbname = "c661_animelarfx";

    //     $conn = mysqli_connect($servername, $username, $password, $dbname);

    //     if (!$conn) {
    //         bot('sendMessage', [
    //             'chat_id' => $cid,
    //             'text' => "❌ Baza bilan ulanishda xatolik: " . mysqli_connect_error(),
    //         ]);
    //         exit();
    //     }
    //     mysqli_query($conn, "INSERT INTO malumot(`ques`,`ans`,`date`) VALUES ('$ques','$ans','$sana')");
            
    //         sms($cid, "Rahmat! Men bu ma'lumotni eslab qolaman! 😊");
    // } else {
    //         sms($cid, "❌ Xatolik: Ma'lumot kiritishda muammo yuzaga keldi.");
    // }
    // mysqli_close($conn); 
}




if ($data == "statistika_data") {

$res = mysqli_query($connect, "SELECT * FROM `kabinet`");
$stat = mysqli_num_rows($res); 

$anime_res = mysqli_query($connect, "SELECT id FROM `animelar` ORDER BY id DESC LIMIT 1");
$anime_data = mysqli_fetch_assoc($anime_res);
$last_anime_id = $anime_data['id'];

$today = date('d.m.Y');
$today_res = mysqli_query($connect, "SELECT COUNT(*) as count FROM `kabinet`");
$today_result = mysqli_query($connect, "SELECT COUNT(*) as count FROM `user_id` WHERE `sana` = '$today'");

$user_data = mysqli_fetch_assoc($today_result);

if ($user_data) { 
    $new_users_today = $user_data['count'];
} else {
    $new_users_today = 0; 
}

  $ping = sys_getloadavg()[0];
  sms($cid2,"💡 <b>O'rtacha yuklanish:</b> <code>$ping</code>\n\n👥 <b>Foydalanuvchilar:</b> $stat ta\n\n📂 <b>Barcha yuklangan animelar:</b> $last_anime_id ta\n\n📅 <b>Bugun qo'shilgan foydalanuvchilar:</b> $new_users_today ta",json_encode([
          'inline_keyboard'=>[
              [['text'=>"Orqaga",'callback_data'=>"back"]]
          ]
      ]));
  
//   bot('SendMessage',[
//       'chat_id'=>$cid2,
//       'text'=>"💡 <b>O'rtacha yuklanish:</b> <code>$ping</code>

// 👥 <b>Foydalanuvchilar:</b> $stat ta

// 📂 <b>Barcha yuklangan animelar:</b> $last_anime_id ta

// 📅 <b>Bugun qo'shilgan foydalanuvchilar:</b> $new_users_today ta",
//       'parse_mode'=>'html',
//       'reply_markup'=>json_encode([
//           'inline_keyboard'=>[
//               [['text'=>"Orqaga",'callback_data'=>"boshqarish"]]
//           ]
//       ])
//   ]);
   exit();
}

//Hisobim

if ($data == "key3_data") {
    $res = mysqli_query($connect, "SELECT * FROM `kabinet`");
    $stat = mysqli_num_rows($res); 

    bot('deleteMessage', [
        'chat_id' => $cid2,
        'message_id' => $mid2,
    ]);
    
    bot('SendMessage', [
        'chat_id' => $cid2,
        'text' => "#ID: <code>$cid2</code> \n BALANS: $pul $valyuta",
        'parse_mode' => 'html',
        'reply_markup' => $orqagastat,
    ]);
    
    file_put_contents("step/$cid2.step", "qo'shish");
    exit();
}

//Ko'nkurs
if ($text == "🎁 Konkurs" and joinchat($cid) == true) {
	if ($giveStatus == "off") $stat = "\n-\n<b>🔴Konkurs tugagan</b>";
	sms($cid, "$giveText\n────────────────\n<b>🔥Sizning taklif havolangiz :</b> <code>https://t.me/$bot?start=giveaway_$cid</code>\n-\n<b>🖇Sizning takliflaringiz :</b> $odam".$stat."", json_encode(['inline_keyboard' => [
		[['text' => "⚡️ Top ishtirokchilar",'callback_data' => "topUsers"]]
	]]));
	exit();
}

if ($data == "topUsers") {
	accl($qid, "Kuting...");
	$result = mysqli_query($connect, "SELECT * FROM `kabinet` ORDER BY `odam` DESC LIMIT 10");
     $i = 1;
	$text = "<b>🎁Top Konkurs ishtrokchilari</b>\n-";
     while ($row = mysqli_fetch_assoc($result)) {
		if ($i == 1) $icon = "🥇";
		elseif ($i == 2) $icon = "🥈";
		elseif ($i == 3) $icon = "🥉";
		else $icon = "$i";

		$getchat = bot('getchat', ['chat_id' => $row['user_id']])->result->first_name;
		$getchat = strip_tags($getchat);

		$text.= "\n$icon. <a href='tg://user?id=" . $row['user_id'] . "'>" . $getchat . "</a> - " . $row['odam'] . "🔗";
		$i++;
	}
	$res = edit($cid2, $mid2, $text, json_encode(['inline_keyboard' => [
		[['text' => "❗️Shartlar",'callback_data' => "giveRules"]]
	]]));
}

if ($data == "giveRules") {
	if ($giveStatus == "off") $stat = "\n-\n<b>🔴Konkurs tugagan</b>";
	edit($cid2, $mid2, "$giveText\n────────────────\n<b>🔥Sizning taklif havolangiz :</b> <code>https://t.me/$bot?start=giveaway_$cid</code>\n-\n<b>🖇Sizning takliflaringiz :</b> $odam".$stat."", json_encode(['inline_keyboard' => [
		[['text' => "⚡️ Top ishtirokchilar",'callback_data' => "topUsers"]]
	]]));
}
//USHBU KODNING USHBU QISMI <----@Padshakh_dev----> tomonidan tuzatildi MANBAGA TEGMANG !//

if ($data == "elonlar" or $data=="elonlarnew" and joinchat($cid)) {
    del();
    if ($announcement == null) {
		sms($cid2, "<b>🗞Xozirda hech qanday yangiliklar mavjud emas !</b>\n📌Bizni kuzatishda davom eting.", null);
		exit();
    } else {
    $announInfoJson = json_decode(file_get_contents("admin/announcement.json"), true);
	$info = "⛩•°•°•°•°•°•°💥°•°•°•°•°•°•⛩\n\n" . $announInfoJson['text'] . "\n\n-\n🕐 : " . $announInfoJson['created'];
   	sms($cid2, $info, json_encode(['inline_keyboard' => [
			[['text' => "👍" . count($announInfoJson['likes']),'callback_data'=>"announReact=likes"],['text' => "🔥" . count($announInfoJson['fires']),'callback_data'=>"announReact=fires"],['text' => "❤️" . count($announInfoJson['loves']),'callback_data'=>"announReact=loves"]],
			[['text' => "❄️" . count($announInfoJson['okeys']),'callback_data'=>"announReact=okeys"]]
		]]));
		exit();
	}
    sms($cid2,$info,$orqagastat);
    
    file_put_contents("step/$cid2.step", "qo'shish");
    exit();
}
if (stripos($data,"announReact=")!==false) {
	$ty = str_replace("announReact=","",$data);
	if ($announcement == null) {
		accl($qid,"No'malum xatolik !",1);
		del();
		sms($cid2,"<b>✅ /start ezing !</b>",json_encode(['remove_keyboard'=>true]));
	} else {
		$announInfoJson = json_decode(file_get_contents("admin/announcement.json"), true);
		$likes = $announInfoJson['likes'];
		$fires = $announInfoJson['fires'];
		$loves = $announInfoJson['loves'];
		$okeys = $announInfoJson['okeys'];
		$checkUseReacts = array_merge($likes,$fires,$loves,$okeys);
		if (in_array($cid2,$checkUseReacts)) {
			accl($qid,"🙃Siz allaqachon reaksiya bosgansiz");
			exit();
		} else {
			$announInfoJson[$ty][] = $cid2;
			file_put_contents("admin/announcement.json", json_encode($announInfoJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
			$announInfoJson = json_decode(file_get_contents("admin/announcement.json"), true);
			$info = "⛩•°•°•°•°•°•°💥°•°•°•°•°•°•⛩\n\n" . $announInfoJson['text'] . "\n\n-\n🕐 : " . $announInfoJson['created'];
			edit($cid2, $mid2, $info, json_encode(['inline_keyboard' => [
				[['text' => "👍" . count($announInfoJson['likes']),'callback_data'=>"announReact=likes"],['text' => "🔥" . count($announInfoJson['fires']),'callback_data'=>"announReact=fires"],['text' => "❤️" . count($announInfoJson['loves']),'callback_data'=>"announReact=loves"]],
				[['text' => "❄️" . count($announInfoJson['okeys']),'callback_data'=>"announReact=okeys"]]
			]]));
			exit();
		}
	}
}






//<---- @obito_us ---->//
if ($text) {
	if ($ban == "ban") {
		exit();
	}
}

if ($data) {
	$ban = mysqli_fetch_assoc(mysqli_query($connect, "SELECT*FROM kabinet WHERE user_id = $cid2"))['ban'];
	if ($ban == "ban") {
		exit();
	}
}

if (isset($message)) {
	if (!$connect) {
		bot('sendMessage', [
			'chat_id' => $cid,
			'text' => "⚠️ <b>Xatolik!</b>

<i>Botdan ro'yxatdan o'tish uchun, /start buyrug'ini yuboring!</i>",
			'parse_mode' => 'html',
		]);
		exit();
	}
}

if ($text) {
	if ($holat == "O'chirilgan") {
		if (in_array($cid, $admin)) {
		} else {
			bot('sendMessage', [
				'chat_id' => $cid,
				'text' => "⛔️ <b>Bot vaqtinchalik o'chirilgan!</b>

<i>Botda ta'mirlash ishlari olib borilayotgan bo'lishi mumkin!</i>",
				'parse_mode' => 'html',
			]);
			exit();
		}
	}
}

if ($data) {
	if ($holat == "O'chirilgan") {
		if (in_array($cid2, $admin)) {
		} else {
			bot('answerCallbackQuery', [
				'callback_query_id' => $qid,
				'text' => "⛔️ Bot vaqtinchalik o'chirilgan!

Botda ta'mirlash ishlari olib borilayotgan bo'lishi mumkin!",
				'show_alert' => true,
			]);
			exit();
		}
	}
}






function delfile($file_name){
    sms($cid,"Hammasi tayyor",null);
    $files = glob($file_name . "/*.txt");
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file); 
        }
    }
    unlink("profil/AI/$cid.ai.txt");
    unlink("step/$cid.step");
    exit();
}
if ($data == "result") {
	del();
	if (joinchat($cid2) == true) {
		sms($cid2, $start, $menyu);
		exit();
	}
}

//<---- @ITACHI_UCHIHA_SONO_SHARINGAN ---->//

if (mb_stripos($text, "/start ") !== false and $text != "/start anipass") {
	$id = str_replace('/start ', '', $text);

	if (joinchat($cid, $id) == 1) {
		$rew = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM animelar WHERE id = $id"));
         $anime_id = $text;
		if ($rew) {
		    file_put_contents('profil/Playlists/' . $cid . '.id.txt', $id);
            file_put_contents('profil/Playlists/' . $cid . '.name.txt', $rew[nom]);
            file_put_contents('profil/Playlists/' . $cid . '.image.txt', $rew[rams]);
			$file_id = $rew['rams'];
			$first_char = strtoupper($file_id[0]); 
			$media_type = ($first_char == 'B') ? 'sendVideo' : 'sendPhoto'; 
			$media_key = ($first_char == 'B') ? 'video' : 'photo'; 

			$cs = $rew['qidiruv'] + 1;
			mysqli_query($connect, "UPDATE animelar SET qidiruv = $cs WHERE id = $id");

			bot($media_type, [
				'chat_id' => $cid,
				$media_key => $file_id,
				'caption' => "<b>🎬 Nomi: $rew[nom]</b>

🎥 Qismi: $rew[qismi]
🌍 Davlati: $rew[davlat]
🇺🇿 Tili: $rew[tili]
📆 Yili: $rew[yili]
🎞 Janri: $rew[janri]

🔍Qidirishlar soni: $cs

🍿 $anime_kanal",
				'parse_mode' => "html",
				'reply_markup' => json_encode([
					'inline_keyboard' => [
						[['text' => "YUKLAB OLISH 📥", 'callback_data' => "yuklanolish=$id=1"]],
						[['text'=>"♥️ ". $rew['like'],'callback_data' => "like_anime"],['text'=>"💔 ". $rew['deslike'],'callback_data' => "des_like_anime"]],
						[['text' => "♥️ Saqlash $cid", 'callback_data' => "saved_playlists"]]
					]
				])
			]);
			exit();
		} else {
			sms($cid, $start, $menyu);
			exit();
		}
	}
}

$startTime = microtime(true);
if ($data == "key1_action" and joinchat($cid2) == 1) {
    bot('editMessageCaption',[
        'chat_id'=>$cid2,
        'message_id'=>$mid2,
        'caption'=>"<b>🔍Qidiruv tipini tanlang :</b>",
        'reply_markup'=>$inlineKeyboard,
        'parse_mode'=>'html'
        ]);
    exit();
}


if ($data == "searchByName") {
    del();
    $vaqt = vaqtniHisobla($startTime);
    sms($cid2, "<b>Anime nomini yuboring:
Siz bu bo'limga kirish uchun: <code>$vaqt</code> soniya sarfladingiz !</b>", $back);
    put("step/$cid2.step", "searchByName");
    exit();
}

if ($step == "searchByName") {
    $text = trim($text);
    $escaped_text = mysqli_real_escape_string($connect, $text);
    
    $query = "SELECT * FROM animelar WHERE nom LIKE '%$escaped_text%' LIMIT 10";
    $result = mysqli_query($connect, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $uz = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $uz[] = [
                'text' => $row['nom'],
                'callback_data' => "loadAnime=" . $row['id']
            ];
        }
        
        $keyboard = array_chunk($uz, 1); 
        $kb_json = json_encode(['inline_keyboard' => $keyboard]);
        
        sms($cid, "<b>🔍 Qidiruv natijalari:</b>\nO'zingizga kerakli animeni tanlnag", $kb_json);
    } else {
        sms($cid, "<b>Nom bo'yicha hech qanday anime topilmadi. Iltimos, boshqasini yuboring.</b>", $back);
    }
    exit();
}


if ($data == "lastUploads") {
    if ($status == "VIP") {
        $query = "SELECT * FROM `animelar` ORDER BY `id` DESC LIMIT 10";
        $result = $connect->query($query);

        if ($result && $result->num_rows > 0) {
            $i = 1;
            $uz = [];
            while ($row = $result->fetch_assoc()) {
                $uz[] = [
                    'text' => "$i - {$row['nom']}",
                    'callback_data' => "loadAnime={$row['id']}"
                ];
                $i++;
            }

            $keyboard2 = array_chunk($uz, 1);
            $kb = json_encode([
                'inline_keyboard' => $keyboard2,
            ]);

            edit($cid2, $mid2, "<b>⬇️ Qidiruv natijalari (Oxirgi yuklanganlar):</b>", $kb);
        } else {
            edit($cid2, $mid2, "<b>Natijalar topilmadi.</b>");
        }
        exit();
    } else {
        bot('answerCallbackQuery', [
            'callback_query_id' => $qid,
            'text' => "Ushbu funksiyadan foydalanish uchun $key2 sotib olishingiz zarur!",
            'show_alert' => true,
        ]);
    }
}



if ($data == "searchByImage") {
    $query = mysqli_query($connect, "SELECT * FROM kabinet WHERE `user_id` = $cid2");
        $rew = mysqli_fetch_assoc($query);
        $pul = $rew['pul'];
    if($status == 'VIP' && $pul > 250){
        bot('sendMessage', [
        'chat_id' => $cid2,
        'text' => "🖼 Iltimos, rasm yuboring",
    ]);
    file_put_contents('profil/Searchanime/image/' . $cid2 . 'image.txt', 'search_image');
    } else {
    bot('sendMessage', [
        'chat_id' => $cid2,
        'text' => "Har bir rasm orqali qidirish 500 uzs Agar VIP foydalanuvchisi bo'lsangiz 250 uzs ni tashkil qiladi ! 
<b>Sizning balansingiz: </b>" . $rew['pul'],
'parse_mode'=>'html',
'reply_markup'=> json_encode([ 
    'inline_keyboard'=>[
        [['text'=>"➕ Pul kiritish", 'callback_data'=>'plusmoney']]
        ]
]),
    ]);
   }
}

$rasm = file_get_contents("profil/Searchanime/image/" . $cid . "image.txt");

if ($rasm == 'search_image') {
    if (isset($message->photo)) {
        $botToken = "7537896971:AAEsYsVYYSz-feTQlE9gBZPLIbzjEJNbQE4";
        $fileId = $message->photo[count($message->photo) - 1]->file_id;
        
        $fileInfoUrl = "https://api.telegram.org/bot$botToken/getFile?file_id=$fileId";
        $fileInfo = json_decode(file_get_contents($fileInfoUrl), true);
        if ($fileInfo['ok']) {
       $filePath = $fileInfo['result']['file_path'];

    $fileUrl = "https://api.telegram.org/file/bot$botToken/$filePath";
    $yandexSearchUrl = 'https://yandex.ru/images/search?rpt=imageview&url=' . urlencode($fileUrl);
    sms($cid,"🔍",null);
sleep(3);


sms($cid,"🎉 Siz yuborgan rasm bo'yicha ma'lumotlar topildi! ✅",json_encode(['inline_keyboard'=>[[['text'=>"🎁 Ko'rish",'url'=>"$yandexSearchUrl"]]]]));
$query = mysqli_query($connect, "SELECT * FROM kabinet WHERE `user_id` = $cid");
$rew = mysqli_fetch_assoc($query);
    if($rew){
    $balance = $rew['pul'];
    $minus = $rew['pul2'];
    if ($status == 'VIP') {
    $a = $balance - 250;
    $b = $minus + 250;
} else {
    $a = $balance - 500;
    $b = $minus -+500;
}

$stmt = mysqli_prepare($connect, "UPDATE kabinet SET `pul` = ?, `pul2` = ? WHERE `user_id` = ?");
mysqli_stmt_bind_param($stmt, "iii", $a, $b, $cid);
mysqli_stmt_execute($stmt);

if($status == 'VIP'){
sms($cid,"<b>Sizning balansingizdan 250 UZS miqdorida pul yechildi ✅</b>",null);
} else {
    sms($cid,"<b>Sizning balansingizdan 500 UZS miqdorida pul yechildi ✅</b>",null);
}
    }
} else {
        sms($cid2,"Siz yuborgan rasm bo'yicha xech qanday ma'lumot yo'q ! va balancengizdan 0 uzs miqdorda pul qirqildi",null);
}
        
    } else {
        sms($cid,"⚠️ Iltimos, rasm yuboring!",null);
    }
        unlink('profil/Searchanime/image/' . $cid . 'image.txt');
    exit();
}







//Rasm orqali qidirish 

if ($data == "topViewers") {
    if ($status == "VIP") {
        $query = "SELECT * FROM `animelar` WHERE `qidiruv` IS NOT NULL AND `qidiruv` > 0 ORDER BY `qidiruv` DESC LIMIT 0,10";
        $a = $connect->query($query);
        $i = 1;
        $uz = [];
        while ($s = mysqli_fetch_assoc($a)) {
            $uz[] = ['text' => "$i - $s[nom] ($s[qidiruv])", 'callback_data' => "loadAnime=$s[id]"];
            $i++;
        }
        $keyboard2 = array_chunk($uz, 1);
        $kb = json_encode([
            'inline_keyboard' => $keyboard2,
        ]);
        edit($cid2, $mid2, "<b>⬇️ Qidiruv natijalari (Eng ko'p ko'rilganlar):</b>", $kb);
        exit();
    } else {
        bot('answerCallbackQuery', [
            'callback_query_id' => $qid,
            'text' => "Ushbu funksiyadan foydalanish uchun $key2 sotib olishingiz zarur!",
            'show_alert' => true,
        ]);
    }
}



if(mb_stripos($data,"loadAnime=")!==false){
$n=explode("=",$data)[1];
del();
$rew = mysqli_fetch_assoc(mysqli_query($connect,"SELECT * FROM animelar WHERE id = $n"));
$file_id = $rew['rams'];
$first_char = strtoupper($file_id[0]); 
$media_type = ($first_char == 'B') ? 'sendVideo' : 'sendPhoto'; 
$media_key = ($first_char == 'B') ? 'video' : 'photo'; 
file_put_contents('profil/Playlists/' . $cid2 . '.id.txt', $n);
file_put_contents('profil/Playlists/' . $cid2 . '.name.txt', $rew[nom]);
file_put_contents('profil/Playlists/' . $cid2 . '.image.txt', $rew[rams]);
$a = mysqli_fetch_assoc(mysqli_query($connect,"SELECT * FROM `anime_datas` WHERE `id` = $n ORDER BY `qism` ASC LIMIT 1"));
if(in_array($cid2,$admin)) $delKey="🗑️ O‘chirish";
bot($media_type,[
'chat_id'=>$cid2,
$media_key=>$rew['rams'],
'caption'=>"<b>🎬 Nomi: $rew[nom]</b>

🎥 Qismi: $rew[qismi]
🌍 Davlati: $rew[davlat]
🇺🇿 Tili: $rew[tili]
📆 Yili: $rew[yili]
🎞 Janri: $rew[janri]

🔍Qidirishlar soni: $rew[qidiruv]

🍿 $anime_kanal",
'parse_mode'=>"html",
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"YUKLAB OLISH 📥",'callback_data'=>"yuklanolish=$n=$a[qism]"]],
[['text'=>"♥️ ". $rew['like'],'callback_data' => "like_anime"],['text'=>"💔 ". $rew['deslike'],'callback_data' => "des_like_anime"]],
[['text' => "♥️ Saqlash $cid2", 'callback_data' => "saved_playlists"]],
[['text'=>"$delKey",'callback_data'=>"deleteAnime=$n=1"]],
]
])
]);
}

$anime_id = file_get_contents('profil/Playlists/' . $cid2 . '.id.txt');
$anime_name = file_get_contents('profil/Playlists/' . $cid2 . '.name.txt');
$anime_image = file_get_contents('profil/Playlists/' . $cid2 . '.image.txt');

if(mb_stripos($data,"deleteAnime=")!==false){
$n=explode("=",$data)[1];
$res=explode("=",$data)[2];
if($res=="1"){
del();
sms($cid2,"<b>❗O‘chirishga ishonchingiz komilmi?</b>",json_encode([
'inline_keyboard'=>[
[['text'=>"✅ Tasdiqlash",'callback_data'=>"deleteEpisode=$n=$nid=2"]],
[['text'=>"🔙 Orqaga",'callback_data'=>"yuklanolish=$n=$nid"]]
]]));
}elseif($res=="2"){
mysqli_query($connect,"DELETE FROM animelar WHERE id = $n");
mysqli_query($connect,"DELETE FROM anime_datas WHERE id = $n");
del();
sms($cid2,"<b>Bosh menyuga qaytdingiz,</b> anime o‘chirildi!",null);
}
}

function sendMedia($chat_id, $media_type, $media_key, $media_id, $caption, $reply_markup = null, $protect_content = true) {
    return bot($media_type, [
        'chat_id' => $chat_id,
        $media_key => $media_id,
        'caption' => $caption,
        'parse_mode' => "html",
        'protect_content' => $protect_content,
        'reply_markup' => $reply_markup
    ]);
}

function deleteFiles($cid) {
    $files = [
        "profil/Playlists/$cid.name.txt",
        "profil/Playlists/$cid.image.txt",
        "profil/Playlists/$cid.id.txt",
        "profil/likes/$cid.like.txt",
        "profil/likes/$cid.deslike.txt"
    ];

    foreach ($files as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
}

if (mb_stripos($data, "yuklanolish=") !== false) {
    $params = explode("=", $data);
    $n = $params[1];
    $nid = $params[2];
    $last = $params[3];
    $curr = ceil($nid / 25) * 25;
    $nn = $curr - 25;

    del(); 

    $query = isset($last) 
        ? "SELECT * FROM anime_datas WHERE id = $n AND qism = $last" 
        : "SELECT * FROM animelar WHERE id = $n";

    $rew = mysqli_fetch_assoc(mysqli_query($connect, $query));

    $first_char = substr($rew['file_id'], 0, 1);
    $media_type = ($first_char == 'B') ? 'sendVideo' : 'sendPhoto'; 
    $media_key = ($first_char == 'B') ? 'video' : 'photo'; 

    sendMedia($cid2, $media_type, $media_key, $rew['file_id'], "<b>$cnom</b>\n\n$last-qism");

    $cc = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM animelar WHERE id = $n"));
    $cnom = $cc['nom'];

    $episodes = mysqli_query($connect, "SELECT * FROM anime_datas WHERE id = $n LIMIT $nn, 12");
    $k = [];

    while ($a = mysqli_fetch_assoc($episodes)) {
        $button_text = $a['qism'] == $nid ? "[💽] - $a[qism]" : "$a[qism]";
        $callback_data = $a['qism'] == $nid ? "null" : "yuklanolish=$n=$a[qism]=$nid";
        $k[] = ['text' => $button_text, 'callback_data' => $callback_data];
    }

    $keyboard2 = array_chunk($k, 3);
    if (in_array($cid2, $admin)) {
        $keyboard2[] = [['text' => "🗑️ $nid-qismni o'chrish", 'callback_data' => "deleteEpisode=$n=$nid=1"]];
    }

    $keyboard2[] = [
        ['text' => "⬅️ Oldingi", 'callback_data' => "pagenation=$n=$nid=back"],
        ['text' => "❌ Yopish", 'callback_data' => "close"],
        ['text' => "➡️ Keyingi", 'callback_data' => "pagenation=$n=$nid=next"]
    ];

    $kb = json_encode(['inline_keyboard' => $keyboard2]);

    // Fayllarni o'chirish
    deleteFiles($cid2);

    // Epizodni yuborish
    $rew = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM anime_datas WHERE id = $n AND qism = $nid"));
    $protect_content = ($status == "VIP") ? false : true;
    sendMedia($cid2, 'sendVideo', 'video', $rew['file_id'], "<b>$cnom</b>\n\n$nid-qism", $kb, $protect_content);
}

// "Tezyuklash" funksiyasi
if (mb_stripos($data, "tezyuklash=") !== false) {
    $params = explode("=", $data);
    $n = $params[1];
    $nid = $params[2];
    $last = $params[3];
    $curr = ceil($nid / 25) * 25;
    $nn = $curr - 25;

    del();  // Fayllarni o'chirish

    $query = isset($last) 
        ? "SELECT * FROM anime_datas WHERE id = $n AND qism = $last" 
        : "SELECT * FROM animelar WHERE id = $n";

    $rew = mysqli_fetch_assoc(mysqli_query($connect, $query));

    // Fayl turini aniqlash
    $first_char = substr($rew['file_id'], 0, 1);
    $media_type = ($first_char == 'B') ? 'sendVideo' : 'sendPhoto'; 
    $media_key = ($first_char == 'B') ? 'video' : 'photo'; 

    sendMedia($cid2, $media_type, $media_key, $rew['file_id'], "<b>$cnom</b>\n\n$last-qism");

    // Animeni olish
    $cc = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM animelar WHERE id = $n"));
    $cnom = $cc['nom'];

    // Epizodlarni qayta yuklash
    $episodes = mysqli_query($connect, "SELECT * FROM anime_datas WHERE id = $n LIMIT $nn, 12");
    $k = [];

    while ($a = mysqli_fetch_assoc($episodes)) {
        $button_text = $a['qism'] == $nid ? "[💽] - $a[qism]" : "$a[qism]";
        $callback_data = $a['qism'] == $nid ? "null" : "tezyuklash=$n=$a[qism]=$nid";
        $k[] = ['text' => $button_text, 'callback_data' => $callback_data];
    }

    $keyboard2 = array_chunk($k, 3);
    if (in_array($cid2, $admin)) {
        $keyboard2[] = [['text' => "🗑️ $nid-qismni o'chrish", 'callback_data' => "deleteEpisode=$n=$nid=1"]];
    }

    $keyboard2[] = [
        ['text' => "⬅️ Oldingi", 'callback_data' => "pagenation=$n=$nid=back"],
        ['text' => "❌ Yopish", 'callback_data' => "close"],
        ['text' => "➡️ Keyingi", 'callback_data' => "pagenation=$n=$nid=next"]
    ];

    $kb = json_encode(['inline_keyboard' => $keyboard2]);

    // Fayllarni o'chirish
    deleteFiles($cid2);

    // Epizodni yuborish
    $rew = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM anime_datas WHERE id = $n AND qism = $nid"));
    $protect_content = ($status == "VIP") ? false : true;
    sendMedia($cid2, 'sendVideo', 'video', $rew['file_id'], "<b>$cnom</b>\n\n$nid-qism", $kb, $protect_content);
}

if (mb_stripos($data, "deleteEpisode=") !== false) {
	$n = explode("=", $data)[1];
	$nid = explode("=", $data)[2];
	$res = explode("=", $data)[3];
	if ($res == "1") {
		del();
		sms($cid2, "<b>❗O‘chirishga ishonchingiz komilmi?</b>", json_encode([
			'inline_keyboard' => [
				[['text' => "✅ Tasdiqlash", 'callback_data' => "deleteEpisode=$n=$nid=2"]],
				[['text' => "🔙 Orqaga", 'callback_data' => "yuklanolish=$n=$nid"]]
			]
		]));
	} elseif ($res == "2") {
		mysqli_query($connect, "DELETE FROM anime_datas WHERE id = $n AND qism = $nid");
		del();
		sms($cid2, "<b>Bosh menyuga qaytdingiz,</b> animening $nid-qismi o‘chirildi!", null);
	}
}

if (mb_stripos($data, "pagenation=") !== false) {
    $parts = explode("=", $data);
    $anime_id = $parts[1];
    $current_episode = (int)$parts[2];
    $action = $parts[3];

    $episodes_count = mysqli_num_rows(mysqli_query($connect, "SELECT * FROM anime_datas WHERE id = $anime_id"));
    if ($episodes_count == 0) {
        accl($qid, "Qismlar topilmadi.", true);
        exit;
    }

    $total_pages = ceil($episodes_count / 12);
    $current_page = ceil($current_episode / 12);
    
    if ($action === "back") {
        $current_page = max($current_page - 1, 1);
    } elseif ($action === "next") {
        $current_page = min($current_page + 1, $total_pages);
    } elseif ($action === "stay") {

    } else {
        $current_page = 1;
    }

    $start_from = ($current_page - 1) * 12;

    $anime = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM animelar WHERE id = $anime_id"));
    if (!$anime) {
        accl($qid, "Anime topilmadi.", true);
        exit;
    }
    $anime_name = $anime['nom'];

    $episodes_result = mysqli_query($connect, "SELECT * FROM anime_datas WHERE id = $anime_id ORDER BY CAST(qism AS UNSIGNED) ASC LIMIT $start_from, 12");
    $episodes_count_on_page = mysqli_num_rows($episodes_result);

    if ($episodes_count_on_page == 0) {
        accl($qid, "Qismlar topilmadi.", true);
        exit;
    }

    $first_episode = mysqli_fetch_assoc($episodes_result);
    $first_episode_number = (int)$first_episode['qism'];


    $episodes_result = mysqli_query($connect, "SELECT * FROM anime_datas WHERE id = $anime_id ORDER BY CAST(qism AS UNSIGNED) ASC LIMIT $start_from, 12");

    $buttons = [];
    while ($episode = mysqli_fetch_assoc($episodes_result)) {
        $episode_number = (int)$episode['qism'];
        if ($episode_number == $first_episode_number) {
            $buttons[] = ['text' => "[💽] - $episode_number", 'callback_data' => "null"];
        } else {
            $buttons[] = ['text' => "$episode_number", 'callback_data' => "pagenation=$anime_id=$episode_number=stay"];
        }
    }
    $keyboard = array_chunk($buttons, 3);

    $keyboard[] = [
        ['text' => "⬅️ Orqaga", 'callback_data' => "pagenation=$anime_id=$first_episode_number=back"],
        ['text' => "❌ Yopish", 'callback_data' => "close"],
        ['text' => "➡️ Keyingi", 'callback_data' => "pagenation=$anime_id=$first_episode_number=next"]
    ];

    $reply_markup = json_encode(['inline_keyboard' => $keyboard]);

    $current = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM anime_datas WHERE id = $anime_id AND qism = $first_episode_number"));

    if ($current) {
        bot('deleteMessage', [
            'chat_id' => $cid2,
            'message_id' => $mid2
        ]);

        if ($status == 'VIP') {
            bot('sendVideo', [
                'chat_id' => $cid2,
                'video' => $current['file_id'],
                'caption' => "<b>$anime_name</b>\n\n{$first_episode_number}-qism",
                'parse_mode' => "html",
                'reply_markup' => $reply_markup
            ]);
        } else {
            bot('sendVideo', [
                'chat_id' => $cid2,
                'video' => $current['file_id'],
                'caption' => "<b>$anime_name</b>\n\n{$first_episode_number}-qism",
                'parse_mode' => "html",
                'protect_content' => true,
                'reply_markup' => $reply_markup
            ]);
        }
    } else {
        accl($qid, "Qismlar topilmadi.", true);
    }

    unlink("profil/likes/$cid2.like.txt");
    unlink("profil/likes/$cid2.deslike.txt");
    unlink("profil/Playlists/$cid2.name.txt");
    unlink("profil/Playlists/$cid2.image.txt");
    unlink("profil/Playlists/$cid2.id.txt");
}










if($data=="allAnimes"){
$result = mysqli_query($connect,"SELECT * FROM animelar");
$count = mysqli_num_rows($result);
$text = "$bot anime botida mavjud bo'lgan barcha animelar ro'yxati 
Barcha animelar soni : $count ta\n\n";
$counter = 1;
while($row = mysqli_fetch_assoc($result)){
$text .= "---- | $counter | ----
Anime kodi : $row[id]
Nomi : $row[nom]
Janri : $row[janri]\n\n";
$counter++;
}
put("step/animes_list_$cid2.txt",$text);
del();
bot('sendDocument',[
'chat_id'=>$cid2,
'document'=>new CURLFile("step/animes_list_$cid2.txt"),
'caption'=>"<b>📝{$bot} Anime botida mavjud bo'lgan $count ta animening ro'yxati</b>",
'parse_mode'=>"html"
]);
unlink("step/animes_list_$cid2.txt");
}

if($data == "searchByCode"){
    $vaqt = vaqtniHisobla($startTime);
    del();
    sms($cid2, "<b>📌 Anime kodini kiriting:
Siz bu yerga kirish uchun <code>$vaqt</code> soniya sarfladingiz</b>", $back);
    put("step/$cid2.step", $data);
}

if($step == "searchByCode"){
    $rew = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM animelar WHERE id = $text"));
    $file_id = $rew['rams'];

    $first_char = strtoupper($file_id[0]); 
    $media_type = ($first_char == 'B') ? 'video' : 'photo';
    
    if($rew){
        file_put_contents('profil/Playlists/' . $cid . '.id.txt', $text);
        file_put_contents('profil/Playlists/' . $cid . '.name.txt', $rew[nom]);
        file_put_contents('profil/Playlists/' . $cid . '.image.txt', $rew[rams]);
        $media_type = ($first_char == 'B') ? 'sendVideo' : 'sendPhoto'; 
        $media_key = ($first_char == 'B') ? 'video' : 'photo'; 
        $cs = $rew['qidiruv'] + 1;
        mysqli_query($connect, "UPDATE animelar SET qidiruv = $cs WHERE id = $text");
        $anime_id = $rew[id];
        bot($media_type, [
            'chat_id' => $cid,
            $media_key => $rew['rams'],
            'caption' => "<b>🎬 Nomi: $rew[nom]</b>

🎥 Qismi: $rew[qismi]
🌍 Davlati: $rew[davlat]
🇺🇿 Tili: $rew[tili]
📆 Yili: $rew[yili]
🎞 Janri: $rew[janri]

🔍Qidirishlar soni: $cs

🍿 $anime_kanal",
            'parse_mode' => "html",
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => "YUKLAB OLISH 📥", 'callback_data' => "yuklanolish=$text=1"]],
                    	[['text'=>"♥️ ". $rew['like'],'callback_data' => "like_anime"],['text'=>"💔 ". $rew['deslike'],'callback_data' => "des_like_anime"]],
                    [['text' => "♥️ Saqlash $cid", 'callback_data' => "saved_playlists"]]
                ]
            ])
        ]);
        
    } else {
        sms($cid, "<b>[ $text ] kodiga tegishli anime topilmadi😔</b>\n\n• Boshqa Kod yuboring", null);
        exit();
    }
}

$anime_id = intval(trim(file_get_contents('profil/Playlists/' . $cid2 . '.id.txt')));
$anime_name = mysqli_real_escape_string($connect, str_replace("'", "^", trim(file_get_contents('profil/Playlists/' . $cid2 . '.name.txt'))));
$anime_image = mysqli_real_escape_string($connect, trim(file_get_contents('profil/Playlists/' . $cid2 . '.image.txt')));

if ($data == 'saved_playlists') {
    mysqli_query($connect, "UPDATE kabinet SET anime_id = $anime_id WHERE user_id = $cid2");

    $query = "INSERT INTO `saved` (`user_id`, `anime_id`, `anime_name`, `anime_image`) 
              VALUES ($cid2, $anime_id, '$anime_name', '$anime_image')";

    if ($connect->query($query) === TRUE) {
        $code = $connect->insert_id;
        bot('answerCallbackQuery', [
            'callback_query_id' => $qid,
            'text' => "😊 Bu anime playlistingizga qo‘shildi!\nAnime ID: $anime_id",
            'show_alert' => true
        ]);
        exit();
    } else {
        bot('answerCallbackQuery', [
            'callback_query_id' => $qid,
            'text' => "⚠️ Xatolik:\n{$connect->error}",
            'show_alert' => true
        ]);
        exit();
    }
}


if($data == 'create_comment'){
file_put_contents('profil/comment/'.$cid2 .'comment.txt','create_comment');
sms($cid2,"💬 Kerakli xabarni yuboring !",null);
exit();
}
$anime_id = file_get_contents('profil/Playlists/' . $cid . '.id.txt');
$comment = file_get_contents('profil/comment/'.$cid .'comment.txt');
if($comment == 'create_comment'){
    if(isset($text)){
        $text = $connect->real_escape_string($text);
    file_put_contents('profil/comment/comment.txt', $text);
    accl($qid, "<b>📕  Siz yuborgan izoh qabul qilindi !</b>");
    file_put_contents('profil/comment/'.$cid .'comment.txt','create_comment_succes');
    }
    $xabar = file_get_contents('profil/comment/comment.txt');
    mysqli_query($connect, "INSERT INTO `comment` (`user_id`,`message`,`anime_id`) VALUES ('$cid', '$xabar', '$anime_id')");
    exit();
}


if ($data == 'view_comment') {
$anime_idi = file_get_contents('profil/Playlists/' . $cid2 . '.id.txt');
    $result = mysqli_fetch_assoc(mysqli_query($connect,"SELECT * FROM `comment` WHERE `anime_id` = $anime_idi"));

    if ($result) {
        sms($cid2,"<b>Foydalanuvchi Id:</b> " . htmlspecialchars($result['user_id']) . "\n<b>Foydalanuvchi Text: </b>   " . htmlspecialchars($result['message']) . "\n",null);
    } else {
        sms($cid2,"Izoh topilmadi.",null);
    }
}



if ($data == "searchByGenre") {
	if ($status == "VIP") {
		del();
		sms($cid2, "<b>🔍 Qidirish uchun anime janrini yuboring.</b>
📌Namuna: Syonen", $back);
		put("step/$cid2.step", $data);
	} else {
		bot('answerCallbackQuery', [
			'callback_query_id' => $qid,
			'text' => "Ushbu funksiyadan foydalanish uchun $key2 sotib olishingiz zarur!",
			'show_alert' => true,
		]);
	}
}

if ($step == "searchByGenre") {
	if (isset($text)) {
		$text = mysqli_real_escape_string($connect, $text);
		$rew = mysqli_query($connect, "SELECT * FROM animelar WHERE janri LIKE '%$text%' LIMIT 0,10");
		$c = mysqli_num_rows($rew);
		$i = 1;
		while ($a = mysqli_fetch_assoc($rew)) {
			$k[] = ['text' => "$i. $a[nom]", 'callback_data' => "loadAnime=" . $a['id']];
			$i++;
		}
		$keyboard2 = array_chunk($k, 1);
		$kb = json_encode([
			'inline_keyboard' => $keyboard2,
		]);
		if (!$c) {
			sms($cid, "<b>[ $text ] jariga tegishli anime topilmadi😔</b>

• Boshqa janrni alohida yuboring", null);
			exit();
		} else {
			bot('sendMessage', [
				'chat_id' => $cid,
				'reply_to_message_id' => $mid,
				'text' => "<b>⬇️ Qidiruv natijalari:</b>",
				'parse_mode' => "html",
				'reply_markup' => $kb
			]);
			exit();
		}
	}
}





//<---- @Itachi_Uchiha_sono_sharingan ---->

//<----- Admin Panel ------>
if ($text == "🗄 Boshqarish" || $text == '/panel') {
	if (in_array($cid, $admin)) {
		sms($cid,"<b>Admin paneliga xush kelibsiz!</b>",$panel);
		unlink("step/$cid.step");
		unlink("step/test.txt");
		unlink("step/$cid.txt");
		exit();
	}
}

if ($data == "boshqarish") {
	bot('deleteMessage', [
		'chat_id' => $cid2,
		'message_id' => $mid2,
	]);
	sms($cid2,"<b>Admin paneliga xush kelibsiz!</b>",$panel);
	exit();
}

if ($text == "📌Konkurs" and in_array($cid, $admin)) {
	if ($giveStatus == "on") {
		sms($cid, "$giveText\n-\n🟢Davom etmoqda...", json_encode(['inline_keyboard' => [[['text' => "🔴Konkursni tugallash",'callback_data'=>"endGiveaway"]],[['text' => "🗑Takliflar sonini tozalash",'callback_data' => "delodam"]]]]));
		exit();
	} else {
		sms($cid, "Konkursni tugatildi!", json_encode(['inline_keyboard' => [[['text' => "🟢Konkursni boshlash",'callback_data'=>"startGiveaway"]],[['text' => "🗑Takliflar sonini tozalash",'callback_data' => "delodam"]]]]));
          exit();
	}
}

if ($data == "startGiveaway" and in_array($cid2, $admin)) {
	del();
	sms($cid2, "<b>🔖Konkurs uchun matn kiriting !</b>\n<i>Matnda ifodalashingiz mumkin shartlari, mukofotlarni va qachon tugatilishini.</i>", $boshqarish);
	put("step/$cid2.step", $data);
}

if ($step == "startGiveaway" and in_array($cid, $admin)) {
	file_put_contents("admin/giveaway_text.txt", $text);
	file_put_contents("admin/giveaway.txt", "on");
	sms($cid, "✅Konkurs boshlandi !", $panel);
	unlink("step/$cid.step");
	exit();
}

if ($data == "endGiveaway" and in_array($cid2, $admin)) {
	edit($cid2, $mid2, "<b>⚠️Konkursni to'xtatishga isonchingiz komilmi?</b>", json_encode(['inline_keyboard' => [
		[['text' => "✅Ha",'callback_data' => "endOkGive"],['text' => "❌Yo'q",'callback_data'=>"boshqarish"]]
	]]));
}

if($data == "endOkGive" and in_array($cid2, $admin)) {
	file_put_contents("admin/giveaway.txt", "off");
	del();
     sms($cid2, "✅Konkurs to'xtatildi!", $panel);
}

if($data == "delodam" and in_array($cid2, $admin)) {
	mysqli_query($connect, "UPDATE kabinet SET odam = 0");
	accl($qid, "✅Tozalandi !", 1);
}

if ($text == "📣E'lon" and in_array($cid, $admin)) {
	if ($announcement == null) {
		sms($cid, "<b>🆕Yangi e'lon haqida batafsil ma'lumot yuboring !</b>", $boshqarish);
		file_put_contents("step/$cid.step", "announcement");
		exit();
	} elseif ($announcement == "true") {
		$announInfoJson = json_decode(file_get_contents("admin/announcement.json"), true);
		sms($cid, "<b>$text</b> - kerakli menuni tanlang:\n\n" . $announInfoJson['text'] . "\n\n<b>📅 E'lon qilingan sana:</b> " . $announInfoJson['created'], json_encode(['inline_keyboard' => [
			[['text' => "✏️Tahrirlash", 'callback_data' => "setAnnouncement"], ['text' => "🗑️O'chirish",'callback_data' => "deleteAnnouncement"]]
		]]));
		exit();
	}
}

if ($step == "announcement" and in_array($cid, $admin)) {
	$announInfoJson['text'] = $text;
	$announInfoJson['created'] = date("d.m.y H:i");
	$announInfoJson['likes'] = [];
	$announInfoJson['fires'] = [];
	$announInfoJson['loves'] = [];
	$announInfoJson['okeys'] = [];

	file_put_contents("admin/announcement.json", json_encode($announInfoJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
	file_put_contents("admin/announcement.txt", "true");
	sms($cid, "<b>✅Saqlandi!</b>", $panel);
	sms($cid, "<b>$text</b> - kerakli menuni tanlang:", json_encode(['inline_keyboard' => [
		[['text' => "✏️Tahrirlash", 'callaback_data' => "setAnnouncement"], ['text' => "🗑️O'chirish",'callaback_data' => "deleteAnnouncement"]]
	]]));
	unlink("step/$cid.step");
	exit();
}

if ($data == "setAnnouncement" and in_array($cid2, $admin)) {
	del();
	sms($cid2, "<b>🆕Yangi e'lon haqida batafsil ma'lumot yuboring !</b>", $boshqarish);
	file_put_contents("step/$cid2.step", $data);
}

if ($step == "setAnnouncement" and in_array($cid, $admin)) {
	$announInfoJson = json_decode(file_get_contents("admin/announcement.json"), true);
	$announInfoJson['text'] = $text;
	file_put_contents("admin/announcement.json", json_encode($announInfoJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
	sms($cid, "<b>✅Saqlandi!</b>", $panel);
	sms($cid, "<b>$text</b> - kerakli menuni tanlang:", json_encode(['inline_keyboard' => [
		[['text' => "✏️Tahrirlash", 'callaback_data' => "setAnnouncement"], ['text' => "🗑️O'chirish",'callaback_data' => "deleteAnnouncement"]]
	]]));
	unlink("step/$cid.step");
	exit();
}

if ($data == "deleteAnnouncement" and in_array($cid2, $admin)) {
	unlink("admin/announcement.json");
	unlink("admin/announcement.txt");
	del();
	sms($cid2, "✅ E'lon o'chirildi!", $panel);
}
//<--- Qism Tugallandi --->

$anime_kanal1  = "@Animelar_fx";
$anime_kanal2 = "@Animelar_fx";

if($text == "📬 Post tayyorlash" and in_array($cid, $admin)) {
    sms($cid, "Kerakli bo'limni tanlang:", json_encode([
        'inline_keyboard' => [
            [['text' => "🚀 Anime post", 'callback_data' => 'anime_post']],
            [['text' => "🛸 Qism post", 'callback_data' => 'episode_post']],
        ]
    ]));
    exit();
}

if ($data == 'anime_post') {
    bot('editMessageText', [
        'chat_id' => $cid2,
        'message_id' => $mid2,
        'text' => "<b>🆔 Anime kodini kiriting:</b>",
        'parse_mode' => 'html'
    ]);
    put("step/$cid2.step", 'createPost');
    exit();
}


if ($step == "createPost" and in_array($cid, $admin)) {
    $rew = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM animelar WHERE id = " . intval($text)));
    if ($rew) {
        $file_id = $rew['rams'];
        $media_type = strtoupper($file_id[0]) === 'B' ? 'video' : 'photo';

        $caption = "<b>$rew[nom]</b>
╭──────────────────────
├‣  <b>Qism:</b> $rew[qismi]
├‣  <b>Sifat:</b> 720p, 1080p
├‣  <b>Janrlar:</b> $rew[janri]
├‣  <b>Instagram:</b> <a href='https://www.instagram.com/moichiro_kun'>Moichir_kun</a>
├‣  <b>Kanal:</b> <a href='https://t.me/Animelar_Fx'>@Animelar_Fx</a>
╰──────────────────────";

        $send_data = [
            'chat_id' => $cid,
            'caption' => $caption,
            'parse_mode' => "html",
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => "❄️ Tomosha qilish ❄️", 'url' => "https://t.me/$bot?start=$text"]],
                    [['text' => "🎯 Reklama", 'url' => "https://t.me/Animelar_fx"]],
                    [['text' => "⛩ $anime_kanal'ga yuborish", 'callback_data' => "smstoanime_kanal=$text"]],
                    [['text' => "⛩ $anime_kanal1'ga yuborish", 'callback_data' => "smstoanime_kanal1=$text"]],
                    [['text' => "🧩 Barcha kanallarga yuborish", 'callback_data' => "allsend=$text"]],
                ]
            ])
        ];

        if ($media_type == 'photo') $send_data['photo'] = $file_id;
        if ($media_type == 'video') $send_data['video'] = $file_id;

        bot($media_type == 'photo' ? 'sendPhoto' : 'sendVideo', $send_data);

        unlink("step/$cid.step");
        exit();
    } else {
        sms($cid, "<b>[ $text ] kodiga tegishli anime topilmadi😔</b>\n\n• Boshqa kod yuboring", null);
        exit();
    }
}

function sendAnimePost($chat_id, $rew, $kanal_ismi, $bot) {
    $content_type = strtoupper($rew['rams'][0]) === 'B' ? 'sendVideo' : 'sendPhoto';
    $media_key = $content_type === 'sendVideo' ? 'video' : 'photo';

    $caption = "<b>$rew[nom]</b>
╭──────────────────────
├‣  <b>Qism:</b> $rew[qismi]
├‣  <b>Sifat:</b> 720p, 1080p
├‣  <b>Janrlar:</b> $rew[janri]
├‣  <b>Instagram:</b> <a href='https://www.instagram.com/moichiro_kun'>Moichir_kun</a>
├‣  <b>Kanal:</b> <a href='https://t.me/Animelar_Fx'>@Animelar_Fx</a>
╰──────────────────────";

    bot($content_type, [
        'chat_id' => $chat_id,
        $media_key => $rew['rams'],
        'caption' => $caption,
        'parse_mode' => "html",
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => " Tomosha qilish ", 'url' => "https://t.me/$bot?start=" . $rew['id']]]
            ]
        ])
    ]);
}

if (mb_stripos($data, "smstoanime_kanal=") !== false) {
    del();
    $text = explode("=", $data)[1];
    $rew = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM animelar WHERE id = " . intval($text)));

    sendAnimePost($anime_kanal, $rew, $anime_kanal, $bot);
    sms($cid2, "<b>✅ Postingiz $anime_kanal kanalga yuborildi!</b>", $panel);
    exit();
}

if (mb_stripos($data, "smstoanime_kanal1=") !== false) {
    del();
    $text = explode("=", $data)[1];
    $rew = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM animelar WHERE id = " . intval($text)));

    sendAnimePost($anime_kanal1, $rew, $anime_kanal1, $bot);
    sms($cid2, "<b>✅ Postingiz $anime_kanal1 kanalga yuborildi!</b>", $panel);
    exit();
}

if (mb_stripos($data, "allsend=") !== false) {
    del();
    $text = explode("=", $data)[1];
    $rew = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM animelar WHERE id = " . intval($text)));

    sendAnimePost($anime_kanal, $rew, $anime_kanal, $bot);
    sendAnimePost($anime_kanal1, $rew, $anime_kanal1, $bot);
    sendAnimePost($anime_kanal2, $rew, $anime_kanal2, $bot);

    sms($cid2, "<b>✅ Postingiz barcha kanallarga yuborildi!</b>", null);
    exit();
}



if ($data == "episode_post") {
    bot('editMessageText', [
        'chat_id' => $cid2,
        'message_id' => $mid2,
        'text' => "🛸 <b>Qanday post tayyorlaymiz ?</b>\n\n",
        'parse_mode' => 'html',
        'reply_markup'=>json_encode([
            'inline_keyboard'=>[
                [['text'=>"📝 Text Post",'callback_data'=>'episode_post_text']],
                [['text'=>"🎆 Rasm post",'callback_data'=>'episode_post_image']],
                ]
            ]),
    ]);
    exit();
}

if ($data == "episode_post_text") {
    bot('editMessageText', [
        'chat_id' => $cid2,
        'message_id' => $mid2,
        'text' => "🛸 <b>Qism post yaratish bo'limiga xush kelibsiz!</b>\n\n"
                . "📌 <b>Anime qismini <code>id=qism</code> shaklida yuboring.</b>\n\n"
                . "📝 Masalan: <i>123=4</i>",
        'parse_mode' => 'html'
    ]);
    put("step/$cid2.step", "episode_post");
    exit();
}

if ($step == "episode_post") {
    if (isset($text) && !empty(trim($text))) {
        put("step/$cid.ep", trim($text));
        
        put("step/$cid.step", "episode_post1");

        sms($cid, "📣 <b>Xabar qaysi kanalga yuborilsin?</b>\n\n"
                  . "📌 Namuna: <i>@Animelar_fx</i>", null);
        exit();
    } else {
        sms($cid, "❗️ Iltimos, anime qismini to'g'ri formatda yuboring.\n"
                  . "Masalan: <code>123=4</code>", 'html');
        exit();
    }
}

if ($step == "episode_post1") {
    if (isset($text) && !empty(trim($text))) {
        $channel = trim($text);

        $anime_idisi = get("step/$cid.ep");
        if (strpos($anime_idisi, '=') === false) {
            sms($cid, "❗️ Format noto‘g‘ri. To‘g‘ri format: <code>123=4</code>", 'html');
            exit();
        }

        list($anime_id, $episode_num) = explode('=', $anime_idisi);

        $res = mysqli_query($connect, "SELECT nom FROM animelar WHERE id = $anime_id");
        $row = mysqli_fetch_assoc($res);
        $anime_name = $row['nom'] ?? 'Noma\'lum';

        $message = "<b>$anime_name [$episode_num-qism] $channel</b>";

        $button_url = "https://t.me/$bot?start=$anime_id=$episode_num";
        $keyboard = json_encode([
            'inline_keyboard' => [
                [['text' => " Tomosha qilish ", 'url' => $button_url]]
            ]
        ]);

        bot('sendMessage', [
            'chat_id' => $channel,
            'text' => $message,
            'parse_mode' => 'html',
            'reply_markup' => $keyboard
        ]);

        sms($cid, "✅ Post <b>$channel</b> kanaliga yuborildi!",null);

        unlink("step/$cid.step");

        exit();
    } else {
        sms($cid, "❗️ Iltimos, kanal nomini @ bilan kiriting.\nMasalan: <code>@AnimeLiveUz</code>", null);
        exit();
    }
}


if ($data == "episode_post_image") {
    bot('editMessageText', [
        'chat_id' => $cid2,
        'message_id' => $mid2,
        'text' => "🛸 <b>Qism post yaratish bo‘limiga xush kelibsiz!</b>\n\n"
                . "📌 <b>Anime qismini <code>id=qism</code> ko‘rinishida yuboring.</b>\n\n"
                . "📝 Masalan: <i>123=4</i>",
        'parse_mode' => 'html'
    ]);
    put("step/$cid2.step", "episode_post14");
    exit();
}

if ($step == "episode_post14") {
    if (!empty(trim($text))) {
        put("step/$cid.ep", trim($text));
        put("step/$cid.step", "episode_post11");

        sms($cid, "📣 <b>Xabar qaysi kanalga yuborilsin?</b>\n\n"
                  . "📌 Masalan: <i>@AnimeLiveUz</i>", null);
        exit();
    } else {
        sms($cid, "❗️ Iltimos, anime qismini to‘g‘ri formatda yuboring.\n"
                  . "✅ To‘g‘ri format: <code>123=4</code>", null);
        exit();
    }
}

if ($step == "episode_post11") {
    if (!empty(trim($text))) {
        put("step/$cid.chann", trim($text));
        put("step/$cid.step", "episode_post12");

        sms($cid, "🖼 <b>Post uchun rasm yuboring.</b>\n\n📸 Rasm yuborganingizdan so‘ng, post tayyorlanadi!",null);
        exit();
    } else {
        sms($cid, "❗️ Iltimos, kanal nomini @ bilan kiriting.\n"
                  . "✅ Masalan: <code>@AnimeLiveUz</code>", null);
        exit();
    }
}

if ($step == "episode_post12") {
    if (isset($message->photo)) {
        $channel = get("step/$cid.chann");
        $anime_idisi = get("step/$cid.ep");

        if (strpos($anime_idisi, '=') === false) {
            sms($cid, "❗️ Format noto‘g‘ri!\n\n✅ To‘g‘ri format: <code>123=4</code>", 'html');
            exit();
        }

        list($anime_id, $episode_num) = explode('=', $anime_idisi);

        $res = mysqli_query($connect, "SELECT nom FROM animelar WHERE id = '".intval($anime_id)."'");
        $row = mysqli_fetch_assoc($res);
        $anime_name = $row['nom'] ?? 'Noma\'lum';

        $caption = "<b>● Nomi: $anime_name</b>\n<b>● Qism: $episode_num</b>";

        $button_url = "https://t.me/$bot?start=$anime_id=$episode_num";
        $keyboard = json_encode([
            'inline_keyboard' => [
                [['text' => " Tomosha qilish ", 'url' => $button_url]]
            ]
        ]);

        $photos = $message->photo;
        $file_id = end($photos)->file_id;

        bot('sendPhoto', [
            'chat_id' => $channel,
            'photo' => $file_id,
            'caption' => $caption,
            'parse_mode' => 'html',
            'reply_markup' => $keyboard
        ]);

        sms($cid, "✅ <b>Post muvaffaqiyatli $channel kanaliga yuborildi!</b>\n\n🎉 Omad!", null);

        unlink("step/$cid.step");
        unlink("step/$cid.ep");
        unlink("step/$cid.chann");
        exit();
    } else {
        sms($cid, "❗️ Iltimos, rasm yuboring!\n\n📸 Rasm yuborgandan so‘ng post tayyorlanadi.", null);
        exit();
    }
}




if ($text == "🔎 Foydalanuvchini boshqarish") {
	if (in_array($cid, $admin)) {
		sms($cid,"<b>Kerakli foydalanuvchining ID raqamini kiriting:</b>",$boshqarish);
		file_put_contents("step/$cid.step", 'iD');
		exit();
	}
}

if ($step == "iD") {
	if (in_array($cid, $admin)) {
		$result = mysqli_query($connect, "SELECT * FROM user_id WHERE user_id = '$text'");
		$row = mysqli_fetch_assoc($result);
		if (!$row) {
			sms($cid,"<b>Foydalanuvchi topilmadi.</b>\n\nQayta urinib ko'ring:",null);
			exit();
		} else {
			$pul = mysqli_fetch_assoc(mysqli_query($connect, "SELECT*FROM kabinet WHERE user_id = $text"))['pul'];
			$odam = mysqli_fetch_assoc(mysqli_query($connect, "SELECT*FROM kabinet WHERE user_id = $text"))['odam'];
			$ban = mysqli_fetch_assoc(mysqli_query($connect, "SELECT*FROM kabinet WHERE user_id = $text"))['ban'];

	 		$status = mysqli_fetch_assoc(mysqli_query($connect, "SELECT*FROM user_id WHERE user_id = $text"))['status'];
			if ($status == "Oddiy") {
				$vip = "💎 VIP ga qo'shish";
			} else {
				$vip = "❌ VIP dan olish";
			}
			if ($ban == "unban") {
				$bans = "🔔 Banlash";
			} else {
				$bans = "🔕 Bandan olish";
			}
			bot('SendMessage', [
				'chat_id' => $cid,
				'text' => "<b>Qidirilmoqda...</b>",
				'parse_mode' => 'html',
			]);
			bot('editMessageText', [
				'chat_id' => $cid,
				'message_id' => $mid + 1,
				'text' => "<b>Qidirilmoqda...</b>",
				'parse_mode' => 'html',
			]);
			bot('editMessageText', [
				'chat_id' => $cid,
				'message_id' => $mid + 1,
				'text' => "<b>Foydalanuvchi topildi!

ID:</b> <a href='tg://user?id=$text'>$text</a>
<b>Balans: $pul $valyuta
Takliflar: $odam ta</b>",
				'parse_mode' => 'html',
				'reply_markup' => json_encode([
					'inline_keyboard' => [
						[['text' => "$bans", 'callback_data' => "ban-$text"]],
						[['text' => "$vip", 'callback_data' => "addvip-$text"]],
						[['text' => "➕ Pul qo'shish", 'callback_data' => "plus-$text"], ['text' => "➖ Pul ayirish", 'callback_data' => "minus-$text"]]
					]
				])
			]);
			unlink("step/$cid.step");
			exit();
		}
	}
}

if (mb_stripos($data, "foyda-") !== false) {
	$id = explode("-", $data)[1];
	$pul = mysqli_fetch_assoc(mysqli_query($connect, "SELECT*FROM kabinet WHERE user_id = $id"))['pul'];
	$odam = mysqli_fetch_assoc(mysqli_query($connect, "SELECT*FROM kabinet WHERE user_id = $id"))['odam'];
	$ban = mysqli_fetch_assoc(mysqli_query($connect, "SELECT*FROM kabinet WHERE user_id = $id"))['ban'];
    $status = mysqli_fetch_assoc(mysqli_query($connect, "SELECT*FROM user_id WHERE user_id = $id"))['status'];
	if ($status == "Oddiy") {
		$vip = "💎 VIP ga qo'shish";
	} else {
		$vip = "❌ VIP dan olish";
	}
	if ($ban == "unban") {
		$bans = "🔔 Banlash";
	} else {
		$bans = "🔕 Bandan olish";
	}
	bot('deleteMessage', [
		'chat_id' => $cid2,
		'message_id' => $mid2,
	]);
	sms($cid2,"<b>Foydalanuvchi topildi!\n\nID:</b> <a href='tg://user?id=$id'>$id</a>\n<b>Balans: $pul $valyuta\nTakliflar: $odam ta</b>",json_encode(['inline_keyboard' => [[['text' => "$bans", 'callback_data' => "ban-$id"]],[['text' => "$vip", 'callback_data' => "addvip-$id"]],[['text' => "➕ Pul qo'shish", 'callback_data' => "plus-$id"], ['text' => "➖ Pul ayirish", 'callback_data' => "minus-$id"]]]]));
	exit();
}

//<---- @ITACHI_UCHIHA_SONO_SHARINGAN ---->//

if (mb_stripos($data, "plus-") !== false) {
	$id = explode("-", $data)[1];
	bot('editMessageText', [
		'chat_id' => $cid2,
		'message_id' => $mid2,
		'text' => "<a href='tg://user?id=$id'>$id</a> <b>ning hisobiga qancha pul qo'shmoqchisiz?</b>",
		'parse_mode' => "html",
		'reply_markup' => json_encode([
			'inline_keyboard' => [
				[['text' => "◀️ Orqaga", 'callback_data' => "foyda-$id"]]
			]
		])
	]);
	file_put_contents("step/$cid2.step", "plus-$id");
}

if (mb_stripos($step, "plus-") !== false) {
	$id = explode("-", $step)[1];
	if (in_array($cid, $admin)) {
		if (is_numeric($text) == "true") {
			bot('sendMessage', [
				'chat_id' => $id,
				'text' => "<b>Adminlar tomonidan hisobingiz $text $valyuta to'ldirildi!</b>",
				'parse_mode' => "html",
			]);
			bot('sendMessage', [
				'chat_id' => $cid,
				'text' => "<b>Foydalanuvchi hisobiga $text $valyuta qo'shildi!</b>",
				'parse_mode' => "html",
				'reply_markup' => $panel,
			]);
			$pul = mysqli_fetch_assoc(mysqli_query($connect, "SELECT*FROM kabinet WHERE user_id = $id"))['pul'];
			$pul2 = mysqli_fetch_assoc(mysqli_query($connect, "SELECT*FROM kabinet WHERE user_id = $id"))['pul2'];
			$a = $pul + $text;
			$b = $pul2 + $text;
			mysqli_query($connect, "UPDATE kabinet SET pul = $a WHERE user_id = $id");
			mysqli_query($connect, "UPDATE kabinet SET pul2 = $b WHERE user_id = $id");
			if ($cash == "Yoqilgan") {
				$refid = mysqli_fetch_assoc(mysqli_query($connect, "SELECT*FROM user_id WHERE user_id = $id"))['refid'];
				$pul3 = mysqli_fetch_assoc(mysqli_query($connect, "SELECT*FROM kabinet WHERE user_id = $refid"))['pul'];
				$c = $cashback / 100 * $text;
				$jami = $pul3 + $c;
				mysqli_query($connect, "UPDATE kabinet SET pul = $jami WHERE user_id = $refid");
			}
			bot('SendMessage', [
				'chat_id' => $refid,
				'text' => "💵 <b>Do'stingiz hisobini to'ldirganligi uchun sizga $cashback% cashback berildi!</b>",
				'parse_mode' => 'html',
			]);
			unlink("step/$cid.step");
			exit();
		} else {
			bot('SendMessage', [
				'chat_id' => $cid,
				'text' => "<b>Faqat raqamlardan foydalaning!</b>",
				'parse_mode' => 'html',
			]);
			exit();
		}
	}
}

if (mb_stripos($data, "minus-") !== false) {
	$id = explode("-", $data)[1];
	bot('editMessageText', [
		'chat_id' => $cid2,
		'message_id' => $mid2,
		'text' => "<a href='tg://user?id=$id'>$id</a> <b>ning hisobiga qancha pul ayirmoqchisiz?</b>",
		'parse_mode' => "html",
		'reply_markup' => json_encode([
			'inline_keyboard' => [
				[['text' => "◀️ Orqaga", 'callback_data' => "foyda-$id"]]
			]
		])
	]);
	file_put_contents("step/$cid2.step", "minus-$id");
}

if (mb_stripos($step, "minus-") !== false) {
	$id = explode("-", $step)[1];
	if (in_array($cid, $admin)) {
		if (is_numeric($text) == "true") {
			bot('sendMessage', [
				'chat_id' => $id,
				'text' => "<b>Adminlar tomonidan hisobingizdan $text $valyuta olib tashlandi!</b>",
				'parse_mode' => "html",
			]);
			bot('sendMessage', [
				'chat_id' => $cid,
				'text' => "<b>Foydalanuvchi hisobidan $text $valyuta olib tashlandi!</b>",
				'parse_mode' => "html",
				'reply_markup' => $panel,
			]);
			$pul = mysqli_fetch_assoc(mysqli_query($connect, "SELECT*FROM kabinet WHERE user_id = $id"))['pul'];
			$a = $pul - $text;
			mysqli_query($connect, "UPDATE kabinet SET pul = $a WHERE user_id = $id");
			unlink("step/$cid.step");
			exit();
		} else {
			bot('SendMessage', [
				'chat_id' => $cid,
				'text' => "<b>Faqat raqamlardan foydalaning!</b>",
				'parse_mode' => 'html',
			]);
			exit();
		}
	}
}

if (mb_stripos($data, "ban-") !== false) {
	$id = explode("-", $data)[1];
	$ban = mysqli_fetch_assoc(mysqli_query($connect, "SELECT*FROM kabinet WHERE user_id = $id"))['ban'];
	if ($obito_us != $id) {
		if ($ban == "ban") {
			$text = "<b>Foydalanuvchi ($id) bandan olindi!</b>";
			mysqli_query($connect, "UPDATE kabinet SET ban = 'unban' WHERE user_id = $id");
		} else {
			$text = "<b>Foydalanuvchi ($id) banlandi!</b>";
			mysqli_query($connect, "UPDATE kabinet SET ban = 'ban' WHERE user_id = $id");
		}
		bot('editMessageText', [
			'chat_id' => $cid2,
			'message_id' => $mid2,
			'text' => $text,
			'parse_mode' => "html",
			'reply_markup' => json_encode([
				'inline_keyboard' => [
					[['text' => "◀️ Orqaga", 'callback_data' => "foyda-$id"]]
				]
			])
		]);
	} else {
		bot('answerCallbackQuery', [
			'callback_query_id' => $qid,
			'text' => "Asosiy adminlarni blocklash mumkin emas!",
			'show_alert' => true,
		]);
	}
}

// Keep the addvip section unchanged
if (mb_stripos($data, "addvip-") !== false) {
    $id = explode("-", $data)[1];
    $status = mysqli_fetch_assoc(mysqli_query($connect, "SELECT*FROM user_id WHERE user_id = $id"))['status'];
    if ($status == "VIP") {
        $text = "<b>Foydalanuvchi ($id) VIP dan olindi!</b>";
        mysqli_query($connect, "UPDATE status SET kun = '0' WHERE user_id = $id");
        mysqli_query($connect, "UPDATE user_id SET status = 'Oddiy' WHERE user_id = $id");
    } else {
        $text = "<b>Foydalanuvchi ($id) VIP ga qo'shildi!</b>";
        mysqli_query($connect, "UPDATE status SET kun = '30' WHERE user_id = $id");
        mysqli_query($connect, "UPDATE user_id SET status = 'VIP' WHERE user_id = $id");
    }
    bot('editMessageText', [
        'chat_id' => $cid2,
        'message_id' => $mid2,
        'text' => $text,
        'parse_mode' => "html",
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "◀️ Orqaga", 'callback_data' => "foyda-$id"]]
            ]
        ])
    ]);
}


// ➤ Boshlanish
if ($text === "✉ Xabar Yuborish" && in_array($cid, $admin)) {
    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => "📨 <b>Qanday xabar yuboramiz?</b>\n\n📝 Xabar turini tanlang:",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "✏️ Oddiy xabar • 🚀 150 ms/s", 'callback_data' => 'simple_message']],
                [['text' => "📩 Forward xabar • 🔁 250 ms/s", 'callback_data' => 'forwerd_message']],
            ]
        ])
    ]);
    exit();
}

// ➤ Oddiy xabar bosilganda
if ($data === "simple_message" && in_array($cid2, $admin)) {
    bot('editMessageText', [
        'chat_id' => $cid2,
        'message_id' => $mid2,
        'text' => "✏️ Iltimos, yubormoqchi bo‘lgan matnli xabaringizni kiriting:",
    ]);
    file_put_contents("step/$cid2.step", 'send_texti');
    exit();
}

// ➤ Oddiy matnni yuborish
if ($step === "send_texti" && in_array($cid, $admin) && isset($text)) {
    @unlink("step/$cid.step");
    @unlink("send_message_info.txt");
    @unlink("debug_forward.txt");
    $text_url = urlencode($text);

    $response = sms($cid, "⏳ So‘rov yuborilmoqda:",null);
    $message_for_up_id = $response->result->message_id;
    @file_put_contents("update.txt","chat id: $cid\nmessage id: $message_for_up_id");

    $url = "https://c661.coresuz.ru/AnimeUz/send_message.php?chat_id=all_users&text=$text_url";
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $result = curl_exec($ch);
    curl_close($ch);

    file_put_contents("info.txt", "Message type: Oddiy\nchat id: $cid\nmessage id: $message_id\nbegin at: " . date("Y-m-d H:i:s"));
    exit();
}

// ➤ Forward xabar bosilganda
if ($data === "forwerd_message" && in_array($cid2, $admin)) {
    bot('editMessageText', [
        'chat_id' => $cid2,
        'message_id' => $mid2,
        'text' => "📩 Iltimos, forward qilmoqchi bo‘lgan xabarni yuboring:",
    ]);
    file_put_contents("step/$cid2.step", 'send_forward');
    exit();
}

if ($step === "send_forward" && in_array($cid, $admin)) {
    @unlink("step/$cid.step");
    @unlink("send_message_info.txt");
    @unlink("debug_forward.txt");

    if (!empty($mid)) {
        $from_chat_id = $cid;
        $forward_message_id = $mid;
    } elseif (!empty($mid)) {
        $from_chat_id = $cid;
        $forward_message_id = $mid;
    } else {
        sms($cid, "❌ Bu forward qilingan xabar emas!", null);
        exit();
    }


    $url = "https://c661.coresuz.ru/AnimeUz/send_message.php?chat_id=all_users&from_chat_id=$from_chat_id&message_id=$forward_message_id";

    $response = sms($cid, "⏳ So‘rov yuborilmoqda:", null);
    $message_for_up_id = $response->result->message_id;
    @file_put_contents("update.txt","chat id: $cid\nmessage id: $message_for_up_id");

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $result = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    file_put_contents("debug_forward.txt", "[$cid] chat: $from_chat_id, msg: $forward_message_id, result: $result, error: $error\n", FILE_APPEND);
    exit();
}

if ($text == "alam"){
    bot('editMessageText',[
        'chat_id'=>7775806579,
        'message_id'=>1109184,
        'text'=>"Yuborildi: 16"
        ]);
        sms($cid,"Tayyor",null);
}





// <---- @obito_us va @ITACHI_UCHIHA_SONO_SHARINGAN ---->

if($text == "📊 Statistika"){
    if(in_array($cid,$admin)){
        $res = mysqli_query($connect, "SELECT * FROM `kabinet`");
        $stat = mysqli_num_rows($res);

        // Animelar bo'limidan oxirgi ID olish
        $anime_res = mysqli_query($connect, "SELECT id FROM `animelar` ORDER BY id DESC LIMIT 1");
        $anime_data = mysqli_fetch_assoc($anime_res);
        $last_anime_id = $anime_data['id'];

        // Bugun qo'shilgan foydalanuvchilar sonini hisoblash
        $today = date('d.m.Y');
$today_res = mysqli_query($connect, "SELECT COUNT(*) as count FROM `kabinet`");
$today_result = mysqli_query($connect, "SELECT COUNT(*) as count FROM `user_id` WHERE `sana` = '$today'");

$user_data = mysqli_fetch_assoc($today_result);

if ($user_data) { 
    $new_users_today = $user_data['count'];
} else {
    $new_users_today = 0; 
}

        $ping = sys_getloadavg()[0];
        bot('SendMessage',[
            'chat_id'=>$cid,
            'text'=>"💡 <b>O'rtacha yuklanish:</b> <code>$ping</code>

👥 <b>Foydalanuvchilar:</b> $stat ta

📂 <b>Barcha yuklangan animelar:</b> $last_anime_id ta

📅 <b>Bugun qo'shilgan foydalanuvchilar:</b> $new_users_today ta",
            'parse_mode'=>'html',
            'reply_markup'=>json_encode([
                'inline_keyboard'=>[
                    [['text'=>"Orqaga",'callback_data'=>"boshqarish"]]
                ]
            ])
        ]);
        exit();
    }
}



// <---- @ITACHI_UCHIHA_SONO_SHARINGAN ---->

if($text == "📢 Kanallar"){
	if(in_array($cid,$admin)){
	bot('SendMessage',[
	'chat_id'=>$cid,
	'text'=>"<b>Quyidagilardan birini tanlang:</b>",
	'parse_mode'=>'html',
	'reply_markup'=>json_encode([
	'inline_keyboard'=>[
	[['text'=>"🔐 Majburiy obunalar",'callback_data'=>"majburiy"]],
	[['text'=>"📌 Qo'shimcha kanalar",'callback_data'=>"qoshimchakanal"]],
	]
	])
	]);
	exit();
}
}

if($data == "kanallar"){
	bot('deleteMessage',[
	'chat_id'=>$cid2,
	'message_id'=>$mid2,
	]);
	bot('SendMessage',[
	'chat_id'=>$cid2,
	'text'=>"<b>Quyidagilardan birini tanlang:</b>",
	'parse_mode'=>'html',
	'reply_markup'=>json_encode([
	'inline_keyboard'=>[
	[['text'=>"🔐 Majburiy obunalar",'callback_data'=>"majburiy"]],
	[['text'=>"📌 Qo'shimcha kanalar",'callback_data'=>"qoshimchakanal"]],
]
	])
	]);
	exit();
}

/*INSTAGRAM QO'SHISH FUNKSIYASI  @ITACHI_UCHIHA_SONO_SHARINGAN TOMONIDAN ISHLAB CHIQILDI */

if($data == "qoshimchakanal"){  
     bot('editMessageText',[
        'chat_id'=>$cid2,
        'message_id'=>$mid2,
'text'=>"<b>Qo'shimcha kanallar sozlash bo'limidasiz:</b>",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"🎥 Anime kanal",'callback_data'=>"anime-kanal"]],
[['text'=>"🎁 Ijtimoiy tarmoqlar", 'callback_data'=>"social"]],
[['text'=>"◀️ Orqaga",'callback_data'=>"kanallar"]]
]
])
]);
}

if ($data == 'social') {
    bot('editMessageText', [
        'chat_id' => $cid2,
        'message_id'=>$mid2,
        'text' => "🌐 O'zingizga kerakli 🎁 ijtimoiy tarmoqni tanlang!",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "📸 Instagram", 'callback_data' => 'channel=insta']],
                [['text' => "🎥 YouTube", 'callback_data' => 'channel=youtube']],
            ],
        ]),
    ]);
}

if (strpos($data, 'channel=') === 0) {
    $channel_name = str_replace('channel=', '', $data);

    if ($channel_name == 'insta') {
        bot('editMessageText',[
            'chat_id'=>$cid2,
            'message_id'=>$mid2,
            'text'=>"📸 Instagram ustida qanday amal bajaramiz? 👇",
            'reply_markup'=>json_encode([
            'inline_keyboard'=>[
            [['text'=>"➕ Kanal qo'shish 💬",'callback_data'=>"newchann=instaplus"],['text'=>"🗑 Kanal o'chirish ❌",'callback_data'=>"delchann=instaminus"]],
            [['text'=>"📃 Ro'yhatni ko'rish 📝", 'callback_data'=>'lists=insta']],
        ],
    ]),
]);
    } elseif ($channel_name == 'youtube') {
         bot('editMessageText',[
            'chat_id'=>$cid2,
            'message_id'=>$mid2,
            'text'=>"🎥 YouTube ustida qanday amal bajaamiz? 👇",
            'reply_markup'=>json_encode([
            'inline_keyboard'=>[
            [['text'=>"➕ Kanal qo'shish 🎬",'callback_data'=>"newchann=youtubeplus"],['text'=>"🗑 Kanal o'chirish ❌",'callback_data'=>"delchann=youtube"]],
            [['text'=>"📃 Ro'yhatni ko'rish 📝", 'callback_data'=>'lists=youtube']],
        ],
    ]),
]);
    }
    exit();
}



if (strpos($data, 'newchann=') === 0) {
    $channel_name = str_replace('newchann=', '', $data);
    if($channel_name = 'instaplus'){
        sms($cid2,"📸 <b>Instagram sahifangizga havola:</b>\n\n🌐 <a href='https://www.instagram.com/'>Instagramni ochish uchun bosing!</a> ✨",null);
        put('insta.txt','kanal');
    }elseif($channel_name = 'youtubeplus'){
        sms($cid2,"📸 <b>Instagram sahifangizga havola:</b>\n\n🌐 <a href='https://www.instagram.com/'>Instagramni ochish uchun bosing!</a> ✨",null);
        put('insta.txt','ytkanal');
    }
    exit();
}

if (strpos($data, 'delchann=') === 0) {
         $channel_name = str_replace('delchann=', '', $data);
         if($channel_name == 'instaminus'){
             $channelinsta = get('admin/instagram.txt');
             if(!empty($channelinsta)){
                  edit($cid2,$mid2,"✅ Sizning Instagram profilingiz muvaffaqiyatli o‘chirildi! 🗑️📸",null);
                     unlink('admin/instagram.txt');
             } else {
                  edit($cid2,$mid2,"📸 <b>Sizning Instagram profilingiz mavjud emas!</b> ❌",null);
             } 
         } else{
             $channelinsta = get('admin/youtube.txt');
             if(!empty($channelinsta)){
                  edit($cid2,$mid2,"✅ Sizning Youtube profilingiz muvaffaqiyatli o‘chirildi! 🗑️📸",null);
                     unlink('admin/youtube.txt');
             } else {
                  edit($cid2,$mid2,"📸 <b>Sizning Youtube profilingiz mavjud emas!</b> ❌",null);
             } 
         }
    exit();
}


$insta = get('insta.txt');

if ($insta == 'kanal' && isset($text)) {
    if (strpos($text, 'https://www.instagram.com/') !== false) {
        sms($cid, "✅ Sizning Instagram profilingiz havolasi qabul qilindi:", null);
        unlink('insta.txt');
        put('admin/instagram.txt', $text);
    } elseif (strpos($text, 'https://www.youtube.com/') !== false || strpos($text, 'https://youtu.be/') !== false) {
        sms($cid, "✅ Sizning YouTube profilingiz havolasi qabul qilindi:", null);
        unlink('insta.txt');
        put('admin/youtube.txt', $text);
    } else {
        sms($cid, "❌ Iltimos, to‘g‘ri Instagram yoki YouTube havolasini yuboring!\n\n🔹 **Instagram:** <code>https://www.instagram.com/foydalanuvchi_nomi</code>\n🔹 **YouTube:** <code>https://www.youtube.com/channel/kanal_id</code>", null);
    }
    exit();
}


     if (strpos($data, 'lists=') === 0) {
         $channel_name = str_replace('lists=', '', $data);
         if($channel_name == 'insta'){
             $channelinsta = get('admin/instagram.txt');
             if(!empty($channelinsta)){
                     edit($cid2,$mid2,"🌟 <b>Sizning Instagram profillaringiz:</b> \n\n $channelinsta",null);
             } else {
                     edit($cid2,$mid2,"🌟 <b>Sizning Instagram profilingiz mavjud emas:</b>",null);
             }
         } elseif($channel_name == 'youtube'){
             $channelinsta = get('admin/youtube.txt');
             if(!empty($channelinsta)){
                     edit($cid2,$mid2,"🌟 <b>Sizning YouTube profillaringiz:</b> \n\n $channelinsta",null);
             } else {
                 edit($cid2,$mid2,"🌟 <b>Sizning YouTube profilingiz mavjud emas:</b>",null);
             }
         }
         exit();
    } 
    

    
if ($data == "anime-kanal" || $data == "animekanal2") {
    $step_name = ($data == "anime-kanal") ? "anime-kanal1" : "animekanal2";

    bot('deleteMessage', [
        'chat_id' => $cid2,
        'message_id' => $mid2,
    ]);

    bot('sendMessage', [
        'chat_id' => $cid2,
        'text' => "<i>Kanalingiz manzilini yuborishdan avval botni kanalingizga admin qilib olishingiz kerak!</i>
        
📢 <b>Kerakli kanalni manzilini yuboring:</b>

<b>Namuna:</b> <code>@username</code>",
        'parse_mode' => 'html',
        'reply_markup' => $boshqarish,
    ]);

    file_put_contents("step/$cid2.step", $step_name);
    exit();
}

if ($step == "anime-kanal1" || $step == "animekanal2") {
    if (in_array($cid, $admin)) {
        if (isset($text) && mb_stripos($text, "@") === 0) {
            $get = bot('getChat', ['chat_id' => $text]);

            if (!$get->ok) {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "<b>Kanal topilmadi. Username noto‘g‘ri yoki kanal mavjud emas!</b>",
                    'parse_mode' => 'html'
                ]);
                exit();
            }

            $chat_id = $get->result->id; // Diqqat: @username emas, ID!
            $ch_user = $get->result->username;

            // get bot info
            $me = bot('getMe');
            $bot_username = $me->result->username; // faqat username, boshida @ yo'q

            // Get all admins in the channel
            $admins_list = bot('getChatAdministrators', ['chat_id' => $chat_id]);

            if (!$admins_list->ok) {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "<b>Adminlar ro‘yxatini olishda xatolik. Bot kanalga qo‘shilmagan bo‘lishi mumkin.</b>",
                    'parse_mode' => 'html'
                ]);
                exit();
            }

            $bot_is_admin = false;
            foreach ($admins_list->result as $admin_obj) {
                if ($admin_obj->user->username == $bot_username) {
                    $bot_is_admin = true;
                    break;
                }
            }

            if ($bot_is_admin) {
                $channel_file = ($step == "anime-kanal1") ? "admin/anime_kanal.txt" : "admin/anime_kanal2.txt";
                file_put_contents($channel_file, $text);

                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "<b>✅ Kanal saqlandi:</b> <code>$text</code>",
                    'parse_mode' => 'html',
                    'reply_markup' => $panel
                ]);

                unlink("step/$cid.step");
            } else {
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "<b>❌ Bot ushbu kanalda admin emas.</b>\nIltimos, botni kanalga admin qilib qo‘shing va qayta urinib ko‘ring.",
                    'parse_mode' => 'html'
                ]);
            }
        } else {
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "<b>Kanal manzilini to‘g‘ri yuboring:</b>\nNamuna: <code>@username</code>",
                'parse_mode' => 'html'
            ]);
        }
        exit();
    }
}



if ($data == "majburiy") {
    bot('editMessageText', [
        'chat_id' => $cid2,
        'message_id' => $mid2,
        'text' => "<b>🔐Majburiy obunalarni sozlash bo'limidasiz:</b>",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "➕ Qo'shish", 'callback_data' => "qoshish"]],
                [['text' => "📑 Ro'yxat", 'callback_data' => "royxat"], ['text' => "🗑 O'chirish", 'callback_data' => "ochirish"]],
                [['text' => "🔙Ortga", 'callback_data' => "kanallar"]]
            ]
        ])
    ]);
}

if ($data == "cancel" && in_array($cid2, $admin)) {
    del();
    sms($cid2, "<b>✅Bekor qilindi !</b>", $panel);
}

if ($data == "qoshish") {
    del();
    sms($cid2, "<b>💬Kanal IDsini yuboring !</b>", $boshqarish);
    file_put_contents("step/$cid2.step", "addchannel=id");
    exit();
}

if (stripos($step, "addchannel=") !== false && in_array($cid, $admin)) {
    $ty = str_replace("addchannel=", '', $step);

    if ($ty == "id" && (is_numeric($text) || stripos($text, "-100") !== false)) {
        if (stripos($text, "-100") !== false) $text = str_replace("-100", '', $text);
        $text = "-100" . $text;
        file_put_contents("step/addchannel.txt", $text);
        
        sms($cid, "<b>📊 Nechta obunachi bo'lganda kanal botdan uzilsin !</b>", null);
        file_put_contents("step/$cid.step", "addchannel=users");
        exit();
    } 
    elseif ($ty == "users" && is_numeric($text)) { 
        file_put_contents("step/addchannelUsers.txt", $text);
        
        sms($cid, "<b>🔗Kanal havolasini kiriting !</b>", null);
        file_put_contents("step/$cid.step", "addchannel=link");
        exit();
    } 
    elseif (stripos($text, "https://") !== false) {
        if (preg_match("~https://t\.me/|https://telegram\.dog/|https://telegram\.me/~", $text)) {
            file_put_contents("step/addchannelLink.txt", $text);
            
            sms($cid, "<b>⚠️Ushbu kanal zayafka kanal sifatida qo'shilsinmi?</b>", json_encode([
                'inline_keyboard' => [
                    [['text' => "✅Ha", 'callback_data' => "addChannel=request"], ['text' => "❌Yo‘q", 'callback_data' => "addChannel=lock"]],
                    [['text' => "🚫Bekor qilish", 'callback_data' => "cancel"]]
                ]
            ]));
            unlink("step/$cid2.step");
            exit();
        } else {
            sms($cid, "<b>📍Faqat Telegram uchun ishlaydi!</b>", null);
            exit();
        }
    }
}

if (stripos($data, "addChannel=") !== false && in_array($cid2, $admin)) {
    $ty = str_replace("addChannel=", '', $data);
    $channelId = file_get_contents("step/addchannel.txt");
    $channelLink = file_get_contents("step/addchannelLink.txt");
    $channelUsers = file_get_contents("step/addchannelUsers.txt");
    
    $forurl = "https://api.telegram.org/bot7537896971:AAEsYsVYYSz-feTQlE9gBZPLIbzjEJNbQE4/getChatMemberCount?chat_id=$channelId";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $forurl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

$toliqazo = 0;

    $sql = "INSERT INTO `channels`(`channelId`, `channelType`, `channelLink`, `channelUsers`, `nowMembers`) VALUES ('$channelId', '$ty', '$channelLink', '$channelUsers', '$toliqazo')";

    if ($connect->query($sql)) {
        del();
        sms($cid2, "<b>✅Majburiy obunaga kanal ulandi!</b>", $panel);
        unlink("step/addchannel.txt");
        unlink("step/addchannelLink.txt");
        unlink("step/addchannelUsers.txt");
    } else {
        accl($qid, "⚠️Tizimda xatolik!\n\n" . $connect->error, 1);
    }
    exit();
}


if ($data == "ochirish") {
    $query = $connect->query("SELECT * FROM `channels`");

    if ($query->num_rows > 0) {
        $soni = $query->num_rows;
        $text = "<b>✂️Kanalni uzish uchun kanal raqami ustiga bosing!</b>\n";
        $co = 1;
        while ($row = $query->fetch_assoc()) {
            $text .= "\n<b>$co.</b> " . $row['channelLink'] . " | " . $row['channelType'];
            $uz[] = ['text' => "🗑️$co", 'callback_data' => "channelDelete=" . $row['id']];
            $co++;
        }
        $e = array_chunk($uz, 5);
        $e[] = [['text' => "🔙Ortga", 'callback_data' => "majburiy"]];
        $json = json_encode(['inline_keyboard' => $e]);
        $text .= "\n\n<b>Ulangan kanallar soni:</b> $soni ta";
        edit($cid2, $mid2, $text, $json);
    } else {
        accl($qid, "Hech qanday kanallar ulanmagan!", 1);
    }
}

if (stripos($data, "channelDelete=") !== false && in_array($cid2, $admin)) {
    $ty = str_replace("channelDelete=", '', $data);
    $sql = "DELETE FROM `channels` WHERE `id` = '$ty'";

    if ($connect->query($sql)) {
        accl($qid, "Kanal uzildi✔️");
        $query = $connect->query("SELECT * FROM `channels`");

        if ($query->num_rows > 0) {
            $soni = $query->num_rows;
            $text = "<b>✂️Kanalni uzish uchun kanal raqami ustiga bosing!</b>\n";
            $co = 1;
            $uz = [];
            while ($row = $query->fetch_assoc()) {
                $text .= "\n<b>$co.</b> " . $row['channelLink'] . " | " . $row['channelType'];
                $uz[] = ['text' => "🗑️$co", 'callback_data' => "channelDelete=" . $row['id']];
                $co++;
            }
            $e = array_chunk($uz, 5);
            $e[] = [['text' => "🔙Ortga", 'callback_data' => "majburiy"]];
            $json = json_encode(['inline_keyboard' => $e]);
            $text .= "\n\n<b>Ulangan kanallar soni:</b> $soni ta";
            edit($cid2, $mid2, $text, $json);
        } else {
            del();
            sms($cid2, "<b>☑️Majburiy obuna ulangan kanallar qolmadi!</b>", $panel);
        }
    } else {
        accl($qid, "⚠️Tizimda xatolik!\n\n" . $connect->error, 1);
    }
}

if (mb_stripos($data, "royxat") !== false) {
    $parts = explode('_', $data);
    $page = isset($parts[2]) && is_numeric($parts[2]) && $parts[2] > 0 ? (int)$parts[2] : 1;
    $limit = 5; 


    $query = $connect->query("SELECT * FROM `channels`");
    $total = $query->num_rows;
    if ($total == 0) {
        bot('answerCallbackQuery', [
            'callback_query_id' => $qid,
            'text' => "⚠️ Hech qanday kanal ulanmagan!",
            'show_alert' => true
        ]);
        exit;
    }

    $offset = ($page - 1) * $limit;
    // Limit va offset bilan query
    $query = $connect->query("SELECT * FROM `channels` LIMIT $limit OFFSET $offset");

    $text = "<b>📋 Ulangan kanallar ro'yxati ($page - qism):</b>\n";
    $co = $offset + 1;

    $token = "7537896971:AAEsYsVYYSz-feTQlE9gBZPLIbzjEJNbQE4"; // TOKEN

    while ($row = $query->fetch_assoc()) {
        $kanal_id = $row['channelId'];
        $kanal_azosi = (int)$row['NowMembers'];
        $kanal_azolari = (int)$row['channelUsers'];
        $link = $row['channelLink'];
        $type = $row['channelType'];

        // Obunachi soni olish (API orqali)
        $obunachilar_url = "https://api.telegram.org/bot$token/getChatMembersCount?chat_id=$kanal_id";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $obunachilar_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($response, true);
        $obunachilar_soni = isset($json['result']) ? (int)$json['result'] : 0;

        $hisobla = max(0, $obunachilar_soni - $kanal_azosi);
        $qoldi = max(0, $kanal_azolari - $obunachilar_soni);

        $text .= "\n🔹 <b>$co - Kanal:</b> <a href='$link'>$link</a>\n";
        $text .= "📎 <b>ID:</b> <code>$kanal_id</code>\n";
        $text .= "📌 <b>Turi:</b> <i>$type</i>\n";
        $text .= "👥 <b>Obunachilar:</b> <b>$obunachilar_soni</b>\n";
        $text .= "➕ <b>Qo'shildi:</b> <b>$hisobla</b>\n";
        $text .= "⏳ <b>Qoldi:</b> <b>$qoldi</b> / $kanal_azolari\n";
        $text .= "──────────────\n";

        $co++;
    }

    // Pagination tugmalari
    $total_pages = ceil($total / $limit);

    $buttons = [];

    if ($page > 1) {
        $buttons[] = ['text' => "⬅️ Oldingi", 'callback_data' => "royxat_page_" . ($page - 1)];
    }
    if ($page < $total_pages) {
        $buttons[] = ['text' => "Keyingi ➡️", 'callback_data' => "royxat_page_" . ($page + 1)];
    }

    $keyboard = [];
    if (!empty($buttons)) {
        $keyboard[] = $buttons;
    }
    // Ortga tugmasi
    $keyboard[] = [['text' => "🔙 Ortga", 'callback_data' => "majburiy"]];

    bot('editMessageText', [
        'chat_id' => $cid2,
        'message_id' => $mid2,
        'text' => $text,
        'parse_mode' => 'html',
        'disable_web_page_preview' => true,
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
    exit();
}


// <---- @obito_us ---->

if ($text == "📋 Adminlar" && in_array($cid, $admin)) {
    $buttons = ($cid == $obito_us) ?
    [
        [['text' => "➕ Yangi admin qo'shish", 'callback_data' => "add"]],
        [['text' => "📑 Ro'yxat", 'callback_data' => "list"], ['text' => "🗑 O'chirish", 'callback_data' => "remove"]],
        [['text' => "Orqaga", 'callback_data' => "boshqarish"]]
    ] : [
        [['text' => "📑 Ro'yxat", 'callback_data' => "list"]],
        [['text' => "Orqaga", 'callback_data' => "boshqarish"]]
    ];

    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => "<b>Quyidagilardan birini tanlang:</b>",
        'parse_mode' => 'html',
        'reply_markup' => json_encode(['inline_keyboard' => $buttons])
    ]);
    exit();
}

if ($data == "admins") {
    $buttons = ($cid2 == $obito_us) ?
    [
        [['text' => "➕ Yangi admin qo'shish", 'callback_data' => "add"]],
        [['text' => "📑 Ro'yxat", 'callback_data' => "list"], ['text' => "🗑 O'chirish", 'callback_data' => "remove"]],
        [['text' => "Orqaga", 'callback_data' => "boshqarish"]]
    ] : [
        [['text' => "📑 Ro'yxat", 'callback_data' => "list"]],
        [['text' => "Orqaga", 'callback_data' => "boshqarish"]]
    ];

    bot('deleteMessage', [
        'chat_id' => $cid2,
        'message_id' => $mid2
    ]);

    bot('sendMessage', [
        'chat_id' => $cid2,
        'text' => "<b>Quyidagilardan birini tanlang:</b>",
        'parse_mode' => 'html',
        'reply_markup' => json_encode(['inline_keyboard' => $buttons])
    ]);
    exit();
}

if ($data == "list") {
    $adminList = str_replace($obito_us, '', $admins);
    $text = trim($adminList) ? "<b>👮 Adminlar ro'yxati:</b>\n$adminList" : "<b>Yordamchi adminlar topilmadi!</b>";

    bot('editMessageText', [
        'chat_id' => $cid2,
        'message_id' => $mid2,
        'text' => $text,
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "Orqaga", 'callback_data' => "admins"]]
            ]
        ])
    ]);
    exit();
}

if ($data == "add") {
    bot('deleteMessage', [
        'chat_id' => $cid2,
        'message_id' => $mid2
    ]);

    bot('sendMessage', [
        'chat_id' => $obito_us,
        'text' => "<b>Kerakli foydalanuvchi ID raqamini yuboring:</b>",
        'parse_mode' => 'html',
        'reply_markup' => $boshqarish
    ]);

    file_put_contents("step/$cid2.step", 'add-admin');
    exit();
}

if ($step == "add-admin" && $cid == $obito_us) {
    $result = mysqli_query($connect, "SELECT * FROM user_id WHERE user_id = '$text'");
    $row = mysqli_fetch_assoc($result);

    if (!$row) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "<b>Ushbu foydalanuvchi botdan foydalanmaydi!</b>\n\nBoshqa ID raqamni kiriting:",
            'parse_mode' => 'html'
        ]);
    } elseif (mb_stripos($admins, $text) !== false) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "<b>Ushbu foydalanuvchi allaqachon adminlar ro'yxatida mavjud!</b>\n\nBoshqa ID raqamni kiriting:",
            'parse_mode' => 'html'
        ]);
    } else {
        file_put_contents("admin/admins.txt", ($admins ? "\n" : "") . $text, FILE_APPEND);

        bot('sendMessage', [
            'chat_id' => $obito_us,
            'text' => "<code>$text</code> <b>adminlar ro'yxatiga qo'shildi!</b>",
            'parse_mode' => 'html',
            'reply_markup' => $panel
        ]);

        unlink("step/$cid.step");
    }
    exit();
}

if ($data == "remove") {
    bot('deleteMessage', [
        'chat_id' => $cid2,
        'message_id' => $mid2
    ]);

    bot('sendMessage', [
        'chat_id' => $obito_us,
        'text' => "<b>Kerakli foydalanuvchi ID raqamini yuboring:</b>",
        'parse_mode' => 'html',
        'reply_markup' => $boshqarish
    ]);

    file_put_contents("step/$cid2.step", 'remove-admin');
    exit();
}

if ($step == "remove-admin" && $cid == $obito_us) {
    $result = mysqli_query($connect, "SELECT * FROM user_id WHERE user_id = '$text'");
    $row = mysqli_fetch_assoc($result);

    if (!$row) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "<b>Ushbu foydalanuvchi botdan foydalanmaydi!</b>\n\nBoshqa ID raqamni kiriting:",
            'parse_mode' => 'html'
        ]);
    } elseif (mb_stripos($admins, $text) !== false) {
        $files = file_get_contents("admin/admins.txt");
        $updated = trim(str_replace("\n" . $text, '', $files));
        file_put_contents("admin/admins.txt", $updated);

        bot('sendMessage', [
            'chat_id' => $obito_us,
            'text' => "<code>$text</code> <b>adminlar ro'yxatidan olib tashlandi!</b>",
            'parse_mode' => 'html',
            'reply_markup' => $panel
        ]);

        unlink("step/$cid.step");
    } else {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "<b>Ushbu foydalanuvchi adminlar ro'yxatida mavjud emas!</b>\n\nBoshqa ID raqamni kiriting:",
            'parse_mode' => 'html'
        ]);
    }
    exit();
}

//<---- @obito_us ---->//

if ($text == "🤖 Bot holati") {
	if (in_array($cid, $admin)) {
		if ($holat == "Yoqilgan") {
			$xolat = "O'chirish";
		}
		if ($holat == "O'chirilgan") {
			$xolat = "Yoqish";
		}
		bot('SendMessage', [
			'chat_id' => $cid,
			'text' => "<b>Hozirgi holat:</b> $holat",
			'parse_mode' => 'html',
			'reply_markup' => json_encode([
				'inline_keyboard' => [
					[['text' => "$xolat", 'callback_data' => "bot"]],
					[['text' => "Orqaga", 'callback_data' => "boshqarish"]]
				]
			])
		]);
		exit();
	}
}

if ($data == "xolat") {
	if ($holat == "Yoqilgan") {
		$xolat = "O'chirish";
	}
	if ($holat == "O'chirilgan") {
		$xolat = "Yoqish";
	}
	bot('deleteMessage', [
		'chat_id' => $cid2,
		'message_id' => $mid2,
	]);
	bot('SendMessage', [
		'chat_id' => $cid2,
		'text' => "<b>Hozirgi holat:</b> $holat",
		'parse_mode' => 'html',
		'reply_markup' => json_encode([
			'inline_keyboard' => [
				[['text' => "$xolat", 'callback_data' => "bot"]],
				[['text' => "Orqaga", 'callback_data' => "boshqarish"]]
			]
		])
	]);
	exit();
}

if ($data == "bot") {
	if ($holat == "Yoqilgan") {
		file_put_contents("admin/holat.txt", "O'chirilgan");
		bot('editMessageText', [
			'chat_id' => $cid2,
			'message_id' => $mid2,
			'text' => "<b>Muvaffaqiyatli o'zgartirildi!</b>",
			'parse_mode' => 'html',
			'reply_markup' => json_encode([
				'inline_keyboard' => [
					[['text' => "◀️ Orqaga", 'callback_data' => "xolat"]],
				]
			])
		]);
	} else {
		file_put_contents("admin/holat.txt", "Yoqilgan");
		bot('editMessageText', [
			'chat_id' => $cid2,
			'message_id' => $mid2,
			'text' => "<b>Muvaffaqiyatli o'zgartirildi!</b>",
			'parse_mode' => 'html',
			'reply_markup' => json_encode([
				'inline_keyboard' => [
					[['text' => "◀️ Orqaga", 'callback_data' => "xolat"]],
				]
			])
		]);
	}
}

//<---- @obito_us ---->//

if ($text == "⚙ Asosiy sozlamalar") {
	if (in_array($cid, $admin)) {
		bot('SendMessage', [
			'chat_id' => $cid,
			'text' => "<b>Asosiy sozlamalar bo'limidasiz.</b>",
			'parse_mode' => 'html',
			'reply_markup' => $asosiy,
		]);
		exit();
	}
}

$delturi = file_get_contents("tizim/turi.txt");
$delmore = explode("\n", $delturi);
$delsoni = substr_count($delturi, "\n");
$key = [];
for ($delfor = 1; $delfor <= $delsoni; $delfor++) {
	$title = str_replace("\n", "", $delmore[$delfor]);
	$key[] = ["text" => "$title - ni o'chirish", "callback_data" => "del-$title"];
	$keyboard2 = array_chunk($key, 1);
	$keyboard2[] = [['text' => "➕ Yangi to'lov tizimi qo'shish", 'callback_data' => "new"]];
	$pay = json_encode([
		'inline_keyboard' => $keyboard2,
	]);
}

if ($text == "💳 Hamyonlar") {
	if (in_array($cid, $admin)) {
		if ($turi == null) {
			bot('SendMessage', [
				'chat_id' => $cid,
				'text' => "<b>Quyidagilardan birini tanlang:</b>",
				'parse_mode' => 'html',
				'reply_markup' => json_encode([
					'inline_keyboard' => [
						[['text' => "➕ Yangi to'lov tizimi qo'shish", 'callback_data' => "new"]],
					]
				])
			]);
			exit();
		} else {
			bot('SendMessage', [
				'chat_id' => $cid,
				'text' => "<b>Quyidagilardan birini tanlang:</b>",
				'parse_mode' => 'html',
				'reply_markup' => $pay
			]);
			exit();
		}
	}
}

if ($data == "hamyon") {
	if ($turi == null) {
		bot('deleteMessage', [
			'chat_id' => $cid2,
			'message_id' => $mid2,
		]);
		bot('SendMessage', [
			'chat_id' => $cid2,
			'text' => "<b>Quyidagilardan birini tanlang:</b>",
			'parse_mode' => 'html',
			'reply_markup' => json_encode([
				'inline_keyboard' => [
					[['text' => "➕ Yangi to'lov tizimi qo'shish", 'callback_data' => "new"]],
				]
			])
		]);
		exit();
	} else {
		bot('deleteMessage', [
			'chat_id' => $cid2,
			'message_id' => $mid2,
		]);
		bot('SendMessage', [
			'chat_id' => $cid2,
			'text' => "<b>Quyidagilardan birini tanlang:</b>",
			'parse_mode' => 'html',
			'reply_markup' => $pay
		]);
		exit();
	}
}

//<---- @obito_us ---->//

if (mb_stripos($data, "del-") !== false) {
	$ex = explode("-", $data);
	$tur = $ex[1];
	$k = str_replace("\n" . $tur . "", "", $turi);
	file_put_contents("tizim/turi.txt", $k);
	bot('deleteMessage', [
		'chat_id' => $cid2,
		'message_id' => $mid2,
	]);
	bot('SendMessage', [
		'chat_id' => $cid2,
		'text' => "<b>To'lov tizimi o'chirildi!</b>",
		'parse_mode' => 'html',
		'reply_markup' => $asosiy
	]);
	deleteFolder("tizim/$tur");
}

if ($data == "new") {
	bot('deleteMessage', [
		'chat_id' => $cid2,
		'message_id' => $mid2,
	]);
	bot('sendMessage', [
		'chat_id' => $cid2,
		'text' => "<b>Yangi to'lov tizimi nomini yuboring:</b>",
		'parse_mode' => 'html',
		'reply_markup' => $boshqarish
	]);
	file_put_contents("step/$cid2.step", 'turi');
	exit();
}

if ($step == "turi") {
	if (in_array($cid, $admin)) {
		if (isset($text)) {
			mkdir("tizim/$text");
			file_put_contents("tizim/turi.txt", "$turi\n$text");
			file_put_contents("step/test.txt", $text);
			bot('SendMessage', [
				'chat_id' => $cid,
				'text' => "<b>Ushbu to'lov tizimidagi hamyoningiz raqamini yuboring:</b>",
				'parse_mode' => 'html',
			]);
			file_put_contents("step/$cid.step", 'wallet');
			exit();
		}
	}
}


if ($step == "wallet") {
	if (in_array($cid, $admin)) {
		if (is_numeric($text) == "true") {
			file_put_contents("tizim/$test/wallet.txt", "$wallet\n$text");
			bot('SendMessage', [
				'chat_id' => $cid,
				'text' => "<b>Ushbu to'lov tizimi orqali hisobni to'ldirish bo'yicha ma'lumotni yuboring:</b>

<i>Misol uchun, \"Ushbu to'lov tizimi orqali pul yuborish jarayonida izoh kirita olmasligingiz mumkin. Ushbu holatda, biz bilan bog'laning. Havola: @obito_us</i>\"",
				'parse_mode' => 'html',
			]);
			file_put_contents("step/$cid.step", 'addition');
			exit();
		} else {
			sms($cid,"<b>Faqat raqamlardan foydalaning!</b>",null);
			exit();
		}
	}
}

if ($step == "addition") {
	if (in_array($cid, $admin)) {
		if (isset($text)) {
			file_put_contents("tizim/$test/addition.txt", "$addition\n$text");
			sms($cid,"<b>Yangi to'lov tizimi qo'shildi!</b>",$asosiy);
			unlink("step/$cid.step");
			unlink("step/test.txt");
			exit();
		}
	}
}


// <---@ITACHI_CUHIHA_SONO_SHARINGAN---> \\

// Shorts yuklash
$shorts = file_get_contents("shorts/$cid.shorts");

if ($text == "🎯 Shorts yuklash" and in_array($cid, $admin)) {
    sms($cid, "<b>📕 Shorts nomini kiriting:</b>", $boshqarish);
    file_put_contents("shorts/$cid.shorts", "shorts-name");
    exit();
}

if ($shorts == "shorts-name" and in_array($cid, $admin)) {
    if (!empty($text)) {
        if (!containsEmoji($text)) {
            $text = $connect->real_escape_string($text);
            file_put_contents("shorts/shorts1.txt", $text);
            sms($cid, "<b>⌛️ Shorts vaqtini kiriting:</b>", $boshqarish);
            file_put_contents("shorts/$cid.shorts", "shorts-anime");
        } else {
            sms($cid, "<b>⚠️ Emoji va maxsus belgilardan foydalanmang!</b>", null);
        }
        exit();
    }
}

if ($shorts == "shorts-anime" and in_array($cid, $admin)) {
    if (!empty($text)) {
        if (!containsEmoji($text)) {
            $text = $connect->real_escape_string($text);
            file_put_contents("shorts/shorts2.txt", $text);
            sms($cid, "<b>💎 Anime kodini kiriting yoki <code>NULL</code> yozing:</b>", $boshqarish);
            file_put_contents("shorts/$cid.shorts", "shorts-video");
        } else {
            sms($cid, "<b>⚠️ Emoji va maxsus belgilardan foydalanmang!</b>", null);
        }
        exit();
    }
}

if ($shorts == "shorts-video" and in_array($cid, $admin)) {
    if (!empty($text)) {
        $text = $connect->real_escape_string($text);
        file_put_contents("shorts/shorts3.txt", $text);
        sms($cid, "<b>📹 1 daqiqa 40 soniyadan oshmagan video yuboring:</b>", $boshqarish);
        file_put_contents("shorts/$cid.shorts", "shorts-correct");
        exit();
    } else {
        sms($cid, "<b>Iltimos, to'g'ri ma'lumot kiriting!</b>", $boshqarish);
    }
}

if ($shorts == "shorts-correct" && in_array($cid, $admin)) {
    if (isset($message->video)) {
        $video = $message->video;
        if ($video->duration <= 100) {
            $file_id = $video->file_id;

            $nomi = file_get_contents("shorts/shorts1.txt");
            $vaqti = file_get_contents("shorts/shorts2.txt");
            $anime_ids = file_get_contents("shorts/shorts3.txt");

            $query = "INSERT INTO `shorts` (`shorts_id`, `name`, `time`, `anime_id`) VALUES ('$file_id', '$nomi', '$vaqti', '$anime_ids')";
            if ($connect->query($query)) {
                $code = $connect->insert_id;
                sms($cid, "<b>✅ Shorts qo'shildi!</b>\n\n<b>Kodi:</b> <code>$code</code>", $panel);

                // Fayllarni o'chirish
                unlink("shorts/shorts1.txt");
                unlink("shorts/shorts2.txt");
                unlink("shorts/shorts3.txt");
                unlink("shorts/$cid.shorts");
            } else {
                sms($cid, "<b>⚠️ Xatolik yuz berdi!</b>\n\n<code>{$connect->error}</code>", $panel);
                file_put_contents("shorts/$cid.shorts", "shorts-complate");
            }
        } else {
            sms($cid, "<b>⚠️ Video 60 soniyadan oshmasligi kerak!</b>", $panel);
                file_put_contents("shorts/$cid.shorts", "shorts-complate");
        }
    } else {
    }
    exit();
} else {

}
// <---- @ITACHI_UCHIHA_SONO_SHARINGAN ---->

if ($text == "🎥 Animelar sozlash" and in_array($cid, $admin)) {
	sms($cid, "<b>Quyidagilardan birini tanlang:</b>", json_encode([
		'inline_keyboard' => [
			[['text' => "➕ Anime qo'shish", 'callback_data' => "add-anime"]],
			[['text' => "📥 Qism qo'shish", 'callback_data' => "add-episode"]],
			[['text' => "📝 Anime tahrirlash", 'callback_data' => "edit-anime"]],
		]
	]));
	exit();
}

if ($data == "add-anime") {
	del();
	sms($cid2, "<b>🍿 Anime nomini kiriting:</b>", $boshqarish);
	put("step/$cid2.step", "anime-name");
}

if ($step == "anime-name" and in_array($cid, $admin)) {
	if (isset($text)) {
		if (containsEmoji($text) == false) {
			$text = $connect->real_escape_string($text);
			put("step/test.txt", $text);
			sms($cid, "<b>🎥 Jami qismlar sonini kiriting:</b>", $boshqarish);
			put("step/$cid.step", "anime-episodes");
			exit();
		} else {
			sms($cid, "<b>⚠️ Anime qo'shishda emoji va shunga o'xshash maxsus belgilardan foydalanish taqiqlangan!</b>

Qayta urining", null);
		}
	}
}

if ($step == "anime-episodes" and in_array($cid, $admin)) {
	if (isset($text)) {
		$text = $connect->real_escape_string($text);
		put("step/test2.txt", $text);
		sms($cid, "<b>🌍 Qaysi davlat ishlab chiqarganini kiriting:</b>", $boshqarish);
		put("step/$cid.step", "anime-country");
		exit();
	}
}

if ($step == "anime-country" and in_array($cid, $admin)) {
	if (isset($text)) {
		$text = $connect->real_escape_string($text);
		put("step/test3.txt", $text);
		sms($cid, "<b>🇺🇿 Qaysi tilda ekanligini kiriting:</b>", $boshqarish);
		put("step/$cid.step", "anime-language");
		exit();
	}
}

if ($step == "anime-language" and in_array($cid, $admin)) {
	if (isset($text)) {
		$text = $connect->real_escape_string($text);
		put("step/test4.txt", $text);
		sms($cid, "<b>📆 Qaysi yilda ishlab chiqarilganini kiriting:</b>", $boshqarish);
		put("step/$cid.step", "anime-year");
		exit();
	}
}

if ($step == "anime-year" and in_array($cid, $admin)) {
	if (isset($text)) {
		$text = $connect->real_escape_string($text);
		put("step/test5.txt", $text);
		sms($cid, "<b>🎞 Janrlarini kiriting:</b>

<i>Na'muna: Drama, Fantastika, Sarguzash</i>", $boshqarish);
		put("step/$cid.step", "anime-genre");
		exit();
	}
}

if ($step == "anime-genre" and in_array($cid, $admin)) {
    if (isset($text)) {
        $text = $connect->real_escape_string($text);
        put("step/test6.txt", $text);
        sms($cid, "<b>🏞 Rasmini yoki 60 soniyadan oshmagan video yuboring:</b>", $boshqarish);
        put("step/$cid.step", "anime-picture");
        exit();
    }
}

// Dasturchi <-- @ITACHI_UCHIHA_SONO_SHARINGAN---> \\

if ($step == "anime-picture" and in_array($cid, $admin)) {
    if (isset($message->photo) || isset($message->video)) {
        if (isset($message->photo)) {
            $file_id = $message->photo[count($message->photo) - 1]->file_id;
        }
        elseif (isset($message->video)) {
            if ($message->video->duration <= 100) {
                $file_id = $message->video->file_id;
            } else {
                sms($cid, "<b>⚠️ Video 1 daqiqa 40 soniyadan oshmasligi kerak!</b>", $panel);
                exit();
            }
        }

        // Ma'lumotlarni olish
        $nom = get("step/test.txt");
        $qismi = get("step/test2.txt");
        $davlati = get("step/test3.txt");
        $tili = get("step/test4.txt");
        $yili = get("step/test5.txt");
        $janri = get("step/test6.txt");
        $date = date('H:i d.m.Y');

        // SQL so'rov
        if ($connect->query("INSERT INTO `animelar` (`nom`, `rams`, `qismi`, `davlat`, `tili`, `yili`, `janri`, `qidiruv`, `like`, `deslike`, `sana`) VALUES ('$nom', '$file_id', '$qismi', '$davlati', '$tili', '$yili', '$janri', '0', '0', '0', '$date')") == TRUE) {
            $code = $connect->insert_id;
            sms($cid, "<b>✅ Anime qo'shildi!</b>\n\n<b>Anime kodi:</b> <code>$code</code>", $panel);
            unlink("step/$cid.step");
            unlink("step/test.txt");
            unlink("step/test2.txt");
            unlink("step/test3.txt");
            unlink("step/test4.txt");
            unlink("step/test5.txt");
            unlink("step/test6.txt");
            exit();
        } else {
            sms($cid, "<b>⚠️ Xatolik!</b>\n\n<code>$connect->error</code>", $panel);
            unlink("step/$cid.step");
            unlink("step/test.txt");
            unlink("step/test2.txt");
            unlink("step/test3.txt");
            unlink("step/test4.txt");
            unlink("step/test5.txt");
            unlink("step/test6.txt");
            exit();
        }
    } else {
        sms($cid, "<b>⚠️ Iltimos, rasm yoki 60 soniyadan oshmagan video yuboring!</b>", $panel);
    }
}

if ($data == "add-episode") {
	del();
	sms($cid2, "<b>🔢 Anime kodini kiriting:</b>", $boshqarish);
	put("step/$cid2.step", "episode-code");
}

if ($step == "episode-code" and in_array($cid, $admin)) {
	if (is_numeric($text)) {
		$text = $connect->real_escape_string($text);
		put("step/test.txt", $text);
		sms($cid, "<b>🎥 Ushbu kodga tegishlik anime qismini yuboring:</b>", $boshqarish);
		put("step/$cid.step", "episode-video");
		exit();
	}
}

if ($step == "episode-video" and in_array($cid, $admin)) {
	if (isset($message->video)) {
		$file_id = $message->video->file_id;
		$id = get("step/test.txt");
		$qism = $connect->query("SELECT * FROM anime_datas WHERE id = $id")->num_rows;
		$qismi = $qism + 1;
		$sana = date('H:i:s d.m.Y');
		if ($connect->query("INSERT INTO anime_datas(id,file_id,qism,sana) VALUES ('$id','$file_id','$qismi','$sana')") == TRUE) {
			$code = $connect->insert_id;
			sms($cid, "<b>✅ $id raqamli animega $qismi-qism yuklandi!</b>

<i>Yana yuklash uchun keyingi qismni yuborsangiz bo'ldi</i>", null);

$result = $connect->query("SELECT nom, id FROM animelar WHERE id = $id");
$results = $connect->query("SELECT user_id FROM saved WHERE `anime_id` = $id");

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $nomi = $row['nom'];

    if ($results->num_rows > 0) {
        while ($userRow = $results->fetch_assoc()) {
            $userId = $userRow['user_id'];
$tugma = json_encode([
        'inline_keyboard'=> [
        [['text' => " Tomosha qilish ", 'url' => 'https://t.me/' . $bot . '?start=' . $id]],
        ]
    ]);
            sms($userId,"Siz yoqtirgan <code>" . $nomi . "</code> animesiga yangi " . $qismi . "-qism yuklandi \n<b>Anime Id:</b> $id",$tugma);
        }
    }
} else {
    sms(7775806579,"Aniq animeni topa olmadim.",null);
}

exit();
		} else {
			sms($cid, "<b>⚠️ Xatolik!</b>\n\n<code>$connect->error</code>", $panel);
			unlink("step/$cid.step");
			unlink("step/test.txt");
			unlink("step/test2.txt");
			exit();
		}
	}
}

if ($data == "edit-anime") {
	edit($cid2, $mid2, "<b>Tahrirlamoqchi bo'lgan animeni tanlang:</b>", json_encode([
		'inline_keyboard' => [
			[['text' => "Anime ma'lumotlarini", 'callback_data' => "editType-animes"]],
			[['text' => "Anime qismini", 'callback_data' => "editType-anime_datas"]]
		]
	]));
}

if (mb_stripos($data, "editType-") !== false) {
	$ex = explode("-", $data)[1];
	put("step/$cid2.tip", $ex);
	del();
	sms($cid2, "<b>Anime kodini kiriting:</b>", $boshqarish);
	put("step/$cid2.step", "edit-anime");
}

if($step == "edit-anime"){
$tip=get("step/$cid.tip");
if($tip=="animes"){
$result=mysqli_query($connect,"SELECT * FROM animelar WHERE id = $text");
$row=mysqli_fetch_assoc($result);
if($row){
$kb=json_encode([
'inline_keyboard'=>[
[['text'=>"Nomini tahrirlash",'callback_data'=>"editAnime-nom-$text"]],
[['text'=>"Qismini tahrirlash",'callback_data'=>"editAnime-qismi-$text"]],
[['text'=>"Davlatini tahrirlash",'callback_data'=>"editAnime-davlat-$text"]],
[['text'=>"Tilini tahrirlash",'callback_data'=>"editAnime-tili-$text"]],
[['text'=>"Yilini tahrirlash",'callback_data'=>"editAnime-yili-$text"]],
[['text'=>"Janrini tahrirlash",'callback_data'=>"editAnime-janri-$text"]],
[['text'=>"Anime rasmini tahrirlash",'callback_data'=>"editAnime-image-$text"]],
]]);
sms($cid,"<b>❓ Nimani tahrirlamoqchisiz?</b>",$kb);
unlink("step/$cid2.step");
exit();
}else{
sms($cid,"<b>❗ Anime mavjud emas, qayta urinib ko'ring!</b>",null);
exit();
}
}else{
$result=mysqli_query($connect,"SELECT * FROM animelar WHERE id = $text");
$row=mysqli_fetch_assoc($result);
if($row){
sms($cid,"<b>Qism raqamini yuboring:</b>",$boshqarish);
put("step/$cid.step","anime-epEdit=$text");
exit();
}else{
sms($cid,"<b>❗ Anime mavjud emas, qayta urinib ko'ring!</b>",null);
exit();
}
}
}


if(mb_stripos($step,"anime-epEdit=")!==false){
$ex = explode("=",$step);
$id = $ex[1];
$result=mysqli_query($connect,"SELECT * FROM anime_datas WHERE id = $id AND qism = $text");
$row=mysqli_fetch_assoc($result);
if($row){
$kb=json_encode([
'inline_keyboard'=>[
[['text'=>"Anime kodini tahrirlash",'callback_data'=>"editEpisode-id-$id-$text"]],
[['text'=>"Qismini tahrirlash",'callback_data'=>"editEpisode-qism-$id-$text"]],
[['text'=>"Videoni tahrirlash",'callback_data'=>"editEpisode-file_id-$id-$text"]],
]]);
sms($cid,"<b>❓ Nimani tahrirlamoqchisiz?</b>",$kb);
unlink("step/$cid.step");
exit();
}else{
sms($cid,"<b>❗ Ushbu animeda $text-qism mavjud emas, qayta urinib ko'ring.</b>",null);
exit();
}
}

if(mb_stripos($data,"editAnime-")!==false){
del();
sms($cid2,"<b>Yangi qiymatini kiriting:</b>",$boshqarish);
put("step/$cid2.step",$data);
}



if (mb_stripos($step, "editAnime-") !== false) {
    $ex = explode("-", $step);
    $tip = $ex[1];
    $id = $ex[2];

    if ($tip == "image") {
        if (isset($message->photo) || isset($message->video)) {
            if (isset($message->photo)) {
                $file_id = $message->photo[count($message->photo) - 1]->file_id;
            } elseif (isset($message->video)) {
                if ($message->video->duration <= 60) {
                    $file_id = $message->video->file_id;
                } else {
                    sms($cid, "<b>⚠️ Video 60 soniyadan oshmasligi kerak!</b>", $panel);
                    exit();
                }
            }

            // SQL bazaga rasm yoki video file_id ni saqlash
            $query = "UPDATE animelar SET rams = '" . mysqli_real_escape_string($connect, $file_id) . "' WHERE id = $id";
            if (mysqli_query($connect, $query)) {
                sms($cid, "<b>✅ Rasm muvaffaqiyatli yangilandi!</b>", $panel);
            } else {
                sms($cid, "<b>❗ Rasmni yangilashda xatolik yuz berdi!</b>", $panel);
            }
            exit();
        } else {
            sms($cid, "<b>⚠️ Iltimos, rasm yoki 60 soniyadan oshmagan video yuboring!</b>", $panel);
            exit();
        }
    } else {
        if ($tip == "qismi" || $tip == "yili") {
            if (is_numeric($text)) {
                mysqli_query($connect, "UPDATE animelar SET `$tip`='$text' WHERE id = $id");
                sms($cid, "<b>✅ Saqlandi.</b>", null);
                unlink("step/$cid.step");
                exit();
            } else {
                sms($cid, "<b>❗Faqat raqamlardan foydalaning.</b>", null);
                exit();
            }
        } else {
            if (isset($text)) {
                mysqli_query($connect, "UPDATE animelar SET `$tip`='$text' WHERE id = $id");
                sms($cid, "<b>✅ Saqlandi.</b>", null);
                unlink("step/$cid.step");
                exit();
            } else {
                sms($cid, "<b>❗Faqat matnlardan foydalaning.</b>", null);
                exit();
            }
        }
    }
}


if(mb_stripos($data,"editEpisode-")!==false){
del();
sms($cid2,"<b>Yangi qiymatini kiriting:</b>",$boshqarish);
put("step/$cid2.step",$data);
}

if(mb_stripos($step,"editEpisode-")!==false){
$ex = explode("-",$step);
$tip = $ex[1];
$id = $ex[2];
$qism_raqami = $ex[3];
if($tip=="file_id"){
if(isset($message->video)){
$file_id = $message->video->file_id;
mysqli_query($connect,"UPDATE anime_datas SET `file_id`='$file_id' WHERE id = $id AND qism = $qism_raqami");
sms($cid,"<b>✅ Saqlandi.</b>",null);
unlink("step/$cid.step");
exit();
}else{
sms($cid,"<b>❗Faqat videodan foydalaning.</b>",null);
exit();
}
}else{
if(is_numeric($text)){
mysqli_query($connect,"UPDATE anime_datas SET `$tip`='$text' WHERE id = $id AND qism = $qism_raqami");
sms($cid,"<b>✅ Saqlandi.</b>",null);
unlink("step/$cid.step");
exit();
}else{
sms($cid,"<b>❗Faqat raqamlardan foydalaning.</b>",null);
exit();
}
}
}
// <---- @obito_us ---->

if ($text == "*️⃣ Birlamchi sozlamalar") {
	if (in_array($cid, $admin)) {
		sms($cid, "<b>Hozirgi birlamchi sozlamalar:</b>

<i>1. Valyuta - $valyuta
2. VIP narxi - $narx $valyuta
3. Studiya nomi - $studio_name</i>", json_encode([
				'inline_keyboard' => [
					[['text' => "1", 'callback_data' => "valyuta"], ['text' => "2", 'callback_data' => "vnarx"], ['text' => "3", 'callback_data' => "studio_name"]],
				]
			]));
		exit();
	}
}

if ($data == "birlamchi") {
	edit($cid2, $mid2, "<b>Hozirgi birlamchi sozlamalar:</b>

<i>1. Valyuta - $valyuta
2. VIP narxi - $narx $valyuta</i>", json_encode([
			'inline_keyboard' => [
				[['text' => "1", 'callback_data' => "valyuta"], ['text' => "2", 'callback_data' => "vnarx"]],
			]
		]));
	exit();
}

if ($data == "valyuta") {
	del();
	sms($cid2, "📝 <b>Yangi qiymatni yuboring:</b>", $boshqarish);
	put("step/$cid2.step", 'valyuta');
	exit();
}

if ($step == "valyuta" and in_array($cid, $admin)) {
	if (isset($text)) {
		put("admin/vip.txt", $text);
		sms($cid, "<b>✅ Saqlandi.</b>", $panel);
		unlink("step/$cid.step");
		exit();
	}
}

if ($data == "vnarx") {
	del();
	sms($cid2, "📝 <b>Yangi qiymatni yuboring:</b>", $boshqarish);
	put("step/$cid2.step", 'vnarx');
	exit();
}

if ($step == "vnarx" and in_array($cid, $admin)) {
	if (isset($text)) {
		put("admin/vip.txt", $text);
		sms($cid, "<b>✅ Saqlandi.</b>", $panel);
		unlink("step/$cid.step");
		exit();
	}
}

if ($data == "studio_name") {
	del();
	sms($cid2, "📝 <b>Yangi qiymatni yuboring:</b>", $boshqarish);
	put("step/$cid2.step", 'vnarx');
	exit();
}

if ($step == "studio_name" and in_array($cid, $admin)) {
	if (isset($text)) {
		put("admin/studio_name.txt", $text);
		sms($cid, "<b>✅ Saqlandi.</b>", $panel);
		unlink("step/$cid.step");
		exit();
	}
}

// <---- @obito_us ---->

if ($text == "📃 Matnlar" and in_array($cid, $admin)) {
	sms($cid, "<b>Quyidagilardan birini tanlang:</b>", json_encode([
		'inline_keyboard' => [
			[['text' => "Boshlang'ich matni", 'callback_data' => "matn1"]],
			[['text' => "Qo'llanma", 'callback_data' => "matn2"]],
			[['text' => "🔖 Homiy matni", 'callback_data' => "matn5"]],
		]
	]));
	exit();
}

if ($data == "matn1") {
	del();
	sms($cid2, "<b>Boshlang'ich matnini yuboring:</b>", $boshqarish);
	put("step/$cid2.step", 'matn1');
	exit();
}

if ($step == "matn1" and in_array($cid, $admin)) {
	if (isset($text)) {
		put("matn/start.txt", $text);
		sms($cid, "<b>✅ Saqlandi.</b>", $panel);
		unlink("step/$cid.step");
		exit();
	}
}

if ($data == "matn2") {
	del();
	sms($cid2, "<b>Qo'llanma matnini yuboring::</b>", $boshqarish);
	put("step/$cid2.step", 'matn2');
	exit();
}

if ($step == "matn2" and in_array($cid, $admin)) {
	if (isset($text)) {
		put("matn/qollanma.txt", $text);
		sms($cid, "<b>✅ Saqlandi.</b>", $panel);
		unlink("step/$cid.step");
		exit();
	}
}

if ($data == "matn5") {
	del();
	sms($cid2, "<b>Homiy matnini yuboring:</b>", $boshqarish);
	put("step/$cid2.step", 'matn5');
	exit();
}

if ($step == "matn5" and in_array($cid, $admin)) {
	if (isset($text)) {
		put("matn/homiy.txt", $text);
		sms($cid, "<b>✅ Saqlandi.</b>", $panel);
		unlink("step/$cid.step");
		exit();
	}
}

// <---- @obito_us ---->

if ($text == "🎛 Tugmalar" and in_array($cid, $admin)) {
	sms($cid, "<b>Quyidagilardan birini tanlang:</b>", json_encode([
		'inline_keyboard' => [
			[['text' => "🖥 Asosiy menyudagi tugmalar", 'callback_data' => "asosiy"]],
			[['text' => "⚠️ O'z holiga qaytarish", 'callback_data' => "reset"]],
		]
	]));
	exit();
}

if ($data == "tugmalar") {
	del();
	sms($cid2, "<b>Quyidagilardan birini tanlang:</b>", json_encode([
		'inline_keyboard' => [
			[['text' => "🖥 Asosiy menyudagi tugmalar", 'callback_data' => "asosiy"]],
			[['text' => "⚠️ O'z holiga qaytarish", 'callback_data' => "reset"]],
		]
	]));
	exit();
}


if ($data == "reset") {
	edit($cid2, $mid2, "<b>Barcha tahrirlangan tugmalar bilan bog'liq sozlamalar o'chirib yuboriladi va birlamchi sozlamalar o'rnatiladi.</b>

<i>Ushbu jarayonni davom ettirsangiz, avvalgi sozlamalarni tiklay olmaysiz, rozimisiz?</i>", json_encode([
			'inline_keyboard' => [
				[['text' => "✅ Roziman", 'callback_data' => 'roziman']],
				[['text' => "◀️ Orqaga", 'callback_data' => "tugmalar"]],
			]
		]));
}

if ($data == "roziman") {
	edit($cid2, $mid2, "<b>Tugma sozlamalari o'chirilib, birlamchi sozlamalar o'rnatildi.</b>", json_encode([
		'inline_keyboard' => [
			[['text' => "Orqaga", 'callback_data' => "tugmalar"]],
		]
	]));
	deleteFolder("tugma");
}

if ($data == "asosiy") {
	edit($cid2, $mid2, "<b>Quyidagilardan birini tanlang:</b>", json_encode([
		'inline_keyboard' => [
			[['text' => $key1, 'callback_data' => "tugma=key1"]],
			[['text' => $key2, 'callback_data' => "tugma=key2"], ['text' => $key3, 'callback_data' => "tugma=key3"]],
			[['text' => $key4, 'callback_data' => "tugma=key4"], ['text' => $key5, 'callback_data' => "tugma=key5"]],
			[['text' => $key6, 'callback_data' => "tugma=key6"]],
			[['text' => "Orqaga", 'callback_data' => "tugmalar"]]
		]
	]));
}

if (mb_stripos($data, "tugma=") !== false) {
	del();
	sms($cid2, "<b>Tugma uchun yangi nom yuboring:</b>", $boshqarish);
	put("step/$cid2.step", $data);
	exit();
}

if (mb_stripos($step, "tugma=") !== false and in_array($cid, $admin)) {
	$tip = explode("=", $step)[1];
	if (isset($text)) {
		put("tugma/$tip.txt", $text);
		sms($cid, "<b>Qabul qilindi!</b>

<i>Tugma nomi</i> <b>$text</b> <i>ga o'zgartirildi.</i>", $panel);
		unlink("step/$cid.step");
		exit();
	}
}

if (isset($message) and empty($step)) {
	$text = mysqli_real_escape_string($connect, $text);
	$kb = json_encode([
		'inline_keyboard' => $keyboard2,
	]);
	if (!$c) {
		sms($cid, "🙁 Uzur! Siz yuborgan " . $text . " Xabari uchun mos bo'limni topa olmadim boshqa so'rovni yuborsangiz ! $cid", null);
	} else {
		bot('sendMessage', [
			'chat_id' => $cid,
			'reply_to_message_id' => $mid,
			'text' => "<b>⬇️ Qidiruv natijalari:</b>",
			'parse_mode' => "html",
			'reply_markup' => $kb
		]);
	}
}