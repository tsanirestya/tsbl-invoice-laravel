<?php

namespace App\Services;

use App\Models\EmployeePartnerCheck;
use App\Models\Partner;
use App\Models\User;

class EmployeePartnerCheckService
{
    public function runFullCheck(): array
    {
        $partners = Partner::whereNull('deleted_at')->get();
        $users    = User::all();
        $matches  = [];

        foreach ($partners as $partner) {
            foreach ($users as $user) {
                $found = $this->findMatches($partner, $user);
                foreach ($found as $match) {
                    EmployeePartnerCheck::firstOrCreate(
                        [
                            'partner_id' => $partner->id,
                            'user_id'    => $user->id,
                            'match_type' => $match['type'],
                        ],
                        [
                            'match_detail' => $match['detail'],
                            'is_reviewed'  => false,
                            'created_at'   => now(),
                        ]
                    );
                    $matches[] = array_merge($match, [
                        'partner' => $partner->nama_partner,
                        'user'    => $user->name,
                    ]);
                }
            }
        }

        return $matches;
    }

    private function findMatches(Partner $partner, User $user): array
    {
        $matches = [];

        // Phone match
        if ($partner->pic_partner_phone && $user->phone) {
            if ($this->similarPhone($partner->pic_partner_phone, $user->phone)) {
                $matches[] = [
                    'type'   => 'PHONE',
                    'detail' => "Phone partner ({$partner->pic_partner_phone}) mirip dengan phone karyawan ({$user->phone})",
                ];
            }
        }

        // Email match
        if ($partner->pic_partner_email && $user->email) {
            if (strtolower($partner->pic_partner_email) === strtolower($user->email)) {
                $matches[] = [
                    'type'   => 'EMAIL',
                    'detail' => "Email partner ({$partner->pic_partner_email}) sama dengan email karyawan ({$user->email})",
                ];
            }
        }

        // Name similarity
        if ($partner->pic_partner && $user->name) {
            if ($this->similarName($partner->pic_partner, $user->name)) {
                $matches[] = [
                    'type'   => 'NAME',
                    'detail' => "Nama PIC partner ({$partner->pic_partner}) mirip dengan nama karyawan ({$user->name})",
                ];
            }
        }

        return $matches;
    }

    private function similarPhone(string $a, string $b): bool
    {
        // Normalize: strip non-digits, compare last 8 digits
        $a = preg_replace('/\D/', '', $a);
        $b = preg_replace('/\D/', '', $b);
        if (strlen($a) < 8 || strlen($b) < 8) return false;
        return substr($a, -8) === substr($b, -8);
    }

    private function similarName(string $a, string $b): bool
    {
        $a = strtolower(trim($a));
        $b = strtolower(trim($b));
        if ($a === $b) return true;
        similar_text($a, $b, $pct);
        return $pct >= 80;
    }
}
