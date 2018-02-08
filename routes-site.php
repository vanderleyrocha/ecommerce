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



?>
