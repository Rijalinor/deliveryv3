<div x-data="{ 
    loading: false,
    async requestStartTrip() {
        this.loading = true;
        // Cek Native Capacitor
        if (typeof window.Capacitor !== 'undefined' && window.Capacitor.getPlatform() !== 'web') {
             try {
                 const { Geolocation } = window.Capacitor.Plugins;
                 let perm = await Geolocation.checkPermissions();
                 if (perm.location !== 'granted' && perm.coarseLocation !== 'granted') {
                     // Minta izin secara eksplisit jika belum diberikan
                     perm = await Geolocation.requestPermissions();
                 }
                 if (perm.location !== 'granted' && perm.coarseLocation !== 'granted') {
                     $wire.notifyLocationFailed();
                     this.loading = false;
                     return;
                 }
             } catch (e) {
                 console.error('Check permission error:', e);
             }
        } else {
            // Cek Web Browser Geolocation
            const granted = await new Promise((resolve) => {
                if (!navigator.geolocation) {
                    $wire.notifyLocationFailed();
                    return resolve(false);
                }
                navigator.geolocation.getCurrentPosition(
                    () => resolve(true),
                    (err) => {
                        console.warn('Geolocation reject:', err);
                        $wire.notifyLocationFailed();
                        resolve(false);
                    },
                    { enableHighAccuracy: true, timeout: 5000 }
                );
            });
            if (!granted) {
                this.loading = false;
                return;
            }
        }
        
        // Panggil fungsi Livewire jika lokasi aman
        $wire.startTrip();
    }
}">
    <button x-on:click="requestStartTrip()" :disabled="loading" class="w-full py-6 text-2xl font-black uppercase tracking-wider shadow-xl rounded-2xl bg-success-600 hover:bg-success-700 text-white transition-all transform active:scale-95 disabled:opacity-50">
        <span x-show="!loading" class="flex items-center justify-center gap-2">
            <x-heroicon-o-play-circle class="w-8 h-8" />
            MULAI TRIP SEKARANG
        </span>
        <span x-show="loading" class="flex items-center justify-center gap-2 animate-pulse">
            <svg class="animate-spin h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Memeriksa Lokasi...
        </span>
    </button>
</div>
