
# Record.php コントローラードキュメント

🌍 利用可能な言語:  
[English (README.md)](README.md) | [العربية (README-ar.md)](README-ar.md) | [Español (README-es.md)](README-es.md) | [Português (README-pt.md)](README-pt.md)

## ファイルの目的
このコントローラーは、ThinkPHPフレームワーク内でAI検出・書き換え履歴レコードの取得を処理します。

## コアメソッド `Index()`

### 機能
- 現在のユーザーのAI検出または書き換え履歴を取得
- ページネーション対応
- 検出結果の視覚化で読みやすく表示

### リクエストパラメーター
| パラメーター | 型   | デフォルト | 説明                           |
|--------------|------|------------|--------------------------------|
| type         | int  | 1          | レコードタイプ (1=検出, 2=書き換え) |
| page_size    | int  | 10         | 1ページあたりの項目数            |

### コードロジックの説明

```php
public function Index()
{
    // 1. リクエストパラメーター取得
    $type = input('type', 1);
    $limit = input('page_size', '10', 'intval');
    
    // 2. クエリ条件設定
    $where = [
        ['site_id', '=', self::$site_id],
        ['user_id', '=', self::$user['id']],
        ['is_delete', '=', 0]
    ];
    
    // 3. テーブル動的選択
    $dbName = ($type == 1) ? 'msg_detect' : 'msg_wyccheck';
    
    // 4. DBからデータ取得
    $list = Db::name($dbName)
        ->where($where)
        ->field('id,message_input,response,create_time')
        ->order('id desc')
        ->paginate($limit)
        ->toArray();
    
    // 5. データ処理
    foreach ($list['data'] as $k => $v) {
        $list['data'][$k]['num'] = mb_strlen($v['message_input'], 'utf8');
        $list['data'][$k]['num2'] = mb_strlen($v['response'], 'utf8');
        $list['data'][$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
        
        if($type == 1){
            $response = json_decode($v['response'], true);
            if(isset($response['percent'])){
                $text = '混合文章';
                if($response['percent'] == 0) $text = '人間の文章';
                if($response['percent'] == 100) $text = 'AI生成';
                
                $list['data'][$k]['response'] = $text.'(AI率:'.$response['percent'].'%)';
            }
        }
    }
    
    // 6. フォーマット済みレスポンス返却
    return successJson(['list' => $list]);
}


### データ処理

1. **文字数計算**

   * `num`: 元の入力テキスト長
   * `num2`: AI処理後の結果の長さ
   * `mb_strlen()`でマルチバイト文字を正確にカウント

2. **検出結果の視覚化**（type=1のみ）

   * JSONの`response`フィールドを解析
   * `percent`値を以下のように変換:

     * 0% → 「人間の文章」
     * 100% → 「AI生成」
     * その他 → 「混合文章」
   * 出力例: `AI生成(AI率:100%)`

3. **日時フォーマット**

   * UNIXタイムスタンプを`Y-m-d H:i:s`形式に変換

### セキュリティ機能

* 厳密なクエリ条件: 現サイト・現ユーザー・非削除レコード
* パラメーターサニタイズ（`intval`）
* 動的テーブル選択でデータ隔離

### レスポンス構造例

```json
{
  "list": {
    "data": [
      {
        "id": 123,
        "message_input": "元のテキスト",
        "response": "AI生成(AI率:100%)",
        "create_time": "2025-06-15 14:30:00",
        "num": 24,
        "num2": 18
      }
    ],
    "total": 45,
    "per_page": 10,
    "current_page": 1,
    "last_page": 5
  }
}
```

---

### 実装ノート

* `Base`コントローラー依存
* `think\facade\Db`使用
* テーブル `msg_detect` と `msg_wyccheck` は必須カラムを含む
* タイムスタンプはUnix時間（整数）
