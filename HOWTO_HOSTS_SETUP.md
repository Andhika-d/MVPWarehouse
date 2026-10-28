# 🛠️ How to Edit HOSTS File (Windows)

## Step-by-Step:

1. **Open Notepad as Administrator**
   - Press `Win + S`, type "notepad"
   - Right-click Notepad → **Run as administrator**

2. **Open HOSTS file**
   - In Notepad, press `Ctrl + O`
   - Navigate to: `C:\Windows\System32\drivers\etc`
   - Change file type to **All Files (*.*)**
   - Select file `hosts`

3. **Add warehouse.local**
   - Go to end of file
   - Add new line:
     ```
     127.0.0.1      warehouse.local
     ```

4. **Save & Close**
   - Press `Ctrl + S`
   - Close Notepad

5. **Flush DNS Cache**
   - Open Command Prompt as Admin
   - Run:
     ```cmd
     ipconfig /flushdns
     ```

6. **Test**
   - Open browser: `http://warehouse.local`
   - Should show your Laravel app!

---

## 🎯 Verify Virtual Host is Working

After editing hosts, restart Apache in Laragon:
- Click **Stop All** in Laragon
- Click **Start All**

Then try:
- `http://warehouse.local`
- `https://warehouse.local` (if SSL enabled)

---

## ✅ Expected Result

When successful, you should see:
- Laravel login page
- No "This site can't be reached" error
- App serving from `D:/111/Andhika/Code/projectRBV1.0/public`
