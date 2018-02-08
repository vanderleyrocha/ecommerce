<?php

use \Hcode\Page;
use \Hcode\Model\Product;
use \Hcode\Model\User;
use \Hcode\Model\Category;
use \Hcode\Model\Cart;
use \Hcode\Model\Address;

$app->get('/', function() 
{   
	$products = Product::listAll();
	$page = new Page();
	$page->setTpl("index", ["products"=>Product::checkList($products)]);
});


$app->get('/category/:idcategory', function($idcategory) 
{   
	$page = (isset($_GET["page"])) ? (int)$_GET["page"] : 1;
	$category = new Category();
	$category->get((int)$idcategory);
	$pagination = $category->getProductsPage($page);
	//var_dump($pagination); exit;
	$pages = [];
	for ($i=1; $i<=$pagination["pages"]; $i++) { 
		array_push($pages, [
			"link"=>"/category/".$category->getidcategory()."?page=".$i,
			"page"=>$i
		]);
	}
	$page = new Page();
	$page->setTpl("category", 
		array("category"=>$category->getValues(), 
			"products"=>$pagination["data"],
			"pages"=>$pages
		)
	);	
});


$app->get('/product/:desurl', function($desurl) 
{   
	$product = new Product();
	$product->getFromURL($desurl);
	$page = new Page();
	$page->setTpl("product-detail", [
		"product"=>$product->getValues(),
		"categories"=>$product->getCategories()
	]);
});


$app->get('/cart', function() 
{  
	$cart = Cart::getFromSession(); 

	$page = new Page();
	$page->setTpl("cart", [
		"cart"=>$cart->getValues(),
		"products"=>$cart->getProducts(),
		"error"=>Cart::getMsgError()
	]);
});


$app->post('/cart/freight', function() 
{
	$cart = Cart::getFromSession();
	$cart->setFreight($_POST["zipcode"]);
	header("Location: /cart");
	exit;
});


$app->get('/cart/:idproduct/add', function($idproduct) 
{   
	$product = new Product();
	$product->get((int)$idproduct);
	$cart = Cart::getFromSession();
	$qtd = (isset($_GET["qtd"])) ? (int)$_GET["qtd"] : 1;

	for ($i=0; $i < $qtd; $i++) { 
		$cart->addProduct($product);
	}
	
	header("Location: /cart");
	exit;
});


$app->get('/cart/:product/minus', function($idproduct) 
{   
	$product = new Product();
	$product->get((int)$idproduct);
	$cart = Cart::getFromSession();
	$cart->removeProduct($product);

	header("Location: /cart");
	exit;
});


$app->get('/cart/:product/remove', function($idproduct) 
{   
	$product = new Product();
	$product->get((int)$idproduct);
	$cart = Cart::getFromSession();
	$cart->removeProduct($product, true);

	header("Location: /cart");
	exit;
});


$app->get('/checkout', function() 
{ 
	User::verifyLogin(false); 
	$cart = Cart::getFromSession();
	$address = new Address(); 
	$page = new Page();
	$page->setTpl("Checkout", [
		"cart"=>$cart->getValues(),
		"address"=>["desaddress"=>"Rua A", "descomplement"=>"", "desdistrict"=>"Manoel Julião", "descity"=>"Rio Branco", "desstate"=>"Acre", "descountry"=>"BR"]
		//"address"=>$address->getValues()
	]);
});


$app->get('/login', function() 
{ 
	$page = new Page();
	$page->setTpl("login", [
		"error"=>User::getMsgError(),
		"errorRegister"=>User::getErrorRegister(),
		"registerValues"=>(isset($_SESSION["registerValues"])) ? $_SESSION["registerValues"] : [
			"name"=>"", "email"=>"", "phone"=>""
		]
	]);
});


$app->post('/login', function() 
{ 
	try {
		User::login($_POST["login"], $_POST["password"]);
	} catch(Exception $e) {
		User::setMsgError($e->getMessage());
	}
	
	header("Location: /checkout");
	exit;
});


