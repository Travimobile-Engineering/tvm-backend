<?php

namespace Tests\Unit\Npis;

use App\Support\Npis\Destination;
use App\Support\Npis\TagNumber;
use Tests\TestCase;

class TagNumberTest extends TestCase
{
    public function test_delegate_and_vendor_tags_contain_a_valid_check_character(): void
    {
        for ($i = 0; $i < 25; $i++) {
            $delegate = TagNumber::generate();
            $vendor = TagNumber::generate(true);

            $this->assertMatchesRegularExpression('/^NPIS26-[23456789ABCDEFGHJKMNPQRSTVWXYZ]{5}$/', $delegate);
            $this->assertMatchesRegularExpression('/^NPIS26-V-[23456789ABCDEFGHJKMNPQRSTVWXYZ]{5}$/', $vendor);
            $this->assertSame($delegate, TagNumber::validate($delegate));
            $this->assertSame($vendor, TagNumber::validate($vendor));
            $this->assertSame($delegate, TagNumber::validate(strtolower(str_replace('-', ' ', $delegate))));
        }
    }

    public function test_invalid_check_character_is_rejected(): void
    {
        $tag = TagNumber::generate();
        $charset = TagNumber::charset();
        $last = $tag[strlen($tag) - 1];
        $replacement = $last === $charset[0] ? $charset[1] : $charset[0];
        $tampered = substr($tag, 0, -1).$replacement;

        $this->assertNull(TagNumber::validate($tampered));
        $this->assertNull(TagNumber::validate('NPIS26-00000'));
        $this->assertNull(TagNumber::validate('not-a-tag'));
    }

    public function test_destination_normalisation_and_masking(): void
    {
        $this->assertSame('name@example.com', Destination::normalize('email', '  NAME@Example.COM '));
        $this->assertSame('+2348031234567', Destination::normalize('sms', ' +2348031234567 '));
        $this->assertSame('+234 803 *** 4567', Destination::mask('sms', '+2348031234567'));
        $this->assertSame('na**@example.com', Destination::mask('email', 'name@example.com'));
        $this->assertSame(Destination::hash('Name@Example.com'), Destination::hash('name@example.com'));
    }
}
