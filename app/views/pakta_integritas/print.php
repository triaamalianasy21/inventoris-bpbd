<?php
if (!$data) die('Data tidak ditemukan');
$isPdf = $isPdf ?? false;
$getVal = function($keys) use ($data) {
    foreach ($keys as $k) {
        if (isset($data[$k]) && $data[$k] !== '') return $data[$k];
    }
    return '';
};
$nama = $getVal(['nama','nama_lengkap','nama_peminjam']);
$nip = $getVal(['nip','nip_nik','nik','nrp','nip_nrp_nik']);
$jabatan = $getVal(['jabatan','jabatan_peminjam']);
$barang = $getVal(['barang','barang_milik_daerah','barang_digunakan','nama_barang']);
$tglDibuatRaw = $getVal(['tgl_dibuat','tanggal','tanggal_dibuat','created_at']);
$logoAbs = realpath(__DIR__ . '/../../public/document/pakta_logo.png');
$logoPath = ($isPdf && $logoAbs) ? $logoAbs : 'public/document/pakta_logo.png';
$formatTanggal = function($raw) {
    if (!$raw) return '';
    $ts = strtotime($raw);
    if ($ts === false) return '';
    $bulan = [
        'Januari','Februari','Maret','April','Mei','Juni',
        'Juli','Agustus','September','Oktober','November','Desember'
    ];
    $m = (int)date('n', $ts);
    $b = $bulan[$m - 1] ?? date('F', $ts);
    return date('d', $ts) . ' ' . $b . ' ' . date('Y', $ts);
};
$tanggalCetak = $formatTanggal($tglDibuatRaw);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pakta Integritas</title>
  <style>
    @page { size: A4; margin: 7.5mm 25.4mm 25.4mm; }
    body { font-family: "Times New Roman", serif; font-size: 12pt; margin: 0; color: #000; line-height: 1.5; }
    .page { width: 100%; margin: 0; padding: 0; box-sizing: border-box; }
    .header { border-bottom: 1.5px solid #000; padding-bottom: 6px; margin-bottom: 10px; }
    .header-inner { display: flex; align-items: center; gap: 10px; }
    .header-table { width: 100%; border-collapse: collapse; }
    .header-table td { vertical-align: middle; padding: 0; }
    .header-table .logo-cell { width: 80px; }
    .header-table .text-cell { text-align: center; }
    .header-table .spacer-cell { width: 80px; }
    .header-logo { width: 80px; height: 80px; }
    .header-text { text-align: center; line-height: 1; width: 100%; }
    .header h1 { font-size: 13pt; margin: 0; letter-spacing: 0.1px; font-weight: bold; }
    .header h2 { font-size: 15pt; margin: 0; font-weight: bold; white-space: nowrap; }
    .header .small { font-size: 9pt; line-height: 1; }
    .title { text-align: center; margin: 10px 0 6px; font-weight: bold; text-transform: uppercase; }
    .title::after { content: none; }
    table.meta { width: 100%; border-collapse: collapse; margin-top: 4px; }
    table.meta td { padding: 2px 4px; vertical-align: top; }
    .meta-label { width: 180px; }
    .meta-colon { width: 10px; }
    .meta-value { padding-left: 2px; }
    .body-text { margin-top: 6px; text-align: justify; }
    .body-text ol { margin: 6px 0 0 18px; padding: 0; }
    .body-text li { margin-bottom: 2px; }
    .signature { margin-top: 20px; width: 240px; margin-left: auto; text-align: left; }
    .sign-gap { height: 2.4em; }
    .no-print { margin-top: 14px; text-align: right; }
    @media print { .no-print { display: none; } }
  </style>
</head>
<body>
  <div class="page">
    <div class="header">
      <?php if (!empty($isPdf)): ?>
        <table class="header-table">
          <tr>
            <td class="logo-cell">
              <?php if (!empty($logoPath)): ?>
                <img src="<?= htmlspecialchars($logoPath) ?>" alt="" class="header-logo">
              <?php endif; ?>
            </td>
            <td class="text-cell">
              <div class="header-text">
                <h1>PEMERINTAH KABUPATEN BANYUMAS</h1>
                <h2>BADAN PENANGGULANGAN BENCANA DAERAH</h2>
                <div class="small">Jl. HM. Bahrun, Berkoh, Kec. Purwokerto Sel., Banyumas, Jawa Tengah 53146</div>
                <div class="small">Telp. (0281) 6570790  Faksimile. (0281) 6841931</div>
                <div class="small">Pos-el : bpbd.banyumas@gmail.com  Laman : bpbd.banyumaskab.go.id</div>
              </div>
            </td>
            <td class="spacer-cell"></td>
          </tr>
        </table>
      <?php else: ?>
        <div class="header-inner">
          <?php if (!empty($logoPath)): ?>
            <img src="<?= htmlspecialchars($logoPath) ?>" alt="" class="header-logo">
          <?php endif; ?>
          <div class="header-text">
            <h1>PEMERINTAH KABUPATEN BANYUMAS</h1>
            <h2>BADAN PENANGGULANGAN BENCANA DAERAH</h2>
            <div class="small">Jl. HM. Bahrun, Berkoh, Kec. Purwokerto Sel., Banyumas, Jawa Tengah 53146</div>
            <div class="small">Telp. (0281) 6570790  Faksimile. (0281) 6841931</div>
            <div class="small">Pos-el : bpbd.banyumas@gmail.com  Laman : bpbd.banyumaskab.go.id</div>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <div class="title">PAKTA INTEGRITAS</div>

    <div style="margin-top:8px;">Yang bertanda tangan di bawah ini :</div>
    <table class="meta">
      <tr>
        <td class="meta-label">Nama</td><td class="meta-colon">:</td>
        <td class="meta-value"><?= htmlspecialchars($nama) ?></td>
      </tr>
      <tr>
        <td class="meta-label">NIP/NRP/NIK</td><td class="meta-colon">:</td>
        <td class="meta-value"><?= htmlspecialchars($nip) ?></td>
      </tr>
      <tr>
        <td class="meta-label">Jabatan</td><td class="meta-colon">:</td>
        <td class="meta-value"><?= htmlspecialchars($jabatan) ?></td>
      </tr>
      <tr>
        <td class="meta-label">Barang Milik Daerah yang<br>digunakan</td><td class="meta-colon">:</td>
        <td class="meta-value"><?= htmlspecialchars($barang) ?></td>
      </tr>
    </table>

    <div class="body-text">
      Dalam rangka penggunaan Barang Milik Daerah Pemerintah Kabupaten Banyumas, dengan ini menyatakan dengan sebenarnya bahwa saya bertanggungjawab :
      <ol>
        <li>Menggunakan barang milik daerah sebagaimana tercantum dalam Lampiran dan merupakan bagian yang tidak terpisahkan dari Berita Acara Peminjaman Barang Milik Daerah sesuai dengan kewenangan;</li>
        <li>Menggunakan Barang Milik Daerah tersebut hanya untuk menunjang tugas pekerjaan dengan penuh tanggung jawab;</li>
        <li>Merawat Barang Milik Daerah tersebut agar dapat berfungsi sebagaimana mestinya;</li>
        <li>Melakukan pengamanan barang milik daerah tersebut agar tidak terjadi kehilangan atas barang tersebut;</li>
        <li>Mengembalikan barang milik daerah tersebut kepada Pengguna Barang/Kuasa Pengguna Barang dalam keadaan Baik.</li>
        <li>Bertanggung jawab sepenuhnya atas kegiatan yang berlangsung selama dalam peminjaman Barang Milik Daerah.</li>
      </ol>
      <div>Demikian Pernyataan Tanggung Jawab ini dibuat dengan sebenarnya untuk dapat dipergunakan sebagaimana mestinya.</div>
    </div>

    <div class="signature">
      <div>Purwokerto<?= $tanggalCetak ? ', ' . htmlspecialchars($tanggalCetak) : '' ?></div>
      <div style="margin-top:18px;">Peminjam</div>
      <div class="sign-gap"></div>
      <div><?= htmlspecialchars($nama) ?></div>
      <div>NIP. <?= htmlspecialchars($nip) ?></div>
    </div>

    <?php if (!$isPdf): ?>
      <div class="no-print">
        <button onclick="window.print()">Cetak / Simpan PDF</button>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
