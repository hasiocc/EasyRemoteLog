<?php

namespace HCC\Logger {

    require_once "RemoteLog.php";
    require_once "RemoteLogEntries.php";

    final class Logger
    {

        private $logServerSetting = [];

        /**
         * @var RemoteLog
         */
        private $RemoteLog = null;

        /**
         * @var RemoteLogEntries
         */
        private $RemoteLogEntries = null;

        function __construct($config)
        {
            $this->logServerSetting = $config;
            $this->RemoteLog = new RemoteLog($this->logServerSetting);
            $this->RemoteLogEntries = new RemoteLogEntries();

            if (isset($config['hostTag']) && !empty($config['hostTag'])) {
                $this->RemoteLogEntries->setGlobalLaHost($config['hostTag']);
            }

            if (isset($config['enable_debug_level']) && is_bool($config['enable_debug_level'])) {
                $this->RemoteLogEntries->setGlobalLaHost($config['hostTag']);
                $this->RemoteLogEntries->setEnableDebugLevel($config['enable_debug_level']);
            }

        }

        /**
         * 傳回RemoteLog物件
         * @return RemoteLog
         */
        public function Log()
        {
            return $this->RemoteLog;
        }

        /**
         * 傳回RemoteLogEntries物件
         * @return RemoteLogEntries
         */
        public function Entries()
        {
            return $this->RemoteLogEntries;
        }

    }
}
