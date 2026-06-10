const selectKind = document.querySelector('select[name="kind"]');

const selectBreed = document.querySelector('select[name="breed"]');

selectKind.addEventListener('change', async () => {
    const response = await fetch('/index.php', {
        method: 'POST',
        body: new URLSearchParams({
            'module': 'kpypets',
            'fc': 'module',
            'controller': 'manage',
            'ajax': true,
            'kind': selectKind.value,
        })
    });

    const data = await response.json();

    if (data.code !== 200) {
        return;
    }

    selectBreed.innerHTML = ''
    selectBreed.appendChild(new Option('-- por favor selecciona --', 0, true))
    for (let breed of data.breeds) {
        selectBreed.appendChild(new Option(breed.name, breed.id))
    }
});
