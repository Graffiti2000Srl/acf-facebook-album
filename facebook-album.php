<?php
class acf_field_facebook_album extends acf_field {

	var $settings,
		$defaults;

	function __construct()
	{
		$this->name = 'facebook_album';
		$this->label = __('Facebook Album');
		$this->category = __("Choice",'acf');
		$this->defaults = array(
			'allow_null' => 1,
			'facebook_page' => ''
		);
		parent::__construct();
		$this->settings = array(
			'path' => apply_filters('acf/helpers/get_path', __FILE__),
			'dir' => apply_filters('acf/helpers/get_dir', __FILE__),
			'version' => '1.0.0'
		);

	}

	function create_options( $field )
	{
		$field = array_merge($this->defaults, $field);
		$key = $field['name'];
		?>
<tr class="field_option field_option_<?php echo $this->name; ?>">
	<td class="label">
		<label><?php _e("Facebook Page ID",'acf'); ?></label>
		<p class="description"><?php _e("Your Facebook Page ID",'acf'); ?></p>
	</td>
	<td>
		<?php

		do_action('acf/create_field', array(
			'type'		=>	'text',
			'name'		=>	'fields['.$key.'][facebook_page]',
			'value'		=>	$field['facebook_page']
		));

		?>
	</td>
</tr>
<tr class="field_option field_option_<?php echo $this->name; ?>">
	<td class="label">
		<label><?php _e("Allow Null?",'acf'); ?></label>
	</td>
	<td>
		<?php
		do_action('acf/create_field', array(
			'type'	=>	'radio',
			'name'	=>	'fields['.$key.'][allow_null]',
			'value'	=>	$field['allow_null'],
			'choices'	=>	array(
				1	=>	__("Yes",'acf'),
				0	=>	__("No",'acf'),
			),
			'layout'	=>	'horizontal',
		));
		?>
	</td>
</tr>
		<?php

	}

	function create_field( $field )
	{
		$field = array_merge($this->defaults, $field); $albums = array(); $continue = false;
		$data = json_decode(file_get_contents('https://graph.facebook.com/'.$field['facebook_page'].'/albums?fields=name'), true);
		if ($data) {
			do {
				if (count($data)) {
					$albums = array_merge($albums, (array) $data['data']);
					if ($continue = array_key_exists('next', $data['paging'])) {
						$data = json_decode(file_get_contents($data['paging']['next']), true);
					}
				} else {
					$continue = false;
				}
			} while ($continue);

			// html
			echo '<select id="' . $field['id'] . '" class="' . $field['class'] . '" name="' . $field['name'] . '" ' . ' >';

			// null
			if( $field['allow_null'] )
			{
				echo '<option value="null">- ' . __("Select an Album",'acf') . ' -</option>';
			}

			// loop through values and add them as options
			if( is_array($albums) )
			{
				foreach( $albums as $album )
				{
					$selected = in_array($album['id'], $field['value']) ? 'selected="selected"' : '';
					echo '<option value="'.$album['id'].'" '.$selected.'>'.$album['name'].'</option>';
				}
			}

			echo '</select>';
		} else {
			echo '<p>Your plugin is misconfigured. Set your Facebook Page ID.</p>';
		}
	}

	function format_value( $value, $post_id, $field )
	{
		return $value;
	}

	function format_value_for_api( $value, $post_id, $field )
	{
		return $value;
	}

}


// create field
new acf_field_facebook_album();

?>