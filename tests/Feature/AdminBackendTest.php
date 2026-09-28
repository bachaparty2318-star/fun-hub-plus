<?php

namespace Tests\Feature;

use App\Models\Bookmark;
use App\Models\Category;
use App\Models\Content;
use App\Models\FanSubmission;
use App\Models\Feedback;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Tests\Support\AdminDatabaseTestCase;

class AdminBackendTest extends AdminDatabaseTestCase
{
    private string $base = '/admin/api';

    private function category(): int
    {
        return Category::firstOrFail()->getKey();
    }

    private function asAdmin(): static
    {
        return $this->actingAs($this->admin);
    }

    private function create(string $resource, array $data): array
    {
        return $this->asAdmin()->postJson($this->base.'/'.$resource, $data)->assertCreated()->json('data');
    }

    public function test_guests_regular_users_and_disabled_admins_are_denied(): void
    {
        $this->getJson($this->base.'/dashboard')->assertUnauthorized();
        $this->actingAs(User::factory()->create())->getJson($this->base.'/dashboard')->assertForbidden();
        $this->admin->is_active = false;
        $this->admin->save();
        $this->asAdmin()->getJson($this->base.'/dashboard')->assertForbidden();
    }

    public function test_login_me_logout_and_hidden_credentials(): void
    {
        $this->postJson($this->base.'/auth/login', ['email' => $this->admin->email, 'password' => 'AdminTest12345!'])
            ->assertOk()->assertJsonPath('data.role', 'admin')->assertJsonMissingPath('data.password_hash')->assertJsonStructure(['csrf_token']);
        $this->getJson($this->base.'/auth/me')->assertOk()->assertJsonStructure(['data' => ['profile']]);
        $this->postJson($this->base.'/auth/logout')->assertOk();
        $this->getJson($this->base.'/auth/me')->assertUnauthorized();
    }

