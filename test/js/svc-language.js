/**
 * Service: Language
 *
 */
angular.module('language', []);
angular.module('language').factory('LanguageSvc', ['$window', function ($window) {
	var language = {
		'en': {
			main_title: 'WordPress POST Access Emulator',
			page_settings: 'Page Settings',
			page_readme: 'After setting the followings, click <span class="highlight">Validate</span> to validate the pages.',
			home_url: 'WordPress Home',
			single_page: 'Single Page',
			ip_address: 'Proxy IP Address',
			note_ip_address: 'You should add <span class="highlight"><code>HTTP_X_FORWARDED_FOR</code></span> into <span class="highlight"><code>$_SERVER</code> keys for extra IPs</span> on <span class="highlight">Settings</span> tab of IP Geo Block in order to emulate the post from outside your nation.',
			submit_post: 'POST Access',
			post_settings: 'POST Settings',
			required: 'Required',
			cb_post_items: 'Select All',
			post_comment: 'Post Comment',
			author: 'Name',
			email: 'Email',
			site_url: 'Site URL',
			comment: 'Comment',
			trackback: 'Trackback',
			title: 'Title',
			excerpt: 'Excerpt',
			trackback_url: 'Trackback URL',
			blog_name: 'Blog Name',
			login: 'Login Form',
			user_name: 'User Name',
			password: 'Password',
			admin_area: 'Admin Area',
			admin_cookie: 'Admin Cookie',
			admin_ajax: 'Admin Ajax',
			_wpnonce: 'Nonce',
			action: 'Action',
			param: 'Parameter',
			pingback: 'Pingback',
			pingback_readme: '<span class="highlight"><code>%WP_HOME%</code></span> will be replaced with <span class="highlight">WordPress HOME</span>.',
			xmlrpc: 'XML-RPC',
			xmlrpc_readme: '<span class="highlight"><code>%USER_NAME%</code></span> and <span class="highlight"><code>%PASSWORD%</code></span> will be replaced with settings in <span class="highlight">Login Form</span>.',
			xmlrpc_demo: 'XML-RPC Demo',
			end: ''
		},

		'ja': {
			main_title: 'WordPressへのPOSTアクセス',
			page_settings: 'ページ設定',
			page_readme: '以下を適切に設定した後、<span class="highlight">Validate</span> を実行し、正当性を確認して下さい。',
			home_url: 'WordPressホーム',
			single_page: 'シングルページ',
			ip_address: 'プロキシアドレス',
			note_ip_address: '海外からの投稿を模擬するために、IP Geo Block の <span class="highlight">設定</span> タブで、<span class="highlight">追加検証する<code>$_SERVER</code>のキー</span> に <span class="highlight"><code>HTTP_X_FORWARDED_FOR</code></span> を設定しておいて下さい。',
			submit_post: 'POSTアクセス',
			post_settings: 'POST設定',
			required: '必須',
			cb_post_items: '全選択',
			post_comment: 'コメント投稿',
			author: '名前',
			email: 'Eメール',
			site_url: 'サイトURL',
			comment: 'コメント',
			trackback: 'トラックバック',
			title: 'タイトル',
			excerpt: '抜粋',
			trackback_url: 'トラックバックURL',
			blog_name: 'ブログ名',
			login: 'ログインフォーム',
			user_name: 'ユーザー名',
			password: 'パスワード',
			admin_area: '管理領域',
			admin_cookie: '管理者クッキー',
			admin_ajax: '管理領域のAjax',
			_wpnonce: 'ナンス',
			action: 'アクション',
			param: 'パラメータ',
			pingback: 'ピンバック',
			pingback_readme: '<span class="highlight"><code>%WP_HOME%</code></span> は <span class="highlight">WordPressホーム</span> の設定値に置き換えられます。',
			xmlrpc: 'XML-RPC',
			xmlrpc_readme: '<span class="highlight"><code>%USER_NAME%</code></span>、<span class="highlight"><code>%PASSWORD%</code></span> は <span class="highlight">ログインフォーム</span> の設定値に置き換えられます。',
			xmlrpc_demo: 'XML-RPC デモ',
			end: ''
		}
	};
	return function () {
		var lang = $window.navigator.userLanguage || $window.navigator.language;
		return language[lang.indexOf('ja') !== -1 ? 'ja' : 'en'];
	};
}]);