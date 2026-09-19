# DESIGN.md — Fluxa

Arah visual ini ditentukan oleh pemilik produk. Dokumen ini hanya mencatat
jawabannya; tidak ada butir di bawah yang dikarang oleh agent.

## Design Read

Aplikasi keuangan bersama (keluarga, RT, komunitas kecil) di Indonesia, dipakai
harian di HP, dengan bahasa visual **ringkas dan gesit**.

Dial: **ENERGY 2 / RHYTHM 2 / MOTION 1**

## Karakter

**Ringkas & gesit.** Terasa seperti aplikasi dompet digital: padat, cepat,
banyak aksi cepat. Yang ditonjolkan adalah kecepatan mencatat, bukan kedalaman
laporan.

## Palet

Tiga warna inti, satu aksen. Netral (putih, abu, hitam) tidak dihitung.

| Peran            | Light                | Dark      |
| ---------------- | -------------------- | --------- |
| Inti / identitas | `#17324F` biru tua   | `#0C1926` |
| Latar            | `#F7F7F5` abu hangat | `#0F1419` |
| Aksen            | `#D97706` amber      | `#C8801E` |

**Arah uang sebagai warna.** Pemasukan memakai biru, pengeluaran memakai amber.
Warna di sini mengkodekan arah uang, bukan identitas kategori.

Warna mark chart berbeda dari warna teks karena alasan terukur: `#17324F` gagal
uji chroma sebagai mark (terbaca abu-abu di grafik), jadi chart memakai langkah
biru yang lebih terang pada hue yang sama.

| Peran chart | Light     | Dark      |
| ----------- | --------- | --------- |
| Pemasukan   | `#17639B` | `#3E97D4` |
| Pengeluaran | `#D97706` | `#C8801E` |

Keempat nilai chart lolos seluruh pemeriksaan validator palet (lightness band,
chroma floor, separasi CVD, normal-vision floor, kontras terhadap surface) pada
mode masing-masing.

## Tipografi

Dua keluarga huruf, dengan pembagian tugas yang jelas:

- **Teks**: Instrument Sans (bawaan starter kit).
- **Nominal**: Archivo, dipakai khusus untuk angka rupiah supaya nominal terasa
  lebih tegas dan langsung terbaca sebagai angka, bukan bagian dari kalimat.

Angka selalu memakai `font-variant-numeric: tabular-nums` supaya digit sejajar
saat nominal ditumpuk dalam daftar.

## Energi

**Tenang, rapi, sedikit gerak.** Fokus pada keterbacaan angka. Transisi hanya
saat berpindah halaman dan menekan tombol.
