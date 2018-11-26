---
layout: page
language: ja
category: codex
section: Dashboard
title: ローカル・データベースの設定
---

本プラグインでは、[Maxmind][Maxmind] と [IP2location][IP2location] が配布する
複数の IP アドレス位置情報データベースをオンサイトで活用します。複数の情報
ソースを持つことは、相互に情報の欠落を補完し合うという意味で、重要な役割を
果たします。これらのデータベースは、プラグイン本体とは分離開発される位置情報
API ライブラリ [IP Geo API][GitGeoAPI] で管理されます。

### 位置情報 API ライブラリ ###

IP Geo API は、位置情報データベースと共に、次のいずれかのディレクトに
インストールされます。

1. `/wp-content/ip-geo-api/`
2. `/wp-content/uploads/ip-geo-api/`
3. `/wp-content/plugins/ip-geo-block/ip-geo-api/`

実際の格納場所は WordPress ツリーのパーミッション設定に依存します。特に 3. の
場合、本プラグインのアップデート時に位置情報データベースがファイルごと削除され
てしまうので、1. か 2. となるようパーミッションを調整する必要があります。

![Local database settings]({{ '/img/2017-03/LocalDatabaseSettings.png' | prepend: site.baseurl }}
 "Local database settings"
)

サーバーのセキュリティ設定によっては、インストール直後に次のようなメッセージが
表示される場合があるかもしれません。

![Error of IP Geo API]({{ '/img/2016-09/ErrorGeoAPI.png' | prepend: site.baseurl }}
 "Error of IP Geo API"
)

この場合 [IP Geo API][GitGeoAPI] を手作業でインストールの上、本プラグインを
一旦無効化し、再度有効化して下さい。パーミッションの設定については、
"[How can I fix permission troubles?][Permission]" を参照して下さい。

### 位置情報データベースの種類 ###

デフォルトでダウンロードされる位置情報データベースは、IP アドレスと対応する国
コードのみが格納されていますが、データベースを差し替えることで、都市名や緯度・
経度を検索することができるようになります。

このプラグイン専用のフィルター・フック
[ip-geo-block-maxmind-zip-ipv4][MaxmindIPv4] と
[ip-geo-block-maxmind-zip-ipv6][MaxmindIPv6] を参照し、データベースのソースを
設定してください。

### CloudFlare と CloudFront 専用の API ライブラリ ###

[CloudFlare][CloudFlare] か [CloudFront][CloudFront] が提供するプロキシーや
ロード・バランサーのサービスを利用している場合、特別な環境変数を通して
アクセス元の国コードを取得することができます。

これを利用する場合は、専用ライブラリのインストールが必要になります。詳細は、
[CloudFlare & CloudFront API class library][APILibrary] を参照してください。

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[Maxmind]:      https://www.maxmind.com/ "IP Geolocation and Online Fraud Prevention | MaxMind"
[IP2Location]:  https://www.ip2location.com/ "IP Address Geolocation to Identify Website Visitor's Geographical Location"
[GitGeoAPI]:    https://github.com/tokkonopapa/WordPress-IP-Geo-API "GitHub - tokkonopapa/WordPress-IP-Geo-API: A class library combined with WordPress plugin IP Geo Block to handle geo-location database of Maxmind and IP2Location."
[Permission]:   {{ '/codex/how-to-fix-permission-troubles.html' | prepend: site.baseurl }} "How can I fix permission troubles? | IP Geo Block"
[MaxmindIPv4]:  {{ '/codex/ip-geo-block-maxmind-zip-ipv4.html'  | prepend: site.baseurl }} "ip-geo-block-maxmind-zip-ipv4 | IP Geo Block"
[MaxmindIPv6]:  {{ '/codex/ip-geo-block-maxmind-zip-ipv6.html'  | prepend: site.baseurl }} "ip-geo-block-maxmind-zip-ipv6 | IP Geo Block"
[APILibrary]:   {{ '/article/api-class-library.html'            | prepend: site.baseurl }} "CloudFlare & CloudFront API class library | IP Geo Block"
[CloudFlare]:   https://www.cloudflare.com/ "Cloudflare - The Web Performance & Security Company | Cloudflare"
[CloudFront]:   https://aws.amazon.com/cloudfront/ "Amazon CloudFront – Content Delivery Network (CDN)"
