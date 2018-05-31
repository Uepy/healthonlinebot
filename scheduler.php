<?php
    
    require_once __DIR__ . '/vendor/autoload.php';
    date_default_timezone_set('Asia/Tokyo');
    
    
    insertNewDay();
    echo "new day was inserted and prepared !";
    
    
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
    
    // ##########       改善point !!!     ##########
    // とりあえず僕だけのユーザーidのテーブルに日付が変わったら新しいデータをinsertする
    // 将来的には PersonalData のテーブルに登録されているユーザーのすべてをinsertできるように
    // ##########       改善point !!!     ##########
    function insertNewDay(){
        $dbh = dbConnection::getConnection();
        $sql = 'insert into U9a6675ed0946c116097b44bd69024fd4 (ymd) values ( ? ) ' ;
        $sth = $dbh->prepare($sql);
        $sth->execute(array(date('Y-m-d')));
        $sql = 'insert into U50e824a2b99879f2eeaad1138c29e8d5 (ymd) values ( ? ) ' ;
        $sth = $dbh->prepare($sql);
        $sth->execute(array(date('Y-m-d')));
    }

?>
