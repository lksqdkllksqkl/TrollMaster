<?php
    require './vendor/autoload.php';

    $app = new \Slim\Slim(array(
		'debug' => true,
        'templates.path' => './view',
    ));
	
	$config = array(
		"nameWebsite" => "TrollMaster",
		"baseUrl" => "http://192.168.1.44/Troll"
	);
	
    $app->view(new \Slim\Views\Twig());

    $app->view->parserOptions = array(
        'charset' => 'utf-8',
        'cache' => realpath('./cache'),
        'auto_reload' => true,
        'strict_variables' => false,
        'autoescape' => true
    );

    $app->view->parserExtensions = array(new \Slim\Views\TwigExtension());
	
	$app->container->singleton('db', function () {
		return new PDO('mysql:host=localhost;dbname=trolldb;charset=utf8', 'root', '');
	});

	$app->post(
		'/new',
		function() use ($app, $config) {
			$pseudo = $app->request()->post('pseudo');
			if($pseudo) {
				$random = substr(str_shuffle(MD5(microtime())), 0, 10);
				$sql = "INSERT INTO stat(pseudo, random) VALUES (:pseudo, :random)";
				$q = $app->db->prepare($sql);
				$q->execute(array(':pseudo'=>$pseudo, ':random'=>$random));
				$app->setCookie('foo', 'bar', '2 days');
				header('Location: '.$config['baseUrl'].'/'.$random);
			}
			exit;
		}
	);
	
	$app->get(
		'/trolled(/:random)',
		function($random = null) use ($app) {
			if($random) {
				if(!$app->getCookie('foo')) {
					$sql = "UPDATE stat SET nbTrolled = nbTrolled + 1 WHERE random = :random";
					$q = $app->db->prepare($sql);
					$q->execute(array(':random'=>$random));
					$app->setCookie('foo', 'bar', '2 days');
				}
			}
			$image = file_get_contents("public/img/trolldance.gif");
			$app->response->header('Content-Type', 'content-type: image/gif');
			echo $image;
		}
	);
	
	$app->get(
		'/(:random)',
		function($random = null) use ($app, $config) {
			$websites = $app->db->query('SELECT * FROM website ORDER BY websiteName')->fetchAll();
			$pseudo = null;
			$nbTrolled = null;
			if($random) {
				if(!$app->getCookie('foo')) {
					$sql = "UPDATE stat SET nbTrolled = nbTrolled + 1 WHERE random = :random";
					$q = $app->db->prepare($sql);
					$q->execute(array(':random'=>$random));
					$app->setCookie('foo', 'bar', '2 days');
					if(!$q->rowCount()) {
						header('Location: '.$config['baseUrl']);
						exit;
					}
				}
				
				$sql = "SELECT pseudo, nbTrolled FROM stat WHERE random = :random";
				$q = $app->db->prepare($sql);
				$q->execute(array(':random'=>$random));
				
				$result = $q->fetch();
				$pseudo = $result['pseudo'];
				$nbTrolled = $result['nbTrolled'];	
			}
			$app->render('index.twig', array(
				'websites' => $websites,
				'config' => $config,
				'pseudo' => $pseudo,
				'nbTrolled' => $nbTrolled,
				'random' => $random
			));
		}
	);

    $app->run();