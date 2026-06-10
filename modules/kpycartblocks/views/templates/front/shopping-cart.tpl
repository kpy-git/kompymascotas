{$component = "shipping-progress"}

<div class="{$component}">
  <div class="{$component}__message">
    {if $remaining_amount > 0}
      {l s='You are %amount% € short of free shipping!' sprintf=['%amount%' => $remaining_amount] d='Modules.Kpycartblocks.Shop'}
    {else}
      {l s='Congrats, you have reached free shipping!' d='Modules.Kpycartblocks.Shop'}
    {/if}
  </div>

  <div class="{$component}__limit lower">{$limit_lower}€</div>

  <div class="{$component}__progress progress" role="progressbar" aria-label="Basic example" aria-valuenow="{$width}" aria-valuemin="0" aria-valuemax="100">
    <div class="progress-bar" style="width: {$width}%"></div>
  </div>

  <div class="{$component}__limit upper">{$limit_upper}€</div>
</div>