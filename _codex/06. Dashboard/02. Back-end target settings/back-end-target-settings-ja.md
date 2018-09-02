---
layout: page
language: ja
category: codex
section: Dashboard
title: バックエンドの設定
---

WordPress には、サイトに何らかの影響を及ぼす重要なバックエンドへの入口、即ちエンドポイントが
あります。このセクションでは、特に重要なエンドポイントに対するリクエストを検証するルールを設定
します。

<!--more-->

#### コメント投稿 ####

`wp-comments-post.php` へのリクエストを検証します。

#### XML-RPC ####

`xmlrpc.php` へのリクエストを検証します。

プラグイン [Jetpack by WordPress.com][Jetpack] は、米国のサーバーからこのエンドポイント
にアクセスします。このため国コード `US` が 「[**国コードのホワイトリスト**][CountryList] 
に含まれない、またはブラックリストに含まれる場合、WordPress.com との連携が機能しません。

[JetpackサーバーのIPアドレス][JetpackHost]、または [Automattic, Inc][Automattic] の 
AS番号 [AS2635][AS2635] を「[**国コードに優先して検証するIPアドレスのホワイトリスト**]
[IP-Whitelist]」に設定して下さい。

#### ログイン・フォーム ####

`wp-login.php` へのアクセスを検証します。

ユーザー登録や [パスワードで保護されたページ][PassProtect] へのアクセスなど、ログイン・
フォームに指定されるアクション毎に設定することができます。

#### 管理領域 ####

`wp-admin/*.php` へのアクセスを検証します。

この領域へのリクエストは、ログイン・ページへのリダイレクトが発生したり、テーマやプラグインの
脆弱性を突き、重要な情報を盗む・悪意のあるコードを設置するなど、攻撃のエンドポイントになり得
ます。「**ゼロデイ攻撃を遮断**」を有効にすることで、これらの攻撃を防御できます。

#### 管理領域 ajax/post ####

特に `wp-admin/admin-ajax.php` と `wp-admin/admin-post.php` へのリクエストを検証
します。

これらのエンドポイントは、WordPress 標準のインターフェースとして、テーマやプラグインが固有の
処理を行うために使われますが、関連する脆弱性も多数見つかっています。
「**ゼロデイ攻撃を遮断**」は、これらの脆弱性を攻撃対象とするリクエストを遮断するができます。

「**ゼロデイ攻撃を遮断**」の有効時、テーマやプラグインの作り方によっては意図しない遮断が発生
することがあります。このような場合、「**例外**」から該当するアクション／ページを選択して下さい。
虫眼鏡ボタン <span class="emoji">
![ログから遮断されたリクエストを検索する]({{ '/img/2018-01/find.png' | prepend: site.baseurl }}
 "ログから遮断されたリクエストを検索する")
</span> を使うと、遮断されたリクエストを素早く特定できます。警告ボタン 
<span class="emoji">
![ログから対象を抽出し、検証する]({{ '/img/2018-01/alert.png' | prepend: site.baseurl }}
 "ログから対象を抽出し、検証する")
</span> でログに飛ぶことができるので、攻撃ではない事を確認した後、例外として指定して下さい。

特に鍵アイコン <span class="emoji">
![Unlock icon]({{ '/img/2017-08/lock.png' | prepend: site.baseurl }})
</span> だけが付いたアクションは管理者専用ですので、指定の際は注意が必要です。

![Find blocked request button]({{ '/img/2018-01/FindLogsButton.png' | prepend: site.baseurl }}
 "Find blocked request button"
)

#### プラグイン領域 ####

`wp-content/plugins/⋯/*.php` へのリクエストを遮断します。

プラグインの中には、直接プラグイン直下のPHPを呼び出す様にプログラムされている場合があります。
[TimThumb][TimThumb] や [脆弱性のあるプラグインやテーマ][ExposeWPConf] に対する攻撃を
遮断する「**ゼロデイ攻撃を遮断**」が選択可能です。

また WordPress とは無関係に実行できるようプログラムされたプラグインもあり、本プラグインの検証
が実行されません。このような場合に備え、「**WPコアの読み込みを強制**」が指定可能です。

「**例外**」は「**管理領域 ajax/post**」とほぼ同様ですが、プラグイン単位で指定します。

#### テーマ領域 ####

`wp-content/themes/⋯/*.php` へのリクエストを遮断します。

「**WPコアの読み込みを強制**」と「**例外**」は、「**プラグイン領域**」と同様です。

### See also ###

- [The best practice of target settings][BestPractice]
- [Prevent exposure of wp-config.php][ExposeWPConf]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[BestPractice]: {{ '/codex/the-best-practice-for-target-settings.html' | prepend: site.baseurl }} "The best practice of target settings | IP Geo Block"
[TimThumb]:     https://blog.sucuri.net/2014/06/timthumb-webshot-code-execution-exploit-0-day.html "TimThumb WebShot Code Execution Exploit (Zeroday)"
[ExposeWPConf]: {{ '/article/exposure-of-wp-config-php.html'           | prepend: site.baseurl }} "Prevent exposure of wp-config.php | IP Geo Block"
[Jetpack]:      https://wordpress.org/plugins/jetpack/ "Jetpack by WordPress.com &#124; WordPress.org"
[CountryList]:  {{ '/codex/validation-rule-settings-ja.html#国コードのホワイトリストブラックリスト' | prepend: site.baseurl }} "検証ルールの設定 | IP Geo Block"
[IP-Whitelist]: {{ '/codex/validation-rule-settings-ja.html#国コードに優先して検証するipアドレスのホワイトリストブラックリスト' | prepend: site.baseurl }} "検証ルールの設定 | IP Geo Block"
[JetpackHost]:  https://github.com/Automattic/jetpack/issues/1719 "Automattic IP Ranges: offer IP list via API endpoint. - Issue #1719 - Automattic/jetpack"
[Automattic]:   https://automattic.com/ "Automattic"
[AS2635]:       https://ipinfo.io/AS2635 "AS2635 Automattic, Inc - ipinfo.io"
[PassProtect]:  https://codex.wordpress.org/Using_Password_Protection "Using Password Protection &laquo; WordPress Codex"
