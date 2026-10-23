{* El selector Todos / Perros / Gatos: enlaces de verdad (cada uno con su dirección). *}
<div class="kpl-quien">
  <span class="kpl-quien__l">¿Para quién?</span>
  <nav class="kpl-seg" aria-label="Elige la mascota">
    <a href="{$kpl.quien.todos}" class="{if $kpl.mascota == ''}on{/if}" {if $kpl.mascota == ''}aria-current="true"{/if}>Todos</a>
    <a href="{$kpl.quien.perro}" class="{if $kpl.mascota == 'perro'}on{/if}" {if $kpl.mascota == 'perro'}aria-current="true"{/if}><img src="{$urls.base_url}modules/kpypromolanding/views/img/dog-face.svg" alt="" width="22" height="22">Perros</a>
    <a href="{$kpl.quien.gato}" class="{if $kpl.mascota == 'gato'}on{/if}" {if $kpl.mascota == 'gato'}aria-current="true"{/if}><img src="{$urls.base_url}modules/kpypromolanding/views/img/cat-face.svg" alt="" width="22" height="22">Gatos</a>
  </nav>
</div>
