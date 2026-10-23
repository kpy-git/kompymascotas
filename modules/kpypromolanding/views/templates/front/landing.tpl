{**
 * /promociones: la portada de las promociones. Todo sale del resumen que dejó la sincronización.
 *}
{extends file='page.tpl'}

{block name='page_title'}{/block}

{block name='page_content_container'}
{if $kpl.fondo}<style>body.kpl-piel-negra,body.kpl-piel-negra #wrapper{literal}{{/literal}background:{$kpl.fondo}{literal}}{/literal}</style>{/if}
<section id="content" class="page-content kpl kpl--landing{if $kpl.piel} kpl--{$kpl.piel}{/if}{if $kpl.campana} kpl--campana kpl--campana-{$kpl.campana.estado}{/if}"{if $kpl.estilo} style="{$kpl.estilo}"{/if}>

  {* Una sola columna desde la 1.4.0: la tarjeta del boletín está fuera de la landing (ver el README). *}
  <div class="kpl-hero kpl-hero--solo">
    <div class="kpl-hero__txt">
      {if $kpl.campana}
        {assign var=c value=$kpl.campana}
        <span class="kpl-pill"><i></i> {$c.nombre}{if $c.fechas} · {$c.fechas|lower}{/if}</span>
        <h1>{if $c.titulo}{$c.titulo}{else}{$c.nombre}{/if}</h1>
        {if $c.estado == 'programada'}
          <p>{if $c.faltan_texto}<strong>{$c.faltan_texto}.</strong> {/if}{$c.texto_antes}</p>
        {elseif $c.estado == 'terminada'}
          <p>{$c.texto_despues}</p>
        {else}
          <p>{$c.texto}</p>
          {if $c.lema}<span class="kpl-lema">{$c.lema}{if $c.quedan_texto} · {$c.quedan_texto|lower}{/if}</span>{/if}
        {/if}
      {else}
        <span class="kpl-pill"><i></i> {if $kpl.n_total == 1}1 promoción activa ahora{else}{$kpl.n_total} promociones activas ahora{/if}</span>
        <h1>Ofertas, <em>regalos</em> y descuentos</h1>
        <p>Las mejores promos de las marcas que tu peludo adora, todas aquí. Cuando una empieza o termina, esta página cambia sola.</p>
      {/if}
    </div>
  </div>

  {include file='module:kpypromolanding/views/templates/front/_partials/quien.tpl'}

  {if $kpl.tipos}
    <section class="kpl-sec">
      <div class="kpl-sec__h"><div><span class="kpl-eyebrow">Elige tu promo</span><h2>¿Cómo quieres ahorrar?</h2><p>Solo salen los tipos que tienen algo este mes. Cada uno lleva a sus marcas.</p></div></div>
      {include file='module:kpypromolanding/views/templates/front/_partials/tipos.tpl' tipos=$kpl.tipos}
    </section>
  {/if}

  {if $kpl.banners}
    <section class="kpl-sec">
      <div class="kpl-sec__h"><div><span class="kpl-eyebrow">Marcas en promoción</span><h2>{if $kpl.campana && $kpl.campana.estado == 'viva'}{$kpl.campana.nombre}, marca a marca{else}Las promociones de ahora, marca a marca{/if}</h2></div></div>
      <div class="kpl-banners">
        {foreach $kpl.banners as $b}
          {include file='module:kpypromolanding/views/templates/front/_partials/banner.tpl' b=$b destacado=$b@first cta='Ver la promoción' lema=(($kpl.campana && $kpl.campana.estado == 'viva') ? $kpl.campana.lema : '')}
        {/foreach}
      </div>
    </section>
  {else}
    <section class="kpl-sec">
      <div class="kpl-vacio"><div class="kpl-vacio__ico">🐾</div><h2>Ahora mismo no hay promociones{if $kpl.mascota} para {if $kpl.mascota == 'perro'}perros{else}gatos{/if}{/if}</h2><p>Vuelve pronto: cuando una empieza, esta página cambia sola.{if $kpl.mascota} <a href="{$kpl.quien.todos}">Ver todas</a>.{/if}</p></div>
    </section>
  {/if}

  {if $kpl.top}
    <section class="kpl-sec">
      <div class="kpl-sec__h"><div><span class="kpl-eyebrow">Lo más rebajado</span><h2>Los productos con más descuento</h2></div><a class="btn btn-outline-primary" href="{$kpl.url_todas}{if $kpl.mascota}?mascota={$kpl.mascota}{/if}">Ver todo lo que está en promoción →</a></div>
      {include file='catalog/_partials/productlist.tpl' products=$kpl.top cssClass='kpl-grid'}
    </section>
  {/if}

  {if $kpl.accesos}
    <section class="kpl-sec kpl-accesos">
      <h2 class="kpl-accesos__h">También te puede interesar</h2>
      <div class="kpl-accesos__g">
        {foreach $kpl.accesos as $a}
          <a class="kpl-acceso" href="{$a.url}"><img src="{$a.img}" alt="" loading="lazy" width="200" height="140"><b>{$a.nombre}</b><span>{$a.sub}</span></a>
        {/foreach}
      </div>
    </section>
  {/if}

</section>
{/block}
