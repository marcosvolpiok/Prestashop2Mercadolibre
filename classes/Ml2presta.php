<?php

class Ml2presta extends ObjectModel{
	public $id_ml2presta;
	public $id_product;
	public $id_ml;

	public static $definition = array(
			'table' => 'ml2presta',
			'primary' => 'id_ml2presta',
			'multilang' => false,
			'fields' => array(
					'id_ml2presta' => array('type' => self::TYPE_INT, 'required' => true, 'validate' => 'isUnsignedId'),
					'id_product' => array('type' => self::TYPE_INT, 'required' => true, 'validate' => 'isUnsignedId'),
					'id_ml' => array('type' => self::TYPE_INT, 'required' => true, 'validate' => 'isUnsignedId'),
			)
	);

}