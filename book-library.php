<?php
/*
Plugin Name: Book Library
Description: Registers a custom post type Book with Author Name and Published Year meta fields.
Version: 1.0
Author: Bibek Paudel
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// 1. Register Custom Post Type: Book
function bl_register_book_post_type() {
    $labels = [
        'name'               => 'Books',
        'singular_name'      => 'Book',
        'menu_name'          => 'Books',
        'name_admin_bar'     => 'Book',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Book',
        'new_item'           => 'New Book',
        'edit_item'          => 'Edit Book',
        'view_item'          => 'View Book',
        'all_items'          => 'All Books',
        'search_items'       => 'Search Books',
        'not_found'          => 'No books found.',
        'not_found_in_trash' => 'No books found in Trash.',
    ];

    $args = [
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => true,
        'show_in_rest'       => true,
        'supports'           => ['title', 'editor', 'thumbnail'],
        'menu_position'      => 20,
        'menu_icon'          => 'dashicons-book',
    ];

    register_post_type('book', $args);
}
add_action('init', 'bl_register_book_post_type');


// 2. Add Meta Box for Author Name and Published Year
function bl_add_book_meta_boxes() {
    add_meta_box(
        'bl_book_details',
        'Book Details',
        'bl_render_book_meta_box',
        'book',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'bl_add_book_meta_boxes');


// 3. Render Meta Box HTML
function bl_render_book_meta_box($post) {
    wp_nonce_field('bl_save_book_meta', 'bl_book_meta_nonce');

    $author_name = get_post_meta($post->ID, '_bl_author_name', true);
    $published_year = get_post_meta($post->ID, '_bl_published_year', true);

    ?>
    <p>
        <label for="bl_author_name">Author Name:</label><br />
        <input type="text" id="bl_author_name" name="bl_author_name" value="<?php echo esc_attr($author_name); ?>" size="25" />
    </p>

    <p>
        <label for="bl_published_year">Published Year:</label><br />
        <input type="number" id="bl_published_year" name="bl_published_year" value="<?php echo esc_attr($published_year); ?>" size="4" min="0" max="<?php echo date('Y'); ?>" />
    </p>
    <?php
}


// 4. Save Meta Box Data
function bl_save_book_meta($post_id) {
    if (!isset($_POST['bl_book_meta_nonce']) || !wp_verify_nonce($_POST['bl_book_meta_nonce'], 'bl_save_book_meta')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['bl_author_name'])) {
        update_post_meta($post_id, '_bl_author_name', sanitize_text_field($_POST['bl_author_name']));
    }

    if (isset($_POST['bl_published_year'])) {
        $year = intval($_POST['bl_published_year']);
        if ($year > 0 && $year <= intval(date('Y'))) {
            update_post_meta($post_id, '_bl_published_year', $year);
        } else {
            delete_post_meta($post_id, '_bl_published_year');
        }
    }
}
add_action('save_post', 'bl_save_book_meta');


// 5. Add Custom Columns to Admin List
function bl_add_book_columns($columns) {
    // Insert Author Name and Published Year after Title column
    $new_columns = [];
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'title') {
            $new_columns['author_name'] = 'Author Name';
            $new_columns['published_year'] = 'Published Year';
        }
    }
    return $new_columns;
}
add_filter('manage_book_posts_columns', 'bl_add_book_columns');


// 6. Populate Custom Columns
function bl_fill_book_columns($column, $post_id) {
    if ($column === 'author_name') {
        $author_name = get_post_meta($post_id, '_bl_author_name', true);
        echo esc_html($author_name);
    }

    if ($column === 'published_year') {
        $published_year = get_post_meta($post_id, '_bl_published_year', true);
        echo esc_html($published_year);
    }
}
add_action('manage_book_posts_custom_column', 'bl_fill_book_columns', 10, 2);
