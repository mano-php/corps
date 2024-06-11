<?php

namespace ManoCode\Corp\Http\Controllers;

use Illuminate\Http\Request;

use ManoCode\Corp\CorpsServiceProvider;
use ManoCode\Corp\Services\DingService;
use Slowlyo\OwlAdmin\Admin;
use Slowlyo\OwlAdmin\Controllers\AdminController;
use Slowlyo\OwlAdmin\Models\AdminRole;
use Slowlyo\OwlAdmin\Models\AdminUser;
use Illuminate\Support\Facades\DB;
use ManoCode\Corp\Models\Employee;

class LoginController extends AdminController
{

    public function dingLogin(Request $request)
    {
        $domain = $request->getSchemeAndHttpHost();
        $dingClientId = CorpsServiceProvider::setting('client_id');
        $callBackUrl = "{$domain}/admin-api/corp/loginByCode";
        $callBackUrl = urlencode($callBackUrl);
        $url = "https://login.dingtalk.com/oauth2/challenge.htm?redirect_uri={$callBackUrl}&response_type=code&client_id={$dingClientId}&scope=openid&prompt=consent";
        $data = [
            'url' => $url
        ];
        return $this->response()->success($data);
    }

    public function loginByCode(Request $request)
    {
        $data = request()->query();

        $code = $data['code'] ?? '';
        if (empty($code)) {
            return $this->response()->fail('参数错误');
        }

        $res = (new DingService())->getUserInfoByCode($code);
//        file_put_contents('./ssdfsd.json', json_encode($res, JSON_UNESCAPED_UNICODE));
//        $res = json_decode('{"nick":"杨波","unionId":"L3Y0sZD9qStvM2gGIMgO2wiEiE","avatarUrl":"https:\/\/static-legacy.dingtalk.com\/media\/lADPDiCpz1S8nmbNAoDNAjI_562_640.jpg","openId":"qzkcrbiimjSStlqVRhS8BMgiEiE","mobile":"17612000371","stateCode":"86","userid":"1947155628848506"}', true);

        $domain = $request->getSchemeAndHttpHost();

        // 创建用户
        if(!($model = Employee::query()->where('dingtalk_id',$res['userid'])->first())){
            return $this->response()->fail('参数错误');
        }
        /**
         * 判断是否有管理员用户 如果没有自动创建
         */
        if ((!($adminUser = AdminUser::query()->where(['username'=>$model->getAttribute('mobile')])->first()))) {
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
        }

        // 判断是否有角色
        if (!DB::table('admin_role_users')->where('user_id', $adminUser->getAttribute('id'))->first()) {
            $content = "<script>
alert('暂无权限登录')
window.location.href='{$domain}/admin#/login?redirect=/dashboard'

</script>";

            echo $content;
            return false;
        }


        // 生成token
        $user = Admin::adminUserModel()::query()->where('username', $model->getAttribute('mobile'))->first();
        if (!$user->enabled) {
            return $this->response()->fail(admin_trans('admin.user_disabled'));
        }

        $module = Admin::currentModule(true);
        $prefix = $module ? $module . '.' : '';
        $token  = $user->createToken($prefix . 'admin')->plainTextToken;

        $adminUserName = $model->getAttribute('mobile');
        // 重定向到首页

        $content = "<script>

localStorage.setItem('admin-api-token', '{$token}');
localStorage.setItem('admin-api-user_name', '{$adminUserName}');

window.location.href='{$domain}/admin#/dashboard'

</script>";

        echo $content;
    }


}
