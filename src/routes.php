<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes

//logoff なんかここ自動化したい
$app->get('/logoff',function (Request $request,Response $response){
    session_destroy();
    echo "<a href='login'>loggoff</a>";
    return $response->withRedirect("/");
});

//register_junp
$app->get('/register',function(Request $request,Response $response){
   //return $this->renderer->render('register.phtml');
    require('../templates/register.phtml');
});
$app->post('/register',function(Request $request,Response $response){
    //post syori kaku
    $pw = $_POST['pwd'];
    $name = $_POST['name'];
    $addles = $_POST['addles'];


    $user = 'ryu';
    $password = 'ryuryu1207';
    $dbname = 'slimapp';
    $host = '127.0.0.1:3306';
    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8";
    $pdo = new PDO($dsn, $user, $password);//MySQLに接続てきな！
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES,false);//おまじない
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);//おまじない



    try {

        //id数える
        $sql = 'SELECT count(id) FROM user';
        $stm = $pdo->prepare($sql);//SQLの文書をセットする感じ
        $stm->execute();//SQLここで実行されてる
        $resulut = $stm->fetchAll(PDO::FETCH_ASSOC);

        //insert文
        $nums = ($resulut[0]["count(id)"]) + 1 ;
        $hashPW = password_hash("$pw", PASSWORD_DEFAULT);
        $sql = 'insert into user VALUE (:id,:name ,:pw,:addles)';
        $sth = $pdo->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $sth->execute(array('id' =>$nums ,':name' => $name, ':pw' => $hashPW, ':addles' => $addles));//値をbindしてSQLの実行

        $resulut = $sth->fetchAll(PDO::FETCH_ASSOC);
        echo "<br>";
        echo $_POST['name']."さんの登録が完了しました"."<br>";
        require('../templates/index.phtml');
        //$pdo =null;
    } catch (Exception $e) {
        echo 'エラーが有りました';
        echo $e->getMessage();
        exit();
    }




});

//login
$app->post('/login',function(Request $request,Response $response){

    $name = $_POST['name'];
    $pwd = $_POST['pwd'];

    $user = 'ryu';
    $password = 'ryuryu1207';
    $dbname = 'slimapp';
    $host = '127.0.0.1:3306';
    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8";
    $pdo = new PDO($dsn, $user, $password);//MySQLに接続てきな！
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES,false);//おまじない
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);//おまじない

    try {
        $sql = 'SELECT id,name,pwd FROM user WHERE name = :name';
        $stm = $pdo->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $stm->execute(array(':name' => $name)); //値をbindして実行!
        $resulut = $stm->fetch(PDO::FETCH_ASSOC); //1つの要素をもらう ここふんわりとしか理解してない
        $verifySuccess = (empty($resulut)) ? false : password_verify($pwd,$resulut['pwd']); // すごいこの文 ? はif文の代わり
        //:の前後でTF
        echo "<br>";
        if ($verifySuccess){
            echo "ログイン成功";
            session_start();
            $_SESSION['user']['username'] = "$name"; //セッションにはいる瞬間なう　ここに情報を追加したらセッションで取り扱える情報が増える
            echo "<a href='tickets' >hoge</a>" ;
            return $response->withRedirect("/tickets");

        }else{
            echo "ログインに失敗しました";

            require('../templates/index.phtml');

        }
        //$pdo =null;
    } catch (Exception $e) {
        echo 'エラーが有りました';
        echo $e->getMessage();
        exit();
    }



    return "\n".$_POST['name'];
});


// 一覧表示
$app->get('/tickets', function (Request $request, Response $response) {
    session_start();
    $sql = 'SELECT * FROM tickets';
    $stmt = $this->db->query($sql);
    $tickets = [];
    while($row = $stmt->fetch()) {
        $tickets[] = $row;
    }
    $data = ['tickets' => $tickets];
    return $this->renderer->render($response, 'tasks/index.phtml', $data);
});

// 新規作成用フォームの表示
$app->get('/tickets/create', function (Request $request, Response $response) {
    session_start();
    return $this->renderer->render($response, 'tasks/create.phtml');
});

// 新規作成
$app->post('/tickets', function (Request $request, Response $response) {
    session_start();
    $subject = $request->getParsedBodyParam('subject');
    // ここに保存の処理を書く
    $sql = 'INSERT INTO tickets (subject,name) values (:subject,:name)';
    $stmt = $this->db->prepare($sql);
    $result = $stmt->execute(['subject' => $subject, 'name' => $_SESSION['user']['username']]);
    if (!$result) {
        throw new \Exception('could not save the ticket');
    }

    // 保存が正常にできたら一覧ページへリダイレクトする
    return $response->withRedirect("/tickets");
});

// 表示
$app->get('/tickets/{id}', function (Request $request, Response $response, array $args) {
    session_start();
    $sql = 'SELECT * FROM tickets WHERE id = :id';
    $stmt = $this->db->prepare($sql);
    $stmt->execute(['id' => $args['id']]);
    $ticket = $stmt->fetch();
    if (!$ticket) {
        return $response->withStatus(404)->write('not found');
    }
    $data = ['ticket' => $ticket];
    return $this->renderer->render($response, 'tasks/show.phtml', $data);
});

// 編集用フォームの表示
$app->get('/tickets/{id}/edit', function (Request $request, Response $response, array $args) {
    session_start();
    $sql = 'SELECT * FROM tickets WHERE id = :id';
    $stmt = $this->db->prepare($sql);
    $stmt->execute(['id' => $args['id']]);
    $ticket = $stmt->fetch();
    if (!$ticket) {
        return $response->withStatus(404)->write('not found');
    }
    $data = ['ticket' => $ticket];
    return $this->renderer->render($response, 'tasks/edit.phtml', $data);
});

// 更新
$app->put('/tickets/{id}', function (Request $request, Response $response, array $args) {
    session_start();
    $sql = 'SELECT * FROM tickets WHERE id = :id';
    $stmt = $this->db->prepare($sql);
    $stmt->execute(['id' => $args['id']]);
    $ticket = $stmt->fetch();
    if (!$ticket) {
        return $response->withStatus(404)->write('not found');
    }
    $ticket['subject'] = $request->getParsedBodyParam('subject');
    $stmt = $this->db->prepare('UPDATE tickets SET subject = :subject WHERE id = :id');
    $stmt->execute($ticket);
    return $response->withRedirect("/tickets");
});

// 削除
$app->delete('/tickets/{id}', function (Request $request, Response $response, array $args) {
    session_start();
    $sql = 'SELECT * FROM tickets WHERE id = :id';
    $stmt = $this->db->prepare($sql);
    $stmt->execute(['id' => $args['id']]);
    $ticket = $stmt->fetch();
    if (!$ticket) {
        return $response->withStatus(404)->write('not found');
    }
    $stmt = $this->db->prepare('DELETE FROM tickets WHERE id = :id');
    $stmt->execute(['id' => $ticket['id']]);
    return $response->withRedirect("/tickets");
});

$app->get('/[{name}]', function (Request $request, Response $response, array $args) {
    session_start();
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);
});
