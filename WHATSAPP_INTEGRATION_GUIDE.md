# WhatsApp Integration Guide
## Volunteer Management System

### Overview

The WhatsApp integration system provides three different modes for sending complaint notifications to relevant departments:

1. **Mock Mode** - Development simulation (always available)
2. **Real Mode** - Chrome browser integration (requires Chrome)
3. **Puppeteer Mode** - Full automation (requires Puppeteer dependency)

### Quick Start

1. **Start the Hybrid WhatsApp Service:**
   ```bash
   node start-hybrid-whatsapp.js
   ```

2. **Access Admin Panel:**
   ```
   http://localhost:3000/settings/whatsapp
   ```

3. **Choose Your Mode:**
   - Start with Mock Mode for development
   - Switch to Real/Puppeteer Mode when ready

### Available Modes

#### 🧪 Mock Mode
- **Purpose:** Development and testing
- **Requirements:** None (always available)
- **Features:**
  - Simulates WhatsApp functionality
  - No real messages sent
  - Perfect for development workflow
  - QR code simulation

**Usage:**
1. Service starts in Mock mode by default
2. Click "Simulate QR Scan" to connect
3. Test complaint forwarding safely

#### 🌐 Real Mode  
- **Purpose:** Real WhatsApp with manual sending
- **Requirements:** Chrome browser installed
- **Features:**
  - Uses your system Chrome browser
  - Opens WhatsApp Web in dedicated window
  - Real QR code scanning with your phone
  - Manual message sending process

**Usage:**
1. Switch to Real mode via admin panel
2. Click "Connect WhatsApp" (opens Chrome)
3. Scan QR code with your phone
4. Confirm connection in admin panel
5. Messages prepared for manual sending

#### 🤖 Puppeteer Mode
- **Purpose:** Full automation
- **Requirements:** Puppeteer npm package
- **Features:**
  - Fully automated browser control
  - Automatic QR code detection
  - Programmatic message sending
  - No manual intervention needed

**Usage:**
1. Install Puppeteer: `npm install puppeteer`
2. Switch to Puppeteer mode
3. Service automatically handles everything
4. Messages sent automatically

### Installation & Setup

#### Basic Setup (Mock Mode)
```bash
# Clone and navigate to project
cd volunteer-management-backend

# Install basic dependencies (already done)
npm install express cors node-fetch

# Start hybrid service
node start-hybrid-whatsapp.js
```

#### Real Mode Setup
```bash
# Install Chrome browser from:
# https://www.google.com/chrome/

# Service will automatically detect Chrome
# No additional setup needed
```

#### Puppeteer Mode Setup
```bash
# Install Puppeteer dependency
npm install puppeteer

# Service will automatically detect Puppeteer
# Full automation will be available
```

### API Endpoints

The hybrid service provides a unified API that works across all modes:

#### Health Check
```bash
GET http://localhost:3001/health
```

#### Switch Modes
```bash
POST http://localhost:3001/switch-mode
Content-Type: application/json

{
  "new_mode": "mock" | "real" | "puppeteer"
}
```

#### Initialize WhatsApp
```bash
POST http://localhost:3001/initialize
Content-Type: application/json

{
  "session_name": "admin-session"
}
```

#### Send Message
```bash
POST http://localhost:3001/send-message
Content-Type: application/json

{
  "phone_number": "628123456789",
  "message": "Your complaint notification message"
}
```

#### Get Connection Status
```bash
GET http://localhost:3001/status
```

### Admin Panel Integration

#### Mode Selection
- Visual mode selector with current mode indicator
- One-click mode switching
- Automatic capability detection

#### Dynamic UI
- Buttons adapt to current mode
- Mode-specific instructions
- Real-time status updates

#### Connection Management
- Mode-aware connection buttons
- QR code display (when applicable)
- Connection confirmation

### File Structure

```
volunteer-management-backend/
├── start-hybrid-whatsapp.js           # Main startup script
├── hybrid-whatsapp-service.js         # Hybrid service implementation
├── start-simple-whatsapp.js           # Legacy mock service
├── simple-whatsapp-service.js         # Legacy mock implementation
├── start-real-whatsapp.js             # Real mode startup script
├── real-whatsapp-service.js           # Real mode implementation
├── start-puppeteer-whatsapp.js        # Puppeteer startup script
├── puppeteer-whatsapp-service.js      # Puppeteer implementation
├── test-simple-service.js             # Testing script
└── WHATSAPP_INTEGRATION_GUIDE.md      # This guide
```

