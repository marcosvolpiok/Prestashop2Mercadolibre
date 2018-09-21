{*
* 2007-2014 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
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
*  @copyright 2007-2014 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
AAAAAA



<div class="tab-pane" id="modatex-nationalpost">
    
<!--
{*
MODATEX

national_post_reference	"1232323221323"
national_post_delivery	"532"
national_post_local_comment	"adadsds"




SCHEMA:
-Buscar si tiene domicilio o sucursal => poner campo oculto que indique si es domicilio o sucursal
-NATIONAL POST PUEDE SER: 532 o 531
	[532: Correo Argentino sucursal, 531: Correo Argentino domicilio]
*}
-->


{if $default_carrier!=41 AND $default_carrier!=40 AND  $default_carrier!=35 AND $default_carrier!=36}
	<p style="color: #E0505B"><strong>El cliente No seleccionó Correo Argentino</strong></p> 
	<p>Igualmente podés seleccionar este medio de envío</p> 
{/if}

<form 
{if !empty($arrEtiquetasNP)}
     style="display: none"
{/if}
id="form_modatex_national_post" style="width: 100px" form method="POST" action="http://www.modatex6.com.ar/venta/forastero_action.php?token=modatex_4f39394d326e6cdfef4aad5954c7ea145d6bc114&local_cd=1051" target="_blank">
     {*
     <input style="display: block" required="" type="text" id="national_post_local_amount" name="national_post_local_amount" placeholder="Monto" value="{$amount}" />
     *}

	<input style="display: none" required="" type="text" id="national_post_reference" name="national_post_reference" placeholder="Nº guía" value="" />
	<input style="display: none" id="nationalpost_to" type="text" name="to" value="" />
	<select id="national_post_delivery" name="national_post_delivery" required="" style="display: inline">
		<option value="532"{if $default_carrier==41 OR $default_carrier==35} selected{/if}>A Sucursal{if $default_carrier==41 OR $default_carrier==35} [Seleccionado por cliente]{/if}</option>
		<option value="531"{if $default_carrier==40 OR $default_carrier==36} selected{/if}>A Domicilio{if $default_carrier==40 OR $default_carrier==36} [Seleccionado por cliente]{/if}</option>
	</select>

	<input style="display: none" required="" type="text" name="national_post_local_comment" placeholder="Pedido" value="Pedido #{$id_order}" />

	<input type="button" onclick="
    if( {literal}$('#national_post_delivery').val()=='532'{/literal} ){ //Sucursal
    	console.log('suc');
    	$('#nationalpost_to').val('{$url_suc_to}&id_order={$id_order}');
    }else{ //Domicilio
    	console.log('dom');
    	$('#nationalpost_to').val('{$url_dom_to}&id_order={$id_order}');
    }

	var nationalpost_number='';
	nationalpost_number = $('.shipping_number_show').text();
	nationalpost_number = nationalpost_number.replace(' ', '');
	nationalpost_number = nationalpost_number.replace('cp', '');
	nationalpost_number = nationalpost_number.replace('CP', '');
	console.log('nationalpost_number = '+nationalpost_number);
	
	$('#national_post_reference').val(nationalpost_number);
	
	{literal}
    var characterReg = /^([0-9]{9})$/;
    {/literal}
    if(!characterReg.test(nationalpost_number)) {
		alert('Por favor revisá que el Número de Seguimiento debe tener solamente 9 números');
		return false;
    }	
	
	$('#form_modatex_national_post').submit();" value="Imprimir etiqueta" id="btn-modatex-nationalpost-submit" class="btn btn-primary"  style="display: inline" />
</form>

     <div style="display: block">
     {if !empty($arrEtiquetasNP)}
          <p>Etiquetas impresas:</p>
          <ul>
         {foreach from=$arrEtiquetasNP item=etiq}
               <li><a href="../content_national_post_etiquetas/{$id_order}/{$etiq.link}" target="blanq">{$etiq.name}</a></li>
         {/foreach}
         </ul>
    {/if}
    </div>


     {if !empty($arrEtiquetasNP)}
          <p><a href="#" onclick="$('#form_modatex_national_post').show('slow');">Imprimir etiqueta nueva</a></p>
     {/if}    
</div> 
