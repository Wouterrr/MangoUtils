<?php

class Mango_Scaffold {

	public static function build($document, array $post)
	{
		if ( is_string($document))
		{
			$document = Mango::factory($document);
		}

		foreach ( $document->fields() as $name => $data)
		{
			switch ( $data['type'])
			{
				case 'boolean':
					$post[$name] = isset($post[$name]);
				break;
			}
		}

		foreach ( $post as $name => $value)
		{
			if ( $field = $document->field($name))
			{
				switch ( $field['type'])
				{
					case 'set': 
						$value = is_string($value) && $value !== ''
							? explode(',', $value)
							: NULL;
					break;
				}
			}
			else
			{
				continue;
			}

			$document->__set($name, $value);
		}

		return $document;
	}

}