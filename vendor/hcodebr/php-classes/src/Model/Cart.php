<?php 

namespace Hcode\Model;
use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Model\User;

class Cart extends Model 
{
	const SESSION = "Cart";
	const SESSION_ERROR = "CartError";


	public static function getCart()
	{
		$hasCart = false;
		$cart = new Cart();
		if (User::checkLogin(false))
		{
			$hasCart = $cart->getFromUserId((int)$_SESSION[User::SESSION]["iduser"]);
			$cart->setFonte("SESSION - UserId");
		} else {
			$hasCart = $cart->getFromSession();
			$cart->setFonte("SESSION");
		}
		if (!$hasCart)
		{
			$cart->setdessessionid(session_id());
			$cart->setFonte("Novo");
		}
		if (User::checkLogin(false))
		{
			$user = User::getFromSession();
			$cart->setiduser($user->getiduser());
		}
		$cart->save();
		$cart->setToSession();
		
		//var_dump($cart);
		//exit;

		return $cart;
	}


	private function getFromSession()
	{
		if (isset($_SESSION[Cart::SESSION]) && ((int)$_SESSION[Cart::SESSION]["idcart"] > 0))
		{
			$this->get((int)$_SESSION[Cart::SESSION]["idcart"]);
		}
		else
		{
			Cart::getFromSessionID();
		}
		
		return ((int)$this->getidcart() > 0);
	}


	private function setToSession()
	{
		$_SESSION[Cart::SESSION] = $this->getValues();
	}

	private static function getFromSessionID()
	{
		$sql = new Sql();
		$results = $sql->select("SELECT * FROM tb_carts WHERE dessessionid = :dessessionid",
			array(":dessessionid"=>session_id())
		);

		if (count($results) > 0)
		{
			$this->setData($results[0]);
			return true;
		} else {
			return false;
		}
	}


	private function getFromUserId($userId)
	{
		$sql = new Sql();
		$results = $sql->select("SELECT * FROM tb_carts WHERE iduser = :iduser",
			array(":iduser"=>$userId)
		);

		if (count($results) > 0)
		{
			$this->setData($results[0]);
			return true;
		} else {
			return false;
		}
	}


	public function get(int $idcart)
	{
		$sql = new Sql();
		$results = $sql->select("SELECT * FROM tb_carts WHERE idcart = :idcart",
			array(":idcart"=>$idcart)
		);

		if (count($results) > 0)
		{
			$this->setData($results[0]);
			return true;
		} else {
			return false;
		}
	}


	public function save()
	{
		$sql = new Sql();
		$results = $sql->select("CALL sp_carts_save (:idcart, :dessessionid, :iduser, :deszipcode, :vlfreight, :nrdays)",
			array(
				":idcart"=>$this->getidcart(),
				":dessessionid"=>$this->getdessessionid(),
				":iduser"=>$this->getiduser(),
				":deszipcode"=>$this->getdeszipcode(),
				":vlfreight"=>$this->getvlfreight(),
				":nrdays"=>$this->getnrdays()
			)
		);

		if (count($results) > 0)
		{
			$this->setData($results[0]);
		}
	}

	public function addProduct(Product $product)
	{
		$sql = new Sql();
		$sql->query("INSERT INTO tb_cartsproducts (idcart, idproduct) VALUES (:idcart, :idproduct)",
		[
			":idcart"=>$this->getidcart(),
			":idproduct"=>$product->getidproduct()
		]);

		$this->getCalculateTotal();
	}

