<?php

namespace Tests\Unit;

use App\Http\Middleware\EnsureModuleAccess;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EnsureModuleAccessTest extends TestCase
{
    public function test_user_with_a_permission_in_the_module_category_is_allowed(): void
    {
        $request = Request::create('/employees');
        $request->setUserResolver(static fn () => new class
        {
            public function getAllPermissions()
            {
                return collect([(object) ['category' => 'HR']]);
            }
        });

        $response = (new EnsureModuleAccess())->handle(
            $request,
            static fn (): Response => new Response('ok'),
            'hr'
        );

        $this->assertSame('ok', $response->getContent());
    }

    public function test_user_without_a_permission_in_the_module_category_is_forbidden(): void
    {
        $request = Request::create('/employees');
        $request->setUserResolver(static fn () => new class
        {
            public function getAllPermissions()
            {
                return collect([(object) ['category' => 'CRM']]);
            }
        });

        try {
            (new EnsureModuleAccess())->handle(
                $request,
                static fn (): Response => new Response('ok'),
                'hr'
            );
            $this->fail('Expected access to be denied.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }
}
