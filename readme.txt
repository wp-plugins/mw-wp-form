=== MW WP Form ===
Contributors: inc2734
Donate link: http://www.amazon.co.jp/registry/wishlist/39ANKRNSTNW40
Tags: plugin, form, confirm, preview, shortcode
Requires at least: 3.4
Tested up to: 3.6.1
Stable tag: 1.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

MW WP Form can create mail form with a confirmation screen using shortcode.

== Description ==

MW WP Form can create mail form with a confirmation screen using shortcode.

* Form created using short codes
* Using confirmation page.
* The page changes by the same URL or individual URL are possible.
* Many validation rules

MW WP Form はショートコードを使って確認画面付きのメールフォームを作成することができるプラグインです。

* ショートコードを使用したフォーム生成
* 確認画面が表示可能
* 同一URL・個別URLでの画面変遷が可能
* 豊富なバリデーションルール

http://2inc.org/manual-mw-wp-form/
http://2inc.org/blog/category/products/wordpress_plugins/mw-wp-form/

== Installation ==

1. Upload `MW WP Form` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. You can create a form by settings page.

== Changelog ==

= 1.1.0 =
* Added  : mwform_value_識別子 フィルターフック追加
* Added  : mwform_hidden の引数 echo を追加（ true or false ）
* Added  : カタカナ バリデーション項目を追加
* Cahged : 管理画面メニュー表示、設定保存の権限を変更（edit_pagesに統一）
* Bug fix: 複数のMIMEタイプをとりえる拡張子を持つファイルのアップロードに対応（avi、mp3、mpg）

= 1.0.4 =
* Bug fix: 画像以外の添付ファイルがカスタムフィールドに表示されないバグを修正
* Bug fix: 動画アップロード時にFatal Errorがでるバグを修正

= 1.0.3 =
* Added  : 管理画面に Donate link を追加

= 1.0.2 =
* Bug fix: シングルページのみ実行可能に変更（検索結果ページ等でリダイレクトしてしまうため）
* Bug fix: URL引数有効 + 同一URL時にリダイレクトループが発生してしまうバグを修正

= 1.0.1 =
* Bug fix: DBに保存しないときに添付ファイルが送られてこない

= 1.0.0 =
* Added  : Donate link を追加
* Added  : DB保存データにメモ欄追加
* Cahged : ファイルアップロード用のディレクトリにアップロードするように変更専用
* Cahged : 拡張子が偽造されたファイルの場合はアップロードしない（php5.3.0以上）
* Cahged : 表示ページのURLに引数が付いている場合でも管理画面で設定したURLにリダイレクトしてしまわないように変更
* Bug fix: 通常バリデーションは配列が来ることを想定していなかったため修正

= 0.9.11 =
* Bug fix: 添付ファイルが複数あり、かつDB保存の場合、管理画面で最後の画像しか表示されないバグを修正
* Cahged : どのフィールドが画像かを示すメタデータの保存形式を配列に変更
* Cahged : mw_form_field::inputPage、mw_form_field::previewPage の引数削除

= 0.9.10 =
* Bug fix: mwform_admin_mail_識別子、mwform_auto_mail_識別子フィルターフックの定義位置が逆だったのを修正
* Bug fix: 添付ファイルが添付されないバグを修正（From Ver0.9.4）
* Bug fix: Akismet Email、Akismet URL の設定が正しく行えなかったのを修正
* Cahged : フォーム送信時は $_POST を WP Query に含めない

= 0.9.9 =
* Added  : mwform_csv_button_識別子 フィルターフック
* Bug fix: name属性が未指定のとき、MW_Form::getZipValue, MW_Form::getCheckedValue でエラーがでるバグ修正

= 0.9.8 =
* Added  : 管理者用・自動返信用メール設定それぞれに 送信元メールアドレス・送信者名の設定を追加
* Added  : mwform_admin_mail_識別子 フィルターフック追加
* Added  : mwform_auto_mail_識別子 フィルターフック追加
* Deleted: mwform_admin_mail_from_識別子 フィルターフック
* Deleted: mwform_admin_mail_sender_識別子 フィルターフック
* Deleted: mwform_auto_mail_from_識別子 フィルターフック
* Deleted: mwform_auto_mail_sender_識別子 フィルターフック

= 0.9.7 =
* Bug fix: CSVダウンロードのバグ修正

