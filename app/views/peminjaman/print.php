<?php
if (!$data) die('Data tidak ditemukan');
$isPdf = $isPdf ?? false;
$pihak1 = $data['nama_pihak1'] ?? '-';
$nip1 = $data['nip'] ?? '-';
$jabatan1 = $data['jabatan'] ?? '-';
$pihak2 = $data['nama_pihak2'] ?? '-';
$nip2 = $data['nip1'] ?? '-';
$jabatan2 = $data['jabatan1'] ?? '-';
$alamat = $data['alamat'] ?? '-';
$noHp = $data['no_hp'] ?? '-';
$tujuan = $data['tujuan'] ?? '-';
$tglPinjam = $data['tgl_pinjam'] ?? ($data['created_at'] ?? '-');
$tglJatuhTempo = $data['tgl_jatuh_tempo'] ?? '-';
$spesifikasi = $data['spesifikasi'] ?? '-';
$keterangan = $data['keterangan'] ?? '-';
$logoAbs = realpath(__DIR__ . '/../../public/document/bast_logo.png');
$logoPath = ($isPdf && $logoAbs) ? $logoAbs : 'public/document/bast_logo.png';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BAST Peminjaman Alat</title>
  <style>
    @page { size: Legal; margin: 7.5mm 25.4mm 25.4mm; }
    body { font-family: "Times New Roman", serif; font-size: 12pt; color: #000; margin: 0; line-height: 1.5; }
    .page { width: 100%; margin: 0; padding: 0; box-sizing: border-box; }
    .header { border-bottom: 1.5px solid #000; padding-bottom: 6px; margin-bottom: 10px; }
    .header-table { width: 100%; border-collapse: collapse; }
    .header-table td { vertical-align: middle; padding: 0; }
    .header-table .logo-cell { width: 80px; }
    .header-table .spacer-cell { width: 80px; }
    .logo { width: 80px; height: 80px; }
    .header-text { text-align: center; line-height: 1; width: 100%; }
    .header-text .l1 { font-weight: bold; font-size: 13pt; }
    .header-text .l2 { font-weight: bold; font-size: 15pt; white-space: nowrap; }
    .header-text .l3 { font-size: 9pt; margin-top: 2px; }
    .header-text .l4 { font-size: 9pt; }
    .title { text-align: center; margin: 8px 0 6px; }
    .title .t1 { font-weight: bold; text-transform: uppercase; display: inline-block; border-bottom: 1px solid #000; padding-bottom: 2px; }
    .title .t2 { margin-top: 4px; }
    .section { margin-top: 8px; }
    .section.text { line-height: 1; text-align: justify; }
    .label { font-weight: bold; }
    table.meta { width: 100%; border-collapse: collapse; margin-top: 4px; }
    table.meta td { padding: 2px 4px; vertical-align: top; }
    table.items { width: 100%; border-collapse: collapse; margin-top: 6px; table-layout: fixed; }
    table.items th, table.items td { border: 1px solid #000; padding: 4px 6px; font-size: 11pt; word-break: break-word; }
    .sign-table { width: 100%; border-collapse: collapse; margin-top: 12px; page-break-inside: avoid; }
    .sign-table td { width: 50%; text-align: center; vertical-align: top; }
    .sign-col-inner { display: inline-block; min-width: 200px; }
    .sign-title { font-weight: bold; margin-bottom: 6px; }
    .sign-sub { font-size: 11pt; line-height: 1.2; font-weight: normal; }
    .sign-head { height: 7.2em; }
    .sign-name { font-weight: normal; }
    .sign-nip { margin-top: 4px; font-size: 11pt; }
    .center { text-align: center; page-break-inside: avoid; }
    .no-print { margin-top: 16px; text-align: right; }
    @media print { .no-print { display: none; } }
  </style>
</head>
<body>
  <div class="page">
    <div class="header">
      <table class="header-table">
        <tr>
          <td class="logo-cell">
            <img src="<?= htmlspecialchars($logoPath) ?>" class="logo" alt="">
          </td>
          <td>
            <div class="header-text">
              <div class="l1">PEMERINTAH KABUPATEN BANYUMAS</div>
              <div class="l2">BADAN PENANGGULANGAN BENCANA DAERAH</div>
              <div class="l3">Jl. HM. Bahrun, Berkoh, Kec. Purwokerto Sel., Banyumas, Jawa Tengah 53146</div>
              <div class="l4">Telp. (0281) 6570790 Faksimile. (0281) 6841931</div>
              <div class="l4">Pos-el : bpbd.banyumas@gmail.com Laman : bpbd.banyumaskab.go.id</div>
            </div>
          </td>
          <td class="spacer-cell"></td>
        </tr>
      </table>
    </div>

    <div class="title">
      <div class="t1">BERITA ACARA PEMINJAMAN PERALATAN</div>
      <div class="t2">Nomor : / 000.2.3.2 / XI/ 2025</div>
    </div>

    <div class="section text">
      <div class="label">Yang bertanda tangan dibawah ini :</div>
      <table class="meta">
        <tr><td width="120">Nama</td><td>: <?= htmlspecialchars($pihak1) ?></td></tr>
        <tr><td>NIP</td><td>: <?= htmlspecialchars($nip1) ?></td></tr>
        <tr><td>Jabatan</td><td>: <?= htmlspecialchars($jabatan1) ?></td></tr>
      </table>
      <div class="section text">Selanjutnya disebut <b>PIHAK I</b>.</div>

      <table class="meta">
        <tr><td width="120">Nama</td><td>: <?= htmlspecialchars($pihak2) ?></td></tr>
        <tr><td>NIP / NIK</td><td>: <?= htmlspecialchars($nip2) ?></td></tr>
        <tr><td>Jabatan</td><td>: <?= htmlspecialchars($jabatan2) ?></td></tr>
        <tr><td>Alamat</td><td>: <?= htmlspecialchars($alamat) ?></td></tr>
        <tr><td>No HP</td><td>: <?= htmlspecialchars($noHp) ?></td></tr>
      </table>
      <div class="section text">Selanjutnya disebut <b>PIHAK II</b>.</div>
    </div>

    <div class="section text">
      Bahwa <b>PIHAK I</b> dan <b>PIHAK II</b> sepakat mengadakan pinjam meminjam peralatan / barang inventaris BPBD Kabupaten Banyumas untuk keperluan <b><?= htmlspecialchars($tujuan) ?></b> dari tanggal <b><?= htmlspecialchars($tglPinjam) ?></b> sampai dengan tanggal <b><?= htmlspecialchars($tglJatuhTempo) ?></b>.
      <div class="section text">Apabila terjadi kerusakan pada barang yang dipinjam maka <b>PIHAK II</b> wajib untuk memperbaiki / mengganti barang tersebut.</div>
      <div class="section text">Barang yang dipinjam <b>PIHAK II</b> berupa :</div>
    </div>

    <table class="items">
      <thead>
        <tr>
          <th width="40">No</th>
          <th width="120">Kode Barang</th>
          <th>Nama Barang</th>
          <th width="120">Spesifikasi</th>
          <th width="60">Jumlah</th>
          <th width="120">Keterangan</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($data['items'])): ?>
          <?php $no = 1; foreach ($data['items'] as $it): ?>
            <tr>
              <td><?= $no++ ?></td>
              <td><?= htmlspecialchars($it['kode_barang'] ?? '-') ?></td>
              <td><?= htmlspecialchars($it['nama_barang'] ?? $it['barang_id']) ?></td>
              <td><?= htmlspecialchars($spesifikasi) ?></td>
              <td><?= (int)($it['qty'] ?? ($it['jumlah'] ?? 0)) ?></td>
              <td><?= htmlspecialchars($keterangan) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="6">Tidak ada barang.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

    <div class="section text">
      <b>PIHAK I</b> menyerahkan barang sepenuhnya kepada <b>PIHAK II</b> dalam kondisi <b>BAIK</b> dan akan dikembalikan kepada <b>PIHAK I</b> dalam keadaan <b>BAIK</b> setelah selesai peminjaman.
      <div class="section text">Demikian untuk dipergunakan sebagaimana mestinya.</div>
    </div>

    <table class="sign-table">
      <tr>
        <td>
          <div class="sign-col-inner">
            <div class="sign-head">
              <div class="sign-title"><b>PIHAK II</b></div>
            </div>
            <div class="sign-name"><?= htmlspecialchars($pihak2) ?></div>
          </div>
        </td>
        <td>
          <div class="sign-col-inner">
            <div class="sign-head">
              <div class="sign-title"><b>PIHAK I</b></div>
              <div class="sign-sub">PENGURUS BARANG<br>BPBD KABUPATEN BANYUMAS</div>
            </div>
            <div class="sign-name"><?= htmlspecialchars($pihak1) ?></div>
            <div class="sign-nip">NIP. <?= htmlspecialchars($nip1) ?></div>
          </div>
        </td>
      </tr>
    </table>

    <div class="section center" style="margin-top:10px;">
      Mengetahui :<br>
      KEPALA PELAKSANA BPBD<br>
      KABUPATEN BANYUMAS
      <div style="margin-top: 54px;">Dwi Irawan Sukma, S.STP., M.Hum</div>
      <div>Pembina Tingkat I (IV/b)</div>
      <div>NIP. 197611111996021001</div>
    </div>

    <?php if (!$isPdf): ?>
      <div class="no-print">
        <button onclick="window.print()">Cetak / Simpan PDF</button>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
