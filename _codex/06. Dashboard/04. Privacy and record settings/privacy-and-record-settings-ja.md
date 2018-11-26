---
layout: page
language: ja
category: codex
section: Dashboard
title: プライバシーと記録の設定
---

<!--more-->

### IPアドレスを匿名化する ###

[GDPR][GDPR] では、IPアドレスは[個人情報][PII]と見做されます。このオプションを
有効にすると、単体では個人が識別できないよう、記録時に IP アドレスの末尾を 
`***` でマスクします。

### 外部APIへの送信を制限する ###

データ管理者が EU 経済圏から圏外のデータ・プロセッサに個人情報を移動させるには、
両者の間で Data Processing Agreement が必要です。このオプションを有効にすると、
取得した IP アドレスを外部の位置情報 API へ送信しません。

### 検証の統計を記録 ###

**全遮断数**、**国別遮断数**、**1日あたりの遮断数**、**遮断したIPアドレスの
タイプ**、**API毎の平均応答時間**などの統計データを記録し、「**統計**」画面に
表示します。

![検証の統計]({{ '/img/2018-09/ValidationStat.png' | prepend: site.baseurl }}
 "検証の統計"
)

### IPアドレスをキャッシュに記録 ###

このプラグインでは、国コード、ホスト名、ログイン失敗回数などを IP アドレスに
紐付けて検証を実行します。このオプションを有効にすると、これらを一定期間、
キャッシュに保持し、国コードやホスト名の重複検索を避け、サーバー負荷を減らすと
共に高速に動作させることができます。

![キャッシュの統計]({{ '/img/2018-09/IPAddressCache.png' | prepend: site.baseurl }}
 "キャッシュの統計"
)

- **各エントリーの有効期間 ［秒］**  
キャッシュを保持する時間を秒単位で指定します。デフォルトは3600秒（1時間）
です。  
  
  ログイン失敗回数が「[**IPアドレス当たりのログイン試行可能回数**][LoginFail]」
  を超えた場合、この期間だけログイン・フォームへのアクセスを遮断します。
  この制限を解除するには、IP アドレスを選択し、「**選択して実行**」から
  「**指定のIPアドレスでエントリを削除**」を実行してください。
  
  ![指定のIPアドレスでエントリを削除]({{ '/img/2018-09/LoginFailure.png' | prepend: site.baseurl }}
   "指定のIPアドレスでエントリを削除"
  )

### 検証のログを記録 ###

このオプションを有効にすると、「ログ」画面で検証結果の履歴を閲覧できるように
なります。

![検証のログ]({{ '/img/2018-09/ValidationLogs.png' | prepend: site.baseurl }}
 "検証のログ"
)

- **各エントリーの有効期間［日］**  
ログ中の各エントリーは、このオプションで指定される期間を過ぎるか、
最大エントリー数（デフォルトで500）を超えると自動的に削除されます。

- **内容を展開する$_POSTのキー**  
HTTP メソッド `POST` でリクエストされた場合、指定したキーに対応する
メッセージ・ボディ中のデータを展開し、安全な形で記録します。以下の例は、
キーに `log` と `pwd` を指定した場合に、`wp-login.php` 宛にリクエスト
されたログイン名とパスワードが記録された様子を示しています。

![$_POST data]({{ '/img/2018-09/PostData.png' | prepend: site.baseurl }}
 "$_POST data"
)

### ガベージコレクション周期［秒］ ###

このオプションで、**IPアドレスのキャッシュ** 及び **検証のログ** 中の有効期限の
過ぎたエントリーを削除する周期を指定します。デフォルトは900秒（15分）です。

### アンインストール時に設定と記録を全て削除 ###

アンインストール時に、このプラグインの設定はもちろん、 記録された IP アドレスを
含む全データをデータベースから削除します。

### 参考情報 ###

- [The best practice of target settings][BestPractice]
- [Prevent exposure of wp-config.php][ExposeWPConf]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[LoginFail]:    {{ '/codex/validation-rule-settings-ja.html#ipアドレス当たりのログイン試行可能回数' | prepend: site.baseurl }} "検証ルールの設定 | IP Geo Block"
[BestPractice]: {{ '/codex/the-best-practice-for-target-settings.html' | prepend: site.baseurl }} "The best practice of target settings | IP Geo Block"
[ExposeWPConf]: {{ '/article/exposure-of-wp-config-php.html'           | prepend: site.baseurl }} "Prevent exposure of wp-config.php | IP Geo Block"
[GDPR]:         https://en.wikipedia.org/wiki/General_Data_Protection_Regulation "General Data Protection Regulation - Wikipedia"
[PII]:          https://en.wikipedia.org/wiki/Personally_identifiable_information "Personally identifiable information - Wikipedia"
