{extends file='page.tpl'}

{block name='breadcrumb'}{/block}

{block name='notifications'}{/block}

{block name='content_columns'}
    <div class="page-header">
        <h1 class="text-center">{l s='Support center' d='Modules.Kpyfaq.Shop'}</h1>
        <div class="agent-contact">
            <a href="{$urls.pages.contact}" class="btn btn-outline-secondary">
                <span class="dot green"></span>
                {l s='Contact an agent' d='Modules.Kpyfaq.Shop'}
            </a>
        </div>
    </div>

    <div class="page-content">
        <h2>{l s='The questions everyone asks' d='Modules.Kpyfaq.Shop'}</h2>

        <section class="faq-frequently-questions">
            {foreach from=$frequently item=item}
                <a href="{$fc_url}/{$item.link_rewrite}" class="faq-frequently-question">
                    <span><i class="material-icons">arrow_right</i></span>
                    {$item.question}
                </a>
            {/foreach}
        </section>

        <h2 class="mt-5">{l s='All the information you need' d='Modules.Kpyfaq.Shop'}</h2>

        <section class="faq-sections">
            {foreach from=$sections item="section"}
                <div class="faq-section">
                    <h3 class="faq-section__title">{$section.title}</h3>
                    <ul class="faq-section__elements">
                        {foreach from=$section.elements item=element}
                            <li class="faq-section__element">
                                <a href="{$fc_url}/{$element.link_rewrite}">{$element.question}</a>
                                <span><i class="material-icons">chevron_right</i></span>
                            </li>
                        {/foreach}
                    </ul>
                </div>
            {/foreach}
        </section>
    </div>
{/block}

