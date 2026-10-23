{**
 * La lista de promociones, arriba del todo y plegada: el resumen se ve siempre («3 en marcha ·
 * 1 programada») y el ojo la despliega. En cada promoción, «Productos» despliega sus productos
 * debajo de su fila, sin tocar el formulario: el JS los pide sin recargar la página; sin JS, el
 * enlace recarga con `&ver=ID` y salen igual.
 *}
<div class="panel kpc-lista-promos" id="kpc-promos">
  <div class="panel-heading kpc-cabeza">
    <button type="button" class="kpc-plegar" data-kpc-plegar="kpc-lista-cuerpo" aria-controls="kpc-lista-cuerpo" aria-expanded="{if $kpc.lista_abierta}true{else}false{/if}" title="Ver u ocultar las promociones">
      <i class="icon-eye"></i> <span>Promociones</span>
    </button>
    <span class="kpc-cuentas">
      {if $kpc.cuentas.en_marcha}<span class="kpc-chip kpc-chip--si">{$kpc.cuentas.en_marcha|intval} en marcha</span>{/if}
      {if $kpc.cuentas.programada}<span class="kpc-chip kpc-chip--info">{$kpc.cuentas.programada|intval} programada{if $kpc.cuentas.programada != 1}s{/if}</span>{/if}
      {if $kpc.cuentas.agotada}<span class="kpc-chip kpc-chip--ojo">{$kpc.cuentas.agotada|intval} agotada{if $kpc.cuentas.agotada != 1}s{/if}</span>{/if}
      {if $kpc.cuentas.apagada}<span class="kpc-chip">{$kpc.cuentas.apagada|intval} apagada{if $kpc.cuentas.apagada != 1}s{/if}</span>{/if}
      {if $kpc.cuentas.fuera}<span class="kpc-chip kpc-chip--gris">{$kpc.cuentas.fuera|intval} tanda{if $kpc.cuentas.fuera != 1}s{/if} de Special Price de fuera</span>{/if}
      {if !$kpc.lista}<small>Todavía no hay ninguna.</small>{/if}
    </span>
    <a class="btn btn-default btn-sm kpc-cabeza__nueva" href="{$kpc.url|escape:'html':'UTF-8'}&amp;actualizar=1" title="Vuelve a calcular a qué llega cada promoción (por si han entrado productos nuevos de una marca o ha cambiado un precio), y deja al día reglas, Special Price y etiquetas">Actualizar ahora</a>
  </div>

  <div class="kpc-lista-cuerpo" id="kpc-lista-cuerpo" {if !$kpc.lista_abierta}hidden{/if}>
    {if $kpc.lista}
      <label class="kpc-terminadas"><input type="checkbox" data-kpc-terminadas> Ver terminadas</label>
      <div class="kpc-tabla-caja">
        <table class="table kpc-tabla kpc-tabla--promos">
          <thead><tr><th>Estado</th><th>Promoción</th><th>Descuento</th><th>Fechas</th><th>A qué llega</th><th>Cómo llega</th><th>Google</th><th></th></tr></thead>
          <tbody>
            {foreach $kpc.lista as $c}
              <tr id="kpc-promo-{$c.id|intval}" data-kpc-estado="{$c.estado|escape:'html':'UTF-8'}" {if $c.estado === 'terminada' && $c.id !== $kpc.ver}hidden{/if} class="{if $c.id === $v.id || $c.id === $kpc.ver}kpc-actual{/if}">
                <td>
                  {if $c.estado === 'en_marcha'}<span class="kpc-chip kpc-chip--si">En marcha</span>
                  {elseif $c.estado === 'programada'}<span class="kpc-chip kpc-chip--info">Programada</span>
                  {elseif $c.estado === 'agotada'}<span class="kpc-chip kpc-chip--ojo" title="Se ha vendido todo su cupo: ya no está en la tienda">Agotada</span>
                  {elseif $c.estado === 'apagada'}<span class="kpc-chip">Apagada</span>
                  {else}<span class="kpc-chip kpc-chip--gris">Terminada</span>{/if}
                </td>
                <td><strong>{$c.codigo|escape:'html':'UTF-8'}</strong><br><small>{$c.nombre|escape:'html':'UTF-8'}</small></td>
                <td>
                  {if $c.tipo === 'segunda_unidad'}<span class="kpc-tipo-mini kpc-tipo-mini--segunda">2ª ud. −{$c.pct|escape:'html':'UTF-8'} %</span>
                  {elseif $c.tipo === 'special_price'}<span class="kpc-tipo-mini kpc-tipo-mini--{$c.etiqueta_clase|escape:'html':'UTF-8'}">{$c.etiqueta|escape:'html':'UTF-8'} {if $c.modo === 'pct'}−{$c.pct|escape:'html':'UTF-8'} %{elseif $c.modo === 'pct_articulo'}% por artículo{else}precio por artículo{/if}</span>
                  {elseif $c.tipo === 'regalo'}<span class="kpc-tipo-mini kpc-tipo-mini--regalo"><i class="icon-gift"></i> Regalo{if $c.regalo} · {$c.regalo|escape:'html':'UTF-8'}{/if}</span>
                  {else}<span class="kpc-tipo-mini kpc-tipo-mini--cupon">Cupón −{$c.pct|escape:'html':'UTF-8'} %</span>{/if}
                </td>
                <td>{$c.desde|default:'ya'|escape:'html':'UTF-8'} → {$c.hasta|default:'sin fin'|escape:'html':'UTF-8'}{if $c.dias}<br><small>{$c.dias|escape:'html':'UTF-8'}</small>{/if}
                  {if $c.cupo}<br><small class="kpc-cupo-mini" title="{$c.cupo_unidad|escape:'html':'UTF-8'}">Cupo {$c.cupo|intval}: <strong>{$c.vendidas|intval}</strong> vendidas{if $c.reservadas} · {$c.reservadas|intval} en cestas{/if}{if $c.estado !== 'agotada'} · quedan {$c.quedan|intval}{/if}{if $c.agotado} · agotada el {$c.agotado|escape:'html':'UTF-8'}{/if}</small>{elseif $c.cupo_lista}<br><small class="kpc-cupo-mini" title="Unidades por artículo, de la lista">Por artículo: {$c.uds_lista|intval} uds. en {$c.articulos_lista|intval} artículos: <strong>{$c.vendidas|intval}</strong> vendidas{if $c.reservadas} · {$c.reservadas|intval} en cestas{/if}{if $c.estado !== 'agotada'} · quedan {$c.quedan|intval}{/if}{if $c.articulos_agotados} · {$c.articulos_agotados|intval} agotados{/if}{if $c.agotado} · agotada el {$c.agotado|escape:'html':'UTF-8'}{/if}</small>{/if}
                </td>
                <td>{$c.ambito|escape:'html':'UTF-8'}<br><small>{$c.formatos|intval} artículo{if $c.formatos != 1}s{/if}{if $c.aviso} · {$c.aviso|escape:'html':'UTF-8'}{/if}</small></td>
                <td>
                  {if $c.tipo === 'segunda_unidad'}<small>sola, al llevar dos</small>
                  {elseif $c.tipo === 'regalo'}<small>tarjeta en la ficha, al precio habitual</small>
                  {elseif $c.tipo === 'special_price'}<small>en el precio</small>
                  {elseif $c.entra}
                    {foreach $c.entra as $e}<span class="kpc-chip kpc-chip--canal">{if $e === 'web'}Todos{elseif $e === 'shopping'}Google{elseif $e === 'email'}Email{elseif $e === 'social'}Redes{else}{$e|escape:'html':'UTF-8'}{/if}</span> {/foreach}
                  {else}<small>la pulsa el cliente</small>{/if}
                  {if !$c.en_ficha}<br><small>oculta en la ficha</small>{/if}
                </td>
                <td>{if $c.tipo === 'special_price'}<small>precio oferta</small>{elseif $c.en_merchant}✓{else}—{/if}</td>
                <td class="kpc-acciones">
                  <a class="btn btn-default btn-sm kpc-ver" href="{$kpc.url|escape:'html':'UTF-8'}&amp;ver={$c.id|intval}#kpc-promo-{$c.id|intval}" data-kpc-ver="{$c.id|intval}" data-kpc-fragmento="{$kpc.url|escape:'html':'UTF-8'}&amp;ajax=1&amp;action=productos&amp;id={$c.id|intval}" aria-expanded="{if $c.id === $kpc.ver}true{else}false{/if}" title="Ver sus productos aquí debajo"><i class="icon-eye"></i> Productos</a>
                  <a class="btn btn-default btn-sm" href="{$kpc.url|escape:'html':'UTF-8'}&amp;editar={$c.id|intval}#kpc-form" title="Abrirla en el formulario">Cambiar</a>
                  <a class="btn btn-default btn-sm" href="{$kpc.url|escape:'html':'UTF-8'}&amp;duplicar={$c.id|intval}#kpc-form" title="Una nueva con lo mismo, para cambiar el código y las fechas">Duplicar</a>
                  <a class="btn btn-default btn-sm" href="{$kpc.url|escape:'html':'UTF-8'}&amp;encender={$c.id|intval}">{if $c.estado === 'apagada'}Encender{else}Apagar{/if}</a>
                  {if $c.estado === 'agotada'}
                    <a class="btn btn-default btn-sm" href="{$kpc.url|escape:'html':'UTF-8'}&amp;reabrir={$c.id|intval}" data-kpc-confirmar="¿Reabrir {$c.codigo|escape:'html':'UTF-8'}? Vuelve a la tienda con el cupo que tenga; si sigue cubierto, se agota con el siguiente pedido. Para dar más unidades, mejor Cambiar y subir el cupo.">Reabrir</a>
                  {/if}
                  {if $c.tipo === 'cupon'}
                    <button type="button" class="btn btn-default btn-sm" data-kpc-copiar-texto="{$kpc.tienda|escape:'html':'UTF-8'}?{$kpc.parametro|escape:'html':'UTF-8'}={$c.codigo|escape:'html':'UTF-8'}" title="Copiar el enlace que deja puesto el cupón">Enlace</button>
                  {/if}
                  <a class="btn btn-default btn-sm kpc-borrar" href="{$kpc.url|escape:'html':'UTF-8'}&amp;borrar={$c.id|intval}" data-kpc-confirmar="¿Borrar {$c.codigo|escape:'html':'UTF-8'}? Se borra también su regla de carrito o sus Special Price. Si solo quieres pararla, mejor Apagar.">Borrar</a>
                </td>
              </tr>
              {if $c.id === $kpc.ver && $kpc.ver_previa}
                <tr class="kpc-sub" data-kpc-sub="{$c.id|intval}">
                  <td colspan="8">{include file=$kpc.plantillas|cat:'productos.tpl' pr=$kpc.ver_previa pv=$kpc.ver_v url=$kpc.url solo_ver=true}</td>
                </tr>
              {/if}
            {/foreach}
          </tbody>
        </table>
      </div>
    {else}
      <p class="kpc-vacio">Todavía no hay promociones: se crean aquí debajo.</p>
    {/if}

    {if $kpc.de_fuera}
      <h4 class="kpc-subtitulo">Special Price cargados fuera de esta pantalla <small>(Julián o SQL; se ven aquí para tenerlo todo junto, se cambian donde se cargaron)</small></h4>
      <div class="kpc-tabla-caja">
        <table class="table kpc-tabla">
          <thead><tr><th>Estado</th><th>Descuento</th><th>Fechas</th><th>Artículos</th><th>Marcas</th></tr></thead>
          <tbody>
            {foreach $kpc.de_fuera as $d}
              <tr>
                <td>{if $d.estado === 'en_marcha'}<span class="kpc-chip kpc-chip--si">En marcha</span>{else}<span class="kpc-chip kpc-chip--info">Programada</span>{/if}</td>
                <td><span class="kpc-tipo-mini kpc-tipo-mini--especial">Special Price −{$d.pct|escape:'html':'UTF-8'}</span></td>
                <td>{$d.desde|escape:'html':'UTF-8'} → {$d.hasta|escape:'html':'UTF-8'}</td>
                <td>{$d.n|intval}</td>
                <td><small>{$d.marcas|escape:'html':'UTF-8'}</small></td>
              </tr>
            {/foreach}
          </tbody>
        </table>
      </div>
    {/if}
  </div>
</div>
