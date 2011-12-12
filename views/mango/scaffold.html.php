<table class="fancy">
	<tbody>
		<?php foreach ( $fields as $name => $fdata): ?>
			<tr>
				<?php
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
							$form = form::select($name, $values, Arr::get($data, $name), array('class' => 'select large', 'id' => $id));
						break;
						case 'checkbox':
							$form = form::checkbox($name, 1, Arr::get($data, $name), array('class' => 'checkbox', 'id' => $id));
						break;
						case 'number':
						case 'text':
						case 'email':
						case 'timestamp':
							$form = form::input($name, Arr::get($data, $name), array('class' => 'text large', 'id' => $id, 'type' => $type));
						break;
					}
			
					echo '<td>' . form::label($id, $name . ( Arr::get($fdata, 'required') ? ' *' : '')) . '</td>';
					echo '<td>' . $form . '</td>';
				?>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>