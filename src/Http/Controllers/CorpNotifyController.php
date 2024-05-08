<?php

namespace ManoCode\Corp\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ManoCode\Corp\CorpsServiceProvider;
use ManoCode\Corp\Events\DingNotify\DefaultEvent;
use ManoCode\Corp\Jobs\TaskSyncUser;
use ManoCode\Corp\Library\DingCrypt;
use ManoCode\Corp\Services\DingService;

class CorpNotifyController
{
    private DingService $dingServices;

    public function __construct(DingService $dingServices)
    {
        $this->dingServices = $dingServices;
    }

    public function notify(Request $request)
    {
        if (CorpsServiceProvider::setting('dingtalk_enabled') == 0) {
            # 未开启
            return 'success';
        }
        $encrypt = $request->json('encrypt', '');
        $signature = $request->query('signature', '');
        $timeStamp = $request->query('timestamp');
        $nonce = $request->query('nonce');
        $client_id = CorpsServiceProvider::setting('client_id');
        $crypt = new DingCrypt(CorpsServiceProvider::setting('token'), CorpsServiceProvider::setting('aes_key'), $client_id);
        $encryData = $crypt->decryptMsg($signature, $timeStamp, $nonce, $encrypt);
        Log::info('钉钉回调原数据，'.serialize($encryData));
        switch ($encryData['EventType'] ?? '') {
            case 'check_url':
                break;
            case 'user_modify_org':
                foreach ($encryData['diffInfo'] as $diffInfo) {
                    TaskSyncUser::dispatch($diffInfo['userid']);
                }
                break;
            default:
                $event = '\\ManoCode\\Corp\\Events\\DingNotify\\' . Str::studly($encryData['EventType']) . 'Event';
                if (class_exists($event)) {
                    event(new $event($encryData));
                }else{
                    # 不存在就抛默认事件，用于兜底
                    DefaultEvent::dispatch($encryData);
                }
//                $file = storage_path('data/def-' . ($encryData['EventType'] ?? '') . '-' . time() . '.json');
//                file_put_contents($file, json_encode($encryData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                break;
        }
        return $crypt->EncryptMsg('success', $client_id);
    }
}
