{* El bloque de la landing de una marca: todas sus promociones vivas (la general grande, las concretas pequeñas). Con campaña viva, su piel y su lema. *}
{assign var=cp value=$kpl.campana}
<section class="kpl kpl--marca{if $cp} kpl--campana kpl--{$cp.piel}{/if}"{if $cp && $cp.estilo} style="{$cp.estilo}"{/if}>
  <div class="container">
    <div class="kpl-sec__h">
      <div>
        <span class="kpl-eyebrow">{if $cp}{$cp.nombre}{if $cp.quedan_texto} · {$cp.quedan_texto|lower}{/if}{else}Promociones vigentes{/if}</span>
        <h2>{if $cp}{$cp.nombre} en {$kpl.marca}{else}Ahora en {$kpl.marca}{/if}</h2>
      </div>
      {if $cp}<a class="btn btn-outline-primary" href="{$cp.url}">Todo el {$cp.nombre} →</a>{/if}
    </div>
    <div class="kpl-marca-promos{if $kpl.promos|count == 1} kpl-marca-promos--una{/if}">
      {foreach $kpl.promos as $b}
        {include file='module:kpypromolanding/views/templates/front/_partials/banner.tpl' b=$b destacado=false ancho=($kpl.promos|count == 1) cta=(($b@first) ? 'Ver los productos' : 'Ver la promoción') lema=(($cp) ? $cp.lema : '')}
      {/foreach}
    </div>
  </div>
</section>
