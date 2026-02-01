/**
 * Capacitor Persistent Background Location Tracker
 * Uses @capacitor-community/background-geolocation to maintain tracking even when app is minimized
 */

import { registerPlugin } from '@capacitor/core';
const BackgroundGeolocation = registerPlugin('BackgroundGeolocation');

class CapacitorLocationTracker {
    constructor() {
        this.watchId = null;
    }

    static isNative() {
        return typeof window !== 'undefined' &&
            window.Capacitor !== undefined &&
            window.Capacitor.getPlatform() !== 'web';
    }

    async startTracking(callback) {
        if (!CapacitorLocationTracker.isNative()) return;

        // BackgroundGeolocation.addWatcher will run in a foreground service on Android
        this.watchId = await BackgroundGeolocation.addWatcher(
            {
                backgroundMessage: "Aplikasi pengantaran sedang melacak lokasi Anda.",
                backgroundTitle: "Driver Tracking Aktif",
                requestPermissions: true,
                stale: false,
                distanceFilter: 10 // Update setiap 10 meter (hemat baterai tapi akurat)
            },
            (location, error) => {
                if (error) {
                    if (error.code === "NOT_AUTHORIZED") {
                        if (window.confirm("Aplikasi butuh izin lokasi 'Allow all the time'. Buka pengaturan?")) {
                            BackgroundGeolocation.openSettings();
                        }
                    }
                    return;
                }

                if (location && callback) {
                    callback({
                        latitude: location.latitude,
                        longitude: location.longitude,
                        accuracy: location.accuracy,
                    });
                }
            }
        );

        console.log('Started persistent background tracking:', this.watchId);
    }

    async stopTracking() {
        if (this.watchId) {
            await BackgroundGeolocation.removeWatcher({ id: this.watchId });
            this.watchId = null;
            console.log('Stopped background tracking');
        }
    }
}

window.CapacitorLocationTracker = CapacitorLocationTracker;
