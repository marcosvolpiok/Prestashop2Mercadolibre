<?php

class ml2presta extends ObjectModel{
	public $id_ml2presta;
	public $id_product;
	public $id_ml;
	public $id_ml_category;

	public static $definition = array(
			'table' => 'ml2presta',
			'primary' => 'id_ml2presta',
			'multilang' => false,
			'fields' => array(
					'id_ml2presta' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
					'id_product' => array('type' => self::TYPE_INT, 'required' => true, 'validate' => 'isUnsignedId'),
					'id_ml' => array('type' => self::TYPE_STRING, 'size' => 13),
					'id_ml_category' => array('type' => self::TYPE_STRING, 'size' => 13),

			)
	);


	public function get_Mlid_By_Idproduct($id_product){
		$query="SELECT COUNT(id_ml) AS Q, id_ml, id_ml2presta
		FROM ". _DB_PREFIX_ . "ml2presta
		WHERE id_product = " . $id_product;
		$res=Db::getInstance()->executeS($query);
		
		//print_r($res);
		//die;
		return $res[0]["id_ml"];
	}

	//Si existe el registro, devuelve el ID, sino devuelve false
	public function exists_idproduct($id_product){
		$query="SELECT id_ml2presta
		FROM ". _DB_PREFIX_ . "ml2presta
		WHERE id_product = " . $id_product;
		$res=Db::getInstance()->executeS($query);
		
		//print_r($res);
		//die;
		return $res[0]["id_ml2presta"];
	}	

	//Devuelve id de categoría con el id de prestashop
	public function get_category_by_idproduct($id_product){
		$query="SELECT id_ml_category
		FROM ". _DB_PREFIX_ . "ml2presta
		WHERE id_product = " . $id_product;
		$res=Db::getInstance()->executeS($query);
		
		//print_r($res);
		//die;
		return $res[0]["id_ml_category"];

	}
}