<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Geocoding\OrsGeocodingService;
use Illuminate\Http\Request;

class GeocodingController extends Controller
{
    public function search(Request $request, OrsGeocodingService $svc)
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 3) {
            return response()->json(['ok' => true, 'data' => []]);
        }
        return response()->json($svc->search($q));
    }

    public function reverse(Request $request, OrsGeocodingService $svc)
    {
        $lat = (float) $request->query('lat');
        $lng = (float) $request->query('lng');

        if (!$lat || !$lng) {
            return response()->json(['ok' => false, 'message' => 'lat/lng required']);
        }

        return response()->json($svc->reverse($lat, $lng));
    }
}
