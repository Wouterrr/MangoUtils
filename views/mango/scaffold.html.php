<?php
	foreach ( $fields as $name => $fdata)
	{
		switch ( $fdata['type'])
		{
			case 'int':
			case 'counter':
			case 'float':
				$type = 'number';
			break;
			case 'timestamp':
				if ( isset($data[$name]) && is_int($data[$name]) && $data[$name] !== 0)
				{
					$data[$name] = Date(DATE_ATOM, $data[$name]);
				}
			case 'set':
				if ( isset($data[$name]))
				{
					$data[$name] = implode(',', $data[$name]);
				}
			case 'string':
			case 'email':
				$type = 'text';
			break;
			case 'boolean':
				$type = 'checkbox';
			break;
			case 'enum':
				$type   = 'select';
				$values = array_combine($fdata['values'], $fdata['values']);
			break;
			default:
				continue 2;
			break;
		}

		$id = 'scaffold-' . $name;

		switch ( $type)
		{
			case 'select':
				$form = form::select($name, $values, Arr::get($data, $name), array('id' => $id));
			break;
			case 'checkbox':
				$form = form::checkbox($name, 1, Arr::get($data, $name), array('id' => $id));
			break;
			case 'set':
			case 'number':
			case 'text':
			case 'email':
			case 'timestamp':
				$form = form::input($name, Arr::get($data, $name), array('id' => $id, 'type' => $type));
			break;
		}

		$label = $name . ( Arr::get($fdata, 'required') ? ' *' : '');

		echo $type === 'checkbox'
			? form::label($id, $form . ' ' . $label, array('class' => 'checkbox'))
			: ( form::label($id, $label) . $form);
	}
?>