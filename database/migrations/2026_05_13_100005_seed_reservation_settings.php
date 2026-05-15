<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            ['key' => 'reservation_max_days_before',           'value' => '30',     'label' => 'Reservasi: Maks. hari sebelum kedatangan'],
            ['key' => 'reservation_min_days_before',           'value' => '0',      'label' => 'Reservasi: Min. hari sebelum kedatangan'],
            ['key' => 'reservation_max_per_day_per_partner',   'value' => '20',     'label' => 'Reservasi: Maks. per hari per partner'],
            ['key' => 'reservation_max_per_hour_per_partner',  'value' => '5',      'label' => 'Reservasi: Maks. per jam per partner'],
            ['key' => 'danger_zone_latitude',                  'value' => '-8.7908','label' => 'Danger Zone: Latitude pusat'],
            ['key' => 'danger_zone_longitude',                 'value' => '115.1553','label' => 'Danger Zone: Longitude pusat'],
            ['key' => 'danger_zone_radius_meters',             'value' => '500',    'label' => 'Danger Zone: Radius (meter)'],
            ['key' => 'qr_self_service_enabled',               'value' => '1',      'label' => 'Self-Service QR: Aktif'],
            ['key' => 'default_booking_pass_template',         'value' => 'default','label' => 'Booking Pass: Template default'],
            ['key' => 'spot_check_percentage',                 'value' => '10',     'label' => 'Spot Check: Persentase (%)'],
        ];

        foreach ($settings as $s) {
            DB::table('settings')->updateOrInsert(['key' => $s['key']], $s);
        }
    }

    public function down(): void
    {
        $keys = [
            'reservation_max_days_before', 'reservation_min_days_before',
            'reservation_max_per_day_per_partner', 'reservation_max_per_hour_per_partner',
            'danger_zone_latitude', 'danger_zone_longitude', 'danger_zone_radius_meters',
            'qr_self_service_enabled', 'default_booking_pass_template', 'spot_check_percentage',
        ];
        DB::table('settings')->whereIn('key', $keys)->delete();
    }
};
