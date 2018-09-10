---
layout: page
language: ja
category: codex
section: Dashboard
title: 位置情報APIの設定
---

このセクションでは、IP アドレスが属する国コードを検索するための位置情報 API を
設定します。API には、自サーバーにダウンロードした位置情報データベースを用いる
タイプと、外部の REST API を叩くタイプの２種類が存在します。

各 API のライセンスや Terms & Use を確認の上、ご利用ください。

<!--more-->

### APIの選択とキーの設定 ###

以下の API は、自サーバーに位置情報データベースをダウンロードするタイプです。

- [GeoLite2][GeoLite2]  
[MaxMind][MaxMind] が無償で公開するデータベースです。本プラグインでは
__Geolite2__ と表記します。

- [GeoLite Legacy][GeoLegacy]  
[MaxMind][MaxMind] が無償で公開するデータベースです。新しい GeoLite2 に移行する
ため、2018年3月から更新が停止し、2019年1月1日以降ダウンロードできなくなります。
本プラグインでは __Maxmind__ と表記します。

- [IP2Location Lite][IP2Lite]  
[IP2Location][IP2Location] が無償で公開するデータベースです。本プラグインでは
__IP2Location__ と表記します。

![位置情報APIの設定]({{ '/img/2018-09/GeolocationAPIs.png' | prepend: site.baseurl }}
 "位置情報APIの設定"
)

また以下は、無料でサービスされる REST API を利用します。各サービスには、1日の
呼び出し回数に制限があるものや、事前登録による API キーの取得が必要なものがあり
ますので、各サービスの利用規約を確認してください。

- [ipinfo.io][IpinfoIO]
- [Nekudo][Nekudo]
- [GeoIPLookup][GeoIPLookup]
- [ip-api.com][ip-api]
- [Ipdata.co][Ipdata]
- [ipstack][ipstack]
- [IPInfoDB][IPInfoDB]

<div class="alert alert-info">
「<strong>プライバシーと記録の設定</strong>」の「<strong>外部APIへの送信を
制限する</strong>」が有効の場合、これらのタイプは選択不可の状態になりますが、
「<strong>検索</strong>」画面の「<strong>IPアドレスの位置情報を検索</strong>」
では利用が可能です。その際、IP アドレスは自動的に匿名化されて送信されます。
<p><img src="/img/2018-09/SearchGeolocation.png" alt="IPアドレスの位置情報を検索" /></p>
</div>

### 参考情報 ###

- [Local database settings][GeoAPILib]
- [Prevent exposure of wp-config.php][ExposeWPConf]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[ExposeWPConf]: {{ '/article/exposure-of-wp-config-php.html'           | prepend: site.baseurl }} "Prevent exposure of wp-config.php | IP Geo Block"
[GeoAPILib]:    {{ '/codex/geolocation-api-library.html'               | prepend: site.baseurl }} "Local database settings | IP Geo Block"
[MaxMind]:      https://www.maxmind.com/ "IP Geolocation and Online Fraud Prevention | MaxMind"
[GeoLite2]:     https://dev.maxmind.com/geoip/geoip2/geolite2/ "GeoLite2 Free Downloadable Databases &laquo; MaxMind Developer Site"
[GeoLegacy]:    https://dev.maxmind.com/geoip/legacy/geolite/ "GeoLite Legacy Downloadable Databases &laquo; MaxMind Developer Site"
[IP2Location]:  https://www.ip2location.com/ "IP Address to Identify Geolocation Information"
[IP2Lite]:      https://lite.ip2location.com/ "Free IP Geolocation Database | IP2Location LITE"
[IpinfoIO]:     https://ipinfo.io/ "IP Address API and Data Solutions - geolocation, company, carrier info, type and more - ipinfo.io"
[Nekudo]:       https://geoip.nekudo.com/ "Free IP GeoLocation/GeoIp API - geoip.nekudo.com"
[GeoIPLookup]:  http://geoiplookup.net/ "What Is My IP Address | Geo IP Lookup"
[ip-api]:       http://ip-api.com/ "IP-API.com - Free Geolocation API"
[Ipdata]:       https://ipdata.co/ "ipdata - Free IP Geolocation API"
[ipstack]:      https://ipstack.com/ "ipstack - Free IP Geolocation API"
[IPInfoDB]:     https://ipinfodb.com/ "Free IP Geolocation Tools and API| IPInfoDB"
