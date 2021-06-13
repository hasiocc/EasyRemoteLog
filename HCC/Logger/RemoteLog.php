<?php
/**
 * 遠程LOG收集器
 * @author HasioCC
 */

namespace HCC\Logger {


    require 'RemoteLogEntries.php';

    use Exception;
    use Firebase\JWT\JWT;

    class RemoteLog
    {

        private $auth_key = "";
        private $server_host = "";
        private $connect_server_timeout = 5; //logServer 連線超過幾秒就強制執行中斷(sec)
        private $send_server_time_out = 0; //傳送log至Server 等待時間最多秒數(sec)
        private $hostTag = ""; //主機來源標示，如果參數有設定，則logfileName的檔名格式為{hostTag}-logfileName

        function __construct($config = [])
        {
            if (isset($config["server_host"])) {
                $this->server_host = $config["server_host"];
            }

            if (isset($config["auth_key"])) {
                $this->auth_key = $config["auth_key"];
            }

            if (isset($config["connect_server_timeout"])) {
                $this->connect_server_timeout = $config["connect_server_timeout"];
            }

            if (isset($config["send_server_time_out"])) {
                $this->connect_server_timeout = $config["send_server_time_out"];
            }

            if (isset($config["hostTag"])) {
                $this->hostTag = $config["hostTag"];
            }
        }

        /**
         * 設定遠端LogServer來源(會強制覆蓋建構子進來的設定)
         * @param string $server_host 設定遠端LogServer來源
         */
        public function setRemoteServer($server_host)
        {
            $this->server_host = $server_host;
        }

        /**
         * 回傳遠端LogServer來源
         * @return mixed|string
         */
        public function getRemoteServer()
        {
            return $this->server_host;
        }

        /**
         * @param string $auth_key 設定發送到遠程logServer金鑰(會強制覆蓋建構子進來的設定)
         */
        public function setAuthServerKey($auth_key)
        {
            $this->auth_key = $auth_key;
        }

        /**
         * 回傳發送到遠程logServer金鑰
         * @return mixed|string
         */
        public function getAuthServerKey()
        {
            return $this->auth_key;
        }

        /**
         * 回傳可以允許連到LogServer 最長執行秒數
         * @return int|mixed
         */
        public function getConnectServerTimeOut()
        {
            return $this->connect_server_timeout;
        }

        /**
         * @param int $sec 設定可以允許連到LogServer 最長執行秒數(會強制覆蓋建構子進來的設定)
         */
        public function setConnectServerTimeOut($sec)
        {
            $this->connect_server_timeout = $sec;
        }

        /**
         * 傳送log至Server 等待時間最多秒數
         * @return int
         */
        public function getSendServerTimeOut()
        {
            return $this->send_server_time_out;
        }

        /**
         * @param int 傳送log至Server 等待時間最多秒數(會強制覆蓋建構子進來的設定)
         */
        public function setSendServerTimeOut($sec)
        {
            $this->send_server_time_out = $sec;
        }

        /**
         * 回傳主機來源標籤
         * @return mixed|string
         */
        public function getHostTag()
        {
            return $this->hostTag;
        }

        /**
         * 主機來源標籤，如果有標示則保存的logfileName格式為{hostTag}-logfileName(預設會抓建構子進來的設定參數資料)
         * @param string $hostTag
         */
        public function setHostTag($hostTag)
        {
            $this->hostTag = $hostTag;
        }

        /**
         * @param string $logfileName 要寫入到日誌檔的名稱
         * @param RemoteLogEntries $entries Log entries 內容
         * @param bool $in_background 是否子線程發送log(在環境允許的情況下)
         * @return bool
         */
        public function writeLog($logfileName, RemoteLogEntries $entries, $in_background = true)
        {

            $write_info['status'] = false;
            $write_info['message'] = "";

            //抓不到設定值就不執行了
            if (empty($this->server_host) || empty($this->auth_key)) {
                $write_info['message'] = "缺少必要server_host和auth_key參數";
                return $write_info;
            }

            $log['logName'] = empty($this->hostTag) ? $logfileName : "{$this->hostTag}-{$logfileName}";
            $log['entries'] = $entries->getEntries();

            $jwtToken = $this->creat_jwt_token($logfileName);

            ignore_user_abort(true);
            set_time_limit(0);

            if ($in_background === true && function_exists('pcntl_fork')) {
                $write_info = $this->backgroundPostCurl($log, $jwtToken);
            } else {
                $write_info = $this->postCurl($log, $jwtToken);
            }
            $entries->clear();
            return $write_info;
        }

        /**
         * 產生要發送到LogServer 的 Token
         * @param string $logfileName log檔名
         * @return string
         */
        private function creat_jwt_token($logfileName)
        {
            $time = time();
            $payload = array(
                "iat" => $time,
                "exp" => $time + (60 * 5),
                "logEvent" => $logfileName,
            );
            $jwt = JWT::encode($payload, $this->auth_key);
            return $jwt;
        }

        private function backgroundPostCurl($data, $jwt_token)
        {
            $write_info['status'] = false;
            $write_info['message'] = "";

            $pid = pcntl_fork();
            if ($pid == -1) {
                $write_info['status'] = false;
                $write_info['message'] = "create thread error";
                return $write_info;
            } else {
                if ($pid) {
                    pcntl_wait($status, WNOHANG);
                } else {
                    try {
                        $this->postCurl($data, $jwt_token);
                    } finally {
                        posix_kill(posix_getpid(), SIGTERM);
                    }
                }
            }

            $write_info['status'] = true;
            $write_info['message'] = "";

            return $write_info;
        }

        private function postCurl($data, $jwt_token)
        {
            $output['status'] = false;
            $output['message'] = "";

            $ch = curl_init($this->server_host);
            try {
                $data_string = json_encode($data);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connect_server_timeout);
                curl_setopt($ch, CURLOPT_TIMEOUT, $this->send_server_time_out);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Content-Type: application/json',
                        'Content-Length: ' . strlen($data_string),
                        'Authorization:Bearer ' . $jwt_token,
                    )
                );

                $result = curl_exec($ch);
                $result = json_decode($result, true);

                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                if ($http_code == 200 && count($result) == 0) {
                    $output['status'] = true;
                } else {
                    if (isset($result['message'])) {
                        $output['message'] = $result['message'];
                    }
                }
            } catch (Exception $e) {
                $output['status'] = false;
                $output['message'] = $e->getMessage();
            } finally {
                curl_close($ch);
            }
            return $output;
        }
    }
}
