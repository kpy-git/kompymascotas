{**
 * Kompy · Acuerdos con proveedores. Ordenada como se usa: cómo funciona (y cómo está), los
 * acuerdos (con el detalle o las ventas del que se pida debajo) y subir una lista (con su
 * comprobación debajo). Misma pinta que Kompy · Promociones y la landing.
 *}
<div class="kps-admin">
  {if $kps.aviso}<div class="alert alert-success">{$kps.aviso|escape:'html':'UTF-8'}</div>{/if}
  {if $kps.error}<div class="alert alert-danger">{$kps.error|escape:'html':'UTF-8'}</div>{/if}

  {* 0 · Cómo funciona y cómo está *}
  <div class="panel kps-panel">
    <div class="panel-heading kps-cabeza"><i class="icon-list-ul"></i> Acuerdos con proveedores <span class="badge">v{$kps.version|escape:'html':'UTF-8'}</span>
      <span class="kps-cabeza__btns">
        <a class="btn btn-primary btn-sm" href="#subir"><i class="icon-upload"></i> Subir una lista</a>
        <a class="btn btn-default btn-sm" href="{$kps.url_promociones|escape:'html':'UTF-8'}"><i class="icon-tags"></i> Kompy · Promociones</a>
      </span>
    </div>
    <div class="kps-flujo">
      <div class="kps-flujo__paso">
        <span class="kps-num">1</span>
        <strong>El proveedor dice qué nos da</strong>
        <p>Una <b>redención</b> (al acabar paga un % de lo vendido sin IVA), un <b>descuento en factura</b> mientras dura (un % sobre el coste neto, por gama) o <b>precios de coste nuevos</b> por EAN, para un periodo o hasta nueva orden.</p>
      </div>
      <div class="kps-flujo__flecha" aria-hidden="true">→</div>
      <div class="kps-flujo__paso kps-flujo__paso--auto">
        <span class="kps-num">2</span>
        <strong>Se apunta por artículo y con fechas</strong>
        <p>En la promoción (Kompy · Promociones, paso «Acuerdo con el proveedor»: sus artículos y sus fechas, y la sigue) o aquí, <b>pegando la lista</b> del proveedor: <b>Comprobar</b> dice qué fila es qué artículo, cuántas se han encontrado y cuáles no.</p>
      </div>
      <div class="kps-flujo__flecha" aria-hidden="true">→</div>
      <div class="kps-flujo__paso">
        <span class="kps-num">3</span>
        <strong>Administración consulta y se baja el Excel</strong>
        <p>De cada acuerdo, <b>la lista</b> (para contrastar facturas) y <b>las ventas del periodo</b>: unidades y valor sin IVA de verdad por artículo y, en una redención, lo que toca cobrar.</p>
      </div>
    </div>
    <div class="kps-cifras">
      <div class="kps-cifra kps-cifra--si"><strong>{$kps.cuentas.vivo|intval}</strong><span>{if $kps.cuentas.vivo == 1}acuerdo en marcha{else}acuerdos en marcha{/if}</span></div>
      <div class="kps-cifra kps-cifra--info"><strong>{$kps.cuentas.articulos_vivos|intval}</strong><span>{if $kps.cuentas.articulos_vivos == 1}artículo con acuerdo hoy{else}artículos con acuerdo hoy{/if}</span></div>
      <div class="kps-cifra"><strong>{$kps.cuentas.programado|intval}</strong><span>{if $kps.cuentas.programado == 1}programado{else}programados{/if}</span></div>
      <div class="kps-cifra"><strong>{$kps.cuentas.terminado|intval}</strong><span>{if $kps.cuentas.terminado == 1}terminado{else}terminados{/if}</span></div>
      {if $kps.cuentas.solapes}<div class="kps-cifra kps-cifra--ojo"><strong>{$kps.cuentas.solapes|intval}</strong><span>acuerdos con artículos que se cruzan con otro</span></div>{/if}
    </div>
  </div>

  {* 1 · Los acuerdos *}
  <div class="panel kps-panel" id="acuerdos">
    <div class="panel-heading kps-cabeza"><i class="icon-list-ul"></i> Los acuerdos
      <span class="kps-cuentas"><span class="kps-chip kps-chip--si">{$kps.cuentas.vivo|intval} en marcha</span> <span class="kps-chip kps-chip--info">{$kps.cuentas.programado|intval} {if $kps.cuentas.programado == 1}programado{else}programados{/if}</span> <span class="kps-chip kps-chip--gris">{$kps.cuentas.terminado|intval} {if $kps.cuentas.terminado == 1}terminado{else}terminados{/if}</span></span>
      {if $kps.promo_filtro}<span class="kps-cabeza__btns"><span class="kps-chip kps-chip--info">solo los de la promoción #{$kps.promo_filtro|intval}</span> <a class="btn btn-default btn-sm" href="{$kps.url|escape:'html':'UTF-8'}#acuerdos">Ver todos</a></span>{/if}
    </div>
    <div class="kps-cuerpo">
      <p class="kps-ayuda">Un acuerdo es <b>un tipo, unas fechas y una marca</b> (y su promoción, si viene de una). Las filas son por artículo: un producto de dos acuerdos distintos sale en los dos. <b>Artículos</b> enseña los suyos (y deja quitar uno, prolongar, cerrar o borrar); <b>Ventas</b> saca lo vendido en su periodo, con su Excel; <b>Excel</b> baja la lista del acuerdo tal cual, para administración. Un acuerdo terminado se queda aquí: nada se borra solo.</p>
      {if !$kps.lotes}
        <p class="kps-gris">Todavía no hay ningún acuerdo. Se meten en cada promoción (paso «Acuerdo con el proveedor») o <a href="#subir">subiendo una lista</a>.</p>
      {else}
        <div class="kps-tabla-caja">
          <table class="table kps-tabla">
            <thead><tr><th>Acuerdo</th><th>Marca</th><th class="kps-num-col">Artículos</th><th>Fechas</th><th>Promoción</th><th></th></tr></thead>
            <tbody>
            {foreach $kps.lotes as $l}
              {if !$kps.promo_filtro || $l.id_kpy_coupon == $kps.promo_filtro}
              <tr class="kps-fila--{$l.estado|escape:'html':'UTF-8'}{if $kps.promo_filtro && $l.id_kpy_coupon == $kps.promo_filtro} kps-actual{/if}">
                <td class="kps-nowrap"><span class="kps-tipo kps-tipo--{$l.tipo|escape:'html':'UTF-8'}">{$l.tipo_titulo|escape:'html':'UTF-8'}</span><small>{$l.valor_texto|escape:'html':'UTF-8'}</small></td>
                <td class="kps-nowrap">{if $l.marca}{$l.marca|escape:'html':'UTF-8'}{else}<span class="kps-gris">sin marca</span>{/if}</td>
                <td class="kps-num-col">{$l.n|intval}{if $l.promo && $l.n_lista}<small>{$l.n_lista|intval} por lista</small>{/if}{if $l.solapes}<small><span class="kps-chip kps-chip--ojo" title="Artículos de este acuerdo que tienen otro acuerdo que se cruza en fechas">{$l.solapes|intval} con otro</span></small>{/if}</td>
                <td class="kps-nowrap">{$l.fechas_corto|escape:'html':'UTF-8'}
                  <small>{if $l.estado == 'vivo'}<span class="kps-chip kps-chip--si">{if $l.sin_fin}hasta nueva orden{else}en marcha{/if}</span>{elseif $l.estado == 'programado'}<span class="kps-chip kps-chip--info">programado{if $l.sin_fin} · sin fin{/if}</span>{else}<span class="kps-chip kps-chip--gris">terminado</span>{/if}</small>
                </td>
                <td class="kps-promo-celda">
                  {if $l.promo}
                    {if $l.promo.borrada}<span class="kps-gris">{$l.promo.codigo|escape:'html':'UTF-8'}</span><small>promoción borrada</small>
                    {else}<a href="{$kps.url_promociones|escape:'html':'UTF-8'}&amp;editar={$l.id_kpy_coupon|intval}">{$l.promo.codigo|escape:'html':'UTF-8'}</a><small title="{$l.promo.nombre|escape:'html':'UTF-8'}">{$l.promo.nombre|escape:'html':'UTF-8'}{if !$l.promo.activo} · apagada{/if}</small>{/if}
                  {else}<span class="kps-gris">suelto</span>{/if}
                </td>
                <td class="kps-acciones">
                  <a class="btn btn-default btn-xs" href="{$kps.url|escape:'html':'UTF-8'}&amp;ver={$l.clave|escape:'html':'UTF-8'}#detalle" title="Los artículos del acuerdo"><i class="icon-eye"></i> Artículos</a>
                  <a class="btn btn-default btn-xs" href="{$kps.url|escape:'html':'UTF-8'}&amp;informe={$l.clave|escape:'html':'UTF-8'}#detalle" title="Lo vendido en su periodo"><i class="icon-search"></i> Ventas</a>
                  <a class="btn btn-default btn-xs" href="{$kps.url|escape:'html':'UTF-8'}&amp;exportar=lista&amp;lote={$l.clave|escape:'html':'UTF-8'}" title="La lista del acuerdo, a Excel"><i class="icon-save"></i> Excel</a>
                </td>
              </tr>
              {/if}
            {/foreach}
            </tbody>
          </table>
        </div>
      {/if}

      {* El detalle de un acuerdo: sus artículos *}
      {if $kps.ver}
        {assign var=v value=$kps.ver}
        <div class="kps-detalle" id="detalle">
          <div class="kps-detalle__cabeza">
            <h3><span class="kps-tipo kps-tipo--{$v.tipo|escape:'html':'UTF-8'}">{$v.tipo_titulo|escape:'html':'UTF-8'}</span> {if $v.marca}{$v.marca|escape:'html':'UTF-8'} · {/if}{$v.valor_texto|escape:'html':'UTF-8'} · {$v.fechas|escape:'html':'UTF-8'}</h3>
            {if $v.promo}<span class="kps-chip kps-chip--info">de la promoción {$v.promo.codigo|escape:'html':'UTF-8'}</span>{/if}
            <span class="kps-cabeza__btns">
              <a class="btn btn-default btn-sm" href="{$kps.url|escape:'html':'UTF-8'}&amp;informe={$v.clave|escape:'html':'UTF-8'}#detalle"><i class="icon-search"></i> Ventas del periodo</a>
              <a class="btn btn-default btn-sm" href="{$kps.url|escape:'html':'UTF-8'}&amp;exportar=lista&amp;lote={$v.clave|escape:'html':'UTF-8'}"><i class="icon-save"></i> Lista a Excel</a>
              <a class="btn btn-default btn-sm" href="{$kps.url|escape:'html':'UTF-8'}#acuerdos">Cerrar</a>
            </span>
          </div>
          <div class="kps-detalle__cuerpo">
            {if !$v.filas}
              <p class="kps-gris">Este acuerdo ya no tiene artículos.</p>
            {else}
              <div class="kps-tabla-caja">
                <table class="table kps-tabla">
                  <thead><tr><th>Artículo</th><th>EAN</th><th>Referencia</th><th>{if $v.tipo == $kps.tipo_coste}Precio de coste nuevo{else}Valor{/if}</th><th>Coste en PrestaShop</th><th></th></tr></thead>
                  <tbody>
                  {foreach $v.filas as $f}
                    <tr>
                      <td>{$f.producto|escape:'html':'UTF-8'}{if $f.formato}<small>{$f.formato|escape:'html':'UTF-8'}</small>{/if}</td>
                      <td class="kps-mono">{$f.ean|escape:'html':'UTF-8'}</td>
                      <td class="kps-mono">{$f.ref|escape:'html':'UTF-8'}</td>
                      <td class="kps-num-col">{$f.valor_texto|escape:'html':'UTF-8'}{if $v.promo}<small>{if $f.origen == 'lista'}de la lista{else}del paso de la promoción{/if}</small>{/if}</td>
                      <td class="kps-num-col">{if $f.coste_texto}{$f.coste_texto|escape:'html':'UTF-8'}{else}<span class="kps-gris">—</span>{/if}</td>
                      <td class="kps-acciones">
                        <form method="post" action="{$kps.url|escape:'html':'UTF-8'}" class="kps-inline" onsubmit="return confirm('¿Quitar este artículo del acuerdo?');">
                          <input type="hidden" name="id" value="{$f.id|intval}"><input type="hidden" name="clave" value="{$v.clave|escape:'html':'UTF-8'}">
                          <button class="btn btn-default btn-xs" name="kpsBorrarFila" value="1" title="Quitar este artículo del acuerdo"><i class="icon-trash"></i></button>
                        </form>
                      </td>
                    </tr>
                  {/foreach}
                  </tbody>
                </table>
              </div>
            {/if}
            <div class="kps-mant">
              {if $v.promo}
                <div class="kps-mant__caja">
                  <strong>Las fechas y los artículos los manda la promoción</strong>
                  <p class="kps-nota">Se cambian en <a href="{$kps.url_promociones|escape:'html':'UTF-8'}&amp;editar={$v.id_kpy_coupon|intval}">la promoción {$v.promo.codigo|escape:'html':'UTF-8'}</a> (pasos 2 y 3): el acuerdo la sigue al guardarla y, en cada reparto de promociones, los artículos que entran nuevos reciben el acuerdo. Si los artículos no cuadran con la promoción (se le piden a Kompy · Promociones):</p>
                  <form method="post" action="{$kps.url|escape:'html':'UTF-8'}" class="kps-mant__fila">
                    <input type="hidden" name="id" value="{$v.id_kpy_coupon|intval}">
                    <button class="btn btn-default btn-sm" name="kpsSincronizarPromo" value="1"><i class="icon-refresh"></i> Poner al día con la promoción</button>
                  </form>
                </div>
              {else}
                <div class="kps-mant__caja">
                  <strong>Prolongar o cerrar</strong>
                  <form method="post" action="{$kps.url|escape:'html':'UTF-8'}" class="kps-mant__fila">
                    <input type="hidden" name="clave" value="{$v.clave|escape:'html':'UTF-8'}">
                    <input type="hidden" name="modo" value="fecha">
                    <span>Hasta el</span> <input type="date" name="hasta" class="form-control" value="{$v.hasta_corto|escape:'html':'UTF-8'}">
                    <button class="btn btn-default btn-sm" name="kpsFinal" value="1"><i class="icon-calendar"></i> Cambiar la fecha de fin</button>
                  </form>
                  <form method="post" action="{$kps.url|escape:'html':'UTF-8'}" class="kps-mant__fila" style="margin-top:6px">
                    <input type="hidden" name="clave" value="{$v.clave|escape:'html':'UTF-8'}">
                    {if $v.sin_fin}
                      <input type="hidden" name="modo" value="hoy">
                      <button class="btn btn-default btn-sm" name="kpsFinal" value="1">Cerrarlo hoy</button>
                    {else}
                      <input type="hidden" name="modo" value="sin_fin">
                      <button class="btn btn-default btn-sm" name="kpsFinal" value="1">Dejarlo hasta nueva orden</button>
                    {/if}
                  </form>
                </div>
              {/if}
              <div class="kps-mant__caja kps-mant__caja--peligro">
                <strong>Borrar el acuerdo entero</strong>
                <p class="kps-nota">Se quitan sus {$v.n|intval} filas. Los pedidos no se tocan. Si solo ha terminado, no hace falta borrarlo: se queda para consultarlo. Escribe BORRAR para confirmar.</p>
                <form method="post" action="{$kps.url|escape:'html':'UTF-8'}" class="kps-mant__fila">
                  <input type="hidden" name="clave" value="{$v.clave|escape:'html':'UTF-8'}">
                  <input type="text" name="confirmar" class="form-control" placeholder="BORRAR" autocomplete="off">
                  <button class="btn btn-danger btn-sm" name="kpsBorrarLote" value="1" disabled><i class="icon-trash"></i> Borrar</button>
                </form>
              </div>
            </div>
          </div>
        </div>
      {/if}

      {* Las ventas del periodo de un acuerdo *}
      {if $kps.informe}
        {assign var=inf value=$kps.informe}
        {assign var=vt value=$inf.ventas}
        <div class="kps-detalle" id="detalle">
          <div class="kps-detalle__cabeza">
            <h3>Ventas · <span class="kps-tipo kps-tipo--{$inf.tipo|escape:'html':'UTF-8'}">{$inf.tipo_titulo|escape:'html':'UTF-8'}</span> {if $inf.marca}{$inf.marca|escape:'html':'UTF-8'} · {/if}{$inf.valor_texto|escape:'html':'UTF-8'} · {$inf.periodo_texto|escape:'html':'UTF-8'}</h3>
            {if $inf.promo}<span class="kps-chip kps-chip--info">de la promoción {$inf.promo.codigo|escape:'html':'UTF-8'}</span>{/if}
            <span class="kps-cabeza__btns">
              <a class="btn btn-primary btn-sm" href="{$inf.excel|escape:'html':'UTF-8'}"><i class="icon-save"></i> Bajar el Excel{if !$kps.excel} (CSV){/if}</a>
              <a class="btn btn-default btn-sm" href="{$kps.url|escape:'html':'UTF-8'}&amp;ver={$inf.clave|escape:'html':'UTF-8'}#detalle"><i class="icon-eye"></i> Artículos</a>
              <a class="btn btn-default btn-sm" href="{$kps.url|escape:'html':'UTF-8'}#acuerdos">Cerrar</a>
            </span>
          </div>
          <div class="kps-detalle__cuerpo">
            <div class="kps-cifras kps-cifras--dentro">
              <div class="kps-cifra"><strong>{$vt.n_pedidos|intval}</strong><span>pedidos {if $inf.pendientes}pagados o pendientes de pago{else}pagados{/if}</span></div>
              <div class="kps-cifra"><strong>{$vt.totales.uds|intval}</strong><span>unidades vendidas</span></div>
              <div class="kps-cifra kps-cifra--si"><strong>{$inf.totales_texto.neto|escape:'html':'UTF-8'}</strong><span>ventas sin IVA (tras vales: {$inf.totales_texto.vales|escape:'html':'UTF-8'})</span></div>
              {if $inf.es_redencion}<div class="kps-cifra kps-cifra--info"><strong>{$inf.totales_texto.importe|escape:'html':'UTF-8'}</strong><span>a cobrar al proveedor (sin IVA)</span></div>{/if}
              {if $inf.es_coste}<div class="kps-cifra"><strong>{$inf.totales_texto.coste|escape:'html':'UTF-8'}</strong><span>coste de lo vendido al precio del acuerdo</span></div><div class="kps-cifra kps-cifra--info"><strong>{$inf.totales_texto.margen|escape:'html':'UTF-8'}</strong><span>margen (ventas − coste)</span></div>{/if}
              {if $vt.pendientes_fuera}<div class="kps-cifra kps-cifra--ojo"><strong>{$vt.pendientes_fuera|intval}</strong><span>pedidos pendientes de pago fuera · <a href="{$inf.url_pendientes|escape:'html':'UTF-8'}">incluirlos</a></span></div>{/if}
              {if $inf.pendientes}<div class="kps-cifra kps-cifra--ojo"><strong>con pendientes</strong><span>se cuentan los pendientes de pago · <a href="{$inf.url_pendientes|escape:'html':'UTF-8'}">solo pagados</a></span></div>{/if}
            </div>
            <p class="kps-ayuda">Ventas sin IVA = lo cobrado por cada línea sin IVA, menos los vales de la cesta que le tocan (regalo, 2ª unidad, cupo, cupones y códigos). Unidades = las vendidas menos las devueltas o reembolsadas («de regalo»: las que se dieron gratis con una promoción de regalo, a 0 €). No cuentan los pedidos en estado: {$kps.estados_fuera_texto|escape:'html':'UTF-8'} (se cambia en <a href="#ajustes">Ajustes</a>). {if $inf.es_redencion}Importe = ventas × {$inf.valor_texto|escape:'html':'UTF-8'}; al proveedor se le factura ese total más IVA.{/if}{if $inf.es_coste}Coste = unidades × precio de coste del acuerdo; margen = ventas − coste.{/if}{if $inf.sin_ventas} <span class="kps-gris">{$inf.sin_ventas|intval} artículos del acuerdo sin ventas en el periodo no salen.</span>{/if}</p>
            {if !$inf.hay_ventas}
              <p class="kps-gris">Sin ventas de estos artículos en el periodo{if $vt.pendientes_fuera} (hay {$vt.pendientes_fuera|intval} pedidos pendientes de pago){/if}.</p>
            {else}
              <div class="kps-tabla-caja">
                <table class="table kps-tabla">
                  <thead><tr><th>Artículo</th><th>EAN</th><th>Referencia</th><th class="kps-num-col">Uds</th><th class="kps-num-col">Pedidos</th><th class="kps-num-col">Ventas sin IVA</th><th class="kps-num-col">{if $inf.es_coste}Precio de coste{elseif $inf.es_redencion}Redención{else}Dto en factura{/if}</th>{if $inf.es_redencion}<th class="kps-num-col">Importe</th>{/if}{if $inf.es_coste}<th class="kps-num-col">Coste</th><th class="kps-num-col">Margen</th>{/if}</tr></thead>
                  <tbody>
                  {foreach $vt.articulos as $a}
                    <tr>
                      <td>{$a.producto|escape:'html':'UTF-8'}{if $a.formato}<small>{$a.formato|escape:'html':'UTF-8'}</small>{/if}</td>
                      <td class="kps-mono">{$a.ean|escape:'html':'UTF-8'}</td>
                      <td class="kps-mono">{$a.ref|escape:'html':'UTF-8'}</td>
                      <td class="kps-num-col">{$a.uds|intval}{if $a.regalo}<small>{$a.regalo|intval} de regalo</small>{/if}</td>
                      <td class="kps-num-col">{$a.pedidos|intval}</td>
                      <td class="kps-num-col">{$a.neto_texto|escape:'html':'UTF-8'}</td>
                      <td class="kps-num-col">{$a.valor_texto|escape:'html':'UTF-8'}</td>
                      {if $inf.es_redencion}<td class="kps-num-col">{$a.importe_texto|escape:'html':'UTF-8'}</td>{/if}
                      {if $inf.es_coste}<td class="kps-num-col">{$a.coste_texto|escape:'html':'UTF-8'}</td><td class="kps-num-col">{$a.margen_texto|escape:'html':'UTF-8'}</td>{/if}
                    </tr>
                  {/foreach}
                  </tbody>
                  <tfoot><tr><td colspan="3">Total</td><td class="kps-num-col">{$vt.totales.uds|intval}{if $vt.totales.regalo}<small>{$vt.totales.regalo|intval} de regalo</small>{/if}</td><td class="kps-num-col">{$vt.n_pedidos|intval}</td><td class="kps-num-col">{$inf.totales_texto.neto|escape:'html':'UTF-8'}</td><td></td>{if $inf.es_redencion}<td class="kps-num-col">{$inf.totales_texto.importe|escape:'html':'UTF-8'}</td>{/if}{if $inf.es_coste}<td class="kps-num-col">{$inf.totales_texto.coste|escape:'html':'UTF-8'}</td><td class="kps-num-col">{$inf.totales_texto.margen|escape:'html':'UTF-8'}</td>{/if}</tr></tfoot>
                </table>
              </div>
              <details class="kps-pliegue">
                <summary>Pedido a pedido <small>({$vt.detalle|count} líneas)</small></summary>
                <div class="kps-pliegue__cuerpo kps-tabla-caja">
                  <table class="table kps-tabla">
                    <thead><tr><th>Pedido</th><th>Fecha</th><th>Artículo</th><th class="kps-num-col">Uds</th><th class="kps-num-col">Línea sin IVA</th><th class="kps-num-col">Vales</th><th class="kps-num-col">Ventas sin IVA</th>{if $inf.es_redencion}<th class="kps-num-col">Importe</th>{/if}</tr></thead>
                    <tbody>
                    {foreach $vt.detalle as $d}
                      <tr>
                        <td class="kps-mono">{$d.pedido|escape:'html':'UTF-8'}{if !$d.pagado}<small>pendiente de pago</small>{/if}</td>
                        <td class="kps-nowrap">{$d.fecha_texto|escape:'html':'UTF-8'}</td>
                        <td>{$d.producto|escape:'html':'UTF-8'}</td>
                        <td class="kps-num-col">{$d.uds|intval}{if $d.regalo}<small>de regalo</small>{/if}</td>
                        <td class="kps-num-col">{$d.bruto_texto|escape:'html':'UTF-8'}</td>
                        <td class="kps-num-col">{$d.vales_texto|escape:'html':'UTF-8'}</td>
                        <td class="kps-num-col">{$d.neto_texto|escape:'html':'UTF-8'}</td>
                        {if $inf.es_redencion}<td class="kps-num-col">{$d.importe_texto|escape:'html':'UTF-8'}</td>{/if}
                      </tr>
                    {/foreach}
                    </tbody>
                  </table>
                </div>
              </details>
            {/if}
          </div>
        </div>
      {/if}
    </div>
  </div>

  {* 2 · Subir una lista *}
  <div class="panel kps-panel" id="subir">
    <div class="panel-heading kps-cabeza"><i class="icon-upload"></i> Subir una lista del proveedor</div>
    <div class="kps-cuerpo">
      <p class="kps-ayuda">La lista tal cual la manda el proveedor: una columna con el <b>EAN</b> (o la referencia del fabricante, o la nuestra) y, si cada artículo trae el suyo, otra con el <b>%</b> o el <b>precio</b>. Se pega copiada del Excel o se sube el fichero. <b>Comprobar</b> no guarda nada: enseña qué fila es qué artículo, cuántas se han encontrado, cuáles no (para reclamarlas) y qué artículos ya tienen otro acuerdo en esas fechas. <b>Guardar</b> escribe solo lo encontrado.</p>
      <form method="post" action="{$kps.url|escape:'html':'UTF-8'}" enctype="multipart/form-data" class="kps-form kps-subir">
        <button type="submit" name="kpsComprobar" value="1" hidden aria-hidden="true" tabindex="-1"></button>
        {assign var=fo value=$kps.form}
        <section class="kps-paso">
          <h3 class="kps-paso__titulo"><span class="kps-num">1</span> Qué acuerdo</h3>
          <div class="kps-tipos">
            {foreach $kps.tipos as $clave => $t}
              <label class="kps-tipo-caja">
                <input type="radio" name="kps_tipo" value="{$clave|escape:'html':'UTF-8'}" {if $fo.tipo == $clave}checked{/if}>
                <span class="kps-tipo-caja__caja"><strong>{$t.titulo|escape:'html':'UTF-8'}</strong><small>{$t.explica|escape:'html':'UTF-8'}</small></span>
              </label>
            {/foreach}
          </div>
          <div class="kps-fila kps-valor-general">
            <div class="kps-campo kps-campo--corto">
              <label for="kps-valor">% general <span class="kps-opcional">(si la lista no lo trae por fila)</span></label>
              <input type="text" class="form-control" id="kps-valor" name="kps_valor" value="{$fo.valor_texto|escape:'html':'UTF-8'}" placeholder="10" inputmode="decimal">
            </div>
            <div class="kps-campo"><small class="kps-nota kps-valor-pista" style="margin-top:28px"></small></div>
          </div>
        </section>

        <section class="kps-paso">
          <h3 class="kps-paso__titulo"><span class="kps-num">2</span> Cuándo <span class="kps-opcional">(y de qué promoción, si viene de una)</span></h3>
          <div class="kps-fila">
            <div class="kps-campo kps-campo--ancho">
              <label for="kps-promo">Promoción <span class="kps-opcional">(opcional)</span></label>
              <select class="form-control" id="kps-promo" name="kps_promo">
                <option value="0" {if !$fo.promo}selected{/if}>Ninguna: un acuerdo suelto, con sus propias fechas</option>
                {foreach $kps.promos as $pr}
                  <option value="{$pr.id|intval}" data-desde="{$pr.desde|escape:'html':'UTF-8'}" data-hasta="{$pr.hasta|escape:'html':'UTF-8'}" {if $fo.promo == $pr.id}selected{/if}>{$pr.texto|escape:'html':'UTF-8'}</option>
                {/foreach}
              </select>
              <small class="kps-nota">Con promoción, el acuerdo lleva sus fechas y la sigue si se alarga; sale en Kompy · Promociones, en su paso «Acuerdo con el proveedor». Los artículos son los de la lista, no los de la promoción.</small>
            </div>
            <div class="kps-campo kps-campo--corto">
              <label for="kps-desde">Desde</label>
              <input type="date" class="form-control" id="kps-desde" name="kps_desde" value="{$fo.desde_texto|escape:'html':'UTF-8'}">
            </div>
            <div class="kps-campo kps-campo--corto">
              <label for="kps-hasta">Hasta</label>
              <input type="date" class="form-control" id="kps-hasta" name="kps_hasta" value="{$fo.hasta_texto|escape:'html':'UTF-8'}">
              <label class="kps-check" style="margin-top:6px"><input type="checkbox" name="kps_sin_fin" value="1" {if $fo.sin_fin}checked{/if}> <span>Hasta nueva orden</span></label>
            </div>
          </div>
          <small class="kps-nota kps-fechas-nota"></small>
        </section>

        <section class="kps-paso kps-pegar">
          <h3 class="kps-paso__titulo"><span class="kps-num">3</span> La lista</h3>
          <div class="kps-fila">
            <div class="kps-campo kps-campo--ancho">
              <label for="kps-texto">Pegada desde el Excel <span class="kps-opcional">(columnas con tabulador o punto y coma; la cabecera no molesta)</span></label>
              <textarea class="form-control" id="kps-texto" name="kps_texto" rows="9" placeholder="EAN&#9;Dto.&#10;8437019628055&#9;15 %&#10;8437019628062&#9;15 %&#10;3182550846042&#9;20 %">{$fo.texto|escape:'html':'UTF-8'}</textarea>
              <div class="kps-fichero">
                <span class="kps-gris">o el fichero:</span>
                <input type="file" name="kps_fichero" accept=".xlsx,.xls,.ods,.csv,.txt,.tsv">
                {if $fo.fichero}<span class="kps-chip kps-chip--info">leído: {$fo.fichero|escape:'html':'UTF-8'}</span>{/if}
              </div>
              <small class="kps-nota">Se reconocen EAN (con o sin los ceros que Excel se come), nuestra referencia, la del proveedor y el MPN, del formato o del producto. Con varias columnas, se queda con la que más artículos encuentra y con la del % o el precio (por su cabecera: «Dto.», «%», «Precio», «Neto»…). Un precio o un % por fila manda sobre el general.</small>
            </div>
          </div>
        </section>

        {if $kps.comprobacion}
          {assign var=c value=$kps.comprobacion}
          <section class="kps-paso" id="comprobacion">
            <h3 class="kps-paso__titulo"><span class="kps-num">4</span> Comprobación <span class="kps-opcional">(todavía no se ha guardado nada)</span></h3>
            {foreach $c.errores as $e}<div class="alert alert-danger">{$e|escape:'html':'UTF-8'}</div>{/foreach}
            {if $c.lectura}
              <div class="kps-lectura">
                <span>{$c.lectura|escape:'html':'UTF-8'}</span>
                {if $c.columnas}
                  <label class="kps-lectura__elige">{if $c.form.tipo == $kps.tipo_coste}Columna del precio{else}Columna del %{/if}:
                    <select name="kps_columna" class="form-control">
                      <option value="" {if $c.form.columna_texto === ''}selected{/if}>la que diga su cabecera</option>
                      <option value="-1" {if $c.form.columna_texto === '-1'}selected{/if}>ninguna{if $c.form.tipo != $kps.tipo_coste}: todos al % general{/if}</option>
                      {foreach $c.columnas as $i => $nombreColumna}
                        {if $i + 1 != $c.r.columna}<option value="{$i|intval}" {if $c.form.columna_texto !== '' && $c.form.columna_texto == $i}selected{/if}>{$i + 1}: {$nombreColumna|escape:'html':'UTF-8'}</option>{/if}
                      {/foreach}
                    </select>
                  </label>
                  <small class="kps-nota">Si no es la buena, elígela y pulsa Comprobar otra vez.</small>
                {/if}
              </div>
            {/if}
            {if $c.r.filas}
              <div class="kps-cifras kps-cifras--dentro">
                <div class="kps-cifra"><strong>{$c.r.filas|intval}</strong><span>filas con código{if $c.r.columna} (columna {$c.r.columna|intval}{if $c.r.columna_valor}; el valor, en la {$c.r.columna_valor|intval}{/if}){/if}</span></div>
                <div class="kps-cifra kps-cifra--si"><strong>{$c.filas|count}</strong><span>artículos encontrados</span></div>
                {if $c.r.no}<div class="kps-cifra kps-cifra--mal"><strong>{$c.r.no|count}</strong><span>filas sin encontrar</span></div>{/if}
                {if $c.r.sin_valor}<div class="kps-cifra kps-cifra--mal"><strong>{$c.r.sin_valor|count}</strong><span>sin un valor que se entienda</span></div>{/if}
                {if $c.r.repetidos}<div class="kps-cifra kps-cifra--ojo"><strong>{$c.r.repetidos|count}</strong><span>repetidas con otro valor</span></div>{/if}
                {if $c.r.apagados}<div class="kps-cifra kps-cifra--ojo"><strong>{$c.r.apagados|intval}</strong><span>de productos apagados o formatos fuera de venta (se guardan igual)</span></div>{/if}
                {if $c.iguales}<div class="kps-cifra kps-cifra--info"><strong>{$c.iguales|intval}</strong><span>ya estaban guardados igual (no se tocan)</span></div>{/if}
                {if $c.solapes}<div class="kps-cifra kps-cifra--ojo"><strong>{$c.solapes|intval}</strong><span>ya tienen otro acuerdo en esas fechas</span></div>{/if}
                {if $c.avisos_coste}<div class="kps-cifra kps-cifra--ojo"><strong>{$c.avisos_coste|intval}</strong><span>precios que no parecen de la unidad de venta</span></div>{/if}
              </div>
            {/if}
            {if $c.r.no}
              <div class="kps-lista-mal">
                <strong>Sin encontrar ({$c.r.no|count}):</strong> no están en la tienda con ese EAN o referencia, o el código viene mal. Se pueden copiar para reclamarlas al proveedor. <button type="button" class="btn btn-default btn-xs" data-kps-copiar="kps-no">Copiar</button>
                <textarea id="kps-no" readonly>{$c.no_texto|escape:'html':'UTF-8'}</textarea>
              </div>
            {/if}
            {if $c.r.sin_valor}<div class="kps-lista-mal"><strong>Encontradas pero sin un valor que se entienda ({$c.r.sin_valor|count}):</strong> {$c.sin_valor_texto|escape:'html':'UTF-8'}. {if $c.form.tipo == $kps.tipo_coste}Hace falta el precio al lado de cada código.{else}Pon el % general o el suyo en la fila.{/if}</div>{/if}
            {if $c.r.repetidos}<div class="kps-lista-ojo"><strong>Repetidas con otro valor:</strong> {$c.repetidos_texto|escape:'html':'UTF-8'}.</div>{/if}
            {if $c.solapes}
              <div class="kps-solapes">
                <strong>{$c.solapes|intval} artículos ya tienen otro acuerdo que se cruza con estas fechas (se ven abajo, en amarillo). ¿Qué hacemos con ellos?</strong>
                <label class="kps-check"><input type="radio" name="kps_solapes" value="sustituir" {if $c.form.solapes == 'sustituir'}checked{/if}> <span><strong>Sustituir</strong>: el acuerdo anterior se recorta para que acabe el día antes de empezar este (o se quita si queda entero dentro de estas fechas). Lo normal cuando el nuevo es el bueno.</span></label>
                <label class="kps-check"><input type="radio" name="kps_solapes" value="saltar" {if $c.form.solapes == 'saltar'}checked{/if}> <span><strong>Saltar</strong>: esos artículos no se guardan; el acuerdo anterior se queda como está. Los demás sí se guardan.</span></label>
                {if $c.pide_politica}<div class="alert alert-warning" style="margin:8px 0 0">Elige una de las dos y vuelve a pulsar Guardar.</div>{/if}
              </div>
            {else}
              <input type="hidden" name="kps_solapes" value="sustituir">
            {/if}
            {if $c.filas}
              <div class="kps-tabla-caja">
                <table class="table kps-tabla">
                  <thead><tr><th>En la lista</th><th>Artículo</th><th>EAN</th><th>Referencia</th><th class="kps-num-col">{if $c.form.tipo == $kps.tipo_coste}Precio nuevo{else}Valor{/if}</th><th class="kps-num-col">Coste en PrestaShop</th><th>Avisos</th></tr></thead>
                  <tbody>
                  {foreach $c.filas as $f}
                    <tr{if $f.cruces} class="kps-fila--ojo"{/if}>
                      <td class="kps-mono">{$f.codigo|escape:'html':'UTF-8'}</td>
                      <td>{$f.producto|escape:'html':'UTF-8'}{if $f.formato}<small>{$f.formato|escape:'html':'UTF-8'}</small>{/if}{if $f.marca}<small>{$f.marca|escape:'html':'UTF-8'}</small>{/if}</td>
                      <td class="kps-mono">{$f.ean|escape:'html':'UTF-8'}</td>
                      <td class="kps-mono">{$f.ref|escape:'html':'UTF-8'}</td>
                      <td class="kps-num-col">{$f.valor_texto|escape:'html':'UTF-8'}{if $f.con_dto}<small>coste con el dto.: {$f.con_dto|escape:'html':'UTF-8'}</small>{/if}</td>
                      <td class="kps-num-col">{if $f.coste_texto}{$f.coste_texto|escape:'html':'UTF-8'}{else}<span class="kps-gris">—</span>{/if}</td>
                      <td>
                        {if $f.igual}<span class="kps-chip kps-chip--info">ya está igual</span>{/if}
                        {if $f.aviso}<span class="kps-chip kps-chip--ojo">{$f.aviso|escape:'html':'UTF-8'}</span>{/if}
                        {foreach $f.cruces as $x}<span class="kps-chip kps-chip--ojo">ya tiene: {$x|escape:'html':'UTF-8'}</span>{/foreach}
                      </td>
                    </tr>
                  {/foreach}
                  </tbody>
                </table>
              </div>
            {/if}
          </section>
        {/if}

        <div class="kps-barra">
          <p class="kps-barra__resumen">
            {if $kps.comprobacion && $kps.comprobacion.items}
              Listo para guardar {$kps.comprobacion.items|count} artículos{if $kps.comprobacion.form.promo_texto} en la promoción {$kps.comprobacion.form.promo_texto|escape:'html':'UTF-8'}{/if}.
            {elseif $kps.comprobacion}
              Hay que arreglar lo de arriba antes de guardar.
            {else}
              Primero Comprobar: no guarda nada, solo enseña qué ha entendido.
            {/if}
          </p>
          <div class="kps-barra__botones">
            <button type="submit" class="btn btn-default" name="kpsComprobar" value="1"><i class="icon-search"></i> Comprobar</button>
            <button type="submit" class="btn btn-primary" name="kpsGuardar" value="1" {if !$kps.comprobacion || !$kps.comprobacion.items}disabled{/if}><i class="icon-save"></i> Guardar</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  {* 3 · Ajustes *}
  <div class="panel kps-panel" id="ajustes">
    <div class="panel-heading kps-cabeza"><i class="icon-cog"></i> Ajustes</div>
    <div class="kps-cuerpo">
      <form method="post" action="{$kps.url|escape:'html':'UTF-8'}">
        <p class="kps-pregunta">Estados de pedido que <b>no</b> cuentan como venta <small>(en las ventas de los acuerdos y para no quitar de un acuerdo lo que ya vendió)</small></p>
        <p class="kps-ayuda">Por defecto: cancelado, error en el pago y reembolsado. Si la tienda tiene estados propios que no son una venta (un pedido borrado en Aqua para liberar mercancía, por ejemplo), márcalos aquí. Los marcados «pagado» son los que PrestaShop da por venta; los pendientes de pago solo cuentan si se piden en el informe.</p>
        <div class="kps-estados">
          {foreach $kps.estados as $e}
            <label class="kps-check"><input type="checkbox" name="kps_estados_fuera[]" value="{$e.id|intval}" {if $e.fuera}checked{/if}> <span>{$e.nombre|escape:'html':'UTF-8'}{if $e.pagado} <span class="kps-chip kps-chip--si">pagado</span>{/if}</span></label>
          {/foreach}
        </div>
        <button type="submit" class="btn btn-default" name="kpsAjustes" value="1"><i class="icon-save"></i> Guardar los ajustes</button>
      </form>
    </div>
  </div>
</div>
