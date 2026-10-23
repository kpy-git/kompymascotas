{**
 * El paso «Acuerdo con el proveedor» del formulario de Kompy · Promociones. Lo pinta kpysupply por
 * el hook displayKpyCouponsFormulario y lo guarda por actionKpyCouponsGuardar. Usa las clases de la
 * pantalla de Promociones (kpc-*) para que parezca un paso más, y las suyas (kps-*) para lo propio.
 *
 * El % de cada tipo sale solo con ese tipo marcado (CSS `:has()`, como el resto del formulario). Ojo
 * con la hoja del back office: `.bootstrap .form-control` pesa más que una clase sola, así que lo
 * propio va bajo `.kpc-admin`.
 *}
{assign var=k value=$kps}
<section class="kpc-paso kps-paso">
  <h3 class="kpc-paso__titulo"><span class="kpc-num">{$k.numero|intval}</span> Acuerdo con el proveedor <span class="kpc-opcional">(lo que el proveedor nos da por esta promoción; no cambia nada de cara al cliente)</span></h3>
  <input type="hidden" name="kps_paso" value="1">

  {if $k.aviso}<div class="alert alert-warning">{$k.aviso|escape:'html':'UTF-8'}</div>{/if}

  <div class="kpc-ayuda">
    Al guardar, el acuerdo se apunta <b>para cada artículo de la promoción</b> (los del paso 2) con <b>las fechas de la promoción</b> (paso 3), y la sigue cuando se alarga o entran artículos nuevos. Se consulta y se exporta a Excel en <a href="{$k.url_pantalla|escape:'html':'UTF-8'}">Catálogo → Kompy · Acuerdos con proveedores</a>: la lista del acuerdo y, al acabar, las ventas sin IVA de cada artículo con lo que toca cobrar.
    {if $k.actual_texto}<span class="kps-paso__actual">Hoy: {$k.actual_texto|escape:'html':'UTF-8'} · <a href="{$k.url_ver|escape:'html':'UTF-8'}">ver</a></span>{/if}
  </div>

  <div class="kpc-radios kps-paso__tipos">
    <label class="kpc-check">
      <input type="radio" name="kps_tipo" value="" {if $k.tipo == ''}checked{/if}>
      <span><strong>Sin acuerdo{if $k.n_lista} general{/if}</strong> — {if $k.n_lista}solo lo subido por lista{else}la promoción la pagamos nosotros{/if}.</span>
    </label>
    <label class="kpc-check">
      <input type="radio" name="kps_tipo" value="redencion" {if $k.tipo == 'redencion'}checked{/if}>
      <span><strong>Redención</strong> — {$k.tipos.redencion.corto|escape:'html':'UTF-8'}.</span>
    </label>
    <div class="kps-paso__valor" data-kps-para="redencion">
      <div class="input-group">
        <input type="text" class="form-control" name="kps_valor_redencion" value="{if $k.tipo == 'redencion'}{$k.valor|escape:'html':'UTF-8'}{/if}" placeholder="10" inputmode="decimal" aria-label="Porcentaje de redención">
        <span class="input-group-addon">%</span>
      </div>
      <span>de las ventas sin IVA de sus artículos, al acabar</span>
    </div>
    <label class="kpc-check">
      <input type="radio" name="kps_tipo" value="factura" {if $k.tipo == 'factura'}checked{/if}>
      <span><strong>Descuento en factura</strong> — {$k.tipos.factura.corto|escape:'html':'UTF-8'}.</span>
    </label>
    <div class="kps-paso__valor" data-kps-para="factura">
      <div class="input-group">
        <input type="text" class="form-control" name="kps_valor_factura" value="{if $k.tipo == 'factura'}{$k.valor|escape:'html':'UTF-8'}{/if}" placeholder="15" inputmode="decimal" aria-label="Porcentaje de descuento en factura">
        <span class="input-group-addon">%</span>
      </div>
      <span>sobre el precio de coste neto, en cada factura del periodo</span>
    </div>
  </div>
  <small class="kpc-nota">¿Un % distinto por gama o por EAN, o <b>precios de coste nuevos</b>? Se sube la lista del proveedor en <a href="{$k.url_pantalla|escape:'html':'UTF-8'}#subir">Acuerdos con proveedores</a> eligiendo esta promoción: lo de la lista manda sobre el % de aquí, artículo por artículo. «Sin acuerdo» quita lo de este paso; lo subido por lista se queda (se quita en la pantalla). Los artículos que ya han vendido con el acuerdo no se quitan nunca solos.</small>
</section>
<style>{literal}
.kps-paso__actual{display:inline-block;margin-left:8px;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:700;background:#E6F1FA;color:#1D5C8C;vertical-align:middle}
.kps-paso__tipos .kpc-check{margin-bottom:8px}
.kps-paso__valor{display:flex;flex-wrap:wrap;align-items:center;gap:8px 10px;margin:-2px 0 12px 24px;color:#33404C}
.kpc-admin .kps-paso__valor .input-group{width:120px;flex:0 0 120px}
.kpc-admin .kps-paso__valor .form-control{height:32px;padding:4px 10px;font-size:15px;font-weight:700;text-align:right}
.kps-paso__tipos:not(:has(input[name="kps_tipo"][value="redencion"]:checked)) [data-kps-para="redencion"],
.kps-paso__tipos:not(:has(input[name="kps_tipo"][value="factura"]:checked)) [data-kps-para="factura"]{display:none}
{/literal}</style>
