<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Article;
use App\Models\ArticleImage;
use App\Models\ArticleTimelineEvent;
use App\Models\CharacterProfile;
use App\Models\Content;
use App\Models\Event;
use App\Models\FanSubmission;
use App\Models\Media;
use App\Models\MerchandiseItem;
use App\Models\MerchandiseImage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoContentSeeder extends Seeder
{
    private array $categories = [
        'anime' => ['Anime', 'Japanese animated series and films with imaginative worlds, characters and stories.'],
        'gaming' => ['Gaming', 'Video games, interactive worlds, releases and player communities across platforms.'],
        'movies' => ['Movies', 'Feature films, cinematic universes and the stories fans discuss on the big screen.'],
        'tv-shows' => ['TV Shows', 'Television and streaming series, episodes, recaps and fan theories.'],
        'k-pop' => ['K-Pop', 'Korean pop artists, music releases, performances and fan culture.'],
        'comics' => ['Comics', 'Comic-book heroes, graphic novels and the universes built around them.'],
        'manga' => ['Manga', 'Japanese comics, serialized stories and the fandoms that follow them.'],
        'cosplay' => ['Cosplay', 'Costume craft, character-inspired looks and community convention highlights.'],
    ];

    public function run(): void
    {
        $categoryModels = $this->seedCategories();
        $adminId = User::where('email', 'admin@fanhubplus.com')->value('user_id');
        $contentDirectory = $this->sourceDirectory('content', 'Content');
        $characterDirectory = $this->sourceDirectory('characters', 'Character profiles');
        $merchandiseDirectory = $this->sourceDirectory('merchandise', 'Merchandise');

        $counts = [
            'categories' => count($categoryModels), 'content' => 0, 'media' => 0,
            'articles' => 0, 'timeline_events' => 0, 'events' => 0,
            'characters' => 0, 'merchandise' => 0, 'merchandise_images' => 0, 'copied' => 0,
        ];

        if ($contentDirectory) {
            $contentFiles = $this->assetFiles($contentDirectory, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'jfif', 'mp4', 'mov', 'webm']);
            $images = array_values(array_filter($contentFiles, fn (string $file) => in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp', 'gif', 'jfif'], true)));
            $videos = array_values(array_filter($contentFiles, fn (string $file) => in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['mp4', 'mov', 'webm'], true)));

            foreach ($images as $file) {
                $fandom = $this->fandom($file);
                $category = $this->categoryFor($file, $categoryModels);
                $video = $this->relatedVideo($fandom, $videos);
                $type = $video && str_contains(strtolower($video), 'trailer') || str_contains(strtolower($file), 'trailer') ? 'trailer' : ($video ? 'video' : 'article');
                $title = $this->humanTitle(pathinfo($file, PATHINFO_FILENAME));
                $thumbnail = $this->copyAsset($file, 'content');
                $counts['copied']++;
                $content = Content::firstOrCreate(['title' => $title], [
                    'category_id' => $category->getKey(), 'title' => $title, 'type' => $type,
                    'genre' => $this->genre($category->slug), 'description' => $this->contentDescription($title, $category->slug),
                    'thumbnail_url' => $thumbnail, 'fandom_name' => $fandom, 'created_by' => $adminId,
                ]);
                $content->update(['thumbnail_url' => $thumbnail, 'type' => $type, 'category_id' => $category->getKey(), 'fandom_name' => $fandom, 'genre' => $this->genre($category->slug)]);
                $counts['content']++;

                if ($video) {
                    $mediaUrl = $this->copyAsset($video, 'content/videos');
                    $mediaType = $type === 'trailer' ? 'trailer' : 'video';
                    $media = Media::firstOrCreate(['content_id' => $content->getKey(), 'media_type' => $mediaType], [
                        'content_id' => $content->getKey(), 'media_type' => $mediaType,
                        'media_url' => $mediaUrl, 'uploaded_by' => $adminId,
                    ]);
                    $media->update(['media_url' => $mediaUrl, 'uploaded_by' => $adminId]);
                    if ($media->wasRecentlyCreated) $counts['media']++;
                }
            }
        }

        foreach (Content::orderBy('content_id')->get() as $content) {
            $article = Article::firstOrCreate(['title' => $content->title], [
                'category_id' => $content->category_id, 'author_id' => $adminId, 'title' => $content->title,
                'body_html' => '<p>'.e($content->description).'</p>', 'cover_image_url' => $content->thumbnail_url,
                'is_featured' => true, 'published_at' => now(), 'fandom_name' => $content->fandom_name, 'genre' => $content->genre,
            ]);
            if ($article->wasRecentlyCreated) $counts['articles']++;
            if ($content->thumbnail_url) ArticleImage::firstOrCreate(['article_id' => $article->getKey(), 'image_url' => $content->thumbnail_url], [
                'article_id' => $article->getKey(), 'image_url' => $content->thumbnail_url,
                'caption' => $content->title, 'display_order' => 0,
            ]);
            foreach ([
                ['Announcement', 'The first reveal establishes the theme and gives fans a clear starting point.'],
                ['Fan discussion', 'Community reactions, details and theories continue to shape the conversation around this feature.'],
            ] as $order => [$label, $description]) {
                $timeline = ArticleTimelineEvent::firstOrCreate(['article_id' => $article->getKey(), 'event_label' => $label], [
                    'article_id' => $article->getKey(), 'event_label' => $label,
                    'event_date' => today()->addDays($order + 1)->toDateString(), 'description' => $description,
                    'display_order' => $order,
                ]);
                if ($timeline->wasRecentlyCreated) $counts['timeline_events']++;
            }
        }

        $eventSeed = [
            ['Fan Hub Convention 2026', 'convention', 'Anime', 'Lahore', 'Expo Centre Lahore'],
            ['Dune Part 3 Premiere Night', 'premiere', 'Movies', 'Karachi', 'Cinepax Ocean Mall'],
            ['BLACKPINK Release Watch Party', 'release', 'K-Pop', 'Islamabad', 'The Arena'],
            ['Comic-Con Cosplay Meetup', 'meetup', 'Cosplay', 'Lahore', 'Community Arts Hall'],
            ['One Piece Screening Day', 'screening', 'Manga', 'Karachi', 'Arts Council Cinema'],
        ];
        foreach ($eventSeed as [$title, $type, $categoryName, $city, $venue]) {
            $category = collect($categoryModels)->first(fn (Category $model) => $model->name === $categoryName);
            $event = Event::firstOrCreate(['title' => $title], [
                'category_id' => $category?->getKey(), 'title' => $title,
                'description' => "A Fan Hub Plus community event for fans to discover, discuss and celebrate {$categoryName}.",
                'event_type' => $type, 'event_date' => now()->addDays(14 + $counts['events'])->setTime(18, 0),
                'city' => $city, 'venue' => $venue, 'created_by' => $adminId,
            ]);
            if ($event->wasRecentlyCreated) $counts['events']++;
        }

        if ($characterDirectory) {
            foreach ($this->assetFiles($characterDirectory, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'jfif']) as $file) {
                $category = $this->categoryFor($file, $categoryModels);
                $name = $this->humanTitle(pathinfo($file, PATHINFO_FILENAME));
                $imageUrl = $this->copyAsset($file, 'characters');
                $character = CharacterProfile::firstOrCreate(['name' => $name], [
                    'category_id' => $category->getKey(), 'fandom_name' => $this->fandom($file), 'name' => $name,
                    'bio' => $this->characterBio($name, $category->slug), 'image_url' => $imageUrl, 'created_by' => $adminId,
                ]);
                $character->update(['category_id' => $category->getKey(), 'fandom_name' => $this->fandom($file), 'image_url' => $imageUrl]);
                if ($character->wasRecentlyCreated) $counts['characters']++;
            }
        }

        if ($merchandiseDirectory) {
            foreach ($this->assetFiles($merchandiseDirectory, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'jfif']) as $file) {
                $category = $this->categoryFor($file, $categoryModels);
                $name = $this->humanTitle(pathinfo($file, PATHINFO_FILENAME));
                $imageUrl = $this->copyAsset($file, 'merchandise');
                $item = MerchandiseItem::firstOrCreate(['name' => $name], [
                    'category_id' => $category->getKey(), 'name' => $name,
                    'description' => $this->merchandiseDescription($name), 'image_url' => $imageUrl,
                    'fandom_name' => $this->fandom($file), 'tag' => $this->merchandiseTag($name),
                ]);
                $item->update(['category_id' => $category->getKey(), 'fandom_name' => $this->fandom($file), 'image_url' => $imageUrl]);
                if ($item->wasRecentlyCreated) $counts['merchandise']++;
                if (MerchandiseImage::firstOrCreate(['item_id' => $item->getKey(), 'image_url' => $imageUrl], ['item_id' => $item->getKey(), 'image_url' => $imageUrl, 'display_order' => 0])->wasRecentlyCreated) $counts['merchandise_images']++;
            }
        }

        $this->seedMembersAndSubmissions($categoryModels, $adminId);

        $this->command?->info('Demo content seeded from public/assets/images.');
        foreach ($counts as $label => $count) $this->command?->info(ucfirst($label).': '.$count);
    }

    private function seedCategories(): array
    {
        $models = [];
        foreach ($this->categories as $slug => [$name, $description]) {
            $models[$slug] = Category::firstOrCreate(['slug' => $slug], ['name' => $name, 'description' => $description]);
        }
        return $models;
    }

    private function sourceDirectory(string $demoName, string $publicName): ?string
    {
        $demo = storage_path('demo-assets/'.$demoName);
        $public = public_path('assets/images/'.$publicName);
        return is_dir($demo) ? $demo : (is_dir($public) ? $public : null);
    }

    private function assetFiles(string $directory, array $extensions): array
    {
        return collect(File::allFiles($directory))->filter(fn ($file) => in_array(strtolower($file->getExtension()), $extensions, true))->map(fn ($file) => $file->getPathname())->sort()->values()->all();
    }

    private function copyAsset(string $file, string $folder): string
    {
        $safeName = Str::slug(pathinfo($file, PATHINFO_FILENAME)).'.'.strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $target = trim($folder, '/').'/'.$safeName;
        Storage::disk('public')->put($target, fopen($file, 'rb'));
        return Storage::url($target);
    }

    private function humanTitle(string $value): string
    {
        return Str::headline(str_replace(['_', '-'], ' ', $value));
    }

    private function fandom(string $file): string
    {
        $value = strtolower(basename($file));
        return match (true) {
            str_contains($value, 'demon slayer') || str_contains($value, 'tanjiro') => 'Demon Slayer',
            str_contains($value, 'jujutsu') => 'Jujutsu Kaisen',
            str_contains($value, 'elden ring') || str_contains($value, 'aloy') || str_contains($value, 'genshin') => str_contains($value, 'genshin') ? 'Genshin Impact' : (str_contains($value, 'aloy') ? 'Horizon' : 'Elden Ring'),
            str_contains($value, 'black pink') || str_contains($value, 'blackpink') || str_contains($value, 'jisoo') => 'BLACKPINK',
            str_contains($value, 'dune') => 'Dune',
            str_contains($value, 'last of us') || str_contains($value, 'ellie') => 'The Last of Us',
            str_contains($value, 'spider-man') || str_contains($value, 'miles') => 'Spider-Man',
            str_contains($value, 'one piece') || str_contains($value, 'nami') => 'One Piece',
            str_contains($value, 'cosplay') || str_contains($value, 'comic-con') => 'Comic-Con Cosplay',
            str_contains($value, 'nezuko') => 'Demon Slayer',
            default => $this->humanTitle(pathinfo($file, PATHINFO_FILENAME)),
        };
    }

    private function categoryFor(string $file, array $categories): Category
    {
        $value = strtolower(basename($file));
        $slug = match (true) {
            str_contains($value, 'demon') || str_contains($value, 'jujutsu') || str_contains($value, 'tanjiro') || str_contains($value, 'nezuko') => 'anime',
            str_contains($value, 'elden') || str_contains($value, 'genshin') || str_contains($value, 'aloy') => 'gaming',
            str_contains($value, 'dune') => 'movies',
            str_contains($value, 'last of us') || str_contains($value, 'ellie') => 'tv-shows',
            str_contains($value, 'blackpink') || str_contains($value, 'black pink') || str_contains($value, 'jisoo') => 'k-pop',
            str_contains($value, 'spider') || str_contains($value, 'miles') => 'comics',
            str_contains($value, 'one piece') || str_contains($value, 'nami') => 'manga',
            str_contains($value, 'cosplay') || str_contains($value, 'comic-con') => 'cosplay',
            default => 'movies',
        };
        return $categories[$slug];
    }

    private function relatedVideo(string $fandom, array $videos): ?string
    {
        foreach ($videos as $video) if (strcasecmp($this->fandom($video), $fandom) === 0) return $video;
        return null;
    }

    private function genre(string $category): string
    {
        return match ($category) {
            'anime' => 'Action/Fantasy', 'gaming' => 'Action/Adventure', 'movies' => 'Sci-Fi',
            'tv-shows' => 'Drama', 'k-pop' => 'Music', 'comics' => 'Superhero',
            'manga' => 'Adventure', 'cosplay' => 'Community', default => 'Entertainment',
        };
    }

    private function contentDescription(string $title, string $category): string
    {
        return "Explore {$title} through a Fan Hub Plus feature built around {$this->genre($category)} storytelling, creative details and the community conversations it inspires. Discover the standout moments, themes and fan perspectives in one place.";
    }

    private function characterBio(string $name, string $category): string
    {
        return "{$name} is a character fans connect with through memorable choices, recognizable style and a distinctive place in the {$this->genre($category)} world. This Fan Hub Plus profile collects the essential context for discovering and discussing their story.";
    }

    private function merchandiseDescription(string $name): string
    {
        return "A fan-focused {$name} collectible selected for display, gifting and everyday fandom moments. Check the item details and availability before adding it to your collection.";
    }

    private function merchandiseTag(string $name): string
    {
        $value = strtolower($name);
        return str_contains($value, 'lightstick') ? 'Limited Edition' : (str_contains($value, 'figure') ? 'Collectible' : 'Standard');
    }

    private function seedMembersAndSubmissions(array $categories, ?int $adminId): void
    {
        $members = [
            ['Sara Fan', 'sara.member@fanhubplus.test', 'Anime', 'Demon Slayer trailer details fans should not miss', 'Why the latest trailer works so well for both casual viewers and longtime fans.'],
            ['Humna Cosplay', 'humna.member@fanhubplus.test', 'Cosplay', 'Comic-Con cosplay details that stood out', 'A community recap of handmade outfits, props and styling choices.'],
            ['Joe Gamer', 'joe.member@fanhubplus.test', 'Gaming', 'Elden Ring boss guide from a fan perspective', 'A short fan guide focused on patterns, timing and what makes the boss memorable.'],
            ['Marea Blink', 'marea.member@fanhubplus.test', 'K-Pop', 'BLACKPINK comeback styling notes', 'A fan submission about comeback visuals, color, wardrobe and stage presence.'],
        ];
        $password = Hash::make('password');
        foreach ($members as [$name, $email, $categoryName, $title, $body]) {
            $user = User::firstOrCreate(['email' => $email], [
                'name' => $name,
                'password_hash' => $password,
                'role' => 'registered',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            $user->forceFill(['name' => $name, 'role' => 'registered', 'is_active' => true, 'email_verified_at' => $user->email_verified_at ?: now()])->save();
            $category = collect($categories)->first(fn (Category $model) => $model->name === $categoryName) ?? reset($categories);
            $cover = Content::where('category_id', $category->getKey())->value('thumbnail_url') ?: '/assets/images/hero section/hero-anime.jfif';
            FanSubmission::firstOrCreate(['user_id' => $user->getKey(), 'title' => $title], [
                'user_id' => $user->getKey(),
                'category_id' => $category->getKey(),
                'title' => $title,
                'body_html' => '<p>'.e($body).'</p>',
                'cover_image_url' => $cover,
                'status' => 'pending',
                'reviewed_by' => $adminId,
                'review_notes' => 'Demo fan submission seeded for review workflow.',
            ]);
        }
    }
}
