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
	$page = new Page();
	$page->setTpl("cart", []);
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

?>
