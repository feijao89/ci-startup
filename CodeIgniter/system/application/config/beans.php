<?php
    /*
     * Tipologie dei fields :
     * - smallint
     * - int
     * - double
     * - float
     * - varchar(lenght)
     * - timestamp
     * - text
     * - char(lenght)
     * - boolean
     * 
     * Regole :
     * - tutti i beans devono avere un id intero
     * - tutte le chiavi esterne devono avere _id come finale
     */
	
	$config['beans'] = array(
		'Package' => array(
			'fields' => array(
				'id' => 'int' ,
				'name' => 'varchar(30)',
				'description' => 'text'
				),
			'has_many' => array(
				'beans' => array('bean' => 'Bean', 'fkey' => 'package_id', 'lazy' => true),
			)
		),
		'Bean' => array(
			'fields' => array(
				'id' => 'int',
				'name' => 'varchar(30)',
				'description' => 'text'//,
				//'package_id' => 'int',
				//'extend_id' => 'int'
				),
			'has_one' => array(
				'package' => array('bean' => 'Package'),
				'extend' => array('bean' => 'Bean', 'lazy' => true)
			),
			'has_many' => array(
				'attributes' => array('bean' => 'Attribute', 'fkey' => 'bean_id', 'lazy' => true),
			)
		),
		'Attribute' => array(
			'fields' => array(
				'id' => 'int',
				'name' => 'varchar(30)',
				'type' => 'varchar(30)',
				'value' => 'varchar(30)',
				'bean_id' => 'int' 
				),
			'has_one' => array(
				'external' => array('bean' => 'Bean')
			)
		),
		'Test1' => array(
			'fields' => array(
				'id' => 'int',
				'a' => 'boolean',
				'b' => 'text'
			)
		),
		'Knight' => array(
			'fields' => array(
				'id' => 'int',
				'name' => 'varchar(30)',
				'username' => 'varchar(30)',
				'password' => 'char(32)',
				'email' => 'varchar(64)'
			)
		)
	);
?>