# Phase 1 Implementation Spec — Project Scaffold + Auth + Multi-tenant

## Environment
- Working directory: /home/deahermes/saas-personal-finance
- PHP 8.5.4, Composer 2.10, Node 22.22, npm 9.2
- MySQL 8.4 (root:dea_mysql, database: personal_finance)
- Redis running locally
- THIS IS A NEW PROJECT — scaffold fresh Laravel 13 into this directory

## IMPORTANT CONSTRAINTS
1. The repo already has docs/ and .cursorrules — do NOT delete them
2. Use --legacy-peer-deps for all npm installs
3. PHP 8.5: maatwebsite/excel blocked, no other known blocks
4. Never use inline validation — always FormRequest classes
5. All tests must pass green before completing
6. Dark mode is DEFAULT (AntD darkAlgorithm)
7. Commit after every completed task with descriptive message
8. Database name: personal_finance (NOT saas_personal_finance — the .cursorrules is wrong about this)
9. Use MySQL root:dea_mysql — prepend sudo if password auth fails

## All 29 Tasks

### TASK 1: Scaffold Laravel 13
```bash
cd /home/deahermes
composer create-project laravel/laravel saas-personal-finance "13.*" --no-interaction
# If it fails because directory exists, move existing contents to tmp, scaffold, then restore docs/ and .cursorrules
```
After scaffold, verify `php artisan --version` shows Laravel 13.x

### TASK 2: Configure .env
Edit .env:
- DB_CONNECTION=mysql
- DB_HOST=127.0.0.1
- DB_PORT=3306
- DB_DATABASE=personal_finance
- DB_USERNAME=root
- DB_PASSWORD=dea_mysql
- QUEUE_CONNECTION=redis
- CACHE_STORE=redis
- SESSION_DRIVER=redis
- REDIS_HOST=127.0.0.1
- APP_URL=http://localhost:8999

### TASK 3: Install Inertia server-side
```bash
cd /home/deahermes/saas-personal-finance
composer require inertiajs/inertia-laravel
php artisan inertia:middleware
```
This registers HandleInertiaRequests in bootstrap/app.php

### TASK 4: Install frontend deps
```bash
cd /home/deahermes/saas-personal-finance
npm install @inertiajs/react react react-dom antd @ant-design/pro-components @ant-design/pro-table recharts dayjs --legacy-peer-deps
npm install -D @vitejs/plugin-react
```
Update vite.config.js to use react plugin instead of vue:
```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.jsx',
            refresh: true,
        }),
        react(),
    ],
});
```

