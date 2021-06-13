<?php

namespace HCC {

    require_once 'Logger' . DIRECTORY_SEPARATOR . 'Logger.php';

    final class Tool
    {

        /**
         * 遠程 Log 收集器
         * @param array $config 設定檔
         * @return Logger\Logger 回傳路徑
         * @example
         *          $config 支援的設定檔參數
         *          [
         *              "auth_key":"key" //遠程伺服器金鑰(*重要提示:金鑰不設定LOG不會傳送)
         *              "server_host ":"Server URL" //遠程LOG伺服器地址(*重要提示:LOG SERVER不設定LOG不會傳送)
         *              "connect_server_timeout":"10" //(單位:秒)連接遠程LOG伺服器時，超時多久就中斷(非必要參數，若不設定預設5秒)
         *              "send_server_time_out":"0" //(單位:秒)Log發送至遠程伺服器，多久沒回應就中斷(非必要參數，若不設定預設不限制)
         *              "hostTag:"host tag" //設定要發送到Log主機上的主機標籤(非必要，不設定傳LOG就不會帶標籤)
         *              "enable_debug_level":true //是否啟用DEBUG LOG(非必要參數，不設定預設啟用)
         *          ]
         *
         */
        public static function Logger($config)
        {
            return new Logger\Logger($config);
        }


    }
}