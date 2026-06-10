{*
 * This file is part of the Gamifications module.
 *
 * @author    Sarunas Jonusas, <jonusas.sarunas@gmail.com>
 * @copyright Copyright (c) permanent, Sarunas Jonusas
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *}

{* en la parte de transformar los puntos, se muestra lo que se mostraba en la parte de loyalty (puntos gastads y actuales) *}

{*<a class="col-lg-4 col-md-6 col-sm-6 col-xs-12" id="history-link" href="{url entity='module' name='gamifications' controller='exchangepoints'}">
	<img src="img/perfil/puntos2.svg" alt="puntos" class="img-item">
    <span class="link-item">
        {l s='My points' mod='gamifications'}
    </span>
</a>
*}
{assign "exchange_url" {url entity='module' name='gamifications' controller='exchangepoints'}}
{assign "exchange_title" {l s='My points' mod='gamifications'}}
{assign "exchange_subtitle" {l s='Exchange your points for discounts and gifts' mod='gamifications'}}


{include file="components/account-grid-item.tpl"
    link=$exchange_url
    title=$exchange_title
    subtitle=$exchange_subtitle
    iconType='tpl'
    iconValue='components/svg-rewards.tpl'
}

{* en la parte de loyalty sólo se muestra el enlace de apadrinamiento, asi pararece que es una opcion distinta*}
{*<a class="col-lg-4 col-md-6 col-sm-6 col-xs-12" id="history-link" href="{url entity='module' name='gamifications' controller='loyality'}">
	<img src="img/perfil/amigo.svg" alt="amigo" class="img-item">
    <span class="link-item">
        {l s='Bring a friend' mod='gamifications'}
    </span>
</a>*}
{assign "loyality_url" {url entity='module' name='gamifications' controller='loyality'}}
{assign "loyality_title" {l s='Bring a friend' mod='gamifications'}}
{assign "loyality_subtitle" {l s='Get discounts by recommending us to your friends' mod='gamifications'}}

{include file="components/account-grid-item.tpl"
    link=$loyality_url
    title=$loyality_title
    subtitle=$loyality_subtitle
    iconType='tpl'
    iconValue='components/svg-friendship.tpl'
}