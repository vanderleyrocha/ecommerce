<?php

use \Hcode\Page;
use \Hcode\Model\User;
use \Hcode\Model\Address;
use \Hcode\Model\Cart;
use \Hcode\Model\Order;
use \Hcode\Model\OrderStatus;


//Início das rotas (GET e POST) para finalização da compra
$app->get('/checkout', function() 
{ 
	User::verifyLogin(false);

	//echo "<h1>G E T</h1><br><br>";

	$address = new Address();
	$cart = Cart::getCart();

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

	$cart = Cart::getCart();

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

$app->get('/profile/orders', function() 
{ 
	User::verifyLogin(false); 
	$user = User::getFromSession();

	//var_dump($user->getOrders());
	//exit;

	$page = new Page();
	$page->setTpl("profile-orders", [
		"orders"=>$user->getOrders()
	]);
});


$app->get('/profile/orders/:idorder', function($idorder) 
{ 
	User::verifyLogin(false); 

	$order = new order();
	$order->get((int)$idorder);

	$cart = new Cart();
	$cart->get((int)$order->getidcart());

	//var_dump($order->getValues());
	//exit;

	$page = new Page();
	$page->setTpl("profile-orders-detail", [
		"order"=>$order->getValues(),
		"cart"=>$cart->getValues(),
		"products"=>$cart->getProducts()
	]);
});


?>