# WhatsApp Integration Troubleshooting Guide
## Volunteer Management System

### 🚨 Common Issues & Solutions

#### Issue 1: "Puppeteer mode not available" atau "Mode puppeteer not available"

**Penyebab:** Chrome browser tidak ditemukan atau dependencies Linux belum terinstall.

**Solusi:**

**Opsi 1: Install Google Chrome (Direkomendasikan)**
```bash
# Download dan install Chrome dari:
# https://www.google.com/chrome/

# Setelah install, restart service:
pkill -f "hybrid-whatsapp-service"
node start-hybrid-whatsapp.js
```

**Opsi 2: Install Dependencies Linux (untuk Puppeteer bundled Chrome)**
```bash
# Untuk Ubuntu/Debian:
sudo apt update
sudo apt install -y libnss3-dev libatk-bridge2.0-dev libdrm2 libxkbcommon-dev libxcomposite-dev libxdamage-dev libxrandr2 libgbm-dev libxss1 libasound2

# Atau jalankan script otomatis:
node fix-linux-deps.js
```

**Opsi 3: Install Puppeteer dengan bundled Chrome**
```bash
node install-puppeteer.js
```

**Opsi 4: Gunakan Real Mode sebagai alternatif**
- Install Chrome terlebih dahulu
- Switch ke Real Mode di admin panel
- Kontrol WhatsApp secara manual

---

#### Issue 2: "Could not find Chrome" error saat connect Puppeteer

**Error message:** 
```
❌ Error starting Puppeteer service: Error: Could not find Chrome (ver. 115.0.5790.102)
```

**Solusi:**

1. **Install Chrome dan restart service**
2. **Atau gunakan Real Mode untuk sementara**
3. **Check diagnosis dengan script:**
   ```bash
   node fix-puppeteer.js
   ```

---

#### Issue 3: "libnss3.so: cannot open shared object file" (Linux/WSL)

**Error message:**
```
error while loading shared libraries: libnss3.so: cannot open shared object file: No such file or directory
```

**Solusi untuk Ubuntu/Debian/WSL:**
```bash
# Install dependencies yang diperlukan:
sudo apt update
sudo apt install -y libnss3-dev libatk-bridge2.0-dev libdrm2 libxkbcommon-dev libxcomposite-dev libxdamage-dev libxrandr2 libgbm-dev libxss1 libasound2

# Test setelah install:
node install-puppeteer.js
```

**Solusi otomatis:**
```bash
node fix-linux-deps.js
```

---

#### Issue 4: QR Code tidak muncul di Puppeteer mode

**Penyebab:** Browser belum selesai loading atau error saat startup.

**Solusi:**
1. **Tunggu 3-5 detik setelah klik "Connect WhatsApp"**
2. **Refresh halaman admin panel**
3. **Check console log service untuk error**
4. **Coba disconnect dan connect kembali**

---

#### Issue 5: "WhatsApp not connected" saat send test message

**Penyebab:** Belum scan QR code atau connection terputus.

**Solusi:**
1. **Pastikan sudah scan QR code dengan phone**
2. **Klik "Confirm Connection" jika sudah scan**
3. **Check status connection di admin panel**
4. **Restart jika perlu**

---

### 🔧 Mode-Specific Solutions

#### Mock Mode (Selalu Tersedia)
```bash
# Switch ke Mock Mode:
curl -X POST http://localhost:3001/switch-mode -d '{"new_mode":"mock"}'

# Features:
✅ Development testing
✅ Simulate message sending  
✅ No real WhatsApp required
✅ Perfect untuk development
```

#### Real Mode (Perlu Chrome)
```bash
# Requirements:
1. Google Chrome installed
2. Switch to Real Mode in admin panel
3. Manual QR scan in Chrome window

# Features:
✅ Real WhatsApp Web
✅ Manual message sending
✅ Real QR code scanning
⚠️  Requires manual interaction
```

#### Puppeteer Mode (Full Automation)
```bash
# Requirements:
1. Chrome browser OR Linux dependencies
2. Puppeteer package installed
3. All libraries available

# Features:
✅ Fully automated
✅ Auto QR code capture
✅ Auto message sending
✅ Connection monitoring
✅ No manual interaction needed
```

