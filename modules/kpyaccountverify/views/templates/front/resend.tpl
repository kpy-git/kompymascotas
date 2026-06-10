{extends file='page.tpl'}

{block name='breadcrumb'}{/block}

{block name='notifications'}{/block}

{block name='content_columns'}
    <div class="account-verify-resend-message">
        <p>{l s='We have sent a new email to verify your account to the following address:' d='Modules.Kpyaccountverify.Shop'}</p>

        <p class="email">{$customer_email}</p>

        <div class="alert alert-info">
            💡 {l s='Note: If you do not receive any emails, check your spam folder and make sure that the email address you are using is correct. ' d='Modules.Kpyaccountverify.Shop'}
        </div>
    </div>
{/block}