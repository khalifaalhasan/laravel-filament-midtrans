<?php

namespace Tests\Feature;

use App\Enums\Facility;
use App\Filament\Resources\Events\Pages\EditEvent;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EventDetailDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_rich_text_is_sanitized_on_render(): void
    {
        $event = Event::factory()->make(['terms' => '<p>Maks <strong>5 tiket</strong></p><script>alert(1)</script><img src=x onerror="alert(2)">']);

        $html = $event->richHtml('terms');

        $this->assertStringContainsString('<strong>5 tiket</strong>', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('onerror', $html);
    }

    public function test_plain_text_description_keeps_line_breaks_and_is_escaped(): void
    {
        $event = Event::factory()->make(['description' => "Baris 1\nRock & Roll"]);

        $this->assertSame("Baris 1<br />\nRock &amp; Roll", $event->richHtml('description'));
        $this->assertNull(Event::factory()->make(['terms' => null])->richHtml('terms'));
    }

    public function test_facilities_cast_to_enums(): void
    {
        $event = Event::factory()->create(['facilities' => [Facility::Toilet, Facility::AreaParkir]]);

        $this->assertEquals([Facility::Toilet, Facility::AreaParkir], $event->refresh()->facilities->all());
    }

    public function test_admin_can_save_detail_fields(): void
    {
        $event = Event::factory()->create();
        $this->actingAs(User::factory()->withRole('ceo')->create());

        Livewire::test(EditEvent::class, ['record' => $event->getRouteKey()])
            ->fillForm([
                'facilities' => ['toilet', 'medis'],
                'instagram_url' => 'https://instagram.com/demo',
                'tiktok_url' => 'bukan-url',
            ])
            ->call('save')
            ->assertHasFormErrors(['tiktok_url']);

        Livewire::test(EditEvent::class, ['record' => $event->getRouteKey()])
            ->fillForm(['facilities' => ['toilet', 'medis'], 'instagram_url' => 'https://instagram.com/demo', 'tiktok_url' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('https://instagram.com/demo', $event->refresh()->instagram_url);
        $this->assertCount(2, $event->facilities);
    }
}
