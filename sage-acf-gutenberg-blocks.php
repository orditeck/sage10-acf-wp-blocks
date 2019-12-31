<?php

namespace Nevek;

use function \Roots\view;
use function \Roots\asset;

// Check whether WordPress and ACF are available; bail if not.
if (
    !function_exists('acf_register_block_type') ||
    !function_exists('add_filter') ||
    !function_exists('add_action')
) {
    return;
}

// Add the default blocks location, 'views/blocks', via filter
add_filter('sage-acf-gutenberg-blocks-templates', function () {
    return ['resources/views/blocks'];
});

/**
 * Create blocks based on templates found in Sage's "views/blocks" directory
 */
add_action('acf/init', function () {
    // Global $sage_error so we can throw errors in the typical sage manner
    global $sage_error;

    // Get an array of directories containing blocks
    $directories = apply_filters('sage-acf-gutenberg-blocks-templates', []);

    // Check whether ACF exists before continuing
    foreach ($directories as $dir) {
        // Sanity check whether the directory we're iterating over exists first
        if (!file_exists(locate_template($dir))) {
            return;
        }

        // Iterate over the directories provided and look for templates
        $template_directory = new \DirectoryIterator(locate_template($dir));

        foreach ($template_directory as $template) {
            if (!$template->isDot() && !$template->isDir()) {

                // Strip the file extension to get the slug
                $slug = removeBladeExtension($template->getFilename());

                // If there is no slug (most likely because the filename does
                // not end with ".blade.php", move on to the next file.
                if (!$slug) {
                    continue;
                }

                // Get header info from the found template file(s)
                $file_path = locate_template($dir . "/${slug}.blade.php");
                $file_headers = get_file_data($file_path, [
                    'title' => 'Title',
                    'description' => 'Description',
                    'category' => 'Category',
                    'icon' => 'Icon',
                    'keywords' => 'Keywords',
                    'mode' => 'Mode',
                    'align' => 'Align',
                    'post_types' => 'PostTypes',
                    'supports_align' => 'SupportsAlign',
                    'supports_mode' => 'SupportsMode',
                    'supports_multiple' => 'SupportsMultiple',
                    'enqueue_style'     => 'EnqueueStyle',
                    'enqueue_script'    => 'EnqueueScript',
                    'enqueue_assets'    => 'EnqueueAssets',
                ]);

                if (empty($file_headers['title'])) {
                    $sage_error(
                        __('This block needs a title: ' . $dir . '/' . $template->getFilename(), 'sage'),
                        __('Block title missing', 'sage')
                    );
                }

                if (empty($file_headers['category'])) {
                    $sage_error(
                        __('This block needs a category: ' . $dir . '/' . $template->getFilename(), 'sage'),
                        __('Block category missing', 'sage')
                    );
                }

                // Checks if dist contains this asset, then enqueues the dist version.
                if (!empty($file_headers['enqueue_style'])) {
                    checkAssetPath($file_headers['enqueue_style']);
                }

                if (!empty($file_headers['enqueue_script'])) {
                    checkAssetPath($file_headers['enqueue_script']);
                }

                // Set up block data for registration
                $data = [
                    'name' => $slug,
                    'title' => $file_headers['title'],
                    'description' => $file_headers['description'],
                    'category' => $file_headers['category'],
                    'icon' => $file_headers['icon'],
                    'keywords' => explode(' ', $file_headers['keywords']),
                    'mode' => $file_headers['mode'],
                    'align' => $file_headers['align'],
                    'render_callback'  => __NAMESPACE__ . '\\sage_blocks_callback',
                    'enqueue_style'   => $file_headers['enqueue_style'],
                    'enqueue_script'  => $file_headers['enqueue_script'],
                    'enqueue_assets'  => $file_headers['enqueue_assets'],
                ];

                // If the PostTypes header is set in the template, restrict this block to those types
                if (!empty($file_headers['post_types'])) {
                    $data['post_types'] = explode(' ', $file_headers['post_types']);
                }

                // If the SupportsAlign header is set in the template, restrict this block to those aligns
                if (!empty($file_headers['supports_align'])) {
                    $data['supports']['align'] = in_array(
                        $file_headers['supports_align'],
                        ['true', 'false'],
                        true
                    ) ?
                        filter_var(
                            $file_headers['supports_align'],
                            FILTER_VALIDATE_BOOLEAN
                        ) : explode(' ', $file_headers['supports_align']);
                }

                // If the SupportsMode header is set in the template, restrict this block mode feature
                if (!empty($file_headers['supports_mode'])) {
                    $data['supports']['mode'] = $file_headers['supports_mode'] === 'true' ? true : false;
                }

                // If the SupportsMultiple header is set in the template, restrict this block multiple feature
                if (!empty($file_headers['supports_multiple'])) {
                    $data['supports']['multiple'] = $file_headers['supports_multiple'] === 'true' ? true : false;
                }

                // Register the block with ACF
                acf_register_block_type($data);
            }
        }
    }
});

/**
 * Callback to register blocks
 * 
 * @param array $block Block element
 * @param string $content Block's content
 * @param bool $is_preview 
 * @param int $post_id
 * 
 * @return string The rendered view
 */
function sage_blocks_callback($block, $content = '', $is_preview = false, $post_id = 0)
{

    // Set up the slug to be useful
    $slug  = str_replace('acf/', '', $block['name']);
    $block = array_merge(['className' => ''], $block);

    // Set up the block data
    $block['post_id'] = $post_id;
    $block['is_preview'] = $is_preview;
    $block['content'] = $content;
    $block['slug'] = $slug;
    // Send classes as array to filter for easy manipulation.
    $block['classes'] = [
        $block['slug'],
        $block['className'],
        $block['is_preview'] ? 'is-preview' : null,
        'align' . $block['align']
    ];

    // Filter the block data.
    $block = apply_filters("sage/blocks/{$slug}/data", $block);

    // Join up the classes.
    $block['classes'] = implode(' ', array_filter($block['classes']));

    // Use Acron's view() function to echo the block and populate it with data
    echo view("blocks/${slug}", [
        'block' => $block,
        'content' => $content,
        'is_preview' => $is_preview,
        'post_id' => $post_id,
    ]);
}

/**
 * Function to strip the `.blade.php` from a blade filename
 * 
 * @param string $filename
 * 
 * @return string|bool
 */
function removeBladeExtension($filename)
{
    // Filename must end with ".blade.php". Parenthetical captures the slug.
    $blade_pattern = '/(.*)\.blade\.php$/';
    $matches = [];

    // If the filename matches the pattern, return the slug.
    if (preg_match($blade_pattern, $filename, $matches)) {
        return $matches[1];
    }

    // Return false if the filename doesn't match the pattern.
    return false;
}

/**
 * Checks asset path for specified asset.
 *
 * @param string &$path
 *
 * @return void
 */
function checkAssetPath(&$path)
{
    if (preg_match("/^(styles|scripts)/", $path)) {
        $path = asset($path)->path();
    }
}
