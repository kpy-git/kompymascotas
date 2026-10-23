{* Las tarjetas de marca de una página «por tipo»: cada marca con su rebaja. Parámetro: lista (claves). *}
<div class="kpl-marcas">
  {foreach $lista as $m}
    <a class="kpl-marca kpl-marca--{$m.tipo}" href="{$m.url}">
      <span class="kpl-et kpl-et--{$m.tipo}">{$m.nombre_tipo}</span>
      {if $m.logo}<img src="{$m.logo}" alt="{$m.marca}" loading="lazy" style="--kpl-logo-k:{$m.logo_k|default:1}">{else}<strong class="kpl-marca__nombre">{if $m.marca}{$m.marca}{else}{$m.titulo}{/if}</strong>{/if}
      <b>{if $m.tipo == 'segunda_unidad'}2ª ud. {/if}{if $m.pct > 0}−{$m.pct_texto} %{else}{$m.nombre_tipo}{/if}</b>
      <small>{if $kpl.mascota}{$m.n[$kpl.mascota]}{else}{$m.n.todos}{/if} productos{if $m.hasta_corta} · hasta el {$m.hasta_corta}{/if}{if $m.codigo} · código {$m.codigo}{/if}</small>
      <span class="kpl-marca__ir">{if $m.marca}{$m.marca}{else}Ver{/if} →</span>
    </a>
  {/foreach}
</div>