---

### 📋 Diagnostic Commands

#### Check service health:
```bash
curl http://localhost:3001/health
```

#### Check available modes:
```bash
curl http://localhost:3001/health | grep available_modes
```

#### Get detailed diagnosis:
```bash
node fix-puppeteer.js
```

#### Test message sending:
```bash
curl -X POST http://localhost:3001/test-message \
  -H 'Content-Type: application/json' \
  -d '{"phone_number":"628123456789"}'
```

---

### 🚀 Quick Setup Guides

#### For Development (Mock Mode):
```bash
1. node start-hybrid-whatsapp.js
2. Open: http://localhost:3000/settings/whatsapp
3. Keep Mock Mode selected
4. Click "Simulate QR Scan"
5. Test complaint forwarding
```

#### For Production (Puppeteer Mode):
```bash
1. Install Chrome: https://www.google.com/chrome/
2. Install dependencies: node fix-linux-deps.js
3. Start service: node start-hybrid-whatsapp.js
4. Switch to Puppeteer mode in admin panel
5. Connect and scan QR code
6. Test with "Send Test Message"
```

#### For Manual Control (Real Mode):
```bash
1. Install Chrome
2. Start service: node start-hybrid-whatsapp.js  
3. Switch to Real mode in admin panel
4. Click "Connect WhatsApp" (opens Chrome)
5. Scan QR code in Chrome window
6. Click "Confirm Real Connection"
7. Send messages manually
```

---

### 🔍 Advanced Troubleshooting

#### Service won't start:
```bash
# Check if port is in use:
netstat -an | grep 3001

# Kill existing processes:
pkill -f "hybrid-whatsapp-service"

# Start with debug:
WHATSAPP_DEBUG=true node start-hybrid-whatsapp.js
```

#### Admin panel can't connect:
```bash
# Check service is running:
curl http://localhost:3001/health

# Check firewall/network:
telnet localhost 3001

# Restart both services:
pkill -f "hybrid-whatsapp-service"
# Restart admin panel too
```

#### Browser automation issues:
```bash
# For WSL/Linux, install additional packages:
sudo apt install -y xvfb

# Run with virtual display:
xvfb-run -a node start-hybrid-whatsapp.js
```

---

### 💡 Best Practices

#### Development Phase:
1. ✅ Start with Mock Mode
2. ✅ Test all complaint forwarding flows
3. ✅ Configure department mappings
4. ✅ Test message templates

#### Testing Phase:
1. ✅ Switch to Real Mode
2. ✅ Test with real WhatsApp account
3. ✅ Verify manual message sending
4. ✅ Test QR code scanning

#### Production Phase:
1. ✅ Install all dependencies
2. ✅ Use Puppeteer Mode for automation
3. ✅ Monitor connection status
4. ✅ Setup alerts for failures

---

### 📞 Support Resources

#### Documentation:
- [WhatsApp Integration Guide](./WHATSAPP_INTEGRATION_GUIDE.md)
- [API Reference](./WHATSAPP_INTEGRATION_GUIDE.md#api-endpoints)

#### Diagnostic Tools:
- `node fix-puppeteer.js` - Diagnose Puppeteer issues
- `node fix-linux-deps.js` - Fix Linux dependencies  
- `node install-puppeteer.js` - Install/test Puppeteer

#### Test Scripts:
- `node test-simple-service.js` - Test mock service
- Manual test via admin panel

---

### 🆘 Emergency Fallbacks

#### If Puppeteer completely fails:
1. ✅ Switch to Real Mode
2. ✅ Use Mock Mode for development
3. ✅ Manual message forwarding as backup

#### If service won't start:
1. ✅ Check logs for errors
2. ✅ Restart system services
3. ✅ Use manual WhatsApp forwarding

#### If admin panel disconnects:
1. ✅ Refresh browser page
2. ✅ Restart WhatsApp service
3. ✅ Check network connectivity

---

**Last Updated:** July 14, 2025  
**Version:** 1.1.0