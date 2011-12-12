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

		foreach ( $post as $name => $data)
		{
			if ( $field = $document->field($name))
			{
				switch ( $document->field($name))
				{
					case 'set': 
						$value = isset($post[$name]) && is_string($post[$name]) && ! empty($post[$name])
							? explode(',', $post[$name])
							: NULL;
					break;
					default:
						$value = Arr::get($post, $name);
					break;
				}
			}
			else
			{
				continue;
			}

			if ( $value === '' && ! $document->__isset($name))
			{
				continue;
			}

			$document->__set($name, $value);
		}

		return $document;
	}

}