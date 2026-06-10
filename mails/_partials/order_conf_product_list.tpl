{**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 *}
{foreach $list as $product}
	<div class="product-item">
		<div class="product-image">
			<img src="{$product['image']}" alt="">
		</div>
		<div class="product-details">
			<div class="product-name">{$product['name']}</div>
			<div class="product-meta">Cantidad: {$product['quantity']}</div>
			<div class="product-pricing">
				<span class="product-unit-price">{$product['unit_price']} / ud.</span>
				<span class="product-total-price">{$product['price']}</span>
			</div>
		</div>
	</div>
{/foreach}

