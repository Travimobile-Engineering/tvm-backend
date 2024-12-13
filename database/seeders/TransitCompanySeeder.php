<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TransitCompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('transit_companies')->insert([
            ['user_id' => '1', 'name' => 'Chisco Transport', 'short_name' => 'Chisco', 'reg_no' => 'NTR-001', 'url' => 'https://www.chisco.com', 'email' => 'info@chisco.com', 'state' => 'Lagos', 'lga' => 'Ikeja', 'phone' => '+2348023456789', 'address' => '22, Awolowo Road, Ikeja, Lagos', 'about_details' => 'Chisco Transport is one of Nigeria\'s leading transport companies, specializing in long-distance travel and logistics services.', 'ver_code' => '', 'ver_code_expires_at' => '2024-12-06 16:00:00'],
            ['user_id' => '1', 'name' => 'God Is Good Motors', 'short_name' => 'GIGM', 'reg_no' => 'NTR-002', 'url' => 'https://www.gigm.com', 'email' => 'support@gigm.com', 'state' => 'Abuja', 'lga' => 'Abuja Municipal', 'phone' => '+2347031234567', 'address' => 'Plot 22, Central Business District, Abuja', 'about_details' => 'GIGM provides comfortable and affordable inter-state travel with modern buses and excellent customer service.', 'ver_code' => '', 'ver_code_expires_at' => '2024-12-06 16:00:00'],
            ['user_id' => '1', 'name' => 'ABC Transport', 'short_name' => 'ABC', 'reg_no' => 'NTR-003', 'url' => 'https://www.abctransport.com', 'email' => 'info@abctransport.com', 'state' => 'Ogun', 'lga' => 'Abeokuta', 'phone' => '+2347039876543', 'address' => 'ABC Transport Headquarters, Abeokuta, Ogun State', 'about_details' => 'ABC Transport is known for its punctuality, safe travel, and regular service routes across Nigeria.', 'ver_code' => '', 'ver_code_expires_at' => '2024-12-06 16:00:00'],
            ['user_id' => '1', 'name' => 'Young Shall Grow Motors', 'short_name' => 'YSG', 'reg_no' => 'NTR-004', 'url' => 'https://www.ysg.com', 'email' => 'contact@ysg.com', 'state' => 'Enugu', 'lga' => 'Enugu North', 'phone' => '+2348037654321', 'address' => 'No. 4, Ogui Road, Enugu', 'about_details' => 'A leading transport company offering state-of-the-art buses for intercity travel across Nigeria.', 'ver_code' => '', 'ver_code_expires_at' => '2024-12-06 16:00:00'],
            ['user_id' => '1', 'name' => 'Peace Mass Transit', 'short_name' => 'PMT', 'reg_no' => 'NTR-005', 'url' => 'https://www.peacemass.com', 'email' => 'info@peacemass.com', 'state' => 'Imo', 'lga' => 'Owerri', 'phone' => '+2348052341234', 'address' => 'Plot 8, Okigwe Road, Owerri, Imo State', 'about_details' => 'Peace Mass Transit is committed to providing affordable and safe travel services across Nigeria.', 'ver_code' => '', 'ver_code_expires_at' => '2024-12-06 16:00:00'],
            ['user_id' => '1', 'name' => 'Steady Fast Transport', 'short_name' => 'SFT', 'reg_no' => 'NTR-006', 'url' => 'https://www.steadyfast.com', 'email' => 'contact@steadyfast.com', 'state' => 'Kano', 'lga' => 'Kano Municipal', 'phone' => '+2347026788901', 'address' => '35, Ahmadu Bello Way, Kano', 'about_details' => 'Steady Fast Transport provides reliable transport services with a focus on customer comfort and safety.', 'ver_code' => '', 'ver_code_expires_at' => '2024-12-06 16:00:00'],
            ['user_id' => '1', 'name' => 'Eagle Transport Services', 'short_name' => 'ETS', 'reg_no' => 'NTR-007', 'url' => 'https://www.eagletransports.com', 'email' => 'support@eagletransports.com', 'state' => 'Lagos', 'lga' => 'Lekki', 'phone' => '+2347023456781', 'address' => 'Eagle Transport Plaza, Lekki, Lagos', 'about_details' => 'Eagle Transport Services offers secure and convenient bus travel with frequent departures across major Nigerian cities.', 'ver_code' => '', 'ver_code_expires_at' => '2024-12-06 16:00:00'],
            ['user_id' => '1', 'name' => 'The Young Star Transport', 'short_name' => 'TYST', 'reg_no' => 'NTR-008', 'url' => 'https://www.tyst.com', 'email' => 'info@tyst.com', 'state' => 'Rivers', 'lga' => 'Port Harcourt', 'phone' => '+2347039871234', 'address' => '23, Trans-Amadi Road, Port Harcourt', 'about_details' => 'The Young Star Transport offers affordable travel options with punctual and comfortable services.', 'ver_code' => '', 'ver_code_expires_at' => '2024-12-06 16:00:00'],
            ['user_id' => '1', 'name' => 'Rider\'s Transport Limited', 'short_name' => 'RTL', 'reg_no' => 'NTR-009', 'url' => 'https://www.riders.com', 'email' => 'support@riders.com', 'state' => 'Delta', 'lga' => 'Warri', 'phone' => '+2348032109876', 'address' => 'Rider\'s Plaza, Warri, Delta State', 'about_details' => 'Rider\'s Transport Limited is known for its efficient travel services and fleet of well-maintained vehicles.', 'ver_code' => '', 'ver_code_expires_at' => '2024-12-06 16:00:00'],
            ['user_id' => '1', 'name' => 'Transcorp Transport', 'short_name' => 'TCT', 'reg_no' => 'NTR-010', 'url' => 'https://www.transcorp.com', 'email' => 'info@transcorp.com', 'state' => 'Abuja', 'lga' => 'Abuja Municipal', 'phone' => '+2347084325678', 'address' => 'Transcorp Transport Headquarters, Abuja', 'about_details' => 'Transcorp Transport provides world-class intercity transport services to customers with a focus on quality and reliability.', 'ver_code' => '', 'ver_codeexpiresd_at' => '2024-12-06 16:00:00']
        ]);
    }
}
