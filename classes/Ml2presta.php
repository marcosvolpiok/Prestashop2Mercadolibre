<?php

class ml2presta extends ObjectModel{
	public $id_ml2presta;
	public $id_product;
	public $id_ml;

	public static $definition = array(
			'table' => 'ml2presta',
			'primary' => 'id_ml2presta',
			'multilang' => false,
			'fields' => array(
					'id_ml2presta' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
					'id_product' => array('type' => self::TYPE_INT, 'required' => true, 'validate' => 'isUnsignedId'),
					'id_ml' => array('type' => self::TYPE_STRING, 'required' => true, 'size' => 13),
			)
	);


	public function get_Mlid_By_Idproduct($id_product){
		$query="SELECT COUNT(id_ml) AS Q, id_ml
		FROM ". _DB_PREFIX_ . "ml2presta
		WHERE id_product = " . $id_product;
		$res=Db::getInstance()->executeS($query);
		
		//print_r($res);
		//die;
		return $res[0]["id_ml"];
	}
}