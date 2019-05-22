<?php
/**
 * Created by PhpStorm.
 * User: denismitr
 * Date: 25.06.2018
 * Time: 23:16
 */

namespace Denismitr\Permissions\Test;


use Denismitr\Permissions\Exceptions\UnauthorizedException;
use Denismitr\Permissions\Middleware\AuthGroupAllMiddleware;
use Denismitr\Permissions\Models\AuthGroup;
use Denismitr\Permissions\Test\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MiddlewareAllTest extends TestCase
{
    /**
     * @var AuthGroupAllMiddleware
     */
    protected $allMiddleware;

    public function setUp(): void
    {
        parent::setUp();

        $this->allMiddleware = new AuthGroupAllMiddleware();
    }

    /** @test */
    public function a_guest_cannot_access_a_route_protected_by_auth_group_middleware()
    {
        $this->assertEquals(
            401,
            $this->runMiddleware($this->allMiddleware, 'admins')
        );
    }

    /** @test */
    public function logged_user_can_access_route_if_belongs_to_auth_group()
    {
        // Given
        /** @var User $user */
        $user = User::create(['email' => 'test@test.com']);
        $editors = AuthGroup::create(['name' => 'editors']);

        // Do
        $user->joinAuthGroup($editors);
        $this->be($user);

        $response = $this->runMiddleware(
            $this->allMiddleware,
            'editors'
        );

        // Expect OK
        $this->assertEquals(200, $response);
    }

    /** @test */
    public function logged_user_can_access_a_route_if_has_all_the_permissions()
    {
        // Given
        /** @var User $user */
        $user = User::create(['email' => 'test@test.com']);
        $editors = AuthGroup::create(['name' => 'editors']);
        $staff = AuthGroup::create(['name' => 'staff']);

        // Do
        $user->joinAuthGroup($editors);
        $user->joinAuthGroup($staff);
        $this->be($user);

        $responseA = $this->runMiddleware(
            $this->allMiddleware,
            'editors|staff'
        );

        $responseB = $this->runMiddleware(
            $this->allMiddleware,
            ['editors', 'staff']
        );

        $responseC = $this->runMiddleware(
            $this->allMiddleware,
            'editors,staff'
        );

        // Expect OK
        $this->assertEquals(200, $responseA);
        $this->assertEquals(200, $responseB);
        $this->assertEquals(200, $responseC);
    }

    public function user_cannot_access_protected_route_not_belonging_to_all_required_auth_group()
    {
        // Given
        /** @var User $user */
        $user = User::create(['email' => 'test@test.com']);
        $editors = AuthGroup::create(['name' => 'editors']);
        $staff = AuthGroup::create(['name' => 'staff']);

        // Do
        $user->joinAuthGroup($editors);
        $this->be($user);

        $responseA = $this->runMiddleware(
            $this->allMiddleware,
            'staff'
        );

        $responseB = $this->runMiddleware(
            $this->allMiddleware,
            'non-existent'
        );

        $responseC = $this->runMiddleware(
            $this->allMiddleware,
            'editors|staff'
        );

        // Expect OK
        $this->assertEquals(403, $responseA);
        $this->assertEquals(403, $responseB);
        $this->assertEquals(403, $responseC);
    }

    /** @test */
    public function logged_user_cannot_access_protected_routes_if_does_not_belong_to_any_group()
    {
        // Given
        /** @var User $user */
        $user = User::create(['email' => 'test@test.com']);
        $editors = AuthGroup::create(['name' => 'editors']);
        $staff = AuthGroup::create(['name' => 'staff']);

        // Do
        $this->be($user);

        $responseA = $this->runMiddleware(
            $this->allMiddleware,
            'staff'
        );

        $responseB = $this->runMiddleware(
            $this->allMiddleware,
            'non-existent'
        );

        $responseC = $this->runMiddleware(
            $this->allMiddleware,
            'editors|staff'
        );

        // Expect OK
        $this->assertEquals(403, $responseA);
        $this->assertEquals(403, $responseB);
        $this->assertEquals(403, $responseC);
    }

    protected function runMiddleware($middleware, $parameters)
    {
        try {
            return $middleware->handle(new Request(), function () {
                return (new Response())->setContent('<html></html>');
            }, $parameters)->status();
        } catch (UnauthorizedException $e) {
            return $e->getStatusCode();
        }
    }
}
