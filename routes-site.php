<?php

use \Hcode\Page;
use \Hcode\Model\Product;
use \Hcode\Model\User;
use \Hcode\Model\Category;
use \Hcode\Model\Cart;

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
	
	//echo "<br><br><br>Carrinho:" . $cart->getidcart();
	//exit;

	$page = new Page();
	$page->setTpl("cart", [
		"cart"=>$cart->getValues(),
		"products"=>$cart->getProducts()
	]);
});


$app->post('/cart/freight', function() 
{   
	$page = new Page();
	echo "carrinho...";
	exit;
	$page->setTpl("cart", []);
});

$app->get('/checkout', function() 
{   
	$page = new Page();
	echo "Finalizar compra...";
	exit;
	$page->setTpl("cart", []);
});


$app->get('/cart/:idproduct/add', function($idproduct) 
{   
	$product = new Product();
	$product->get((int)$idproduct);
	$cart = Cart::getFromSession();
	$qtd = (isset($_GET["qtd"])) ? (int)$_GET["qtd"] : 1;
	//
	//echo "<br><br>Produto:<br>";
	//print_r($product);
	//echo "<br><br>Carrinho:<br>";
	//print_r($cart);
	//echo "<br><br>Quantidade: $qtd<br>";
	//exit;

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

?>
