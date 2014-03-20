<?php

class acf_field_facebook_album extends acf_field
{
    public $settings;

    public $defaults;

    public function __construct()
    {
        $this->name     = 'facebook_album';
        $this->label    = __('Facebook Album');
        $this->category = __('Choice', 'acf');

        $this->defaults = array(
            'allow_null'    => 1,
            'facebook_page' => '',
            'save_format'   => 'images',
        );

        parent::__construct();

        $this->settings = array(
            'path'    => apply_filters('acf/helpers/get_path', __FILE__),
            'dir'     => apply_filters('acf/helpers/get_dir', __FILE__),
            'version' => '0.1',
        );
    }

    public function create_options($field)
    {
        $field = array_merge($this->defaults, $field);
        $key   = $field['name'];
        ?>
        <tr class="field_option field_option_<?php echo $this->name; ?>">
            <td class="label">
                <label><?php _e('Return Value', 'acf'); ?></label>
                <p><?php _e('Specify the returned value on front end', 'acf') ?></p>
            </td>
            <td>
                <?php

                do_action('acf/create_field', array(
                    'type'		=>	'radio',
                    'name'		=>	'fields['.$key.'][save_format]',
                    'value'		=>	$field['save_format'],
                    'layout'	=>	'horizontal',
                    'choices'	=> array(
                        'images' => __('Array of images', 'acf'),
                        'album'  => __('Album object', 'acf'),
                        'id'     => __('Album ID', 'acf'),
                        'ul'     => __('List of images', 'acf'),
                        'div'    => __('Containers with background', 'acf'),
                    )
                ));

                ?>
            </td>
        </tr>
        <tr class="field_option field_option_<?php echo $this->name; ?>">
            <td class="label">
                <label><?php _e('Facebook Page URI', 'acf'); ?></label>
                <p class="description"><?php _e('Your Facebook Page URI', 'acf'); ?></p>
            </td>
            <td>
                <?php

                do_action('acf/create_field', array(
                    'type'  => 'text',
                    'name'  => 'fields['.$key.'][facebook_page]',
                    'value' => $field['facebook_page'],
                ));

                ?>
            </td>
        </tr>
        <tr class="field_option field_option_<?php echo $this->name; ?>">
            <td class="label">
                <label><?php _e('Allow Null?', 'acf'); ?></label>
            </td>
            <td>
                <?php

                do_action('acf/create_field', array(
                    'type'	  => 'radio',
                    'name'	  => 'fields['.$key.'][allow_null]',
                    'value'	  => $field['allow_null'],
                    'choices' => array(
                        1 => __("Yes", 'acf'),
                        0 => __("No", 'acf'),
                    ),
                    'layout'  => 'horizontal',
                ));

                ?>
            </td>
        </tr>
    <?php
    }

    public function create_field($field)
    {
        $field    = array_merge($this->defaults, $field);
        $albums   = array();
        $continue = false;

        $page     = $field['facebook_page'];
        $pageName = substr(strrchr(trim($page, '/'), '/'),1);
        $pageID   = json_decode(file_get_contents('https://graph.facebook.com/' . $pageName . '?fields=id'));

        if (isset($pageID)) {
            $pageID = $pageID->id;
            $data   = json_decode(file_get_contents('https://graph.facebook.com/' . $pageID . '/albums?fields=name'), true);

            if ($data) {
                do {
                    if (count($data['data'])) {
                        $albums = array_merge($albums, (array) $data['data']);
                        if ($continue = array_key_exists('next', $data['paging'])) {
                            $data = json_decode(file_get_contents($data['paging']['next']), true);
                        }
                    } else {
                        $continue = false;
                    }
                } while ($continue);

                // html
                echo '<select id="' . $field['id'] . '" class="' . $field['class'] . '" name="' . $field['name'] . '" ' . '>';

                // null
                if($field['allow_null'])
                {
                    echo '<option value="null">- ' . __('Select an Album', 'acf') . ' -</option>';
                }

                // loop through values and add them as options
                if (is_array($albums))
                {
                    foreach ($albums as $album)
                    {
                        echo '<option value="' . $album['id'] . '"' . ($album['id'] == $field['value'] ? ' selected="selected"' : '') . '>' . $album['name'] . '</option>';
                    }
                }

                echo '</select>';
            } else {
                echo '<p>Your plugin is misconfigured. Set your Facebook Page URI.</p>';
            }
        } else {
            echo '<p>Your plugin is misconfigured. Set your Facebook Page URI.</p>';
        }
    }

    public function format_value($value, $post_id, $field)
    {
        return $value;
    }

    public function format_value_for_api($value, $post_id, $field)
    {
        $format = $field['save_format'];

        switch ($format) {
            case 'images':
            case 'ul':
            case 'div':
                $continue = false;
                $data = json_decode(file_get_contents('https://graph.facebook.com/' . $value . '/photos'), true);
                $value = array();

                if ($data) {
                    do {
                        if (count($data['data'])) {
                            $value = array_merge($value, (array) $data['data']);
                            if ($continue = array_key_exists('next', $data['paging'])) {
                                $data = json_decode(file_get_contents($data['paging']['next']), true);
                            }
                        } else {
                            $continue = false;
                        }
                    } while ($continue);
                }
                break;

            case 'album':
                $value = json_decode(file_get_contents('https://graph.facebook.com/' . $value), true);
                break;

            case 'id':
            default:
                # do nothing
                break;
        }

        if ($format == 'ul') {
            $images = $value;
            $value = '<ul>';

            foreach ($images as $image) {
                $value .= '<li><a href="' . $image['link'] . '" target="_blank"><img src="' . $image['source'] . '" alt="' . (isset($image['name']) ? $image['name'] : '') . '"></a></li>';
            }

            $value .= '</ul>';
        }

        if ($format == 'div') {
            $images = $value;
            $value = '';

            foreach ($images as $image) {
                $value .= '<div style="background-image: url(' . $image['source'] . ');" onclick="window.open(\'' . $image['link'] . '\');"></div>';
            }
        }

        return $value;
    }
}


// create field
new acf_field_facebook_album();