    public function test_non_admin_credentials_and_wrong_password_cannot_login(): void
    {
        $user = User::factory()->create();
        $this->postJson($this->base.'/auth/login', ['email' => $user->email, 'password' => 'password'])->assertUnprocessable();
        $this->postJson($this->base.'/auth/login', ['email' => $this->admin->email, 'password' => 'wrong'])->assertUnprocessable();
        $this->postJson($this->base.'/auth/login', ['email' => ['malformed'], 'password' => 'wrong'])->assertUnprocessable();
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson($this->base.'/auth/login', ['email' => 'missing@example.com', 'password' => 'wrong'])->assertUnprocessable();
        }
        $this->postJson($this->base.'/auth/login', ['email' => 'missing@example.com', 'password' => 'wrong'])->assertStatus(429);
    }

    public function test_real_csrf_check_rejects_mutations_without_token(): void
    {
        $this->app['env'] = 'local';
        try {
            $this->asAdmin()->postJson($this->base.'/categories', ['name' => 'CSRF', 'slug' => 'csrf'])->assertStatus(419);
            $response = $this->getJson($this->base.'/auth/csrf')->assertOk();
            $this->withHeader('X-CSRF-TOKEN', $response->json('csrf_token'))
                ->postJson($this->base.'/categories', ['name' => 'CSRF', 'slug' => 'csrf'])->assertCreated();
        } finally {
            $this->app['env'] = 'testing';
        }
    }

    public function test_category_crud_search_unique_validation_and_in_use_protection(): void
    {
        $data = $this->create('categories', ['name' => 'TestLibraryArea', 'slug' => 'books']);
        $id = $data['category_id'];
        $this->getJson($this->base.'/categories?q=TestLibraryArea&per_page=1&sort=name&direction=asc')->assertOk()->assertJsonPath('total', 1);
        $this->patchJson($this->base.'/categories/'.$id, ['name' => 'Novels'])->assertOk()->assertJsonPath('data.name', 'Novels');
        $this->postJson($this->base.'/categories', ['name' => 'Novels', 'slug' => 'novels'])->assertUnprocessable();
        $this->deleteJson($this->base.'/categories/'.$id)->assertNoContent();
        $this->getJson($this->base.'/categories/'.$id)->assertNotFound();
        Content::create(['category_id' => $this->category(), 'title' => 'Protected', 'type' => 'image']);
        $this->deleteJson($this->base.'/categories/'.$this->category())->assertConflict();
    }

    public function test_content_tagging_filtering_attribution_and_unsafe_urls(): void
    {
        $tag = $this->create('tags', ['tag_name' => 'Adventure']);
        $content = $this->create('content', [
            'category_id' => $this->category(), 'title' => 'Future game', 'type' => 'release',
            'release_date' => '2027-05-01', 'fandom_name' => 'Example', 'tag_ids' => [$tag['tag_id']],
            'created_by' => 999999,
        ]);
        $this->assertEquals($this->admin->getKey(), $content['created_by']);
        $this->assertCount(1, $content['tags']);
        $this->getJson($this->base.'/content?release_year=2027&type=release')->assertOk()->assertJsonPath('total', 1);
        $this->patchJson($this->base.'/content/'.$content['content_id'], ['tag_ids' => []])->assertOk()->assertJsonCount(0, 'data.tags');
        $this->patchJson($this->base.'/content/'.$content['content_id'], ['thumbnail_url' => 'javascript:alert(1)'])->assertUnprocessable();
        $this->getJson($this->base.'/content?sort=password_hash')->assertUnprocessable();
        $this->getJson($this->base.'/content?per_page=10000')->assertUnprocessable();
    }

    public function test_articles_sanitize_html_and_manage_images_and_timelines(): void
    {
        $article = $this->create('articles', [
            'category_id' => $this->category(), 'title' => 'Story',
            'body_html' => '<h2>Safe</h2><script>alert(1)</script><img src="https://example.com/a.jpg" onerror="alert(1)">',
            'is_featured' => true, 'published_at' => '2026-09-24 10:00:00',
        ]);
        $this->assertStringNotContainsString('script', $article['body_html']);
        $this->assertStringNotContainsString('onerror', $article['body_html']);
        $image = $this->create('article-images', ['article_id' => $article['article_id'], 'image_url' => '/storage/a.jpg', 'display_order' => 2]);
        $event = $this->create('article-timeline-events', ['article_id' => $article['article_id'], 'event_label' => 'Premiere', 'event_date' => '2027-01-01']);
        $this->getJson($this->base.'/articles/'.$article['article_id'])->assertOk()->assertJsonCount(1, 'data.images')->assertJsonCount(1, 'data.timeline');
        $this->patchJson($this->base.'/article-images/'.$image['image_id'], ['caption' => 'Caption'])->assertOk();
        $this->deleteJson($this->base.'/article-timeline-events/'.$event['timeline_event_id'])->assertNoContent();
        $this->deleteJson($this->base.'/articles/'.$article['article_id'])->assertNoContent();
        $this->assertDatabaseMissing('article_images', ['image_id' => $image['image_id']]);
    }

    public function test_characters_and_merchandise_support_fandom_filters_and_gallery(): void
    {
        $character = $this->create('characters', ['category_id' => $this->category(), 'name' => 'Hero', 'fandom_name' => 'World', 'genre' => 'Fantasy']);
        $merch = $this->create('merchandise', ['category_id' => $this->category(), 'name' => 'Figure', 'fandom_name' => 'World', 'tag' => 'Pre-Order']);
        $image = $this->create('merchandise-images', ['item_id' => $merch['item_id'], 'image_url' => 'https://example.com/figure.png']);
        $this->getJson($this->base.'/characters?fandom_name=World&genre=Fantasy')->assertOk()->assertJsonPath('total', 1);
        $this->getJson($this->base.'/merchandise/'.$merch['item_id'])->assertOk()->assertJsonCount(1, 'data.images');
        $this->deleteJson($this->base.'/merchandise/'.$merch['item_id'])->assertNoContent();
        $this->assertDatabaseMissing('merchandise_images', ['image_id' => $image['image_id']]);
        $this->deleteJson($this->base.'/characters/'.$character['character_id'])->assertNoContent();
    }

    public function test_deleting_content_cleans_media_bookmarks_and_ratings(): void
    {
        $content = $this->create('content', ['category_id' => $this->category(), 'title' => 'Clip', 'type' => 'video']);
        $media = $this->create('media', ['content_id' => $content['content_id'], 'media_type' => 'video', 'media_url' => 'https://example.com/video.mp4']);
        Bookmark::create(['user_id' => $this->admin->getKey(), 'item_type' => 'content', 'item_id' => $content['content_id']]);
        Bookmark::create(['user_id' => $this->admin->getKey(), 'item_type' => 'media', 'item_id' => $media['media_id']]);
        DB::table('media_ratings')->insert(['media_id' => $media['media_id'], 'user_id' => $this->admin->getKey(), 'rating_value' => 4]);
        $this->getJson($this->base.'/media/'.$media['media_id'].'/ratings')->assertOk()->assertJsonPath('data.total_ratings', 1);
        $this->deleteJson($this->base.'/content/'.$content['content_id'])->assertNoContent();
        $this->assertDatabaseCount('bookmarks', 0);
        $this->assertDatabaseCount('media_ratings', 0);
        $this->assertDatabaseMissing('media', ['media_id' => $media['media_id']]);
    }

    public function test_event_date_and_coordinate_validation_including_partial_updates(): void
    {
        $event = $this->create('events', ['title' => 'Convention', 'city' => 'Karachi', 'event_type' => 'convention', 'event_date' => '2027-01-10', 'end_date' => '2027-01-11', 'latitude' => 24.8607, 'longitude' => 67.0011]);
        $id = $event['event_id'];
        $this->patchJson($this->base.'/events/'.$id, ['event_date' => '2027-01-12'])->assertUnprocessable();
        $this->patchJson($this->base.'/events/'.$id, ['latitude' => 100])->assertUnprocessable();
        $this->patchJson($this->base.'/events/'.$id, ['latitude' => null])->assertUnprocessable();
        $this->patchJson($this->base.'/events/'.$id, ['latitude' => null, 'longitude' => null])->assertOk();
        $this->getJson($this->base.'/events?city=Karachi')->assertOk()->assertJsonPath('total', 1);
    }

    public function test_approval_publishes_one_safe_article_and_repeated_review_is_blocked(): void
    {
        $submission = FanSubmission::create(['user_id' => $this->admin->getKey(), 'category_id' => $this->category(), 'title' => 'Fan story', 'body_html' => '<p>Story</p><script>alert(1)</script>']);
        $this->asAdmin()->postJson($this->base.'/submissions/'.$submission->getKey().'/review', ['decision' => 'approved'])
            ->assertOk()->assertJsonPath('data.status', 'approved')->assertJsonPath('data.published_article.body_html', '<p>Story</p>');
        $this->assertNotNull($submission->fresh()->published_article_id);
        $this->postJson($this->base.'/submissions/'.$submission->getKey().'/review', ['decision' => 'approved'])->assertConflict();
        $this->assertDatabaseCount('articles', 1);
    }

    public function test_rejection_does_not_publish_and_unsafe_submission_requires_replacement(): void
    {
        $submission = FanSubmission::create(['user_id' => $this->admin->getKey(), 'category_id' => $this->category(), 'title' => 'Bad', 'body_html' => '<script>alert(1)</script>']);
        $url = $this->base.'/submissions/'.$submission->getKey().'/review';
        $this->asAdmin()->postJson($url, ['decision' => 'approved'])->assertUnprocessable();
        $this->assertEquals('pending', $submission->fresh()->status);
        $this->postJson($url, ['decision' => 'rejected', 'review_notes' => 'Needs content'])->assertOk()->assertJsonPath('data.status', 'rejected');
        $this->assertDatabaseCount('articles', 0);
    }

    public function test_feedback_resolution_and_reopening(): void
    {
        $feedback = Feedback::create(['type' => 'bug', 'message' => 'Example bug']);
        $url = $this->base.'/feedback/'.$feedback->getKey();
        $this->asAdmin()->patchJson($url, ['status' => 'resolved'])->assertOk();
        $this->assertNotNull($feedback->fresh()->resolved_at);
        $this->patchJson($url, ['status' => 'open'])->assertOk()->assertJsonPath('data.resolved_at', null);
        $this->patchJson($url, ['status' => 'invalid'])->assertUnprocessable();
        $this->deleteJson($url)->assertNoContent();
    }

    public function test_user_creation_profile_edit_deactivation_and_deletion(): void
    {
        $user = $this->create('users', ['name' => 'Member', 'email' => 'member@example.com', 'password' => 'MemberTest123!', 'role' => 'registered']);
        $id = $user['user_id'];
        $this->assertArrayNotHasKey('password_hash', $user);
        $this->patchJson($this->base.'/users/'.$id.'/profile', ['favorite_fandoms' => ['World'], 'category_ids' => [$this->category()], 'font_size' => 'large'])
            ->assertOk()->assertJsonPath('data.profile.favorite_fandoms.0', 'World')->assertJsonCount(1, 'data.favorite_categories');
        DB::table('sessions')->insert(['id' => 'member-session', 'user_id' => $id, 'payload' => '', 'last_activity' => time()]);
        $this->patchJson($this->base.'/users/'.$id, ['is_active' => false])->assertOk()->assertJsonPath('data.is_active', false);
        $this->assertDatabaseMissing('sessions', ['id' => 'member-session']);
        $this->deleteJson($this->base.'/users/'.$id)->assertNoContent();
        $this->assertDatabaseMissing('user_profiles', ['user_id' => $id]);
    }

    public function test_admin_cannot_delete_disable_or_demote_self(): void
    {
        $url = $this->base.'/users/'.$this->admin->getKey();
        $this->asAdmin()->deleteJson($url)->assertConflict();
        $this->patchJson($url, ['is_active' => false])->assertConflict();
        $this->patchJson($url, ['role' => 'registered'])->assertConflict();
    }

    public function test_demoting_another_admin_revokes_their_sessions(): void
    {
        $other = User::factory()->create(['role' => 'admin']);
        DB::table('sessions')->insert(['id' => 'other-session', 'user_id' => $other->getKey(), 'payload' => '', 'last_activity' => time()]);
        $this->asAdmin()->patchJson($this->base.'/users/'.$other->getKey(), ['role' => 'registered'])->assertOk();
        $this->assertDatabaseMissing('sessions', ['id' => 'other-session']);
    }

    public function test_password_change_requires_current_password_and_revokes_sessions(): void
    {
        $data = ['current_password' => 'wrong', 'password' => 'ChangedTest123!', 'password_confirmation' => 'ChangedTest123!'];
        $this->asAdmin()->putJson($this->base.'/auth/password', $data)->assertUnprocessable();
        DB::table('sessions')->insert(['id' => 'old-session', 'user_id' => $this->admin->getKey(), 'payload' => '', 'last_activity' => time()]);
        $data['current_password'] = 'AdminTest12345!';
        $this->putJson($this->base.'/auth/password', $data)->assertOk();
        $this->assertTrue(Hash::check($data['password'], $this->admin->fresh()->password_hash));
        $this->assertDatabaseMissing('sessions', ['id' => 'old-session']);
    }

    public function test_password_recovery_is_admin_only_and_token_is_single_use(): void
    {
        Notification::fake();
        $this->postJson($this->base.'/auth/forgot-password', ['email' => $this->admin->email])->assertOk();
        Notification::assertSentTo($this->admin, ResetPassword::class);
        $member = User::factory()->create();
        $this->postJson($this->base.'/auth/forgot-password', ['email' => $member->email])->assertOk();
        Notification::assertNotSentTo($member, ResetPassword::class);
        $token = Password::createToken($this->admin);
        $data = ['email' => $this->admin->email, 'token' => $token, 'password' => 'ResetTest12345!', 'password_confirmation' => 'ResetTest12345!'];
        $this->postJson($this->base.'/auth/reset-password', $data)->assertOk();
        $this->assertTrue(Hash::check($data['password'], $this->admin->fresh()->password_hash));
        $this->postJson($this->base.'/auth/reset-password', $data)->assertUnprocessable();
    }

    public function test_upload_rejects_scripts_and_stores_safe_images(): void
    {
        Storage::fake('public');
        $bad = UploadedFile::fake()->createWithContent('evil.php', '<?php echo 1;');
        $this->asAdmin()->postJson($this->base.'/uploads', ['kind' => 'image', 'file' => $bad])->assertUnprocessable();
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');
        $good = UploadedFile::fake()->createWithContent('photo.png', $png);
        $response = $this->postJson($this->base.'/uploads', ['kind' => 'image', 'file' => $good])->assertCreated();
        Storage::disk('public')->assertExists($response->json('data.path'));
    }

    public function test_dashboard_and_read_only_reports_handle_empty_content(): void
    {
        $this->asAdmin()->getJson($this->base.'/dashboard')->assertOk()->assertJsonPath('data.totals.categories', 8)->assertJsonMissingPath('data.chatbot_enabled');
        $this->getJson($this->base.'/activity')->assertOk();
        $this->getJson($this->base.'/ratings')->assertOk();
        $this->getJson($this->base.'/feedback')->assertOk();
        $this->getJson($this->base.'/submissions')->assertOk();
        $this->getJson($this->base.'/users')->assertOk();
        $this->getJson($this->base.'/unknown-resource')->assertNotFound();
        $this->getJson($this->base.'/users/999999')->assertNotFound();
    }
}
