<p style="font-family: Arial; font-size: 16; font-weight: bold; color: black; align: center;">
    Procesando operación...
</p>

<form id="3dsmethod-form" action="{$threeDSMethodURL}" method="POST">
    <input type="hidden" name="threeDSMethodData" value="{$threeDSMethodData}"> 
</form>
<iframe id="3dsmethod-iframe" name="3dsmethod" style="display:none;"></iframe>

<input type="hidden" id="redirect_url" value="{$redirect_url}">

<script>
    window.onload = function () {
        var form = document.getElementById("3dsmethod-form"); 
        var iframe = document.getElementById("3dsmethod-iframe"); 

        iframe.onload = function () {
            setTimeout(() => {
                redirect(true);
            }, 500);
        };

        form.target = "3dsmethod"; 
        form.submit();
    }

    setTimeout(() => {
        redirect(false);
    }, 10000);

    function redirect(threedsmethod){
        var redirectURL = document.getElementById('redirect_url').value;
        if(threedsmethod){
            redirectURL += "?threeDSCompInd=Y"
        }
        parent.location.href = redirectURL;
    }
</script>