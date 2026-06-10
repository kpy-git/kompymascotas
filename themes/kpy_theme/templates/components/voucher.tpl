<div class="cart-rule-grid {if isset($classes)}{$classes}{/if}">
  <div class="cart-rule-code">
      {$code}
  </div>
  <div class="cart-rule-content">
    {block name='voucher-name'}
      <div class="cart-rule-name">{$name}</div>
    {/block}

    {block name='voucher-amount'}
      <div class="cart-rule-amount">{$value}</div>
    {/block}

    {block name='voucher-extra'}
      <div class="cart-rule-expiration">{l s='Valid until %date%' sprintf=['%date%' => $voucher_date] d='Shop.Theme.Checkout'}</div>
    {/block}
  </div>
</div>