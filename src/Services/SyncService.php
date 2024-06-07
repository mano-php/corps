<?php

namespace ManoCode\Corp\Services;

use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use ManoCode\Corp\Models\Department;
use ManoCode\Corp\Models\Employee;
use Slowlyo\OwlAdmin\Models\AdminRole;
use Slowlyo\OwlAdmin\Models\AdminUser;

abstract class SyncService implements SyncServiceInterface
{
    /**
     * 同步部门，通用方法
     * @param array $array
     * @return \Illuminate\Database\Eloquent\Builder|Model
     */
    protected function syncUpdateDepartment(array $array)
    {
        return Department::query()->where('third_party_id', $array['third_party_id'])->updateOrCreate([
            'third_party_id' => $array['third_party_id'],
        ], [
            'name' => $array['name'],
            'third_party_id' => $array['third_party_id'],
            'parent_id' => cache()->remember('ding_dept_id:' . $array['parent_id'], 3600 * 24 * 365, function () use ($array) {
                return intval(Department::query()->where('third_party_id', $array['parent_id'])->value('id'));
            }),
            'type' => $array['type'],
        ]);
    }


    /**
     * 同步部门，通用方法
     * @param array $user
     * @return void
     * @throws \Exception
     */
    protected function syncUpdateUser(array $user)
    {
        # 钉钉的需要特殊处理
        if (isset($user['orderInDepts'])) {
            $order_depts = preg_replace('/(\d+):(\d+)/', '"$1":"$2"', $user['orderInDepts']);
            $order_depts = json_decode($order_depts, true);
        }
        if (isset($user['gmtCreate'])) {
            $dateTime = new DateTime($user['gmtCreate']);
            $user['join_date'] = $dateTime->format('Y-m-d H:i:s');
        }
        $user = array_filter($user);
        switch (true) {
            # 钉钉的同步用户  后面按需求在添加飞书、企业微信等
            case isset($user['dingtalk_id']):
                # 钉钉的部门也需要特殊处理
                $dept_ids = [];
                foreach ($order_depts as $deptId => $order) {
                    $dept_ids[] = cache()->remember('ding_dept_id:' . $deptId, 3600 * 24 * 365, function () use ($deptId) {
                        if ($deptId == 1) {
                            return 0;
                        }
                        return Department::query()->where('third_party_id', $deptId)->value('id');
                    });
                }
                $user['department_ids'] = implode(',', $dept_ids);
                $user['department_id'] = current($dept_ids);
                Employee::query()->updateOrCreate([
                    'dingtalk_id' => $user['dingtalk_id']
                ], $user);
                /**
                 * 判断用户是否入库
                 */
                if(!($model = Employee::query()->where('dingtalk_id',$user['dingtalk_id'])->first())){
                    break;
                }
                /**
                 * 角色自动创建
                 */
                if(!($adminRole = AdminRole::query()->where(['name'=>'员工','slug'=>'employees'])->first())){
                    $adminRole = new AdminRole();
                    $adminRole->setAttribute('name','员工');
                    $adminRole->setAttribute('slug','employees');
                    $adminRole->setAttribute('created_at',date('Y-m-d H:i:s'));
                    $adminRole->save();
                }
                /**
                 * 判断是否有管理员用户 如果没有自动创建
                 */
                if ((!AdminUser::query()->where(['username'=>$model->getAttribute('mobile')])->first())) {
                    // 创建管理员
                    $adminUser = new AdminUser();
                    $adminUser->setAttribute('username',$model->getAttribute('mobile'));
                    // 用户密码 默认为 手机号
                    $adminUser->setAttribute('password',bcrypt($model->getAttribute('mobile')));
                    $adminUser->setAttribute('enabled',1);
                    $adminUser->setAttribute('name',$model->getAttribute('name'));
                    $adminUser->setAttribute('avatar',$model->getAttribute('avatar'));
                    $adminUser->setAttribute('created_at',date('Y-m-d H:i:s'));
                    $adminUser->save();
                    // 绑定角色
                    DB::table('admin_role_users')->insert([
                        'role_id'=>$adminRole->getAttribute('id'),
                        'user_id'=>$adminUser->getAttribute('id'),
                        'created_at'=>$adminUser->getAttribute('created_at')
                    ]);
                }
                break;
        }
    }


    public static function make($type = 'dingtak')
    {
        switch ($type) {
            case 'dingtalk':
                return new DingService();
            default:
                throw new \Exception("暂时仅支持钉钉");
                break;
        }
    }
}
