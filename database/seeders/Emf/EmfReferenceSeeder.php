<?php

namespace Database\Seeders\Emf;

use App\Models\Emf\Lga;
use App\Models\Emf\State;
use Illuminate\Database\Seeder;

class EmfReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $states = [
            ['AB', 'Abia'], ['AD', 'Adamawa'], ['AK', 'Akwa Ibom'], ['AN', 'Anambra'],
            ['BA', 'Bauchi'], ['BY', 'Bayelsa'], ['BE', 'Benue'], ['BO', 'Borno'],
            ['CR', 'Cross River'], ['DE', 'Delta'], ['EB', 'Ebonyi'], ['ED', 'Edo'],
            ['EK', 'Ekiti'], ['EN', 'Enugu'], ['FC', 'FCT'], ['GO', 'Gombe'],
            ['IM', 'Imo'], ['JI', 'Jigawa'], ['KD', 'Kaduna'], ['KN', 'Kano'],
            ['KT', 'Katsina'], ['KE', 'Kebbi'], ['KO', 'Kogi'], ['KW', 'Kwara'],
            ['LA', 'Lagos'], ['NA', 'Nasarawa'], ['NI', 'Niger'], ['OG', 'Ogun'],
            ['ON', 'Ondo'], ['OS', 'Osun'], ['OY', 'Oyo'], ['PL', 'Plateau'],
            ['RI', 'Rivers'], ['SO', 'Sokoto'], ['TA', 'Taraba'], ['YO', 'Yobe'],
            ['ZA', 'Zamfara'],
        ];

        foreach ($states as $index => [$code, $name]) {
            State::query()->updateOrCreate(['code' => $code], [
                'name' => $name,
                'sort_order' => $index + 1,
            ]);
        }

        $specific = [
            'LA' => ['Ikeja', 'Eti-Osa', 'Ojo', 'Ikorodu', 'Surulere'],
            'FC' => ['Abuja Municipal', 'Gwagwalada', 'Kuje'],
            'KN' => ['Nassarawa', 'Fagge', 'Dala'],
            'RI' => ['Port Harcourt', 'Obio/Akpor', 'Eleme'],
            'OY' => ['Ibadan North', 'Egbeda', 'Oluyole'],
            'EN' => ['Enugu North', 'Nsukka'],
            'KD' => ['Kaduna North', 'Chikun'],
            'DE' => ['Warri South', 'Oshimili South'],
            'OG' => ['Abeokuta South', 'Ado-Odo/Ota'],
            'AN' => ['Onitsha North', 'Awka South'],
        ];

        foreach ($states as [$code, $name]) {
            $names = $specific[$code] ?? [$name.' Central', $name.' North'];
            foreach ($names as $position => $lgaName) {
                Lga::query()->updateOrCreate(['id' => 'lga_'.strtolower($code).'_'.($position + 1)], [
                    'state_code' => $code,
                    'name' => $lgaName,
                ]);
            }
        }
    }
}
