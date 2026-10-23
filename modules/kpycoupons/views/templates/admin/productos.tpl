{**
 * Los productos de una promoción: las cuentas, cómo se ha leído la lista y la tabla.
 *
 * Sale en dos sitios:
 *   - desplegado en la lista de promociones, para revisar (`$solo_ver`): sin «Quitar», con un botón
 *     para cambiarla;
 *   - debajo del formulario, al comprobar o al cambiar una promoción: con «Quitar» y «Volver a
 *     meter», que se aplican al guardar.
 *
 * Variables: `$pr` (KpcPantalla::previa), `$pv` (la promoción: id, tipo), `$url`, `$solo_ver`.
 *}
<div class="kpc-productos" data-kpc-productos-de="{$pv.id|intval}">
  <div class="kpc-cifras">
    <div class="kpc-cifra kpc-cifra--si"><strong>{$pr.articulos|intval}</strong><span>artículos entran</span></div>
    <div class="kpc-cifra"><strong>{$pr.productos|intval}</strong><span>productos</span></div>
    {if $pr.agotados}<div class="kpc-cifra kpc-cifra--ojo"><strong>{$pr.agotados|intval}</strong><span>ya vendieron sus unidades (fuera hasta reabrir)</span></div>{/if}
    {if $pr.especial}<div class="kpc-cifra kpc-cifra--ojo"><strong>{$pr.especial|intval}</strong><span>{if $pr.es_precio}ya con otro Special Price en esas fechas{else}fuera por Special Price{/if}</span></div>{/if}
    {foreach $pr.sin_precio as $motivo => $n}
      <div class="kpc-cifra kpc-cifra--ojo"><strong>{$n|intval}</strong><span>{$motivo|escape:'html':'UTF-8'}</span></div>
    {/foreach}
    {foreach $pr.otra as $codigo => $n}
      <div class="kpc-cifra kpc-cifra--ojo"><strong>{$n|intval}</strong><span>ya en {$codigo|escape:'html':'UTF-8'}, que descuenta más</span></div>
    {/foreach}
    {foreach $pr.quita as $codigo => $n}
      <div class="kpc-cifra kpc-cifra--info"><strong>{$n|intval}</strong><span>pasan de {$codigo|escape:'html':'UTF-8'} a esta ({if $pr.es_regalo}esta es más antigua{else}descuenta más{/if})</span></div>
    {/foreach}
    {if $pr.es_regalo}
      {if $pr.regalo.kilos}<div class="kpc-cifra kpc-cifra--info"><strong>{$pr.regalo.kilos|intval}</strong><span>regalan más de lo mismo («+ 3 kg gratis»; en la landing, «Kilos gratis»)</span></div>{/if}
      {if $pr.regalo.otros}<div class="kpc-cifra kpc-cifra--info"><strong>{$pr.regalo.otros|intval}</strong><span>regalan otra cosa («+ Regalo», «+ Snacks»…; en la landing, «Regalos»)</span></div>{/if}
      {if $pr.regalo.sin_regalo}<div class="kpc-cifra kpc-cifra--ojo"><strong>{$pr.regalo.sin_regalo|intval}</strong><span>sin regalo: sin formato pequeño en su ficha (ponles uno de reserva)</span></div>{/if}
      {if $pr.regalo.no_venta}<div class="kpc-cifra kpc-cifra--ojo"><strong>{$pr.regalo.no_venta|intval}</strong><span>con un regalo que no se vende</span></div>{/if}
      {if $pr.regalo.agotados}<div class="kpc-cifra kpc-cifra--ojo"><strong>{$pr.regalo.agotados|intval}</strong><span>con el regalo agotado ahora mismo</span></div>{/if}
      {if $pr.regalo.mismo}<div class="kpc-cifra kpc-cifra--ojo"><strong>{$pr.regalo.mismo|intval}</strong><span>regalan el mismo artículo que se compra</span></div>{/if}
      {if $pr.regalo.otra_marca}<div class="kpc-cifra kpc-cifra--info"><strong>{$pr.regalo.otra_marca|intval}</strong><span>con un regalo de otra marca (ojo al almacén)</span></div>{/if}
    {/if}
    {if $pr.fuera}<div class="kpc-cifra"><strong>{$pr.fuera|intval}</strong><span>quitados a mano o con «dejar fuera»</span></div>{/if}
  </div>
  {if $pr.momento}<p class="kpc-nota">Calculado para el {$pr.momento|escape:'html':'UTF-8'}, su primer día.</p>{/if}

  {foreach ['fichero' => 'Fichero', 'lista' => 'Lista', 'excluir' => 'Dejar fuera'] as $clave => $titulo}
    {if $pr[$clave]}
      {assign var=inf value=$pr[$clave]}
      <div class="kpc-leida {if $inf.no || $inf.sin_valor_n}kpc-leida--faltan{/if}">
        <p>
          <strong>{$titulo|escape:'html':'UTF-8'}{if $clave === 'fichero'} «{$inf.fichero|escape:'html':'UTF-8'}»{/if}:</strong>
          {$inf.filas|intval} línea{if $inf.filas != 1}s{/if}{if $clave === 'fichero'} (columna {$inf.columna|intval}{if $inf.columna_valor}, valor en la {$inf.columna_valor|intval}{/if}){/if}, leídas como {$inf.como|escape:'html':'UTF-8'}.
          <strong>{$inf.encontradas|intval}</strong> encontrada{if $inf.encontradas != 1}s{/if} → {$inf.articulos|intval} artículo{if $inf.articulos != 1}s{/if}.
          {if $inf.apagados}{$inf.apagados|intval} son de productos apagados u ocultos, o formatos apagados: no entran.{/if}
          {if $inf.no}<strong class="kpc-rojo">{$inf.no_n|intval} no encontradas.</strong>{/if}
          {if $inf.sin_valor_n}<strong class="kpc-rojo">{$inf.sin_valor_n|intval} sin un % o un precio que se entienda.</strong>{/if}
          {if $inf.columna_unidades}Unidades en la columna {$inf.columna_unidades|intval}.{/if}
          {if $inf.sin_unidades_n}<strong class="kpc-rojo">{$inf.sin_unidades_n|intval} sin unas unidades que se entiendan (entran solo con el cupo de la promoción).</strong>{/if}
        </p>
        {if $inf.no}
          <textarea class="form-control kpc-leida__no" rows="3" readonly id="kpc-no-{$clave|escape:'html':'UTF-8'}-{$pv.id|intval}">{$inf.no_texto|escape:'html':'UTF-8'}</textarea>
          <button type="button" class="btn btn-default btn-sm" data-kpc-copiar="#kpc-no-{$clave|escape:'html':'UTF-8'}-{$pv.id|intval}">Copiar las que faltan</button>
        {/if}
        {if $inf.sin_valor_n}
          <textarea class="form-control kpc-leida__no" rows="2" readonly>{$inf.sin_valor_texto|escape:'html':'UTF-8'}</textarea>
        {/if}
        {if $inf.sin_unidades_n}
          <textarea class="form-control kpc-leida__no" rows="2" readonly>{$inf.sin_unidades_texto|escape:'html':'UTF-8'}</textarea>
        {/if}
      </div>
    {/if}
  {/foreach}

  {if $pr.filas}
    <div class="kpc-herramientas">
      <input type="search" class="form-control kpc-herramientas__buscar" placeholder="Buscar: nombre, marca, formato, EAN o id…" data-kpc-filtrar aria-label="Buscar en los productos de la promoción">
      <span class="kpc-herramientas__cuenta" data-kpc-cuenta-filas>{$pr.filas_n|intval} filas</span>
      {if $solo_ver}
        <span class="kpc-herramientas__botones">
          <a class="btn btn-default btn-sm" href="{$url|escape:'html':'UTF-8'}&amp;editar={$pv.id|intval}#kpc-previa" title="Abrirla en el formulario para quitar productos o cambiar lo que sea">Quitar productos o cambiarla</a>
        </span>
      {else}
        <span class="kpc-herramientas__cambios" data-kpc-cambios hidden></span>
        <span class="kpc-herramientas__botones">
          <button type="submit" form="kpc-form" name="accion" value="comprobar" class="btn btn-default btn-sm">Comprobar</button>
          <button type="submit" form="kpc-form" name="accion" value="guardar" class="btn btn-primary btn-sm"><i class="icon-save"></i> Guardar</button>
        </span>
      {/if}
    </div>
    <div class="kpc-tabla-caja">
      <table class="table kpc-tabla kpc-tabla--previa">
        <thead><tr>
          <th>Id</th><th>EAN</th><th>Entra</th><th>Producto</th><th>Formato</th>
          {if $pv.cupo_lista}<th class="text-right">Unidades</th>{/if}
          <th class="text-right">{if $pr.es_precio}Precio normal{else}Precio{/if}</th>
          {if $pr.es_regalo}
            <th>Regalo</th>
            <th class="text-right">Vale</th>
          {else}
            <th class="text-right">Dto.</th>
            <th class="text-right">{if $pv.tipo === 'segunda_unidad'}2 uds. con la promo{else}Con la promo{/if}</th>
          {/if}
          {if !$solo_ver}<th></th>{/if}
        </tr></thead>
        <tbody>
          {foreach $pr.filas as $f}
            <tr class="kpc-fila-{$f.estado|escape:'html':'UTF-8'}" data-kpc-fila data-kpc-id="{$f.quitar|escape:'html':'UTF-8'}">
              <td><small>{$f.id|escape:'html':'UTF-8'}</small></td>
              <td><code>{$f.ean|escape:'html':'UTF-8'}</code></td>
              <td>
                {if $f.estado === 'entra'}<span class="kpc-chip kpc-chip--si">Entra</span>
                {elseif $f.estado === 'especial'}<span class="kpc-chip kpc-chip--ojo">{if $pr.es_precio}Ya tiene Special Price{else}Special Price{/if}</span>
                {elseif $f.estado === 'sinprecio'}<span class="kpc-chip kpc-chip--ojo" title="{$f.motivo|escape:'html':'UTF-8'}">No lo lleva</span>
                {elseif $f.estado === 'sinregalo'}<span class="kpc-chip kpc-chip--ojo" title="{$f.motivo|escape:'html':'UTF-8'}">Sin regalo</span>
                {elseif $f.estado === 'agotado'}<span class="kpc-chip kpc-chip--gris" title="Ya vendió sus unidades: fuera de la tienda hasta reabrir o darle más">Agotado</span>
                {elseif $f.estado === 'fuera'}<span class="kpc-chip kpc-chip--gris">Quitado</span>
                {else}<span class="kpc-chip kpc-chip--ojo">En {$f.motivo|escape:'html':'UTF-8'}</span>{/if}
              </td>
              <td><a href="{$f.enlace|escape:'html':'UTF-8'}" target="_blank" rel="noopener">{$f.nombre|escape:'html':'UTF-8'}</a><br><small>{$f.marca|escape:'html':'UTF-8'}{if $f.estado === 'sinprecio' || $f.estado === 'sinregalo'} · <span class="kpc-rojo">{$f.motivo|escape:'html':'UTF-8'}</span>{/if}</small></td>
              <td>{$f.formato|escape:'html':'UTF-8'}</td>
              {if $pv.cupo_lista}<td class="text-right">{if $f.unidades !== null}<strong>{$f.unidades|intval}</strong>{if $f.uds_vendidas || $f.estado === 'agotado'}<br><small class="kpc-gris">{$f.uds_vendidas|intval} vendidas{if $f.estado !== 'agotado'} · quedan {$f.uds_quedan|intval}{/if}</small>{/if}{else}<span class="kpc-gris" title="Sin unidades en la lista: solo cuenta el cupo de la promoción">—</span>{/if}</td>{/if}
              <td class="text-right">{$f.precio|escape:'html':'UTF-8'}</td>
              {if $pr.es_regalo}
                <td class="kpc-celda-regalo" data-kpc-celda-regalo="{$f.clave|intval}">
                  {if $f.estado === 'entra' && $f.regalo}
                    <span data-kpc-regalo-actual>
                      {if $f.regalo.n > 1}<strong>{$f.regalo.n|intval} ×</strong> {/if}<a href="{$f.regalo.enlace|escape:'html':'UTF-8'}" target="_blank" rel="noopener">{$f.regalo.texto|escape:'html':'UTF-8'}</a>
                      <br><small>{$f.regalo.origen|escape:'html':'UTF-8'} · stock {$f.regalo.stock|intval}{if $f.regalo.reserva} · reserva: {$f.regalo.reserva|escape:'html':'UTF-8'}{/if}{if isset($f.regalo.tarjeta)} · tarjeta «<strong>{$f.regalo.tarjeta|escape:'html':'UTF-8'}</strong>»{/if}</small>
                      {foreach $f.regalo.avisos as $a} <span class="kpc-chip kpc-chip--{$a.clase|escape:'html':'UTF-8'}">{$a.texto|escape:'html':'UTF-8'}</span>{/foreach}
                    </span>
                    <span class="kpc-regalo-nuevo" data-kpc-regalo-nuevo hidden></span>
                  {else}—{/if}
                </td>
                <td class="text-right">{if $f.estado === 'entra' && $f.regalo}<strong>{$f.con_promo|escape:'html':'UTF-8'}</strong>{else}—{/if}</td>
              {else}
                <td class="text-right">{if $f.estado === 'entra'}{$f.dto|escape:'html':'UTF-8'}{if $f.valor}<br><small class="kpc-gris">{$f.valor|escape:'html':'UTF-8'}</small>{/if}{/if}</td>
                <td class="text-right">{if $f.estado === 'entra'}<strong>{$f.con_promo|escape:'html':'UTF-8'}</strong>{else}—{/if}</td>
              {/if}
              {if !$solo_ver}
                <td class="text-right kpc-celda-botones">
                  {if $f.estado === 'fuera'}
                    <button type="button" class="btn btn-default btn-xs" data-kpc-meter="{$f.quitar|escape:'html':'UTF-8'}" data-kpc-ean="{$f.ean|escape:'html':'UTF-8'}">Volver a meter</button>
                  {else}
                    {if $pr.es_regalo && ($f.estado === 'entra' || $f.estado === 'sinregalo')}
                      <button type="button" class="btn btn-default btn-xs" data-kpc-cambiar-regalo="{$f.clave|intval}" title="Elegir otro artículo como regalo solo para esta fila">{if $f.estado === 'sinregalo'}Ponerle regalo{else}Cambiar regalo{/if}</button>
                    {/if}
                    <button type="button" class="btn btn-default btn-xs kpc-quitar" data-kpc-quitar="{$f.quitar|escape:'html':'UTF-8'}">Quitar</button>
                  {/if}
                </td>
              {/if}
            </tr>
          {/foreach}
        </tbody>
      </table>
    </div>
    {if $pr.recortada}<p class="kpc-nota">Se enseñan {$pr.filas_n|intval} de {$pr.total|intval}. Las cuentas de arriba son de todos.</p>{/if}
    {if !$solo_ver}<p class="kpc-nota">«Quitar» lo apunta en «Dejar fuera» (arriba) y se aplica al <strong>Guardar</strong>. «Volver a meter» lo borra de ahí.{if $pr.es_regalo} «Cambiar regalo» elige otro artículo solo para esa fila (se queda aunque cambies el modo), y se aplica al Guardar.{/if}</p>{/if}
  {else}
    <p class="kpc-vacio">No llega a ningún artículo. Revisa lo elegido: todo se cruza, y quizá no hay nada que cumpla todo a la vez.</p>
  {/if}
</div>
