# 📱 Quick APK Build Guide

This is a simplified version of [BUILD_ANDROID_APK.md](../BUILD_ANDROID_APK.md)

## Prerequisites
- ✅ Android Studio installed
- ✅ Java JDK 17 installed
- ✅ Node.js v18+ installed

## Quick Build Steps

### 1. Install & Build Web Assets
```bash
npm install
npm run build
```

### 2. Initialize Capacitor
```bash
npx cap init
# App name: Delivery Driver
# App ID: com.deliveryapp.driver
# Web dir: public

npx cap add android
npx cap sync
```

### 3. Open in Android Studio
```bash
npx cap open android
```

### 4. Build APK
In Android Studio:
- **Build → Build Bundle(s) / APK(s) → Build APK(s)**
- Wait for build to complete
- APK will be at: `android/app/build/outputs/apk/debug/app-debug.apk`

## Done! 🎉

Install APK to your Android phone and test.

---

**Need detailed guide?** See [BUILD_ANDROID_APK.md](../BUILD_ANDROID_APK.md)
