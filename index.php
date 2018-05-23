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
        
        if(getBoolInput($userId) && ctype_digit(substr($event->getPostbackData(),-1))){
          setHealthData($userId,substr($event->getPostbackData(),-1),getHealthTypeFromInputPhase($userId));
          $bot->replyText($event->getReplyToken(), convertHealthType2Jap(getHealthTypeFromInputPhase($userId))."のデータを記録しました！\nありがとうございます！！");
          setInputPhase($userId,'false','');
        }
        
      switch ($event->getPostbackData()) {
    
        case 'cmd_cancel':
          setInputPhase($userId,'false','');
          $bot->replyText($event->getReplyToken(), "入力はキャンセルされました。");
          break;
          
          
        case 'cmd_OK':
          setInputPhase($userId,'true','');
          
          switch (getHealthTypeFromInputPhase($userId)) {
            case 'shit':
              replyShitButton($bot,$event->getReplyToken());
              break;
            
            
            case 'pain':
              replyPainButton($bot,$event->getReplyToken());
              break;
            
            
            case 'health':
              replyHealthButton($bot,$event->getReplyToken());
              break;
              

            case 'training':
              replyTrainingButton($bot,$event->getReplyToken());
              break;
              
              
            default:
              $bot->replyText($event->getReplyToken(), convertHealthType2Jap(getHealthTypeFromInputPhase($userId))."のデータを入力してください
              \n入力をキャンセルする場合は、上のキャンセルボタンを押して下さい");
              break;
          }
          
          
        default :
          $bot->replyText($event->getReplyToken(), "不正な入力が行われたかもしれません\n申し訳ございません");
          break;
      }
          
      
      // InputPaseがtrueの場合
      // cmd_OK が押されると、inputPhaseがtrueになるのでここに遷移します
      }else if(getBoolInput($userId)){
        setHealthData($userId,$event->getText(),getHealthTypeFromInputPhase($userId));
        $bot->replyText($event->getReplyToken(), convertHealthType2Jap(getHealthTypeFromInputPhase($userId))."のデータを記録しました！\nありがとうございます！！");
        setInputPhase($userId,'false','');
        
        
      //Postbackイベントじゃなかった場合  
      }else
      
      
      switch ($event->getText()) {
        
        
        case 'おはよう' :
          setWakeup($userId,date('H:i'));
          replyTextMessage($bot,$event->getReplyToken(),"おはようございます!\n起床時刻が登録されました");
          break;
          
        
        case $typeJap = '体重' :
          
          setInputPhase($userId,'false','weight');
          replyInputConfirm($bot,$event->getReplyToken(),$typeJap,'weight');
          break;
          
        case $typeJap = '筋肉量' :
          
          setInputPhase($userId,'false','muscle');
          replyInputConfirm($bot,$event->getReplyToken(),$typeJap,'muscle');
          break;
        
        case $typeJap = '朝食' :
          
          setInputPhase($userId,'false','breakfast');
          replyInputConfirm($bot,$event->getReplyToken(),$typeJap,'breakfast');
          break;
        
        case $typeJap = '昼食' :
          
          setInputPhase($userId,'false','lunch');
          replyInputConfirm($bot,$event->getReplyToken(),$typeJap,'lunch');
          break;
        
        case $typeJap = '夕食' :
          
          setInputPhase($userId,'false','dinner');
          replyInputConfirm($bot,$event->getReplyToken(),$typeJap,'dinner');
          break;
        
        case $typeJap = 'うんち' :
          
          setInputPhase($userId,'false','shit');
          replyInputConfirm($bot,$event->getReplyToken(),$typeJap,'shit');
          break;
          
        case $typeJap = '筋肉痛' :
          
          setInputPhase($userId,'false','pain');
          replyInputConfirm($bot,$event->getReplyToken(),$typeJap,'pain');
          break;
          
        case $typeJap = '体調' :
          
          setInputPhase($userId,'false','health');
          replyInputConfirm($bot,$event->getReplyToken(),$typeJap,'health');
          break;
          
        case $typeJap = '筋トレ' :
          
          setInputPhase($userId,'false','training');
          replyInputConfirm($bot,$event->getReplyToken(),$typeJap,'training');
          break;
          

        
        case $typeJap = 'メモ' :
          
          setInputPhase($userId,'false','memo');
          replyInputConfirm($bot,$event->getReplyToken(),$typeJap,'memo');
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
    
    function convertHealthType2Jap($healthType){
      switch($healthType){
        case 'weight': return '体重';
          break;
          
        case 'muscle': return '筋肉量';
          break;
        
        case 'wakeup': return '起床時刻';
          break;
        
        case 'sleep': return '就寝時刻';
          break;
        
        case 'shit': return 'うんちの状態';
          break;
        
        case 'pain': return '筋肉痛';
          break;
        
        case 'breakfast': return '朝食';
          break;
        
        case 'lunch': return '昼食';
          break;
        
        case 'dinner': return '夕食';
          break;
        
        case 'training': return '筋トレ';
          break;
        
        case 'health': return '体調';
          break;
        
        case 'memo': return 'メモ';
          break;
          
        default :
          error_log("\nfailed in convertHealthType2Jap \nrequired healthType didn't match anything.");
          return '';
          break;
          
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
      // error_log("\nwakeup : " . print_r($wakeup,true));
      //error_log("\nY-m-d : " . print_r(date('Y-m-d'),true));
    }
    
    // データをセット
    // 引数はユーザーID、入力するデータ、データを入力するフィールド
    function setHealthData($userId,$data,$healthType){
      $dbh = dbConnection::getConnection();
      error_log("\ncalled setHealthData");
      if(!$healthType === 'shit'){
        $sql = 'update ' .$userId.
        ' set ' .$healthType.' = ? where ymd = ?';
        $sth = $dbh->prepare($sql);
        $sth->execute(array($data,date('Y-m-d')));
      }else{
        switch($data){
          case 3 : $value = '{下痢}';
            break;
          
          case 2 : $value = '{便秘}';
            break;
          
          default : $value = '{快便}';
            break;
        }
        $sql = 'update ' .$userId.
        ' set ' .$healthType.' = ? where ymd = ?';
        $sth = $dbh->prepare($sql);
        $sth->execute(array($value,date('Y-m-d')));
      }
      
      // error_log("\ndata : " . print_r($data,true));
      // error_log("\nhealthType : " . print_r($healthType,true));
      // error_log("\Y-m-d : " . print_r(date('Y-m-d'),true));
      
    }
    
    function setInputPhase($userId,$boolInput,$healthType){
      error_log("\ncalled setInputPhase ");
      $dbh = dbConnection::getConnection();
      // $healthTypeが空文字で渡された場合はboolinpuのみupdate
      if(!$healthType){
        error_log("\nupdate only boolInput");
        $sql = 'update tbl_input_phase set boolInput = ? 
        where (pgp_sym_decrypt(userid,\'' . getenv('DB_ENCRYPT_PASS') . '\') ) = ?';
        // error_log("\nboolInput : " . print_r($boolInput,true));
        $sth = $dbh->prepare($sql);
        $sth->execute(array($boolInput,$userId));
      }else{
        error_log("\nupdate both boolInput and healthType");
        $sql = 'update tbl_input_phase set boolInput = ? , dataType = ? 
        where (pgp_sym_decrypt(userid,\'' . getenv('DB_ENCRYPT_PASS') . '\') ) = ?';
        // error_log("\nboolInput : " . print_r($boolInput,true));
        // error_log("\nhealthType : " . print_r($healthType,true));
        $sth = $dbh->prepare($sql);
        $sth->execute(array($boolInput,$healthType,$userId));
      }

    }
    
    function getBoolInput($userId){
      $dbh = dbConnection::getConnection();
      error_log("\ncalled getBoolInput");
      $sql = 'select boolinput from tbl_input_phase 
      where (pgp_sym_decrypt(userid,\'' . getenv('DB_ENCRYPT_PASS') . '\') ) = ?';
      $sth = $dbh->prepare($sql);
      $sth->execute(array($userId));
      $boolInput = array_column($sth->fetchAll(),'boolinput');
      if($boolInput[0] == 1){
        error_log("\nboolInput : true");
        return true ;
      }else{
        error_log("\nboolInput : false");
        return false;
      }
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
      $sql = 'select ymd,weight,muscle,wakeup,sleep,shit,pain,breakfast,lunch,dinner,training,health,memo from ' .$userId .' where ymd = ?';
      $sth = $dbh->prepare($sql);
      $sth->execute(array(date('Y-m-d')));
      //$sth = $dbh->query($sql);
      $result = $sth->fetchAll();
      //error_log("\nfetchAll : " . print_r($result,true));
      //error_log("\narraycolumn ymd : " . print_r(array_column($result,'ymd'),true));
      //error_log("\narraycolumn ymd0 : " . print_r(array_column($result,'ymd')[0],true));
      $teststring = "日付 : ". array_column($result,'ymd')[0] ."\n体重 : ". array_column($result,'weight')[0] .
      "\n筋肉量 : ". array_column($result,'muscle')[0] ."\n起床時刻 : ". array_column($result,'wakeup')[0] .
      "\n就寝時刻 : ". array_column($result,'sleep')[0] ."\nうんちの状態 : ". array_column($result,'shit')[0].
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
    // 画像とタイトルはnullを指定することで省略可
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
    
    function replyShitButton($bot,$replyToken){
      $actionArray = array( 
        new LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder('下痢','cmd_3'),
        new LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder('便秘','cmd_2'),
        new LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder('快便','cmd_1'),
        new LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder('キャンセル','cmd_cancel')
        );

      replyButtonsTemplate($bot,$replyToken,'うんコンディションの入力',
      null,'うんコンディションの入力','うんコンディションを選択して下さい',$actionArray);
    }
    
    function replyPainButton($bot,$replyToken){
      $actionArray = array( 
        new LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder('痛い！','cmd_3'),
        new LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder('少し痛い','cmd_2'),
        new LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder('なし','cmd_1'),
        new LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder('キャンセル','cmd_cancel')
        );

      replyButtonsTemplate($bot,$replyToken,'筋肉痛の入力',
      null,'筋肉痛の入力','筋肉痛の程度を選択して下さい',$actionArray);
    }
    
    function replyHealthButton($bot,$replyToken){
      $actionArray = array( 
        new LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder('良い','cmd_1'),
        new LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder('悪い','cmd_0'),
        new LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder('キャンセル','cmd_cancel')
        );

      replyButtonsTemplate($bot,$replyToken,'体調の入力',
      null,'体調の入力','体調の程度を選択して下さい',$actionArray);
    }
    
    function replyTrainingButton($bot,$replyToken){
      $actionArray = array( 
        new LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder('した','cmd_1'),
        new LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder('してない','cmd_0'),
        new LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder('キャンセル','cmd_cancel')
        );

      replyButtonsTemplate($bot,$replyToken,'筋トレの入力',
      null,'筋トレの入力','筋トレの有無を選択して下さい',$actionArray);
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
    
    
    
    function replyInputConfirm($bot,$replyToken,$typeJap,$type){
      replyConfirmTemplate($bot,$replyToken,
      $typeJap. 'を入力します', $typeJap. 'を入力します',
            new LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder('はい','cmd_OK'),
            new LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder('いいえ','cmd_cancel'));
    }


 ?>