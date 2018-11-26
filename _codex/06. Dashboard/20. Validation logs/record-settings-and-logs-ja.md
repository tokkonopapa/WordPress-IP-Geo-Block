---
layout: page
language: ja
category: codex
section: Dashboard
title: 検証のログ
---

本プラグインでは、「[プライバシーと記録の設定][Privacy]」セクションの
「**検証のログを記録**」が有効な時、データベースにログが記録されます。

<!--more-->

### 記録される内容 ###

![Validation logs]({{ '/img/2018-12/ValidationLogs.png' | prepend: site.baseurl }}
 "Validation logs"
)

以下に、ログに記録された内容の幾つかを紹介します。

#### リクエスト ####

リクエスト・メソッドとポートに引き続き、要求されたパスが記録されます。
[RFC2616][RFC2616-SEC9]（[RFC7231][RFC7231-SEC4]に引き継がれました) は、8つの
メソッド、即ち GET, POST, PUT, DELETE, HEAD, OPTIONS, TRACE, CONNECT を定義し、
またそれらについて以下のように述べています。

> 特に、GETとHEADメソッドは検索以外のアクションをとるべきではないという慣習が
> 確立されています。 これらの方法は「安全」と考えられるべきです。 これにより、
> ユーザエージェントは、POST、PUT、DELETEなどの他のメソッドを特別な方法で表現
> することができるため、安全でない可能性のあるアクションが要求されていることを
> ユーザに認識させることができます。

しかし現実には、GET ソッドによる単純なハイパーリンクに（値の検索ではなく）
データベースを書き換える処理が実装されている例を見つけることができます。

![Media Library]({{ '/img/2016-01/MediaLibrary.png' | prepend: site.baseurl }}
 "Media Library"
)

何れにしても、悪意のあるリクエストに対しては、どんな影響があるかを注意深く監視
する必要があるということです。

#### $_POST データ ####

POST メソッドによるリクエストが遮断されると、サーバー変数 `$_POST` 中のキーが
ログに記録されます。この時「[プライバシーと記録の設定][Privacy]」セクションの
「**内容を展開する$_POSTのキー**」に応じて、同時にその内容が記録されます。

![Record settings]({{ '/img/2016-01/RecordSettings.png' | prepend: site.baseurl }}
 "Record settings"
)

以下は、興味の対象となるであろうキーのリストです。

- `action`  
  このキーは WordPress ではとてもよく使用されます。通常、実行内容を示します。

- `comment`  
  `wp-comments-post.php` に送信されるコメントの内容です。

- `log`, `pwd`  
  `wp-login.php` に送信されるログイン名とパスワードです。遮断の対象でない限り、
  `pwd` は `***` でマスクされます。  
  
  ![Log of Login form]({{ '/img/2016-01/LogLoginForm.png' | prepend: site.baseurl }}
   "Log of Login form"
  )
{% comment %} *** {% endcomment %}

- `FILES`  
  POST メソッドによるアップロードされ、サーバー変数 `$_FILES` に格納された内容
  です。（バージョン 3.0.3 以降）  
  
  ![Malicious file upload]({{ '/img/2017-04/LogUploadFile.png' | prepend: site.baseurl }}
   "Malicious file upload"
  )

#### 検証結果 ####

| 表示          | 定義                                               |
|:--------------|:---------------------------------------------------|
| passed        | 検証を通過                                         |
| passUA        | 「ユーザーエージェント文字列と条件」により通過     |
| blocked       | 「国コード」により遮断                             |
| blockUA       | 「ユーザーエージェント文字列と条件」により遮断     |
| wp-zep        | 「ゼロデイ攻撃を遮断」により遮断                   |
| multi         | XML-RPC の多重呼び出しにより遮断                   |
| badsig        | 「悪意のあるシグネチャ」により遮断                 |
| badbot        | 「行儀の悪いボットやクローラーを遮断」により遮断   |
| extra         | 「国コードに優先して検証するIPアドレス」により遮断 |
| failed        | ログイン失敗により遮断                             |
| limited       | 「ログイン試行可能回数」が許容値を超えたため遮断   |
| upload        | 「悪意のあるアップロード防止」により遮断           |
| ^             | 予期しないファイルがアップロードされたため遮断     |

### ライブアップデート ###

「[プライバシーと記録の設定][Privacy]」セクションの設定によらず、本プラグインで
検証されるすべてのリクエストを、ほぼリアルタイムで表示します。

![Live update]({{ '/img/2018-12/LiveUpdate.png' | prepend: site.baseurl }}
 "Live update"
)

### 参考情報 ###

- [ip-geo-block-record-logs][RecordLogs]
- [ip-geo-block-logs[-preset]][LogsPreset]
- [ip-geo-block-live-log][LiveLogs]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "IP Geo Block &#124; WordPress.org"
[RFC2616-SEC9]: https://tools.ietf.org/html/rfc2616#section-9 "Hypertext Transfer Protocol -- HTTP/1.1: 9 Method Definitions"
[RFC7231-SEC4]: https://tools.ietf.org/html/rfc7231#section-4 "Hypertext Transfer Protocol (HTTP/1.1): 4. Request Methods"
[Privacy]:      {{ '/codex/privacy-and-record-settings.html' | prepend: site.baseurl }} 'Privacy and record settings | IP Geo Block'
[PHP-UPLOADS]:  https://php.net/manual/features.file-upload.post-method.php 'PHP: POST method uploads - Manual'
[RecordLogs]:   {{ '/codex/ip-geo-block-record-logs.html'    | prepend: site.baseurl }} 'ip-geo-block-record-logs | IP Geo Block'
[LogsPreset]:   {{ '/codex/ip-geo-block-logs-preset.html'    | prepend: site.baseurl }} 'ip-geo-block-logs[-preset] | IP Geo Block'
[LiveLogs]:     {{ '/codex/ip-geo-block-live-log.html'       | prepend: site.baseurl }} 'ip-geo-block-live-log | IP Geo Block'
