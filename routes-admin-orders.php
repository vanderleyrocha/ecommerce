<?php

use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Order;
use \Hcode\Model\Cart;


$app->get('/admin/orders/:idorder/status', function($idorder) 
{ 
	User::verifyLogin();
	$page = new PageAdmin();
	$order = new Order();
	$order->get((int)$idorder);
	$page->setTpl("order-status", [
		"order"=>$order->getValues(),
		"status"=>Order::listStatus(),
		"msgError"=>"",
		"msgSuccess"=>""
	]);
});

$app->post('/admin/orders/:idorder/status', function($idorder) 
{ 
	User::verifyLogin();

	$order = new Order();
	$order->get((int)$idorder);
	$order->setidstatus($_POST["idstatus"]);
	$order->save();
	header("Location: /admin/orders");
	exit;
});

$app->get('/admin/orders/:idorder/delete', function($idorder) 
{ 
	User::verifyLogin();

	$order = new Order();
	$order->get((int)$idorder);
	$order->delete();
	header("Location: /admin/orders");
	exit;
});


$app->get('/admin/orders/:idorder', function($idorder) 
{ 
	
	User::verifyLogin();
	$page = new PageAdmin();
	$order = new Order();
	$order->get((int)$idorder);
	$cart = new Cart();
	$cart->get($order->getidcart());
	$products = $cart->getProducts();
	//var_dump($products); 
	
	$page->setTpl("order", [
		"order"=>$order->getValues(),
		"cart"=>$cart->getValues(),
		"products"=>$cart->getProducts()
	]);
	
});


$app->get('/admin/orders', function() 
{ 
	User::verifyLogin();
	$page = new PageAdmin();
	$page->setTpl("orders", ["orders"=>Order::listAll()]);
});

?>