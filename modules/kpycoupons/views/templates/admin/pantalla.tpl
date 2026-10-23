{**
 * Kompy · Promociones: formulario, productos de la promoción, lista y ficheros de Google (ver KpcPantalla).
 *
 * Lo que se ve según lo elegido lo decide el CSS (`:has()`), no el JS: funciona desde el primer
 * momento y aunque el JS tarde o falle.
 *   `.kpc-tras-tipo`: no se ve hasta elegir qué promoción es.
 *   `.kpc-tras-elegir`: no se ve hasta elegir cómo se eligen los productos (por marca o lista).
 *   `data-kpc-elegir="marca|lista"`: solo con esa manera de elegir los productos.
 *   `data-kpc-si-packs`: solo con «Incluir los packs» marcado.
 *   `data-kpc-solo="a b"`: solo con esos tipos (cupon, segunda_unidad, special_price, regalo).
 *   `data-kpc-modo="a b"`: solo con esos modos del Special Price (pct, pct_articulo, precio_articulo);
 *   con un cupón, una 2ª unidad o un regalo cuenta como «pct».
 *   `data-kpc-regalo-modo="mismo"`: solo cuando el regalo es «el mismo artículo para todos».
 *}
{assign var=v value=$kpc.v}
<div class="kpc-admin" id="kpc-admin" data-kpc-buscar="{$kpc.url|escape:'html':'UTF-8'}&amp;ajax=1&amp;action=buscar">

  {foreach $kpc.avisos as $aviso}
    <div class="alert {if $aviso[0] === 'ok'}alert-success{else}alert-danger{/if}">{$aviso[1]|escape:'html':'UTF-8'}</div>
  {/foreach}

  {include file=$kpc.plantillas|cat:'lista.tpl'}

  <form method="post" action="{$kpc.url|escape:'html':'UTF-8'}" enctype="multipart/form-data" class="kpc-form" id="kpc-form" novalidate{if $kpc.comprobado} data-kpc-comprobado{/if}>
    <input type="hidden" name="kpc_formulario" value="1">
    <input type="hidden" name="id_kpy_coupon" value="{$v.id|intval}">

    <div class="panel kpc-panel">
      <div class="panel-heading kpc-cabeza">
        <i class="icon-tags"></i>
        {if $v.id}Cambiar «{$v.nombre|default:$v.codigo|escape:'html':'UTF-8'}»{else}Promoción nueva{/if}
        {if $v.id}<a class="btn btn-default btn-sm kpc-cabeza__nueva" href="{$kpc.url|escape:'html':'UTF-8'}">+ Nueva</a>{/if}
      </div>

      {* 1 · Qué promoción *}
      <section class="kpc-paso">
        <h3 class="kpc-paso__titulo"><span class="kpc-num">1</span> Qué promoción</h3>
        <div class="kpc-tipos" role="radiogroup" aria-label="Tipo de promoción">
          <label class="kpc-tipo">
            <input type="radio" name="tipo" value="cupon" {if $v.tipo === 'cupon'}checked{/if}>
            <span class="kpc-tipo__caja">
              <span class="kpc-tipo__icono kpc-tipo__icono--cupon" aria-hidden="true">%</span>
              <strong>Cupón con código</strong>
              <small>Un % con su código (BOSKE10), que se descuenta en la cesta. El cliente lo pulsa en la ficha o lo escribe, o le llega ya puesto.</small>
            </span>
          </label>
          <label class="kpc-tipo">
            <input type="radio" name="tipo" value="segunda_unidad" {if $v.tipo === 'segunda_unidad'}checked{/if}>
            <span class="kpc-tipo__caja">
              <span class="kpc-tipo__icono kpc-tipo__icono--segunda" aria-hidden="true">2ª</span>
              <strong>2ª unidad al %</strong>
              <small>Al llevar dos del mismo formato, la segunda sale al %. Entra sola, sin código. 4 uds = 2 con descuento. Los packs no entran.</small>
            </span>
          </label>
          <label class="kpc-tipo">
            <input type="radio" name="tipo" value="special_price" {if $v.tipo === 'special_price'}checked{/if}>
            <span class="kpc-tipo__caja">
              <span class="kpc-tipo__icono kpc-tipo__icono--especial" aria-hidden="true">★</span>
              <strong>Special Price</strong>
              <small>El precio ya rebajado en la ficha y en los listados, tachado y con la etiqueta «Special Price» (ninguna otra). Un % para todos, uno por artículo o un precio por artículo.</small>
            </span>
          </label>
          <label class="kpc-tipo">
            <input type="radio" name="tipo" value="regalo" {if $v.tipo === 'regalo'}checked{/if}>
            <span class="kpc-tipo__caja">
              <span class="kpc-tipo__icono kpc-tipo__icono--regalo" aria-hidden="true"><i class="icon-gift"></i></span>
              <strong>Regalo con la compra</strong>
              <small>En la ficha sale una tarjeta más, «12 kg + 3 kg gratis». Si el cliente la elige, el saco va a su precio de siempre y el pequeño (o el artículo que elijas: latas, snacks, una carda) entra en la cesta a 0 €.</small>
            </span>
          </label>
        </div>

        <p class="kpc-elige">Elige el tipo: aparecerá solo lo que hace falta para ese.</p>

        <div class="kpc-tras-tipo">
          {* El regalo: qué se regala, cuántos, el tope y si los packs también regalan *}
          <div class="kpc-regalo-bloque" data-kpc-solo="regalo">
            <input type="hidden" name="regalo_parejas" value="{$v.regalo_parejas|escape:'html':'UTF-8'}" data-kpc-parejas>
            <p class="kpc-pregunta">¿Qué se regala?</p>
            <div class="kpc-radios">
              <label class="kpc-check"><input type="radio" name="regalo_modo" value="pequeno" {if $v.regalo_modo !== 'mismo'}checked{/if}>
                <span><strong>El formato pequeño de la misma ficha</strong> — con el saco de 12 kg, el de 3 kg de ese mismo producto; con el de 10 kg de gato, el de 2,5 kg. El más pequeño que esté a la venta (sin OUTLET ni «gratis»). Un producto sin formato pequeño regala el de reserva; sin reserva, se queda fuera.</span></label>
              <label class="kpc-check"><input type="radio" name="regalo_modo" value="mismo" {if $v.regalo_modo === 'mismo'}checked{/if}>
                <span><strong>El mismo artículo para todos</strong> — una caja de latas, un snack, una carda… cualquier artículo de la tienda, con su EAN y su stock.</span></label>
            </div>
            <div class="kpc-fila">
              <div class="kpc-campo kpc-campo--articulo" data-kpc-regalo-modo="mismo">
                <label>Artículo que se regala</label>
                {include file=$kpc.plantillas|cat:'articulo.tpl' nombre='regalo_mismo' valor=$v.regalo_mismo dichos=$v.regalo_articulos}
                <small class="kpc-nota">Busca por nombre, EAN, referencia o id. Mejor de la misma marca y el mismo proveedor que lo que se compra: el pedido va entero al mismo almacén.</small>
              </div>
              <div class="kpc-campo kpc-campo--articulo">
                <label>Regalo de reserva <span class="kpc-opcional">(opcional)</span></label>
                {include file=$kpc.plantillas|cat:'articulo.tpl' nombre='regalo_reserva' valor=$v.regalo_reserva dichos=$v.regalo_articulos}
                <small class="kpc-nota">Si el regalo se agota, entra este. Y, con «el pequeño de la misma ficha», es lo que se regala a los productos que no tienen formato pequeño.</small>
              </div>
            </div>
            <div class="kpc-fila">
              <div class="kpc-campo">
                <p class="kpc-pregunta">¿Cuántos?</p>
                <label class="kpc-check"><input type="radio" name="regalo_por" value="unidad" {if $v.regalo_por !== 'pedido'}checked{/if}>
                  <span><strong>Uno por cada unidad</strong> — dos sacos, dos regalos.</span></label>
                <label class="kpc-check"><input type="radio" name="regalo_por" value="pedido" {if $v.regalo_por === 'pedido'}checked{/if}>
                  <span><strong>Uno por pedido</strong> — aunque lleve varios.</span></label>
                <small class="kpc-nota">Cuántos regalos hay en total (los que pone el fabricante) se pone en el paso 3, en el cupo.</small>
              </div>
            </div>
            <label class="kpc-check">
              <input type="checkbox" name="regalo_packs" value="1" {if $v.regalo_packs}checked{/if}>
              <span><strong>Los packs también regalan</strong> — el «Pack 2 x Saco de 12 Kg» regala dos. Sin marcar, solo los sueltos.</span>
            </label>
            <div class="kpc-fila">
              <div class="kpc-campo kpc-campo--ancho">
                <label for="kpc-regalo-corto">Cómo se llama la tarjeta de la ficha <span class="kpc-opcional">(opcional)</span></label>
                <div class="kpc-prefijo"><span>12 kg +</span><input type="text" class="form-control" id="kpc-regalo-corto" name="regalo_corto" value="{$v.regalo_corto|escape:'html':'UTF-8'}" maxlength="24" placeholder="se decide solo"></div>
                <small class="kpc-nota">Solo cuando el regalo es otra cosa (con el pequeño de su ficha dice siempre «+ 3 kg gratis»). Vacío: «Snacks» si es un snack, «Latas» o «Pack latas» si son latas (sobres, tarrinas…), y «Regalo» para lo demás. Escribe aquí si prefieres otro («Carda», «Juguete»).</small>
              </div>
            </div>
            <p class="kpc-nota">El cliente elige en la ficha la tarjeta con regalo; la de siempre («12 kg») sigue con su Special Price y sin regalo. Con la de regalo, el saco grande va a su <strong>precio habitual</strong> (sin el Special Price) y el regalo entra en la cesta como una línea normal a 0 € (con su EAN y su stock, el almacén lo prepara como cualquier otra), y queda apuntado en el pedido («(regalo)») y en el sell-out de la marca. Si el cliente quita el saco, el regalo se va con él; si quita el regalo (la papelera de su vale), el saco vuelve a su precio de hoy.</p>
          </div>

          <div class="kpc-modos" data-kpc-solo="special_price" role="radiogroup" aria-label="Cómo se rebaja">
            <p class="kpc-pregunta">¿Cómo se rebaja?</p>
            <label class="kpc-check"><input type="radio" name="modo_precio" value="pct" {if $v.modo_precio === 'pct'}checked{/if}>
              <span><strong>El mismo % para todos</strong> — sobre el precio de hoy (el de PriceShape), como hace Julián.</span></label>
            <label class="kpc-check"><input type="radio" name="modo_precio" value="pct_articulo" {if $v.modo_precio === 'pct_articulo'}checked{/if}>
              <span><strong>Cada artículo con su %</strong> — va en la lista, al lado del EAN o la referencia.</span></label>
            <label class="kpc-check"><input type="radio" name="modo_precio" value="precio_articulo" {if $v.modo_precio === 'precio_articulo'}checked{/if}>
              <span><strong>Cada artículo con su precio final, IVA incluido</strong> — el PVP que manda el proveedor, en la lista.</span></label>
          </div>

          {* Cómo se llama en la tienda: la etiqueta de la ficha, los listados y la cesta *}
          <div class="kpc-modos" data-kpc-solo="special_price" role="radiogroup" aria-label="Cómo se llama">
            <p class="kpc-pregunta">¿Cómo se llama en la tienda?</p>
            <label class="kpc-check"><input type="radio" name="etiqueta" value="" {if $v.etiqueta === ''}checked{/if}>
              <span><strong>Special Price</strong> — la etiqueta crema de siempre.</span></label>
            <label class="kpc-check"><input type="radio" name="etiqueta" value="liquidacion" {if $v.etiqueta === 'liquidacion'}checked{/if}>
              <span><strong>Liquidación</strong> — en rojo: un precio agresivo para acabar un stock. Va bien con las unidades por artículo (paso 3). En la landing va al cajón «Outlet y liquidación», con todas las marcas, sin banner propio.</span></label>
            <label class="kpc-check"><input type="radio" name="etiqueta" value="outlet" {if $v.etiqueta === 'outlet'}checked{/if}>
              <span><strong>Outlet</strong> — en gris oscuro: fin de lote, últimas unidades. Mismo cajón de la landing que la liquidación.</span></label>
            <label class="kpc-check"><input type="radio" name="etiqueta" value="bajada" {if $v.etiqueta === 'bajada'}checked{/if}>
              <span><strong>Bajada de precio</strong> — una rebaja que no es una campaña: ni Special Price ni cupón.</span></label>
            <small class="kpc-nota">Por dentro es lo mismo (el precio ya rebajado, tachado, «hasta el…», precio de oferta en Google); cambia el nombre y el color en la ficha, los listados, el buscador y la cesta.</small>
          </div>

          <div class="kpc-fila">
            <div class="kpc-campo kpc-campo--pct" data-kpc-solo="cupon segunda_unidad special_price">
              <label for="kpc-pct">
                <span data-kpc-solo="cupon">Descuento</span>
                <span data-kpc-solo="segunda_unidad">Descuento en la 2ª unidad</span>
                <span data-kpc-solo="special_price" data-kpc-modo="pct">Descuento</span>
                <span data-kpc-solo="special_price" data-kpc-modo="pct_articulo precio_articulo">% para los que no traen el suyo</span>
              </label>
              <div class="input-group">
                <input type="number" class="form-control" id="kpc-pct" name="porcentaje" min="0" max="90" step="0.5" value="{$v.pct_texto|escape:'html':'UTF-8'}" placeholder="—">
                <span class="input-group-addon">%</span>
              </div>
              <small class="kpc-nota" data-kpc-solo="segunda_unidad">En la cesta es la mitad por cada par: 30 % en la 2ª son 15 % en las dos.</small>
              <small class="kpc-nota" data-kpc-solo="special_price" data-kpc-modo="pct_articulo precio_articulo">Opcional. Con 0, solo entran los de la lista con su valor.</small>
            </div>
            <div class="kpc-campo" data-kpc-solo="cupon">
              <label for="kpc-codigo">Código</label>
              <input type="text" class="form-control kpc-codigo" id="kpc-codigo" name="codigo" value="{$v.codigo|escape:'html':'UTF-8'}" maxlength="20" autocomplete="off" spellcheck="false" placeholder="Ej.: BOSKE10">
              <small class="kpc-nota">Lo que escribe el cliente: mayúsculas y números, sin espacios, 20 como mucho (Google no admite más).</small>
            </div>
            <div class="kpc-campo">
              <label for="kpc-nombre">Nombre <span class="kpc-opcional">(opcional)</span></label>
              <input type="text" class="form-control" id="kpc-nombre" name="nombre" value="{$v.nombre|escape:'html':'UTF-8'}" maxlength="128" placeholder="Si lo dejas vacío, se pone solo">
              <small class="kpc-nota" data-kpc-solo="cupon segunda_unidad">El que sale en la cesta y en el pedido.</small>
              <small class="kpc-nota" data-kpc-solo="special_price">Para reconocerla en la lista («Royal Canin sacos, septiembre»).</small>
              <small class="kpc-nota" data-kpc-solo="regalo">Para reconocerla en la lista («Boske 12 + 3 kg, noviembre»). En la cesta y en el pedido el vale dice «Regalo: 1 × …».</small>
            </div>
          </div>
        </div>
      </section>

      <div class="kpc-tras-tipo">
      {* 2 · A qué productos: primero cómo se eligen; luego solo lo de esa manera *}
      <section class="kpc-paso">
        <h3 class="kpc-paso__titulo"><span class="kpc-num">2</span> A qué productos</h3>
        <div class="kpc-tipos kpc-tipos--dos" role="radiogroup" aria-label="Cómo se eligen los productos">
          <label class="kpc-tipo" data-kpc-modo="pct">
            <input type="radio" name="elegir" value="marca" {if $v.elegir === 'marca'}checked{/if}>
            <span class="kpc-tipo__caja">
              <span class="kpc-tipo__icono kpc-tipo__icono--como" aria-hidden="true"><i class="icon-tags"></i></span>
              <strong>Por marca</strong>
              <small>La marca entera o, dentro de ella, sus gamas y sus formatos: «Boske entera», «Royal Canin razas gato, sacos de 10 kg».</small>
            </span>
          </label>
          <label class="kpc-tipo">
            <input type="radio" name="elegir" value="lista" {if $v.elegir === 'lista'}checked{/if}>
            <span class="kpc-tipo__caja">
              <span class="kpc-tipo__icono kpc-tipo__icono--como" aria-hidden="true"><i class="icon-list-ul"></i></span>
              <strong>Con una lista</strong>
              <small>La del proveedor: EAN, referencias o ids, pegados o en su Excel. Con el % o el precio de cada uno si hace falta.</small>
            </span>
          </label>
        </div>
        <p class="kpc-elige-como">Elige cómo: aparecerá solo lo de esa manera, y después lo demás.</p>

        {* Por marca: la marca, sus gamas y sus formatos (gamas y formatos los pinta el JS con los datos de cada marca) *}
        <div class="kpc-modo-bloque" data-kpc-elegir="marca" data-kpc-modo="pct">
          <div class="kpc-campo">
            <label for="kpc-buscar-marcas">Marca</label>
            <div class="kpc-picker" data-kpc-picker="marcas" data-kpc-nombre="marcas[]" data-kpc-valores="{$v.marcas_csv|escape:'html':'UTF-8'}">
              <div class="kpc-chips" data-kpc-chips></div>
              <input type="search" class="form-control" id="kpc-buscar-marcas" placeholder="Escribe una marca: «royal», «farmina»…" autocomplete="off" data-kpc-buscar>
              <ul class="kpc-opciones" data-kpc-opciones hidden></ul>
            </div>
          </div>
          <div class="kpc-afinar" data-kpc-afinar hidden>
            <div data-kpc-gamas data-kpc-marcadas="{$v.categorias_csv|escape:'html':'UTF-8'}"></div>
            <div class="kpc-fichas-caja" data-kpc-caja-tamanos hidden>
              <p class="kpc-pregunta">Formatos <small>— sin marcar ninguno, todos</small></p>
              <div data-kpc-tamanos></div>
              <small class="kpc-nota">El formato es el del suelto y vale para sus packs: «Sacos 12 kg» es el saco y el «Pack 2 x 12 Kg».</small>
            </div>
          </div>
          <input type="hidden" name="tamanos_ofrecidos" value="" data-kpc-ofrecidos="tamanos">
          <script type="application/json" id="kpc-tamanos-elegidos">{$v.tamanos_json nofilter}</script>
          {if $v.antiguos}
            {* Filtros de antes de la 1.6 (características o categorías de la tienda): se mantienen tal cual. *}
            <p class="kpc-nota">Lleva además, de antes: <strong>{$v.antiguos|escape:'html':'UTF-8'}</strong>. Se mantiene.</p>
            {foreach $v.caracteristicas as $id}<input type="hidden" name="caracteristicas[]" value="{$id|intval}">{/foreach}
            {foreach $v.generales as $id}<input type="hidden" name="categorias[]" value="{$id|intval}">{/foreach}
            {if $v.lista}<input type="hidden" name="lista_de_antes" value="{$v.lista|escape:'html':'UTF-8'}">{/if}
          {/if}
        </div>

        {* Con una lista *}
        <div class="kpc-modo-bloque" data-kpc-elegir="lista">
          <div class="kpc-campo kpc-lista">
            <label for="kpc-lista">Lista de artículos <span class="kpc-opcional">(la del proveedor, o la tuya)</span></label>
            <textarea class="form-control" id="kpc-lista" name="lista" rows="6" spellcheck="false" placeholder="Pega aquí EAN, referencias, ids (7214), «producto-formato» (7214-90684) o enlaces de la web. Uno por línea, o una columna copiada de Excel." data-kpc-placeholder-valor="Pega el EAN (o la referencia) y su valor, uno por línea: dos columnas copiadas de Excel, o «8710255120850;13» o «8710255120850 12,99».">{$v.lista|escape:'html':'UTF-8'}</textarea>
            <div class="kpc-lista__pie">
              <select name="lista_tipo" class="form-control kpc-lista__tipo" aria-label="Qué hay en la lista">
                <option value="auto" {if $v.lista_tipo === 'auto'}selected{/if}>Lo detecta sola</option>
                <option value="codigo" {if $v.lista_tipo === 'codigo'}selected{/if}>EAN o referencias</option>
                <option value="id" {if $v.lista_tipo === 'id'}selected{/if}>IDs de producto</option>
                <option value="combinacion" {if $v.lista_tipo === 'combinacion'}selected{/if}>producto-formato</option>
              </select>
              <label class="btn btn-default kpc-fichero">
                <i class="icon-upload"></i> Subir Excel o CSV
                <input type="file" name="lista_fichero" accept=".xlsx,.xls,.ods,.csv,.txt,.tsv" data-kpc-fichero>
              </label>
              <span class="kpc-fichero__nombre" data-kpc-fichero-nombre></span>
            </div>
            <small class="kpc-nota">Con varias columnas (EAN, nombre, precio…) se queda con la que encuentra. Lo que no encuentre te lo dice uno por uno. El EAN del saco es del saco, aunque su pack lo comparta.</small>
            <small class="kpc-nota kpc-nota--valor" data-kpc-solo="special_price" data-kpc-modo="pct_articulo">Cada artículo con su %: «13», «13 %» o «0,13» (como lo deja Excel). Se busca sola la columna del descuento.</small>
            <small class="kpc-nota kpc-nota--valor" data-kpc-solo="special_price" data-kpc-modo="precio_articulo">Cada artículo con su precio final con IVA: «12,99», «12,99 €» o «1.234,56». Se busca sola la columna del precio (mejor si su cabecera dice «promo» u «oferta»).</small>
          </div>
        </div>

        {* Lo que vale para las dos maneras *}
        <div class="kpc-tras-elegir">
          <div class="kpc-checks">
            <label class="kpc-check" data-kpc-solo="cupon special_price">
              <input type="checkbox" name="incluir_packs" value="1" {if $v.incluir_packs}checked{/if}>
              <span><strong>Incluir los packs</strong> — el «Pack 2 x Saco de 12 Kg» del saco, o la caja doble de latas, con el mismo descuento que su suelto.</span>
            </label>
            <div class="kpc-fichas-caja kpc-solo-packs" data-kpc-elegir="marca" data-kpc-modo="pct" data-kpc-si-packs data-kpc-solo="cupon special_price" data-kpc-caja-packs hidden>
              <p class="kpc-pregunta">¿Solo algunos packs? <small>— sin marcar ninguno, los sueltos y todos sus packs; marcando, solo esos packs (sin los sueltos)</small></p>
              <div class="kpc-fichas" data-kpc-packs data-kpc-marcados="{$v.packs_csv|escape:'html':'UTF-8'}"></div>
            </div>
            <p class="kpc-nota" data-kpc-solo="segunda_unidad">La 2ª unidad entra solo en los sueltos: el pack ya es el descuento por cantidad.</p>
            <p class="kpc-nota" data-kpc-solo="regalo">Lo que se compra para llevarse el regalo. Los sueltos siempre; los packs, si arriba has marcado «los packs también regalan». Entran también los artículos con Special Price (su tarjeta de siempre lo mantiene; la de regalo va al precio habitual). Un artículo que ya tenga cupón o 2ª unidad se queda con esa: un artículo, una promoción.</p>
          </div>

          <details class="kpc-excluir" {if $v.excluir}open{/if} id="kpc-excluir">
            <summary>Dejar fuera algunos artículos {if $v.excluir}<span class="kpc-contador">con lista</span>{/if}</summary>
            <textarea class="form-control" name="excluir" rows="3" spellcheck="false" placeholder="Igual que la lista: EAN, referencias, ids o enlaces. Lo de aquí no entra nunca. El botón «Quitar» de los productos lo apunta aquí solo." data-kpc-excluir>{$v.excluir|escape:'html':'UTF-8'}</textarea>
          </details>

          <div class="kpc-checks">
            <label class="kpc-check">
              <input type="checkbox" name="sin_outlet" value="1" {if $v.sin_outlet}checked{/if}>
              <span><strong>Fuera los formatos OUTLET</strong> — ya van rebajados.</span>
            </label>
            <label class="kpc-check" data-kpc-solo="cupon segunda_unidad">
              <input type="checkbox" name="excluir_especial" value="1" {if $v.excluir_especial}checked{/if}>
              <span><strong>Respetar el Special Price</strong> — lo que ya tiene Special Price se queda sin esta promo.</span>
            </label>
            <p class="kpc-nota" data-kpc-solo="special_price">Un Special Price no pisa nunca otro: lo que ya tiene uno en esas fechas se queda como está (y te lo dice).</p>
          </div>

          <button type="submit" name="accion" value="comprobar" class="btn btn-default kpc-comprobar">
            <i class="icon-search"></i> Comprobar a qué llega <small>(no guarda nada)</small>
          </button>
        </div>
      </section>

      <div class="kpc-tras-elegir">
      {* 3 · Cuándo *}
      <section class="kpc-paso">
        <h3 class="kpc-paso__titulo"><span class="kpc-num">3</span> Cuándo</h3>
        <div class="kpc-fila">
          <div class="kpc-campo">
            <label for="kpc-desde">Desde</label>
            <div class="kpc-fecha">
              <input type="date" class="form-control" id="kpc-desde" name="fecha_desde" value="{$v.fecha_desde|escape:'html':'UTF-8'}">
              <input type="time" class="form-control" name="hora_desde" value="{$v.hora_desde|escape:'html':'UTF-8'}" aria-label="Hora de inicio (opcional)">
            </div>
            <small class="kpc-nota">Vacío: desde que se guarda. Sin hora: a las 00:00.</small>
          </div>
          <div class="kpc-campo">
            <label for="kpc-hasta">Hasta</label>
            <div class="kpc-fecha">
              <input type="date" class="form-control" id="kpc-hasta" name="fecha_hasta" value="{$v.fecha_hasta|escape:'html':'UTF-8'}">
              <input type="time" class="form-control" name="hora_hasta" value="{$v.hora_hasta|escape:'html':'UTF-8'}" aria-label="Hora de fin (opcional)">
            </div>
            <small class="kpc-nota" data-kpc-solo="cupon segunda_unidad regalo">Vacío: hasta que se apague. Sin hora: a las 23:59.</small>
            <small class="kpc-nota" data-kpc-solo="special_price"><strong>Obligatorio</strong> en un Special Price: la ficha dice «hasta el…». Sin hora: a las 23:59.</small>
          </div>
        </div>
        <div class="kpc-atajos" role="group" aria-label="Duración">
          <span>Duración:</span>
          <button type="button" class="btn btn-default btn-sm" data-kpc-dias="7">1 semana</button>
          <button type="button" class="btn btn-default btn-sm" data-kpc-dias="15">15 días</button>
          <button type="button" class="btn btn-default btn-sm" data-kpc-dias="30">1 mes</button>
          <button type="button" class="btn btn-default btn-sm" data-kpc-dias="fin">Hasta fin de mes</button>
          <button type="button" class="btn btn-default btn-sm" data-kpc-dias="0" data-kpc-solo="cupon segunda_unidad regalo">Sin fin</button>
        </div>

        {* El cupo: por cantidad, además de por tiempo (2.3.0). Un bloque aparte: el número a la izquierda, qué cuenta a su lado, y debajo sus dos opciones y cómo va. *}
        <div class="kpc-bloque kpc-cupo">
          <label class="kpc-bloque__titulo" for="kpc-cupo">Cupo de unidades <span class="kpc-opcional">(opcional: además de las fechas, un tope de unidades)</span></label>
          <div class="kpc-cupo__entrada">
            <div class="input-group kpc-cupo__numero">
              <input type="number" class="form-control" id="kpc-cupo" name="cupo" min="0" step="1" value="{if $v.cupo > 0}{$v.cupo|intval}{/if}" placeholder="sin cupo">
              <span class="input-group-addon">uds.</span>
            </div>
            <p class="kpc-cupo__que">
              <strong data-kpc-solo="special_price">Unidades que se venden a este precio.</strong>
              <strong data-kpc-solo="cupon">Unidades a las que el cupón descuenta.</strong>
              <strong data-kpc-solo="segunda_unidad">Unidades rebajadas (la 2ª de cada par).</strong>
              <strong data-kpc-solo="regalo">Regalos: los que pone el fabricante.</strong>
              Al llegar, la promoción <strong>se acaba sola</strong> aunque no haya llegado la fecha: desaparece de la ficha, los listados, la cesta, la landing y Google, y los precios vuelven. Cuenta los pedidos hechos; lo que está en una cesta queda reservado una hora. Lo que no cabe se vende a precio habitual (o sin descuento), y la cesta lo dice.
            </p>
          </div>
          <div class="kpc-cupo__opciones">
            <label class="kpc-check">
              <input type="checkbox" name="cupo_ficha" value="1" {if $v.cupo_ficha}checked{/if}>
              <span><strong>Decir en la ficha cuántas quedan</strong> — «Quedan 12 unidades»: cuando quedan pocas, vende más.</span>
            </label>
            <label class="kpc-check" data-kpc-solo="cupon segunda_unidad special_price">
              <input type="checkbox" name="cupo_lista" value="1" {if $v.cupo_lista}checked{/if}>
              <span><strong>Las unidades van en la lista, artículo a artículo</strong> — lo de una liquidación: 12 sacos de este lote, 5 de aquel. En el paso 2 se pegan dos columnas, el EAN (o la referencia) y sus unidades (con «Unidades» de cabecera, o como última columna; con un % por artículo: EAN, %, unidades). Cada artículo se agota por su cuenta y se va de la tienda; la promoción, cuando no le queda ninguno. El cupo de arriba, si se pone, es el tope de todos juntos.</span>
            </label>
          </div>
          {if $v.cupo_estado}
            <p class="kpc-cupo__estado{if $v.cupo_estado.agotado} is-agotada{/if}">
              {if $v.cupo_estado.agotado}<strong>Agotada el {$v.cupo_estado.agotado|escape:'html':'UTF-8'}.</strong>{/if}
              {if $v.cupo_estado.lista}{$v.cupo_estado.articulos|intval} artículos con sus unidades ({$v.cupo_estado.cupo|intval} en total): {/if}<strong>{$v.cupo_estado.vendidas|intval}</strong> vendidas · <strong>{$v.cupo_estado.reservadas|intval}</strong> en cestas ahora · quedan <strong>{$v.cupo_estado.quedan|intval}</strong>{if !$v.cupo_estado.lista} de {$v.cupo_estado.cupo|intval}{/if}.{if $v.cupo_estado.agotados} {$v.cupo_estado.agotados|intval} artículos ya agotados (fuera de la tienda hasta que se reabra o se les den más unidades).{/if}
              {if $v.cupo_estado.agotado}Al guardar con un cupo mayor que lo vendido (o sin cupo) se vuelve a abrir.{/if}
            </p>
          {/if}
        </div>

        <div class="kpc-encendida">
          <label class="kpc-check">
            <input type="checkbox" name="activo" value="1" {if $v.activo}checked{/if}>
            <span><strong>Encendida</strong> — apagada no descuenta ni se ve (un Special Price devuelve el precio de siempre al momento), pero se queda guardada.</span>
          </label>
        </div>
      </section>

      {* 4 · Cómo llega al cliente *}
      <section class="kpc-paso">
        <h3 class="kpc-paso__titulo"><span class="kpc-num">4</span> Cómo llega al cliente</h3>
        <p class="kpc-ayuda" data-kpc-solo="special_price">Se ve solo: precio rebajado en la ficha y en los listados, el «Precio habitual» tachado, «hasta el…» y la etiqueta «Special Price», sola: ni el % ni ninguna otra. Al terminar, todo vuelve a como estaba. Nada que pulsar.</p>
        <p class="kpc-ayuda" data-kpc-solo="regalo">Nada que pulsar: en la ficha sale la tarjeta «12 kg + 3 kg gratis» (o «12 kg + Regalo») al lado de la de siempre, con el precio habitual y el €/kg de los dos sacos. Al elegirla y añadir, el regalo entra a 0 € en su propia línea («1 Gift»). Con un accesorio, unas latas o un snack, debajo del precio sale «Te llevas de regalo…» con su foto. En los listados, la etiqueta «+ 3 kg gratis» (o «+ regalo»); en la cesta, el vale «Regalo: 1 × …», con papelera para volver a la tarjeta de siempre. Si el regalo se agota, entra el de reserva; si no hay, la tarjeta de regalo no sale y la cesta avisa.</p>
        <label class="kpc-check" data-kpc-solo="cupon segunda_unidad">
          <input type="checkbox" name="en_ficha" value="1" {if $v.en_ficha}checked{/if}>
          <span><strong>Se enseña en la ficha y en los listados</strong> — el bloque de la promo, la franja en los formatos y la etiqueta sobre la foto.</span>
        </label>

        <div data-kpc-solo="cupon">
          <p class="kpc-pregunta">¿Le llega ya puesto?</p>
          <div class="kpc-radios">
            <label class="kpc-check"><input type="radio" name="entra" value="no" {if $v.entra === 'no'}checked{/if}>
              <span><strong>No</strong> — lo pone el cliente: pulsándolo en la ficha o escribiendo el código.</span></label>
            <label class="kpc-check"><input type="radio" name="entra" value="todos" {if $v.entra === 'todos'}checked{/if}>
              <span><strong>Sí, a todo el mundo</strong> — como un precio rebajado, pero en la cesta. En la ficha de sus productos lo ve cualquiera, venga de donde venga (y solo en los suyos).</span></label>
            <label class="kpc-check"><input type="radio" name="entra" value="canal" {if $v.entra === 'canal'}checked{/if}>
              <span><strong>Solo si la visita llega de:</strong></span></label>
          </div>
          <div class="kpc-canales" data-kpc-canales>
            <label class="kpc-pastilla"><input type="checkbox" name="canales[]" value="shopping" {if isset($v.canal_marcado.shopping)}checked{/if}><span>Google (Shopping y anuncios)</span></label>
            <label class="kpc-pastilla"><input type="checkbox" name="canales[]" value="email" {if isset($v.canal_marcado.email)}checked{/if}><span>Email</span></label>
            <label class="kpc-pastilla"><input type="checkbox" name="canales[]" value="social" {if isset($v.canal_marcado.social)}checked{/if}><span>Redes (Meta, Instagram, TikTok)</span></label>
          </div>
          <small class="kpc-nota">Se recuerda de dónde llegó aunque luego navegue. Si el cliente lo quita, no vuelve a entrar solo; una visita nueva desde ese canal lo vuelve a poner.</small>
          <div class="kpc-enlace">
            <span>Enlace que lo deja puesto (para emails, redes, influencers):</span>
            <code data-kpc-enlace data-kpc-tienda="{$kpc.tienda|escape:'html':'UTF-8'}" data-kpc-parametro="{$kpc.parametro|escape:'html':'UTF-8'}">{$kpc.tienda|escape:'html':'UTF-8'}?{$kpc.parametro|escape:'html':'UTF-8'}={$v.codigo|default:'CODIGO'|escape:'html':'UTF-8'}</code>
            <button type="button" class="btn btn-default btn-sm" data-kpc-copiar="[data-kpc-enlace]">Copiar</button>
            <small class="kpc-nota">Vale en cualquier página: añade <code>?{$kpc.parametro|escape:'html':'UTF-8'}=CÓDIGO</code> (o <code>&amp;{$kpc.parametro|escape:'html':'UTF-8'}=CÓDIGO</code> si ya lleva «?»).</small>
          </div>
        </div>
        <p class="kpc-ayuda" data-kpc-solo="segunda_unidad">La 2ª unidad no se pone ni se quita: entra sola en la cesta al llevar dos del mismo formato, y la cesta avisa «Añade 1 ud. más de… y te ahorras…».</p>
      </section>

      {* 5 · Google y precios *}
      <section class="kpc-paso">
        <h3 class="kpc-paso__titulo"><span class="kpc-num">5</span> Google y precios</h3>
        <label class="kpc-check" data-kpc-solo="cupon segunda_unidad regalo">
          <input type="checkbox" name="en_merchant" value="1" {if $v.en_merchant}checked{/if}>
          <span><strong>Anunciar en Google Shopping</strong> — sale en el fichero de promociones de Merchant. Apágalo en un cupón solo para email.</span>
        </label>
        <p class="kpc-ayuda" data-kpc-solo="special_price">A Google va solo, como <strong>precio de oferta</strong> (precio tachado y «OFERTA»), en el fichero «precio-oferta». Nunca como promoción: Google lo rechazaría.</p>
        <p class="kpc-nota" data-kpc-solo="regalo">A Google va como promoción de «regalo» (free gift), con lo que se regala en la descripción.</p>
        <div class="kpc-fila">
          <div class="kpc-campo kpc-campo--ancho" data-kpc-solo="cupon segunda_unidad regalo">
            <label for="kpc-titulo">Título en Google <span class="kpc-opcional">(opcional)</span></label>
            <input type="text" class="form-control" id="kpc-titulo" name="merchant_titulo" value="{$v.merchant_titulo|escape:'html':'UTF-8'}" maxlength="60" placeholder="Se pone solo: «10 % de descuento en Boske con el codigo BOSKE10»">
            <small class="kpc-nota">60 caracteres como mucho, sin MAYÚSCULAS ni «!» (Google lo rechaza). <span data-kpc-cuenta></span></small>
          </div>
          <div class="kpc-campo" data-kpc-solo="cupon segunda_unidad regalo">
            <label for="kpc-mid">Id en Merchant <span class="kpc-opcional">(opcional)</span></label>
            <input type="text" class="form-control" id="kpc-mid" name="merchant_id" value="{$v.merchant_id|escape:'html':'UTF-8'}" maxlength="64" placeholder="código_fecha">
            <small class="kpc-nota">Google no deja repetirlo nunca.</small>
          </div>
        </div>
        <label class="kpc-check">
          <input type="checkbox" name="bloquear_priceshape" value="1" {if $v.bloquear_priceshape}checked{/if}>
          <span><strong>Bloquear PriceShape</strong> — aviso para la herramienta de precios: que no mueva estos precios mientras dure.</span>
        </label>
      </section>

      {* 6 · Lo que añaden otros módulos (la landing de promociones: dirección, banner, foto, campaña) *}
      {if $kpc.hook_formulario}{$kpc.hook_formulario nofilter}{/if}

      <div class="kpc-barra">
        <p class="kpc-barra__resumen" data-kpc-resumen aria-live="polite"></p>
        <div class="kpc-barra__botones">
          <button type="submit" name="accion" value="comprobar" class="btn btn-default">Comprobar</button>
          <button type="submit" name="accion" value="guardar" class="btn btn-primary"><i class="icon-save"></i> Guardar</button>
        </div>
      </div>
      </div>{* /.kpc-tras-elegir *}
      </div>{* /.kpc-tras-tipo *}
    </div>
  </form>

  {* Los productos de la promoción que está en el formulario (al comprobar o al cambiarla) *}
  {if $kpc.previa}
    <div class="panel kpc-previa" id="kpc-previa">
      <div class="panel-heading kpc-cabeza">
        <i class="icon-eye"></i> Productos de {if $v.id}«{$v.codigo|escape:'html':'UTF-8'}»{else}la promoción nueva{/if}
        {if $v.id}<a class="btn btn-default btn-sm kpc-cabeza__nueva" href="{$kpc.url|escape:'html':'UTF-8'}&amp;editar={$v.id|intval}#kpc-previa" title="Volver a mirar cómo está ahora">Volver a mirar</a>{/if}
      </div>
      {include file=$kpc.plantillas|cat:'productos.tpl' pr=$kpc.previa pv=$v url=$kpc.url solo_ver=false}
    </div>
  {/if}

  {* Google Merchant: se configura una vez, así que va plegado *}
  <details class="panel kpc-merchant">
    <summary class="kpc-merchant__titulo"><i class="icon-google"></i> Google Merchant <small>— las direcciones de los tres ficheros, para programarlas una vez en Merchant</small></summary>
    <p>Estos tres ficheros salen de las promociones de arriba. Lo mejor es pegar la dirección en Merchant una sola vez y programar la obtención: así cada cambio llega solo, sin subir nada a mano.</p>
    <div class="kpc-tabla-caja">
      <table class="table">
        <thead><tr><th>Fichero</th><th>Dónde va en Merchant</th><th>Dirección para programar</th><th></th></tr></thead>
        <tbody>
          {foreach $kpc.merchant as $m}
            <tr>
              <td><strong>{$m.fichero|escape:'html':'UTF-8'}</strong><br><small>{$m.que|escape:'html':'UTF-8'}</small></td>
              <td><small>{$m.donde|escape:'html':'UTF-8'}</small></td>
              <td><input type="text" class="form-control" readonly onclick="this.select()" value="{$m.url|escape:'html':'UTF-8'}"></td>
              <td class="text-right"><a class="btn btn-default" target="_blank" rel="noopener" href="{$m.url|escape:'html':'UTF-8'}&amp;descargar=1">Descargar</a></td>
            </tr>
          {/foreach}
        </tbody>
      </table>
    </div>
    <p class="kpc-nota">La dirección lleva una clave: quien la tenga ve qué promociones hay antes de que salgan. No se publica en ningún sitio.</p>
  </details>
</div>
<script type="application/json" id="kpc-datos">{$kpc.datos nofilter}</script>
