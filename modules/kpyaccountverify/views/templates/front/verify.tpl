{extends file='page.tpl'}

{block name='breadcrumb'}{/block}

{block name='notifications'}{/block}

{block name='content_columns'}

<div class="verify">
    <div class="icon">✨</div>
    <h1>{l s="You're already part of this" d='Modules.Kpyaccountverify.Shop'}</h1>
    <p>{l s='Your email has been successfully verified. You can now enjoy all our benefits' d='Modules.Kpyaccountverify.Shop'}</p>
    <p>{l s='We are very excited to have you here.' d='Modules.Kpyaccountverify.Shop'}</p>
    <a href="{$urls.shop_domain_url}" class="btn btn-outline-primary">{l s='Explore the web' d='Modules.Kpyaccountverify.Shop'}</a>
</div>

{/block}