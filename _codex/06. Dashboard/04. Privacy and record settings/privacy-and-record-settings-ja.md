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
有効にすると、単体では個人が識別できないように、記録時に IP アドレスの末尾を 
`***` でマスクします。

### 外部APIへの送信を制限する ###

データ・コントローラが EU 経済圏から圏外のデータ・プロセッサに個人情報を移動
させるには、両者の間で Data Processing Agreement が必要です。このオプションを
有効にすると、取得した IP アドレスを外部の位置情報 API へ送信しません。

### 検証の統計を記録 ###

**全遮断数**、**国別遮断数**、**1日あたりの遮断数**、**遮断したIPアドレスの
タイプ**、**API毎の平均応答時間**などの統計データを記録し、「**統計**」タブに
表示します。

![検証の統計]({{ '/img/2018-09/ValidationStat.png' | prepend: site.baseurl }}
 "検証の統計"
)

### IPアドレスをキャッシュに記録 ###

![キャッシュの統計]({{ '/img/2018-09/IPAddressCache.png' | prepend: site.baseurl }}
 "キャッシュの統計"
)

- **各エントリーの有効期間 ［秒］**  

### 検証のログを記録 ###

![検証のログ]({{ '/img/2018-09/ValidationLogs.png' | prepend: site.baseurl }}
 "検証のログ"
)

- **各エントリーの有効期間［日］**  

- **内容を展開する$_POSTのキー**  

### ガベージコレクション周期［秒］ ###

### アンインストール時に設定と記録を全て削除 ###

### 参考情報 ###

- [The best practice of target settings][BestPractice]
- [Prevent exposure of wp-config.php][ExposeWPConf]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[BestPractice]: {{ '/codex/the-best-practice-for-target-settings.html' | prepend: site.baseurl }} "The best practice of target settings | IP Geo Block"
[ExposeWPConf]: {{ '/article/exposure-of-wp-config-php.html'           | prepend: site.baseurl }} "Prevent exposure of wp-config.php | IP Geo Block"
[GDPR]:         https://en.wikipedia.org/wiki/General_Data_Protection_Regulation "General Data Protection Regulation - Wikipedia"
[PII]:          https://en.wikipedia.org/wiki/Personally_identifiable_information "Personally identifiable information - Wikipedia"
