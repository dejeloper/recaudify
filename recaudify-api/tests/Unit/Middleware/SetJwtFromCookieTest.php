<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\SetJwtFromCookie;
use Illuminate\Http\Request;
use Tests\TestCase;

class SetJwtFromCookieTest extends TestCase
{
    private function handle(Request $request): Request
    {
        $captured = $request;
        (new SetJwtFromCookie())->handle($request, function (Request $r) use (&$captured) {
            $captured = $r;

            return response("ok");
        });

        return $captured;
    }

    public function test_sets_authorization_header_from_cookie(): void
    {
        $request = Request::create("/", "GET", [], ["token" => "abc123"]);

        $result = $this->handle($request);

        $this->assertSame("Bearer abc123", $result->headers->get("Authorization"));
    }

    public function test_does_not_override_existing_authorization_header(): void
    {
        $request = Request::create("/", "GET", [], ["token" => "cookie-token"]);
        $request->headers->set("Authorization", "Bearer header-token");

        $result = $this->handle($request);

        $this->assertSame("Bearer header-token", $result->headers->get("Authorization"));
    }

    public function test_does_nothing_without_cookie(): void
    {
        $request = Request::create("/", "GET");

        $result = $this->handle($request);

        $this->assertFalse($result->headers->has("Authorization"));
    }
}
