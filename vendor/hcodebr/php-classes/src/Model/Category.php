<?php 

namespace Hcode\Model;
use \Hcode\DB\Sql;
use \Hcode\Model;

class Category extends Model {

	public static function listAll()
	{
		$sql = new Sql();
		return $sql->select("SELECT * FROM tb_categories ORDER BY descategory");
	}

public function save()
	{
		$sql = new Sql();
		$results = $sql->select("CALL sp_categories_save(:idcategory, :descategory)",
			array(
				":idcategory"=>$this->getidcategory(),
				":descategory"=>$this->getdescategory()
			)
		);
		$this->setData($results[0]);
		Category::updateFile();
	}

	public function get($idcategory)
	{
		$sql = new Sql();
		$results = $sql->select("SELECT * FROM tb_categories WHERE idcategory = :idcategory",
			array(
				":idcategory"=>$idcategory
			)
		);
		$this->setData($results[0]);
	}

	public function delete()
	{
		$sql = new Sql();
		$sql->query("DELETE FROM tb_categories WHERE idcategory = :idcategory",
			array(
				":idcategory"=>$this->getidcategory()
			)
		);
		Category::updateFile();
	}

	public static function updateFile()
	{
		$categories = Category::listAll();
		$html = [];
		foreach ($categories as $row) {
			$idcategory = $row["idcategory"];
			$descategory = $row["descategory"];
			array_push($html, "<li><a href='/category/$idcategory'>$descategory</a></li>");
		}

		file_put_contents($_SERVER["DOCUMENT_ROOT"].DIRECTORY_SEPARATOR."views".DIRECTORY_SEPARATOR."categories-menu.html", implode("", $html));
	}

	public function getProducts($related = true)
	{
		$sql = new Sql();
		if ($related)
		{
			return $sql->select(
				"select * from tb_products a
				where a.idproduct in 
				(select b.idproduct from tb_productscategories b where b.idcategory = :idcategory)",
				[":idcategory"=>$this->getidcategory()]
			);
		} else {
			return $sql->select(
				"select * from tb_products a
				where a.idproduct not in 
				(select b.idproduct from tb_productscategories b where b.idcategory = :idcategory)",
				[":idcategory"=>$this->getidcategory()]
			);
		}
	}


	public function getProductsPage($page = 1, $itensPerPage = 4)
	{
		$start = ($page - 1) * $itensPerPage;
		$sql = new Sql();
		
		$results = $sql->select(
			"SELECT SQL_CALC_FOUND_ROWS * 
			 FROM tb_products a INNER JOIN tb_productscategories b USING(idproduct)
			 INNER JOIN tb_categories c USING(idcategory)
			 WHERE c.idcategory = :idcategory
			 LIMIT $start, $itensPerPage",
			 [":idcategory"=>$this->getidcategory()]);

		$resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal");

		return ["data"=>Product::checkList($results),
			"total"=>(int)$resultTotal[0]["nrtotal"],
			"pages"=>ceil($resultTotal[0]["nrtotal"] / $itensPerPage)
		];
			
	}


	public function addProduct($idproduct)
	{
		$sql = new Sql();
		$sql->query(
			"
				INSERT INTO tb_productscategories(idcategory, idproduct)
				VALUES (:idcategory, :idproduct)
			",
			array(
				":idcategory"=>$this->getidcategory(),
				":idproduct"=>$idproduct
			)
		);
	}

	public function removeProduct($idproduct)
	{
		$sql = new Sql();
		$sql->query("DELETE FROM tb_productscategories WHERE idcategory = :idcategory AND idproduct = :idproduct",
			array(
				":idcategory"=>$this->getidcategory(),
				":idproduct"=>$idproduct
			)
		);
	}
}

 ?>