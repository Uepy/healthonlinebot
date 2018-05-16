<?php

    require_once __DIR__ . '/vendor/autoload.php';
    date_default_timezone_set('Asia/Tokyo');
    
    // lineID、ユーザー名、身長等の基本情報テーブルのテーブル名を定義
    define('TABLE_USERS_INFO','tbl_users_info');
    
    
    //アクセストークンでCurlHTTPClientをインスタンス化
    $httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(getenv('CHANNEL_ACCESS_TOKEN'));
    
    // CurlHTTPClient とシークレットを使いLineBotをインスタンス化
    $bot = new \LINE\LINEBot($httpClient,['channelSecret' => getenv('CHANNEL_SECRET')]);
    
    
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


  
  

    // 配列に格納された各イベントをループ処理
    foreach ($events as $event) {
        
        // ユーザーIDの取得
        $userId = $event->getUserId();
        
        // ユーザープロファイルの取得
        $profile = $bot -> getProfile($userId) -> getJSONDecodedBody();
        
        $bot->replyText($event->getReplyToken(), getUserName($userId) ."さんの記録\n" .getUserRecord($userId) );
    }

    
    
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
    
    
    
    // TABLE_USERS_INFO の名前を返す
    function getUserName($userId){
      $dbh = dbConnection::getConnection();
      $sql = 'select name from ' . TABLE_USERS_INFO . ' where ? =
      (pgp_sym_decrypt(userid,\'' . getenv('DB_ENCRYPT_PASS') . '\') )' ;
      $sth = $dbh->prepare($sql);
      $sth->execute(array($userId));
      $userName = array_column($sth->fetchAll(),'name');
      return $userName[0];
    }
    
    // userId に一致するユーザーの記録を返す
    function getUserRecord($userId){
      $dbh = dbConnection::getConnection();
      $sql = 'select ymd,weight,muscle,wakeup,sleep,bencon,pain,breakfast,lunch,dinner,training,health,memo from ' .$userId ;
      $sth = $dbh->query($sql);
      $result = $sth->fetchAll();
      error_log("\nfetchAll : " . print_r($result,true));
      $teststring = "日付 : ". $result['ymd'] ."\n体重 : ". $result['weight'] .
      "\n筋肉量 : ". $result['muscle'] ."\n起床時刻 : ". $result['wakeup'] .
      "\n入眠時刻 : ". $result['sleep'] ."\nうんちの状態 : ". $result['bencon'].
      "\n筋肉痛 : ". $result['pain'] ."\n朝食 : ". $result['breakfast'] .
      "\n昼食 : ". $result['lunch'] ."\n夕食 : ". $result['dinner'] .
      "\n筋トレ : ". $result['training'] ."\n健康状態 : ". $result['health'] ."\nメモ : ". $result['memo'];
      return $teststring;
    }
    

 ?>