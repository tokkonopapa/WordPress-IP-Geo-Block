---
layout: page
language: ja
category: codex
section: Dashboard
title: バックエンドの設定
---

WordPress には、サイトに何らかの影響を及ぼす重要なバックエンドへの入口、即ち
エンドポイントがあります。このセクションでは、特に重要なエンドポイントに対する
リクエストを検証するルールを設定します。

<!--more-->

#### コメント投稿 ####

`wp-comments-post.php` へのリクエストを検証します。

- **投稿フォーム上のメッセージ**  
テンプレートのアクション・フック [`comment_form`][HookComment] または
[`comment_form_top`][HookCommTop] をトリガーに、投稿フォーム上に出力する
メッセージを設定します。以下のタグが使用可能です：`<a>`, `<abbr>`, `<acronym>`,
`<b>`, `<cite>`, `<code>`, `<del>`, `<em>`, `<i>`, `<q>`, `<s>`, `<strike>`,
`<strong>`

#### XML-RPC ####

`xmlrpc.php` へのリクエストを検証します。

プラグイン [Jetpack by WordPress.com][Jetpack] は、米国のサーバーからこの
エンドポイントにアクセスします。このため国コード `US` が
「[**国コードのホワイトリスト**][CountryList] 」に含まれない、または
ブラックリストに含まれる場合、WordPress.com との連携が機能しません。

[Jetpack サーバーのIPアドレス][JetpackHost]、または 
[Automattic, Inc][Automattic] の AS番号 [AS2635][AS2635] を「[**国コードに優先
して検証するIPアドレスのホワイトリスト**][IP-Whitelist]」に設定して下さい。

#### ログイン・フォーム ####

`wp-login.php` へのアクセスを検証します。

- **対象アクション**  
ユーザー登録や[パスワードで保護されたページ][PassProtect]へのアクセスなど、
ログイン・フォームに関連するアクション毎に設定することができます。

- **IPアドレス当たりのログイン試行可能回数**  
ログイン試行可能な最大回数を設定します。「[**プライバシーと記録の設定**]
[Privacy]」の「[**IPアドレスをキャッシュに記録**][IPCache]」を有効化する
必要があります。

#### 管理領域 ####

`wp-admin/*.php` へのアクセスを検証します。

`wp-admin` 直下のファイルへのリクエストは、ログイン・ページへのリダイレクトを
発生させるばかりでなく、テーマやプラグインの脆弱性を突いた重要情報の搾取や
バックドアの設置など、攻撃のエンドポイントになり得ます。

- **ゼロデイ攻撃を遮断**  
「**国コードで遮断**」では防ぐことができないこれらの攻撃から、サイトを守ること
ができます。

#### 管理領域 ajax/post ####

特に `wp-admin/admin-ajax.php` と `wp-admin/admin-post.php` へのリクエストを
検証します。

これらのエンドポイントは、テーマやプラグインが固有の処理を行うための標準的な
インターフェースとして使われますが、セキュリティ上の配慮に欠けた脆弱なテーマや
プラグインが後を絶ちません。

- **ゼロデイ攻撃を遮断**  
  「**国コードで遮断**」だけでは防ぐことができない攻撃から、サイトを守ることが
  できます。

- **例外**  
「**ゼロデイ攻撃を遮断**」を有効化すると、プラグインやテーマによっては意図
しない遮断が発生することがあります。この様な場合は、該当するアクションやページ
を例外として指定します。虫眼鏡ボタン（<span class="emoji">
![ログから遮断されたリクエストを検索する]({{ '/img/2018-01/find.png' | prepend: site.baseurl }}
 "ログから遮断されたリクエストを検索する")
</span>）は遮断されたリクエストをログから検索し、警告ボタン（<span class="emoji">
![ログから対象を抽出し、検証する]({{ '/img/2018-01/alert.png' | prepend: site.baseurl }}
 "ログから対象を抽出し、検証する")
