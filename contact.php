<?php
include dirname(__FILE__) . '/common.php';
$domain = 'esoftpowers.com';

// リクエストパラメータ
$request_body = file_get_contents('php://input');
$request_json = str_replace(array("\n","\r"), "", $request_body); 
$request_json = preg_replace('/([{,]+)(\s*)([^"]+?)\s*:/', '$1"$3":', $request_json);
$request_json = preg_replace('/(,)\s*}$/','}', $request_json);
$request_params = json_decode($request_json, true);
if (json_last_error() != JSON_ERROR_NONE) {
    output_log("JSONデコードエラー"."\n■message\n".json_last_error_msg(), $request_body);
    exit_error(400);
}

// リファラチェック
if (!check_referer($domain)) {
    output_log("リファラチェックエラー"."\n■referer\n".$_SERVER['HTTP_REFERER'], $request_body);
    exit_error(403);
}

// リクエストパラメータのチェック
$request_params += array(
    'type' => '',
    'name' => '',
    'affiliation' => '',
    'email' => '',
    'contents' => '',
);

// お問い合わせ種類
$types = array(
    1 => '採用についてのお問い合わせ',
    2 => 'お仕事のご依頼やご相談',
    3 => 'パートナーシップに関するお問い合わせ',
    4 => 'その他'
);
$type = $request_params['type'];
if (!isset($types[$type])) {
    output_log("パラメータチェックエラー"."\n■type\n".$type, $request_body);
    exit_error(400);
}

// お名前
$name = $request_params['name'];
if ('' == strval($name) || 60 < mb_strlen($name)) {
    output_log("パラメータチェックエラー"."\n■name\n".$name, $request_body);
    exit_error(400);
}

// ご所属
$affiliation = $request_params['affiliation'];
if (60 < mb_strlen($affiliation)) {
    output_log("パラメータチェックエラー"."\n■affiliation\n".$affiliation, $request_body);
    exit_error(400);
}

// メールアドレス
$email = $request_params['email'];
if ('' == strval($email) || 256 < strlen($email) || !check_mail($email)) {
    output_log("パラメータチェックエラー"."\n■email\n".$email, $request_body);
    exit_error(400);
}

// お問い合わせ内容
$contents = $request_params['contents'];
if ('' == strval($contents) || 2000 < mb_strlen($contents)) {
    output_log("パラメータチェックエラー"."\n■contents\n".$contents, $request_body);
    exit_error(400);
}

// 管理者アドレス
$mail_admin = $type == 1 ? 'saiyou@'.$domain : 'info@'.$domain;

// メール本文
$mail_body = "ホームページよりお問い合わせがありました。\n";
$mail_body .= "\n------------------------------------------------------";
$mail_body .= "\n[お問い合わせ種類]"."\n".$types[$type]."\n";
$mail_body .= "\n[お名前]"."\n".$name."\n";
$mail_body .= "\n[ご所属]"."\n".$affiliation."\n";
$mail_body .= "\n[メールアドレス]"."\n".$email."\n";
$mail_body .= "\n[お問い合わせ内容]"."\n".$contents."\n";
$mail_body .= "------------------------------------------------------\n";
$mail_body .= "\n".date('Y/m/d (D) H:i:s');
$mail_body .= "\n";

// メール送信
if (!send_mail($email, $mail_admin, $mail_admin, 'ホームページよりお問い合わせ', $mail_body)) {
    output_log("メール送信エラー"."\n■from\n".$email."\n■to\n".$mail_admin."\n■body\n".$mail_body, $request_body);
    exit_error(500);
}

// 自動返信メール本文
$remail_body = $name." 様\n\n";
$remail_body .= "お問い合わせいただき誠にありがとうございます。\n";
$remail_body .= "下記のとおりお問い合わせを受け付けました。\n";
$remail_body .= "\n------------------------------------------------------";
$remail_body .= "\n[お問い合わせ種類]"."\n".$types[$type]."\n";
$remail_body .= "\n[お名前]"."\n".$name."\n";
$remail_body .= "\n[ご所属]"."\n".$affiliation."\n";
$remail_body .= "\n[メールアドレス]"."\n".$email."\n";
$remail_body .= "\n[お問い合わせ内容]"."\n".$contents."\n";
$remail_body .= "------------------------------------------------------\n";
$remail_body .= "\n確認後、返信させていただきます。少々お待ちください。\n";
$remail_body .= "\n================================";
$remail_body .= "\n株式会社イーソフトパワーズ";
$remail_body .= "\nTEL: 03-6273-4837";
$remail_body .= "\nEmail: ".$mail_admin;
$remail_body .= "\nWeb: http://esoftpowers.com";
$remail_body .= "\n================================";
$remail_body .= "\n";

// 自動返信メール送信
send_mail($mail_admin, $email, $mail_admin, 'お問い合わせありがとうございます', $remail_body);

// 正常終了
$response_body = json_encode(array('result_code' => 'success'));
exit_ok($response_body);