### Development Workflow

#### Phase 1: Development (Mock Mode)
1. Start with Mock mode
2. Configure department mappings
3. Test complaint forwarding flow
4. Verify message templates

#### Phase 2: Testing (Real Mode)
1. Switch to Real mode
2. Connect with real WhatsApp account
3. Test manual message sending
4. Verify real-world functionality

#### Phase 3: Production (Puppeteer Mode)
1. Install Puppeteer dependencies
2. Switch to Puppeteer mode
3. Enable full automation
4. Monitor automated sending

### Configuration

#### Department Mappings
```javascript
{
  "Teknis": {
    "department_name": "Dinas Teknis",
    "phone_number": "628123456789",
    "contact_person": "Pak Budi"
  },
  "Pelayanan": {
    "department_name": "Dinas Pelayanan",
    "phone_number": "628987654321", 
    "contact_person": "Bu Sari"
  }
  // ... more departments
}
```

#### Message Template
```
Pengaduan Baru - {ticket_number}

📋 Judul: {title}
📂 Kategori: {category}
📝 Deskripsi: {description}
⚡ Prioritas: {priority}
👤 Pelapor: {user_name} ({user_email})
📅 Tanggal: {created_at}

📍 Diteruskan ke: {department_name}
👤 PIC: {contact_person}
```

### Troubleshooting

#### Common Issues

**Service Won't Start:**
```bash
# Check if port 3001 is available
netstat -an | grep 3001

# Kill existing processes if needed
pkill -f "hybrid-whatsapp-service"

# Restart service
node start-hybrid-whatsapp.js
```

**Mode Switch Fails:**
```bash
# Check service health
curl http://localhost:3001/health

# Verify available modes
curl http://localhost:3001/health | grep available_modes
```

**Chrome Not Found (Real Mode):**
```bash
# Install Chrome
# Windows: Download from google.com/chrome
# Linux: sudo apt install google-chrome-stable
# Mac: Download from google.com/chrome
```

**Puppeteer Installation Issues:**
```bash
# Try installing with different flags
npm install puppeteer --unsafe-perm=true
npm install puppeteer --legacy-peer-deps

# Or use system Chrome
npm install puppeteer-core
```

#### Debug Mode
Enable detailed logging by setting environment variable:
```bash
WHATSAPP_DEBUG=true node start-hybrid-whatsapp.js
```

### Security Considerations

#### Production Deployment
- Use HTTPS for admin panel
- Implement proper authentication
- Secure API endpoints
- Regular security updates

#### WhatsApp Account Security
- Use dedicated business account
- Enable two-factor authentication
- Monitor login sessions
- Regular password changes

### Performance Optimization

#### Resource Usage
- Mock Mode: Minimal resources
- Real Mode: Moderate (Chrome process)  
- Puppeteer Mode: Higher (automated browser)

#### Scaling Considerations
- Single WhatsApp account limitation
- Queue management for high volume
- Rate limiting for API protection
- Monitor service health

### Support & Maintenance

#### Monitoring
- Service health checks
- Connection status monitoring
- Message delivery tracking
- Error logging and alerting

#### Updates
- Regular dependency updates
- WhatsApp Web compatibility
- Feature enhancements
- Security patches

---

## Quick Reference

### Start Commands
```bash
# Hybrid service (recommended)
node start-hybrid-whatsapp.js

# Legacy services (if needed)
node start-simple-whatsapp.js      # Mock only
node start-real-whatsapp.js        # Real only  
node start-puppeteer-whatsapp.js   # Puppeteer only
```

### Mode Switching API
```bash
# Switch to Mock
curl -X POST http://localhost:3001/switch-mode -d '{"new_mode":"mock"}'

# Switch to Real  
curl -X POST http://localhost:3001/switch-mode -d '{"new_mode":"real"}'

# Switch to Puppeteer
curl -X POST http://localhost:3001/switch-mode -d '{"new_mode":"puppeteer"}'
```

### Admin Panel URLs
- Settings: `http://localhost:3000/settings/whatsapp`
- Complaints: `http://localhost:3000/complaints`

---

**Last Updated:** July 14, 2025
**Version:** 1.0.0