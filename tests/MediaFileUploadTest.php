<?php

namespace Slimani\MediaManager\Tests;

use App\Models\User;
use Livewire\Livewire;
use Slimani\MediaManager\Tests\Components\TestMediaFileUploadForm;

class MediaFileUploadTest extends TestCase
{
    public function test_it_can_render_media_file_upload_field()
    {
        $user = User::factory()->create();
        Livewire::actingAs($user)
            ->test(TestMediaFileUploadForm::class)
            ->assertOk();
    }

    public function test_it_has_browse_action()
    {
        $user = User::factory()->create();
        Livewire::actingAs($user)
            ->test(TestMediaFileUploadForm::class)
            ->assertSee('Browse Media');
    }
}
