<?php 

namespace Hcode\Model;
use \Hcode\DB\Sql;
use \Hcode\Model;

class Order extends Model 
{

	public function save()
	{
		$sql = new Sql();
		if (!(int)$this->getidorder() > 0)
		{
			$this->setidorder($sql->getValue("SELECT idorder FROM tb_orders where idcart = :idcart", [":idcart"=>$this->getidcart()]));
		}
		$results = $sql->select("
			CALL sp_orders_save (:idorder, :idcart, :iduser, :idstatus, :idaddress, :vltotal)
			", array(
			":idorder"=>$this->getidorder(),
			":idcart"=>$this->getidcart(),
			":iduser"=>$this->getiduser(),
			":idstatus"=>$this->getidstatus(),
			":idaddress"=>$this->getidaddress(),
			":vltotal"=>$this->getvltotal()
		));

		if (count($results) > 0)
		{
			$this->setData($results[0]);	
		}		
	}

	public function get(int $idorder)
	{
		$sql = new Sql();
		$results = $sql->select("
			SELECT * 
			FROM tb_orders a
			INNER JOIN tb_ordersstatus b USING(idstatus)
			INNER JOIN tb_carts c USING(idcart)
			INNER JOIN tb_users d ON d.iduser = a.iduser
			INNER JOIN tb_addresses e USING(idaddress)
			INNER JOIN tb_persons f ON f.idperson = d.idperson
			WHERE a.idorder = :idorder", 
			array(":idorder"=>$idorder)
		);

		if (count($results) > 0)
		{
			$this->setData($results[0]);
			return true;
		} else {
			return false;
		}
	}

}

 ?>