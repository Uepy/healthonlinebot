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
        
        
        //Postbackイベントの場合
        if($event instanceof \LINE\LINEBot\Event\PostbackEvent){
          
        switch ($event->getPostbackData()) {
      
          case 'cmd_cancel':
            setInputPhase($userId,false,'');
            $bot->replyText($event->getReplyToken(), "入力はキャンセルされました。");
            break;
            
            
          case 'cmd_OK':
            setInputPhase($userId,true,'');
            $bot->replyText($event->getReplyToken(), "データを入力してください");
            break;
            
          default :
            $bot->replyText($event->getReplyToken(), "不正な入力が行われたかもしれません\n申し訳ございません");
            break;
        }
            
        
        //PostbackイベントじゃなくInputPaseがtrueの場合
        }else if(getBoolInput($userId)){
          setHealthData($userId,$event->getText(),getHealthTypeFromInputPhase());
          $bot->replyText($event->getReplyToken(), "データを記録しました！\nありがとうございます！！");
        //Postbackイベントじゃなかった場合  
        }else
        
        
        switch ($event->getText()) {
          
          
          case 'おはよう' :
            setWakeup($userId,date('H:i'));
            replyTextMessage($bot,$event->getReplyToken(),"おはようございます!\n起床時刻が登録されました");
            break;
            
          
          case '体重' :
            
            setInputPhase($userId,false,'weight');
            replyConfirmTemplate($bot,$bot, $event->getReplyToken(),
            '体重を入力します','体重を入力します',
            new LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder('はい','cmd_OK'),
            new LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder('いいえ','cmd_cancel'));
            
            
            break;
            
          case 'メモ' :
            
            setInputPhase($userId,false,'memo');
            replyConfirmTemplate($bot,$bot, $event->getReplyToken(),
            'メモを入力します','メモを入力します',
            new LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder('はい','cmd_OK'),
            new LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder('いいえ','cmd_cancel'));
            
            
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
    
    // 起床時刻をセット
    function setWakeup($userId,$wakeup){
      $dbh = dbConnection::getConnection();
      $sql = 'update ' .$userId.
      ' set wakeup = ? where ymd = ?';
      $sth = $dbh->prepare($sql);
      $sth->execute(array($wakeup,date('Y-m-d')));
      error_log("\nwakeup : " . print_r($wakeup,true));
      error_log("\nY-m-d : " . print_r(date('Y-m-d'),true));
    }
    
    // データをセット
    // 引数はユーザーID、入力するデータ、データを入力するフィールド
    function setHealthData($userId,$data,$healthType){
      $dbh = dbConnection::getConnection();
      $sql = 'update ' .$userId.
      ' set ? = ? where ymd = ?';
      $sth = $dbh->prepare($sql);
      $sth->execute(array($healthType,$data,date('Y-m-d')));
      error_log("\ncalled setHealthData");
      error_log("\ndata : " . print_r($data,true));
      error_log("\nhealthType : " . print_r($healthType,true));
      error_log("\Y-m-d : " . print_r(date('Y-m-d'),true));
    }
    
    function setInputPhase($userId,$boolInput,$healthType){
      error_log("\ncalled setInputPhase ");
      $dbh = dbConnection::getConnection();
      if(!$healthType){
        error_log("\nupdate only boolInput");
        $sql = 'update tbl_input_phase set boolInput = ? 
        where (pgp_sym_decrypt(userid,\'' . getenv('DB_ENCRYPT_PASS') . '\') ) = ?';
        error_log("\nboolInput : " . print_r($boolInput,true));
        error_log("\nhealthType : " . print_r($healthType,true));
        $sth = $dbh->prepare($sql);
        $sth->execute(array($boolInput,$userId));
      }else{
        error_log("\nupdate both boolInput and healthType");
        $sql = 'update tbl_input_phase set boolInput = ? , dataType = ? 
        where (pgp_sym_decrypt(userid,\'' . getenv('DB_ENCRYPT_PASS') . '\') ) = ?';
        error_log("\nboolInput : " . print_r($boolInput,true));
        error_log("\nhealthType : " . print_r($healthType,true));
        $sth = $dbh->prepare($sql);
        $sth->execute(array($boolInput,$healthType,$userId));
      }

    }
    
    function getBoolInput($userId){
      $dbh = dbConnection::getConnection();
      $sql = 'select boolInput from tbl_input_phase  
      where (pgp_sym_decrypt(userid,\'' . getenv('DB_ENCRYPT_PASS') . '\') ) = ?';
      $sth = $dbh->prepare($sql);
      $sth->execute(array($userId));
      $boolInput = array_column($sth->fetchAll(),'boolInput')[0];
      error_log("\ncalled getBoolInput");
      error_log("\nboolInput : " . print_r($boolInput,true));
      return $boolInput;
    }
    
    function getHealthTypeFromInputPhase($userId){
      error_log("\ncalled getHealthTypeFromInputPhase");
      $dbh = dbConnection::getConnection();
      $sql = 'select dataType from tbl_input_phase  
      where (pgp_sym_decrypt(userid,\'' . getenv('DB_ENCRYPT_PASS') . '\') ) = ?';
      $sth = $dbh->prepare($sql);
      $sth->execute(array($userId));
      $healthType = array_column($sth->fetchAll(),'datatype')[0];
      
      error_log("\nhealthType : " . print_r($healthType,true));
      return $healthType;
    }
    
    // userId に一致するユーザーの記録を返す
    function getUserRecord($userId){
      $dbh = dbConnection::getConnection();
      $sql = 'select ymd,weight,muscle,wakeup,sleep,bencon,pain,breakfast,lunch,dinner,training,health,memo from ' .$userId .'where ymd = '.date('Y-m-d');
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
    
    
    // buttons テンプレート アクション引数が配列版
    // Buttons テンプレートを返信 
    // 引数(LINEBot,返信先,代替テキスト,画像URL,タイトル,本文,アクション配列)
    function replyButtonsTemplate($bot,$replyToken,$alterText,$imageUrl,$title,$text,$actionArray){
    
      // TemplateMessageBuilderの引数(代替テキスト,ButtonTemplateBuilder)
      $builder = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder($alterText,
      // ButtonTemplateBuilderの引数(タイトル,本文,画像URL,アクション配列)
      new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder(
        $title,$text,$imageUrl,$actionArray));
        
      $response = $bot -> replyMessage($replyToken,$builder);
      if(!$response->isSucceeded()){
        error_log('failed to push buttons' . $response->getHTTPStatus . ' ' . $response->getRawBody());
      }
    }
    
    // Confirm テンプレートを返信 
    // 引数(LINEBot,返信先,代替テキスト,本文,可変長アクション配列)
    function replyConfirmTemplate($bot,$replyToken,$alterText,$text,...$actions){
      $actionArray = array();
      foreach($actions as $value){
        array_push($actionArray,$value);
      }
      // TemplateMessageBuilderの引数(代替テキスト,ButtonTemplateBuilder)
      $builder = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder($alterText,
      new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ConfirmTemplateBuilder(
        $text,$actionArray));
      
      $response = $bot -> replyMessage($replyToken,$builder);
      if(!$response->isSucceeded()){
        error_log('failed to push confirm button' . $response->getHTTPStatus . ' ' . $response->getRawBody());
      }
    }


 ?>