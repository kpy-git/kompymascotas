{**
 * Kompy · Landing de promociones. Ordenada como se usa: cómo funciona (y el estado), las
 * promociones en la landing, las campañas, las imágenes y el mantenimiento. Misma pinta que la
 * pantalla de Kompy · Promociones (pasos numerados, fichas, chips), para que se vea que son la
 * misma cosa en dos sitios.
 *}
<div class="kpl-admin">
  {if $kpl.aviso}<div class="alert alert-success">{$kpl.aviso|escape:'html':'UTF-8'}</div>{/if}
  {if $kpl.error}<div class="alert alert-danger">{$kpl.error|escape:'html':'UTF-8'}</div>{/if}

  {* 0 · Cómo funciona y cómo está *}
  <div class="panel kpl-panel">
    <div class="panel-heading kpl-cabeza"><i class="icon-storefront"></i> Landing de promociones <span class="badge">v{$kpl.version}</span>
      <span class="kpl-cabeza__btns">
        <a class="btn btn-default btn-sm" href="{$kpl.url_landing|escape:'html':'UTF-8'}" target="_blank" rel="noopener"><i class="icon-external-link"></i> Ver /promociones</a>
        <form method="post" action="{$kpl.url|escape:'html':'UTF-8'}" class="kpl-inline"><button class="btn btn-primary btn-sm" name="kplSincronizar" value="1"><i class="icon-refresh"></i> Sincronizar ahora</button></form>
      </span>
    </div>
    <div class="kpl-flujo">
      <div class="kpl-flujo__paso">
        <span class="kpl-num">1</span>
        <strong>Creas la promoción</strong>
        <p>En <a href="{$kpl.url_promociones|escape:'html':'UTF-8'}">Catálogo → Kompy · Promociones</a>: qué es, a qué productos, cuándo, cómo llega al cliente y Google. Su <b>paso 6, «En la landing»</b>, es de este módulo: dirección, título, foto, orden y campañas, todo opcional.</p>
      </div>
      <div class="kpl-flujo__flecha" aria-hidden="true">→</div>
      <div class="kpl-flujo__paso kpl-flujo__paso--auto">
        <span class="kpl-num">2</span>
        <strong>Esto se hace solo al guardar</strong>
        <p>Y cada vez que una promoción empieza o termina: la <b>categoría</b> bajo «Promociones», el <b>logo</b> y el <b>saco</b> sin fondo, el <b>banner</b> con sus textos, la foto de las <b>tarjetas</b>, <b>/promociones</b>, el bloque en la <b>landing de la marca</b> y, si hay campaña, las etiquetas y la piel de toda la tienda.</p>
      </div>
      <div class="kpl-flujo__flecha" aria-hidden="true">→</div>
      <div class="kpl-flujo__paso">
        <span class="kpl-num">3</span>
        <strong>Aquí se mira y se afina</strong>
        <p>Cómo ha quedado cada promoción, las <b>campañas</b> (Black Friday…), las <b>imágenes</b> (motor y biblioteca) y el mantenimiento. Lo que es de una promoción se cambia en su paso 6, desde la lista de abajo.</p>
      </div>
    </div>
    {if $kpl.sin_kpycoupons}
      <p class="alert alert-warning kpl-ayuda"><b>Kompy · Promociones (kpycoupons) no contesta</b> al hook <code>{$kpl.hook_promociones|escape:'html':'UTF-8'}</code>: hace falta su versión 3.0.0 o posterior, encendida. Mientras, la lista de promociones de abajo sale vacía y «Sincronizar ahora» no toca nada (ni las categorías ni el paso 6 de cada promoción).</p>
    {/if}
    <div class="kpl-cifras">
      <div class="kpl-cifra{if $kpl.pendiente} kpl-cifra--ojo{else} kpl-cifra--si{/if}"><strong>{if $kpl.pendiente}pendiente{elseif $kpl.hace}{$kpl.hace}{else}nunca{/if}</strong><span>última sincronización{if $kpl.fecha && !$kpl.pendiente} · {$kpl.fecha}{/if}{if $kpl.pendiente} · se hará en la próxima visita a la tienda{/if}</span></div>
      <div class="kpl-cifra"><strong>{$kpl.n_landing}</strong><span>promociones en la landing ahora</span></div>
      <div class="kpl-cifra"><strong>{$kpl.cuentas.programada}</strong><span>programadas (saldrán solas)</span></div>
      <div class="kpl-cifra"><strong>{$kpl.n_categorias}</strong><span>categorías bajo «Promociones» (#{$kpl.raiz})</span></div>
      <div class="kpl-cifra"><strong>{$kpl.n_imagenes}</strong><span>logos y sacos recortados</span></div>
      {if $kpl.campana_viva}<div class="kpl-cifra kpl-cifra--negra"><strong>{$kpl.campana_viva|escape:'html':'UTF-8'}</strong><span>campaña en marcha ahora</span></div>{/if}
    </div>
  </div>

  {* 1 · Las promociones en la landing *}
  <div class="panel kpl-panel">
    <div class="panel-heading kpl-cabeza"><i class="icon-tags"></i> Las promociones en la landing
      <span class="kpl-cuentas"><span class="kpl-chip kpl-chip--si">{$kpl.cuentas.viva} en marcha</span> <span class="kpl-chip kpl-chip--info">{$kpl.cuentas.programada} programadas</span> <span class="kpl-chip kpl-chip--gris">{$kpl.cuentas.terminada + $kpl.cuentas.apagada} terminadas o apagadas</span>{if $kpl.cuentas.fuera} <span class="kpl-chip kpl-chip--gris">{$kpl.cuentas.fuera} en marcha fuera de la landing</span>{/if}</span>
      <a class="btn btn-default btn-sm kpl-cabeza__nueva" href="{$kpl.url_promociones|escape:'html':'UTF-8'}">+ Nueva promoción (en Kompy · Promociones)</a>
    </div>
    <div class="kpl-cuerpo">
      <p class="kpl-ayuda">Cada promoción de Kompy · Promociones y lo que esta landing hace con ella. <b>Editar</b> abre la promoción allí, con su paso 6. Una categoría se crea la primera vez que una promoción la necesita y <b>no se borra nunca</b>: se vacía y se apaga cuando no hay promoción, y la dirección sigue contestando («esta promoción ha terminado»).</p>
      <div class="kpl-tabla-caja">
        <table class="table kpl-tabla">
          <thead><tr><th>Estado</th><th>Promoción</th><th>Banner en la landing</th><th>Dirección y categoría</th><th>Paso 6</th><th></th></tr></thead>
          <tbody>
          {foreach $kpl.promos as $pr}
            <tr class="kpl-fila--{$pr.estado}">
              <td class="kpl-nowrap">
                <span class="kpl-chip kpl-chip--{if $pr.estado == 'viva'}si{elseif $pr.estado == 'programada'}info{else}gris{/if}">{if $pr.estado == 'viva'}en marcha{elseif $pr.estado == 'programada'}programada{elseif $pr.estado == 'apagada'}apagada{else}terminada{/if}</span>
                <small class="kpl-gris">{if $pr.desde}{$pr.desde}{/if}{if $pr.hasta} → {$pr.hasta}{elseif $pr.estado != 'terminada'} → sin fin{/if}</small>
              </td>
              <td>
                <span class="kpl-tipo-mini kpl-tipo-mini--{$pr.tipo}">{$pr.nombre_tipo|escape:'html':'UTF-8'}{if $pr.pct > 0} −{$pr.pct} %{/if}</span>
                <strong class="kpl-bloque">{$pr.nombre|default:$pr.codigo|escape:'html':'UTF-8'}</strong>
                <small class="kpl-gris">{$pr.codigo|escape:'html':'UTF-8'} · {$pr.formatos} artículos</small>
              </td>
              <td class="kpl-banner-celda">
                {if $pr.fuera}
                  <small class="kpl-gris">fuera de la landing: {$pr.fuera|escape:'html':'UTF-8'}</small>
                {elseif $pr.en_landing}
                  <span class="kpl-thumbs">{if $pr.logo}<img src="{$pr.logo|escape:'html':'UTF-8'}" alt="" class="kpl-thumb-logo">{/if}{foreach $pr.imagenes as $im}<img src="{$im|escape:'html':'UTF-8'}" alt="" class="kpl-thumb-saco">{/foreach}</span>
                  <small class="kpl-bloque">{$pr.titulo|escape:'html':'UTF-8'} · {$pr.n} productos</small>
                {elseif $pr.estado == 'viva'}
                  <small class="kpl-gris">{if $pr.clave}en marcha pero sin productos a la venta (se los llevó otra promoción){else}aún sin sincronizar{/if}</small>
                {elseif $pr.estado == 'programada'}
                  <small class="kpl-gris">saldrá sola el {$pr.desde}: categoría preparada, banner al empezar</small>
                {else}
                  <small class="kpl-gris">fuera de la landing</small>
                {/if}
              </td>
              <td>
                {if $pr.fuera}<small class="kpl-gris">—</small>{elseif $pr.url}<a href="{$pr.url|escape:'html':'UTF-8'}" target="_blank" rel="noopener" class="kpl-bloque kpl-url">/promociones/{$pr.clave|escape:'html':'UTF-8'}</a>{else}<small class="kpl-gris">se decide al sincronizar</small>{/if}
                {if $pr.id_category}<small class="kpl-gris">categoría #{$pr.id_category}</small>{/if}
              </td>
              <td>{if $pr.afinado}<span class="kpl-chip kpl-chip--ojo">a mano: {', '|implode:$pr.afinado|escape:'html':'UTF-8'}</span>{else}<span class="kpl-chip kpl-chip--gris">todo automático</span>{/if}</td>
              <td class="kpl-nowrap text-right"><a class="btn btn-default btn-sm" href="{$pr.url_editar|escape:'html':'UTF-8'}"><i class="icon-pencil"></i> Editar</a></td>
            </tr>
          {foreachelse}
            <tr><td colspan="6">Todavía no hay promociones. Se crean en <a href="{$kpl.url_promociones|escape:'html':'UTF-8'}">Kompy · Promociones</a>.</td></tr>
          {/foreach}
          </tbody>
        </table>
      </div>
      <p class="kpl-subtitulo">Las tres de siempre <small>— no dependen de ninguna promoción: se llenan solas con el catálogo</small></p>
      <div class="kpl-permanentes">
        {foreach $kpl.permanentes as $pe}
          <a class="kpl-permanente" href="{$pe.url|escape:'html':'UTF-8'}" target="_blank" rel="noopener">
            {if $pe.imagen}<img src="{$pe.imagen|escape:'html':'UTF-8'}" alt="">{/if}
            <strong>{$pe.nombre}</strong>
            <small>{$pe.n} productos · /promociones/{$pe.clave}{if $pe.id_category} · categoría #{$pe.id_category}{/if}</small>
            <span class="kpl-gris">{if $pe.tipo == 'kilos_gratis'}formatos «+ N kg gratis» (sale el saco Jirafa){elseif $pe.tipo == 'regalos'}packs con regalo{else}formatos outlet{/if}</span>
          </a>
        {/foreach}
      </div>
    </div>
  </div>

  {* 2 · Campañas *}
  <div class="panel kpl-panel" id="kpl-campanas">
    <div class="panel-heading kpl-cabeza"><i class="icon-calendar"></i> Campañas <small class="kpl-cabeza__sub">Black Friday, Cyber Monday, Reyes y las que vengan: una capa por encima de las promociones, con inicio y fin</small>
      <span class="kpl-cabeza__btns">
        {if $kpl.faltan_predefinidas}<form method="post" action="{$kpl.url|escape:'html':'UTF-8'}" style="display:inline"><button class="btn btn-default btn-sm" name="kplCrearPredefinidas" value="1" title="Black Friday, Cyber Monday y Reyes Magos, con sus fechas y su paleta"><i class="icon-magic"></i> Crear las predefinidas que faltan ({$kpl.faltan_predefinidas|intval})</button></form>{/if}
        <a class="btn btn-default btn-sm" href="{$kpl.url|escape:'html':'UTF-8'}&amp;campana=-1#kpl-campanas"><i class="icon-plus"></i> Nueva campaña</a>
      </span>
    </div>
    <div class="kpl-cuerpo">
      <p class="kpl-ayuda">Una campaña no toca las promociones: <b>se pone encima</b> entre sus fechas y se quita sola al terminar, sin nada que limpiar. Mientras dura: su dirección (<code>/blackfriday</code>) con su paleta y sus textos (y <code>/promociones</code> manda allí), una cinta arriba de toda la tienda, el bloque de la campaña en la portada de la tienda (en el sitio del carrusel, con sus productos), las etiquetas de campaña en los listados, la ficha y la cesta («Special Price» → «Black Friday −10 %») y el bloque de cada marca con su piel. Antes de empezar, su dirección dice «empieza en N días»; después, «ha terminado». Qué promociones entran: las marcadas con la campaña en su paso 6; si no hay ninguna marcada, todas las vivas. Si dos campañas coinciden en fecha, manda la que empezó más tarde. Las tres predefinidas (Black Friday, Cyber Monday, Reyes Magos) vienen con sus fechas de cada año y su paleta: se cambia lo que se quiera.</p>
      <div class="kpl-tabla-caja">
        <table class="table kpl-tabla kpl-tabla--campanas">
          <thead><tr><th>Campaña</th><th>Dirección</th><th>Fechas</th><th>Estado</th><th></th></tr></thead>
          <tbody>
          {foreach $kpl.campanas as $c}
            <tr{if $c.id_kpy_promo_campana == $kpl.campana.id_kpy_promo_campana} class="kpl-actual"{/if}>
              <td><strong>{$c.nombre|escape:'html':'UTF-8'}</strong> <small class="kpl-gris">piel {$c.piel}</small></td>
              <td><a href="{$c.url_front|escape:'html':'UTF-8'}" target="_blank" rel="noopener" class="kpl-url">/{$c.clave|escape:'html':'UTF-8'}</a></td>
              <td>{$c.fechas|escape:'html':'UTF-8'}</td>
              <td><span class="kpl-chip kpl-chip--{if $c.estado == 'viva'}si{elseif $c.estado == 'programada'}info{else}gris{/if}">{if $c.estado == 'viva'}en marcha{if $c.quedan_texto} · {$c.quedan_texto|lower}{/if}{elseif $c.estado == 'programada'}programada{if $c.faltan_texto} · {$c.faltan_texto|lower}{/if}{elseif $c.estado == 'terminada'}terminada{else}apagada{/if}</span></td>
              <td class="text-right"><a class="btn btn-default btn-sm" href="{$kpl.url|escape:'html':'UTF-8'}&amp;campana={$c.id_kpy_promo_campana}#kpl-campanas"><i class="icon-pencil"></i> Editar</a></td>
            </tr>
          {foreachelse}
            <tr><td colspan="5">Ninguna todavía.</td></tr>
          {/foreach}
          </tbody>
        </table>
      </div>

      <form method="post" action="{$kpl.url|escape:'html':'UTF-8'}" class="kpl-portada">
        <div class="kpl-portada__txt"><strong>La portada de la tienda, durante cualquier campaña</strong><small class="kpl-nota">Debajo del bloque de la campaña salen sus productos con más descuento, con estas marcas primero (y antes, el orden de marcas de la propia campaña). Cada marca pone como mucho la mitad, para que no salga todo de la misma.</small></div>
        <div class="kpl-portada__campos">
          <label>Marcas primero <input type="text" class="form-control" name="marcas_prioridad" value="{$kpl.marcas_prioridad|escape:'html':'UTF-8'}" placeholder="Boske, Royal Canin" maxlength="255"></label>
          <label>Productos <input type="number" class="form-control" name="portada_n" value="{$kpl.portada_n|intval}" min="0" max="16" step="1"></label>
          <button class="btn btn-default" name="kplGuardarPortada" value="1"><i class="icon-save"></i> Guardar</button>
        </div>
      </form>

      {assign var=c value=$kpl.campana}
      <form method="post" action="{$kpl.url|escape:'html':'UTF-8'}#kpl-campanas" class="kpl-form" id="kpl-campana">
        <input type="hidden" name="id_kpy_promo_campana" value="{$c.id_kpy_promo_campana|intval}">
        <h4 class="kpl-form__titulo">{if $c.id_kpy_promo_campana}Cambiar «{$c.nombre|escape:'html':'UTF-8'}»{else}Campaña nueva{/if}</h4>

        <section class="kpl-paso">
          <h3 class="kpl-paso__titulo"><span class="kpl-num">1</span> Qué campaña y cuándo</h3>
          <div class="kpl-fila">
            <div class="kpl-campo">
              <label for="kpl-nombre">Nombre</label>
              <input type="text" class="form-control" id="kpl-nombre" name="nombre" value="{$c.nombre|escape:'html':'UTF-8'}" placeholder="Black Friday" maxlength="64" required>
              <small class="kpl-nota">Como lo lee el cliente: en la cinta, en la portada, en las migas.</small>
            </div>
            <div class="kpl-campo">
              <label for="kpl-clave">Dirección</label>
              <div class="input-group"><span class="input-group-addon">/</span><input type="text" class="form-control" id="kpl-clave" name="clave" value="{$c.clave|escape:'html':'UTF-8'}" placeholder="blackfriday" pattern="[a-z0-9]+" maxlength="32" required></div>
              <small class="kpl-nota">Minúsculas y números, sin espacios ni guiones. Vive todo el año: antes dice cuándo empieza, después que terminó.</small>
            </div>
          </div>
          <div class="kpl-fila">
            <div class="kpl-campo">
              <label for="kpl-desde">Empieza</label>
              <div class="kpl-fecha"><input type="date" class="form-control" id="kpl-desde" name="desde_fecha" value="{$c.desde_fecha|escape:'html':'UTF-8'}" required><input type="time" class="form-control" name="desde_hora" value="{$c.desde_hora|escape:'html':'UTF-8'}" aria-label="Hora de inicio"></div>
              <small class="kpl-nota">Sin hora: a las 00:00.</small>
            </div>
            <div class="kpl-campo">
              <label for="kpl-hasta">Termina</label>
              <div class="kpl-fecha"><input type="date" class="form-control" id="kpl-hasta" name="hasta_fecha" value="{$c.hasta_fecha|escape:'html':'UTF-8'}" required><input type="time" class="form-control" name="hasta_hora" value="{$c.hasta_hora|escape:'html':'UTF-8'}" aria-label="Hora de fin"></div>
              <small class="kpl-nota">Sin hora: a las 23:59. A esa hora todo vuelve solo a como estaba.</small>
            </div>
          </div>
          <label class="kpl-check"><input type="checkbox" name="activa" value="1"{if $c.activa} checked{/if}> <span><strong>Activa</strong> — sin marcar, la campaña se guarda pero no existe: ni dirección, ni cinta, ni etiquetas.</span></label>
        </section>

        <section class="kpl-paso">
          <h3 class="kpl-paso__titulo"><span class="kpl-num">2</span> Cómo se ve</h3>
          <div class="kpl-pieles" role="radiogroup" aria-label="Piel">
            {foreach $kpl.pieles as $p}
              <label class="kpl-piel">
                <input type="radio" name="piel" value="{$p}"{if $p == $c.piel} checked{/if}>
                <span class="kpl-piel__caja kpl-piel__caja--{$p}">
                  <span class="kpl-piel__muestra kpl-piel__muestra--{$p}"><i></i><i></i><i></i></span>
                  <strong>{if $p == 'negra'}Oscura{else}Clara{/if}</strong>
                  <small>{if $p == 'negra'}Fondo oscuro (negro, azul noche…) y texto claro: Black Friday, Cyber Monday.{else}Fondo claro (crema, marfil…) y texto oscuro: Reyes, Navidad, verano…{/if}</small>
                </span>
              </label>
            {/foreach}
          </div>
          <p class="kpl-pregunta">Paleta <small>— tres colores que mandan en toda la campaña: la página, la cinta, el bloque de la portada, las etiquetas, la ficha y la cesta. Una de las preparadas, o los tuyos. Al cambiarlos, las etiquetas de abajo se ponen a juego solas.</small></p>
          <div class="kpl-paletas">
            {foreach $kpl.paletas as $clave => $p}
              <button type="button" class="kpl-paleta" data-kpl-paleta="{$p|@json_encode|escape:'html':'UTF-8'}" title="Poner la paleta de {$p.nombre|escape:'html':'UTF-8'}">
                <span class="kpl-paleta__muestra" style="background:{$p.colores.fondo}"><i style="background:{$p.colores.acento}"></i><i style="background:{$p.colores.acento2}"></i></span>
                <span class="kpl-paleta__txt"><strong>{$p.nombre|escape:'html':'UTF-8'}</strong><small>{if $p.piel == 'negra'}oscura{else}clara{/if} · {$p.colores.fondo} · {$p.colores.acento} · {$p.colores.acento2}</small></span>
              </button>
            {/foreach}
          </div>
          <div class="kpl-fila kpl-colores">
            <div class="kpl-campo"><label for="kpl-color-fondo">Fondo</label><span class="kpl-colorcampo"><input type="color" value="{$c.colores.fondo}" data-kpl-color="color_fondo" aria-label="Elegir el fondo"><input type="text" class="form-control kpl-color" id="kpl-color-fondo" name="color_fondo" value="{$c.colores.fondo}" maxlength="7" pattern="#[0-9A-Fa-f]{literal}{6}{/literal}"></span><small class="kpl-nota">La página de la campaña, la cinta y el bloque de la portada.</small></div>
            <div class="kpl-campo"><label for="kpl-color-acento">Acento</label><span class="kpl-colorcampo"><input type="color" value="{$c.colores.acento}" data-kpl-color="color_acento" aria-label="Elegir el acento"><input type="text" class="form-control kpl-color" id="kpl-color-acento" name="color_acento" value="{$c.colores.acento}" maxlength="7" pattern="#[0-9A-Fa-f]{literal}{6}{/literal}"></span><small class="kpl-nota">Píldoras, títulos de las tarjetas, Special Price y kilos gratis, el precio en la ficha.</small></div>
            <div class="kpl-campo"><label for="kpl-color-acento2">Acento secundario</label><span class="kpl-colorcampo"><input type="color" value="{$c.colores.acento2}" data-kpl-color="color_acento2" aria-label="Elegir el acento secundario"><input type="text" class="form-control kpl-color" id="kpl-color-acento2" name="color_acento2" value="{$c.colores.acento2}" maxlength="7" pattern="#[0-9A-Fa-f]{literal}{6}{/literal}"></span><small class="kpl-nota">Cupón y 2ª unidad, y los botones.</small></div>
          </div>
          <div class="kpl-fila">
            <div class="kpl-campo"><label for="kpl-titulo">Título de la portada</label><input type="text" class="form-control" id="kpl-titulo" name="titulo" value="{$c.titulo|escape:'html':'UTF-8'}" placeholder="Black Friday en Kompy" maxlength="120"></div>
            <div class="kpl-campo kpl-campo--ancho"><label for="kpl-texto">Texto de la portada, mientras dura</label><input type="text" class="form-control" id="kpl-texto" name="texto" value="{$c.texto|escape:'html':'UTF-8'}" placeholder="Las mejores marcas al mejor precio del año. Cuando se acaba, se acaba." maxlength="255"></div>
          </div>
          <div class="kpl-fila">
            <div class="kpl-campo"><label for="kpl-lema">Lema <span class="kpl-opcional">(en la portada, en cada banner y en la portada de la tienda)</span></label><input type="text" class="form-control" id="kpl-lema" name="lema" value="{$c.lema|escape:'html':'UTF-8'}" placeholder="Stock limitado · hasta agotar existencias" maxlength="120"></div>
            <div class="kpl-campo"><label for="kpl-antes">Texto antes de empezar</label><input type="text" class="form-control" id="kpl-antes" name="texto_antes" value="{$c.texto_antes|escape:'html':'UTF-8'}" placeholder="Apúntate al boletín y entérate antes que nadie…" maxlength="255"></div>
            <div class="kpl-campo"><label for="kpl-despues">Texto cuando ha terminado</label><input type="text" class="form-control" id="kpl-despues" name="texto_despues" value="{$c.texto_despues|escape:'html':'UTF-8'}" placeholder="Se acabó por este año. Pero las promociones de ahora siguen aquí." maxlength="255"></div>
          </div>
        </section>

        <section class="kpl-paso">
          <h3 class="kpl-paso__titulo"><span class="kpl-num">3</span> En toda la tienda, mientras dura</h3>
          <div class="kpl-checks">
            <label class="kpl-check"><input type="checkbox" name="cinta" value="1"{if $c.cinta} checked{/if}> <span><strong>Cinta arriba de todas las páginas</strong> — una línea con este texto, que lleva a la campaña:</span></label>
            <input type="text" class="form-control kpl-sangrado" name="cinta_texto" value="{$c.cinta_texto|escape:'html':'UTF-8'}" placeholder="Black Friday · hasta agotar existencias" maxlength="120">
            <label class="kpl-check"><input type="checkbox" name="redirigir" value="1"{if $c.redirigir} checked{/if}> <span><strong>/promociones manda a la campaña</strong> — el botón del menú y los enlaces de siempre llevan a la dirección de la campaña.</span></label>
            <label class="kpl-check"><input type="checkbox" name="sin_carrusel" value="1"{if $c.sin_carrusel} checked{/if}> <span><strong>El bloque de campaña hace de portada</strong> — mientras dura, el carrusel grande de la portada de la tienda no sale: arriba va el bloque de la campaña (título, lema, días que quedan, marcas y accesos). Al terminar vuelve el carrusel.</span></label>
          </div>
          <p class="kpl-pregunta">Etiquetas de campaña <small>— en los listados, la ficha, la cesta y el buscador, en lugar de las de siempre. Vacío = esa promoción no cambia.</small></p>
          <div class="kpl-tabla-caja">
            <table class="table kpl-tabla kpl-etiquetas">
              <thead><tr><th>Promoción</th><th>Nombre <small class="kpl-gris">(<code>{literal}{pct}{/literal}</code> = el %)</small></th><th>Fondo</th><th>Texto</th><th>Así queda</th></tr></thead>
              <tbody>
              {foreach $kpl.tipos_etiqueta as $t}
                {assign var=e value=$c.etiquetas[$t]}
                <tr>
                  <td><span class="kpl-tipo-mini kpl-tipo-mini--{$t}">{$kpl.nombres_tipo[$t]}</span></td>
                  <td><input type="text" name="et_nombre_{$t}" class="form-control" value="{$e.nombre|escape:'html':'UTF-8'}" maxlength="40"></td>
                  <td><input type="text" name="et_fondo_{$t}" class="form-control kpl-color" value="{$e.fondo|escape:'html':'UTF-8'}" maxlength="7" pattern="#[0-9A-Fa-f]{literal}{6}{/literal}"></td>
                  <td><input type="text" name="et_texto_{$t}" class="form-control kpl-color" value="{$e.texto|escape:'html':'UTF-8'}" maxlength="7" pattern="#[0-9A-Fa-f]{literal}{6}{/literal}"></td>
                  <td><span class="kpl-muestra" style="background:{$e.fondo|escape:'html':'UTF-8'};color:{$e.texto|escape:'html':'UTF-8'}">{$e.nombre|replace:'{pct}':'15'|escape:'html':'UTF-8'}</span></td>
                </tr>
              {/foreach}
              </tbody>
            </table>
          </div>
          <small class="kpl-nota">Los colores de la primera fila (Special Price) son también los del precio en las tarjetas de los listados y en la ficha mientras dura la campaña. Al cambiar la paleta de arriba se recalculan solos; después se pueden afinar a mano.</small>
        </section>
        <script>{literal}
        (function () {
          var form = document.getElementById('kpl-campana');
          if (!form) { return; }
          function hex(v) { return /^#[0-9A-Fa-f]{6}$/.test(v) ? v.toUpperCase() : null; }
          function oscuro(h) { var r = parseInt(h.substr(1, 2), 16), g = parseInt(h.substr(3, 2), 16), b = parseInt(h.substr(5, 2), 16); return .299 * r + .587 * g + .114 * b < 128; }
          function piel() { var r = form.querySelector('input[name=piel]:checked'); return r ? r.value : 'negra'; }
          function colores() { return { fondo: hex(form.color_fondo.value) || '#111111', acento: hex(form.color_acento.value) || '#F2C965', acento2: hex(form.color_acento2.value) || '#FF6A5C' }; }
          function muestra(t) {
            var f = form['et_fondo_' + t], x = form['et_texto_' + t];
            var fila = f && f.closest('tr'), s = fila && fila.querySelector('.kpl-muestra');
            if (s && hex(f.value) && hex(x.value)) { s.style.background = f.value; s.style.color = x.value; }
          }
          // Las etiquetas a juego con la paleta: en la piel oscura, fondo de la campaña y texto del acento; en la clara, llenas del acento.
          function etiquetas() {
            var c = colores(), n = piel() === 'negra', sp, cu;
            if (n) { sp = [c.fondo, c.acento]; cu = [c.fondo, c.acento2]; }
            else { sp = [c.acento, oscuro(c.acento) ? '#FFFFFF' : '#1F1F1F']; cu = [c.acento2, oscuro(c.acento2) ? '#FFFFFF' : '#1F1F1F']; }
            var m = { special_price: sp, kilos_gratis: sp, cupon: cu, segunda_unidad: cu };
            Object.keys(m).forEach(function (t) { var f = form['et_fondo_' + t], x = form['et_texto_' + t]; if (!f || !x) { return; } f.value = m[t][0]; x.value = m[t][1]; muestra(t); });
          }
          form.querySelectorAll('[data-kpl-color]').forEach(function (p) {
            var t = form[p.getAttribute('data-kpl-color')];
            p.addEventListener('input', function () { t.value = p.value.toUpperCase(); etiquetas(); });
            t.addEventListener('input', function () { if (hex(t.value)) { p.value = t.value; etiquetas(); } });
          });
          form.querySelectorAll('input[name=piel]').forEach(function (r) { r.addEventListener('change', etiquetas); });
          form.querySelectorAll('[data-kpl-paleta]').forEach(function (b) {
            b.addEventListener('click', function () {
              var p = JSON.parse(b.getAttribute('data-kpl-paleta'));
              var r = form.querySelector('input[name=piel][value="' + p.piel + '"]');
              if (r) { r.checked = true; }
              ['fondo', 'acento', 'acento2'].forEach(function (k) { form['color_' + k].value = p.colores[k]; var pk = form.querySelector('[data-kpl-color=color_' + k + ']'); if (pk) { pk.value = p.colores[k]; } });
              etiquetas();
            });
          });
          form.querySelectorAll('.kpl-etiquetas input').forEach(function (i) {
            i.addEventListener('input', function () { var t = (i.name.match(/_(special_price|cupon|segunda_unidad|kilos_gratis)$/) || [])[1]; if (t) { muestra(t); } });
          });
        })();
        {/literal}</script>

        <section class="kpl-paso">
          <h3 class="kpl-paso__titulo"><span class="kpl-num">4</span> Orden de las marcas <span class="kpl-opcional">(opcional)</span></h3>
          <input type="text" class="form-control" name="orden_marcas" value="{$c.orden_marcas|escape:'html':'UTF-8'}" placeholder="Royal Canin, Boske, Eukanuba" maxlength="255">
          <small class="kpl-nota">Nombres separados por comas: esas marcas van primero en la landing de la campaña y en la portada de la tienda, en ese orden; las demás, detrás, por rebaja. Vacío: todas por rebaja.</small>
        </section>

        <div class="kpl-barra">
          <p class="kpl-barra__resumen">{if $c.id_kpy_promo_campana}{if $c.estado == 'viva'}En marcha ahora{if $c.quedan_texto}: {$c.quedan_texto|lower}{/if}.{elseif $c.estado == 'programada'}Programada{if $c.faltan_texto}: {$c.faltan_texto|lower}{/if}.{elseif $c.estado == 'terminada'}Terminada.{else}Apagada.{/if} Se aplica sola entre sus fechas.{else}Se aplica sola entre sus fechas. Nada que arrancar ni que parar.{/if}</p>
          <div class="kpl-barra__botones">
            {if $c.id_kpy_promo_campana}<button class="btn btn-default" name="kplBorrarCampana" value="1" onclick="return confirm('¿Borrar la campaña «{$c.nombre|escape:'javascript'}»?')"><i class="icon-trash"></i> Borrar</button>{/if}
            <button class="btn btn-primary" name="kplGuardarCampana" value="1"><i class="icon-save"></i> Guardar campaña</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  {* 3 · Imágenes *}
  <div class="panel kpl-panel" id="kpl-imagenes">
    <div class="panel-heading kpl-cabeza"><i class="icon-picture"></i> Imágenes <small class="kpl-cabeza__sub">logos y sacos sin fondo, hechos solos; y las subidas a mano</small></div>
    <div class="kpl-cuerpo">
      <p class="kpl-ayuda">No hay que preparar nada. La primera vez que una promoción necesita el logo de una marca o el saco de un producto, se coge la foto del catálogo, se le quita el fondo y se guarda aquí. De cada ficha se prueba primero la foto propia del formato (el saco Jirafa en kilos gratis), luego la portada y luego las demás, y se queda la primera que es <b>un solo saco de pie</b>; las fotos de transición (saco viejo, flecha, saco nuevo) y los rótulos se descartan solos. Lo mismo decide la foto de las tarjetas de producto en la landing: <b>{$kpl.n_fotos} fotos medidas, {$kpl.n_fotos_descartadas} descartadas</b>. Si una foto no sale bien, o quieres tu propio logo o tu propio saco, súbelos en la biblioteca de abajo: un logo o un saco subidos a mano se usan solos.</p>

      <details class="kpl-detalle" {if !$kpl.rembg && !$kpl.removebg}open{/if}>
        <summary><i class="icon-cog"></i> Motor de recorte <small>— ahora: {if $kpl.rembg}rembg ({$kpl.rembg_modelo}){if $kpl.removebg}, remove.bg{/if} y el propio de respaldo{elseif $kpl.removebg}remove.bg y el propio de respaldo{else}el recorte propio del módulo{/if}</small></summary>
        <div class="kpl-detalle__cuerpo">
          <p class="kpl-nota">Por este orden, y si uno falla el siguiente: <b>rembg</b> (servidor propio, inteligencia artificial, gratis), <b>remove.bg</b> (servicio de pago, con clave) y el <b>recorte propio</b> (sin dependencias, vale para casi todo). Los logos van siempre con el propio. Después de cambiar de motor: «Rehacer las imágenes», abajo.</p>
          <form method="post" action="{$kpl.url|escape:'html':'UTF-8'}#kpl-imagenes" class="kpl-motor">
            <div class="kpl-fila">
              <div class="kpl-campo kpl-campo--ancho">
                <label for="kpl-rembg">rembg <span class="kpl-opcional">(dirección del contenedor)</span></label>
                <div class="kpl-motor__fila">
                  <input type="text" class="form-control" id="kpl-rembg" name="rembg" value="{$kpl.rembg|escape:'html':'UTF-8'}" placeholder="http://rembg:7000">
                  <select name="rembg_modelo" class="form-control">{foreach $kpl.rembg_modelos as $m}<option value="{$m}"{if $m == $kpl.rembg_modelo} selected{/if}>{$m}</option>{/foreach}</select>
                  <button class="btn btn-default" name="kplProbarRembg" value="1"><i class="icon-check"></i> Probar y guardar</button>
                </div>
                <small class="kpl-nota">Un contenedor junto a la tienda, en la misma red que PHP: <code>docker run -d --name rembg --restart unless-stopped --network &lt;red de php&gt; -p 127.0.0.1:7000:7000 -e OMP_NUM_THREADS=4 -e U2NET_HOME=/models -v rembg-models:/models danielgatis/rembg:latest s --host 0.0.0.0 --port 7000</code>. Código: <a href="https://github.com/danielgatis/rembg" target="_blank" rel="noopener">github.com/danielgatis/rembg</a>. Modelo <b>u2net</b> (rápido) o <b>isnet-general-use</b> (bordes más finos, más lento).</small>
              </div>
              <div class="kpl-campo">
                <label for="kpl-removebg">remove.bg <span class="kpl-opcional">(clave)</span></label>
                <div class="kpl-motor__fila">
                  <input type="text" class="form-control" id="kpl-removebg" name="removebg" value="{$kpl.removebg|escape:'html':'UTF-8'}" placeholder="vacío = no se usa">
                  <button class="btn btn-default" name="kplGuardarClave" value="1">Guardar</button>
                </div>
                <small class="kpl-nota"><a href="https://www.remove.bg/es/api" target="_blank" rel="noopener">remove.bg</a> → cuenta → «API key». La cuenta gratuita da 50 imágenes al mes (de sobra para los banners).</small>
              </div>
            </div>
          </form>
        </div>
      </details>

      <p class="kpl-subtitulo" id="kpl-biblioteca">Biblioteca <small>— {$kpl.subidas|count} subidas a mano y {$kpl.automaticas|count} hechas solas</small></p>
      <div class="kpl-ayuda kpl-ayuda--biblio">
        <b>Qué subir.</b> PNG o WebP <b>con el fondo transparente</b>, recortado al borde (sin aire alrededor). Logos: <b>600 px de ancho</b> o más. Sacos y latas: <b>900 px de ancho</b> o más. Más grande no hace falta: el módulo lo deja a esa medida, sin aire y en WebP; más pequeño no se agranda. Hasta 4 MB. Si subes un JPG (o un PNG) con fondo blanco, se le quita el fondo con el motor de arriba.<br>
        <b>Qué hace cada una.</b> Un <b>logo</b> sustituye al automático de su marca en toda la landing (banners, tarjetas de marca, portada de la tienda). Un <b>saco</b> o una <b>lata</b> de una marca es la foto de los banners de sus promociones, en vez de la del catálogo; con mascota, solo de esa mascota (en una promoción de perros y gatos, uno por cada una). Con <b>gama</b> (Mature, Puppy, Sterilised…), solo entra en las promociones de esa marca que nombren la gama en el nombre de la promoción, en el título del banner o en sus productos. <b>Otra foto</b> no se usa sola: se elige en el paso 6 de la promoción. Las subidas ganan siempre a las automáticas; si borras una subida, vuelve la automática. Los logos se pintan todos con el mismo peso a la vista (uno muy apaisado, algo más alto; uno cuadrado, algo más bajo).
      </div>
      <form method="post" action="{$kpl.url|escape:'html':'UTF-8'}#kpl-biblioteca" enctype="multipart/form-data" class="kpl-subir">
        <label class="btn btn-default kpl-fichero"><i class="icon-upload"></i> Elegir imagen<input type="file" name="imagen" accept=".png,.webp,.jpg,.jpeg" required></label>
        <select name="tipo" class="form-control" title="Qué es">{foreach $kpl.tipos_imagen as $k => $n}<option value="{$k}">{$n}</option>{/foreach}</select>
        <select name="id_manufacturer" class="form-control" title="Marca"><option value="0">Marca…</option>{foreach $kpl.marcas as $m}<option value="{$m.id_manufacturer}">{$m.name|escape:'html':'UTF-8'}</option>{/foreach}</select>
        <select name="mascota" class="form-control" title="Mascota"><option value="">Perros y gatos</option><option value="perro">Perros</option><option value="gato">Gatos</option></select>
        <input type="text" name="gama" class="form-control" placeholder="Gama (opcional): Mature, Puppy…" maxlength="40">
        <input type="text" name="titulo" class="form-control" placeholder="Nombre (opcional)" maxlength="80">
        <button class="btn btn-primary" name="kplSubir" value="1"><i class="icon-upload"></i> Subir</button>
      </form>
      <div class="kpl-galeria">
        {foreach $kpl.subidas as $im}
          <div class="kpl-img kpl-img--subida{if !$im.url} kpl-img--rota{/if}">
            {if $im.url}<img src="{$im.url|escape:'html':'UTF-8'}" alt="" loading="lazy">{else}<div class="kpl-img__no">sin fichero</div>{/if}
            <small>
              <span class="kpl-img__tipo">{$im.tipo_nombre|escape:'html':'UTF-8'}</span><br>
              {if $im.marca_nombre}<b>{$im.marca_nombre|escape:'html':'UTF-8'}</b>{elseif $im.id_manufacturer}<b>marca {$im.id_manufacturer}</b>{else}<b>sin marca</b>{/if}{if $im.mascota} · {if $im.mascota == 'perro'}perros{else}gatos{/if}{/if}{if $im.gama} · {$im.gama|escape:'html':'UTF-8'}{/if}<br>
              {$im.ancho}×{$im.alto} · {($im.bytes/1024)|round} KB{if $im.nota}<br><span class="kpl-gris">{$im.nota|escape:'html':'UTF-8'}</span>{/if}
            </small>
            <form method="post" action="{$kpl.url|escape:'html':'UTF-8'}#kpl-biblioteca"><input type="hidden" name="id" value="{$im.id_kpy_promo_imagen}"><button class="btn btn-link btn-xs" name="kplBorrarImagen" value="1" onclick="return confirm('¿Borrar esta imagen? Si es un logo o un saco, vuelve el automático.')">borrar</button></form>
          </div>
        {foreachelse}
          <p class="kpl-gris">Aún no has subido ninguna. Mientras, todo sale con los logos y los sacos hechos solos.</p>
        {/foreach}
      </div>
      <details class="kpl-detalle kpl-detalle--automaticas"{if !$kpl.subidas} open{/if}>
        <summary><i class="icon-magic"></i> Hechas solas <small>— {$kpl.automaticas|count} logos y sacos recortados del catálogo; se rehacen solas si cambia la foto</small></summary>
        <div class="kpl-detalle__cuerpo">
          <div class="kpl-galeria">
            {foreach $kpl.automaticas as $im}
              <div class="kpl-img{if !$im.url} kpl-img--rota{/if}{if $im.origen == 'producto' && !$im.apta} kpl-img--descartada{/if}">
                {if $im.url}<img src="{$im.url|escape:'html':'UTF-8'}" alt="" loading="lazy">{else}<div class="kpl-img__no">sin fichero</div>{/if}
                <small>
                  <b>{if $im.origen == 'marca'}logo{elseif $im.apta}saco héroe{else}descartada{/if}</b>{if $im.marca_nombre} · {$im.marca_nombre|escape:'html':'UTF-8'}{elseif $im.id_manufacturer && $im.origen != 'marca'} · marca {$im.id_manufacturer}{/if}{if $im.id_product} · producto {$im.id_product}{/if}{if $im.mascota} · {$im.mascota}{/if}<br>
                  {$im.ancho}×{$im.alto} · {($im.bytes/1024)|round} KB · {$im.motor}{if $im.nota}<br><span class="kpl-gris">{$im.nota|escape:'html':'UTF-8'}</span>{/if}
                </small>
              </div>
            {foreachelse}
              <p class="kpl-gris">Aún no hay ninguna: se hacen al sincronizar con promociones en marcha.</p>
            {/foreach}
          </div>
        </div>
      </details>
    </div>
  </div>

  {* 4 · Mantenimiento *}
  <details class="panel kpl-panel kpl-detalle kpl-detalle--panel" id="kpl-mantenimiento">
    <summary class="panel-heading kpl-cabeza"><i class="icon-wrench"></i> Mantenimiento <small class="kpl-cabeza__sub">rehacer, ajustes, borrar todo</small></summary>
    <div class="kpl-cuerpo">
      <div class="kpl-mant">
        <form method="post" action="{$kpl.url|escape:'html':'UTF-8'}" class="kpl-mant__fila">
          <div><strong>Rehacer las imágenes</strong><small class="kpl-nota">Borra los logos y sacos automáticos (no las subidas a mano) y las medidas de las fotos, y lo vuelve a hacer todo con el motor de ahora.</small></div>
          <button class="btn btn-default" name="kplRehacerImagenes" value="1" onclick="return confirm('¿Borrar las imágenes recortadas automáticamente y volver a hacerlas todas?')"><i class="icon-picture"></i> Rehacer las imágenes</button>
        </form>
        <form method="post" action="{$kpl.url|escape:'html':'UTF-8'}" class="kpl-mant__fila">
          <div>
            <label class="kpl-check"><input type="checkbox" name="heroe_tienda" value="1"{if $kpl.heroe_tienda} checked{/if}> <span><strong>La foto de las tarjetas también en toda la tienda</strong> — en los listados de las categorías, el buscador y la portada, la tarjeta de un producto en promoción enseña su foto «un solo saco de pie» en vez de la portada del catálogo (si esta es una foto de transición). Sin marcar, solo en las páginas de la landing. La ficha y el catálogo no cambian nunca.</span></label>
          </div>
          <button class="btn btn-default" name="kplGuardarAjustes" value="1"><i class="icon-save"></i> Guardar</button>
        </form>
        <form method="post" action="{$kpl.url|escape:'html':'UTF-8'}" class="kpl-mant__fila kpl-mant__fila--peligro">
          <div><strong>Borrar todo</strong><small class="kpl-nota">Borra las categorías nuestras (con la raíz «Promociones»), las imágenes, las medidas y los datos de la landing. Las promociones de Kompy · Promociones y las campañas no se tocan. Para dejar PrestaShop como si este módulo nunca hubiera estado. Escribe <b>BORRAR</b> para confirmar.</small></div>
          <span class="kpl-mant__peligro"><input type="text" class="form-control" name="confirmar" placeholder="BORRAR"><button class="btn btn-danger" name="kplBorrarTodo" value="1" onclick="return confirm('¿Seguro? Se borran las categorías, las imágenes y los datos de la landing.')">Borrar todo</button></span>
        </form>
      </div>
    </div>
  </details>
</div>
