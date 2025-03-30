<?php

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    // Configure the 'solo' tenant
    config(['tenancy.tenants.solo' => ['database' => 'tenant_solo_test']]);

    // Set up the tenant database connection
    config(['database.connections.tenant' => [
        'driver' => 'pgsql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '5432'),
        'database' => 'tenant_solo_test',
        'username' => env('DB_USERNAME', 'postgres'),
        'password' => env('DB_PASSWORD', 'C@rtini#5'),
    ]]);

    // Switch to the tenant connection
    DB::setDefaultConnection('tenant');
    DB::reconnect('tenant');

    // Run migrations on the tenant database
    Artisan::call('migrate:fresh', [
        '--database' => 'tenant',
        '--path' => 'database/migrations', // Ensure migrations are picked up from the correct directory
    ]);

    // Clear rate limiting (if applicable to your tests)
    \Illuminate\Support\Facades\RateLimiter::clear('login');
});

describe('AuthController', function () {
    describe('POST /v1/auth/register', function () {
        it('can register a new user successfully', function () {
            $response = $this->postJson('/api/v1/auth/register', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'Password123',
                'password_confirmation' => 'Password123',
            ], ['X-Tenant-ID' => 'solo']);

            $response
                ->assertStatus(200)
                ->assertJson(fn (AssertableJson $json) =>
                $json->where('status', 'success')
                    ->where('message', 'User registered successfully. Please verify your email.')
                    ->has('data.token')
                    ->etc()
                );

            expect(User::where('email', 'john@example.com')->exists())->toBeTrue();
        });

        it('fails registration with invalid data', function () {
            $response = $this->postJson('/api/v1/auth/register', [
                'name' => '',
                'email' => 'invalid-email',
                'password' => 'short',
                'password_confirmation' => 'different',
            ], ['X-Tenant-ID' => 'solo']);

            $response
                ->assertStatus(422)
                ->assertJson(fn (AssertableJson $json) =>
                $json->where('status', 'error')
                    ->where('message', 'Validation failed')
                    ->has('errors.name')
                    ->etc()
                );
        });

        it('fails registration with duplicate email', function () {
            User::factory()->create(['email' => 'john@example.com']);
            $response = $this->postJson('/api/v1/auth/register', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'Password123',
                'password_confirmation' => 'Password123',
            ], ['X-Tenant-ID' => 'solo']);

            $response->assertStatus(422)
                ->assertJsonPath('errors.email', ['Email sudah terdaftar.']);
        });
    });

    describe('POST /v1/auth/login', function () {
        it('can login with valid credentials', function () {
            $user = User::factory()->create([
                'email' => 'john@example.com',
                'password' => Hash::make('Password123'),
                'email_verified_at' => now(),
            ]);

            $response = $this->postJson('/api/v1/auth/login', [
                'email' => 'john@example.com',
                'password' => 'Password123',
            ], ['X-Tenant-ID' => 'solo']);

            $response
                ->assertStatus(200)
                ->assertJson(fn (AssertableJson $json) =>
                $json->where('status', 'success')
                    ->where('message', 'Login successful')
                    ->has('data.token')
                    ->etc()
                );
        });

        it('fails login with invalid credentials', function () {
            $user = User::factory()->create([
                'email' => 'john@example.com',
                'password' => Hash::make('Password123'),
                'email_verified_at' => now(),
            ]);

            $response = $this->postJson('/api/v1/auth/login', [
                'email' => 'john@example.com',
                'password' => 'wrongpassword',
            ], ['X-Tenant-ID' => 'solo']);

            $response
                ->assertStatus(401)
                ->assertJson(fn (AssertableJson $json) =>
                $json->where('status', 'error')
                    ->where('message', 'Invalid credentials or email not verified')
                    ->etc()
                );
        });

        it('fails login if email is not verified', function () {
            $user = User::factory()->create([
                'email' => 'john@example.com',
                'password' => Hash::make('Password123'),
                'email_verified_at' => null,
            ]);

            $response = $this->postJson('/api/v1/auth/login', [
                'email' => 'john@example.com',
                'password' => 'Password123',
            ], ['X-Tenant-ID' => 'solo']);

            $response
                ->assertStatus(401)
                ->assertJson(fn (AssertableJson $json) =>
                $json->where('status', 'error')
                    ->where('message', 'Invalid credentials or email not verified')
                    ->etc()
                );
        });

        it('fails login with wrong tenant', function () {
            $user = User::factory()->create([
                'email' => 'john@example.com',
                'password' => Hash::make('Password123'),
                'email_verified_at' => now(),
            ]);

            $response = $this->postJson('/api/v1/auth/login', [
                'email' => 'john@example.com',
                'password' => 'Password123',
            ], ['X-Tenant-ID' => 'wrong_tenant']);

            $response->assertStatus(403)
                ->assertJson(['error' => 'Tenant tidak ditemukan']);
        });

        it('blocks login after exceeding rate limit for the tenant', function () {
            $user = User::factory()->create(['email' => 'john@example.com']);

            RateLimiter::clear('login');
            for ($i = 0; $i < 5; $i++) {
                $this->postJson('/api/v1/auth/login', [
                    'email' => 'john@example.com',
                    'password' => 'wrongpassword',
                ], ['X-Tenant-ID' => 'solo']);
            }

            $response = $this->postJson('/api/v1/auth/login', [
                'email' => 'john@example.com',
                'password' => 'wrongpassword',
            ], ['X-Tenant-ID' => 'solo']);

            $response->assertStatus(429)
                ->assertJson(['message' => 'Too Many Attempts.']);
        });
    });

    describe('POST /v1/logout', function () {
        it('can logout successfully', function () {
            $user = User::factory()->create();
            Sanctum::actingAs($user);

            $response = $this->postJson('/api/v1/logout', [], ['X-Tenant-ID' => 'solo']);

            $response
                ->assertStatus(200)
                ->assertJson(fn (AssertableJson $json) =>
                $json->where('status', 'success')
                    ->where('message', 'Logged out successfully')
                    ->where('data', null)
                    ->etc()
                );

            expect($user->tokens()->count())->toBe(0);
        });

        it('fails logout if not authenticated', function () {
            $response = $this->postJson('/api/v1/logout', [], ['X-Tenant-ID' => 'solo']);

            $response->assertStatus(401);
        });
    });

    describe('POST /v1/auth/refresh', function () {
        it('can refresh token successfully', function () {
            $user = User::factory()->create();
            $refreshToken = $user->createToken('refresh_token', ['refresh'], Carbon::now()->addDays(7))->plainTextToken;

            $response = $this->withHeaders([
                'Authorization' => "Bearer $refreshToken",
                'X-Tenant-ID' => 'solo',
            ])->postJson('/api/v1/auth/refresh');

            $response
                ->assertStatus(200)
                ->assertJson(fn (AssertableJson $json) =>
                $json->where('status', 'success')
                    ->where('message', 'Token refreshed successfully')
                    ->has('data.token')
                    ->has('data.refresh_token')
                    ->etc()
                );

            expect($user->tokens()->where('token', hash('sha256', $refreshToken))->exists())->toBeFalse();
        });

        it('fails refresh if not authenticated', function () {
            $response = $this->postJson('/api/v1/auth/refresh', [], ['X-Tenant-ID' => 'solo']);
            $response
                ->assertStatus(401)
                ->assertJson(fn (AssertableJson $json) =>
                $json->where('status', 'error')
                    ->where('message', 'Unauthenticated - Refresh token is required')
                    ->etc()
                );
        });

        it('fails refresh token after logout', function () {
            $user = User::factory()->create();
            $accessToken = $user->createToken('access_token', ['*'])->plainTextToken;
            $refreshToken = $user->createToken('refresh_token', ['refresh'])->plainTextToken;

            $this->withHeaders([
                'Authorization' => "Bearer $accessToken",
                'X-Tenant-ID' => 'solo',
            ])->postJson('/api/v1/logout');

            $response = $this->withHeaders([
                'Authorization' => "Bearer $refreshToken",
                'X-Tenant-ID' => 'solo',
            ])->postJson('/api/v1/auth/refresh');

            $response->assertStatus(401);
        });

        it('fails refresh with invalid refresh token', function () {
            $user = User::factory()->create();
            $accessToken = $user->createToken('access_token', ['*'])->plainTextToken;

            $response = $this->withHeaders([
                'Authorization' => "Bearer invalid-token",
                'X-Tenant-ID' => 'solo',
            ])->postJson('/api/v1/auth/refresh');

            $response
                ->assertStatus(401)
                ->assertJson(fn (AssertableJson $json) =>
                $json->where('status', 'error')
                    ->where('message', 'Invalid refresh token')
                    ->etc()
                );
        });
    });
});
