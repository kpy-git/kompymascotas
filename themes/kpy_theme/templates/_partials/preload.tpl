{$themeDir = _PS_THEME_DIR_}
{$preloadFilePath = "`$themeDir`assets/preload.html"}
{$assetsUrl = $urls.theme_assets}

{if file_exists($preloadFilePath)}
    {capture name="preloadBlock"}{include file=$preloadFilePath}{/capture}
    {$smarty.capture.preloadBlock|replace:'href="../':"href=\"$assetsUrl" nofilter}
{/if}

<link rel="preconnect" href="https://www.googletagmanager.com">
<link rel="preconnect" href="https://widgets.rr.skeepers.io">
<link rel="dns-prefetch" href="https://www.google-analytics.com">
<link rel="dns-prefetch" href="https://www.paypal.com">
<link rel="dns-prefetch" href="https://cdn.doofinder.com">
<link rel="dns-prefetch" href="https://cdn.connectif.cloud">