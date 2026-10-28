# 🛠️ Cara Setup Hosts File untuk warehouse.local

## ⚠️ Perlu Administrator Privilege

Edit file hosts membutuhkan Administrator privilege.

---

## Langkah-by-Langkah

### 1. Buka Notepad sebagai Administrator

1. Tekan `Windows + S`, ketik **Notepad**
2. Klik kanan Notepad → **Run as administrator**
3. Jika diminta UAC, klik **Yes**

### 2. Buka File Hosts

Di Notepad (Admin), klik:
- **File** → **Open**
- Pilih **All Files (\*.\*)**
- Masukkan path:  
  ```
  C:\Windows\System32\drivers\etc\hosts
  ```
- Klik **Open**

### 3. Tambah Entry warehouse.local

Cari bagian `# Localhost` atau di akhir file, tambahkan:

```
# warehouse.local - Laravel App
127.0.0.1  warehouse.local
::1  warehouse.local
```

✅ Contoh akhir file hosts:
```
127.0.0.1  localhost
::1  localhost

# warehouse.local - Laravel App
127.0.0.1  warehouse.local
::1  warehouse.local
```

### 4. Save File

- Tekan `Ctrl + S` untuk save
- Tutup Notepad

### 5. Flush DNS Cache

Buka **Command Prompt** sebagai Administrator, jalankan:

```cmd
ipconfig /flushdns
```

### 6. Test

Buka browser, akses:
- `http://warehouse.local`
- `https://warehouse.local` (jika SSL sudah aktif)

---

## 🔍 Troubleshooting

### Still "This site can't be reached"?

1. Pastikan Nginx sudah reload:
   ```cmd
   C:\laragon\laragon.exe --reload
   ```

2. Cek apakah entry sudah ada di hosts:
   ```cmd
   findstr warehouse C:\Windows\System32\drivers\etc\hosts
   ```

3. Cek apakah Laragon Web Server running:
   - Buka Laragon UI
   - Klik **Stop All**
   - Klik **Start All**

4. Cek log Nginx:
   ```
   D:\111\Andhika\Code\projectRBV1.0\storage\logs\nginx_error.log
   ```

---

## 📝catatan

- Setiap kali tambah domain baru, edit file hosts lagi
- Jangan hapus entry `127.0.0.1 localhost` atau `::1 localhost`
- Entry harus dalam format: `IP  domain.com`
