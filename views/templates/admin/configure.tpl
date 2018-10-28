{*
* 2007-2018 PrestaShop
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2018 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
<div class="tab-content panel">	
	<!-- Tab Configuracion -->
	<div id="general">
		<div class="panel">
			<div class="panel-heading">
				<i class="icon-cogs"></i>{l s='Version used:' mod='mercadolibre2prestashop'}
			</div>
			{l s='Version:' mod='mercadolibre2prestashop'}

			{$version|escape:'htmlall':'UTF-8'}
		</div>	
		{* $config_general *}
		{* $config_general|escape:'htmlall':'UTF-8' *}

<form id="module_form" class="defaultForm form-horizontal" action="#" method="post" enctype="multipart/form-data" novalidate> <input type="hidden" name="btnSubmitLogin" value="1" /> <div class="panel" id="fieldset_0"> <div class="panel-heading"> <i class="icon-cogs"></i> 
{l s='Credentials' mod='mercadolibre2prestashop'} </div> <div class="form-wrapper"> <div class="form-group"> <label class="control-label col-lg-3 required"> 
{l s='AppId' mod='mercadolibre2prestashop'} 
</label> <div class="col-lg-9"> <input type="text" name="appId" id="appId" value="8168669721034782" class="" required="required" /> </div> </div> <div class="form-group"> <label class="control-label col-lg-3 required"> 
{l s='SecretKey' mod='mercadolibre2prestashop'} 
</label> <div class="col-lg-9"> <input type="text" name="secretKey" id="secretKey" value="mkQAwLhNs0FZs9Vh0sdqlrRNn7vUTONl" class="" required="required" /> </div> </div> <div class="form-group"> <label class="control-label col-lg-3 required"> 
{l s='Country' mod='mercadolibre2prestashop'}
 </label> <div class="col-lg-9"> <select name="pais" class=" fixed-width-xl" id="pais" > <option value="" >
 {l s='Select your country' mod='mercadolibre2prestashop'}
</option> <option value="MLA" selected="selected" >Argentina</option> <option value="MLB" >Brasil</option> <option value="MCO" >Colombia</option> <option value="MCR" >Costa Rica</option> <option value="MEC" >Ecuador</option> <option value="MLC" >Chile</option> <option value="MLM" >Mexico</option> <option value="MLU" >Uruguay</option> <option value="MLV" >Venezuela</option> <option value="MPA" >Panama</option> <option value="MPE" >Peru</option> <option value="MPT" >Portugal</option> <option value="MRD" >Dominicana</option> </select> <p class="help-block"> 
 {l s='Select the country with which you operate with MercadoLibre' mod='mercadolibre2prestashop'} 
</p> </div> </div> </div><!-- /.form-wrapper --> <div class="panel-footer"> <button type="submit" value="1" id="module_form_submit_btn" name="btnSubmitLogin" class="button"> <i class="process-icon-save"></i>  {l s='Save' mod='mercadolibre2prestashop'} </button> </div> </div> </form>


	</div>
</div>