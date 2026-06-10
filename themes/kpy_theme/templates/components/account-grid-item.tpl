<a href="{$link}">
  <div class="customer-account-grid__item">
    <div class="customer-account-grid__item-icon">
      {if $iconType=='tpl'}
        {include file=$iconValue classes="customer-account__icon"}
      {else}
        {$iconValue nofilter}
      {/if}
    </div>
    <div class="customer-account-grid__item-content">
      <h2>{$title}</h2>
      <p>{$subtitle}</p>
    </div>
    <div class="customer-account-grid__chevron-item">
      <i class="material-icons">chevron_right</i>
    </div>
  </div>
</a>