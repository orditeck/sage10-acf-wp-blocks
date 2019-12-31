# Sage 10 ACF Gutenberg Blocks

Generate ACF Gutenberg blocks just by adding templates to your Sage 10 theme.

This is a fork of [MWDelaney/sage-acf-wp-blocks](https://github.com/MWDelaney/sage-acf-wp-blocks).

## Installation

Run the following in your Sage 10-based theme directory:

```sh
composer require "orditeck/sage10-acf-wp-blocks"
```

## Creating blocks

Add blade templates to `views/blocks` which get and use ACF data. Each template requires a comment block with some data in it:

```blade
{{--
  Title:
  Description:
  Category:
  Icon:
  Keywords:
  Mode:
  Align:
  PostTypes:
  SupportsAlign:
  SupportsMode:
  SupportsMultiple:
--}}
```

### Example block template

```blade
{{--
  Title: Testimonial
  Description: Customer testimonial
  Category: formatting
  Icon: admin-comments
  Keywords: testimonial quote
  Mode: edit
  Align: left
  PostTypes: page post
  SupportsAlign: left right
  SupportsMode: false
  SupportsMultiple: false
--}}

<blockquote data-{{ $block['id'] }} class="{{ $block['classes'] }}">
    <p>{{ get_field('testimonial') }}</p>
    <cite>
      <span>{{ get_field('author') }}</span>
    </cite>
</blockquote>

<style type="text/css">
  [data-{{$block['id']}}] {
    background: {{ get_field('background_color') }};
    color: {{ get_field('text_color') }};
  }
</style>
```

## Data Options

The options in the file header map to options in the [`acf_register_block_type` function](https://www.advancedcustomfields.com/resources/acf_register_block_type/).

| Field              | Description                                                                                                                                                                                                                                                          | Values                                                                 | Notes                                 |
| ------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------- | ------------------------------------- |
| `Title`            | Title of the block in the gutenberg editor                                                                                                                                                                                                                           | i.e. `Testimonial`                                                     | _required_                            |
| `Description`      | Description of the block in the gutenberg editor                                                                                                                                                                                                                     | i.e. `My testimonial block`                                            | _optional_                            |
| `Category`         | Category to store the block in. Use these values or [register your own custom block categories](https://wordpress.org/gutenberg/handbook/extensibility/extending-blocks/#managing-block-categories)                                                                  | `common`, `formatting`, `layout`, `widgets`, `embed`                   | _required_                            |
| `Icon`             | An icon property can be specified to make it easier to identify a block. Uses [dashicons](https://developer.wordpress.org/resource/dashicons/)                                                                                                                       | i.e. `book-alt`                                                        | _optional_                            |
| `Keywords`         | An array of search terms to help user discover the block while searching. Sepearate values with a space.                                                                                                                                                             | i.e. `quote mention cite`                                              | _optional_                            |
| `Mode`             | The display mode for your block. auto: Preview is shown by default but changes to edit form when block is selected. preview: Preview is always shown. Edit form appears in sidebar when block is selected. edit: Edit form is always shown.                          | `auto`, `preview` or `edit`                                            | _optional_ (defaults to `preview`)    |
| `Align`            | The default block alignment.                                                                                                                                                                                                                                         | `left center right wide full`                                          | _optional_ (defaults to empty string) |
| `PostTypes`        | An array of post types to restrict this block type to. Sepearate values with a space.                                                                                                                                                                                | i.e. `post page`                                                       |
| `SupportsAlign`    | This property adds block controls which allow the user to change the block’s alignment. Set to true to show all alignments, false to hide the alignment toolbar. Set to an array (strings separated by spaces) of specific alignment names to customize the toolbar. | (boolean) `true`, `false`<br> or (array) `left center right wide full` | _optional_ (defaults to true)         |
| `SupportsMode`     | This property allows the user to toggle between edit and preview modes via a button.                                                                                                                                                                                 | `true` or `false`                                                      | _optional_ (defaults to `true`)       |
| `SupportsMultiple` | This property allows the block to be added multiple times.                                                                                                                                                                                                           | `true` or `false`                                                      | _optional_ (defaults to `true`)       |

## Creating ACF fields

Once a block is created you'll be able to assign ACF fields to it using the standard Custom Fields interface in WordPress. I recommend using [orditeck/sage10-advanced-custom-fields](https://github.com/orditeck/sage10-advanced-custom-fields) to keep your ACF fields in version control with Sage.

## Filter block data

Block data can be altered via the 'sage/blocks/[block-name]/data' filter. For example, if your block template is called `my-block.blade.php`, you can alter the data this way:

```php
add_filter('sage/blocks/my-block/data', function ($block) { // Do your thing here. });
```