	public function removeProduct(Product $product, $all = false)
	{
		$sql = new Sql();
		if ($all)
		{
			$sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW() 
				WHERE idcart = :idcart AND idproduct = :idproduct AND dtremoved IS NULL",
				[
					":idcart"=>$this->getidcart(),
					":idproduct"=>$product->getidproduct()
				]
			);
		} 
		else 
		{
			$sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW() 
				WHERE idcart = :idcart AND idproduct = :idproduct AND dtremoved IS NULL LIMIT 1",
				[
					":idcart"=>$this->getidcart(),
					":idproduct"=>$product->getidproduct()
				]
			);			
		}

		$this->getCalculateTotal();

	}

	public function getProducts()
	{
		$sql = new Sql();
		$results = $sql->select("
			SELECT b.idproduct, b.desproduct, b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, b.desurl, 
				COUNT(*) AS nrqtd, SUM(b.vlprice) AS vltotal
			FROM tb_cartsproducts a 
			INNER JOIN tb_products b USING(idproduct) 
			WHERE a.idcart = :idcart AND a.dtremoved IS NULL 
			GROUP BY b.idproduct, b.desproduct, b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, b.desurl
			ORDER BY b.desproduct",
			array(":idcart"=>$this->getidcart())
		);

		return Product::checkList($results);
	}


	public function getProductsTotals()
	{
		$sql = new Sql();
		$results = $sql->select("
			SELECT SUM(vlprice) AS vlprice, SUM(vlwidth) AS vlwidth, SUM(vlheight) AS vlheight, SUM(vllength) AS vllength, SUM(vlweight) AS vlweight, COUNT(*) AS nrqtd
			FROM tb_products a
			INNER JOIN tb_cartsproducts b USING(idproduct)
			WHERE b.idcart = :idcart AND b.dtremoved IS NULL",
			[
				":idcart"=>$this->getidcart()
			]
		);
		if (count($results) > 0) {
			return $results[0];
		} else {
			return [];
		}
	}

	public function setFreight($nrzipcode)
	{
		$nrzipcode = str_replace("-", "", $nrzipcode);
		$totals = $this->getProductsTotals();
		if ($totals["nrqtd"] > 0) {
			if ($totals["vlheight"] < 2) $totals["vlheight"] = 2;
			if ($totals["vllength"] < 16) $totals["vllength"] = 16;
			$qs = http_build_query([
				"nCdEmpresa"=>"",
				"sDsSenha"=>"",
				"nCdServico"=>"40010",
				"sCepOrigem"=>"69918430",
				"sCepDestino"=>$nrzipcode,
				"nVlPeso"=>$totals["vlweight"],
				"nCdFormato"=>"1",
				"nVlComprimento"=>$totals["vllength"],
				"nVlAltura"=>$totals["vlheight"],
				"nVlLargura"=>$totals["vlwidth"],
				"nVlDiametro"=>"0",
				"sCdMaoPropria"=>"S",
				"nVlValorDeclarado"=>$totals["vlprice"],
				"sCdAvisoRecebimento"=>"S"
			]);
			$xml = simplexml_load_file("http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx/CalcPrecoPrazo?".$qs);
			//var_dump($xml);
			//exit;
			$result = $xml->Servicos->cServico;

			if ($result->Erro != "0")
			{
				Cart::setMsgError($result->MsgErro);
			} else {
				Cart::clearMsgError();
			}

			$this->setnrdays($result->PrazoEntrega);
			$this->setvlfreight(Cart::formatValueToDecimal($result->Valor));
			$this->setdeszipcode($nrzipcode);

			$this->save();

			return $result;
		}

	}

	private static function formatValueToDecimal($value):float
	{
		$value = str_replace(".", "", $value);
		return str_replace(",", ".", $value);
	}

	public static function setMsgError($msg)
	{
		$_SESSION[Cart::SESSION_ERROR] =  $msg;
	}


	public static function getMsgError()
	{
		$msg = (isset($_SESSION[Cart::SESSION_ERROR])) ? $_SESSION[Cart::SESSION_ERROR] : "";
		Cart::clearMsgError();
		return $msg;
	}

	public static function clearMsgError()
	{
		$_SESSION[Cart::SESSION_ERROR] =  NULL;
	}


	public function updateFreight()
	{
		if ($this->getdeszipcode() != "") {
			$this->setFreight($this->getdeszipcode());
		}
	}


	public function getValues()
	{
		$this->calculateTotal();
		$values = parent::getValues();

		if (!isset($values["deszipcode"])) $values["deszipcode"] = "";
		if (!isset($values["vlfreight"])) $values["vlfreight"] = 0.0;
		if (!isset($values["nrdays"])) $values["nrdays"] = 0;
		
		//var_dump($values);
		//exit;		
		return $values;
	}

	public function calculateTotal()
	{
		$this->updateFreight();
		$totals = $this->getProductsTotals();
		$this->setvlsubtotal($totals["vlprice"]);
		$this->setvltotal($totals["vlprice"] + $this->getvlfreight());
	}


}

 ?>