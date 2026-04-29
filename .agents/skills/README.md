# Daftar Skill POS KanSor

- `users`
  Deskripsi singkat: Skill untuk CRUD pengguna admin dan petugas lokal.
  Path: `.agents/skills/users/skill.md`
  Digunakan saat: membuat atau mengubah modul pengguna, validasi role, status aktif, dan guard admin.

- `suppliers`
  Deskripsi singkat: Skill untuk manajemen data pemasok beserta status aktif dan potongan kantin.
  Path: `.agents/skills/suppliers/skill.md`
  Digunakan saat: membuat CRUD pemasok, filter pemasok aktif, dan sinkronisasi supplier.

- `foods`
  Deskripsi singkat: Skill untuk manajemen makanan yang terhubung ke pemasok.
  Path: `.agents/skills/foods/skill.md`
  Digunakan saat: membuat CRUD makanan, validasi relasi supplier, dan dropdown makanan transaksi.

- `transactions`
  Deskripsi singkat: Skill untuk input transaksi harian petugas, koreksi admin, dan perhitungan total.
  Path: `.agents/skills/transactions/skill.md`
  Digunakan saat: menulis form request transaksi, service perhitungan, policy sale, atau konfirmasi status.

- `reports`
  Deskripsi singkat: Skill untuk rekap harian, bulanan, total kantin, dan ringkasan pendapatan.
  Path: `.agents/skills/reports/skill.md`
  Digunakan saat: membuat laporan kantin, command recalculation, dan agregasi per pemasok.

- `rencana-rancangan`
  Deskripsi singkat: Referensi rencana implementasi dan integrasi backend POS Kantin.
  Path: `.agents/skills/rencana-rancangan/PLAN.md`
  Digunakan saat: menyelaraskan patch implementasi dengan roadmap proyek.
