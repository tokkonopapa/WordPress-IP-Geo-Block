---
layout: page
language: ja
category: codex
section: Dashboard
title: バックエンドの設定
---

WordPress には、サイトに何らかの影響を与える重要なバックエンドへの入口（エンドポイント）が
あります。このセクションでは、それらのうち特に重要な入口に対するリクエストを検証するための
ルールを設定します。

<!--more-->

#### コメント投稿 ####

`wp-comments-post.php` へのリクエストを検証します。

#### XML-RPC ####

`xmlrpc.php` へのリクエストを検証します。

プラグイン [Jetpack by WordPress.com][Jetpack] は、米国のサーバーからこのエンドポイント
にアクセスします。このため `US` が 「[**国コードのホワイトリスト**][CountryList] に含まれ
ない、またはブラックリストに含まれる場合、WordPress.com との連携が機能しません。

[JetpackサーバーのIPアドレス][JetpackHost]、または [Automattic, Inc][Automattic] の 
AS番号 [AS2635][AS2635] を「[**国コードに優先して検証するIPアドレスのホワイトリスト**]
[IP-Whitelist]」に設定して下さい。

#### ログイン・フォーム ####

`wp-login.php` へのアクセスを検証します。

ログインだけでなく、ユーザー登録や [パスワードで保護されたページ][PassProtect] へのアクセス
など、アクション別に指定することができます。

#### 管理領域 ####

`wp-admin/*.php` へのアクセスを検証します。

この領域へのリクエストは、ログイン・ページへのリダイレクトが発生したり（未認証の場合）、テーマ
やプラグインの脆弱性を突いた攻撃によりサイトに意図しない影響を与える（認証済みの場合）などが
起き得ます。「**ゼロデイ攻撃を遮断**」を有効にすることで、これらの攻撃を防御できます。

#### 管理領域 ajax/post ####

特に `wp-admin/admin-ajax.php` と `wp-admin/admin-post.php` へのリクエストを検証
します。

これらのエンドポイントは、テーマやプラグインが固有の処理を行うための WordPress 標準の
インターフェースとして使われますが、関連する脆弱性も多数見つかっています。
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
</span> だけが付いたアクションは管理者専用です。例外に指定する際は注意が必要です。

![Find blocked request button]({{ '/img/2018-01/FindLogsButton.png' | prepend: site.baseurl }}
 "Find blocked request button"
)

#### プラグイン領域 ####

`wp-content/plugins/⋯/*.php` へのリクエストを遮断します。

プラグインの中には、直接プラグイン直下のPHPを呼び出す様にプログラムされている場合があります。
このようなプラグインにも[脆弱性が多数見つかっている][ExposeWPConf]ため、これらを攻撃対象と
するリクエストを遮断する「**ゼロデイ攻撃を遮断**」が選択可能です。

また WordPress とは無関係に、単独で実行されるようプログラムされたタイプのプラグインもあり、
本プラグインによる検証が実行されません。このような場合に備えて「**WPコアの読み込みを強制**」
を指定できます。

「**例外**」は「**管理領域 ajax/post**」とほぼ同様ですが、プラグイン単位で指定します。

#### テーマ領域 ####

`wp-content/themes/⋯/*.php` へのリクエストを遮断します。

「**WPコアの読み込みを強制**」と「**例外**」は、「**プラグイン領域**」と同様です。

### See also ###

- [The best practice of target settings][BestPractice]
- [Prevent exposure of wp-config.php][ExposeWPConf]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[BestPractice]: {{ '/codex/the-best-practice-for-target-settings.html' | prepend: site.baseurl }} "The best practice of target settings | IP Geo Block"
[ExposeWPConf]: {{ '/article/exposure-of-wp-config-php.html'           | prepend: site.baseurl }} "Prevent exposure of wp-config.php | IP Geo Block"
[Jetpack]:      https://wordpress.org/plugins/jetpack/ "Jetpack by WordPress.com &#124; WordPress.org"
[CountryList]:  {{ '/codex/validation-rule-settings-ja.html#国コードのホワイトリストブラックリスト' | prepend: site.baseurl }} "検証ルールの設定 | IP Geo Block"
[IP-Whitelist]: {{ '/codex/validation-rule-settings-ja.html#国コードに優先して検証するipアドレスのホワイトリストブラックリスト' | prepend: site.baseurl }} "検証ルールの設定 | IP Geo Block"
[JetpackHost]:  https://github.com/Automattic/jetpack/issues/1719 "Automattic IP Ranges: offer IP list via API endpoint. - Issue #1719 - Automattic/jetpack"
[Automattic]:   https://automattic.com/ "Automattic"
[AS2635]:       https://ipinfo.io/AS2635 "AS2635 Automattic, Inc - ipinfo.io"
[PassProtect]:  https://codex.wordpress.org/Using_Password_Protection "Using Password Protection &laquo; WordPress Codex"
