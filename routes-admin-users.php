<?php


use \Hcode\PageAdmin;
use \Hcode\Model\User;

$app->get('/admin/users', function() 
{   
	User::verifyLogin();
	$search = (isset($_GET["search"]) ? $_GET["search"] : "");
	$nrpage = (isset($_GET["page"]) ? (int)$_GET["page"] : 1);
	$pagination = User::getPage($nrpage, $search);	
	$pages = [];
	for ($i=1; $i <= $pagination["pages"]; $i++) { 
		array_push($pages, [
			"href"=>"/admin/users?" . http_build_query([
				"page"=>$i,
				"search"=>$search
			]),
			"text"=>$i
		]);
	}
	$page = new PageAdmin();
	$page->setTpl("users", array(
		"users"=>$pagination["data"],
		"pages" =>$pages,
		"search"=>$search
	));
});

$app->get('/admin/users/create', function() 
{   
	User::verifyLogin();

	$page = new PageAdmin();
	$page->setTpl("users-create");
});

$app->get('/admin/users/:iduser/password', function($iduser) 
{   
	User::verifyLogin();

	$user = new User();
	$user->get((int)$iduser);
	$page = new PageAdmin();
	$page->setTpl("users-password", array(
		"user"=>$user->getValues(),
		"msgError"=>User::getMsgError(),
		"msgSuccess"=>User::getMsgSuccess()
	));	
});


$app->post('/admin/users/:iduser/password', function($iduser) 
{   
	User::verifyLogin();

	$user = new User();
	$user->get((int)$iduser);

	$hasError = false;
	
	if (!isset($_POST["despassword"]) || $_POST["despassword"] == "")
	{
		User::setMsgError("Digite a nova senha");
		$hasError = true;
	}
	else if (!isset($_POST["despassword-confirm"]) || $_POST["despassword-confirm"] == "")
	{
		User::setMsgError("Digite a confirmação da nova senha");
		$hasError = true;
	} 
	else if ($_POST["despassword"] != $_POST["despassword-confirm"])
	{
		User::setMsgError("Confirmação da senha não corresponde a nova senha digitada");
		$hasError = true;
	}
	
	if ($hasError)	
	{
		header("Location: /admin/users/$iduser/password");
	} 
	else 
	{
		$user->setPassword(User::getPasswordHash($_POST["despassword"]));
		User::setMsgSuccess("Senha alterada com sucesso");
		header("Location: /admin/users");
	}
	exit;
});



$app->get('/admin/users/:iduser/delete', function($iduser) 
{   
	User::verifyLogin();

	$user = new User();
	$user->get((int)$iduser);
	$user->delete();

	header("Location: /admin/users");
	exit;	
});

$app->get('/admin/users/:iduser', function($iduser) 
{   
	User::verifyLogin();	
	$user = new User();
	$user->get((int)$iduser);
	$page = new PageAdmin();
	$page->setTpl("users-update", array("user"=>$user->getValues()));
});

$app->post('/admin/users/create', function() 
{   
	User::verifyLogin();
	$user = new User();
	$_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0;
	$user->setData($_POST);
	$user->save();
	header("Location: /admin/users");
	exit;		
});

$app->post('/admin/users/:iduser', function($iduser) 
{   
	User::verifyLogin();		
	$user = new User();
	$_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0;
	$user->get((int)$iduser);
	$user->setData($_POST);
	$user->update();
	header("Location: /admin/users");
	exit;		
});

?>