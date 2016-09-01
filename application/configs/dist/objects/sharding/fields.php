<?php
return array(
    'bucket'=>array(
            'type' => 'link',
            'title' => 'BUCKET',
            'unique' => false,
            'db_isNull' => false,
            'required' => true,
            'validator' => '',
            'link_config' =>
                array (
                    'link_type' => 'object',
                    'object' => 'bucket',
                ),
            'db_type' => 'bigint',
            'db_default' => false,
            'db_unsigned' => true,
            'system'=>true,
            'lazyLang'=>true
    )
);