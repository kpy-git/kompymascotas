{*
 * This file is part of the Gamifications module.
 *
 * @author    Sarunas Jonusas, <jonusas.sarunas@gmail.com>
 * @copyright Copyright (c) permanent, Sarunas Jonusas
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *}
{if $possible_points > 1}
<div class="gamifications-product-points">
	<span><img class="gamifications-product-logo" src="{$urls.img_url}loyalty.svg" alt="puntos acumulables"></span>
	<span>{l s='Earn %points% points when you purchase this product' sprintf=['%points%' => $possible_points] mod='gamifications'}</span>
	<span>{include file="components/info-svg.tpl" classes="gamifications-product-info"}</span>
</div>

<dialog class="gamifications-product-dialog">
	
	<h4>{l s='Earn points with every purchase' mod='gamifications'}</h4>

	<div class="gamifications-product-dialog-body">
		<div class="gamifications-dialog__box">
			<div class="gamifications-dialog__ico">
				<img src="{$module_img}earn.svg" alt="acumula puntos">
			</div>
			<div>
				<p class="gamifications-dialog__title">{l s='Purchase and accumulate points' mod='gamifications'}</p>
				<p class="gamifications-dialog__subtitle">{l s='Earn 1 point for every €1 you spend when purchasing any of our products.' mod='gamifications'}</p>
			</div>
		</div>

		<div class="gamifications-dialog__box">
			<div class="gamifications-dialog__ico">
				<img src="{$module_img}gift.svg" alt="recompensas">
			</div>
			<div>
				<p class="gamifications-dialog__title">{l s='Receive rewards' mod='gamifications'}</p>
				<p class="gamifications-dialog__subtitle">{l s='Use your points to get discount vouchers and free products on your next purchases.' mod='gamifications'}</p>
			</div>
		</div>

		<div class="gamifications-dialog__box">
			<div class="gamifications-dialog__ico">
				<img src="{$module_img}manage.svg" alt="gestiona tus puntos">
			</div>
			<div>
				<p class="gamifications-dialog__title">{l s='Manage your points' mod='gamifications'}</p>
				<p class="gamifications-dialog__subtitle">{l s='You can view your accumulated points and redeem your rewards from your customer profile, in the section' mod='gamifications'} <a href="/module/gamifications/exchangepoints">{l s='My points' mod='gamifications'}</a></p>
			</div>
		</div>
	</div>

	<div class="dialog-footer text-end mt-4">
		<span class="btn btn-sm btn-primary close-dialog">{l s='Close' mod='gamifications'}</span>
	</div>

</dialog>

{/if}