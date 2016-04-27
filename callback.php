<?php
/**
 * $otType は 登録時とブロック時にリクエストが飛んで来る。
 * 4 = 友だち追加
 * 8 = ブロック
 */
require_once 'twitteroauth.php';

/* LINE からのアクセスか検証 */
$channel_secret = "";
$headers = getallheaders();
$content = file_get_contents('php://input');

if (base64_decode($headers['X-LINE-CHANNELSIGNATURE']) === hash_hmac('sha256', $content, $channel_secret, true)) {
    $content = json_decode($content);
    $f_mids = file_get_contents('./mids');
    $opType = $content->result[0]->content->opType;
    $mid = $content->result[0]->content->params[0];
} else {
    die();
}

/*
 * 最初の登録
 */
if ($opType === 4 && strpos($f_mids, $mid) === false) {
    $text = "ご登録していただきありがとうございます。\r\n田村ゆかりさん公式サイトが更新される度にLINEでお知らせいたします。\r\n尚、ご不要になった際には当アカウントをブロックすることで解除されます。";
    $response_format_text = ['contentType' => 1, "toType" => 1, "text" => $text];
    $post_data = [
        "to" => [$mid],
        "toChannel" => 1383378250,
        "eventType" => "138311608800106203",
        "content" => $response_format_text
    ];

    file_put_contents('./mids', $mid, FILE_APPEND | LOCK_EX);
    $result = toPost($post_data);
    toTweet("LINE BOT 登録された。\r\n" . date("Y/m/d g:i"));

    die();
}

/**
 * ブロック時
 * もしかしたら空行ができるかも いやできるね
 */
if ($opType === 8 && strpos($f_mids, $mid) === true) {
    $a = preg_replace("/$mid/", '', $f_mids);
    unlink('./mids');
    file_put_contents('./mids', $a);

    toTweet("LINE BOT 削除された。\r\n" . date("Y/m/d g:i"));
}

function toPost($post_data)
{
    $ch = curl_init("https://trialbot-api.line.me/v1/events");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json; charser=UTF-8',
        'X-Line-ChannelID: ',
        'X-Line-ChannelSecret: ',
        'X-Line-Trusted-User-With-ACL: '
    ));
    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}

function toTweet($getMessage)
{
    $sConsumerKey = "";
    $sConsumerSecret = "";
    $sAccessToken = "";
    $sAccessTokenSecret = "";
    $twObj = new TwitterOAuth($sConsumerKey, $sConsumerSecret, $sAccessToken, $sAccessTokenSecret);
    $sTweet = "@lu_iskun " . $getMessage;
    $vRequest = $twObj->OAuthRequest("https://api.twitter.com/1.1/statuses/update.json", "POST",
        array("status" => $sTweet));
}

function getallheaders()
{
    $headers = '';
    foreach ($_SERVER as $name => $value) {
        if (substr($name, 0, 5) == 'HTTP_') {
            $headers[strtoupper(str_replace(' ', '-', ucwords(str_replace('_', ' ', substr($name, 5)))))] = $value;
        }
    }
    return $headers;
}

