<?php
/**
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2015 PrestaShop SA
*  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class Ml2Presta extends ObjectModel
{
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


    public function getMlIdByIdProduct($id_product)
    {
        $query="SELECT COUNT(id_ml) AS Q, id_ml, id_ml2presta
		FROM ". _DB_PREFIX_ . "ml2presta
		WHERE id_product = " . (int) $id_product;
        $res=Db::getInstance()->executeS($query);
        
        //print_r($res);
        //die;
        return $res[0]["id_ml"];
    }

    //Si existe el registro, devuelve el ID, sino devuelve false
    public function existsIdProduct($id_product)
    {
        $query="SELECT id_ml2presta
		FROM ". _DB_PREFIX_ . "ml2presta
		WHERE id_product = " . (int) $id_product;
        $res=Db::getInstance()->executeS($query);
        
        //print_r($res);
        //die;
        return $res[0]["id_ml2presta"];
    }

    //Devuelve id de categor??a con el id de prestashop
    public function getCategoryByIdProduct($id_product)
    {
        $query="SELECT id_ml_category
		FROM ". _DB_PREFIX_ . "ml2presta
		WHERE id_product = " . (int) $id_product;
        $res=Db::getInstance()->executeS($query);
        
        //print_r($res);
        //echo "jjjj " .  $res[0]["id_ml_category"] . "jjj";
        //die;
        return $res[0]["id_ml_category"];
        //return $res;
    }

    //Si existe el id de Mercadolibre, devuelve el ID, sino devuelve false
    public function existsMlProduct($id_ml)
    {
        $query="SELECT id_ml2presta
        FROM ". _DB_PREFIX_ . "ml2presta
        WHERE id_ml = '" . $id_ml . "'";
        $res=Db::getInstance()->executeS($query);

        return $res[0]["id_ml2presta"];
    }
}
