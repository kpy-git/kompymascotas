{* La cabecera de las páginas con listado, según el modo: tipo, promo o todas. Con una campaña viva
   (`$kpl.campana`), las etiquetas ya vienen con su nombre («Black Friday») y el lema de la campaña
   acompaña a lo que entra en ella. *}
{if $kpl.modo == 'promo'}
  {assign var=c value=$kpl.c}
  {if $kpl.estado == 'viva'}
    <div class="kpl-pcab kpl-pcab--{$c.tipo}">
      <div class="kpl-pcab__txt">
        <div class="kpl-banner__top">
          {if $c.logo}<img class="kpl-banner__logo" src="{$c.logo}" alt="{$c.marca}" style="--kpl-logo-k:{$c.logo_k|default:1}">{elseif $c.marca}<strong class="kpl-banner__marca">{$c.marca}</strong>{/if}
          <span class="kpl-et kpl-et--{$c.tipo}">{$c.nombre_tipo}</span>
        </div>
        <h1>{$c.titulo}</h1>
        {if $kpl.duracion.texto}<div class="kpl-dur">⏱ {$kpl.duracion.texto}{if $kpl.duracion.quedan} · <i>{$kpl.duracion.quedan}</i>{/if}</div>{/if}
        {if $kpl.en_campana && $kpl.campana && $kpl.campana.lema}<span class="kpl-lema">{$kpl.campana.lema}</span>{/if}
        <div class="kpl-pcab__meta">
          <span>{if $c.n.todos == 1}1 producto{else}{$c.n.todos} productos{/if}</span>
          <span>{$c.texto}</span>
        </div>
        <div class="kpl-pcab__btns">
          {if $kpl.cupon}
            {if $kpl.cupon_aplicado}
              <span class="btn kpl-btn--ok">✓ Cupón {$kpl.cupon} aplicado · se descuenta en la cesta</span>
            {else}
              <a class="btn btn-primary" href="{$kpl.url_cupon}" rel="nofollow">Aplicar cupón {$kpl.cupon}</a>
            {/if}
          {/if}
          {if $c.marca_url}<a class="kpl-pcab__lnk" href="{$c.marca_url}">Ver toda la marca →</a>{/if}
        </div>
      </div>
      <div class="kpl-pcab__img">
        {if $c.imagenes}<div class="kpl-pila{if $c.lado} kpl-pila--lado{/if}">{foreach $c.imagenes as $im}<img src="{$im}" alt="" fetchpriority="high">{/foreach}</div>{/if}
      </div>
    </div>
    {if $kpl.con_chips}
      <div class="kpl-quien kpl-quien--chips">
        <span class="kpl-quien__l">¿Para quién?</span>
        <nav class="kpl-seg" aria-label="Elige la mascota">
          <a href="{$kpl.quien.todos}" class="{if $kpl.mascota == ''}on{/if}">Todos ({$c.n.todos})</a>
          <a href="{$kpl.quien.perro}" class="{if $kpl.mascota == 'perro'}on{/if}"><img src="{$urls.base_url}modules/kpypromolanding/views/img/dog-face.svg" alt="" width="22" height="22">Perros ({$c.n.perro})</a>
          <a href="{$kpl.quien.gato}" class="{if $kpl.mascota == 'gato'}on{/if}"><img src="{$urls.base_url}modules/kpypromolanding/views/img/cat-face.svg" alt="" width="22" height="22">Gatos ({$c.n.gato})</a>
        </nav>
      </div>
    {elseif $c.mascota}
      <p class="kpl-solo">Solo {if $c.mascota == 'perro'}perros{else}gatos{/if} · {if $c.n.todos == 1}1 producto{else}{$c.n.todos} productos{/if}</p>
    {/if}
  {else}
    <h1 class="kpl-h1--oculto">{$c.titulo}</h1>
  {/if}

{elseif $kpl.modo == 'tipo'}
  <div class="kpl-tcab">
    <span class="kpl-et kpl-et--{$kpl.tipo}">{$kpl.nombre_tipo}</span>
    <h1>{$kpl.titulo}</h1>
    <p>{$kpl.texto}</p>
    {if $kpl.en_campana && $kpl.campana && $kpl.campana.lema}<span class="kpl-lema">{$kpl.campana.lema}{if $kpl.campana.quedan_texto} · {$kpl.campana.quedan_texto|lower}{/if}</span>{/if}
  </div>
  {include file='module:kpypromolanding/views/templates/front/_partials/quien.tpl'}
  {if $kpl.filtros_url}<p class="kpl-filtros"><a class="btn btn-outline-primary" href="{$kpl.filtros_url}">Filtrar por marca, precio o mascota →</a></p>{/if}
  {if $kpl.marcas}
    <div class="kpl-sec__h"><div><span class="kpl-eyebrow">Marcas</span><h2>{$kpl.h2_marcas}</h2></div></div>
    {include file='module:kpypromolanding/views/templates/front/_partials/marcas.tpl' lista=$kpl.marcas}
    <div class="kpl-sec__h kpl-sec__h--lista"><div><span class="kpl-eyebrow">O míralo todo</span><h2>{$kpl.h2_productos}</h2></div></div>
  {/if}

{else}
  <div class="kpl-tcab">
    <h1>{$kpl.titulo}</h1>
    <p>{$kpl.texto}</p>
    {if $kpl.en_campana && $kpl.campana && $kpl.campana.lema}<span class="kpl-lema">{$kpl.campana.lema}{if $kpl.campana.quedan_texto} · {$kpl.campana.quedan_texto|lower}{/if}</span>{/if}
  </div>
  {include file='module:kpypromolanding/views/templates/front/_partials/quien.tpl'}
{/if}
