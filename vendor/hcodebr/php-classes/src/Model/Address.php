<?php 

namespace Hcode\Model;
use \Hcode\DB\Sql;
use \Hcode\Model;

class Address extends Model 
{

	const ADDRESS_ERROR = "AddressError";

	public static function getModel($optional = false)
	{
		$model = array(
			"fields"=>array("desaddress", "descomplement", "descity", "desstate", "descountry", "deszipcode", "desdistrict"),
			"msgError"=>array(
				"desaddress"=>"Digite o nome do logradouro",
				"descomplement"=>"Digite os dados complementares do endereço",
				"descity"=>"Digite o nome da cidade",
				"desstate"=>"Digite o nome do estado",
				"descountry"=>"Digite o nome do país",
				"deszipcode"=>"Digite o CEP",
				"desdistrict"=>"Digite o bairro"
			)
		);
		return $model;
	}

	public static function getAddress($nrcep)
	{
		//http://viacep.com.br/ws/01001000/json/
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://viacep.com.br/ws/$nrcep/json/");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$data = json_decode(curl_exec($ch), true);
		curl_close($ch);
		return $data;
	}

	public function loadFromCEP($nrcep)
	{
		$data = $this->getAddress($nrcep);
		if (isset($data["logradouro"]) && $data["logradouro"])
		{
			$this->setdesaddress($data["logradouro"]);
			$this->setdescomplement($data["complemento"]);
			$this->setdesdistrict($data["bairro"]);
			$this->setdescity($data["localidade"]);
			$this->setdesstate($data["uf"]);
			$this->setdescountry("Brasil");
			$this->setdeszipcode($nrcep);
		}		
	}


	public function save()
	{
		//var_dump($this);
		//exit;
		$sql = new Sql();
		if (!(int)$this->getidaddress() > 0)
		{
			$this->setidaddress($sql->getValue("SELECT idaddress FROM tb_addresses where idperson = :idperson", [":idperson"=>$this->getidperson()]));
		}
		$results = $sql->select("
			CALL sp_addresses_save
			(:idaddress, :idperson, :desaddress, :descomplement, :descity, :desstate, :descountry, :deszipcode, :desdistrict)
			", array(
			":idaddress"=>$this->getidaddress(),
			":idperson"=>$this->getidperson(),
			":desaddress"=>utf8_decode($this->getdesaddress()),
			":descomplement"=>utf8_decode($this->getdescomplement()),
			":descity"=>utf8_decode($this->getdescity()),
			":desstate"=>utf8_decode($this->getdesstate()),
			":descountry"=>utf8_decode($this->getdescountry()),
			":deszipcode"=>$this->getdeszipcode(),
			":desdistrict"=>utf8_decode($this->getdesdistrict())
		));

		if (count($results) > 0)
		{
			$this->setData($results[0]);	
		}		
	}




	public function get(int $idperson)
	{
		$sql = new Sql();
		$results = $sql->select("SELECT * FROM tb_addresses WHERE idperson = :idperson",
			array(":idperson"=>$idperson)
		);

		if (count($results) > 0)
		{
			$this->setData($results[0]);
			return true;
		} else {
			return false;
		}
	}



	public static function setMsgError($msg)
	{
		$_SESSION[Address::ADDRESS_ERROR] =  $msg;
	}


	public static function getMsgError()
	{
		$msg = (isset($_SESSION[Address::ADDRESS_ERROR])) ? $_SESSION[Address::ADDRESS_ERROR] : "";
		Address::clearMsgError();
		return $msg;
	}

	public static function clearMsgError()
	{
		$_SESSION[Address::ADDRESS_ERROR] =  NULL;
	}


}

 ?>