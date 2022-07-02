<?php

namespace Zeaven\EasySuit\Console\Commands;

use App\Domain\Module\System\Model\Permission;
use App\Domain\Module\System\Model\Role;
use Illuminate\Console\Command;
use Route;
use Str;

class RouteToPermission extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'route:permission';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '根据路由生成权限';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $count = 0;
        $role_count = 0;
        db_trans(
            function () use (&$count, &$role_count) {
                // 添加配置文件中的权限
                $config = config('user-permission', []);
                $defaultGuards = array_keys(config('auth.guards'));
                foreach ($config['permissions'] as $permission) {
                    $guards = $permission['guard_name'] ?: $defaultGuards;
                    foreach ($guards as $guard_name) {
                        Permission::updateOrCreate(
                            [
                                'name' => $permission['name'],
                                'guard_name' => $guard_name
                            ],
                            [
                                'description' => $permission['description'],
                            ]
                        );
                        $count++;
                    }
                }
                foreach ($config['roles'] as $role) {
                    $guards = $role['guard_name'] ?: $defaultGuards;
                    foreach ($guards as $guard_name) {
                        $role['guard_name'] = $guard_name;
                        Role::updateOrCreate(
                            [
                                'name' => $role['name'],
                                'guard_name' => $guard_name
                            ],
                            [
                                'description' => $role['description'],
                            ]
                        );
                        $role_count++;
                    }
                }
            }
        );
        $this->info("{$count}个权限已生成，{$role_count}个角色已生成！");
    }
}
