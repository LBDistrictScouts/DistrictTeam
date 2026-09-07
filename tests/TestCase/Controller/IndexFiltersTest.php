<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class IndexFiltersTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Groups', 'app.Sections', 'app.Teams', 'app.Roles', 'app.Members',
        'app.MemberContactMethods', 'app.Appointments',
    ];

    /** @return array<string, array{0: string, 1: string}> */
    public static function indexPages(): array
    {
        return [
            'members' => ['/members?q=Ada&status=active', 'Ada'],
            'teams' => ['/teams?q=Digital&group_id=aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'Digital Team'],
            'roles' => ['/roles?q=Digital&status=filled', 'Digital Lead'],
            'groups' => ['/groups?q=District&type=district', 'District'],
            'sections' => ['/sections?q=First&section_type=cubs', 'First Scout Group'],
            'appointments' => ['/appointments?q=Ada&status=active', 'Ada Lovelace'],
        ];
    }

    #[DataProvider('indexPages')]
    public function testIndexFiltersUseGetParameters(string $url, string $expectedText): void
    {
        $this->get($url);

        $this->assertResponseOk();
        $this->assertResponseContains($expectedText);
        $this->assertResponseContains('Search & filters');
        $this->assertResponseContains('data-workspace-index-filters');
        $this->assertResponseContains('data-has-active-filters="true"');
        $this->assertResponseContains('workspace-index-filters.js');
    }

    public function testPaginationLimitIsSavedForEveryDirectory(): void
    {
        $this->get('/members?limit=50');

        $this->assertResponseOk();
        $this->assertSession(50, 'Pagination.limit');
        $this->assertResponseContains('name="limit"');

        $this->get('/teams');

        $this->assertResponseOk();
        $this->assertResponseContains('name="limit"');
    }

    public function testInvalidGroupFilterIsIgnored(): void
    {
        foreach (['/appointments', '/roles', '/teams', '/sections'] as $path) {
            $this->get($path . '?group_id=invalid');

            $this->assertResponseOk();
        }
    }
}
