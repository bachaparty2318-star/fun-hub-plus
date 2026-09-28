<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Content;
use App\Models\Event;
use App\Models\Media;
use App\Models\MerchandiseItem;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\Support\AdminDatabaseTestCase;

class VisitorBackendTest extends AdminDatabaseTestCase
{
    private string $base = '/visitor/api';

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    private function category(): int
    {
        return Category::firstOrFail()->getKey();
    }

    public function test_visitor_can_browse_public_catalog_without_login(): void
    {
        $content = Content::create([
            'category_id' => $this->category(),
            'title' => 'Visitor trailer',
            'type' => 'video',
            'genre' => 'Action',
            'fandom_name' => 'Fan World',
            'release_date' => '2027-03-01',
            'popularity_score' => 50,
        ]);
        $tag = Tag::create(['tag_name' => 'Visitor']);
        $content->tags()->attach($tag);
        Media::create(['content_id' => $content->getKey(), 'media_type' => 'trailer', 'media_url' => 'https://example.com/trailer.mp4']);
        Article::create(['category_id' => $this->category(), 'title' => 'Draft', 'body_html' => '<p>Hidden</p>', 'published_at' => null]);

        $query = http_build_query([
            'category_id' => $this->category(),
            'genre' => 'Action',
            'fandom_name' => 'Fan World',
            'release_year' => 2027,
            'min_popularity' => 40,
            'tag_id' => $tag->getKey(),
            'sort' => 'popular',
        ]);

        $this->getJson($this->base.'/categories')->assertOk()->assertJsonCount(8, 'data');
        $this->getJson($this->base.'/tags?q=Vis')->assertOk()->assertJsonPath('total', 1);
        $this->getJson($this->base.'/explore?'.$query)->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.item_type', 'media')
            ->assertJsonPath('data.0.share_url', url('/visitor/api/catalog/media/1'));
        $this->getJson($this->base.'/explore?q=Draft')->assertOk()->assertJsonPath('total', 0);
        $this->getJson($this->base.'/catalog/media/1')->assertOk()
            ->assertJsonPath('data.share_url', url('/visitor/api/catalog/media/1'))
            ->assertJsonMissingPath('data.created_by');
        $this->assertSame(0, $content->fresh()->view_count);
    }

    public function test_visitor_upcoming_share_and_events_work_without_personal_data(): void
    {
        $content = Content::create([
            'category_id' => $this->category(),
            'title' => 'Future release',
            'type' => 'release',
            'release_date' => now()->addMonth()->toDateString(),
        ]);
        Content::create([
            'category_id' => $this->category(),
            'title' => 'Past release',
            'type' => 'release',
            'release_date' => now()->subMonth()->toDateString(),
        ]);
        MerchandiseItem::create(['category_id' => $this->category(), 'name' => 'Pre-order badge', 'is_upcoming' => true]);
        $event = Event::create([
            'category_id' => $this->category(),
            'title' => 'Visitor convention',
            'city' => 'Karachi',
            'event_type' => 'convention',
            'event_date' => '2027-02-01',
            'end_date' => '2027-02-03',
            'latitude' => 24.86,
            'longitude' => 67.01,
        ]);

        $this->getJson($this->base.'/upcoming')->assertOk()->assertJsonPath('total', 2);
        $this->getJson($this->base.'/share/content/'.$content->getKey())->assertOk()
            ->assertJsonPath('data.url', url('/visitor/api/catalog/content/'.$content->getKey()))
            ->assertJsonMissingPath('data.note');
        $this->getJson($this->base.'/events?city=Karachi&from=2027-02-02&to=2027-02-02')->assertOk()->assertJsonPath('total', 1);
        $this->getJson($this->base.'/events?latitude=24.86&longitude=67.01&radius_km=25')->assertOk()->assertJsonPath('total', 1);
        $this->getJson($this->base.'/events/'.$event->getKey())->assertOk()->assertJsonMissingPath('data.created_by');
    }

    public function test_visitor_can_register_recover_password_and_submit_feedback(): void
    {
        $this->postJson($this->base.'/feedback', [
            'type' => 'query',
            'message' => 'Visitor question',
            'status' => 'resolved',
            'user_id' => $this->admin->getKey(),
        ])->assertCreated()->assertJsonPath('data.user_id', null)->assertJsonPath('data.status', 'open');

        $this->getJson($this->base.'/auth/csrf')->assertOk()->assertJsonStructure(['csrf_token']);
        $response = $this->postJson($this->base.'/auth/register', [
            'name' => 'Visitor Join',
            'email' => 'visitor-join@example.com',
            'password' => 'VisitorJoin123!',
            'password_confirmation' => 'VisitorJoin123!',
            'role' => 'admin',
        ])->assertCreated()
            ->assertJsonPath('data.role', 'registered')
            ->assertJsonPath('data.email_verified_at', null)
            ->assertJsonStructure(['csrf_token']);
        $user = User::findOrFail($response->json('data.user_id'));
        Notification::assertSentTo($user, VerifyEmail::class);

        $this->postJson($this->base.'/auth/forgot-password', ['email' => $user->email])->assertOk();
        $token = Password::createToken($user);
        $this->getJson($this->base.'/auth/reset-password/'.$token.'?email='.$user->email)
            ->assertOk()->assertJsonPath('submit_to', url('/visitor/api/auth/reset-password'));
    }

    public function test_visitor_prefix_does_not_expose_member_only_actions(): void
    {
        $this->getJson($this->base.'/dashboard')->assertNotFound();
        $this->getJson($this->base.'/profile')->assertNotFound();
        $this->getJson($this->base.'/bookmarks')->assertNotFound();
        $this->postJson($this->base.'/submissions', [])->assertNotFound();
        $this->putJson($this->base.'/media/1/rating', ['rating_value' => 5])->assertNotFound();
    }
}
