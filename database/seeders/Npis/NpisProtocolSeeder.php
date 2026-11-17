<?php

namespace Database\Seeders\Npis;

use App\Models\Npis\ProtocolCategory;
use App\Models\Npis\ProtocolZone;
use Illuminate\Database\Seeder;

class NpisProtocolSeeder extends Seeder
{
    public function run(): void
    {
        $zones = [
            ['Z1', 'Main hall'],
            ['Z2', 'VIP lounge'],
            ['Z3', 'Backstage'],
            ['Z4', 'Exhibition'],
            ['Z5', 'Media centre'],
            ['Z6', 'Secretariat'],
            ['Z7', 'Catering & back of house'],
        ];

        foreach ($zones as $index => [$code, $name]) {
            ProtocolZone::updateOrCreate(['code' => $code], ['name' => $name, 'sort_order' => $index + 1]);
        }

        $full = 'full';
        $escort = 'escort';
        $none = 'none';

        $categories = [
            ['VP', 'VIP & Special Guests', 'VIPs and special guests on the protocol list', '#C9A227', '#000000', true, false, [$full, $full, $escort, $full, $none, $none, $none]],
            ['SK', 'Speakers', 'Speakers and panellists', '#5B2A86', '#FFFFFF', true, false, [$full, $full, $full, $full, $escort, $none, $none]],
            ['DL', 'Delegates', 'Registered delegates', '#0F7A4D', '#FFFFFF', false, false, [$full, $none, $none, $full, $none, $none, $none]],
            ['IM', 'Intelligence Managers', 'Intelligence managers', '#1F4FB4', '#FFFFFF', false, false, [$full, $none, $none, $full, $none, $none, $none]],
            ['EX', 'Exhibitors', 'Exhibitors and stand crew', '#F28C0F', '#000000', false, false, [$full, $none, $none, $full, $none, $none, $none]],
            ['MD', 'Media', 'Photography, videography and press crew', '#C2185B', '#FFFFFF', true, false, [$full, $escort, $escort, $full, $full, $none, $none]],
            ['OC', 'Organising Committee', 'Sub-committee, secretariat, ushers and greeters', '#6D4C41', '#FFFFFF', true, false, [$full, $full, $full, $full, $full, $full, $full]],
            ['SC', 'Security & Park Marshals', 'Security and park marshals (issued by the Security lead)', '#111111', '#FFFFFF', true, true, [$full, $full, $full, $full, $full, $full, $full]],
            ['SV', 'Service Providers', 'Caterers, sound, lighting, LED crew, logistics, cleaning', '#A7B0B8', '#000000', true, false, [$escort, $none, $none, $escort, $none, $none, $full]],
            ['MC', 'Medical', 'Medical and first-aid team (red cross on the tag)', '#FFFFFF', '#000000', true, true, [$full, $full, $full, $full, $full, $full, $full]],
        ];

        foreach ($categories as $index => [$code, $name, $members, $color, $textColor, $photoRequired, $reserved, $zoneAccess]) {
            ProtocolCategory::updateOrCreate(['code' => $code], [
                'name' => $name,
                'members' => $members,
                'color' => $color,
                'text_color' => $textColor,
                'photo_required' => $photoRequired,
                'reserved' => $reserved,
                'zone_access' => $zoneAccess,
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
        }
    }
}
