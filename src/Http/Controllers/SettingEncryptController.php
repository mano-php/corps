<?php

namespace ManoCode\Corp\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use ManoCode\Corp\CorpsServiceProvider;
use Slowlyo\OwlAdmin\Admin;
use Slowlyo\OwlAdmin\Renderers\Page;
use Slowlyo\OwlAdmin\Renderers\Form;
use Slowlyo\OwlAdmin\Controllers\AdminController;
use Slowlyo\OwlAdmin\Renderers\TreeControl;
use Slowlyo\OwlAdmin\Renderers\Wrapper;

class SettingEncryptController extends AdminController
{
    public function settingEncrypt(Request $request)
    {
        $params = $request->post();
        foreach ($params as $key => $item) {
            if (in_array($key, ['corp_id', 'client_id', 'client_secret', 'agent_id', 'aes_key', 'token'])) {
                $params[$key] = Crypt::encryptString($item);
            }
        }
        Admin::extension($request->input('extension'))->saveConfig($params);
        return $this->response()->successMessage(admin_trans('admin.save_success'));
    }

    public function settingDecrypt(Request $request, Response $response)
    {
        $config = CorpsServiceProvider::setting();
        if($config){
            foreach ($config as $key => $item) {
                if (in_array($key, ['corp_id', 'client_id', 'client_secret', 'agent_id', 'aes_key', 'token'])) {
                    try{
                        $config[$key] = Crypt::decryptString($item);
                    }catch (\Throwable $throwable){
                        // 异常时 说明配置项是明文或者 env app_key 发生了变化
                    }
                }
            }
        }
        return $this->response()->success($config);
    }

}