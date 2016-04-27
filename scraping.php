<?php
require_once 'simple_html_dom.php';

$html = file_get_html('http://www.tamurayukari.com/');

if ($html === false) {
    die();
}

@$value = file_get_contents('content.txt');

/* 最終更新日時 ex) 2016.04.01 */
$date = $html->find('div[id=news_table] th', 0)->plaintext;

/* 更新内容 */
$content = $html->find('td a', 0)->plaintext;
$content = trim($content);

/* 更新URL */
$url = $html->find('div[id=news_table] a', 0)->href;
$url = trim(toGetShortUrl($url));

if ($value !== $content) {
    file_put_contents('content.txt', $content);

    $f_mids = file('./mids');

    foreach ($f_mids as $row) {
        $text = "【田村ゆかり公式サイト通知BOTよりお知らせ】\r\n田村ゆかり公式サイトが更新されました！\r\n$content\r\n詳しくはこちら $url";
        $response_format_text = ['contentType' => 1, "toType" => 1, "text" => $text];
        $post_data = [
            "to" => [trim($row)],
            "toChannel" => "1383378250",
            "eventType" => "138311608800106203",
            "content" => $response_format_text
        ];

        toPost($post_data);
    }
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

function toGetShortUrl($url)
{
    $api = '';

    $data = array(
        'longUrl' => $url
    );
    $data = json_encode($data);

    $header = array(
        "Content-Type: application/json",
        "Content-Length: " . strlen($data)
    );

    $context = array(
        "http" => array(
            "method" => "POST",
            "header" => implode("\r\n", $header),
            "content" => $data
        )
    );

    $result = file_get_contents("https://www.googleapis.com/urlshortener/v1/url?key=${api}", false,
        stream_context_create($context));
    $result = json_decode($result);

    return $result->id;
}
