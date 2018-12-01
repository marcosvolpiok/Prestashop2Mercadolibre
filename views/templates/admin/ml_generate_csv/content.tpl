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

<form method="post" action="{$formAction |escape:'htmlall':'UTF-8'}">
	{foreach from=$items item=item}
		<img src="{$item.body->thumbnail |escape:'htmlall':'UTF-8'}" alt="Imagen" />
		<label id="item_{$item.body->id |escape:'htmlall':'UTF-8'}">
			<input for="item_{$item.body->id |escape:'htmlall':'UTF-8'}" type="checkbox" name="item[]" value="{$item.body->id |escape:'htmlall':'UTF-8'}"

			 />
			{$item.body->title |escape:'htmlall':'UTF-8'} - {$item.body->currency_id |escape:'htmlall':'UTF-8'}  {$item.body->price |escape:'htmlall':'UTF-8'}
		</label>
		<br />
	{/foreach}

	{* idItems|@print_r *} 
	{* $items|@print_r *} 

	<input type="submit" value="{l s='Create CSV' mod='mercadolibre2prestashop'}" />
</form>