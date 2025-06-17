# Record.php Controller Documentation

## File Purpose
This controller handles retrieval of AI detection/rewriting history records within a ThinkPHP framework application.

## Core Method `Index()`

### Functionality
- Retrieves current user's AI detection or rewriting history
- Supports paginated results
- Visualizes detection results for readability

### Request Parameters
| Parameter    | Type | Default | Description                     |
|--------------|------|---------|---------------------------------|
| type         | int  | 1       | Record type (1=detection, 2=rewrite) |
| page_size    | int  | 10      | Items per page                  |

### Code Logic Walkthrough

```php
public function Index()
{
    // 1. Get request parameters
    $type = input('type', 1);
    $limit = input('page_size', '10', 'intval');
    
    // 2. Build query conditions
    $where = [
        ['site_id', '=', self::$site_id],       // Current site
        ['user_id', '=', self::$user['id']],    // Current user
        ['is_delete', '=', 0]                   // Non-deleted records
    ];
    
    // 3. Dynamic table selection
    $dbName = ($type == 1) ? 'msg_detect' : 'msg_wyccheck';
    
    // 4. Query database records
    $list = Db::name($dbName)
        ->where($where)
        ->field('id,message_input,response,create_time')
        ->order('id desc')
        ->paginate($limit)
        ->toArray();
    
    // 5. Process result data
    foreach ($list['data'] as $k => $v) {
        // Calculate text lengths
        $list['data'][$k]['num'] = mb_strlen($v['message_input'], 'utf8');
        $list['data'][$k]['num2'] = mb_strlen($v['response'], 'utf8');
        
        // Format timestamp
        $list['data'][$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
        
        // Special handling for detection results
        if($type == 1){
            $response = json_decode($v['response'], true);
            if(isset($response['percent'])){
                // Generate human-readable text
                $text = 'Mixed Writing';
                if($response['percent'] == 0) $text = 'Human Writing';
                if($response['percent'] == 100) $text = 'AI Generated';
                
                $list['data'][$k]['response'] = $text.'(AI Rate:'.$response['percent'].'%)';
            }
        }
    }
    
    // 6. Return formatted response
    return successJson(['list' => $list]);
}
```
### Data Processing
1. **Text Length Calculation**  
   - `num`: Original input text length
   - `num2`: AI-processed result length
   - Uses `mb_strlen()` for accurate multi-byte character counting

2. **Detection Visualization** (type=1 only)
   - Parses JSON `response` field
   - Converts `percent` value to human-readable format:
     - 0% → "Human Writing"
     - 100% → "AI Generated"
     - Other → "Mixed Writing"
   - Output format: `AI Generated(AI Rate:100%)`

3. **Time Formatting**  
   - Converts Unix timestamp to `Y-m-d H:i:s` format

### Security Features
1. Strict query constraints:
   - Current site (`site_id`)
   - Current user (`user_id`)
   - Non-deleted records only (`is_delete=0`)
2. Parameter sanitization:
   - `intval()` ensures page_size is integer
3. Data isolation:
   - Dynamic table selection prevents unauthorized access

### Response Structure
```json
{
  "list": {
    "data": [
      {
        "id": 123,
        "message_input": "Original text",
        "response": "AI Generated(AI Rate:100%)",
        "create_time": "2025-06-15 14:30:00",
        "num": 24,
        "num2": 18
      }
    ],
    // Pagination metadata
    "total": 45,
    "per_page": 10,
    "current_page": 1,
    "last_page": 5
  }
}
```

## Implementation Notes
1. **Dependencies**:
   - Requires `Base` controller for user/site context
   - Uses `think\facade\Db` for database operations

2. **Table Requirements**:
   - `msg_detect` and `msg_wyccheck` must contain:
     - id, message_input, response, create_time
     - site_id, user_id, is_delete columns

3. **Data Expectations**:
   - Detection records (`type=1`) must have JSON `response` containing `percent` key
   - Timestamps stored as Unix time integers
