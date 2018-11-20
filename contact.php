<?php
include dirname(__FILE__) . '/common.php';
$domain = 'esoftpowers.com';

// ドメインチェック
if (!check_domain($domain)) {
    output_log("ドメインチェックエラー"."\n■origin\n".$_SERVER['HTTP_ORIGIN']."\n■referer\n".$_SERVER['HTTP_REFERER'], $request_body);
    exit_error(403);
}

// メソッドチェック
if (!check_method('POST')) {
    output_log("メソッドチェックエラー"."\n■method\n".$_SERVER['REQUEST_METHOD'], $request_body);
    exit_error(403);
}

// リクエストパラメータ
$request_body = file_get_contents('php://input');
$request_params = decode_json_request($request_body);
if (json_last_error() != JSON_ERROR_NONE) {
    output_log("JSONデコードエラー"."\n■message\n".json_last_error_msg(), $request_body);
    exit_error(400);
}

// リクエストパラメータのチェック
$request_params += array(
    'type' => '',
    'name' => '',
    'affiliation' => '',
    'email' => '',
    'phone' => '',
    'contents' => '',
    'privacy' => false,
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

// 電話番号
$phone = $request_params['phone'];
if (25 < mb_strlen($phone)) {
    output_log("パラメータチェックエラー"."\n■phone\n".$phone, $request_body);
    exit_error(400);
}

// お問い合わせ内容
$contents = $request_params['contents'];
if ('' == strval($contents) || 2000 < mb_strlen($contents)) {
    output_log("パラメータチェックエラー"."\n■contents\n".$contents, $request_body);
    exit_error(400);
}

// 個人情報の取り扱いについて
$privacy = $request_params['privacy'];
if (!is_bool($privacy) || !$privacy) {
    output_log("パラメータチェックエラー"."\n■privacy\n".$privacy, $request_body);
    exit_error(400);
}

// 管理者アドレス
$mail_admin = $type == 1 ? 'saiyou@'.$domain : 'info@'.$domain;

// メール件名
$mail_subject = '【お問い合わせ】'.$types[$type];

// メール本文
$mail_body = "ホームページよりお問い合わせがありました。\n";
$mail_body .= "\n------------------------------------------------------";
$mail_body .= "\n[お問い合わせ種類]"."\n".$types[$type]."\n";
$mail_body .= "\n[お名前]"."\n".$name."\n";
$mail_body .= "\n[ご所属]"."\n".$affiliation."\n";
$mail_body .= "\n[メールアドレス]"."\n".$email."\n";
$mail_body .= "\n[電話番号]"."\n".$phone."\n";
$mail_body .= "\n[お問い合わせ内容]"."\n".$contents."\n";
$mail_body .= "------------------------------------------------------\n";
$mail_body .= "\n".date('Y/m/d (D) H:i:s');
$mail_body .= "\n";

// メール送信
if (!send_mail($email, $mail_admin, $mail_admin, $mail_subject, $mail_body)) {
    output_log("メール送信エラー"."\n■from\n".$email."\n■to\n".$mail_admin, $request_body);
    exit_error(500);
}

// 自動返信メール件名
$remail_subject = '【eSoftPowers】お問い合わせ受付のお知らせ';

// 自動返信メール本文
$remail_body = $name." 様\n\n";
$remail_body .= "お問い合わせいただき誠にありがとうございます。\n";
$remail_body .= "下記のとおりお問い合わせを受け付けました。\n";
$remail_body .= "\n------------------------------------------------------";
$remail_body .= "\n[お問い合わせ日時]"."\n".date('Y/m/d (D) H:i:s')."\n";
$remail_body .= "\n[お問い合わせ種類]"."\n".$types[$type]."\n";
$remail_body .= "\n[お問い合わせ内容]"."\n".$contents."\n";
$remail_body .= "------------------------------------------------------\n";
$remail_body .= "\n確認後、返信させていただきます。少々お待ちください。\n";
$remail_body .= "\n◇ご注意◇";
$remail_body .= "\nこのメールはメールフォームよりお問い合わせいただいた方へ自動送信しております。";
$remail_body .= "\nお急ぎの場合はお電話にてお問い合わせください。\n";
$remail_body .= "\n================================";
$remail_body .= "\n株式会社イーソフトパワーズ";
$remail_body .= "\nTEL: 03-6273-4837";
$remail_body .= "\nEmail: ".$mail_admin;
$remail_body .= "\nWeb: http://esoftpowers.com";
$remail_body .= "\n================================";
$remail_body .= "\n";

// 自動返信メール送信
send_mail($mail_admin, $email, $mail_admin, $remail_subject, $remail_body);

// 正常終了
$response_body = json_encode(array('result_code' => 'success'));
exit_ok($response_body);
