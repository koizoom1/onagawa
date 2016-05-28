<?php
require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;

$app = new Silex\Application();

$app->post('/callback', function (Request $request) use ($app) {
    $client = new GuzzleHttp\Client();

    $body = json_decode($request->getContent(), true);
    foreach ($body['result'] as $msg) {
        //if (!preg_match('/(ぬるぽ|ヌルポ|ﾇﾙﾎﾟ|nullpo)/i', $msg['content']['text'])) {
        //    continue;
        //}

        if (!preg_match('/(画像)/i', $msg['content']['text'])) {
            continue;
        }

        $url = 'https://drive.google.com/folderview?id=0B2v2JSLLU2UZUmd3TFVfUERETnc&usp=sharing'; // 対象のURL又は対象ファイルのパス
        $html = file_get_contents($url); // HTMLを取得

        //パターン
        $pattern= '/'.preg_quote('https://lh','/').'[0-9]'.preg_quote('.googleusercontent.com/','/').'[0-9a-zA-Z_]+=s190/';

        //パターンマッチ＆抽出
        preg_match($pattern, $html, $result);
        $full = preg_replace($result[0], '=s190', '');

        $resContent = $msg['content'];
        //$resContent['text'] = 'ねばぎば！';
        
        $resContent['text'] = $result;

        $resContent['contentType'] = '2';
        $resContent['originalContentUrl'] = $full;
        $resContent['previewImageUrl'] = $result[0];

        $requestOptions = [
            'body' => json_encode([
                'to' => [$msg['content']['from']],
                'toChannel' => 1383378250, # Fixed value
                'eventType' => '138311608800106203', # Fixed value
                'content' => $resContent,
            ]),
            'headers' => [
                'Content-Type' => 'application/json; charset=UTF-8',
                'X-Line-ChannelID' => getenv('LINE_CHANNEL_ID'),
                'X-Line-ChannelSecret' => getenv('LINE_CHANNEL_SECRET'),
                'X-Line-Trusted-User-With-ACL' => getenv('LINE_CHANNEL_MID'),
            ],
            'proxy' => [
                'https' => getenv('FIXIE_URL'),
            ],
        ];

        try {
            $client->request('post', 'https://trialbot-api.line.me/v1/events', $requestOptions);
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }

    return 'OK';
});

$app->run();
