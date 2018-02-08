<?php

use \Hcode\Model\User;

function formatPrice(float $vlprice)
{
	return number_format($vlprice, 2, ",", ".");
}

function checkLogin($inadmin = true)
{
	return User::checkLogin();
}

function getUserName()
{
	$user = User::getFromSession();
	//var_dump($user);
	//exit;
	return $user->getdesperson();
}

?>