---
layout: page
language: ja
category: codex
section: Dashboard
title: フロントエンドの設定
---

このセクションでは、望まない国からの一般公開ページ、即ちフロントエンドへの
アクセスを遮断するルールを設定します。

スパマーに対しては、フロントエンドのコメント・フォーム取得を防止することで、
サーバーの負荷とコメント・スパムの量を減らします。またテーマやプラグインの
脆弱性を狙った攻撃に対しては、マルウェアの設置などサイトがハックされるリスク
を減らします。

一般に、コンテンツの[リージョン・コントロール][GeoBlock]を行う場合を除き、
全てのリクエストの中から悪意のあるリクエストだけを選別することは困難ですが、
「[**検証ルールの設定**][RuleSettings]」との組み合わせにより、サイトにとって
不必要なトラフィックとリスクとかなり減らすことができます。

![Front-end target settings]({{ '/img/2016-08/FrontEndSettings.png' | prepend: site.baseurl }}
 "Front-end target settings"
)

### 一般公開ページ ###

「**国コードで遮断**」を有効化し、特定国からのリクエストを遮断します。
この設定を無効にした場合でも、「[**検証ルールの設定**][RuleSettings]」で設定
した「**国コードに優先して検証するIPアドレスのホワイトリスト／ブラックリスト**」
や「**悪意のあるシグネチャ**」、**悪意のあるアップロード防止** は有効です。

### マッチング規則 ###

次の中から1つを選択します：

- **［検証ルールの設定］に従う**
- **ホワイトリスト**
- **ブラックリスト**

**ホワイトリスト**または**ブラックリスト**を選択した場合には、「[**検証ルールの
設定**][RuleSettings]」とは別に、国コードや遮断時の応答ルールを設定することが
できます。

[国コードによる遮断が適切ではない][GeoBlockEU]、または特定のボットやクローラー
だけを遮断したい場合、「**国コードのホワイトリスト**」を空のままに設定すれば、
「**ユーザーエージェント文字列と条件**」のルールだけを適用することができます。

![Additional 3 options]({{ '/img/2016-08/FrontEndMatchingRule.png' | prepend: site.baseurl }}
 "Additional 3 options"
)

### 検証対象 ###

以下のどちらかを選択します：

- **全てのリクエスト**  
フロントエンドへの全リクエストを検証対象とします。このオプションを選択した場合
に限り、[キャッシュ・プラグインとの互換性をとる][LivingCache]ことができます。

- **ターゲットを指定**  
**ページ**、**投稿タイプ**、**カテゴリ** や **タグ** で検証対象を指定します。
このオプションを選択した場合、URL からこれらのターゲット情報を取得するため、
「[検証のタイミング][TimingRule]」は [`wp`][ActionHookWP]アクション・フックまで
遅延され、またキャッシュ・プラグインとの互換性も失います。
  
  ![Validation target]({{ '/img/2016-11/ValidationTarget.png' | prepend: site.baseurl }}
   "Validation target"
  )  
  
  <div class="alert alert-info">
    <strong>注意事項：</strong>
    ここで全ての対象にチェックを入れたとしても、攻撃者はシングル・ページにも
    アーカイブ・ページにも属さないトップ・ページにはアクセスすることができます。
    それ故、全てのリクエストを検証対象とするためには、「<strong>全てのリクエスト
    </strong>」を選択してください。
  </div>

### 行儀の悪いボットやクローラーを遮断 ###

短時間で多くのリクエストを繰り返す、行儀の悪いボットやクローラーを遮断します。
せっかちな訪問者にも不快な思いをさせない程度に、観測期間とページ要求の回数を
設定してください。

![行儀の悪いボットやクローラーを遮断]({{ '/img/2016-08/FrontEndBadBehave.png' | prepend: site.baseurl }}
 "行儀の悪いボットやクローラーを遮断"
)

### ユーザーエージェント文字列と条件 ###

検証対象からは外したい検索エンジンのボットやクローラー、あるいは国コードでは
弾けないリクエストなどに対するルールを、「`:`」（通過）または「`#`」（遮断）で
区切られた「**ユーザーエージェント文字列**」と「**条件**」のペアで設定します。

![UA string and qualification]({{ '/img/2016-08/UA-Qualify.png' | prepend: site.baseurl }}
 "UA string and qualification"
)

詳しくは "[UA string and qualification][UA-Qualify]" を参照してください。

#### DNS 逆引き ####

「**条件**」中の `HOST` にホスト名（の一部）を指定可能にするためには、この
オプションを有効にします。無効の場合、`HOST` 及び <code>HOST=&hellip;</code>は
常に「真」に評価されます。

### シミュレーション・モード ###

このオプションを有効にした場合、検証を模擬するだけで、実際には遮断しないように
振る舞わせることができます。検証結果は「**ログ**」上で確認することができるので、
設定したルールが意図通りに働いているかどうかを事前にチェックすることができます。

![Logs for public faicing pages]({{ '/img/2016-08/Logs-Public.png' | prepend: site.baseurl }}
 "Logs for public faicing pages"
)

### 参考情報 ###

- [The best practice of target settings][BestPractice]
- [Living with caching plugin][LivingCache]
- [UA string and qualification][UA-Qualify]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[BestPractice]: {{ '/codex/the-best-practice-for-target-settings.html'        | prepend: site.baseurl }} "The best practice of target settings | IP Geo Block"
[LivingCache]:  {{ '/codex/living-with-caching-plugin.html'                   | prepend: site.baseurl }} "Living with caching plugin | IP Geo Block"
[UA-Qualify]:   {{ '/codex/ua-string-and-qualification.html'                  | prepend: site.baseurl }} "UA string and qualification | IP Geo Block"
[RuleSettings]: {{ '/codex/validation-rule-settings.html'                     | prepend: site.baseurl }} "検証ルールの設定 | IP Geo Block"
[TimingRule]:   {{ '/codex/validation-rule-settings-ja.html#検証のタイミング' | prepend: site.baseurl }} "検証ルールの設定 | IP Geo Block"
[ActionHookWP]: https://codex.wordpress.org/Plugin_API/Action_Reference/wp "Plugin API/Action Reference/wp &laquo; WordPress Codex"
[GeoBlock]:     https://en.wikipedia.org/wiki/Geo-blocking "Geo-blocking - Wikipedia"
[GeoBlockEU]:   https://ec.europa.eu/digital-single-market/en/faq/geo-blocking "Geo-blocking | Digital Single Market"
