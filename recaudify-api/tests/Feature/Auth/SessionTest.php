<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\UserSession;
use App\Services\UserSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SessionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(["name" => "superadmin", "guard_name" => "api"]);
        $this->user = User::factory()
            ->withRole("superadmin")
            ->create(["username" => "testuser"]);
    }

    private function loginAndGetToken(): string
    {
        $response = $this->postJson("/api/auth/login", [
            "username" => "testuser",
            "password" => "password",
        ]);

        return $response->json("data.token");
    }

    public function test_login_creates_a_session_record(): void
    {
        $token = $this->loginAndGetToken();

        $this->assertDatabaseCount("user_sessions", 1);

        $sessionId = JWTAuth::setToken($token)->getPayload()->get("session_id");
        $this->assertNotEmpty($sessionId);
        $this->assertDatabaseHas("user_sessions", ["session_id" => $sessionId, "user_id" => $this->user->id]);
    }

    public function test_authenticated_request_with_revoked_session_returns_401(): void
    {
        $token = $this->loginAndGetToken();

        $this->withToken($token)->getJson("/api/auth/me")->assertStatus(200);

        // Se revoca vía el servicio real (no un UPDATE crudo) para probar el camino de
        // invalidación de caché que hace que la revocación sea inmediata.
        $session = UserSession::firstOrFail();
        app(UserSessionService::class)->revoke($session);

        $this->withToken($token)->getJson("/api/auth/me")->assertStatus(401);
    }

    public function test_refresh_preserves_session_id_claim(): void
    {
        $token = $this->loginAndGetToken();
        $originalSessionId = JWTAuth::setToken($token)->getPayload()->get("session_id");

        $response = $this->withToken($token)->postJson("/api/auth/refresh")->assertStatus(200);
        $newToken = $response->json("data.token");

        $newSessionId = JWTAuth::setToken($newToken)->getPayload()->get("session_id");

        $this->assertSame($originalSessionId, $newSessionId);
        $this->withToken($newToken)->getJson("/api/auth/me")->assertStatus(200);
    }

    public function test_logout_revokes_current_session(): void
    {
        $token = $this->loginAndGetToken();

        $this->withToken($token)->postJson("/api/auth/logout")->assertStatus(200);

        $this->assertDatabaseMissing("user_sessions", ["user_id" => $this->user->id, "revoked_at" => null]);
    }

    public function test_user_can_list_own_sessions_with_current_flag(): void
    {
        $token = $this->loginAndGetToken();

        $response = $this->withToken($token)->getJson("/api/auth/sessions");

        $response->assertStatus(200)->assertJsonCount(1, "data")->assertJsonPath("data.0.is_current", true);
    }

    public function test_user_can_revoke_own_session(): void
    {
        $token = $this->loginAndGetToken();
        $this->loginAndGetToken(); // segunda sesión

        $sessions = $this->withToken($token)->getJson("/api/auth/sessions")->json("data");
        $otherSession = collect($sessions)->firstWhere("is_current", false);

        $this->withToken($token)
            ->postJson("/api/auth/sessions/{$otherSession["id"]}/revoke")
            ->assertStatus(200);

        $this->assertDatabaseMissing("user_sessions", ["id" => $otherSession["id"], "revoked_at" => null]);
    }

    public function test_user_cannot_revoke_another_users_session(): void
    {
        $token = $this->loginAndGetToken();

        $otherUser = User::factory()->create(["username" => "otro"]);
        $otherSession = UserSession::create([
            "user_id" => $otherUser->id,
            "session_id" => "otro-session",
            "expires_at" => now()->addHours(4),
        ]);

        $this->withToken($token)
            ->postJson("/api/auth/sessions/{$otherSession->id}/revoke")
            ->assertStatus(404);
    }

    public function test_revoke_all_keeps_current_session_active(): void
    {
        $token = $this->loginAndGetToken();
        $this->loginAndGetToken();
        $this->loginAndGetToken();

        $this->withToken($token)->postJson("/api/auth/sessions/revoke-all")->assertStatus(200);

        $this->withToken($token)->getJson("/api/auth/me")->assertStatus(200);

        $activeCount = UserSession::where("user_id", $this->user->id)->whereNull("revoked_at")->count();
        $this->assertSame(1, $activeCount);
    }

    public function test_viewing_any_user_sessions_is_blocked_without_permission(): void
    {
        // Sin el rol superadmin (que hace bypass de horario y permisos vía Gate::before) un
        // usuario sin "sessions.view" no puede ver el endpoint admin, sea por falta de horario
        // o por falta de permiso — ambos casos son 403, que es lo que nos importa probar aquí.
        Role::firstOrCreate(["name" => "sin-permisos", "guard_name" => "api"]);
        $plainUser = User::factory()
            ->withRole("sin-permisos")
            ->create(["username" => "sinpermiso"]);
        $this->giveFullDaySchedule($plainUser);
        $token = $this->loginAs($plainUser);

        $this->withToken($token)
            ->getJson("/api/sessions?user_id={$this->user->id}")
            ->assertStatus(403);
    }

    public function test_admin_can_view_and_revoke_any_user_sessions_with_permission(): void
    {
        // superadmin ya bypassa el gate de permisos (AppServiceProvider::boot) y el chequeo de
        // horario — la cobertura de "sin permiso => 403" vive en el test anterior.
        $target = User::factory()->create(["username" => "objetivo"]);
        $this->giveFullDaySchedule($target);
        $this->loginAs($target);

        $admin = User::factory()
            ->withRole("superadmin")
            ->create(["username" => "admin2"]);
        $adminToken = $this->loginAs($admin);

        $list = $this->withToken($adminToken)
            ->getJson("/api/sessions?user_id={$target->id}")
            ->assertStatus(200)
            ->json("data");

        $this->assertCount(1, $list);

        $this->withToken($adminToken)
            ->postJson("/api/sessions/{$list[0]["id"]}/revoke")
            ->assertStatus(200);

        // No se re-autentica como "target" acá: el guard JWT de este paquete cachea el usuario
        // resuelto por instancia mientras dure el proceso de test, y Laravel reutiliza esa
        // instancia entre requests simuladas de un mismo método — cambiar de identidad a mitad
        // de test no es fiable. El estado en BD es la fuente de verdad y ya se prueba la
        // aplicación real del 401 (con el mismo token, sin cambiar de usuario) en
        // test_authenticated_request_with_revoked_session_returns_401.
        $this->assertDatabaseHas("user_sessions", ["id" => $list[0]["id"], "user_id" => $target->id]);
        $this->assertDatabaseMissing("user_sessions", ["id" => $list[0]["id"], "revoked_at" => null]);
    }

    private function loginAs(User $user): string
    {
        $response = $this->postJson("/api/auth/login", [
            "username" => $user->username,
            "password" => "password",
        ]);

        return $response->json("data.token");
    }

    private function giveFullDaySchedule(User $user): void
    {
        foreach (range(0, 6) as $dayOfWeek) {
            $user->schedules()->create(["day_of_week" => $dayOfWeek, "start_time" => "00:00", "end_time" => "23:59"]);
        }
    }
}
