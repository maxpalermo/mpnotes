UPDATE ps_mpnote
SET flags = JSON_OBJECT(
    'printable', COALESCE(
        (SELECT JSON_UNQUOTE(JSON_EXTRACT(value, '$.value'))
         FROM JSON_TABLE(
             flags,
             '$[*]' COLUMNS(
                 id VARCHAR(10) PATH '$.id',
                 value JSON PATH '$'
             )
         ) AS item
         WHERE item.id = '4'
         LIMIT 1
        ), 0
    ),
    'chat', COALESCE(
        (SELECT JSON_UNQUOTE(JSON_EXTRACT(value, '$.value'))
         FROM JSON_TABLE(
             flags,
             '$[*]' COLUMNS(
                 id VARCHAR(10) PATH '$.id',
                 value JSON PATH '$'
             )
         ) AS item
         WHERE item.id = '5'
         LIMIT 1
        ), 0
    )
)
WHERE flags IS NOT NULL 
  AND flags != '' 
  AND JSON_VALID(flags)
  AND JSON_TYPE(flags) = 'ARRAY';