= 0.9.6 =
* Bug fix: 電話番号のバリデーションチェックを修正
* Added  : CSVダウンロード機能追加
* Added  : mwform_admin_mail_from_識別子 フック追加
* Added  : mwform_admin_mail_sender_識別子 フック追加
* Added  : mwform_auto_mail_from_識別子 フック追加
* Added  : mwform_auto_mail_sender_識別子 フック追加

= 0.9.5 =
* Added  : バリデーションエラー時に遷移するURLを設定可能に
* Cahged : 送信メールの Return-Path に「管理者宛メール設定の送信先」が利用されるように変更
* Cahged : {投稿情報}、{ユーザー情報}の値がない場合は空値が返るように変更
* Cahged : 設定済みのバリデーションルールは閉じた状態で表示されるように変更
* Cahged : Mail::createBody の挙動を変更（送信された値がnullの場合はキーも値も出力しない）
* Bug fix: Mail::createBody で Checkbox が未チェックで送信されたときに Array と出力されてしまうバグを修正

= 0.9.4 =
* Bug fix: 管理画面での 確認ボタン の表記間違いを修正

= 0.9.3 =
* Added  : readme.txt にマニュアルのURLを追記
* Bug fix: 確認ボタン 挿入ボタンが表示されていなかったのを修正
* Bug fix: 末尾に / のつかない URL の場合に画面変遷が正しく行われないバグを修正

= 0.9.2 =
* Bug fix: ファイルの読み込みタイミング等を変更

= 0.9.1 =
* Bug fix: 画像・ファイルアップロードフィールドのクラス名が正しく設定されていないのを修正
* Bug fix: 画像・ファイルアップロードフィールドで未アップロード時でも確認画面に項目が表示されてしまうのを修正
* Cahged : 言語ファイルの読み込みタイミングを変更

= 0.9 =
* Added  : Akismet設定を追加

= 0.8.1 =
* Cahged : functions.php を用いたフォーム作成は非推奨・サポート、メンテナンス停止
* Added  : チェックボックスで区切り文字の設定機能を追加
           [mwform_checkbox name="checkbox" children="A,B,C" separator="、"]

= 0.8 =
* Added  : 画像アップロードフィールドを追加
* Added  : ファイルアップロードフィールドを追加
* Added  : ファイルタイプ バリデーション項目を追加
* Added  : ファイルサイズ バリデーション項目を追加
* Added  : 管理画面で不正な値は save しないように修正
* Added  : datepickerで年月をセレクトボックスで選択できる設定をデフォルトに
* Added  : アクションフック mwform_add_shortcode, mwform_add_qtags 追加
* Bug fix: バリデーション項目 文字数の範囲, 最小文字数 の挙動を修正
* Cahged : フォーム制作画面でビジュアルエディタを使えるように変更

= 0.7.1 =
* Added  : メール設定を 自動返信メール設定 と 管理者宛メール設定 に分割
* Note   : データベースには 管理者宛メール設定 のデータが保存される
* Note   : 管理者宛メール設定 が空の場合は 自動返信メール設定 が使用される

= 0.7 =
* Added  : 問い合わせデータをデータベースに保存する機能を追加
* Added  : アンインストール時にデータを削除するように修正
* Bug fix: 一覧画面で QTags の JSエラーがでていたのを修正

= 0.6.4 =
* Added  : 引数を有効にする meta_box を追加
* Bug fix: "Zip Code" が日本語化されていないバグを修正
* Bug fix: ページリダイレクトのURL判定を変更
* Bug fix: バリデーション mail に複数のメールアドレスを指定できないように変更

= 0.6.3 =
* Bug fix: 管理画面のURL設定で http から入れないとメールが二重送信されてしまうバグを修正
* Bug fix: フォーム識別子部分が Firefox でコピペできないバグを修正

= 0.6.2 =
* Bug fix: Infinite loop when WordPress not root installed.

= 0.6.1 =
* Added To E-mail adress settings.

= 0.6 =
* Added settings page.
* Deprecated: acton hook mwform_mail_{$key}. This hook is removed when next version up.
* Added filter hook mwform_mail_{$key}.
* Bug fix: Validations.

= 0.5.5 =
* Added tag to show login user meta.
{user_id}, {user_login}, {user_email}, {user_url}, {user_registered}, {display_name}

= 0.5 =
* Initial release.