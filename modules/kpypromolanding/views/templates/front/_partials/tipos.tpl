{* Las tarjetas de tipo: solo las que tienen algo (eso ya viene filtrado). *}
<div class="kpl-tipos">
  {foreach $tipos as $t}
    <a class="kpl-tipo kpl-tipo--{$t.tipo}" href="{$t.url}">
      <div class="kpl-tipo__txt">
        <span class="kpl-et kpl-et--{$t.tipo}">{$t.nombre}</span>
        <h3>{if $t.tipo == 'special_price'}Special Price{elseif $t.tipo == 'cupon'}Cupones con código{elseif $t.tipo == 'segunda_unidad'}2ª unidad al −20 / −30 %{elseif $t.tipo == 'kilos_gratis'}Kilos gratis{elseif $t.tipo == 'regalos'}Regalos{else}Outlet y liquidación{/if}</h3>
        <div class="kpl-tipo__n">{$t.n} productos · {if $t.tipo == 'special_price'}El precio ya rebajado en la ficha{elseif $t.tipo == 'cupon'}Se descuentan en la cesta{elseif $t.tipo == 'segunda_unidad'}Llévate dos y paga menos{elseif $t.tipo == 'kilos_gratis'}Sacos con kilos de regalo{elseif $t.tipo == 'regalos'}Un regalo con tu compra{else}Últimas unidades{/if}</div>
        <span class="kpl-tipo__cta">{if $t.tipo == 'kilos_gratis'}Ver sacos{elseif $t.tipo == 'regalos'}Ver regalos{elseif $t.tipo == 'outlet'}Ver últimas unidades{else}Ver marcas{/if} <i>→</i></span>
      </div>
      <div class="kpl-tipo__img">
        {if $t.imagen}<img src="{$t.imagen}" alt="" loading="lazy">{/if}
        {if $t.tipo == 'cupon' && $t.codigo}<span class="kpl-tipo__ticket">{$t.codigo}</span>{/if}
        {if $t.tipo == 'kilos_gratis'}<span class="kpl-tipo__ticket kpl-tipo__ticket--kg">kg gratis</span>{/if}
      </div>
    </a>
  {/foreach}
</div>
