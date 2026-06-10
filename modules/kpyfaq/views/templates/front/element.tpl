{extends file='page.tpl'}

{block name='breadcrumb'}{/block}

{block name='notifications'}{/block}

{block name='content_columns'}
    <div class="page-header element">
        <h1 class="text-center">{l s='Support center' d='Modules.Kpyfaq.Shop'}</h1>
    </div>

    <div class="page-content">
        <ol class="breadcrumb">
            {foreach from=$breadcrumb.links item=path}
                {if $path.url == $urls.pages.index}
                    <li class="breadcrumb-item home-breadcrumb">
                        <a href="{$path.url}" class="breadcrumb-link"><span><i class="material-icons">home</i></span></a>
                    </li>
                {else}
                    <li class="breadcrumb-item">
                        {if $path.url !== "#"}
                            <a href="{$path.url}" class="breadcrumb-link"><span>{$path.title}</span></a>
                        {else}
                            <span class="breadcrumb-link">{$path.title}</span>
                        {/if}
                    </li>
                {/if}
            {/foreach}
        </ol>

        <div class="grid-section my-4">
            {if !$modules.kpyfrontcontrollervariables.is_mobile }
                <div class="section">
                    <h2 class="section__title">{$section.title}</h2>
                    <ul class="section__elements">
                        {foreach from=$section.elements item=element}
                            <li {if $element.id == $current_element }class="active"{/if}>
                                <a href="{$fc_url}/{$element.link_rewrite}">{$element.question}</a>
                            <span><i class="material-icons">chevron_right</i></span>
                            </li>
                        {/foreach}
                    </ul>

                    <a href="{$urls.pages.contact}" class="btn btn-outline-secondary mt-3">
                        <span class="dot green"></span>
                        {l s='Contact an agent' d='Modules.Kpyfaq.Shop'}
                    </a>
                </div>
            {/if}

            <div class="element">
                <h1 class="element__title">{$element.question}</h1>
                <div class="element__content">{$element.answer nofilter}</div>
            </div>
        </div>
    </div>

{/block}