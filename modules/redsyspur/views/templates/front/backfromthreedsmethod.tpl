<p style="font-family: Arial; font-size: 16; font-weight: bold; color: black; align: center;">
    Procesando operación...
</p>

<input type="hidden" id="redirect_url" value="{$redirect_url}">

<script>
    parent.location.href = document.getElementById('redirect_url').value;
</script>