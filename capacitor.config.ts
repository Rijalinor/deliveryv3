import type { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'com.deliveryapp.driver',
  appName: 'Delivery Driver',
  webDir: 'public',
  server: {
    androidScheme: 'https',
    // Production URL via Ngrok
    url: 'https://unremonstrating-inconstantly-cynthia.ngrok-free.dev',
    cleartext: false,
  },
  plugins: {
    Geolocation: {
      backgroundLocationIndicator: true,
    },
  },
};

export default config;
