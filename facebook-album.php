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
            'version' => '1.0',
        );
    }

    function render_field_settings( $field ) {
        // Formatting
        acf_render_field_setting( $field, array(
            'label'			=> __('Return Value','acf'),
            'instructions'	=> __('Specify the returned value on front end','acf'),
            'type'			=> 'radio',
            'name'			=> 'save_format',
            'choices'		=> array(
                'images' => __('Array of images', 'acf'),
                'album'  => __('Album object', 'acf'),
                'id'     => __('Album ID', 'acf'),
                'ul'     => __('List of images', 'acf'),
                'div'    => __('Containers with background', 'acf'),
                'js'     => __('Containers with data-src', 'acf'),
            )
        ));

        // Facebook Page URI
        acf_render_field_setting( $field, array(
            'label'			=> __('Facebook Page URI','acf'),
            'instructions'	=> __('Your Facebook Page URI (if not specified, you\'ll be promped in the post page)','acf'),
            'type'			=> 'text',
            'name'			=> 'facebook_page',
        ));

    }

    public function render_field($field)
    {
        $field    = array_merge($this->defaults, $field);
        $albums   = array();
        $continue = false;

        $page     = $field['facebook_page'];
        $pageName = substr(strrchr(trim($page, '/'), '/'),1);
        $pageID   = json_decode(file_get_contents('https://graph.facebook.com/' . $pageName . '?fields=id'));

        echo '<div class="acf-input-wrap">';

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

        echo '</div>';
    }

    public function format_value($value, $post_id, $field)
    {
        $format = $field['save_format'];

        switch ($format) {
            case 'js':
                $value = "<div data-src=\"https://graph.facebook.com/$value/photos\"></div>";
                break;
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
