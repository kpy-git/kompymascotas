{**
 * Kompy · El bloque de promociones de la ficha, bajo el precio (hook `displayProductPriceBlock`,
 * type=weight, product_sheet; ver `KpcFicha`). Lo pinta el servidor y PrestaShop lo repinta entero
 * en cada refresco (formato, cantidad, cupón, opción del regalo): aquí no hay estado en el navegador.
 *
 * La tarjeta «12 kg + 3 kg gratis» la monta `ficha.js` en la fila de formatos del tema con los datos
 * de `data-kpc-datos`; el campo oculto `kpy_regalo` (0|1) es el que viaja en el refresco y al añadir a
 * la cesta (`KpcRegalo::antesDelCambio` lo lee). Lo que se dice con la tarjeta elegida (la pastilla
 * «Kilos gratis», «Incluye…», hasta cuándo) sale de aquí.
 *}
<div class="kpc-ficha{if $kpc.tipo} kpc-ficha--{$kpc.tipo}{/if}" data-kpc-ficha data-kpc-estado="{$kpc.estado}" data-kpc-regalo="{$kpc.regalo_elegido}"{if $kpc.kg} data-kpc-kg="1"{/if}{if $kpc.campana} data-kpc-campana="{$kpc.campana.clave}" data-kpc-piel="{$kpc.campana.piel}"{if $kpc.campana.estilo} style="{$kpc.campana.estilo}"{/if}{/if}>
  {if $kpc.kg}
    <p class="kpc-kg">{$kpc.kg}</p>
  {/if}
  {if $kpc.quedan.texto}
    <p class="kpc-quedan kpc-quedan--{$kpc.quedan.clase}{if $kpc.quedan.pocas} is-pocas{/if}{if $kpc.quedan.agotado} is-agotado{/if}">{$kpc.quedan.texto}</p>
  {/if}
  {if $kpc.tarjeta}
    <input type="hidden" name="kpy_regalo" value="{$kpc.regalo_elegido}" data-kpc-regalo-campo>
  {/if}
  {if $kpc.regalo && $kpc.regalo.elegida}
    <span class="kpc-pill kpc-pill--{$kpc.regalo.clase}" data-kpc-pill>{$kpc.regalo.pill}</span>
    <div class="kpc-incluye">
      {if $kpc.regalo.imagen}<img class="kpc-incluye__foto" src="{$kpc.regalo.imagen}" alt="" width="48" height="48" loading="lazy" decoding="async">{/if}
      <span class="kpc-incluye__texto">
        <strong>{$kpc.regalo.incluye}</strong>
        {if $kpc.regalo.fin}<span class="kpc-fin">{$kpc.regalo.fin}</span>{/if}
        {if $kpc.regalo.quedan}<span class="kpc-quedan-regalos">{$kpc.regalo.quedan}</span>{/if}
      </span>
    </div>
  {/if}
  {if $kpc.cupon}
    {if $kpc.cupon.entra}
      <button type="button" class="kpc-cupon{if $kpc.cupon.aplicado} is-aplicado{/if}{if $kpc.cupon.auto} is-auto{/if}" data-kpc-cupon="{$kpc.cupon.codigo}" data-kpc-accion="{if $kpc.cupon.aplicado}quitar{else}poner{/if}" data-kpc-url="{$kpc.cupon.url}" data-kpc-aplicando="{$kpc.textos.aplicando}" aria-pressed="{if $kpc.cupon.aplicado}true{else}false{/if}">
        <span class="kpc-cupon__marca" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"></path></svg></span>
        <span class="kpc-cupon__texto">
          <span class="kpc-cupon__titulo">{$kpc.cupon.titulo}</span>
          <span class="kpc-cupon__sub">{$kpc.cupon.sub}</span>
        </span>
        {if $kpc.cupon.aplicado}
          <span class="kpc-cupon__quitar">{$kpc.textos.quitar}</span>
        {else}
          <span class="kpc-cupon__codigo">{$kpc.cupon.codigo}</span>
        {/if}
      </button>
    {else}
      <p class="kpc-nota kpc-nota--cupon">{$kpc.cupon.para}</p>
    {/if}
  {/if}
  {if $kpc.segunda}
    {if $kpc.segunda.entra}
      {if $kpc.segunda.pulsable}
        <button type="button" class="kpc-segunda is-pulsable" data-kpc-segunda-mas>
      {else}
        <div class="kpc-segunda{if $kpc.segunda.puesta} is-puesta{/if}">
      {/if}
          <span class="kpc-segunda__marca" aria-hidden="true">{if $kpc.segunda.puesta}<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"></path></svg>{else}2ª{/if}</span>
          <span class="kpc-segunda__texto">
            <span class="kpc-segunda__titulo">{$kpc.segunda.titulo}</span>
            <span class="kpc-segunda__sub">{$kpc.segunda.texto}</span>
          </span>
      {if $kpc.segunda.pulsable}
        </button>
      {else}
        </div>
      {/if}
    {else}
      <p class="kpc-nota kpc-nota--segunda">{$kpc.segunda.para}</p>
    {/if}
  {/if}
  {if $kpc.aviso}
    <p class="kpc-nota kpc-nota--aviso">{$kpc.aviso}</p>
  {/if}
  <script type="application/json" data-kpc-datos>{$kpc.datos nofilter}</script>
</div>
