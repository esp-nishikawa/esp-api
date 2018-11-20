# esp-api
***

## 配置

- そのまま配置する
```
/
│
├ api_logs ... ログフォルダ（733）
│
├ httpdocs
│　│
│　├ api
│　│　├ common.php
│　│　├ contact.php
│　│　└ ～
│　│
│　└ ～
│
└ ～
```

## 実行

- POST /api/contact.php HTTP/1.1
```
{"type":1,"name":"名前","affiliation":"所属","email":"mail@domain.com","phone":"0123456789","contents":"問い合わせ内容","privacy":true}
```
