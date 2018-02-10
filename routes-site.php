<?php

use \Hcode\Page;
use \Hcode\Model\Product;
use \Hcode\Model\User;
use \Hcode\Model\Category;
use \Hcode\Model\Cart;
use \Hcode\Model\Address;
use \Hcode\Model\Order;
use \Hcode\Model\OrderStatus;



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
	echo "<h1>G E T cart</h1><br><br>";
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
	echo "<h1>P O S T frete</h1><br><br>";
	$cart = Cart::getFromSession();
	$cart->setFreight($_POST["deszipcode"]);
	header("Location: /cart");
	exit;
});


$app->get('/cart/:idproduct/add', function($idproduct) 
{ 
	echo "<h1>G E T add produto</h1><br><br>";  
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
	echo "<h1>G E T remove 1 produto</h1><br><br>";     
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


//Início das rotas (GET e POST) para finalização da compra
$app->get('/checkout', function() 
{ 
	User::verifyLogin(false);

	//echo "<h1>G E T</h1><br><br>";

	$address = new Address();
	$cart = Cart::getFromSession();

	$user = User::getFromSession();
	
	if (!$address->get($user->getidperson()))
	{
		//echo "<h2>Buscando endereço pelo CEP</h2><br><br>";
		if (isset($_GET["deszipcode"]))
		{
			$address->loadFromCEP($_GET["deszipcode"]);
			$cart->setdeszipcode($_GET["deszipcode"]);
			$cart->save();
			$cart->calculateTotal();
		}
			
	}

	//var_dump($address);
	//exit;

	$page = new Page();
	$page->setTpl("checkout", [
		"cart"=>$cart->getValues(),
		"address"=>$address->getValues(),
		"products"=>$cart->getProducts(),
		"error"=>Address::getMsgError()
	]);
});



$app->post('/checkout', function() 
{ 
	User::verifyLogin(false);

	$user = new User();
	$user = User::getFromSession();

	$cart = Cart::getFromSession();

	$_POST["idperson"] = $user->getidperson();

	$address = new Address(); 
	$address->setData($_POST);
	$model = Address::getModel();
	$fields = $model["fields"];
	$msgError = $model["msgError"];

	foreach ($fields as $value) 
	{
		if (!isset($_POST[$value]) || $_POST[$value] === "")
		{
			$page = new Page();
			$page->setTpl("checkout", [
				"cart"=>$cart->getValues(),
				"address"=>$address->getValues(),
				"products"=>$cart->getProducts(),
				"error"=>$msgError[$value]
			]);
			exit;
		}
	}

	$address->save();

	$cart->calculateTotal();
	$data = [
		"idcart"=>$cart->getidcart(),
		"iduser"=>$user->getiduser(),
		"idstatus"=>OrderStatus::EM_ABERTO,
		"idaddress"=>$address->getidaddress(),
		"vltotal"=>$cart->getvltotal()
	];

	$order = new Order();
	$order->setData($data);

	$order->save();

	header("Location: /order/".$order->getidorder());
	exit;
});
//Fim das rotas (GET e POST) para finalização da compra


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


$app->get('/order/:idorder', function($idorder) 
{ 
	User::verifyLogin(false); 

	$order = new Order();
	$order->get((int)$idorder);
	$page = new Page();
	$page->setTpl("payment", [
		"order"=>$order->getValues()
	]);
});


$app->get('/boleto/:idorder', function($idorder) 
{ 
	User::verifyLogin(false); 

	$order = new Order();
	$order->get((int)$idorder);


// DADOS DO BOLETO PARA O SEU CLIENTE
$dias_de_prazo_para_pagamento = 10;
$taxa_boleto = 5.00;
$data_venc = date("d/m/Y", time() + ($dias_de_prazo_para_pagamento * 86400));  // Prazo de X dias OU informe data: "13/04/2006"; 
$valor_cobrado = $order->getvltotal(); // Valor - REGRA: Sem pontos na milhar e tanto faz com "." ou "," ou com 1 ou 2 ou sem casa decimal
$valor_cobrado = str_replace(",", ".",$valor_cobrado);
$valor_boleto=number_format($valor_cobrado+$taxa_boleto, 2, ',', '');

$dadosboleto["nosso_numero"] = $order->getidorder();  // Nosso numero - REGRA: Máximo de 8 caracteres!
$dadosboleto["numero_documento"] = $order->getidorder();	// Num do pedido ou nosso numero
$dadosboleto["data_vencimento"] = $data_venc; // Data de Vencimento do Boleto - REGRA: Formato DD/MM/AAAA
$dadosboleto["data_documento"] = date("d/m/Y"); // Data de emissão do Boleto
$dadosboleto["data_processamento"] = date("d/m/Y"); // Data de processamento do boleto (opcional)
$dadosboleto["valor_boleto"] = $valor_boleto; 	// Valor do Boleto - REGRA: Com vírgula e sempre com duas casas depois da virgula

// DADOS DO SEU CLIENTE
$dadosboleto["sacado"] = $order->getdesperson();
$dadosboleto["endereco1"] = $order->getdesaddress(). ", " .$order->getdescomplement(). "  - " .$order->getdesdistrict();
$dadosboleto["endereco2"] = " " .$order->getdescity(). " - " .$order->getdesstate(). " - " .$order->getdeszipcode();

// INFORMACOES PARA O CLIENTE
$dadosboleto["demonstrativo1"] = "Pagamento de Compra na Loja Hcode E-commerce";
$dadosboleto["demonstrativo2"] = "Taxa bancária - R$ 0,00";
$dadosboleto["demonstrativo3"] = "";
$dadosboleto["instrucoes1"] = "- Sr. Caixa, cobrar multa de 2% após o vencimento";
$dadosboleto["instrucoes2"] = "- Receber até 10 dias após o vencimento";
$dadosboleto["instrucoes3"] = "- Em caso de dúvidas entre em contato conosco: suporte@hcode.com.br";
$dadosboleto["instrucoes4"] = "&nbsp; Emitido pelo sistema Projeto Loja Hcode E-commerce - www.hcode.com.br";

// DADOS OPCIONAIS DE ACORDO COM O BANCO OU CLIENTE
$dadosboleto["quantidade"] = "";
$dadosboleto["valor_unitario"] = "";
$dadosboleto["aceite"] = "";		
$dadosboleto["especie"] = "R$";
$dadosboleto["especie_doc"] = "";


// ---------------------- DADOS FIXOS DE CONFIGURAÇÃO DO SEU BOLETO --------------- //


// DADOS DA SUA CONTA - ITAÚ
$dadosboleto["agencia"] = "1690"; // Num da agencia, sem digito
$dadosboleto["conta"] = "48781";	// Num da conta, sem digito
$dadosboleto["conta_dv"] = "2"; 	// Digito do Num da conta

// DADOS PERSONALIZADOS - ITAÚ
$dadosboleto["carteira"] = "175";  // Código da Carteira: pode ser 175, 174, 104, 109, 178, ou 157

// SEUS DADOS
$dadosboleto["identificacao"] = "Hcode Treinamentos";
$dadosboleto["cpf_cnpj"] = "24.700.731/0001-08";
$dadosboleto["endereco"] = "Rua Ademar Saraiva Leão, 234 - Alvarenga, 09853-120";
$dadosboleto["cidade_uf"] = "São Bernardo do Campo - SP";
$dadosboleto["cedente"] = "HCODE TREINAMENTOS LTDA - ME";

// NÃO ALTERAR!
$path = $_SERVER["DOCUMENT_ROOT"].DIRECTORY_SEPARATOR."res".DIRECTORY_SEPARATOR."boletophp".DIRECTORY_SEPARATOR."include".DIRECTORY_SEPARATOR;
require_once($path."funcoes_itau.php"); 
require_once($path."layout_itau.php");



});

?>
