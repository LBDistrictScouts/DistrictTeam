<?php
declare(strict_types=1);

namespace App\Test\TestCase\Middleware;

use App\Middleware\HostHeaderMiddleware;
use Cake\Core\Configure;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\InternalErrorException;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\Uri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * HostHeaderMiddleware test case.
 */
class HostHeaderMiddlewareTest extends TestCase
{
    private bool $debug;

    private mixed $fullBaseUrl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->debug = (bool)Configure::read('debug');
        $this->fullBaseUrl = Configure::read('App.fullBaseUrl');
    }

    protected function tearDown(): void
    {
        Configure::write('debug', $this->debug);
        Configure::write('App.fullBaseUrl', $this->fullBaseUrl);

        parent::tearDown();
    }

    public function testDebugModeBypassesValidation(): void
    {
        Configure::write('debug', true);
        Configure::delete('App.fullBaseUrl');

        $response = $this->process('http://untrusted.example');

        $this->assertSame(204, $response->getStatusCode());
    }

    public function testProductionRequiresFullBaseUrl(): void
    {
        Configure::write('debug', false);
        Configure::delete('App.fullBaseUrl');

        $this->expectException(InternalErrorException::class);
        $this->process('https://district.example');
    }

    public function testProductionRejectsMismatchedHost(): void
    {
        Configure::write('debug', false);
        Configure::write('App.fullBaseUrl', 'https://district.example');

        $this->expectException(BadRequestException::class);
        $this->process('https://attacker.example');
    }

    public function testProductionAcceptsConfiguredHostCaseInsensitively(): void
    {
        Configure::write('debug', false);
        Configure::write('App.fullBaseUrl', 'https://DISTRICT.example');

        $response = $this->process('https://district.example/members');

        $this->assertSame(204, $response->getStatusCode());
    }

    private function process(string $uri): ResponseInterface
    {
        $request = new ServerRequest(['uri' => new Uri($uri)]);
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(['status' => 204]);
            }
        };

        return (new HostHeaderMiddleware())->process($request, $handler);
    }
}
