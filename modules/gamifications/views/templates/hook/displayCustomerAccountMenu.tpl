<a class="account-menu__line{if $exchange_active} active{/if}" href="{$exchange_url}">
    <span class="link-item">
        {include file="components/svg-rewards.tpl" classes="account-menu__icon"}
        {l s='My points' mod='gamifications'}
    </span>
</a>

<a class="account-menu__line{if $loyality_active} active{/if}" href="{$loyality_url}">
    <span class="link-item">
        {include file="components/svg-friendship.tpl" classes="account-menu__icon"}
        {l s='Bring a friend' mod='gamifications'}
    </span>
</a>