$app->get('/logout', function() 
{ 
	User::logout();
	header("Location: /login");
	exit;
});


$app->post('/register', function() 
{

	$_SESSION["registerValues"] = $_POST;

	if (!isset($_POST["name"]) || ($_POST["name"] == ""))
	{
		User::setErrorRegister("Informe seu nome.");
		header("Location: /login");
		exit;		
	}

	if (!isset($_POST["email"]) || ($_POST["email"] == ""))
	{
		User::setErrorRegister("Informe seu email.");
		header("Location: /login");
		exit;		
	}

	if (User::LoginExists($_POST["email"]))
	{
		User::setErrorRegister("E-mail já cadastrado.");
		header("Location: /login");
		exit;		
	}

	if (!isset($_POST["password"]) || ($_POST["password"] == ""))
	{
		User::setErrorRegister("Informe sua senha.");
		header("Location: /login");
		exit;		
	}

	$user = new User();

	$user->setData([
		"inadmin"=>0,
		"deslogin"=>$_POST["email"],
		"desperson"=>$_POST["name"],
		"desemail"=>$_POST["email"],
		"despassword"=>$_POST["password"],
		"nrphone"=>$_POST["phone"]
	]);

	$user->save();

	User::login($_POST["email"], $_POST["password"]);
	
	header("Location: /checkout");
	exit;

});


// Rotas do Forgot

$app->get('/forgot', function() 
{   
	$page = new Page();
	$page->setTpl("forgot");
});

$app->post('/forgot', function() 
{   
	$user = User::getForgot($_POST["email"], false);
	header("Location: /forgot/sent");
	exit;
});

$app->get('/forgot/sent', function() 
{   
	$page = new Page();
	$page->setTpl("forgot-sent");
});

$app->get('/forgot/reset', function() 
{
	$user = User::validForgotDecrypt($_GET["code"]);   
	$page = new Page();
	$page->setTpl("forgot-reset", array(
		"name"=>$user["desperson"],
		"code"=>$_GET["code"]
		)
	);
});

$app->post('/forgot/reset', function() 
{   
	$forgot = User::validForgotDecrypt($_POST["code"]);
	User::setForgotUsed($forgot["idrecovery"]);

	$user = new User();
	$user->get((int)$forgot["iduser"]);

	$password = password_hash($_POST["password"], PASSWORD_DEFAULT, ["cost"=>12]);
	$user->setPassword($password);
	
	$page = new Page();

	$page->setTpl("forgot-reset-success");

});


//Rotas do Profile

$app->get('/profile', function() 
{ 
	User::verifyLogin(false); 
	$user = User::getFromSession();
	$page = new Page();
	$page->setTpl("profile", [
		"user"=>$user->getValues(),
		"profileMsg"=>User::getMsgSuccess(),
		"profileError"=>User::getMsgError();
	]);
});

$app->post('/profile', function() 
{ 
	User::verifyLogin(false);

	if (!isset($_POST["desperson"]) || ($_POST["desperson"] == ""))
	{
		User::setError("Informe seu nome.");	
		header("Location: /profile");
		exit;	
	}
	if (!isset($_POST["desemail"]) || ($_POST["desemail"] == ""))
	{
		User::setError("Informe seu email.");
		header("Location: /profile");
		exit;		
	}

	$user = User::getFromSession();

	if ($_POST["desemail"] !== $user->getdesemail())
	{
		if (User::LoginExists($_POST["desemail"]))
		{
			User::setError("Esse endereço de e-mail pertence a outro usuário")
		}

	}
	 

	//var_dump($_POST["despassword"]);
	//echo "<br><br>";
	//var_dump($user->getdespassword());
	//exit;

	// Para evitar command injection
	$_POST["inadmin"] = $user->getinadmin();
	$_POST["despassword"] = $user->getdespassword();
	$_POST["deslogin"] = $_POST["desemail"];

	$user->setData($_POST);
	$user->update();

	User::getMsgSuccess("Dados alterados com sucesso!");

	header("Location: /profile");
	exit;
});

?>
