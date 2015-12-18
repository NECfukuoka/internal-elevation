# 標高API

----
## 概要

標高APIは，位置情報(緯度・経度)から該当する場所の標高を取得するためのWeb ReST APIです．
リクエストされた位置情報に該当する標高タイルを取得し標高値を算出します．

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
http://[server]/getelevation.php?lon=[経度]&lat=[緯度]&callback=[JSONPで返すときのコールバック関数]&outtype=[アウトプットの形式]

```

インプットパラメータ|意味|備考|パラメータの指定について
---|----|--------|---------|--------------------
lon|経度|度の10進法で指定します|必ず指定してください
lat|緯度|度の10進法で指定します|必ず指定してください
callback|JSONPで返すときのコールバック関数|JavaScriptの関数名に使用出来る文字列を指定します|outtypeを指定する場合は指定しない
outtype|アウトプットの形式|「JSON」という固定文字列を指定します|callbackを指定する場合は指定しない

**「callback」を指定するとJSONP形式で、「outtype」を指定するとJSON形式で結果が返ります．**

**「callback」と「outtype」はどちらかを指定してください．（両方の指定はしないでください）**

**位置情報の測地系はJGD2011で指定します**


JSON形式の結果の場合、フォーマットは以下のようになります：
```
{"elevation":[標高値(メートル)],"hsrc":"[標高のデータ参照元]"}
```

JSONP形式の結果の場合、フォーマットは以下のようになります：
```
[JSONPで返すときのコールバック関数]{"elevation":[標高値(メートル)],"hsrc":"[標高のデータ参照元]"}
```


**例1**

リクエスト

```
http://localhost:8082/getelevation.php?lon=139.11849975585938&lat=35.38121266833199&outtype=JSON
```

レスポンス(JSON)

```
{"elevation":550,"hsrc":"5m\uff08\u30ec\u30fc\u30b6\uff09"}
```
**例2**

リクエスト

```
http://localhost:8082/getelevation.php?lon=139.11849975585938&lat=35.38121266833199&callback=test
```

レスポンス(JSONP)

```
test({"elevation":550,"hsrc":"5m\uff08\u30ec\u30fc\u30b6\uff09"})
```
