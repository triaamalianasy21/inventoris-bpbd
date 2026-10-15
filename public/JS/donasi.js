let kategoriDipilih = '', nominalDipilih = 0, metodeDipilih = '';

function showStep(step){
    for(let i=1;i<=5;i++){
        const el = document.getElementById(`step${i}`);
        if(el) el.style.display = 'none';
    }
    document.getElementById(`step${step}`).style.display = 'flex';
    updateKategoriDisplay();
}

document.addEventListener('DOMContentLoaded', () => {
    showStep(1);

    const form = document.getElementById('formDonasi');
    if(form){
        form.addEventListener('submit', e => {
            e.preventDefault();
            alert(`Terima kasih ${form.nama.value}! Donasi sebesar Rp ${nominalDipilih} telah dikirim.`);

            form.reset();
            kategoriDipilih = '';
            nominalDipilih = 0;
            metodeDipilih = '';

            showStep(1);
        });
    }
});

function pilihKategori(kat){
    kategoriDipilih = kat;
    document.getElementById('judul-detail').innerText = kat;
    document.getElementById('foto-detail').src = "/androidweb/logo.png";
    updateKategoriDisplay();
    showStep(2);
}

function pilihNominal(n){
    nominalDipilih = n;
    document.getElementById('nominalLain').value = n;
}

function pilihMetode(m){
    metodeDipilih = m;
    document.getElementById('infoMetode').innerText = `Metode dipilih: ${m}`;
}

function bukaStep(step){
    if(step === 3 && !kategoriDipilih) return alert("Pilih kategori!");
    if(step === 4 && !nominalDipilih) return alert("Pilih nominal!");
    if(step === 5 && !metodeDipilih) return alert("Pilih metode!");
    showStep(step);
}

function bagikanDonasi(){
    navigator.clipboard.writeText(window.location.href);
    alert("Link donasi berhasil disalin!");
}

function updateKategoriDisplay(){
    const list = [
        "kategori-terpilih-3",
        "kategori-terpilih-4",
        "kategori-terpilih-5"
    ];

    list.forEach(id => {
        const el = document.getElementById(id);
        if(el){
            el.innerText = kategoriDipilih ? `(${kategoriDipilih})` : "";
        }
    });
}
