# DESIGN.md — Fluxa

Arah visual ini ditentukan oleh pemilik produk. Dokumen ini hanya mencatat
jawabannya; tidak ada butir di bawah yang dikarang oleh agent.

## Design Read

Aplikasi keuangan bersama (keluarga, RT, komunitas kecil) di Indonesia, dipakai
harian di HP, dengan bahasa visual **ringkas dan gesit**.

Arah visual diperbarui mengikuti prototype KantongKita milik pemilik produk:
hijau tua sebagai warna inti, latar putih hangat, kartu ber-radius besar,
lencana ikon bulat, dan ringkasan saldo sebagai satu kartu hijau menonjol.

Dial: **ENERGY 2 / RHYTHM 2 / MOTION 1**

## Karakter

**Ringkas & gesit.** Terasa seperti aplikasi dompet digital: padat, cepat,
banyak aksi cepat. Yang ditonjolkan adalah kecepatan mencatat, bukan kedalaman
laporan.

## Palet

Hijau tua sebagai inti, putih hangat sebagai latar, satu aksen amber.

| Peran            | Light                  | Dark      |
| ---------------- | ---------------------- | --------- |
| Inti / identitas | `#15653F` hijau tua    | `#0E3A26` |
| Latar            | `#F5F4F1` putih hangat | `#0F1512` |
| Teks hijau       | `#1B6B4A` (5.88:1)     | `#4FB07C` |
| Teks amber       | `#92400E` (7.09:1)     | `#D99A4A` |
| Aksen            | `#D98026` amber        | `#C0842A` |

Teks putih di atas kartu hero `#15653F` memberi 7.08:1.

### Warna grafik

Pemasukan vs pengeluaran **tidak** memakai pasangan hijau/merah seperti
prototype. Pasangan itu gagal uji buta warna: ΔE hanya 5.0 (deutan) di light dan
0.9 di dark, di bawah ambang mana pun, sehingga sekitar 8% pembaca pria tidak
bisa membedakan kedua batang. Penggantinya hijau/amber, yang lolos seluruh
pemeriksaan.

| Peran       | Light     | Dark      |
| ----------- | --------- | --------- |
| Pemasukan   | `#2E8B57` | `#3A9668` |
| Pengeluaran | `#D98026` | `#C0842A` |

Donat kategori memakai palet kategorikal lima warna yang lolos validator pada
kedua mode, dengan nama, persentase, dan nominal tertulis di legenda sehingga
identitas tidak pernah bergantung pada warna saja.

| Slot | Light     | Dark      |
| ---- | --------- | --------- |
| 1    | `#2E8B57` | `#3A9668` |
| 2    | `#2F72C4` | `#4585C9` |
| 3    | `#E08A1E` | `#C0842A` |
| 4    | `#D6455A` | `#CC5066` |
| 5    | `#8155C6` | `#8768C2` |

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
