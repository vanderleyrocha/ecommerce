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


// Rota para exibir produtos por categoria (Template category)
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
// Rota para exibir produtos por categoria (Template category)


// Rota para exibir detalhes do produto (Template product-detail)
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
// Rota para exibir detalhes do produto (Template product-detail)


//Início das rotas de gerenciamento do carrinho
$app->get('/cart', function() 
{  
	//echo "<h1>G E T cart</h1><br><br>";
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
	//echo "<h1>P O S T frete</h1><br><br>";
	$cart = Cart::getFromSession();
	$cart->setFreight($_POST["deszipcode"]);
	header("Location: /cart");
	exit;
});


$app->get('/cart/:idproduct/add', function($idproduct) 
{ 
	//echo "<h1>G E T add produto</h1><br><br>";  
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
	//echo "<h1>G E T remove 1 produto</h1><br><br>";     
	$product = new Product();
	$product->get((int)$idproduct);
	$cart = Cart::getFromSession();
	$cart->removeProduct($product);

	header("Location: /cart");
	exit;
});


$app->get('/cart/:product/remove', function($idproduct) 
{ 
	//echo "<h1>G E T remove todos os produto</h1><br><br>";   
	$product = new Product();
	$product->get((int)$idproduct);
	$cart = Cart::getFromSession();
	$cart->removeProduct($product, true);

	header("Location: /cart");
	exit;
});
//fim das rotas de gerenciamento do carrinho



//Início das rotas (GET, POST) de login e resistro de erro
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
	
	header("Location: /");
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
//Fim das rotas (GET, POST) de login e resistro de erro


// Início das rotas do Forgot (Esqueci a senha)
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
// Fim das rotas do Forgot (Esqueci a senha)


//Início das rotas do Profile de usuário
$app->get('/profile', function() 
{ 
	User::verifyLogin(false); 
	$user = User::getFromSession();
	$page = new Page();
	$page->setTpl("profile", [
		"user"=>$user->getValues(),
		"profileMsg"=>User::getMsgSuccess(),
		"profileError"=>User::getMsgError()
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
			User::setError("Esse endereço de e-mail pertence a outro usuário");
		}

	}

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

//Fim das rotas do Profile de usuário


?>
