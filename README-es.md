
# Documentación del Controlador Record.php

🌍 Disponible en:  
[English (README.md)](README.md) | [日本語 (README-jp.md)](README-jp.md) | [العربية (README-ar.md)](README-ar.md) | [Português (README-pt.md)](README-pt.md)

## Propósito del archivo
Este controlador gestiona la recuperación de los registros históricos de detección/revisión de IA dentro de una aplicación con framework ThinkPHP.

## Método principal `Index()`

### Funcionalidad
- Recupera el historial de detección o revisión del usuario actual  
- Soporta resultados paginados  
- Visualiza resultados de detección para mejor legibilidad

### Parámetros de solicitud
| Parámetro   | Tipo | Predeterminado | Descripción                   |
|-------------|------|----------------|-------------------------------|
| type        | int  | 1              | Tipo de registro (1=detección, 2=revisión) |
| page_size   | int  | 10             | Ítems por página              |

### Explicación del código

```php
public function Index()
{
    // 1. Obtener parámetros de solicitud
    $type = input('type', 1);
    $limit = input('page_size', '10', 'intval');
    
    // 2. Construir condiciones de consulta
    $where = [
        ['site_id', '=', self::$site_id],
        ['user_id', '=', self::$user['id']],
        ['is_delete', '=', 0]
    ];
    
    // 3. Selección dinámica de tabla
    $dbName = ($type == 1) ? 'msg_detect' : 'msg_wyccheck';
    
    // 4. Consultar base de datos
    $list = Db::name($dbName)
        ->where($where)
        ->field('id,message_input,response,create_time')
        ->order('id desc')
        ->paginate($limit)
        ->toArray();
    
    // 5. Procesar resultados
    foreach ($list['data'] as $k => $v) {
        $list['data'][$k]['num'] = mb_strlen($v['message_input'], 'utf8');
        $list['data'][$k]['num2'] = mb_strlen($v['response'], 'utf8');
        $list['data'][$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
        
        if($type == 1){
            $response = json_decode($v['response'], true);
            if(isset($response['percent'])){
                $text = 'Escritura mixta';
                if($response['percent'] == 0) $text = 'Escritura humana';
                if($response['percent'] == 100) $text = 'Generado por IA';
                
                $list['data'][$k]['response'] = $text.'(Tasa IA:'.$response['percent'].'%)';
            }
        }
    }
    
    // 6. Retornar respuesta formateada
    return successJson(['list' => $list]);
}
````

### Procesamiento de datos

1. **Cálculo de longitud de texto**

   * `num`: longitud del texto original
   * `num2`: longitud del resultado procesado por IA
   * Usa `mb_strlen()` para contar caracteres multibyte

2. **Visualización de detección** (solo type=1)

   * Analiza el campo JSON `response`
   * Convierte `percent` a formato legible:

     * 0% → "Escritura humana"
     * 100% → "Generado por IA"
     * Otro → "Escritura mixta"
   * Formato de salida: `Generado por IA(Tasa IA:100%)`

3. **Formato de tiempo**

   * Convierte timestamp Unix a `Y-m-d H:i:s`

### Seguridad

* Restricciones estrictas de consulta: sitio actual, usuario actual, registros no eliminados
* Sanitización de parámetros (`intval`)
* Selección dinámica de tabla para aislamiento de datos

### Estructura de respuesta

```json
{
  "list": {
    "data": [
      {
        "id": 123,
        "message_input": "Texto original",
        "response": "Generado por IA(Tasa IA:100%)",
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

### Notas de implementación

* Depende del controlador `Base` para contexto de usuario/sitio
* Usa `think\facade\Db` para operaciones en BD
* Tablas `msg_detect` y `msg_wyccheck` deben tener columnas requeridas
* Los timestamps se almacenan como enteros Unix