### TASK 5: Install Laravel Sanctum
```bash
cd /home/deahermes/saas-personal-finance
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

### TASK 6: Configure middleware in bootstrap/app.php
Add to web middleware group (in ->withMiddleware() callback):
- \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class
- \App\Http\Middleware\HandleInertiaRequests::class

### TASK 7: [TDD] RegistrationTest
Create `tests/Feature/Auth/RegistrationTest.php` that:
- Tests user can register with name, email, password, password_confirmation
- Asserts user is created in DB
- Asserts a personal team is automatically created for the user
- Asserts user has current_team_id set
Run `php artisan test --filter=RegistrationTest` — should FAIL (RED phase)

Create the registration endpoint to make it pass:
- `app/Http/Controllers/Auth/RegisteredUserController.php` with store() method
- Route in routes/auth.php or routes/web.php
- Uses Laravel's built-in auth (no Fortify/Jetstream — we build custom)

### TASK 8-10: Team Migrations
Create these migration files:
- `database/migrations/xxxx_create_teams_table.php`:
  - id, user_id (owner FK), name, personal_team (boolean, default false), timestamps
- `database/migrations/xxxx_create_team_user_table.php`:
  - id, team_id FK, user_id FK, role (string, default 'member'), timestamps
  - unique index on [team_id, user_id]
- `database/migrations/xxxx_create_team_invitations_table.php`:
  - id, team_id FK, email, role (string, default 'member'), timestamps

### TASK 11: Add profile fields to users table
Migration `add_profile_fields_to_users_table`:
- currency (string, 3, default 'IDR')
- timezone (string, default 'Asia/Jakarta')
- locale (string, 5, default 'id')
- profile_photo_path (string, nullable)
- current_team_id (foreignId for teams, nullable)
- two_factor_secret (text, nullable)
- two_factor_recovery_codes (text, nullable)

### TASK 12: Team Model + Relationships
`app/Models/Team.php`:
```php
class Team extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'user_id', 'personal_team'];
    protected $casts = ['personal_team' => 'boolean'];
    
    public function owner(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function users(): BelongsToMany { return $this->belongsToMany(User::class, 'team_user')->withPivot('role')->withTimestamps(); }
    public function invitations(): HasMany { return $this->hasMany(TeamInvitation::class); }
}
```

Update `app/Models/User.php`:
```php
// Add relationships:
public function currentTeam(): BelongsTo { return $this->belongsTo(Team::class, 'current_team_id'); }
public function allTeams(): BelongsToMany { return $this->belongsToMany(Team::class, 'team_user')->withPivot('role')->withTimestamps(); }
public function ownedTeams(): HasMany { return $this->hasMany(Team::class, 'user_id'); }
// Add fillable: 'current_team_id', 'currency', 'timezone', 'locale', 'profile_photo_path', 'two_factor_secret', 'two_factor_recovery_codes'
```

### TASK 13: TeamUser Pivot Model
`app/Models/TeamUser.php` extends Pivot:
```php
class TeamUser extends Pivot
{
    protected $casts = ['role' => 'string'];
}
```

### TASK 14: TeamInvitation Model
`app/Models/TeamInvitation.php`:
```php
class TeamInvitation extends Model
{
    use HasFactory;
    protected $fillable = ['team_id', 'email', 'role'];
    public function team(): BelongsTo { return $this->belongsTo(Team::class); }
}
```

### TASK 15: CreatePersonalTeamAction
`app/Actions/Teams/CreatePersonalTeamAction.php`:
```php
namespace App\Actions\Teams;

use App\Models\Team;
use App\Models\User;

class CreatePersonalTeamAction
{
    public function execute(User $user): Team
    {
        $team = $user->ownedTeams()->create([
            'name' => $user->name . "'s Team",
            'personal_team' => true,
        ]);
        $team->users()->attach($user, ['role' => 'owner']);
        $user->forceFill(['current_team_id' => $team->id])->save();
        return $team;
    }
}
```

### TASK 16: CreatePersonalTeamListener
`app/Listeners/CreatePersonalTeamListener.php`:
```php
namespace App\Listeners;

use App\Actions\Teams\CreatePersonalTeamAction;
use Illuminate\Auth\Events\Registered;

class CreatePersonalTeamListener
{
    public function __construct(private CreatePersonalTeamAction $action) {}

    public function handle(Registered $event): void
    {
        $this->action->execute($event->user);
    }
}
```
Laravel 11+ auto-discovers listeners — no manual registration needed.

### TASK 17: EnsureTeamContext Middleware
`app/Http/Middleware/EnsureTeamContext.php`:
- Gets current user
- If user has no current_team_id, abort(403, 'No team context.')
- Shares current team via Inertia
Register in bootstrap/app.php web middleware group after auth.

### TASK 18: BelongsToTeam Trait
`app/Models/Concerns/BelongsToTeam.php`:
```php
namespace App\Models\Concerns;

