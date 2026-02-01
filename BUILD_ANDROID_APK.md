# 📱 Build Android APK - Step by Step Guide

Saya sudah siapkan semua konfigurasi Capacitor. Sekarang tinggal build APK-nya di komputer kamu.

## 📋 Prerequisites Check

Pastikan sudah install:
- ✅ Android Studio (sudah)
- ✅ Node.js v18+ (sudah)
- ⚠️ **Java JDK 17** - Check dengan command: `java -version`

Kalau JDK belum ada, download dari: https://adoptium.net/temurin/releases/

---

## 🚀 Build Steps

### 1. Install Dependencies
```bash
cd /home/rijal/projectlaravel/deliveryv3
npm install
```

### 2. Build Web Assets
```bash
npm run build
```

### 3. Initialize Capacitor & Add Android
```bash
npx cap init
# Pilih:
# - App name: Delivery Driver
# - App ID: com.deliveryapp.driver
# - Web dir: public

npx cap add android
```

### 4. Sync Assets ke Android
```bash
npx cap sync
```

### 5. Konfigurasi Android Permissions
Buka file: `android/app/src/main/AndroidManifest.xml`

Tambahkan permissions ini **SEBELUM** tag `<application>`:
```xml
<!-- Location Permissions -->
<uses-permission android:name="android.permission.ACCESS_COARSE_LOCATION" />
<uses-permission android:name="android.permission.ACCESS_FINE_LOCATION" />
<uses-permission android:name="android.permission.ACCESS_BACKGROUND_LOCATION" />
<uses-permission android:name="android.permission.FOREGROUND_SERVICE" />
<uses-permission android:name="android.permission.FOREGROUND_SERVICE_LOCATION" />
<uses-permission android:name="android.permission.INTERNET" />
```

### 6. Update Server URL (Production)
Edit `capacitor.config.ts`, uncomment dan set production URL:
```typescript
server: {
    androidScheme: 'https',
    url: 'https://your-domain.com',  // ← Ganti dengan domain kamu
    cleartext: true
},
```

### 7. Open Android Studio
```bash
npx cap open android
```

### 8. Build APK dari Android Studio
1. **Build > Build Bundle(s) / APK(s) > Build APK(s)**
2. Tunggu sampai selesai (5-10 menit pertama kali)
3. APK akan ada di: `android/app/build/outputs/apk/debug/app-debug.apk`

---

## 🧪 Testing

### Test di Emulator
1. Buka Android Studio
2. **Device Manager** (icon HP di toolbar)
3. Create/Start Emulator
4. Run app: **Run > Run 'app'**

### Test di HP Asli
1. Enable **Developer Options** di HP
2. Enable **USB Debugging**
3. Colok HP ke laptop
4. Run app: **Run > Run 'app'**

---

## 📦 Generate Release APK (Production)

### 1. Create Keystore
```bash
cd android/app
keytool -genkey -v -keystore delivery-driver.keystore -alias delivery -keyalg RSA -keysize 2048 -validity 10000
```

### 2. Edit `android/app/build.gradle`
Tambahkan sebelum `android {`:
```gradle
def keystoreProperties = new Properties()
def keystorePropertiesFile = rootProject.file('keystore.properties')
if (keystorePropertiesFile.exists()) {
    keystoreProperties.load(new FileInputStream(keystorePropertiesFile))
}
```

Di dalam `android { ... }`, tambahkan:
```gradle
signingConfigs {
    release {
        keyAlias keystoreProperties['keyAlias']
        keyPassword keystoreProperties['keyPassword']
        storeFile keystoreProperties['storeFile'] ? file(keystoreProperties['storeFile']) : null
        storePassword keystoreProperties['storePassword']
    }
}

buildTypes {
    release {
        signingConfig signingConfigs.release
        minifyEnabled false
        proguardFiles getDefaultProguardFile('proguard-android.txt'), 'proguard-rules.pro'
    }
}
```

### 3. Create `android/keystore.properties`
```properties
storePassword=your_store_password
keyPassword=your_key_password
keyAlias=delivery
storeFile=app/delivery-driver.keystore
```

### 4. Build Release APK
Di Android Studio:
**Build > Generate Signed Bundle / APK > APK**

Atau via command:
```bash
cd android
./gradlew assembleRelease
```

APK akan ada di: `android/app/build/outputs/apk/release/app-release.apk`

---

## 🔧 Troubleshooting

### Error: "SDK location not found"
Create `android/local.properties`:
```properties
sdk.dir=/home/rijal/Android/Sdk
```

### Error: Gradle build failed
```bash
cd android
./gradlew clean
./gradlew build
```

### Location tidak jalan
1. Check permissions di Settings > Apps > Delivery Driver > Permissions
2. Allow Location "All the time" (bukan "Only while using")

---

## ✅ Checklist Testing
- [ ] App bisa install dan open
- [ ] Login berhasil
- [ ] Map muncul dengan benar
- [ ] GPS dapat lokasi
- [ ] Bisa klik "Arrived" dan "Done" 
- [ ] Location update terus jalan saat app minimize
- [ ] Notifikasi "App is running in background" muncul

---

## 📝 Notes
- File APK debug bisa langsung di-install tanpa keystore
- File APK release wajib pakai keystore untuk publish ke Play Store
- Keystore **JANGAN SAMPAI HILANG** - simpan di tempat aman!
