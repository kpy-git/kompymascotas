{extends file="components/voucher.tpl"}

{block name='voucher-amount'}
    {if $reward_image != ''}
      <div class="cart-rule-gift">
        <div class="image-gift"><img src="{$reward_image}"></div>

        <div class="cart-rule-amount">{$value}</div>
      </div>
    {else}
      <div class="cart-rule-amount">{$value}</div>
    {/if}
{/block}

{block name='voucher-extra'}

  <div class="progress" role="progressbar" aria-valuenow="{$percentage}" aria-valuemin="0" aria-valuemax="100">
    <div class="progress-bar" style="width: {$percentage}%"></div>
  </div>
  <div class="progress-legend">
      {$customer_points}/{$reward_points}
  </div>


  <form method="post">
    <button type="submit" class="btn btn-primary float-end" name="exchange_points" {if $buttonStatus == 'disabled'}disabled{/if}>
        {l s='Canjear puntos' mod='gamifications'}
    </button>
    <input type="hidden" name="exchange_points" value="1">
    <input type="hidden" name="csrf_token" value="{$csrf_token}">
    <input type="hidden" name="id_point_exchange_reward" value="{$form_value}">
  </form>

{/block}