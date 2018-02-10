<?php

use \Hcode\Model\User;
use \Hcode\Model\Cart;

function formatPrice($vlprice)
{
	if (!($vlprice > 0)) $vlprice = 0;
	return number_format($vlprice, 2, ",", ".");
}

function checkLogin($inadmin = true)
{
	return User::checkLogin($inadmin);
}

function getUserName()
{
	$user = User::getFromSession();
	return $user->getdesperson();
}

function getCartTotals($field)
{
	$cart = Cart::getCart();
	$totals = $cart->getProductsTotals();
	return $totals[$field];
}

function getCartId()
{
	$cart = Cart::getCart();
	return $cart->getidcart()."- ".$cart->getFonte();
}

?>