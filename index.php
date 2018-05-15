<?php

    require_once __DIR__ . '/vendor/autoload.php';
    date_default_timezone_set('Asia/Tokyo');
    
    //アクセストークンでCurlHTTPClientをインスタンス化
    private $httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(getenv('CHANNEL_ACCESS_TOKEN'));
    
    // CurlHTTPClient とシークレットを使いLineBotをインスタンス化
    private $bot = new \LINE\LINEBot($httpClient,['channelSecret' => getenv('CHANNEL_SECRET')]);
    
    
    // LINE Messaging API がリクエストに付与した署名を取得
    $signature = $_SERVER["HTTP_" . \LINE\LINEBot\Constant\HTTPHeader::LINE_SIGNATURE];
    try {
      // 署名が正当かチェック 正当であればリクエストをパースして配列へ
      $events = $bot->parseEventRequest(file_get_contents('php://input'), $signature);
    } catch(\LINE\LINEBot\Exception\InvalidSignatureException $e) {
      error_log("parseEventRequest failed. InvalidSignatureException => ".var_export($e, true));
    } catch(\LINE\LINEBot\Exception\UnknownEventTypeException $e) {
      error_log("parseEventRequest failed. UnknownEventTypeException => ".var_export($e, true));
    } catch(\LINE\LINEBot\Exception\UnknownMessageTypeException $e) {
      error_log("parseEventRequest failed. UnknownMessageTypeException => ".var_export($e, true));
    } catch(\LINE\LINEBot\Exception\InvalidEventRequestException $e) {
      error_log("parseEventRequest failed. InvalidEventRequestException => ".var_export($e, true));
    }

    // ユーザーIDの取得
    $userId = $event->getUserId();
    
    // ユーザープロファイルの取得
    $profile = $bot -> getProfile($userId) -> getJSONDecodedBody();
  
  

    // 配列に格納された各イベントをループ処理
    foreach ($events as $event) {
        $bot->replyText($event->getReplyToken(),'こんにちは、'+ $profile['displayName'] + 'さん。' );
    }

    /*
    
    // データベースへの接続を管理するクラス
    class dbConnection{
      // インスタンス
      protected static $db;
      // コンストラクタ
      private function __construct(){
        
        try{
          // 環境変数からデータベースへの接続情報を取得
          $url = parse_url(getenv('DATABASE_URL'));
          // データソース
          $dsn = sprintf('pgsql:host=%s;dbname=%s',$url['host'],substr($url['path'],1));
          // 接続を確立
          self::$db = new PDO($dsn,$url['user'],$url['pass']);
          // エラー時には例外を投げるように設定
          self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        catch(PDOException $e){
          echo 'Connection Error: ' . $e->getMessage();
        }
      }
    
        // シングルトン 存在しない場合のみインスタンス化
        public static function getConnection(){
          if(!self::$db){
            new dbConnection();
          }
          return self::$db;
        }
        
    }
    
    */

 ?>