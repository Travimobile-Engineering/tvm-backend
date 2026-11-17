<?php

namespace Database\Seeders\Npis;

use App\Models\Npis\Airport;
use App\Models\Npis\Bank;
use App\Models\Npis\Command;
use App\Models\Npis\Rank;
use App\Models\Npis\State;
use App\Models\Npis\Unit;
use Illuminate\Database\Seeder;

class NpisReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $ranks = [
            ['IGP', 'Inspector-General of Police', 'IGP', 'senior_officer'],
            ['DIG', 'Deputy Inspector-General of Police', 'DIG', 'senior_officer'],
            ['AIG', 'Assistant Inspector-General of Police', 'AIG', 'senior_officer'],
            ['CP', 'Commissioner of Police', 'CP', 'senior_officer'],
            ['DCP', 'Deputy Commissioner of Police', 'DCP', 'senior_officer'],
            ['ACP', 'Assistant Commissioner of Police', 'ACP', 'senior_officer'],
            ['CSP', 'Chief Superintendent of Police', 'CSP', 'senior_officer'],
            ['SP', 'Superintendent of Police', 'SP', 'senior_officer'],
            ['DSP', 'Deputy Superintendent of Police', 'DSP', 'senior_officer'],
            ['ASP', 'Assistant Superintendent of Police', 'ASP', 'senior_officer'],
            ['INSP', 'Inspector of Police', 'Insp.', 'inspectorate'],
            ['SGT', 'Sergeant', 'Sgt.', 'rank_and_file'],
            ['CPL', 'Corporal', 'Cpl.', 'rank_and_file'],
            ['PC', 'Police Constable', 'PC', 'rank_and_file'],
            ['CIV', 'Civilian / Stakeholder', 'Civ.', 'civilian'],
        ];

        foreach ($ranks as $index => [$code, $name, $abbreviation, $category]) {
            Rank::updateOrCreate(['code' => $code], [
                'name' => $name,
                'abbreviation' => $abbreviation,
                'category' => $category,
                'sort_order' => $index + 1,
            ]);
        }

        $states = [
            ['AB', 'Abia', 'Umuahia', 'Aba'],
            ['AD', 'Adamawa', 'Yola', 'Mubi'],
            ['AK', 'Akwa Ibom', 'Uyo', 'Eket'],
            ['AN', 'Anambra', 'Awka', 'Onitsha'],
            ['BA', 'Bauchi', 'Bauchi', 'Azare'],
            ['BY', 'Bayelsa', 'Yenagoa', 'Ogbia'],
            ['BE', 'Benue', 'Makurdi', 'Gboko'],
            ['BO', 'Borno', 'Maiduguri', 'Biu'],
            ['CR', 'Cross River', 'Calabar', 'Ikom'],
            ['DE', 'Delta', 'Asaba', 'Warri'],
            ['EB', 'Ebonyi', 'Abakaliki', 'Afikpo'],
            ['ED', 'Edo', 'Benin City', 'Auchi'],
            ['EK', 'Ekiti', 'Ado-Ekiti', 'Ikere'],
            ['EN', 'Enugu', 'Enugu', 'Nsukka'],
            ['FC', 'Federal Capital Territory', 'Garki', 'Gwagwalada'],
            ['GO', 'Gombe', 'Gombe', 'Kumo'],
            ['IM', 'Imo', 'Owerri', 'Orlu'],
            ['JI', 'Jigawa', 'Dutse', 'Hadejia'],
            ['KD', 'Kaduna', 'Kaduna', 'Zaria'],
            ['KN', 'Kano', 'Kano', 'Wudil'],
            ['KT', 'Katsina', 'Katsina', 'Funtua'],
            ['KE', 'Kebbi', 'Birnin Kebbi', 'Argungu'],
            ['KO', 'Kogi', 'Lokoja', 'Okene'],
            ['KW', 'Kwara', 'Ilorin', 'Offa'],
            ['LA', 'Lagos', 'Ikeja', 'Surulere'],
            ['NA', 'Nasarawa', 'Lafia', 'Keffi'],
            ['NI', 'Niger', 'Minna', 'Bida'],
            ['OG', 'Ogun', 'Abeokuta', 'Ijebu-Ode'],
            ['ON', 'Ondo', 'Akure', 'Ondo'],
            ['OS', 'Osun', 'Osogbo', 'Ile-Ife'],
            ['OY', 'Oyo', 'Ibadan', 'Ogbomoso'],
            ['PL', 'Plateau', 'Jos', 'Pankshin'],
            ['RI', 'Rivers', 'Port Harcourt', 'Bonny'],
            ['SO', 'Sokoto', 'Sokoto', 'Tambuwal'],
            ['TA', 'Taraba', 'Jalingo', 'Wukari'],
            ['YO', 'Yobe', 'Damaturu', 'Potiskum'],
            ['ZA', 'Zamfara', 'Gusau', 'Kaura Namoda'],
        ];

        foreach ($states as $index => [$code, $name, $divisionOne, $divisionTwo]) {
            State::updateOrCreate(['code' => $code], ['name' => $name, 'sort_order' => $index + 1]);

            $commandId = 'cmd_'.strtolower($code);
            Command::updateOrCreate(['id' => $commandId], [
                'state_code' => $code,
                'name' => $code === 'FC' ? 'FCT Police Command' : $name.' State Police Command',
                'type' => 'state_command',
                'sort_order' => $index + 1,
            ]);

            foreach ([
                ['hq', 'Command Headquarters', 'headquarters'],
                ['sid', 'State Intelligence Department', 'department'],
                ['cid', 'State Criminal Investigation Department', 'department'],
                ['d1', $divisionOne.' Division', 'division'],
                ['d2', $divisionTwo.' Division', 'division'],
            ] as $unitIndex => [$suffix, $unitName, $type]) {
                Unit::updateOrCreate(['id' => $commandId.'_'.$suffix], [
                    'command_id' => $commandId,
                    'name' => $unitName,
                    'type' => $type,
                    'sort_order' => $unitIndex + 1,
                ]);
            }
        }

        $formations = [
            ['fhq', 'Force Headquarters', 'force_headquarters', ['Force Intelligence Department', 'Force Criminal Investigation Department', 'Department of Operations', 'Department of Information and Communication Technology']],
            ['pmf', 'Police Mobile Force', 'formation', ['PMF Headquarters', 'Squadron Operations Desk']],
            ['spu', 'Special Protection Unit', 'formation', ['SPU Headquarters']],
            ['rail', 'Railway Police Command', 'formation', ['Railway Command Headquarters', 'Idu Station Unit']],
            ['marine', 'Marine Police Command', 'formation', ['Marine Headquarters']],
        ];

        foreach ($formations as $formationIndex => [$suffix, $name, $type, $units]) {
            $commandId = 'cmd_'.$suffix;
            Command::updateOrCreate(['id' => $commandId], [
                'state_code' => 'FC',
                'name' => $name,
                'type' => $type,
                'sort_order' => 100 + $formationIndex,
            ]);

            foreach ($units as $unitIndex => $unitName) {
                Unit::updateOrCreate(['id' => $commandId.'_u'.($unitIndex + 1)], [
                    'command_id' => $commandId,
                    'name' => $unitName,
                    'type' => $unitIndex === 0 ? 'headquarters' : 'unit',
                    'sort_order' => $unitIndex + 1,
                ]);
            }
        }

        $airports = [
            ['LOS', 'Murtala Muhammed International Airport', 'Lagos', 'LA'],
            ['PHC', 'Port Harcourt International Airport', 'Port Harcourt', 'RI'],
            ['KAN', 'Mallam Aminu Kano International Airport', 'Kano', 'KN'],
            ['ENU', 'Akanu Ibiam International Airport', 'Enugu', 'EN'],
            ['QOW', 'Sam Mbakwe Airport', 'Owerri', 'IM'],
            ['CBQ', 'Margaret Ekpo International Airport', 'Calabar', 'CR'],
            ['BNI', 'Benin Airport', 'Benin City', 'ED'],
            ['KAD', 'Kaduna International Airport', 'Kaduna', 'KD'],
            ['ILR', 'Ilorin International Airport', 'Ilorin', 'KW'],
            ['YOL', 'Yola Airport', 'Yola', 'AD'],
            ['MIU', 'Maiduguri International Airport', 'Maiduguri', 'BO'],
            ['SKO', 'Sadiq Abubakar III International Airport', 'Sokoto', 'SO'],
            ['JOS', 'Yakubu Gowon Airport', 'Jos', 'PL'],
            ['QUO', 'Victor Attah International Airport', 'Uyo', 'AK'],
            ['ABB', 'Asaba International Airport', 'Asaba', 'DE'],
            ['IBA', 'Ibadan Airport', 'Ibadan', 'OY'],
            ['AKR', 'Akure Airport', 'Akure', 'ON'],
            ['GMO', 'Gombe Lawanti International Airport', 'Gombe', 'GO'],
            ['QRW', 'Warri Airport', 'Warri', 'DE'],
        ];

        foreach ($airports as $index => [$code, $name, $city, $stateCode]) {
            Airport::updateOrCreate(['code' => $code], [
                'name' => $name,
                'city' => $city,
                'state_code' => $stateCode,
                'sort_order' => $index + 1,
            ]);
        }

        $banks = [
            ['044', 'Access Bank'],
            ['023', 'Citibank Nigeria'],
            ['050', 'Ecobank Nigeria'],
            ['070', 'Fidelity Bank'],
            ['011', 'First Bank of Nigeria'],
            ['214', 'First City Monument Bank (FCMB)'],
            ['058', 'Guaranty Trust Bank (GTBank)'],
            ['301', 'Jaiz Bank'],
            ['082', 'Keystone Bank'],
            ['50515', 'Moniepoint MFB'],
            ['999992', 'OPay'],
            ['526', 'Parallex Bank'],
            ['076', 'Polaris Bank'],
            ['101', 'Providus Bank'],
            ['221', 'Stanbic IBTC Bank'],
            ['068', 'Standard Chartered Bank'],
            ['232', 'Sterling Bank'],
            ['100', 'SunTrust Bank'],
            ['032', 'Union Bank of Nigeria'],
            ['033', 'United Bank for Africa (UBA)'],
            ['215', 'Unity Bank'],
            ['035', 'Wema Bank'],
            ['057', 'Zenith Bank'],
        ];

        foreach ($banks as $index => [$code, $name]) {
            Bank::updateOrCreate(['code' => $code], ['name' => $name, 'sort_order' => $index + 1]);
        }
    }
}
