{* Un banner de promoción: texto y colores en HTML, logo y sacos sin fondo. Parámetros: b (la clave), destacado, ancho (solo, a todo el ancho), cta. *}
<div class="kpl-banner kpl-banner--{$b.tipo}{if isset($destacado) && $destacado} kpl-banner--destacado{/if}{if isset($ancho) && $ancho} kpl-banner--ancho{/if}">
  <div class="kpl-banner__txt">
    <div class="kpl-banner__top">
      {if $b.logo}<img class="kpl-banner__logo{if isset($destacado) && $destacado} kpl-banner__logo--caja{/if}" src="{$b.logo}" alt="{$b.marca}" loading="lazy" style="--kpl-logo-k:{$b.logo_k|default:1}">{elseif $b.marca}<strong class="kpl-banner__marca">{$b.marca}</strong>{/if}
      <span class="kpl-et kpl-et--{$b.tipo}">{$b.nombre_tipo}</span>
    </div>
    <h3>{$b.titulo}</h3>
    {if $b.texto}<p>{$b.texto}</p>{/if}
    <div class="kpl-banner__meta">
      <span>{$b.hasta_texto}</span>
      <span>{if $b.n.todos == 1}1 producto{else}{$b.n.todos} productos{/if}</span>
      {if isset($lema) && $lema}<span class="kpl-banner__lema">{$lema}</span>{/if}
    </div>
    <div class="kpl-banner__btns">
      <a class="btn btn-primary" href="{$b.url}">{if isset($cta) && $cta}{$cta}{else}Ver la promoción{/if}</a>
      {if $b.url_cupon}<a class="btn btn-outline-primary" href="{$b.url_cupon}" rel="nofollow">Aplicar cupón {$b.codigo}</a>{/if}
    </div>
  </div>
  <div class="kpl-banner__img">
    {if $b.imagenes}
      <div class="kpl-pila{if $b.lado} kpl-pila--lado{/if}">
        {foreach $b.imagenes as $im}<img src="{$im}" alt="" loading="lazy">{/foreach}
      </div>
    {/if}
  </div>
</div>
