import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

window.mapPicker = (cfg) => ({
  query: '',
  results: [],
  lat: cfg.lat,
  lng: cfg.lng,
  address: cfg.address,
  map: null,
  marker: null,
  geocodeUrl: cfg.geocodeUrl,
  reverseUrl: cfg.reverseUrl,

  init() {
    const startLat = this.lat ?? (window.WAREHOUSE_LAT ?? -3.3190);
    const startLng = this.lng ?? (window.WAREHOUSE_LNG ?? 114.5900);

    this.map = L.map(this.$refs.map).setView([startLat, startLng], 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors',
    }).addTo(this.map);

    this.marker = L.marker([startLat, startLng], { draggable: true }).addTo(this.map);

    this.marker.on('dragend', async (e) => {
      const p = e.target.getLatLng();
      this.setPoint(p.lat, p.lng, true);
    });

    this.map.on('click', async (e) => {
      this.setPoint(e.latlng.lat, e.latlng.lng, true);
    });

    // jika state sudah ada, set marker
    if (this.lat && this.lng) {
      this.marker.setLatLng([this.lat, this.lng]);
      this.map.setView([this.lat, this.lng], 15);
    }
  },

  async doSearch() {
    if (!this.query || this.query.length < 3) {
      this.results = [];
      return;
    }
    const url = new URL(this.geocodeUrl, window.location.origin);
    url.searchParams.set('q', this.query);

    const res = await fetch(url.toString(), { credentials: 'same-origin' });
    const json = await res.json();
    this.results = json?.data ?? [];
  },

  async pickResult(r) {
    this.results = [];
    this.query = r.label;
    await this.setPoint(r.lat, r.lng, false);
    // address ikut label
    this.address = r.label;
  },

  async setPoint(lat, lng, doReverse) {
    this.lat = Number(lat);
    this.lng = Number(lng);
    this.marker.setLatLng([this.lat, this.lng]);
    this.map.setView([this.lat, this.lng], 15);

    if (doReverse) {
      const url = new URL(this.reverseUrl, window.location.origin);
      url.searchParams.set('lat', this.lat);
      url.searchParams.set('lng', this.lng);

      const res = await fetch(url.toString(), { credentials: 'same-origin' });
      const json = await res.json();
      const label = json?.data?.label;
      if (label) this.address = label;
    }
  },
});
