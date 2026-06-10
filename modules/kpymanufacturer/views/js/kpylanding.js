// Tabs gamas (perro/gato)
function switchTab(pet, btn) {
    document.querySelectorAll('.tab-btn').forEach(b => {
        b.classList.remove('active');
        b.setAttribute('aria-selected', 'false');
    });
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    btn.setAttribute('aria-selected', 'true');
    document.getElementById(`tab-${pet}`).classList.add('active');
}

// Tabs productos (filtro)
function switchProdTab(pet, btn) {
    document.querySelectorAll('.prod-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const cards = document.querySelectorAll('#prod-grid .product-card');
    cards.forEach(card => {
        card.classList.toggle('hidden', pet !== 'all' && card.dataset.pet !== pet);
    });
}
