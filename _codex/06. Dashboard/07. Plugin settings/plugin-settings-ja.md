---
layout: page
language: ja
category: codex
section: Dashboard
title: プラグインの設定
---

このセクションには、プラグインの動作設定や診断情報を取得する機能が集約されて
います。

<!--more-->

### 緊急時用ログイン・リンク ###

万が一、自分自身がブロックされログインできなくなった場合、ログイン・フォーム
へのアクセスを特別に許可するための秘密鍵付きリンクを生成します。生成された
秘密鍵は、それが削除されるまで有効です。

![Emergency login link]({{ '/img/2018-09/EmergencyLoginLink.png' | prepend: site.baseurl }}
 "Emergency login link"
)

秘密鍵そのものを本プラグインは保持しないので、ブラウザの「お気に入り」や
「ブックマーク」等に保管し、また他に知られないよう注意してください。

["What should I do when I&apos;m locked out?"][EmergentFunc] で解説している
緊急時用関数が特定の検証を完全に無効化してしまうのに対し、この秘密鍵付きリンク
を用いた場合、ログインできなかった理由が特定できるようになります。

- **国コードか IP アドレスがホワイトリストに含まれない、またはブラックリストに
含まれる場合**  
  
  ![Blocking reason 1]({{ '/img/2018-09/LoginValidation1.png' | prepend: site.baseurl }}
   "Blocking reason 1"
  )
  
  上記のメッセージが表示される場合、「[検証ルールの設定][ValidateRule]」を
  見直してください。

- **ログイン試行の回数制限に引っかかった場合**  
  
  ![Blocking reason 2]({{ '/img/2018-09/LoginValidation2.png' | prepend: site.baseurl }}
   "Blocking reason 2"
  )
  
  上記のメッセージが表示される場合、「**統計**」画面の「**キャッシュの統計**」
  に移動し、あなたの IP アドレスをキャッシュのエントリから削除してください。

### 設定のエクスポート、インポート ###

このプラグインの設定を、[JSON][JSON] 形式のテキスト・ファイルへ書き込んだり
（エクスポート）、ファイルからの読み込み（インポート）を行います。

「インポート」を行うと、現在の設定値と異なる項目には
<code style="color:red">*</code> {% comment %}*{% endcomment %}
が付きます。これらは各セクションの UI を変更するだけなので、「**変更を保存**」
を実行し、設定を反映させてください。

### プリセットのインポート ###

あらかじめ本プラグインが持つ設定値をインポートします。特に「**バックエンドの
推奨設定**」は、本プラグインの能力を最大限発揮させるバックエンドに関する設定値
をインポートします。

### 診断情報 ###

問題発生の時診断やデバッグに役立つ情報を表示します。

特に本プラグインと他プラグインとの競合が生じた場合には、「**情報を表示**」を
実行してください。あなたの IP アドレスによる遮断されたリクエストをログから
抽出し、末尾に表示します。以下はその例です。

{% highlight text %}
- 2018-09-15 09:14:51 wp-zep  GET:/wp-admin/admin-ajax.php?action=ipgb-tester-admin-ajax(HTTP_REFERER=http://localhost/,HTTP_DNT=1,HTTP_UPGRADE_INSECURE_REQUESTS=1,HTTP_X_FORWARDED_FOR=192.168.0.***)
- 2018-09-15 07:11:12 limited GET:/wp-login.php(HTTP_UPGRADE_INSECURE_REQUESTS=1,HTTP_X_FORWARDED_FOR=192.168.0.***)
- 2018-09-15 07:10:13 limited GET:/(HTTP_REFERER=http://localhost/wp-login.php,HTTP_DNT=1,HTTP_UPGRADE_INSECURE_REQUESTS=1,HTTP_X_FORWARDED_FOR=192.168.0.***)
- 2018-09-15 07:10:09 limited POST:/wp-login.php(HTTP_REFERER=http://localhost/wp-login.php,HTTP_DNT=1,HTTP_UPGRADE_INSECURE_REQUESTS=1,HTTP_X_FORWARDED_FOR=192.168.0.***)
- 2018-09-15 07:10:02 failed  POST:/wp-login.php(HTTP_REFERER=http://localhost/wp-login.php,HTTP_DNT=1,HTTP_UPGRADE_INSECURE_REQUESTS=1,HTTP_X_FORWARDED_FOR=192.168.0.***)
- 2018-09-15 07:09:19 blocked GET:/wp-login.php(HTTP_DNT=1,HTTP_UPGRADE_INSECURE_REQUESTS=1,HTTP_X_FORWARDED_FOR=192.168.0.***)
- 2018-09-15 06:22:52 wp-zep  GET:/wp-content/plugins/ip-geo-block/samples.php?file=../../../wp-config.php(HTTP_REFERER=http://localhost/blog/2018/07/29/hello-world/,HTTP_DNT=1,HTTP_UPGRADE_INSECURE_REQUESTS=1,HTTP_X_FORWARDED_FOR=192.168.0.***)
{% endhighlight %}

これらは問題解決に大いに役立ちます。[サポート・フォーラム][SupportForum] 等での
質問やレポートの際には、コピー＆ペーストによる情報提供をお願いします <span class="emoji">
![emoji](https://assets-cdn.github.com/images/icons/emoji/unicode/1f4cc.png)
</span>。

### 参考情報 ###

- [The best practice of target settings][BestPractice]
- [Prevent exposure of wp-config.php][ExposeWPConf]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[SupportForum]: https://wordpress.org/support/plugin/ip-geo-block "View: [IP Geo Block] Support &#124; WordPress.org"
[BestPractice]: {{ '/codex/the-best-practice-for-target-settings.html' | prepend: site.baseurl }} "The best practice of target settings | IP Geo Block"
[EmergentFunc]: {{ '/codex/what-should-i-do-when-i-m-locked-out.html'  | prepend: site.baseurl }} "What should I do when I&apos;m locked out? | IP Geo Block"
[ValidateRule]: {{ '/codex/validation-rule-settings.html'              | prepend: site.baseurl }} "Validation rule settings | IP Geo Block"
[ExposeWPConf]: {{ '/article/exposure-of-wp-config-php.html'           | prepend: site.baseurl }} "Prevent exposure of wp-config.php | IP Geo Block"
[JSON]:         https://en.wikipedia.org/wiki/JSON "JSON - Wikipedia"