</span>）は特定した該当リクエストのログを表示するので、正当なリクエストである
ことを検証した後、例外として指定して下さい。  
  
  特に鍵アイコン（<span class="emoji">
![Unlock icon]({{ '/img/2017-08/lock.png' | prepend: site.baseurl }})
</span>）だけが付いたアクションは管理者専用です。指定の際は注意が必要です。

![Find blocked request button]({{ '/img/2018-01/FindLogsButton.png' | prepend: site.baseurl }}
 "Find blocked request button"
)

#### プラグイン領域 ####

`wp-content/plugins/⋯/*.php` へのリクエストを遮断します。

- **ゼロデイ攻撃を遮断**  
[脆弱性のあるプラグイン][ExposeWPConf]の様に、配下の PHP を直接呼び出す様、
プログラムされたプラグインが存在します。この設定により、「**国コードで遮断**」
だけでは防ぐことができないこれらの脆弱性に対する攻撃から、サイトを守ります。

- **WPコアの読み込みを強制**  
[TimThumb][TimThumb] の様に、WordPress のコアとは無関係に単独実行可能な PHP
ファイルを含むプラグインが存在します。この場合、本プラグイン起動されず検証が
実行されませんが、この設定により、本プラグインによる検証を確実に実行させます。

- **例外**  
「**管理領域 ajax/post**」とほぼ同様ですが、プラグイン単位で指定します。

#### テーマ領域 ####

`wp-content/themes/⋯/*.php` へのリクエストを遮断します。

「**WPコアの読み込みを強制**」と「**例外**」は、「**プラグイン領域**」と同様
です。

### 参考情報 ###

- [The best practice of target settings][BestPractice]
- [Prevent exposure of wp-config.php][ExposeWPConf]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[ExposeWPConf]: {{ '/article/exposure-of-wp-config-php.html'                                                                    | prepend: site.baseurl }} "Prevent exposure of wp-config.php | IP Geo Block"
[BestPractice]: {{ '/codex/the-best-practice-for-target-settings.html'                                                          | prepend: site.baseurl }} "The best practice of target settings | IP Geo Block"
[Privacy]:      {{ '/codex/privacy-and-record-settings-ja.html'                                                                 | prepend: site.baseurl }} "プライバシーと記録の設定 | IP Geo Block"
[IPCache]:      {{ '/codex/privacy-and-record-settings-ja.html#ipアドレスをキャッシュに記録'                                    | prepend: site.baseurl }} "プライバシーと記録の設定 | IP Geo Block"
[CountryList]:  {{ '/codex/validation-rule-settings-ja.html#国コードのホワイトリストブラックリスト'                             | prepend: site.baseurl }} "検証ルールの設定 | IP Geo Block"
[IP-Whitelist]: {{ '/codex/validation-rule-settings-ja.html#国コードに優先して検証するipアドレスのホワイトリストブラックリスト' | prepend: site.baseurl }} "検証ルールの設定 | IP Geo Block"
[TimThumb]:     https://blog.sucuri.net/2014/06/timthumb-webshot-code-execution-exploit-0-day.html "TimThumb WebShot Code Execution Exploit (Zeroday)"
[HookComment]:  https://developer.wordpress.org/reference/hooks/comment_form/ "comment_form | Hook | WordPress Developer Resources"
[HookCommTop]:  https://developer.wordpress.org/reference/hooks/comment_form_top/ "comment_form_top | Hook | WordPress Developer Resources"
[Jetpack]:      https://wordpress.org/plugins/jetpack/ "Jetpack by WordPress.com &#124; WordPress.org"
[JetpackHost]:  https://github.com/Automattic/jetpack/issues/1719 "Automattic IP Ranges: offer IP list via API endpoint. - Issue #1719 - Automattic/jetpack"
[Automattic]:   https://automattic.com/ "Automattic"
[AS2635]:       https://ipinfo.io/AS2635 "AS2635 Automattic, Inc - ipinfo.io"
[PassProtect]:  https://codex.wordpress.org/Using_Password_Protection "Using Password Protection &laquo; WordPress Codex"
