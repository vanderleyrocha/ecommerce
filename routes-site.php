<?php

use \Hcode\Page;
use \Hcode\Model\Product;
use \Hcode\Model\User;
use \Hcode\Model\Category;

$app->get('/', function() 
{   
	$products = Product::listAll();
	$page = new Page();
	$page->setTpl("index", ["products"=>Product::checkList($products)]);
});


$app->get('/category/:idcategory', function($idcategory) 
{   
	User::verifyLogin();	
	$category = new Category();
	$category->get((int)$idcategory);
	$page = new Page();
	$page->setTpl("category", 
		array("category"=>$category->getValues(), 
			"products"=>Product::checkList($category->getProducts())
		)
	);	
});


?>
