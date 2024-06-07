<?php

if (!function_exists('admin_corp_amis')) {
    /**
     * 数据字典
     *
     * @return \ManoCode\Corp\AdminCorpAmis
     */
    function admin_corp_amis()
    {
        return app('admin.corp.amis');
    }
}
