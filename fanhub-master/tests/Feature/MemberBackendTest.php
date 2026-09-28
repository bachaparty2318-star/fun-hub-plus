<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Bookmark;
use App\Models\Category;
use App\Models\CharacterProfile;
use App\Models\Content;
use App\Models\Event;
use App\Models\Media;
use App\Models\MerchandiseItem;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\Support\AdminDatabaseTestCase;

class MemberBackendTest extends AdminDatabaseTestCase
{
    private string $base = '/user/api';

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->member = User::factory()->create(['password_hash' => 'MemberTest123!']);
        $this->member->profile()->create([]);
    }

    private function category(): int
    {
        return Category::firstOrFail()->getKey();
    }

    private function member(): static
    {
        return $this->actingAs($this->member);
    }

    private function content(array $extra = []): Content
    {
        return Content::create(array_merge(['category_id' => $this->category(), 'title' => 'Example video', 'type' => 'video'], $extra));
    }

    private function article(array $extra = []): Article
    {
        return Article::create(array_merge(['category_id' => $this->category(), 'title' => 'Article story', 'body_html' => '<p>Story</p>', 'published_at' => now()->subMinute()], $extra));
    }

    private function media(): Media
    {
        return Media::create(['content_id' => $this->content()->getKey(), 'media_type' => 'video', 'media_url' => 'https://example.com/video.mp4']);
    }

    private function verification(User $user): string
    {
        return URL::temporarySignedRoute('member.verification.verify', now()->addHour(), ['id' => $user->getKey(), 'hash' => sha1($user->email)]);
    }

    public function test_registration_creates_only_a_registered_unverified_account_and_profile(): void
    {
        $response = $this->postJson($this->base.'/auth/register', [
            'name' => 'New Member', 'email' => 'new-member@example.com', 'password' => 'NewMember123!',
            'password_confirmation' => 'NewMember123!', 'role' => 'admin', 'is_active' => false, 'email_verified_at' => now(),
        ])->assertCreated()->assertJsonPath('data.role', 'registered')->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.email_verified_at', null)->assertJsonMissingPath('data.password_hash')->assertJsonStructure(['csrf_token', 'data' => ['profile']]);
        $user = User::findOrFail($response->json('data.user_id'));
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->getJson($this->base.'/dashboard')->assertForbidden();
        $this->getJson($this->base.'/auth/me')->assertOk();
        $this->getJson($this->verification($user))->assertOk();
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->getJson($this->base.'/dashboard')->assertOk();
        $this->getJson('/admin/api/dashboard')->assertForbidden();
    }

    public function test_registration_requires_unique_email_and_confirmed_strong_password(): void
    {
        $this->postJson($this->base.'/auth/register', [
            'name' => 'Bad', 'email' => $this->member->email, 'password' => 'short', 'password_confirmation' => 'different',
        ])->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_signed_verification_rejects_other_users_tampering_and_expiry(): void
    {
        $other = User::factory()->unverified()->create();
        $this->member()->getJson($this->verification($other))->assertForbidden();
        $this->member->email_verified_at = null;
        $this->member->save();
        $valid = $this->verification($this->member);
        $this->getJson($valid.'&injected=1')->assertForbidden();
        $expired = URL::temporarySignedRoute('member.verification.verify', now()->subMinute(), ['id' => $this->member->getKey(), 'hash' => sha1($this->member->email)]);
        $this->getJson($expired)->assertForbidden();
        $this->getJson($valid)->assertOk();
        $this->getJson($valid)->assertOk();
    }

    public function test_login_logout_and_inactive_accounts(): void
    {
        $this->postJson($this->base.'/auth/login', ['email' => $this->member->email, 'password' => 'MemberTest123!'])->assertOk()->assertJsonStructure(['csrf_token']);
        $this->getJson($this->base.'/auth/me')->assertOk();
        $this->postJson($this->base.'/auth/logout')->assertOk();
        $this->getJson($this->base.'/dashboard')->assertUnauthorized();
        $this->member->is_active = false;
        $this->member->save();
        $this->postJson($this->base.'/auth/login', ['email' => $this->member->email, 'password' => 'MemberTest123!'])->assertUnprocessable();
        $this->member()->getJson($this->base.'/profile')->assertForbidden();
    }

    public function test_login_throttle_and_role_separation(): void
    {
        $this->postJson($this->base.'/auth/login', ['email' => $this->admin->email, 'password' => 'AdminTest12345!'])->assertUnprocessable();
        for ($i = 0; $i < 5; $i++) {
            $this->postJson($this->base.'/auth/login', ['email' => $this->member->email, 'password' => 'bad'])->assertUnprocessable();
        }
        $this->postJson($this->base.'/auth/login', ['email' => $this->member->email, 'password' => 'bad'])->assertStatus(429);
    }

    public function test_registration_csrf_is_enforced_outside_test_bypass(): void
    {
        $this->app['env'] = 'local';
        try {
            $data = ['name' => 'CSRF Member', 'email' => 'csrf-member@example.com', 'password' => 'CsrfMember123!', 'password_confirmation' => 'CsrfMember123!'];
            $this->postJson($this->base.'/auth/register', $data)->assertStatus(419);
            $csrf = $this->getJson($this->base.'/auth/csrf')->assertOk()->json('csrf_token');
            $this->withHeader('X-CSRF-TOKEN', $csrf)->postJson($this->base.'/auth/register', $data)->assertCreated();
        } finally {
            $this->app['env'] = 'testing';
        }
    }

    public function test_password_reset_links_target_member_route_and_tokens_are_single_use(): void
    {
        $this->postJson($this->base.'/auth/forgot-password', ['email' => $this->member->email])->assertOk();
        Notification::assertSentTo($this->member, ResetPassword::class, function ($notification) {
            $this->assertStringContainsString('/user/api/auth/reset-password/', $notification->toMail($this->member)->actionUrl);

            return true;
        });
        $this->postJson($this->base.'/auth/forgot-password', ['email' => $this->admin->email])->assertOk();
        Notification::assertNotSentTo($this->admin, ResetPassword::class);
        $token = Password::createToken($this->member);
        $data = ['email' => $this->member->email, 'token' => $token, 'password' => 'NewPassword123!', 'password_confirmation' => 'NewPassword123!'];
        $this->postJson($this->base.'/auth/reset-password', $data)->assertOk();
        $this->assertTrue(Hash::check('NewPassword123!', $this->member->fresh()->password_hash));
        $this->postJson($this->base.'/auth/reset-password', $data)->assertUnprocessable();
    }

    public function test_email_change_rechecks_password_revokes_sessions_and_requires_reverification(): void
    {
        $data = ['email' => 'changed@example.com', 'current_password' => 'incorrect'];
        $this->member()->putJson($this->base.'/auth/email', $data)->assertUnprocessable();
        DB::table('sessions')->insert(['id' => 'old-member-session', 'user_id' => $this->member->getKey(), 'payload' => '', 'last_activity' => time()]);
        $oldLink = $this->verification($this->member);
        $data['current_password'] = 'MemberTest123!';
        $this->putJson($this->base.'/auth/email', $data)->assertOk()->assertJsonPath('data.email_verified_at', null);
        $this->assertDatabaseMissing('sessions', ['id' => 'old-member-session']);
        $this->getJson($oldLink)->assertForbidden();
        $this->getJson($this->base.'/dashboard')->assertForbidden();
        Notification::assertSentTo($this->member, VerifyEmail::class);
    }

    public function test_change_password_and_session_ownership(): void
    {
        DB::table('sessions')->insert([
            ['id' => 'mine', 'user_id' => $this->member->getKey(), 'payload' => 'secret', 'last_activity' => time()],
            ['id' => 'theirs', 'user_id' => $this->admin->getKey(), 'payload' => 'secret', 'last_activity' => time()],
        ]);
        $this->member()->getJson($this->base.'/auth/sessions')->assertOk()->assertJsonCount(1, 'data')->assertJsonMissingPath('data.0.payload');
        $this->deleteJson($this->base.'/auth/sessions/theirs')->assertNotFound();
        $this->deleteJson($this->base.'/auth/sessions/mine')->assertNoContent();
        $data = ['current_password' => 'MemberTest123!', 'password' => 'ChangedMember123!', 'password_confirmation' => 'ChangedMember123!'];
        $this->putJson($this->base.'/auth/password', $data)->assertOk();
        $this->assertTrue(Hash::check($data['password'], $this->member->fresh()->password_hash));
    }

    public function test_profile_preferences_and_favorites_cannot_escalate_privileges(): void
    {
        $this->member()->patchJson($this->base.'/profile', [
            'name' => 'Edited', 'role' => 'admin', 'email' => 'ignored@example.com', 'user_id' => $this->admin->getKey(),
            'dark_mode_enabled' => true, 'font_size' => 'large', 'favorite_fandoms' => ['World'],
            'category_ids' => [$this->category()], 'display_preferences' => ['default_sort' => 'popular', 'content_layout' => 'list'],
        ])->assertOk()->assertJsonPath('data.role', 'registered')->assertJsonPath('data.email', $this->member->email)
            ->assertJsonPath('data.profile.dark_mode_enabled', true)->assertJsonCount(1, 'data.favorite_categories');
        $this->patchJson($this->base.'/profile', ['category_ids' => []])->assertOk()->assertJsonCount(0, 'data.favorite_categories');
        $this->patchJson($this->base.'/profile', ['display_preferences' => ['unexpected' => 'value']])->assertUnprocessable();
        $this->assertEquals('Fan Hub Admin', $this->admin->fresh()->name);
    }

    public function test_avatar_and_submission_image_uploads_are_validated(): void
    {
        Storage::fake('public');
        $bad = UploadedFile::fake()->createWithContent('evil.svg', '<svg onload="alert(1)"/>');
        $this->member()->postJson($this->base.'/profile/avatar', ['file' => $bad])->assertUnprocessable();
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');
        $this->postJson($this->base.'/profile/avatar', ['file' => UploadedFile::fake()->createWithContent('avatar.png', $png)])->assertCreated();
        $this->assertStringContainsString('/avatars/'.$this->member->getKey().'/', $this->member->profile()->first()->avatar_url);
        $response = $this->postJson($this->base.'/submissions/image', ['file' => UploadedFile::fake()->createWithContent('cover.png', $png)])->assertCreated();
        Storage::disk('public')->assertExists($response->json('data.path'));
    }

    public function test_explorer_combines_all_types_and_hides_drafts_and_future_articles(): void
    {
        $this->content(['title' => 'Generic']);
        $this->article(['title' => 'Published']);
        $this->article(['title' => 'Secret draft', 'published_at' => null]);
        $this->article(['title' => 'Scheduled', 'published_at' => now()->addDay()]);
        CharacterProfile::create(['category_id' => $this->category(), 'name' => 'Hero']);
        MerchandiseItem::create(['category_id' => $this->category(), 'name' => 'Figure']);
        $this->media();
        $this->getJson($this->base.'/explore')->assertOk()->assertJsonPath('total', 5);
        $this->getJson($this->base.'/explore?content_type=article')->assertOk()->assertJsonPath('total', 1);
        $this->getJson($this->base.'/explore?q=Secret')->assertOk()->assertJsonPath('total', 0);
        $this->getJson($this->base.'/explore?sort=bad')->assertUnprocessable();
        $this->getJson($this->base.'/explore?per_page=101')->assertUnprocessable();
    }

    public function test_explorer_filters_tags_genre_fandom_year_popularity_and_sort(): void
    {
        $content = $this->content(['title' => 'Target', 'genre' => 'Fantasy', 'fandom_name' => 'World', 'release_date' => '2027-01-01', 'popularity_score' => 42]);
        $tag = Tag::create(['tag_name' => 'Epic']);
        $content->tags()->attach($tag);
        $this->content(['title' => 'Other', 'popularity_score' => 1]);
        $query = http_build_query(['category_id' => $this->category(), 'genre' => 'Fantasy', 'fandom_name' => 'World', 'release_year' => 2027, 'min_popularity' => 40, 'tag_id' => $tag->getKey()]);
        $this->getJson($this->base.'/explore?'.$query)->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.title', 'Target');
        $this->getJson($this->base.'/explore?sort=popular')->assertOk()->assertJsonPath('data.0.title', 'Target');
        $this->getJson($this->base.'/explore?sort=alphabetical')->assertOk()->assertJsonPath('data.0.title', 'Other');
        $media = Media::create(['content_id' => $content->getKey(), 'media_type' => 'trailer', 'media_url' => '/storage/trailer.mp4']);
        $this->getJson($this->base.'/explore?tag_id='.$tag->getKey())->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.item_type', 'media');
    }

    public function test_public_details_include_galleries_but_no_private_author_data_or_unsafe_html(): void
    {
        $article = $this->article(['author_id' => $this->member->getKey(), 'body_html' => '<p>Good</p><script>bad()</script>']);
        $article->images()->create(['image_url' => '/storage/image.jpg']);
        $article->timeline()->create(['event_label' => 'Premiere']);
        $this->getJson($this->base.'/catalog/article/'.$article->getKey())->assertOk()
            ->assertJsonPath('data.body_html', '<p>Good</p>')->assertJsonCount(1, 'data.images')->assertJsonCount(1, 'data.timeline')
            ->assertJsonMissingPath('data.author.email')->assertJsonMissingPath('data.author_id');
        $draft = $this->article(['published_at' => null]);
        $this->getJson($this->base.'/catalog/article/'.$draft->getKey())->assertNotFound();
        $this->getJson($this->base.'/share/article/'.$draft->getKey())->assertNotFound();
        $this->getJson($this->base.'/share/article/'.$article->getKey())->assertOk()->assertJsonStructure(['data' => ['title', 'url']]);
    }

    public function test_upcoming_releases_include_undated_preorders_but_exclude_past_releases(): void
    {
        $this->content(['release_date' => now()->addMonth()->toDateString(), 'type' => 'release']);
        $this->content(['release_date' => now()->subMonth()->toDateString(), 'type' => 'release']);
        MerchandiseItem::create(['category_id' => $this->category(), 'name' => 'Preorder', 'is_upcoming' => true]);
        $this->getJson($this->base.'/upcoming')->assertOk()->assertJsonPath('total', 2);
    }

    public function test_content_views_are_deduplicated_and_activity_is_personal(): void
    {
        $content = $this->content();
        $url = $this->base.'/catalog/content/'.$content->getKey();
        $this->member()->getJson($url)->assertOk();
        $this->getJson($url)->assertOk();
        $this->assertEquals(1, $content->fresh()->view_count);
        $this->getJson($this->base.'/activity')->assertOk()->assertJsonPath('total', 1);
        $this->actingAs($this->admin)->getJson($this->base.'/activity')->assertOk()->assertJsonPath('total', 0);
    }

    public function test_bookmarks_are_idempotent_owner_scoped_and_notes_do_not_leak(): void
    {
        $content = $this->content();
        $data = ['item_type' => 'content', 'item_id' => $content->getKey(), 'note' => 'Private note', 'user_id' => $this->admin->getKey()];
        $id = $this->member()->postJson($this->base.'/bookmarks', $data)->assertCreated()->json('data.bookmark_id');
        $this->postJson($this->base.'/bookmarks', $data)->assertOk();
        $this->assertDatabaseCount('bookmarks', 1);
        $this->patchJson($this->base.'/bookmarks/'.$id, ['note' => 'Edited'])->assertOk();
        $this->getJson($this->base.'/share/content/'.$content->getKey())->assertOk()->assertJsonMissingPath('data.note');
        $this->actingAs($this->admin)->getJson($this->base.'/bookmarks')->assertOk()->assertJsonPath('total', 0);
        $this->patchJson($this->base.'/bookmarks/'.$id, ['note' => 'Hijack'])->assertNotFound();
        $this->deleteJson($this->base.'/bookmarks/'.$id)->assertNotFound();
        $this->member()->deleteJson($this->base.'/bookmarks/'.$id)->assertNoContent();
    }

    public function test_bookmarks_reject_missing_drafts_and_hide_items_unpublished_later(): void
    {
        $draft = $this->article(['published_at' => null]);
        $this->member()->postJson($this->base.'/bookmarks', ['item_type' => 'article', 'item_id' => $draft->getKey()])->assertNotFound();
        $this->postJson($this->base.'/bookmarks', ['item_type' => 'content', 'item_id' => 999999])->assertNotFound();
        $article = $this->article();
        $this->postJson($this->base.'/bookmarks', ['item_type' => 'article', 'item_id' => $article->getKey()])->assertCreated();
        $article->update(['published_at' => null]);
        $this->getJson($this->base.'/bookmarks')->assertOk()->assertJsonPath('data.0.item', null);
        $this->getJson($this->base.'/dashboard')->assertOk()->assertJsonPath('data.bookmarks.0.item', null);
    }

    public function test_each_bookmarkable_resource_is_supported(): void
    {
        $character = CharacterProfile::create(['category_id' => $this->category(), 'name' => 'Hero']);
        $merch = MerchandiseItem::create(['category_id' => $this->category(), 'name' => 'Figure']);
        $media = $this->media();
        $this->member();
        foreach (['character' => $character, 'merchandise' => $merch, 'media' => $media] as $type => $item) {
            $this->postJson($this->base.'/bookmarks', ['item_type' => $type, 'item_id' => $item->getKey()])->assertCreated();
        }
        $this->getJson($this->base.'/bookmarks')->assertOk()->assertJsonPath('total', 3);
    }

    public function test_rating_ownership_validation_and_switching_between_stars_and_thumbs(): void
    {
        $media = $this->media();
        $url = $this->base.'/media/'.$media->getKey().'/rating';
        $this->member()->putJson($url, ['rating_value' => 6])->assertUnprocessable();
        $this->putJson($url, [])->assertUnprocessable();
        $this->putJson($url, ['rating_value' => 4, 'thumbs_value' => 'up'])->assertUnprocessable();
        $this->putJson($url, ['rating_value' => 4, 'user_id' => $this->admin->getKey()])->assertOk()->assertJsonPath('data.user_id', $this->member->getKey());
        $this->putJson($url, ['thumbs_value' => 'up'])->assertOk()->assertJsonPath('data.rating_value', null);
        $this->assertDatabaseCount('media_ratings', 1);
        $this->actingAs($this->admin)->getJson($url)->assertOk()->assertJsonPath('data', null);
        $this->deleteJson($url)->assertNotFound();
        $this->member()->deleteJson($url)->assertNoContent();
    }

    public function test_submissions_remain_pending_until_admin_approval_and_cannot_be_hijacked(): void
    {
        $data = ['category_id' => $this->category(), 'title' => 'My story', 'body_html' => '<p>Safe</p><script>bad()</script>', 'status' => 'approved', 'user_id' => $this->admin->getKey()];
        $id = $this->member()->postJson($this->base.'/submissions', $data)->assertCreated()->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.body_html', '<p>Safe</p>')->assertJsonPath('data.user_id', $this->member->getKey())->json('data.submission_id');
        $this->assertDatabaseCount('articles', 0);
        $this->actingAs($this->admin)->getJson($this->base.'/submissions/'.$id)->assertNotFound();
        $this->patchJson($this->base.'/submissions/'.$id, ['title' => 'Hijack'])->assertNotFound();
        $this->member()->patchJson($this->base.'/submissions/'.$id, ['title' => 'Edited'])->assertOk();
        $this->actingAs($this->admin)->postJson('/admin/api/submissions/'.$id.'/review', ['decision' => 'approved'])->assertOk();
        $this->assertDatabaseCount('articles', 1);
        $this->member()->patchJson($this->base.'/submissions/'.$id, ['title' => 'After review'])->assertConflict();
        $this->deleteJson($this->base.'/submissions/'.$id)->assertConflict();
        $this->getJson($this->base.'/submissions/'.$id)->assertOk()->assertJsonPath('data.status', 'approved');
        $this->getJson($this->base.'/explore?item_type=article')->assertOk()->assertJsonPath('total', 1);
    }

    public function test_pending_submission_can_be_withdrawn_and_empty_sanitized_body_is_rejected(): void
    {
        $data = ['category_id' => $this->category(), 'title' => 'Bad', 'body_html' => '<script>bad()</script>'];
        $this->member()->postJson($this->base.'/submissions', $data)->assertUnprocessable();
        $data['body_html'] = '<p>Good</p>';
        $id = $this->postJson($this->base.'/submissions', $data)->assertCreated()->json('data.submission_id');
        $this->deleteJson($this->base.'/submissions/'.$id)->assertNoContent();
        $this->assertDatabaseCount('fan_submissions', 0);
    }

    public function test_feedback_creation_and_history_do_not_allow_cross_user_access(): void
    {
        $anonymous = $this->postJson($this->base.'/feedback', ['type' => 'query', 'message' => 'Visitor question', 'status' => 'resolved', 'user_id' => $this->member->getKey()])
            ->assertCreated()->assertJsonPath('data.user_id', null)->assertJsonPath('data.status', 'open')->json('data.feedback_id');
        $id = $this->member()->postJson($this->base.'/feedback', ['type' => 'bug', 'message' => 'Member bug'])->assertCreated()->json('data.feedback_id');
        $this->getJson($this->base.'/feedback')->assertOk()->assertJsonPath('total', 1);
        $this->getJson($this->base.'/feedback/'.$anonymous)->assertNotFound();
        $this->actingAs($this->admin)->getJson($this->base.'/feedback/'.$id)->assertNotFound();
    }

    public function test_unverified_users_cannot_use_personalized_or_contribution_endpoints(): void
    {
        $user = User::factory()->unverified()->create();
        $this->actingAs($user)->getJson($this->base.'/dashboard')->assertForbidden();
        $this->getJson($this->base.'/bookmarks')->assertForbidden();
        $this->postJson($this->base.'/submissions', [])->assertForbidden();
        $this->putJson($this->base.'/media/1/rating', ['rating_value' => 5])->assertForbidden();
        $this->getJson($this->base.'/profile')->assertOk();
        $this->postJson($this->base.'/auth/email/resend')->assertOk();
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_dashboard_only_contains_current_users_activity_preferences_and_bookmarks(): void
    {
        $content = $this->content();
        Bookmark::create(['user_id' => $this->admin->getKey(), 'item_type' => 'content', 'item_id' => $content->getKey(), 'note' => 'Admin secret']);
        $this->member()->patchJson($this->base.'/profile', ['favorite_fandoms' => ['World'], 'category_ids' => [$this->category()]])->assertOk();
        $this->getJson($this->base.'/dashboard')->assertOk()->assertJsonPath('data.counts.bookmarks', 0)
            ->assertJsonPath('data.favorite_fandoms.0', 'World')->assertJsonCount(1, 'data.recent_activity');
    }

    public function test_nearby_events_and_calendar_overlap_filtering(): void
    {
        $near = Event::create(['title' => 'Karachi meetup', 'city' => 'Karachi', 'event_type' => 'meetup', 'event_date' => '2027-01-10', 'end_date' => '2027-01-12', 'latitude' => 24.86, 'longitude' => 67.01]);
        Event::create(['title' => 'Lahore meetup', 'city' => 'Lahore', 'event_type' => 'meetup', 'event_date' => '2027-01-10', 'latitude' => 31.52, 'longitude' => 74.35]);
        $this->getJson($this->base.'/events?latitude=24.86&longitude=67.01&radius_km=30')->assertOk()->assertJsonPath('total', 1);
        $this->getJson($this->base.'/events?city=Karachi&from=2027-01-11&to=2027-01-11')->assertOk()->assertJsonPath('total', 1);
        $this->getJson($this->base.'/events?to=2027-02-01')->assertOk()->assertJsonPath('total', 2);
        $this->getJson($this->base.'/events?latitude=24.86')->assertUnprocessable();
        $this->getJson($this->base.'/events?from=2027-02-01&to=2027-01-01')->assertUnprocessable();
        $this->getJson($this->base.'/events/'.$near->getKey())->assertOk()->assertJsonMissingPath('data.created_by');
    }

    public function test_public_catalog_endpoints_work_without_login(): void
    {
        $this->getJson($this->base.'/categories')->assertOk()->assertJsonCount(8, 'data');
        $this->getJson($this->base.'/tags')->assertOk();
        $this->getJson($this->base.'/explore')->assertOk()->assertJsonPath('total', 0);
        $this->getJson($this->base.'/upcoming')->assertOk();
        $this->getJson($this->base.'/events')->assertOk();
        $this->getJson($this->base.'/catalog/content/999999')->assertNotFound();
        $this->getJson($this->base.'/profile')->assertUnauthorized();
    }

    public function test_guests_receive_auth_errors_on_throttled_personal_routes(): void
    {
        $this->postJson($this->base.'/submissions', [])->assertUnauthorized();
        $this->postJson($this->base.'/profile/avatar', [])->assertUnauthorized();
        $this->postJson($this->base.'/auth/email/resend')->assertUnauthorized();
        $this->postJson('/admin/api/uploads', [])->assertUnauthorized();
    }
}
