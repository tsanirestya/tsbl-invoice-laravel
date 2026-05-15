<?php

namespace App\Http\Middleware;

use App\Models\Partner;
use Closure;
use Illuminate\Http\Request;

class ValidateReservationToken
{
    public function handle(Request $request, Closure $next)
    {
        $token   = $request->route('token');
        $partner = Partner::where('reservation_token', $token)->whereNull('deleted_at')->first();

        if (!$partner) {
            abort(403, 'Link reservasi tidak valid atau sudah kedaluwarsa.');
        }

        if (!$partner->isReservationTokenValid()) {
            if ($partner->reservation_suspended) {
                abort(403, 'Akun partner Anda dibekukan sementara. Hubungi TSBL untuk informasi lebih lanjut.');
            }
            abort(403, 'Link reservasi sudah kedaluwarsa. Minta token baru dari tim TSBL.');
        }

        // Device binding check
        $fingerprint = $request->input('device_fingerprint') ?? $request->header('X-Device-Fingerprint');
        if ($fingerprint) {
            $knownDevices = $partner->known_devices ?? [];
            $maxDevices   = $partner->max_devices ?? 3;

            if (!in_array($fingerprint, $knownDevices)) {
                if (count($knownDevices) >= $maxDevices) {
                    abort(403, "Perangkat baru tidak diizinkan. Maksimum {$maxDevices} perangkat per partner. Hubungi TSBL.");
                }
                $knownDevices[] = $fingerprint;
                $partner->update(['known_devices' => $knownDevices]);
            }
        }

        $request->attributes->set('partner', $partner);

        return $next($request);
    }
}
