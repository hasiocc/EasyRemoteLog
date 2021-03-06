<?php

namespace HCC\Logger {

    class RemoteLogEntries
    {

        //一個或多個系統無法使用
        const EMERGENCY = 800;

        //一個人必須立即採取行動
        const ALERT = 700;

        //關鍵事件會導致更嚴重的問題或中斷
        const CRITICAL = 600;

        //錯誤事件可能會導致問題
        const ERROR = 500;

        //警告事件可能會導致問題
        const WARNING = 400;

        //正常但重要的事件，例如啟動、關閉或配置更改
        const NOTICE = 300;

        //常規信息，例如正在進行的狀態或性能。
        const INFO = 200;

        //DEBUG (100) 調試或跟踪信息。
        const DEBUG = 100;

        //預設Log等級
        const DEFAULT_LEVEL = 0;

        private $log = [];

        private $labels = [];

        private $hostTag = "";

        private $enable_debug_level = true;

        /**
         * @param array $labels 設定全域的log標籤
         * @example $this->setGlobalLabels([["key1":"value2"],["key1":"value2"]])
         */
        public function setGlobalLabels($labels)
        {
            $this->labels = $labels;
        }

        /**
         * 回傳全域設定的Log標籤
         * @return array
         * @example
         *          回傳格式:
         *          [
         *             ["key1":"value2"],
         *             ["key1":"value2"]
         *          ]
         *
         */
        public function getGlobalLabels()
        {
            return $this->labels;
        }


        /**
         * 指定全域主機標籤
         * @param $hostTag
         */
        public function setGlobalLaHost($hostTag)
        {
            $this->hostTag = $hostTag;
        }

        /**
         * 回傳全域主機標籤
         * @return string
         */
        public function getGlobalLaHost()
        {
            return $this->hostTag;
        }

        /**
         * 會傳是否紀錄DEBUG LEVEL 等級的LOG
         * @return bool
         */
        public function getEnableDebugLevel()
        {
            return $this->enable_debug_level;
        }

        /**
         * 設定是否紀錄DEBUG LEVEL 等級的LOG
         * @param $value
         */
        public function setEnableDebugLevel($value)
        {
            $this->enable_debug_level = $value;
        }

        /**
         * @param string|array $data log的訊息
         * @param string $level log的訊息等級
         * @param array $labels log的標籤(非必須),若有設定會以此設定為主
         * @param array $operation log標記的唯一操作來源，如果ID為同一組視為同一項操作
         * @param array $sourceLocation Log的來源程式碼標記(非必須)
         * @param array $httpRequest 假設來源是http協議的請求，可在這邊標記請求的協議類型
         */
        public function addLog(
            $data,
            $level = null,
            $labels = null,
            $operation = null,
            $sourceLocation = null,
            $httpRequest = null
        ) {

            if ($this->enable_debug_level === false) {
                if ($level == self::DEBUG) {
                    return;
                }
            }

            $log = [];

            $log['message']['time'] = date(DATE_RFC3339);

            if (is_string($data)) {
                $log['message'] = ['message' => $data];
            } else {
                $log['message'] = $data;
            }

            if (empty($level)) {
                $log['options']['severity'] = self::DEFAULT_LEVEL;
            } else {
                $log['options']['severity'] = $level;
            }

            if (is_array($labels) && count($labels) > 0) {
                $log['options']['labels'] = $labels;
            } else {
                if (is_array($this->labels) && count($this->labels) > 0) {
                    $log['options']['labels'] = $this->labels;
                }
            }

            //如果有指定全域主機標籤，則把label裡的host替換掉，如果沒有就建立
            if (!empty($this->hostTag)) {
                $log['options']['labels']['host_tag'] = $this->hostTag;
            }

            if (is_array($operation) && count($operation) > 0) {
                $log['options']['operation'] = $operation;
            }

            if (is_array($sourceLocation) && count($sourceLocation) > 0) {
                $log['options']['sourceLocation'] = $sourceLocation;
            }

            if (is_array($httpRequest) && count($httpRequest) > 0) {
                $log['options']['httpRequest'] = $httpRequest;
            }

            $this->log[] = $log;
        }

        /**
         * 傳回log Entries
         * @return array
         */
        public function getEntries()
        {
            return $this->log;
        }

        /**
         * 清除Log緩存
         */
        public function clear()
        {
            $this->log = [];
        }


    }
}
