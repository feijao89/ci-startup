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
				'extend' => array('bean' => 'Bean')
			),
			'has_many' => array(
				'attributes' => array('bean' => 'Attribute', 'fkey' => 'bean_id'),
			)
		),
		'Attribute' => array(
			'fields' => array(
				'id' => 'int',
				'name' => 'varchar(30)',
				'type' => 'varchar(30)',
				'value' => 'varchar(30)',
				'bean_id' => 'int' 
				)/*,
			'has_one' => array(
				'bean' => array('bean' => 'Bean')
			)*/
		),
		'Test1' => array(
			'fields' => array(
				'id' => 'int',
				'a' => 'boolean',
				'b' => 'text'
			)
		)
	);
?>