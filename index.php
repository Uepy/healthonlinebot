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
        
        //$bot->replyText($event->getReplyToken(), getUserName($userId) ."さんの記録\n" .getUserRecord($userId) );
        
        switch ($event->getText()) {
          
          
          case 'おはよう' :
            setWakeup($userId,date('H:i'));
            replyTextMessage($bot,$event->getReplyToken(),"おはようございます！\n起床時刻が登録されました\n今日も一日顔晴りましょう！");
            break;
            
          
          case 'おやすみ' :
            setWakeup($userId,date('H:i'));
            replyTextMessage($bot,$event->getReplyToken(),"おやすみなさい\n就寝時刻が登録されました\n今日も一日お疲れ様でした");
            break;
            
            
          case '体重' :
            
            // 
            $bot->replyText($event->getReplyToken(), getUserName($userId) ."ちゃんの記録\n" .getUserRecord($userId) );
            break;
          
          // どれでもない場合は記録を返す  
          default:
            
            $bot->replyText($event->getReplyToken(), getUserName($userId) ."さんの記録\n" .getUserRecord($userId) );
            break;
        }
        
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
    
    // 体重をセット
    function setWeight($userId,$weight){
      $dbh = dbConnection::getConnection();
      $sql = 'update ' .$userId.
      ' set weight = ? where ymd = ?';
      $sth = $dbh->prepare($sql);
      $sth->execute(array($weight,date('Y-m-d')));
    }
    
    // 起床時刻をセット
    function setWakeup($userId,$wakeup){
      $dbh = dbConnection::getConnection();
      $sql = 'update ' .$userId.
      ' set wakeup = ? where ymd = ?';
      $sth = $dbh->prepare($sql);
      $sth->execute(array($wakeup,date('Y-m-d')));
      error_log("\nwakeup : " . print_r($wakeup,true));
      error_log("\Y-m-d : " . print_r(date('Y-m-d'),true));
    }
    
    // 起床時刻をセット
    function setSleep($userId,$sleep){
      $dbh = dbConnection::getConnection();
      $sql = 'update ' .$userId.
      ' set sleep = ? where ymd = ?';
      $sth = $dbh->prepare($sql);
      $sth->execute(array($sleep,date('Y-m-d')));
      error_log("\nsleep : " . print_r($sleep,true));
      error_log("\Y-m-d : " . print_r(date('Y-m-d'),true));
    }
    
    function setInputPhase($userId,$boolInput,$healthType){
      $dbh = dbConnection::getConnection();
      $sql = 'update tbl_input_phase set boolInput = ? , healthType = ? 
      where (pgp_sym_decrypt(userid,\'' . getenv('DB_ENCRYPT_PASS') . '\') ) = ?';
      $sth = $dbh->prepare($sql);
      $sth->execute(array($boolInput,$healthType,$userId));
    }
    
    function getBoolInput($userId){
      $dbh = dbConnection::getConnection();
      $sql = 'select boolInput from tbl_input_phase  
      where (pgp_sym_decrypt(userid,\'' . getenv('DB_ENCRYPT_PASS') . '\') ) = ?';
      $sth = $dbh->prepare($sql);
      $sth->execute(array($userId));
      $boolInput = array_column($sth->fetchAll(),'boolInput')[0];
      return $boolInput;
    }
    
    // userId に一致するユーザーの記録を返す
    function getUserRecord($userId){
      $dbh = dbConnection::getConnection();
      $sql = 'select ymd,weight,muscle,wakeup,sleep,bencon,pain,breakfast,lunch,dinner,training,health,memo from ' .$userId ;
      $sth = $dbh->query($sql);
      $result = $sth->fetchAll();
      //error_log("\nfetchAll : " . print_r($result,true));
      //error_log("\narraycolumn ymd : " . print_r(array_column($result,'ymd'),true));
      //error_log("\narraycolumn ymd0 : " . print_r(array_column($result,'ymd')[0],true));
      $teststring = "日付 : ". array_column($result,'ymd')[0] ."\n体重 : ". array_column($result,'weight')[0] .
      "\n筋肉量 : ". array_column($result,'muscle')[0] ."\n起床時刻 : ". array_column($result,'wakeup')[0] .
      "\n入眠時刻 : ". array_column($result,'sleep')[0] ."\nうんちの状態 : ". array_column($result,'bencon')[0].
      "\n筋肉痛 : ". array_column($result,'pain')[0] ."\n朝食 : ". array_column($result,'breakfast')[0] .
      "\n昼食 : ". array_column($result,'lunch')[0] ."\n夕食 : ". array_column($result,'dinner')[0] .
      "\n筋トレ : ". array_column($result,'training')[0] ."\n健康状態 : ". array_column($result,'health')[0] .
      "\nメモ : ". array_column($result,'memo')[0];
      return $teststring;
    }
    
    // テキストを返信 引数はLINEbot、返信先、テキストメッセージ
    function replyTextMessage($bot,$replyToken,$text){
      $response = $bot->replyMessage($replyToken,
      new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($text));
      
      if (!$response->isSucceeded()){
        error_log('failed to replyTextMessage' . $response->getHTTPStatus . ' ' . $response->getRawBody());
      }
    }
    
    // テキストをプッシュ 引数は、LINEbot、ユーザーID、テキストメッセージ
    function pushTextMassage($bot,$userId,$text){
      $response = $bot->pushMessage($userId, new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($message));
      
      if (!$response->isSucceeded()){
        error_log('failed to pushTextMassage' . $response->getHTTPStatus . ' ' . $response->getRawBody());
      }
    }

 ?>