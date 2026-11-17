<?php

namespace Tests\Feature;

use Database\Seeders\PortfolioContentSeeder;
use Database\Seeders\WebsiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PortfolioContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(WebsiteSeeder::class);
    }

    public function test_all_remaining_projects_receive_complete_copy_without_being_published(): void
    {
        foreach (config('portfolio-content') as $slug => $copy) {
            DB::table('portfolio_projects')->insert([
                'slug' => $slug, 'title' => 'Original project title', 'description' => '', 'content' => '',
                'category' => 'Original category', 'status' => 'draft', 'is_demonstration' => false,
                'sort_order' => 7, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $featuredBefore = DB::table('portfolio_projects')->whereIn('slug', ['alfa-network', 'e-school'])->get()->toJson();
        $this->seed(PortfolioContentSeeder::class);
        foreach (config('portfolio-content') as $slug => $copy) {
            $record = DB::table('portfolio_projects')->where('slug', $slug)->first();
            foreach ($copy as $field => $value) {
                $this->assertSame($value, $record->$field);
            }
            $this->assertSame($copy['description'], $record->seo_description);
            $this->assertSame('Original project title', $record->title);
            $this->assertSame('Original category', $record->category);
            $this->assertSame('draft', $record->status);
            $this->assertNull($record->published_at);
            $this->assertSame(7, $record->sort_order);
            $this->assertGreaterThanOrEqual(2, count(explode("\n\n", $record->content)));
            DB::table('portfolio_projects')->where('id', $record->id)->update(['status' => 'published', 'published_at' => now()]);
            $this->get('/portfolio/'.$slug)->assertOk()->assertSee($copy['description'])->assertSee($copy['challenge'])->assertSee($copy['solution'])->assertSee($copy['outcome']);
        }
        $this->assertSame($featuredBefore, DB::table('portfolio_projects')->whereIn('slug', ['alfa-network', 'e-school'])->get()->toJson());
    }

    public function test_existing_edits_and_archived_projects_are_preserved_when_reseeded(): void
    {
        $slugs = array_keys(config('portfolio-content'));
        $id = DB::table('portfolio_projects')->insertGetId([
            'slug' => $slugs[0], 'title' => 'Edited title', 'description' => 'Our own summary',
            'content' => 'Our own content', 'challenge' => 'Our own challenge', 'status' => 'published', 'published_at' => now(),
        ]);
        $archivedId = DB::table('portfolio_projects')->insertGetId([
            'slug' => $slugs[1], 'title' => 'Archived project', 'description' => '', 'status' => 'draft', 'deleted_at' => now(),
        ]);
        $this->seed(PortfolioContentSeeder::class);
        $this->assertDatabaseHas('portfolio_projects', ['id' => $id, 'description' => 'Our own summary', 'content' => 'Our own content', 'challenge' => 'Our own challenge', 'status' => 'published']);
        $this->assertDatabaseHas('portfolio_projects', ['id' => $archivedId, 'description' => '', 'content' => null]);
        DB::table('portfolio_projects')->where('id', $id)->update(['solution' => 'Edited solution']);
        $this->seed(PortfolioContentSeeder::class);
        $this->assertDatabaseHas('portfolio_projects', ['id' => $id, 'solution' => 'Edited solution']);
        $this->assertDatabaseCount('portfolio_projects', 4);
    }
}
