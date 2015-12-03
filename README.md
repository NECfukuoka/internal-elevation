# 標高API

----
## 概要

標高APIは，位置情報(緯度・経度)から該当する場所の標高を取得するためのWeb ReST APIです．
国土地理院の提供している標高地図情報から，リクエストされた位置情報に該当する標高タイルを取得し標高値を算出します．

PHPで実装され，Apache HTTPDサーバ経由で配信されます．このアプリケーションはDockerで起動します．
検索パラメータをURLに含めてGETメソッドでリクエストを送信すると，検索結果がJSON形式で戻ります．


----
## Dockerへの配備方法

1. GitHubよりZIPファイルとして本API一式をダウンロードし，Dockerの環境にコピーし，ZIPを展開します．

2. 展開したフォルダ内直下(elevation)に移動し，
以下のdockerコマンドを実行しDocker Imageを作成します．
```
$ docker build -t elevation-search .
```

3. Docker Containerを配備(起動)します．
```
$ docker run -itd -p 8082:80 --name elevation-search elevation-search
```
**ポート番号の部分は自身の環境にあわせて変更してください．**

4. 以下のURLにアクセスし，JSONが戻ることを確認します．
```
http://localhost:8082/getelevation.php?lon=139.11849975585938&lat=35.38121266833199
```
**localhostではなくIPアドレスを指定してもかまいません**


----
## API仕様

APIへのリクエスト(URL)は以下となります：
```
http://[server]/getelevation.php?lon=[経度]&lat=[緯度]
```
**位置情報の測地系はJGD2011で指定します**


レスポンスとしてJSON形式の値が戻ります．フォーマットは以下のようになります：
```
{"elevation":[標高値(メートル)],"hsrc":"[標高のデータ参照元]"}
```

例：リクエスト

```
http://localhost:8082/getelevation.php?lon=139.11849975585938&lat=35.38121266833199
```

例：レスポンス(JSON)

```
{"elevation":550,"hsrc":"5m\uff08\u30ec\u30fc\u30b6\uff09"}
```