use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToTeam
{
    protected static function bootBelongsToTeam(): void
    {
        static::addGlobalScope('team', function (Builder $builder) {
            if (Auth::check() && Auth::user()->current_team_id) {
                $builder->where($builder->getModel()->getTable() . '.team_id', Auth::user()->current_team_id);
            }
        });

        static::creating(function ($model) {
            if (Auth::check() && empty($model->team_id)) {
                $model->team_id = Auth::user()->current_team_id;
            }
        });
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
```

### TASK 19: [TDD] BelongsToTeamTest
`tests/Unit/Models/Concerns/BelongsToTeamTest.php`:
- Create 2 users, each with their own team
- Create a test model that uses the trait (or test it on a future model)
- Assert cross-team query isolation: user1 can only see their team's records

### TASK 20: 2FA Setup
```bash
composer require pragmarx/google2fa-laravel
```
Add two_factor_secret and two_factor_recovery_codes columns to users (already in TASK 11 migration).
Publish config: `php artisan vendor:publish --provider="PragmaRX\Google2FALaravel\ServiceProvider"`

### TASK 21: Auth React Pages
Create `resources/js/Pages/Auth/` directory with:
- `GuestLayout.jsx` — centered card layout with dark background
- `Register.jsx` — AntD Form with name, email, password, password_confirmation, submit
- `Login.jsx` — AntD Form with email, password, remember me, submit, forgot password link
- `ForgotPassword.jsx` — AntD Form with email, submit
- `ResetPassword.jsx` — AntD Form with email, password, password_confirmation, token (hidden), submit

### TASK 22: Inertia Root View
`resources/views/app.blade.php`:
```html
<!DOCTYPE html>
<html data-theme="dark" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Personal Finance</title>
    @viteReactRefresh
    @vite('resources/js/app.jsx')
</head>
<body style="margin:0; background:#141414;">
    @inertia
</body>
</html>
```

### TASK 23: Inertia::share() in AppServiceProvider
In `app/Providers/AppServiceProvider.php` boot():
```php
Inertia::share([
    'auth' => [
        'user' => fn () => Auth::check() ? Auth::user()->only('id', 'name', 'email') : null,
    ],
    'current_team' => fn () => Auth::check() && Auth::user()->currentTeam ? Auth::user()->currentTeam->only('id', 'name') : null,
    'flash' => fn () => [
        'success' => session('success'),
        'error' => session('error'),
    ],
]);
```

### TASK 24: ThemeContext + AntD Dark Mode
`resources/js/Contexts/ThemeContext.jsx`:
```jsx
import { createContext, useContext, useState } from 'react';

const ThemeContext = createContext();

export function ThemeProvider({ children }) {
    const [theme, setTheme] = useState('dark'); // dark is default
    return <ThemeContext.Provider value={{ theme, setTheme }}>{children}</ThemeContext.Provider>;
}

export const useTheme = () => useContext(ThemeContext);
```

`resources/js/app.jsx`:
```jsx
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { ConfigProvider, theme } from 'antd';
import { ThemeProvider, useTheme } from './Contexts/ThemeContext';

createInertiaApp({
    resolve: name => { /* resolve pages */ },
    setup({ el, App, props }) {
        createRoot(el).render(
            <ThemeProvider>
                <ThemedApp App={App} props={props} />
            </ThemeProvider>
        );
    },
});

function ThemedApp({ App, props }) {
    const { theme: currentTheme } = useTheme();
    return (
        <ConfigProvider theme={{ algorithm: currentTheme === 'dark' ? theme.darkAlgorithm : theme.defaultAlgorithm }}>
            <App {...props} />
        </ConfigProvider>
    );
}
```

### TASK 25: Auth Controller (Sanctum SPA)
`app/Http/Controllers/Auth/AuthenticatedSessionController.php`:
- login(): validate credentials, Auth::attempt(), $request->session()->regenerate(), redirect to dashboard
- logout(): Auth::guard('web')->logout(), $request->session()->invalidate(), $request->session()->regenerateToken(), redirect to /
Add routes in routes/web.php or routes/auth.php.

### TASK 26: [TDD] TeamIsolationTest
`tests/Feature/Auth/TeamIsolationTest.php`:
- Create user with no team
- Try to access a team-protected route
- Assert 403 response
- Create user with team
- Access team-protected route
- Assert 200 response

### TASK 27: Install Cashier + Cashier Paddle
```bash
composer require laravel/cashier laravel/cashier-paddle
```
Config only — no billing logic yet.

### TASK 28: Publish Cashier migrations, migrate
```bash
php artisan vendor:publish --tag=cashier-migrations
php artisan vendor:publish --tag=cashier-paddle-migrations
php artisan migrate
```

### TASK 29: Full test suite GREEN
```bash
php artisan test
```
All tests must pass. Fix any failures, rerun until green.

## Post-Implementation Checklist
1. git add -A && git commit -m "feat: Phase 1 — Laravel 13 scaffold + Sanctum auth + Teams multi-tenant + dark mode"
2. git push origin main
3. php artisan serve --port=8999 (background)
4. curl -s http://localhost:8999 returns HTML