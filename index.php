<?php
  require_once 'vendor/autoload.php';

  // Klein configuration
  $base = dirname($_SERVER['PHP_SELF']);
  if (ltrim($base, '/')) {
    $_SERVER['REQUEST_URI'] =
    substr(
      $_SERVER['REQUEST_URI'], strlen($base)
    );
  }
  $router = new \Klein\Klein();

  // Twig configuration
  $router->respond(function(
    $request, $response, $service, $app
  ) use ($klein) {
    $app->register('twig', function() {
      $loader = new Twig_Loader_Filesystem(
        'templates'
      );
      return $twig = new Twig_Environment(
        $loader,
        array('auto_reload' => true)
      );
    });
    $app->register('db', function() {
        return new PDO(
          "mysql:host=localhost;port=3306;dbname=bfs",
          "root",
          "root"
        );
    });
  });

  // API
  $router->respond('GET', '/', function(
    $request, $response, $service, $app
  ) {
    session_start();
    if (!isset($_SESSION['user_id'])) {
      $response->redirect('./signin', $code = 302);
    } else {
      echo $app->twig->render(
        'form.twig',
        array(
          'title' => 'Main',
          'header' => 'It works!! ' . $_SESSION['user_name'],
          'action'=> './handler',
          'questions' => array(
            array(
              'number' => '0',
              'text' => 'Для чего служат Системы управления
                контентом?',
              'type' => 'text'
            ),
            array(
              'number' => '1',
              'text' => 'Какие системы управления интернет
                контентом вы знаете?',
              'type' => 'text'
            ),
            array(
              'number' => '2',
              'text' => 'Какие из фрагментов HTML кода являются
                кореектными?',
              'type' => 'checkbox',
              'answers' => array(
                '<b><i> Text bold and italic </i></b>',
                '<b><i> Text bold and italic </b></i>',
                '<i><b> Text bold and italic </b></i>'
              )
            ),
            array(
              'number' => '3',
              'text' => 'Какие из фрагментов XML кода являются
                корректными?',
              'type' => 'checkbox',
              'answers' => array(
                '<composition>
                  <ingredient amount="150" unit="грамм">
                    Сгущёнка
                  </Inredient>
                  <ingredient amount="1" unit="стакан">
                    Мука
                  </Inredient>
                  <ingredient amount="1.5" unit="стакан">
                    Тёплая вода
                  </Inredient>
                  <ingredient amount="1" unit="чайная ложка">
                    Соль
                  </Inredient>
                </composition>',
                '<settings>
                  <login>user</login>
                  <password>password</password>
                  <userid>
                    09ec1fb8-4274-4fa2-be0e-e0db4114a353
                  </userid>
                </settings>',
                '<rule id="777">
                  Символы < и > и & нельзя ислользовать в
                  символьных даннах XML непосредсвенно.
                </rule>',
                '<entity> </entity>'
              )
            ),
            array(
              'number' => '4',
              'text' => 'В чём предназначение каскадных таблиц
                стилей CSS?',
              'type' => 'text'
            ),
            array(
              'number' => '5',
              'text' => 'Опишите Принцип работы протокола HTTP',
              'type' => 'text'
            )
          )
        )
      );
    }
  });
  $router->respond('GET', '/signin',
    function(
      $request, $response, $service, $app
    ) {
      echo $app->twig->render(
        'signPage.twig',
        array(
          'title' => 'Sign in',
          'header' => 'Sign in',
          'anotherAction' => 'Or register',
          'link' => './register',
          'action' => './signin'
        )
      );
    }
  );
  $router->respond('GET', '/register',
    function(
      $request, $response, $service, $app
    ) {
      echo $app->twig->render(
        'signPage.twig',
        array(
          'title' => 'Register',
          'header' => 'Register',
          'anotherAction' => 'Or sign in',
          'link' => './signin',
          'action' => './register'
        )
      );
    }
  );
  $router->respond('GET', '/thankyoupage',
    function(
      $request, $response, $service, $app
    ) {
      echo $app->twig->render(
        'thankyoupage.twig',
        array(
          'title' => 'Success',
          'thankHeader' =>
            'Form has been sent successfully!',
          'thankLinkText' =>
            'Do you want to complete it again?',
          'thankLink' => './'
        )
      );
    }
  );
  $router->respond('POST', '/handler',
    function(
      $request, $response, $service, $app
    ) {
      session_start();
      $stmt = $app->db->prepare(
        'INSERT INTO record (date, user_id)
        VALUES (:date, :uid)'
      );
      $stmt->bindValue(':date', date('Y:m:d H:i:s'));
      $user_id = $_SESSION['user_id'];
      $stmt->bindValue(':uid', $user_id);
      $stmt->execute();
      $lastId = $app->db->lastInsertId();

      $chbx = $app->db->prepare(
        'INSERT INTO field_checkbox_answer
        (record_id, question_number, checkbox_answer)
        VALUES (:rec_id, :ques_num, :cb_ans)'
      );
      $txt = $app->db->prepare(
        'INSERT INTO field_text_answer
        (record_id, question_number, text_answer)
        VALUES (:rec_id, :ques_num, :text_ans)'
      );

      $send = true;

      foreach ($_POST as $key => $value) {
        if (is_array($value)) {
          foreach ($value as $inner_key => $inner_value) {
            $chbx->bindValue(':rec_id', $lastId);
            $chbx->bindValue(':ques_num', $key);
            $chbx->bindValue(':cb_ans', $inner_value);
            $var = $chbx->execute();
            if (!$var) {
              echo $chbx->errorCode();
              $send = false;
            }
          }
        } else {
          $txt->bindValue(':rec_id', $lastId);
          $txt->bindValue(':ques_num', $key);
          $txt->bindValue(':text_ans', $value);
          $var = $txt->execute();
          if (!$var) {
            echo $txt->errorCode();
            $send = false;
          }
        }
      }

      if (send == true) {
        $response->redirect('./thankyoupage', $code = 302);
      }
    }
  );
  $router->respond('POST', '/register',
    function(
      $request, $response, $service, $app
    ) {
      session_start();
      $stmt = $app->db->prepare(
        'SELECT name FROM users WHERE name = :nm'
      );
      $stmt->bindValue(':nm', $_POST['name']);
      $stmt->execute();
      $user = $stmt->fetch(PDO::FETCH_ASSOC);
      $reg = $app->db->prepare(
        'INSERT INTO users (name, password)
        VALUES (:nm, :pw)'
      );


      if (!empty($user)) {
        echo $app->twig->render(
          'signPageError.twig',
          array(
            'title' => 'Register',
            'header' => 'Register',
            'anotherAction' => 'Or sign in',
            'link' => './signin',
            'action' => './register',
            'errorMessage' =>
              'User with this name already exists'
          )
        );
      } else {
        $reg->bindValue(':nm', $_POST['name']);
        $reg->bindValue(':pw', md5($_POST['password']));
        $reg->execute();
        $lastId = $app->db->lastInsertId();

        $_SESSION['user_name'] = $_POST['name'];
        $_SESSION['user_id'] = $lastId;

        $response->redirect('./', $code = 302);
      }
    }
  );
  $router->respond('POST', '/signin',
    function(
      $request, $response, $service, $app
    ) {
      session_start();
      $stmt = $app->db->prepare(
        'SELECT user_id, name, password
        FROM users WHERE name = :nm'
      );
      $stmt->bindValue(':nm', $_POST['name']);
      $stmt->execute();
      $user = $stmt->fetch(PDO::FETCH_ASSOC);

      if (empty($user)) {
        echo $app->twig->render(
          'signPageError.twig',
          array(
            'title' => 'Sign in',
            'header' => 'Sign in',
            'anotherAction' => 'Or register',
            'link' => './register',
            'action' => './signin',
            'errorMessage' =>
              'This user doesn\'t exist'
          )
        );
      } else if ($user['password'] != md5($_POST['password'])) {
        echo $app->twig->render(
          'signPageError.twig',
          array(
            'title' => 'Sign in',
            'header' => 'Sign in',
            'anotherAction' => 'Or register',
            'link' => './register',
            'action' => './signin',
            'errorMessage' =>
              'Incorrect password'
          )
        );
      } else {
        $_SESSION['user_name'] = $_POST['name'];
        $_SESSION['user_id'] = $user['user_id'];

        $response->redirect('./', $code = 302);
      }
    }
  );
  try {
    $router->dispatch();
  }
  catch (Exception $e) {
    echo $e->getMessage();
  }
?>
