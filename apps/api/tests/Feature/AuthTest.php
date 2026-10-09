<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Elf;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\ElfSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = '1qaz@WSX3edc';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([DepartmentSeeder::class, ElfSeeder::class]);
    }

    private function login(string $number, string $password = self::PASSWORD)
    {
        return $this->postJson('/api/auth/login', ['number' => $number, 'password' => $password]);
    }

    public function test_精靈可以登入並取得權杖與權限(): void
    {
        $this->login('E008')
            ->assertOk()
            ->assertJsonPath('elf.number', 'E008')
            ->assertJsonPath('elf.department', '人力資源部')
            ->assertJsonPath('elf.rank', '部長')
            ->assertJsonStructure(['token', 'elf' => ['id', 'name', 'permissions']])
            ->assertJsonMissingPath('elf.apiToken');
    }

    public function test_資料庫只存權杖雜湊(): void
    {
        $token = $this->login('E001')->json('token');

        $stored = Elf::where('number', 'E001')->value('api_token');

        $this->assertNotSame($token, $stored);
        $this->assertSame(hash('sha256', $token), $stored);
    }

    public function test_不是公司精靈的編號不能登入(): void
    {
        $this->login('E999')->assertStatus(401)->assertJsonPath('message', '帳號或密碼錯誤');
    }

    public function test_密碼錯誤與帳號不存在回同一則訊息(): void
    {
        $this->login('E001', 'wrong-password')
            ->assertStatus(401)
            ->assertJsonPath('message', '帳號或密碼錯誤');
    }

    public function test_密碼雜湊儲存且不會出現在回應(): void
    {
        $response = $this->login('E001');

        $response->assertJsonMissingPath('elf.password');
        $this->assertNotSame(self::PASSWORD, Elf::where('number', 'E001')->value('password'));
    }

    public function test_重複執行_seeder_不會蓋掉已修改的密碼(): void
    {
        Elf::where('number', 'E001')->first()->update(['password' => 'Changed!Pass123']);

        $this->seed(ElfSeeder::class);

        $this->login('E001')->assertStatus(401);
        $this->login('E001', 'Changed!Pass123')->assertOk();
    }


    public function test_缺少欄位回_422(): void
    {
        $this->postJson('/api/auth/login', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['number', 'password']);
    }

    public function test_登入後可取得自己的資料(): void
    {
        $token = $this->login('E004')->json('token');

        $this->withToken($token)->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('number', 'E004')
            ->assertJsonPath('department', '馴鹿管理部');
    }

    public function test_未登入或權杖錯誤回_401(): void
    {
        $this->getJson('/api/auth/me')->assertStatus(401);
        $this->withToken('invalid')->getJson('/api/auth/me')->assertStatus(401);
    }

    public function test_登出後權杖失效(): void
    {
        $token = $this->login('E001')->json('token');

        $this->withToken($token)->postJson('/api/auth/logout')->assertNoContent();

        $this->withToken($token)->getJson('/api/auth/me')->assertStatus(401);
    }

    public function test_閒置超過_30_分鐘權杖失效並回報原因(): void
    {
        $token = $this->login('E001')->json('token');

        $this->travel(29)->minutes();
        $this->withToken($token)->getJson('/api/auth/me')->assertOk();

        // 剛才的請求算活動，所以要再閒置 30 分鐘才會失效
        $this->travel(29)->minutes();
        $this->withToken($token)->getJson('/api/auth/me')->assertOk();

        $this->travel(30)->minutes();
        $this->withToken($token)->getJson('/api/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('reason', 'idle');

        // 失效後權杖被清掉，再用同一組也只是一般的未登入
        $this->withToken($token)->getJson('/api/auth/me')
            ->assertStatus(401)
            ->assertJsonMissingPath('reason');
    }

    public function test_重新登入會讓舊權杖失效(): void
    {
        $old = $this->login('E001')->json('token');
        $this->login('E001');

        $this->withToken($old)->getJson('/api/auth/me')->assertStatus(401);
    }

    public function test_權限依職級與部門給予(): void
    {
        $permissions = fn (string $number) => $this->login($number)->json('elf.permissions');

        // 人力資源部資深精靈：精靈管理、職缺、動力單位，但不能審核假單
        $hr = $permissions('E019');
        $this->assertContains('elf.roster', $hr);
        $this->assertContains('career.manage', $hr);
        $this->assertContains('reindeer.manage', $hr);
        $this->assertNotContains('leave.review', $hr);

        // 馴鹿管理部：只有動力單位管理；部長多了請假審核
        $reindeer = $permissions('E013');
        $this->assertContains('reindeer.manage', $reindeer);
        $this->assertNotContains('elf.roster', $reindeer);
        $this->assertContains('leave.review', $permissions('E004'));

        // 其他部門的實習精靈只有所有人都有的權限
        $this->assertSame(['leave.apply', 'password.change', 'complaint.file'], $permissions('E020'));

        // 副聖誕老人可審核部長假單
        $this->assertContains('leave.review', $permissions('E001'));
    }

    public function test_人員配置符合設定(): void
    {
        $this->assertSame(3, Elf::where('rank', '實習精靈')->count());
        $this->assertSame(1, Elf::where('rank', '副聖誕老人')->count());

        // 董事會只有副聖誕老人，不適用「每個部門都有部長與精靈」
        $this->assertSame('董事會', Elf::where('rank', '副聖誕老人')->first()->department->name);

        foreach (Department::where('name', '!=', '董事會')->get() as $department) {
            $this->assertSame(
                1,
                Elf::where('department_id', $department->id)->where('rank', '部長')->count(),
                $department->name,
            );
            $this->assertGreaterThanOrEqual(
                1,
                Elf::where('department_id', $department->id)->whereIn('rank', ['正式精靈', '資深精靈'])->count(),
                $department->name,
            );
        }
    }

    public function test_seeder_重複執行不會產生重複精靈(): void
    {
        $this->seed(ElfSeeder::class);

        $this->assertSame(22, Elf::count());
    }

    public function test_正式環境拒絕非_https_的登入(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->postJson('http://localhost/api/auth/login', ['number' => 'E001', 'password' => self::PASSWORD])
            ->assertStatus(403);

        $this->postJson('https://localhost/api/auth/login', ['number' => 'E001', 'password' => self::PASSWORD])
            ->assertOk();
    }

    private const NEW_PASSWORD = 'N3w!Passw0rd#x';

    private function changePassword(string $token, array $overrides = [])
    {
        return $this->withToken($token)->putJson('/api/auth/password', [
            'oldPassword' => self::PASSWORD,
            'newPassword' => self::NEW_PASSWORD,
            'newPasswordConfirmation' => self::NEW_PASSWORD,
            ...$overrides,
        ]);
    }

    public function test_修改密碼後舊密碼失效且新密碼可登入(): void
    {
        $token = $this->login('E001')->json('token');

        $this->changePassword($token)->assertNoContent();

        // 改密碼後所有登入失效，需用新密碼重新登入
        $this->withToken($token)->getJson('/api/auth/me')->assertUnauthorized();

        $this->login('E001')->assertStatus(401);
        $this->login('E001', self::NEW_PASSWORD)->assertOk();
    }

    public function test_修改密碼需要登入(): void
    {
        $this->putJson('/api/auth/password', [])->assertUnauthorized();
    }

    public function test_舊密碼錯誤回_422_且登入狀態不受影響(): void
    {
        $token = $this->login('E001')->json('token');

        $this->changePassword($token, ['oldPassword' => 'wrong-Password1!'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('oldPassword');

        $this->withToken($token)->getJson('/api/auth/me')->assertOk();
    }

    public function test_新密碼不符規則回_422(): void
    {
        $token = $this->login('E001')->json('token');

        $invalid = ['aa1!aaaaaaaa', 'AA1!AAAAAAAA', 'Aaa!aaaaaaaa', 'Aa1aaaaaaaaa', 'Aa1!aaaaaaa'];

        foreach ($invalid as $password) {
            $this->changePassword($token, ['newPassword' => $password, 'newPasswordConfirmation' => $password])
                ->assertStatus(422)
                ->assertJsonValidationErrors('newPassword');
        }
    }

    public function test_新密碼不可與舊密碼相同(): void
    {
        $token = $this->login('E001')->json('token');

        $this->changePassword($token, [
            'newPassword' => self::PASSWORD,
            'newPasswordConfirmation' => self::PASSWORD,
        ])->assertStatus(422)->assertJsonValidationErrors('newPassword');
    }

    public function test_兩次新密碼不一致回_422(): void
    {
        $token = $this->login('E001')->json('token');

        $this->changePassword($token, ['newPasswordConfirmation' => 'Different!Pass12'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('newPasswordConfirmation');
    }
}
