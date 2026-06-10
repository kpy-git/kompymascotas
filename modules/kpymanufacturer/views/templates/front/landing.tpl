{extends file='page.tpl'}

{block name='breadcrumb'}{/block}

{block name='page_title'}{/block}

{block name='content_columns'}
    {include file='module:kpymanufacturer/views/templates/front/_partials/header.tpl'}

    {include file='module:kpymanufacturer/views/templates/front/_partials/gamas.tpl'}

    {if $banners|count > 0}
        {include file='module:kpymanufacturer/views/templates/front/_partials/slider.tpl'}
    {/if}

    {include file='module:kpymanufacturer/views/templates/front/_partials/products.tpl'}

    {if $comments|count > 0}
        {include file='module:kpymanufacturer/views/templates/front/_partials/comments.tpl'}
    {/if}

    {if $videos|count > 0}
        {include file='module:kpymanufacturer/views/templates/front/_partials/videos.tpl'}
    {/if}

    {include file='module:kpymanufacturer/views/templates/front/_partials/footer.tpl'}
{/block}