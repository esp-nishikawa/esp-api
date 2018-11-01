<?php
date_default_timezone_set('Asia/Tokyo');
mb_language('Japanese');
mb_internal_encoding('UTF-8');

/**
 * PHP 5.4.0
 */
if (!function_exists('http_response_code')) {
    function http_response_code($code = NULL) {
        $prev_code = (isset($GLOBALS['http_response_code']) ? $GLOBALS['http_response_code'] : 200);

        if ($code === NULL) {
            return $prev_code;
        }

        switch ($code) {
            case 100: $text = 'Continue'; break;
            case 101: $text = 'Switching Protocols'; break;
            case 200: $text = 'OK'; break;
            case 201: $text = 'Created'; break;
            case 202: $text = 'Accepted'; break;
            case 203: $text = 'Non-Authoritative Information'; break;
            case 204: $text = 'No Content'; break;
            case 205: $text = 'Reset Content'; break;
            case 206: $text = 'Partial Content'; break;
            case 300: $text = 'Multiple Choices'; break;
            case 301: $text = 'Moved Permanently'; break;
            case 302: $text = 'Moved Temporarily'; break;
            case 303: $text = 'See Other'; break;
            case 304: $text = 'Not Modified'; break;
            case 305: $text = 'Use Proxy'; break;
            case 400: $text = 'Bad Request'; break;
            case 401: $text = 'Unauthorized'; break;
            case 402: $text = 'Payment Required'; break;
            case 403: $text = 'Forbidden'; break;
            case 404: $text = 'Not Found'; break;
            case 405: $text = 'Method Not Allowed'; break;
            case 406: $text = 'Not Acceptable'; break;
            case 407: $text = 'Proxy Authentication Required'; break;
            case 408: $text = 'Request Time-out'; break;
            case 409: $text = 'Conflict'; break;
            case 410: $text = 'Gone'; break;
            case 411: $text = 'Length Required'; break;
            case 412: $text = 'Precondition Failed'; break;
            case 413: $text = 'Request Entity Too Large'; break;
            case 414: $text = 'Request-URI Too Large'; break;
            case 415: $text = 'Unsupported Media Type'; break;
            case 500: $text = 'Internal Server Error'; break;
            case 501: $text = 'Not Implemented'; break;
            case 502: $text = 'Bad Gateway'; break;
            case 503: $text = 'Service Unavailable'; break;
            case 504: $text = 'Gateway Time-out'; break;
            case 505: $text = 'HTTP Version not supported'; break;
            default:
                trigger_error('Unknown http status code ' . $code, E_USER_ERROR); // exit('Unknown http status code "' . htmlentities($code) . '"');
                return $prev_code;
        }

        $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
        header($protocol . ' ' . $code . ' ' . $text);
        $GLOBALS['http_response_code'] = $code;

        // original function always returns the previous or current code
        return $prev_code;
    }
}

/**
 * PHP 5.5.0
 */
if (!function_exists('json_last_error_msg')) {
    function json_last_error_msg() {
        static $ERRORS = array(
            JSON_ERROR_NONE => 'No error',
            JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH => 'State mismatch (invalid or malformed JSON)',
            JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
            JSON_ERROR_SYNTAX => 'Syntax error',
            JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded'
        );

        $error = json_last_error();
        return isset($ERRORS[$error]) ? $ERRORS[$error] : 'Unknown error';
    }
}

/**
 * シャットダウン時に実行する関数を登録
 */
register_shutdown_function(
    function() {
        $e = error_get_last();
        if( $e['type'] == E_ERROR ||
            $e['type'] == E_PARSE ||
            $e['type'] == E_CORE_ERROR ||
            $e['type'] == E_COMPILE_ERROR ||
            $e['type'] == E_USER_ERROR ) {
            $message = "予期しないエラー";
            $message .= "\n■type\n".$e['type'];
            $message .= "\n■message\n".$e['message'];
            $message .= "\n■file\n".$e['file'];
            $message .= "\n■line\n".$e['line'];
            output_log($message, file_get_contents('php://input'));
        }
    }
);

/**
 * ログ出力
 */
function output_log($message, $request = NULL) {
    $contents = date('Y-m-d H:i:s');
    $contents .= " ".@$_SERVER['REMOTE_ADDR'];
    $contents .= " ".getHostByAddr(getenv('REMOTE_ADDR'));
    $contents .= " ".$message;
    if ($request) {
        $contents .= "\n■request\n".$request."\n";
    }
    $contents .= "\n";
    file_put_contents(dirname(__FILE__)."/logs/api_log.".date('Ymd'), $contents, FILE_APPEND | LOCK_EX);
}

/**
 * リファラチェック
 */
function check_referer($domain){
    if (strpos($_SERVER['HTTP_REFERER'], $domain) === false) {
        return false;
    }
    return true;
}

/**
 * メールアドレスチェック
 */
function check_mail($email){
    if (preg_match("/^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/", $email)) {
        return true;
    }
    return false;
}

/**
 * メール送信
 */
function send_mail($from, $to, $admin, $subject, $body) {
    $header = "From: $from\n";
    $header .= "Reply-To: $from";
    return mb_send_mail($to, $subject, $body, $header, '-f'.$admin);
}

/**
 * 正常終了(200)
 */
function exit_ok($response_body) {
    header("Content-Type: application/json; charset=UTF-8");
    exit($response_body);
}

/**
 * 異常終了(200以外)
 */
function exit_error($status_code) {
    http_response_code($status_code);
    exit;
}
