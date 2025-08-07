
# Documentação do Controlador Record.php

🌍 Disponível em:  
[English (README.md)](README.md) | [日本語 (README-jp.md)](README-jp.md) | [العربية (README-ar.md)](README-ar.md) | [Español (README-es.md)](README-es.md)

## Propósito do arquivo
Este controlador lida com a recuperação dos registros históricos de detecção/revisão de IA dentro de uma aplicação ThinkPHP.

## Método principal `Index()`

### Funcionalidades
- Recupera o histórico de detecção ou revisão do usuário atual  
- Suporta resultados paginados  
- Visualiza os resultados da detecção para melhor leitura

### Parâmetros da requisição
| Parâmetro   | Tipo | Padrão | Descrição                       |
|-------------|-------|--------|-------------------------------|
| type        | int   | 1      | Tipo do registro (1=detecção, 2=revisão) |
| page_size   | int   | 10     | Itens por página               |

### Explicação do código

```php
public function Index()
{
    // 1. Obter parâmetros da requisição
    $type = input('type', 1);
    $limit = input('page_size', '10', 'intval');
    
    // 2. Construir condições da consulta
    $where = [
        ['site_id', '=', self::$site_id],
        ['user_id', '=', self::$user['id']],
        ['is_delete', '=', 0]
    ];
    
    // 3. Seleção dinâmica de tabela
    $dbName = ($type == 1) ? 'msg_detect' : 'msg_wyccheck';
    
    // 4. Consultar banco de dados
    $list = Db::name($dbName)
        ->where($where)
        ->field('id,message_input,response,create_time')
        ->order('id desc')
        ->paginate($limit)
        ->toArray();
    
    // 5. Processar resultados
    foreach ($list['data'] as $k => $v) {
        $list['data'][$k]['num'] = mb_strlen($v['message_input'], 'utf8');
        $list['data'][$k]['num2'] = mb_strlen($v['response'], 'utf8');
        $list['data'][$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
        
        if($type == 1){
            $response = json_decode($v['response'], true);
